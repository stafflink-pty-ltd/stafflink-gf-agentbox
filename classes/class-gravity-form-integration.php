<?php

/**
 * Combine all the functions and check if we should do something with it.
 *
 * @param  array $entry The Gravity Form entry Object.
 * @param  array $form The Gravity Form form object.
 * @return void
 */
function compose_gravity_forms_submission_for_agentbox( $entry, $form ) {
	err( 'OC First signup form submitted...' );

	$body       = prepare_gforms_data_for_agentbox( $entry, $form );
	$contact_id = get_agentbox_contact_id_by_email( $body['Email Address'] );

	// If no contact found - create a contact object in Agentbox
	if ( ! $contact_id ) {

		$create_contact_fields = prepare_form_submission_to_create_contact( $body );
		$contact_id            = post_agentbox_contact( $create_contact_fields );

	} else {
		// Update existing contact.
		$create_contact_fields = prepare_form_submission_to_create_contact( $body );
		$updated_contact       = update_existing_contact_marketing( $contact_id, $create_contact_fields );
	}

	// Sign the contact up to Contact Requirements (buy alerts).
	if ( isset( $body['Buyer Alerts'] ) && 'Buyer Alerts' === $body['Buyer Alerts'] ) {

		$prepare           = prepare_form_submission_for_contact_requirements( $body, $contact_id );
		$sent_requirements = post_contact_requirements_to_agentbox( $prepare );

		if ( ! $sent_requirements ) {
			err( 'Couldn\'t send contact requirements.' );
		}
	} else {
		err( 'skipping buyer requirements, user didn\'t sign up to buyer alerts...' );
	}

	$cookie = setcookie( 'ocfirst', $body['Email Address'], time() + ( 86400 * 30 ), '/' );
	if ( ! $cookie ) {
		err( 'cookie couldn\'t be set. Failed.' );
	}
}
add_action( 'gform_after_submission_24', 'compose_gravity_forms_submission_for_agentbox', 10, 2 );


/**
 * Checks agentbox to see if the user already exists.
 *
 * @param string $email the contacts' email address.
 * @return string|false returns a user ID if they exist, or false.
 */
function get_agentbox_contact_id_by_email( $email ) {

	$agentbox      = new ocre\Agentbox_Contact();
	$check_contact = $agentbox->get( 'contacts', array( 'email' => $email ) );

	if ( 404 === $check_contact['response']['code'] ) {
		err( '404 in get_agentbox_contact_id_by_email();' );
		return false;
	}

	$body = json_decode( $check_contact['body'] );

	if ( $body->response->contacts[0]->email !== $email ) {
		err( 'Couldn\'t find user.' );
		return false;
	}
	return $body->response->contacts[0]->id;
}

/**
 * Get agentbox user by email address.
 *
 * @param  string $email The email address of the staff member.
 * @return false|int returns an agent ID or false if they aren't found.
 */
function get_agentbox_staff_id_by_email( $email ) {

	if ( ! is_email( $email ) ) {
		return false;
	}

	$agentbox    = new ocre\Agentbox_Contact();
	$check_staff = $agentbox->get( 'staff', array( 'email' => $email ) );

	if ( 404 === $check_staff['response']['code'] ) {
		err( '404 in get_agentbox_staff_id_by_email();' );
		return false;
	}

	$body = json_decode( $check_staff['body'] );


	// if no staff member found, return false
	if( empty( $body->response->staffMembers) ) {
		err( 'couldn\'t confirm staff member. emails didn\'t match. exiting.', 0 );
		return false;
	}

	// if email does not match, return false;
	if ( $body->response->staffMembers[0]->email !== $email ) {
		err( 'couldn\'t confirm staff member. emails didn\'t match. exiting.', 0 );
		return false;
	}

	return $body->response->staffMembers[0]->id;
}

/**
 * Adds contact to agentbox. Returns the Contact ID if success, otherwise return is false.
 *
 * @param  array $body The form body.
 * @return false|int returns a contact ID or false if they aren't found.
 */
