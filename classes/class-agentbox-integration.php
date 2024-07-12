<?php
/**
 *  Class for agentbox contact.
 *
 * @package category
 */

namespace GFAgentbox;

/**
 * Agentbox Contact class. Does things to Agentbox API uwu.
 *
 *  @param array $headers
 *  @param string $url
 *  @param string $version
 *  @param array $params
 *  @param array $options
 */
if ( !class_exists( 'AgentBoxIntegration' ) ) {
	class AgentBoxIntegration
	{

		/**
		 * Holds the header information
		 *
		 * @var array
		 */
		private $headers = array();

		/**
		 * String URL of the API to hit.
		 *
		 * @var string
		 */
		private $url;

		/**
		 * String of the version.
		 *
		 * @var string
		 */
		private $version;

		/**
		 * Parameters that sit on the end of the URL.
		 *
		 * @var array
		 */
		private $params = array();

		/**
		 * Post header options.
		 *
		 * @var array
		 */
		private $options = array();

		/**
		 * Set all the things.
		 */
		public function __construct()
		{

			$this->headers = array(
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
				'X-Client-ID'  => getenv( 'AGENTBOX_CLIENT_ID' ),
				'X-API-Key'    => getenv( 'AGENTBOX_CLIENT_SECRET' ),
			);
			$this->options = array(
				'headers'     => $this->headers,
				'method'      => 'POST',
				'data_format' => 'body',
			);
			$this->url     = 'https://api.agentboxcrm.com.au/';
			$this->version = 'version=2';
			$this->params  = array(
				'page'  => 1,
				'limit' => 20,
			);
		}

		/**
		 * Complete a GET request from Agentbox API.
		 *
		 * @param string $endpoint      Available endpoints: 'contacts', 'listings', 'suburbs', 'offices', 'staff', etc.
		 * @param array  $filters        Filter data by key pairs. eg ['email' => 'matt.neal@realcoder.com.au'].
		 * @param array  $params         Additional paramaters. eg ['page' => 1, 'limit' => 20].
		 * @return array|false          Returns the result body, or false if failure.
		 * example: Find a contact by   $endpoint = 'contacts';
		 */
		public function get( $endpoint, $filters = array(), $params = array() )
		{

			if ( empty( $params ) ) {
				$params = array(
					'page'  => 1,
					'limit' => 20,
				);
			}

			$get = $this->url . $endpoint . '?';

			foreach ( $params as $key => $param ) {
				$get .= $key . '=' . $param . '&';
			}

			if ( $filters ) {
				foreach ( $filters as $key => $filter ) {
					$get .= 'filter[' . $key . ']=' . rawurlencode( $filter ) . '&';
				}
			}
			$get .= $this->version;

			$response = wp_remote_get( $get, array( 'headers' => $this->headers ) );

			if ( is_wp_error( $response ) || !is_array( $response ) || 200 !== $response['response']['code'] ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
					// phpcs:disable WordPress.PHP.DevelopmentFunctions
					error_log( 'Failed to \'GET\' from endpoint: ' . $endpoint . ' Response code: ' . $response['response']['code'] . ' ' . $response['response']['message'], 0 );
					error_log( 'Filters: ', 0 );
					error_log( print_r( $filters, true ), 0 );
					error_log( 'Params: ', 0 );
					error_log( print_r( $params, true ), 0 );
					// phpcs:enable
				}

				return false;
			} else {
				return $response;
			}
		}

		/**
		 * Complete a POST to Agentbox API.
		 *
		 * @param string $endpoint      Available endpoints: 'search-requirements', 'contacts', 'enquiries'.
		 * @param array  $body           Array of options. Each endpoint has it's own fields. See Agentbox
		 *                              documentation for more information.
		 * @return array|false          Returns the result body, or false if failure.
		 * example: Add a contact       $endpoint = 'contacts';
		 *                              $body = ['firstName' => 'bob', 'lastName' => 'marley', 'email' => 'bob@marley.com'];
		 */
		public function post( $endpoint, $body )
		{

			$this->options['body'] = wp_json_encode( $body );
			$response              = wp_remote_post( $this->url . $endpoint . '?' . $this->version, $this->options );

			if ( is_wp_error( $response ) || !is_array( $response ) ) {
				error_log( 'Failed to post to agentbox. Endpoint: ' . $endpoint . '. body: ', 0 );
				error_log( print_r( $body, true ), 0 );
				return false;
			} else {
				return $response;
			}
		}

		/**
		 * Complete a PUT to Agentbox API. Used to update stuff.
		 *
		 * @param string $endpoint      Available endpoints: 'search-requirements', 'contacts', 'subscribe'.
		 * @param array  $body           Array of options. Each endpoint has it's own fields. See Agentbox documentation for more information.
		 * @param string $endpoint_id   Sometimes optional. The specific item to be updated.
		 * @return array|false          Returns the result body, or false if failure.
		 *
		 * example: Update a contact    $endpoint = contacts;
		 *                              $body = ['contact']['firstName' => 'matt', 'lastName' => 'neal'];
		 *                              $endpoint_id = 240966;
		 */
		public function put( $endpoint, $body, $endpoint_id )
		{

			$put  = $this->url . $endpoint . '/' . $endpoint_id . '?' . $this->version;
			$body = wp_json_encode( $body );

			$this->options['method'] = 'PUT';
			$this->options['body']   = $body;

			$response = wp_remote_request( $put, $this->options );

			if ( is_wp_error( $response ) || !is_array( $response ) ) {
				error_log( 'Failed to update ' . $endpoint . '. body: ', 0 );
				error_log( print_r( $body, true ), 0 );
				error_log( 'Endpoint ID ( the item to update, generally a contact): ' . $endpoint_id, 0 );
				return false;
			} else {
				return $response;
			}
		}

		/**
		 * Get the listing from Agentbox API
		 *
		 * @param string $listing_id unique listing id from the propety.
		 * @return string The body of the response. Empty string if no body or incorrect parameter given.
		 */
		public function get_listing( $listing_id )
		{

			$get      = $this->url . 'listings/' . $listing_id . '?' . $this->version;
			$response = wp_remote_get( $get, array( 'headers' => $this->headers ) );

			if ( is_wp_error( $response ) || !is_array( $response ) || 200 !== $response['response']['code'] ) {
				error_log( 'Failed to \'GET\' from endpoint: ' . '"listings"' . '. Response code: ' . $response['response']['code'] . ' ' . $response['response']['message'], 0 );
				return false;
			} else {
				return wp_remote_retrieve_body( $response );
			}
		}

		/**
		 * Get a listing from the Agentbox API and return everything.
		 *
		 * @param string $listing_id unique listing id from the propety.
		 * @return string The body of the response. Empty string if no body or incorrect parameter given.
		 */
		public function get_full_listing( $listing_id )
		{

			$include  = 'include=images%2CfloorPlans%2Cdocuments%2CexternalLinks%2CrelatedStaffMembers%2CinspectionDates%2CextraFields&';
			$get      = $this->url . 'listings/' . $listing_id . '?' . $include . $this->version;
			$response = wp_remote_get( $get, array( 'headers' => $this->headers ) );

			if ( is_wp_error( $response ) || !is_array( $response ) || 200 !== $response['response']['code'] ) {
				error_log( 'Failed to get the full listing from AgentBox. Response code: ' . $response['response']['code'] . ' ' . $response['response']['message'], 0 );
				error_log( 'Class ocre\Agentbox_Contact. listing ID: ' . $listing_id, 0 );
				return false;
			} else {
				return wp_remote_retrieve_body( $response );
			}
		}

	}

}