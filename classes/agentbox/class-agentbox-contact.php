<?php 

namespace GFAgentbox\Agentbox;

class AgentboxContact {

    /**
     * Agentbox Contact
     *
     * @var array
     */
    protected $_contact = array();

    /**
     * Primary owner of the contact
     *
     * @var array
     */
    protected $_primary_owner = array();

    public function __construct( $contact )
    {
        $this->_contact = $contact;
    }

    /**
     * Check if the current contact has a primary owner registered
     *
     * @return boolean
     */
    protected function has_primary_owner() : bool
    {

    }
    
    /**
     * Get the primary owner associated with the contact
     *
     * @return array
     */
    protected function get_primary_owner() : array
    {

    }


    protected function attach_listing()
    {

    }
}