function post_agentbox_contact( $body ) {

	$agentbox = new ocre\Agentbox_Contact();
	$response = $agentbox->post( 'contacts', $body );

	if ( false === $response ) {
		err( 'post_agent_contact failed.' );
		return false;
	}
	$contact = json_decode( $response['body'] );

	if ( isset( $contact->response->status ) && 'success' === $contact->response->status ) {
		return $contact->response->contact->id;
	} else {
		// If we can't create contact, remove mobile and try again.
		if ( $contact->response->errors ) {
			$body['contact']['comments'] = 'Couldn\'t add mobile number to this contact,
            because it\'ts in use within another contact. Number entered: ' . $body['contact']['mobile'];

			unset( $body['contact']['mobile'] );
			$response = $agentbox->post( 'contacts', $body );
			$contact  = json_decode( $response['body'] );

			if ( $contact->response->errors ) {
				err( 'error: ' . $contact->response->errors[0]->detail );
			}

			if ( isset( $contact->response->status ) && 'success' === $contact->response->status ) {
				return $contact->response->contact->id;
			}
		}
	}
}

/**
 * Take data from Gravity Forms hook and put it into a usable array.
 *
 * @param  array $entry The form entry.
 * @param  array $form The form data.
 * @return array|void returns an array to be sent to Agentbox or void.
 */
function prepare_gforms_data_for_agentbox( $entry, $form ) {
	err( 'preparing form submission for agentbox...' );
	$body = array();

	foreach ( $form['fields'] as $field ) {
		$inputs = $field->get_entry_inputs();
		if ( is_array( $inputs ) ) {
			foreach ( $inputs as $input ) {
				$value = rgar( $entry, (string) $input['id'] );
				if ( $value ) {
					$body[ $input['label'] ] = $value;
				}
			}
		} else {
			$value = rgar( $entry, (string) $field->id );
			if ( $value ) {
				$body[ $field->label ] = $value;
			}
		}
	}
	err( 'done.' );
	return $body;
}

/**
 * Grabs the form submission data ($body) and structures it so the API can use it.
 *
 * @param  array  $body The body to be submitted to Agentbox.
 * @param  string $contact_id the ID number of the contact if they exist.
 * @return array The results.
 */
function prepare_form_submission_for_contact_requirements( $body, $contact_id = '' ) {

	err( 'Preparing form submission for contact requirements...' );

	$requirements = array(
		'listingType'  => 'Sale',
		'propertyType' => 'Residential',
	);

	$price = array(
		'from' => $body['Minimum Price Search'],
		'to'   => $body['Maximum Price Search'],
	);

	$cats = explode( ',', $body['What type of property are you looking for?'] );
	$cats = preg_replace( '/[^a-zA-Z \/ 0-9]+/', '', $cats );

	$categories = array();
	foreach ( $cats as $category ) {
		$categories[] = array(
			'name' => $category,
		);
	}

	$suburb_list = array();
	$suburbs     = explode( ',', $body['What suburbs are you interested in'] );
	$suburbs     = preg_replace( '/[^a-zA-Z \/ 0-9]+/', '', $suburbs );

	foreach ( $suburbs as $suburb ) {
		$suburb_list[] = array(
			'name'  => $suburb,
			'state' => 'SA',
		);
	}

	( 'Surrounding Suburbs' === $body['Surrounding Suburbs'] ) ? $surrounding_suburbs = true : $surrounding_suburbs = false;
	( $contact_id ) ? $requirements['contactId']                                      = $contact_id : null;
	( $categories ) ? $requirements['propertyCategories']                             = $categories : null;
	( $body['Minimum Price Search'] && $body['Maximum Price Search'] ) ? $requirements['price'] = $price : null;
	( $body['Bedrooms'] ) ? $requirements['bedrooms']              = array( 'from' => $body['Bedrooms'] ) : null;
	( $body['Bathrooms'] ) ? $requirements['bathrooms']            = array( 'from' => $body['Bathrooms'] ) : null;
	( $suburb_list ) ? $requirements['suburbs']                    = $suburb_list : null;
	( $surrounding_suburbs ) ? $requirements['surroundingSuburbs'] = $surrounding_suburbs : null;

	$search_requirements = array( 'searchRequirement' => $requirements );

	err( 'Done.' );
	return $search_requirements;
}

/**
 * Sends the users data to agentbox.
 *
 * @param array $requirements the users' requirement
 * @return bool|int Returns false OR agent ID if successful.
 */
