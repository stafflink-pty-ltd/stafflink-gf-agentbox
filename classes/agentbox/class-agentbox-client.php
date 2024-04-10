<?php

namespace GFAgentbox\Agentbox;

use GFAgentbox\Inc\Base_Connection;
use GFAgentbox\Inc\ConnectionInterface;
use GFAgentbox\Inc\EndpointConfiguration;

class AgentBoxClient extends Base_Connection implements ConnectionInterface
{

    /**
     * Contains the agentbox filters that the URI uses to get data
     *
     * @var array 
     */
    protected $filters = [];

    /**
     * Class constructor
     * 
     * @param array $headers
     */
    public function __construct( $headers = [] )
    {
        $this->headers = empty( $headers )
            ? [ 
                'headers' => [ 
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                    'X-Client-ID'  => $_ENV['AGENTBOX_CLIENT_ID'],
                    'X-API-Key'    => $_ENV['AGENTBOX_CLIENT_SECRET'],
                ],
                'params'  => [ 
                    'page'  => 1,
                    'limit' => 20,
                ],
            ]
            : $headers;
        $this->domain  = "https://api.agentboxcrm.com.au/";
        $this->version = "2";
        $this->config  = new EndpointConfiguration( $this->headers );
    }

    /**
     * Create the endpoint to be used in the request
     *
     * @param string $resource resource used in agentbo endpoint
     * @return string
     */
    protected function create_endpoint( $resource )
    {
        // Get endpoint params and filters
        $params  = $this->create_http_query_params();
        $filters = $this->create_http_query_filters();

        // Endpoint base url
        $endpoint = "{$this->domain}{$resource}?";

        if ( $filters !== "" ) {
            $endpoint .= "{$filters}&";
        }

        if ( $params ) {
            $endpoint .= "{$params}&";
        }

        $endpoint .= "version={$this->version}";

        return $this->endpoint = $endpoint;
    }

    /**
     * Agentbox GET Request
     * 
     * Create a GET request to agentbox server depending on endpoint and filters
     *
     * @param string $resource Request resource
     * @param array $options REQUEST method and headers
     * @return string|array Returns either a JSON string or an array
     */
    public function get( $resource, $filters = [] ): string|array
    {
        // Set the filters to be used then create the endpoint for the GET request
        $this->config->set( [ 'filters' => $filters ] );
        $endpoint = $this->create_endpoint( $resource );
        $this->request_method = 'GET';

        // Do a GET request
        $this->log( "Processing GET request to {$resource} resource" );
        $response = wp_remote_get( $endpoint, $this->config->headers );

        return $this->response( $response );
    }

    /**
     * Agentbox POST request
     *
     * @param string $resource Request resource
     * @param array $request_body Body of the request to be sent over Agentbox
     * @return string|array
     */
    public function post( $resource, $request_body ) : string|array 
    {
        $endpoint = $this->create_endpoint( $resource );
        $this->request_method = "POST";

        // Do a POST request
        $this->log( "Processing POST request to {$resource} resource");

        // Set the body of the request;
        $this->config->setBody( $request_body );
        $this->config->convertToJSON( 'body' );

        $response = wp_remote_post( $endpoint, $this->config->get_configs() );

        return $response;
    }

    /**
     * Undocumented function
     *
     * @param [type] $resource
     * @param [type] $body
     * @return array|false
     */
    public function put( $resource, $request_body ): array|false
    {
        $endpoint = $this->create_endpoint( $resource );
        $this->request_method = "POST";

        // Do a POST request
        $this->log( "Processing POST request to {$resource} resource");

        // Set the body of the request;
        $this->config->setBody( $request_body );
        $this->config->convertToJSON( 'body' );

        $response = wp_remote_post( $endpoint, $this->config->get_configs() );

        return $response;
    }

    /**
     * STAHP WAISTING PAPER, go paperless
     *
     * @param bool $bool Determine if you want to save logs or not. Just save logs
     * @return void
     */
    public function save_logs( $bool )
    {
        $this->do_logs = $bool;
    }

    /**
     *  Process the response of the requests
     *
     * @param array $response
     * @return array|bool
     */
    protected function response( $response ): array|bool
    {
        // log errors then return immedia
        if ( is_wp_error( $response ) || 201 !== $response['response']['code'] ) {
            if ( defined( 'WP_DEBUG' ) && true === WP_DEBUG || $this->save_logs ) {
                $this->log( "Failed to do a {$this->request_method} request" );
            }

            return false;
        }

        return $response;
    }

    /**
     * Create Filters for Agentbox's HTTP requests
     *
     * @return string
     */
    protected function create_http_query_filters(): string
    {
        $filters = [];
        // if filters are available, create http query for them
        if ( !$this->config->has( 'filters' ) ) {
            return "";
        }

        // add filters to array
        foreach ( $this->config->filters['filters'] as $key => $filter ) {
            $filters[] = 'filter[' . $key . ']=' . rawurlencode( $filter );
        }

        // create string for filters
        $filters = implode( '&', $filters );

        return $filters;
    }

    /**
     * Creates a trail of logs
     *
     * @param string $message
     * @param boolean $is_print_r
     * @return void
     */
    protected function log( $message, $is_print_r = false )
    {
        $prepend_message = "AgentBox Integration: ";
        if ( $is_print_r ) {
            error_log( print_r( $message, true ), 0 );
        } else {
            error_log( $prepend_message . $message, 0 );
        }
    }

    public function set_param( $key, $value )
    {
        $this->config->{$key} = $value;
    }

    public function get_params( $key = "" )
    {
        if ( $key !== "" ) {
            return $this->config->{$key};
        }

        return $this->config->params;
    }



}