<?php
namespace GFAgentbox\Agentbox;

use GFAgentbox\Agentbox\AgentBoxClient;
use GFAgentbox\Agentbox\AgentboxContact;
use GFAgentbox\Inc\StafflinkLogger;

/**
 * AGENTBOX WRAPPER CLASS
 * 
 * Each Agentbox endpoints are represented by methods.
 * We will only add methods here if the plugin requires you to, as of now
 * not all endpoints are being used to integration with agentbox, we don't
 * need to add them all.
 */
class AgentboxClass
{
    /**
     * AgentboxContact
     *
     * @var AgentboxClient
     */
    protected $_state = [];

    /**
     * Save the initial feed passed to the class
     *
     * @var array
     */
    protected $_feed = [];

    /**
     * Record the transactions that we did.
     *
     * @var array
     */
    protected $_transactions = [];

    /**
     * Create a logger for Agentbox
     *
     * @var StafflinkLogger
     */
    protected $_logger;

    /**
     * Agentbox payload source, defaults to website
     *
     * @var string
     */
    protected $_source;

    /**
     * Class options
     *
     * @var array
     */
    protected $_options = [ 
        'return_type'                => 'object',
        'attached_listing'           => true,
        'contact_class_appendable'   => true, // Used for contact classes saving
        'save_primary_owner_default' => true, // Used for using default OC behavior in saving and checking for primary owners
        'related_staff_appendable'   => true, // When set to false staff member will be overridden, when set to true related staff will be appended
    ];

    /**
     * Save more gravity form information
     *
     * @var array
     */
    private $_gravityforms = [];

    /**
     * Additional objects to be included in the Agentbox response
     *
     * @var array
     */
    protected $_includes = [];

    /**
     * Save the last transaction done in the query
     *
     * @var array
     */
    protected $_last_transaction = [];

    /**
     * Agentbox class constructor
     *
     * @param array $feed
     * @param string $source Default value: 'website'
     * @param array $options
     */
    public function __construct( $feed = [], $options = [], $source = "website"  )
    {
        $this->_feed    = $feed;
        $this->_source  = $source;
        $this->_options = array_merge( $this->_options, $options );
        $this->_state   = new AgentboxContact( $feed );
        $this->_logger  = new StafflinkLogger;
    }

    /**
     * Save information from gravity forms as additional settings
     *
     * @param array $args
     * @return void
     */
    public function gravity_form( $args )
    {
        $this->_gravityforms = array_merge( $this->_gravityforms, $args);
    }

    /**
     * POST request for creating Agentbox enquiry.
     *
     * @return void
     */
    public function enquiries()
    {
        $request_type = 'enquiry';
        $client       = new AgentBoxClient();

        // Create post request for enquiries
        $feed = $this->_state->get( $request_type );

        try {
            // Send Enquiry
            $this->_logger->log( 'Creating Enquiries for: ' . $this->_state );
            $enquiry_res = $client->post( 'enquiries', $feed );

            // Log results
            if( isset( $enquiry_res['http'] ) ) {
                $this->_logger->log_error( "(Enquiry Submission) {$enquiry_res['message']} " );
            } else { 
                $this->_logger->log_debug( "(Enquiry Submission) {$enquiry_res['response']['code']} {$enquiry_res['response']['message']}" );
            }
            
            $enquiry_contact = json_decode( $enquiry_res['body'] );

            // Do Agent Process next
            if( $this->_state->get_agent_email() !== "" ) {
                $agent_res = $this->attach_agent( $enquiry_contact );
            }
            

            // Continue with the enquiry process
            
            

        } catch( \Exception $e) {
            $this->_logger->log_error( $e->getMessage() );
        }
    }

    /**
     * Attach the agent to the enquiry
     *
     * @param array $user_contact Enquiry result or contact request result
     * @return void
     */
    public function attach_agent( $user_contact )
    {
        // Default behavior: 
        // Check if contact already has a primary owner.
        // Check if there is an agent attached to the feed
        // Check if there is a default agent saved in the settings
        // if ( $this->_options['save_primary_owner_default'] ) {
        //     $contact = $this->contacts( [ '' ] );
        //     $this->_state->attach_agent( $contact );
        // }

        // Check if user is registered, quickly return if not
        if( ! $user_contact ) {
            return;
        }

        // Check if passed enquiry is the raw http response from agentbox
        if( is_array($user_contact) && isset($user_contact['body'])) {
            $user_contact = json_decode( $user_contact['body'] );
        }

        // Process user
        if( $user_contact instanceof \stdClass ) {
            if( property_exists($user_contact, "response") ) {
                $this->_logger->log_array($user_contact);
                $enquiry = $user_contact->response;
            }
        }

        // use OC's process in saving the primary owner
        if( $this->_options['save_primary_owner_default'] ) {
            $this->process_oc_primary_owner( $enquiry );
        }
    }