function post_contact_requirements_to_agentbox( $requirements ) {
	err( 'Posting contact requirements to Agentbox...' );
	$agentbox            = new ocre\Agentbox_Contact();
	$search_requirements = $agentbox->post( 'search-requirements', $requirements );

	if ( false === $search_requirements ) {
		error_log( 'post_agent_contact failed.', 0 );
		return false;
	} else {
		err( 'Sending complete, checking result...' );
	}

	$response = json_decode( $search_requirements['body'] );

	if ( 'success' === $response->response->status ) {
		err( 'Success.' );
		return $response->response->searchRequirement->id;
	} else {
		err( 'Failed to create requirement in Agentbox.' );
		err( $response );
		return false;
	}

}

/**
 * Checks if values exist, then adds them to an array in order to be sent to Agentbox
 * to create a contact.
 *
 * @param array $body The form submission body.
 * @return array The modified body.
 */
function prepare_form_submission_to_create_contact( $body ) {

	err( 'Preparing form submission to create contact...' );
	$return = array();

	( $body['First Name'] ) ? $return['firstName'] = $body['First Name'] : null;
	( $body['Last Name'] ) ? $return['lastName']   = $body['Last Name'] : null;
	( $body['Email Address'] ) ? $return['email']  = $body['Email Address'] : null;
	( $body['Mobile Number'] ) ? $return['mobile'] = $body['Mobile Number'] : null;
	( isset($body['Buyer Alerts']) &&  $body['Buyer Alerts'] ) ? $return['subscriptions'][]         = array( 'name' => 'Buyer Alerts' ) : null;
	( isset($body['Weekly On The Market']) && $body['Weekly On The Market'] ) ? $return['subscriptions'][] = array( 'name' => 'Weekly On The Market' ) : null;
	( $body['OC First Alerts'] ) ? $return['subscriptions'][]      = array( 'name' => 'Off-Market Alert' ) : null;

	$agent_name = $body['Agent in Contact'] ?? $body['Agent Name'];

	if( isset($body['Have you dealt with an Ouwens Casserly agent before or is there an agent you would prefer to deal with?'] ) || $agent_name !== "" ) {
		if ( 'yes' === $body['Have you dealt with an Ouwens Casserly agent before or is there an agent you would prefer to deal with?'] ) {
			$agent_id = get_agentbox_staff_id_by_email( $agent_name );
			( $agent_id ) ? $return['attachedRelatedStaffMembers'][] = array(
				'id'   => $agent_id,
				'role' => 'Primary Owner',
			) : null;
		} else {
			$agent_id = get_agentbox_staff_id_by_email( 'enquiries@ocre.com.au' );
			$return['attachedRelatedStaffMembers'][] = array(
				'id'   => $agent_id,
				'role' => '',
			);
		}
	// if no agent selected and user submitted early
	} else {
		$agent_id = get_agentbox_staff_id_by_email( 'enquiries@ocre.com.au' );
		$return['attachedRelatedStaffMembers'][] = array(
			'id'   => $agent_id,
			'role' => '',
		);
	}
	
	$contact = array( 'contact' => $return );
	err( 'Done.' );
	return $contact;
}

/**
 * Updates the users' marketing preferences.
 *
 * @param  int   $contact_id The ID of the contact from Agentbox.
 * @param  array $update Array of options to update.
 * @return int|false Returns an ID or false if it fails.
 */
function update_existing_contact_marketing( $contact_id, $update ) {
	err( 'Updating existing contact marketing...' );
	$agentbox = new ocre\Agentbox_Contact();
	$updated  = $agentbox->put( 'contacts', $update, $contact_id );

	if ( false === $updated ) {
		error_log( 'update_existing_contact_marketing() failed.', 0 );
		return false;
	} else {
		err( 'Done, checking result...' );
	}

	$response = json_decode( $updated['body'] );

	if ( 'success' === $response->response->status ) {
		err( 'Success.' );
		return $response->response->contact->id;
	} else {
		return false;
	}

}

/**
 * Checks if the email inside agentbox already contains a primary owner.
 *
 * @param string $contact - agentbox object raw response
 * @return boolean
 */
function contact_has_primary_owner( $contact ) {


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
    

    return false;
}

/**
 * Get and checks the contact if they have OC office as their primary owner.
 *
 * @param [object] $contact - agentbox contact response
 * @return boolean
 */
