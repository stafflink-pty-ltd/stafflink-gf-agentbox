<?php

namespace GFAgentbox\Agentbox;

use GFAgentbox\Agentbox\AgentBoxClient;

/**
 * AGENTBOX CONTACT CLASS
 * 
 * This class is used as a state management for the inital contact/feed that the user
 * sent through gravity forms. This also serves as the class for the enquiry/contact done.
 */
class AgentboxContact
{

    /**
     * Agentbox Contact
     *
     * @var array
     */
    protected $_contact = array();


    /**
     * Storage for the inital contact that was sent by the Gravity Form feed
     *
     * @var array
     */
    protected $_initial_contact = array();

    /**
     * Primary owner of the contact
     *
     * @var array
     */
    protected $_primary_owner = array();

    /**
     * Comment string
     *
     * @var string
     */
    protected $_comment;

    /**
     * The current request being done, also used for title
     *
     * @var [type]
     */
    protected $_request_type;

    /**
     * Class Constructor
     *
     * @param [type] $contact
     * @param string $request_type
     */
    public function __construct( $contact, $request_type = 'enquiry' )
    {
        $this->_initial_contact = $contact;
        $this->_contact         = $contact;
        $this->_request_type    = $request_type;
    }

    /**
     * Get the current contact state
     * 
     * @param string $request_type
     *
     * @return void
     */
    public function get( $request_type = '' )
    {
        return !empty( $this->_contact ) ? $this->_contact : $this->create_body( $this->_request_type );
    }

    /**
     * Create the body payload for Agentbox
     *
     * @return array
     */
    public function create_body( $request_type ): array
    {
        $this->_request_type = $request_type;

        // replace existing contact when re-creating the body
        $this->_contact = [];

        return [

        ];
    }

    /**
     * Create the comment for the body
     *
     * @return string
     */
    public function create_comment(): string
    {
        $title = ucfirst( $this->_request_type );

        // Comment header
        $comment = "{$title} Details:" . PHP_EOL;
        ;

        // Create comment body
        foreach ( $this->_contact as $type => $value ) {
            $comment .= "{$type}: {$value}" . PHP_EOL;
            ;
        }

        // Write commen footer
        $comment .= "<br /> (this was submitted via Gravity Forms - Agentbox)";

        return $comment;
    }

    /**
     * Get or create a comment string
     *
     * @return string
     */
    public function get_comment(): string
    {
        return $this->_comment == "" ? $this->create_comment() : $this->_comment;
    }


    protected function attach_listing()
    {
        // Attach property id to the enquiry if property_id is available
        if ( rgar( $this->_contact, 'property_id' ) ) {
            // $body['enquiry']['attachedListing']['id'] = $this->_contact['property_id'];
            // $body['enquiry']['attachedContact']['actions']['attachListingAgents'] = true;
        }
    }

    protected function attach_agent( $contact )
    {

    }

    protected function attach_project()
    {

    }
}