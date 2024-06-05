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
     * Log errors for future purposes
     *
     * @var array
     */
    protected $_errors = [];

    /**
     * Create logs of what the Agentbox stuff is doing
     *
     * @var boolean
     */
    protected $_logging = true;

    /**
     * Create a logger for Agentbox
     *
     * @var [type]
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
            $res = $client->post( 'enquiries', $feed );
            
            // Log results
            if( isset( $res['http'] ) ) {
                $this->_logger->log_error( "(Enquiry Submission) {$res['message']} " );
            } else {
                $this->_logger->log_debug( "(Enquiry Submission) {$res}['response']['code'] {$res['response']['message']}" );
            }
            
            $contact = json_decode( $res['body'] );

            // Do Agent Process next
            if( $this->_state->get_agent_email() !== "" ) {
                $agent_res = $this->attach_agent( $this->_state->get_agent_email() );
            }
            

            // Continue with the enquiry process

            // $this->_logger->log( var_export($contact->response->errors[0], true));

            

        } catch( \Exception $e) {
            $this->_logger->log_error( $e->getMessage() );
        }
        

        // var_dump($body);
        // $req  = $client->post( 'enquiry', $body );

        // $this->save_transactions( 'Enquiry', 'Create post request for enquiries ', $req );
    }

    /**
     * Attach the agent to the enquiry
     *
     * @param string $agent_email
     * @return void
     */
    public function attach_agent( $agent_email )
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
        $is_registered = $this->is_user_registered( $this->_state->get_email() );
        if( ! $is_registered ) {
            return;
        }
    
        // Get Agent ID in agentbox
        $agent_res = $this->get_staff_by_email( $agent_email );
        $agent_id = json_decode($agent_res['body'], true);

        // use OC's process in saving the primary owner
        if( $this->_options['save_primary_owner_default'] ) {
            $this->process_oc_primary_owner( $agent_id,  );
        }
    }

    /**
     * Do the OC process in adding primary owner for contacts
     * 
     * @param array $contact
     * @return void
     */
    public function process_oc_primary_owner( $contact )
    {
        // Check first if contact has primary owner
        $has_primary_owner = $this->contact_has_primary_owner( $contact );
        if( $has_primary_owner ) {
            $this->_logger->log_info( 'Primary owner found, saving listing agent' );

            return;
        }

        // Continue here if no primary owner was found
    }


    public function have_default_primary_owner( $contact )
    {

    }

    public function replace_primary_owner( $contact )
    {

    }

    /**
     * Check if the contact already has a Primary owner or not
     *
     * @param array|string $contact
     * @return boolean
     */
    public function contact_has_primary_owner( $contact )
    {
        // if contact passed is a result of previous agentbox request
        if( isset( $contact['body'] ) ) {
            $contact = json_decode($contact['body']);
    
            // Run through related staff to check if the Primary Owner exists
            if( !empty($contact->response->contacts) ) {
                $relatedStaff = $contact->response->contacts[0]->relatedStaffMembers;
                foreach( $relatedStaff as $staff ) {
                    if( $staff->role == "Primary Owner") {
                        return true;
                    }
                }
            }
        }

        // check if passed argument is an email
        if( is_email( $contact ) ) {
            $user = $this->is_user_registered( $this->_state->get_email() );

            $this->contact_has_primary_owner( $user );
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
     * Check if contact is registered to OC First
     *
     * @param string $user_email
     * @return boolean|array
     */
    public function is_user_registered( $user_email )
    {
        $client  = new AgentBoxClient();
        $contact = $client->get( 'contacts', ['email' => $user_email], ['relatedStaffMembers'] );
        $response  = json_decode($contact['body']);

        if( $response->response->items > 0 ) {
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

        return json_decode( $this->staff( [ 'email' => $email ] ) );
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