function contact_has_oc_primary_owner( $contact ) {
	// Get primary owner
	$primaryOwner = get_contact_primary_owner( $contact );

	// check if email matches OC office email
	return ($primaryOwner->staffMember->email == 'enquiries@ocre.com.au');
}

/**
 * create a GET Request that gets the Primary Owner of the contact being processed
 *
 * @param [object] $contact
 * @return object|false
 */
function get_contact_primary_owner( $contact ) {

    $contact = json_decode($contact['body']);

	if( !empty($contact->response->contacts) ) {
		$relatedStaff = $contact->response->contacts[0]->relatedStaffMembers;
		foreach( $relatedStaff as $staff ) {
			if( $staff->role == "Primary Owner") {
				return $staff;
			}
		}
	}

	return false;
}


/**
 * Process for adding contact to agentbox depending whether the contact is a new contact, 
 *
 * @param string $contactEmail -  The contact email used by the user when accessing the form
 * @param string $agentEmail - The email of the agent being contacted
 * @param array $enquiry_body
 * @return void
 */
function process_contact_for_agentbox( $contactEmail, $agentEmail, $enquiry_body ) {
	$agentbox = new ocre\Agentbox_Contact();

	$contact = $agentbox->get('contacts',['email' => $contactEmail],['include' => 'relatedStaffMembers']);

	// Logs process
	_el("Contact Saving Process started for: {$contactEmail}");
	_el('Sending Agentbox post');

	// Send an Enquiry POST request to agentbox 
	$insert_enquiry = $agentbox->post('enquiries', $enquiry_body);

	// Log enquiry to debug.log
	_el('(Enquiry Submission) ' . $insert_enquiry['response']['code'] . ' ' . $insert_enquiry['response']['message']);

	$enquiryResponse  = json_decode($insert_enquiry['body'], true);

	// PROCESS FOR PRIMARY OWNER
	// Check contact if they already have a primary Owner
	$hasPrimaryOwner = contact_has_primary_owner( $contact );


	// Check if user has primary owner attached
	if( ! $hasPrimaryOwner ) {
		_el('No Primary Owner found... Saving new Primary Owner...');
		// Agent ID used for saving the primary owner if the contact does not have one.
		$agentID = get_agentbox_staff_id_by_email( $agentEmail ) ?: get_agentbox_staff_id_by_email( 'enquiries@ocre.com.au' );

		$contact_body = [
			'contact' => [
				"attachedRelatedStaffMembers" => [
					[
						'role' => 'Primary Owner',
						'id' => $agentID,
					]
				]
			]
		];

		// send PUT Request to edit contact's primary owner in agentbox
		$update = $agentbox->put('contacts', $contact_body, $enquiryResponse['response']['enquiry']['contact']['id']);
		_el('(Contact Update) ' . $update['response']['code'] . ' ' . $update['response']['message']);
	}

	// Check if they contact has an owner but the owner is the OC office. If it is the office,
	// update the primary owner to be the new contact ID
	if( $hasPrimaryOwner && (contact_has_oc_primary_owner( $contactEmail )) ) {
		_el('Primary Owner found, but has OC as primary Owner... Saving new Primary Owner');
		$agentID = get_agentbox_staff_id_by_email( $agentEmail ) ?: get_agentbox_staff_id_by_email( 'enquiries@ocre.com.au' );

		$contact_body = [
			'contact' => [
				"attachedRelatedStaffMembers" => [
					[
						'role' => 'Primary Owner',
						'id' => $agentID,
					]
				]
			]
		];

		// send PUT Request to edit contact's primary owner in agentbox
		$update = $agentbox->put('contacts', $contact_body, $enquiryResponse['response']['enquiry']['contact']['id']);
		_el('(Contact Update) ' . $update['response']['code'] . ' ' . $update['response']['message']);
	}
}