    /**
     * Do the OC process in adding primary owner for contacts
     * 
     * @param array $agentbox_enquiry_response Response from Agentbox enquiry
     * @return array returns the update results
     */
    protected function process_oc_primary_owner( $agentbox_enquiry_response )
    {
        $_registered = $this->is_user_registered( $this->_state->get_email() );
        $agent_email = $this->_state->get_agent_email();
        

        // Check first if contact has primary owner
        $has_primary_owner  = $this->contact_has_primary_owner( $_registered );
        $have_default_owner = $this->have_default_primary_owner( $_registered );

        // REPLACE primary owner if default is found
        if( $have_default_owner && $has_primary_owner ) {
            $this->_logger->log_info( "Default Primary owner found: {$this->_options['global_default_primary_owner']}" );
            
            // check if agent email exists
            $this->update_primary_owner( $agentbox_enquiry_response );

            return [];
        }

        // RETURN if primary owner detected
        if( !$have_default_owner && $has_primary_owner ) {
            $this->_logger->log_info( 'Primary owner found' );
            return [];
        }

        // Update ownership if no primary owner found
        $this->update_primary_owner( $agentbox_enquiry_response );
    }

    /**
     * Check if the contact have the default primary owner as its primary owner
     *
     * @param string|array $contact  The enquiry contact
     * @return boolean
     */
    public function have_default_primary_owner( $contact )
    {
        if( ! isset($this->_options['global_default_primary_owner']) && $this->_options['global_default_primary_owner'] == "" ) {
            return false;
        }

        if( is_array( $contact ) && isset( $contact['response'] ) ) {
            $contact = json_decode($contact['body']);
        }

        $default_primary_owner = $this->_options['global_default_primary_owner'];
        
        // if contact passed is a result of previous agentbox request
        if( $contact instanceof \stdClass) {
            if( property_exists($contact, "response") ) {
                $this->_logger->log_array($contact);
                $user = $contact->response->contacts[0];

                $primary_owner = $this->contact_has_primary_owner( $user );
            }

            return $default_primary_owner == $primary_owner;
        }

        // check if passed argument is an email
        if ( is_email( $contact ) ) {
            $user = $this->is_user_registered( $this->_state->get_email() );

            $agent = $this->contact_has_primary_owner( $user, false );

            // recursive method -> pass the stdClass to method
            $this->have_default_primary_owner($agent);
        }

 
        return false;
    }

    /**
     * Update the primary owner
     *
     * @param string $agentbox_contact 
     * @param string $agent_email Default: null. Saves the agent email as primary owner
     * @return void
     */
    public function update_primary_owner( $agentbox_contact, $agent_email = null )
    {
        $agent_email = $agent_email ?? $this->_state->get_agent_email();
        $agent_res = $this->get_staff_by_email( $agent_email );

        $this->_logger->log_info( 'Adding primary owner: ' . $agent_email );
        $contact_id = "";


        // READ: Main point of this code is to get the ID of the contact/enquirer in agentbox
        //       then pass it to the PUT request for the primary owner processing


        // ACCEPTS: Agentbox stdClass        
        // Main code block to process the contact_id. All succeeding code block loops here.
        if ( $agentbox_contact instanceof \stdClass ) {
            if ( property_exists( $agentbox_contact, "enquiry" ) ) {
                $contact_id = $agentbox_contact->enquiry->contact->id;
            }
        }

        // ACCEPTS: Agentbox raw response
        // if contact passed is a result of previous agentbox enqjuiry request.
        if ( is_array( $agentbox_contact ) && isset( $agentbox_contact['body'] ) ) {
            $contact = json_decode( $agentbox_contact['body'] );

            // Run through related staff to check if the Primary Owner exists
            if ( !empty( $contact->response ) ) {
                $this->update_primary_owner( $contact->response );
            }
        }

        // ACCEPTS: email
        // check if passed argument is an email
        if ( !( $agentbox_contact instanceof \stdClass ) ) {
            if ( is_email( $agentbox_contact ) ) {
                $contact      = $this->contacts( [ 'email' => $agentbox_contact ] );
                $contact_body = json_decode( $contact['body'] );
                $contact_id = $contact_body->response->contacts[0]->id;
            }
        }

        // Get agent ID
        $agent_id = $agent_res->id;

        $contact_body = [ 
            'contact' => [ 
                "attachedRelatedStaffMembers" => [ 
                    [ 
                        'role' => 'Primary Owner',
                        'id'   => $agent_id,
                    ],
                ],
            ],
        ];


        if( $contact_id !== "") {
            $this->put_contacts( $contact_id, $contact_body );
        } else {
            $this->_logger->log_error( "no contact id" );
        }
        
    }

