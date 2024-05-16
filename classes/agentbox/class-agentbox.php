<?php

namespace GFAgentbox\Agentbox;

use GFAgentbox\Agentbox\AgentBoxClient;
use GFAgentbox\Agentbox\AgentboxContact;

class AgentboxClass
{

    /**
     * Current body being processed
     *
     * @var array
     */
    protected $_current_body = [];

    /**
     * Initial body passed via constructor
     *
     * @var array
     */
    protected $_body = [];

    /**
     * Save client response for future use
     *
     * @var array
     */
    protected $_client_response = [];


    /**
     * Record the steps that we did.
     *
     * @var array
     */
    protected $_steps = [];


    /**
     * Agentbox payload source
     *
     * @var string
     */
    protected $_source;

    /**
     * Feed passed from Gravity Forms
     *
     * @var array
     */
    protected $_gform_feed = [];


    /**
     * Agentbox class constructor
     *
     * @param array $gform_feed
     * @param string $source
     */
    public function __construct( $gform_feed = [], $source = "website" )
    {
        // $this->_attached_contact = 
        $this->_gform_feed = $gform_feed;
        $this->_source     = $source;
    }


    /**
     * POST request for creating Agentbox enquiry.
     *
     * @return void
     */
    public function enquiries()
    {
        $client = new AgentBoxClient();
    }

    protected function create_body()
    {

    }


    protected function create_comment( $body = [] )
    {

    }


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