<?php

namespace GFAgentbox\Agentbox;

use GFAgentbox\Agentbox\AgentBoxClient;
use GFAgentbox\Agentbox\AgentboxClass;

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
     * @var string
     */
    protected $_request_type;

    /**
     * Pas the current agentbox process being used
     *
     * @var AgentboxClass
     */
    protected $agentbox;

    /**
     * Enquiry Source
     *
     * @var string
     */
    protected $_source = "website";

    /**
     * Contact information of the feed
     *
     * @var string
     */
    protected $_attached_contact;

    /**
     * Body of payload
     *
     * @var string
     */
    protected $_body;

    /**
     * Class Constructor
     *
     * @param [type] $contact
     * @param string $request_type
     */
    public function __construct( $contact, $request_type = 'enquiry', AgentboxClass $agentbox = null )
    {
        $this->_initial_contact = $contact;
        $this->_contact         = $this->create_body( $request_type );
        $this->_request_type    = $request_type;
        $this->agentbox         = $agentbox;
    }

    /**
     * Get the current contact state
     * 
     * @param string $request_type
     *
     * @return array
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

        //Extract Agentbox required fields
        $firstName = $this->get_first_name() ?: "";
        $lastName  = $this->get_last_name() ?: "";
        $email     = $this->get_email() ?: "";
        $mobile    = $this->get_mobile() ?: "";



        $this->_attached_contact = [ 
            'firstName' => $firstName,
            'lastName'  => $lastName,
            'email'     => $email,
            'mobile'    => $mobile,
        ];

        $comment = $this->create_comment();

        // Create body for enquiry
        $body = [ 
            $this->_request_type => [ 
                "comment"         => $comment,
                "source"          => $this->_source,
                "attachedContact" => $this->_attached_contact,
            ]
        ];
        
        // Extract Agency information
        $agent_email = $this->get_agent_email();
        $property = $this->get_property();

        if( $property !== "" ) {
            $body['enquiry']["attachedListing"]["id"] = $property;
            $body['enquiry']['attachedContact']['actions']['attachListingAgents'] = true;
        }


        return $body;
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
        $comment = "{$title} Details: <br />" . PHP_EOL;

        // Create comment body
        foreach ( $this->_initial_contact as $type => $value ) {
            if( is_array( $value ) ) {
                $val = implode( ', ', $value);
                $comment .= "{$type}: {$val} <br />" . PHP_EOL;
            } else {
                $comment .= "{$type}: {$value} <br />" . PHP_EOL; 
            }
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
    public function get_comment() : string
    {
        return $this->_comment == "" ? $this->create_comment() : $this->_comment;
    }


    /**
     * Check if listing exists. then get the property agentbox id
     *
     * @return string
     */
    protected function get_property()
    {
        // If the properties agentbox was sent by user, return with the result quickly
        if ( rgar( $this->_initial_contact, 'Property Agentbox ID' ) !== "" ) {
            return rgar( $this->_initial_contact, 'Property Agentbox ID' );
        }
        
        //
        if ( rgar( $this->_initial_contact, 'Property Address' ) !== "" ) {
            $address = rgar( $this->_initial_contact, 'Source' );
            return get_unique_id_by_listing($address);
        }

        if ( rgar( $this->_initial_contact, 'property_id' ) !== "" ) {
            
        }
    }

    protected function attach_agent( $contact )
    {
        if ( rgar( $this->_contact, 'Agent Email' ) ) {
            var_dump( $this->_contact['Agent Email'] );
        }
    }

    protected function attach_project()
    {
        if ( rgar( $this->_contact, 'property_id' ) ) {
            // $body['enquiry']['attachedListing']['id'] = $this->_contact['property_id'];
            // $body['enquiry']['attachedContact']['actions']['attachListingAgents'] = true;
        }
    }

    // GETTERS AND SETTER

    /**
     * Extract first name dynamically
     *
     * @return string|null
     */
    public function get_first_name()
    {
        return rgar( $this->_initial_contact, 'First Name' );
    }

    /**
     * Extract last name dynamically
     *
     * @return string|null
     */
    public function get_last_name()
    {
        return rgar( $this->_initial_contact, 'Last Name' );
    }

    /**
     * Extract Email from feed
     *
     * @return string|null
     */
    public function get_email()
    {
        return rgar( $this->_initial_contact, 'Email' );
    }

    /**
     * Extract Mobile from the feed
     *
     * @return string|null
     */
    public function get_mobile()
    {
        return rgar( $this->_initial_contact, 'Mobile' );
    }

    /**
     * Extract Agent Email from Feed
     *
     * @return string|null
     */
    public function get_agent_email()
    {
        return rgar( $this->_initial_contact, 'Last Name' );
    }

    /**
     * Set source for contact
     *
     * @param string $source
     * @return void
     */
    public function set_source( $source )
    {
        $this->_source = $source;
    }

    /**
     * String value of this object
     *
     * @return string
     */
    public function __toString()
    {
         //Extract Agentbox required fields
         $firstName = $this->get_first_name() ?: "";
         $lastName  = $this->get_last_name() ?: "";
         $email     = $this->get_email() ?: "";
         $mobile    = $this->get_mobile() ?: "";

         return $firstName . " " . $lastName . " - " . $email;
    }
}