    /**
     * Sends a put request to the contacts endpoint
     *
     * @param array $update_payload
     * @return void
     */
    public function put_contacts( $contact_id, $update_payload )
    {
        $client = new AgentBoxClient();
        $update = $client->put( "contacts/{$contact_id}", $update_payload );

        // Log the results
        if( isset( $update['http'] ) ) {
            $this->_logger->log( "(Enquiry Submission) {$update['message']} " );
        } else {    
            $this->_logger->log_debug( "(Enquiry Submission) {$update['response']['code']} {$update['response']['message']}" );
        }
    }

    /**
     * Check if the contact already has a Primary owner or not
     *
     * @param array|string $contact
     * @param boolean $return_bool Default: true. Indicates the type of data this method will return
     * @return boolean|array
     */
    public function contact_has_primary_owner( $contact, $return_bool = true )
    {
        // if contact passed is a result of previous agentbox request
        if( is_array($contact) && isset( $contact['body'] ) ) {
            $contact = json_decode($contact['body']);
    
            // Run through related staff to check if the Primary Owner exists
            if( !empty($contact->response->contacts) ) {
                $relatedStaff = $contact->response->contacts[0]->relatedStaffMembers;
            }
        }

        if( $contact instanceof \stdClass ) {
            if( property_exists($contact, "relatedStaffMembers") ) {
                $relatedStaff = $contact->relatedStaffMembers;
            }
        }

        // check if passed argument is an email
        if( !( $contact instanceof \stdClass ) ) {
            if(  is_email( $contact ) ) {
                $user = $this->is_user_registered( $this->_state->get_email() );
    
                $this->contact_has_primary_owner( $user );
            }
        }
    
        // Process checking of related staffs
        foreach( $relatedStaff as $staff ) {
            if( $staff->role == "Primary Owner") {
                if( $return_bool ) {
                    return true;
                }
                return $contact;
            }
        }
        
    
        return false;
    }

    /**
     * Check if contact already exists in Agentbox
     *
     * @param string $email
     * @return boolean
     */
    public function is_contact_exists( $email )
    {
        $user = $this->is_user_registered( $email );
        
        return isset($user['body']) ?: false;
    }

    /**
     * Get response
     *
     * @param string $agentbox_http_response JSON string of the body
     * @param string $key
     * @return array|string
     */
    protected function get_body( $agentbox_http_response, $key = "" )
    {
        $response = json_decode($agentbox_http_response);
        $contact = $response->response->contacts[0];

        // if no key is available, return all response
        if ( $key == "" ) {
            return $contact;
        }

        if( property_exists( $contact, $key  ) ) {
            return $contact->{$key};
        }

        return $response;
    }

    /**
     * Check if contact is registered to OC First
     *
     * @param string $user_email
     * @param string $return_bool return type of the method, returns boolean or http_response
     * @return boolean|array
     */
    public function is_user_registered( $user_email, $return_bool = false )
    {
        $client  = new AgentBoxClient();
        $contact = $client->get( 'contacts', ['email' => $user_email], ['relatedStaffMembers'] );
        $response  = json_decode($contact['body']);

        if( $response->response->items > 0 ) {
            $this->_logger->log_info( "{$user_email} is saved in Agentbox" );
            // Return true if we need a boolean response instead of the result
            if( $return_bool ) {
                return true;
            }
            return $contact;
        }

        return false;
    }


