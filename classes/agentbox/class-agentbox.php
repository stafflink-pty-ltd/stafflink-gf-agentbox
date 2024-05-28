<?php

namespace GFAgentbox\Agentbox;

use GFAgentbox\Agentbox\AgentBoxClient;
use GFAgentbox\Agentbox\AgentboxContact;

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
    public function __construct( $feed = [], $source = "website", $options = [] )
    {
        $this->_feed    = $feed;
        $this->_source  = $source;
        $this->_options = array_merge( $this->_options, $options );
        $this->_state   = new AgentboxContact( $feed );
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

        // Default behavior: 
        // Check if contact already has a primary owner.
        // Check if there is an agent attached to the feed
        // Check if there is a default agent saved in the settings
        if ( $this->_options['save_primary_owner_default'] ) {
            $contact = $this->contacts( [ '' ] );
            $this->_state->attach_agent( $contact );
        }


        // Create post request for enquiries
        $body = $this->_state->get( $request_type );
        $req  = $client->post( 'enquiry', $body );

        $this->save_transactions( 'Enquiry', 'Create post request for enquiries ', $req );
    }

    /**
     * Create contact endpoint request to Agentbox
     * 
     * HTTP Requests: GET, POST, PUT
     *
     * @param array|string $info variable used as filter or as contact id depending on what was passed
     * @param string $request Http Request type. Default: 'get'
     * @param array $include (optional)  additional information added to response
     * 
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
     * 
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
     *
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
     * 
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
        $this->_steps[] = compact( 'key', 'additional_information', 'http_response' );
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
    }

    /**
     * Get the source
     *
     * @return string
     */
    public function get_source(): string
    {
        return $this->_source;
    }

}