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
        'return_type' => 'object',
        'attached_listing' => true,
        'contact_class_appendable' => true,
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
        $body         = $this->_state->get( $request_type );

        // Create post request for enquiries
        $req = $client->post( 'enquiry', $body );
        $this->save_steps( 'Create post request for enquiries ', $req );

        

        // Attach Primary Owner
        if( isset( $this->_feed['agent_id'] ) ) {

        }
    }

    public function inlude() 
    {

    }


    public function attach_property()
    {
        // Attach property id to the enquiry if property_id is available
        if(  rgar( $this->_feed, 'property_id') )  {
            $body['enquiry']['attachedListing']['id'] = $this->_feed['property_id'];
            $body['enquiry']['attachedContact']['actions']['attachListingAgents'] = true;
        }
    }

    // public function 

    public function contact( $email )
    {
        
    }

    public function response( $options = [] )
    {

    }

    /**
     * Undocumented function
     *
     * @param string $member_id
     * @return array
     */
    public function get_staff( $member_id = "" )
    {
        $client = new AgentBoxClient();

        $req = $client->get( 'staff', array( 'email' => $member_id));



        return [];
    }
    

    // LOGGING AND STUFF

    /**
     * Save the steps for future logging purposes
     *
     * @param string $message
     * @param string|array $additional_information
     * @return void
     */
    protected function save_steps( $message, $additional_information )
    {
        $this->_steps[] = compact( 'message', 'additional_information');
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