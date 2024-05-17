<?php

namespace GFAgentbox\Agentbox;

use GFAgentbox\Agentbox\AgentBoxClient;
use GFAgentbox\Agentbox\AgentboxContact;

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
     * Record the steps that we did.
     *
     * @var array
     */
    protected $_steps = [];

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
        'related_staff_appendable'   => true, // When set to false staff member will be overriden, when set to true related staff will be appended
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

        $this->save_steps( 'Enquiry', 'Create post request for enquiries ', $req );
    }

    /**
     * Create contact endpoint request to Agentbox
     *
     * @param string $request Http Request type
     * @param array|string $info variable used as filter or as contact id depending on what was passed
     * @param array $include additional information added to response
     * 
     * @return array
     */
    public function contacts( $request = 'get', $info, $include = [] )
    {
        $client  = new AgentBoxClient();
        $include = empty( $include ) ? $this->_includes : $include;

        // Pass the information as filter
        // see filters  for contacts in Agentbox
        if ( is_array( $info ) && !empty( $info ) ) {

            $contact = $client->{$request}( 'contacts', $info, $include );
            $this->save_steps( 'Contacts', '', $contact );

            return $contact;
        }

        // Do the request for information passed as contact id
        $contact = $client->{$request}( "contact/{$info}", [], $include );

        return $contact;
    }

    /**
     * Returns staff member records
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
            $this->save_steps( 'Contacts', '', $staff );

            return $staff;
        }

        // Do the request for information passed as contact id
        $staff = $client->get( "staff/{$info}", [], $include );

        return json_decode( $staff );
    }

    /**
     * Add Agentbox includes
     *
     * @param array $includes
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
     * @return void
     */
    public function response( $options = [] )
    {

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
     * @return void
     */
    protected function save_steps( $key, $additional_information, $http_response )
    {
        $this->_steps[] = compact( 'key', 'additional_information', 'http_response' );
    }


    //GETTERS AND SETTERS

    /**
     * Set the source of the Agentbox payload
     *
     * @param string $source
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