add_action('gform_after_submission_6', 'post_agent_enquiry_to_agentbox', 10, 2);
function post_agent_enquiry_to_agentbox($entry)
{
    $agentbox = new ocre\Agentbox_Contact();
    _el('Testing this one if working');
    wp_mail('amiel@stafflink.com.au', 'test', implode(' ', $entry ));

    // Instantiate all the variables
    $name = explode(' ', $entry['1']);
    $firstName      = $name[0];
    $lastName       = $name[1];
    $email          = $entry['2'];
    $mobile         = $entry['3'];
    $aboutEnquiry   = $entry['4'];
    $referral       = $entry['5'] ?? "";
    $agentEnquired  = $entry['8'] ?? "";
    $propertyID     = $entry['6'] ?? "";
    $agentEmail     = $entry['9'];

    // Create comment to be sent to agentbox form Enquiry Details
    $comment        = "Enquiry Details: <b r />";
    $comment        .= "Name: {$entry['1']}<br />";
    $comment        .= "Email: {$email}<br />";
    $comment        .= "Mobile: {$mobile}<br />";
    $comment        .= "Referral: {$referral}<br />";
    $comment        .= "Agent Enquired: {$agentEnquired}<br />";
    $comment        .= "Enquiry message: {$aboutEnquiry}<br />";


    // Create the body of the REQUEST
    $body = [
        "enquiry" => [
            "comment" => $comment . ' <br> (this was submitted on ' . $entry['source_url'] . ' )',
            "source" => "website",
            "attachedContact" => [
                "firstName" => $firstName,
                "lastName" => $lastName,
                "email" => $email,
                "mobile" => $mobile
            ]
        ]
    ];

    // If property ID exists, attach contact to listing.
    if (! $propertyID ) {
        $body['enquiry']["attachedListing"]["id"] = $propertyID;
        $body['enquiry']['attachedContact']['actions']['attachListingAgents'] = true;
    }

    // Starts the process for contacts being sent to agentbox
    process_contact_for_agentbox( $email, $agentEmail, $body );
}

add_action('gform_after_submission_7', 'post_appraisal_request_to_agentbox', 10, 2);
function post_appraisal_request_to_agentbox($entry)
{

    $agentbox = new ocre\Agentbox_Contact();

    $comment = $entry['1.3'] . ' ' . $entry['1.6'] . ' has submitted an appraisal request. <br><br>';
    $comment .= 'Property Address: ' . $entry['6.1'] . ', ' . $entry['6.2'] . ', ' . $entry['6.3'] . ', ' . $entry['6.4'] . ', ' . $entry['6.5'] . ', ' . $entry['6.6'] . '. <br>';
    $comment .= 'Property Type: ' . $entry['7'] . '<br>';
    $comment .= 'Bedrooms: ' . $entry['8'] . '<br>';
    $comment .= 'Bathrooms: ' . $entry['9'] . '<br>';
    if (!empty($entry['10'])) $comment .= 'Other features: ' . $entry['10'] . '<br><br>';
    if (!empty($entry['12'])) $comment .= 'This contact has dealt with ' . $entry['12'] . ' previously. <br>';
    if (!empty($entry['13'])) $comment .= 'They would like to be contacted in the ' . $entry['13'] . '. <br><br>';
    if (!empty($entry['16'])) $comment .= 'This form was submitted on an agent page. The agents\'s email is ' . $entry['16'];

    $body = [
        "enquiry" => [
            "comment" => $comment,
            "source" => "website",
            "attachedContact" => [
                "firstName" => $entry['1.3'],
                "lastName" => $entry['1.6'],
                "email" => $entry['2'],
                "mobile" => $entry['3']
            ]
        ]
    ];

    $insert_enquiry = $agentbox->post('enquiries', $body);
    _el('(Enquiry Submission) ' . $insert_enquiry['response']['code'] . ' ' . $insert_enquiry['response']['message']);

    // Assign contact to agent.
    $enquiry = json_decode($insert_enquiry['body'], true);
    $agent_id = $agentbox->get('staff', ['email' => $entry['16']]);
    $agent_id = json_decode($agent_id['body'], true);


    if( contact_has_primary_owner( $insert_enquiry )  ) {
        $contact_body = [
            'contact' => [
                "attachedRelatedStaffMembers" => [
                    [
                        'role' => 'Primary Owner',
                        'id' => $agent_id['response']['staffMembers'][0]['id'],
                    ]
                ]
            ]
        ];
    }
    
    // Update the contact who submitted the form.
    $update = $agentbox->put('contacts', $contact_body, $enquiry['response']['enquiry']['contact']['id']);
    _el('(Contact Update) ' . $update['response']['code'] . ' ' . $update['response']['message']);
}