    /**
     * Create contact endpoint request to Agentbox
     * 
     * HTTP Requests: GET, POST, PUT
     *
     * @param array|string $info variable used as filter or as contact id depending on what was passed
     * @param string $request Http Request type. Default: 'get'
     * @param array $include (optional)  additional information added to response
     * @return array
     */
    public function contacts( $info, $request = 'get', $include = [] )
    {
        $client  = new AgentBoxClient();
        $include = empty( $include ) ? $this->_includes : $include;

        // Pass the information as filter
        // see filters  for contacts in Agentbox
        if ( is_array( $info ) && !empty( $info ) ) {

            $contact = $client->{$request}( 'contacts', $info, $include );
            $this->save_transactions( 'Contacts', '', $contact );

            return $contact;
        }

        // Do the request for information passed as contact id
        $contact = $client->{$request}( "contact/{$info}", [], $include );

        return $contact;
    }

    /**
     * Returns staff member records
     * 
     * HTTP Requests: GET
     *
     * @param array|string $info variable used as filter or as contact id depending on what was passed
     * @param array $include (optional) additional information added to response
     * @return array
     */
    public function staff( $info, $include = [] )
    {
        $client  = new AgentBoxClient();
        $include = empty( $include ) ? $this->_includes : $include;

        // Pass the information as filter
        // see filters  for contacts in Agentbox
        if ( is_array( $info ) && !empty( $info ) ) {

            $staff = $client->get( 'staff', $info, $include );
            $this->save_transactions( 'Contacts', '', $staff );

            return $staff;
        }

        // Do the request for information passed as contact id
        $staff = $client->get( "staff/{$info}", [], $include );

        return json_decode( $staff );
    }

    /**
     * Returns a set of contact classes
     * 
     * HTTP Requests: GET
     * 
     * @param string $filter (optional) Fitler results by contact classes
     * @param array $include (optional) Output addtional object in the response
     * @return void
     */
    public function contact_classes( $filter = "", $include = [] )
    {
        $client = new AgentBoxClient();
        $include = empty( $include ) ? $this->_includes : $include;

        

    }


    /**
     * Returns a recrod of projects
     *
     * @param string $project_id (optional) Unique project id
     * @param array $includes (optional) Output additional objects in the response.
     * @return array
     */
    public function projects( $project_id = null, $includes = [] )
    {
        $client = new AgentBoxClient();
        $include = empty( $include ) ? $this->_includes : $include;

        $endpoint = ( $project_id ) ? "projects" : "projects/{$project_id}";

        $project = $client->get( $endpoint );
    }

    /**
     * Add Agentbox includes, using this will replace the include array everytime.
     *
     * @param array $includes
     * 
     * @return void
     */
    public function inlude( $includes = [] )
    {
        $this->_include = $includes;
    }

    /**
     * Get the result of the chained queries
     *
     * @param array $options response type
     * 
     * @return void
     */
    public function response( $options = [] )
    {
        if( isset( $options['response_type'] ) && 'json' == $options['response_type'] ) {

        }

        if( isset( $options['response_type'] ) && 'array' == $options['response_type'] ) {

        }

        if( isset( $options['response_type'] ) && 'object' == $options['response_type'] ) {

        }
    }

    /**
     * Wrapper method for getting staff using email
     *
     * @param string $email staff email§
     * @return array|boolean
     */
    public function get_staff_by_email( $email )
    {
        // return false if passed arg is an invalid email format
        if ( ! is_email( $email ) ) {
            return false;
        }

        $agent_info = $this->staff( [ 'email' => $email ] );
        $agent_info = json_decode( $agent_info['body'] );

        return $agent_info->response->staffMembers[0];
    }


    // LOGGING AND STUFF

    /**
     * Save the steps for future logging purposes
     *
     * @param string $message
     * @param string|array $additional_information
     * 
     * @return void
     */
    protected function save_transactions( $key, $additional_information, $http_response )
    {
        // $this->_steps[] = compact( 'key', 'additional_information', 'http_response' );
    }


    //GETTERS AND SETTERS

    /**
     * Set the source of the Agentbox payload
     *
     * @param string $source
     * 
     * @return void
     */
    public function set_source( $source )
    {
        $this->_source = $source;
        $this->_state->set_source( $source );
    }

    /**
     * Get the source
     *
     * @return string
     */
    public function get_source() : string
    {
        return $this->_source;
    }

    /**
     * Get the agentbox contact state
     *
     * @return array
     */
    public function get_agentbox_contact()
    {
        return $this->_state->get();
    }

}