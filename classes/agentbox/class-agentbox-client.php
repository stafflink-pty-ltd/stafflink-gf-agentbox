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
                    'X-Client-ID'  => 'aHR0cHM6Ly9vY3JlbXVsdGkuYWdlbnRib3hjcm0uY29tLmF1L2FkbWluLw',
                    'X-API-Key'    => '1930-3426-4edc-1c98-a09e-910c-a7e0-ed71-1cd9-a753',
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
        $params  = $this->create_http_query_params();
        $filters = $this->create_http_query_filters();

        $endpoint = "{$this->domain}{$resource}?";

        if( $filters !== "" ) {
            $endpoint .= "{$filters}&";
        }

        if( $params ) {
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

        // Do a GET request
        $response = wp_remote_get( $endpoint, $this->config->headers );

        return $response;

        if ( is_wp_error( $response ) ) {
            // wp_send_json_error( $response );
        }

        // wp_send_json_success( $response );
    }

    /**
     * Create a POST request with body
     *
     * @param [type] $endpoint
     * @param [type] $body
     * @return array|false
     */
    public function post( $endpoint, $body ): array|false
    {

    }

    public function put( $endpoint, $body ): array|false
    {

    }

    /**
     * Create Filters for Agentbox's HTTP requests
     *
     * @return string
     */
    protected function create_http_query_filters() : string
    {
        $filters = [];
        // if filters are available, create http query for them
        if ( ! $this->config->has( 'filters' ) ) {
            return "";
        }

        // add filters to array
        foreach ( $this->config->filters['filters'] as $key => $filter ) {
            $filters [] = 'filter[' . $key . ']=' . rawurlencode( $filter );
        }

        // create string for filters
        $filters = implode( '&', $filters);

        return $filters;
    }

    public function create_agentbox_comment()
    {

    }

    protected function log()
    {

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