add_action('gform_after_submission_33', 'post_enquiry_to_agentbox', 10, 2);
function post_enquiry_to_agentbox($entry)
{
    $agentbox = new ocre\Agentbox_Contact();

    $environment = wp_get_environment_type();

    //check form if valid
    $form_id = $entry['form_id']; // Replace 123 with the ID of your Gravity Form
    $form = GFAPI::get_form($form_id);
    $formTitle = $form['title'];

    if ($formTitle != "Get In Touch") {
        return false;
    }


    $firstName      = $entry['7'];
    $lastName       = $entry['8'];
    $email          = $entry['9'];
    $phone          = $entry['11'];
    $aboutEnquiry   = $entry['5'];
    $message        = $entry['6'];

    $comment        = "Enquiry Details: <br />";
    $comment        .= "Name: {$firstName} {$lastName}<br />";
    $comment        .= "Email: {$email}<br />";
    $comment        .= "Phone: {$phone}<br />";
    $comment        .= "Enquiry: {$aboutEnquiry}<br />";
    $comment        .= "Message: {$message}<br />";

    $body = [
        "enquiry" => [
            "comment" => $comment . ' <br> (this was submitted on ' . $entry['source_url'] . ' )',
            "source" => "website",
            "attachedContact" => [
                "firstName" => $firstName,
                "lastName" => $lastName,
                "email" => $email,
                "mobile" => $phone
            ]
        ]
    ];

    $propertyUniqueID =  get_unique_id_by_listing($entry['source_url']);

    // If property ID exists, attach contact to listing.
    if ($propertyUniqueID != "") {
        $body['enquiry']["attachedListing"]["id"] = $propertyUniqueID;
        $body['enquiry']['attachedContact']['actions']['attachListingAgents'] = true;
    }

    $insert_enquiry = $agentbox->post('enquiries', $body);
    _el('(Enquiry Submission) ' . $insert_enquiry['response']['code'] . ' ' . $insert_enquiry['response']['message']);
}

add_action( 'gform_after_submission_63', 'post_request_an_appraisal_to_agentbox', 10, 2 );
function post_request_an_appraisal_to_agentbox( $entry )
{
    $agentbox = new ocre\Agentbox_Contact();
    $environment = wp_get_environment_type();

    //check form if valid
    $form_id = $entry['form_id']; // Replace 123 with the ID of your Gravity Form
    $form = GFAPI::get_form($form_id);
    $formTitle = $form['title'];

    if ($formTitle != "Digital appraisal Step form") {
        return false;
    }

    $firstName  = $entry['5'];
    $lastName   = $entry['6'];
    $email      = $entry['7'];
    $phone      = $entry['8'];
    $property   = $entry['16'];
    $pm         = $entry['17'];
    $info       = $entry['14'];
    $subs       = $entry['15'];

    $comment = "Enquiry Details: <br />";
    $comment .= "Name: {$firstName} {$lastName} <br />";
    $comment .= "Email: {$email} <br />";
    $comment .= "Phone: {$phone} <br />";
    $comment .= "Property for appraisal: {$property} <br />";
    $comment .= "Property Manager? {$pm}";
    $comment .= "Additional Information:<br />{$info}<br/>";

    if( $subs!= '' ) {
        $comment .= "Receive the latest property listings: yes";
    }

    $body = [
        "enquiry" => [
            "comment"           => $comment . "<br> (this was submitted on " . $entry['source_url'], 
            "source"            => "website",
            "attachedContact"  => [
                "firstName" => $firstName,
                "lastName"  => $lastName,
                "email"     => $email,
                "mobile"    => $phone,
            ]
        ]
    ];

    // $propertyUniqueID = get_unique_id_by_listing( $entry['source_url'] );

    // if ( $propertyUniqueID != "" ) {
    //     $body['enquiry']['attachedListing']['id'] = $propertyUniqueID;
    //     $body['enquiry']['attachedContact']['actions']['attachListingAgents'] = true;
    // }

    $test = "post data first name: {$firstName}<br />";
    $test .= "post data last name: {$lastName}<br />";
    $test .= "post data email: {$email}<br />";
    $test .= "post data phone: {$phone}<br />";
    // $test .= "post data listingID: {$propertyUniqueID}<br />";

    _el( $test );

    $insert_enquiry = $agentbox->post( 'enquiries', $body );
    _el('(Enquiry Submission) ' . $insert_enquiry['response']['code'] . ' ' . $insert_enquiry['response']['message']);
}