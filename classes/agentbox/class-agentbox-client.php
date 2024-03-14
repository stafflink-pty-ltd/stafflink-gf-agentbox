<?php

namespace GFAgentbox\Agentbox;

use GFAgentbox\Inc\Base_Connection;
use GFAgentbox\Inc\ConnectionInterface;
use GFAgentbox\Inc\EndpointConfiguration;

class AgentBoxClient implements ConnectionInterface
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
                    'X-Client-ID'  => getenv( 'AGENTBOX_CLIENT_ID' ),
                    'X-API-Key'    => getenv( 'AGENTBOX_CLIENT_SECRET' ),
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

        return $this->endpoint = "{$this->domain}/{$resource}?{$filters}&{$params}&version={$this->version}";
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

        var_dump( $endpoint );
        exit;

        // Do a GET request
        $response = wp_remote_get( $request, $this->config->headers );
        if ( is_wp_error( $response ) ) {
            $this->log_what_happened( 'some text' );
            wp_send_json_error( $response );
        }

        wp_send_json_success( $response );
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

    protected function create_http_query_filters()
    {
        // if filters are available, create http query for them
        if ( $this->config->has( 'filters' ) ) {
            foreach ( $this->config->filters as $key => $filter ) {
                $request .= 'filter[' . $key . ']=' . rawurlencode( $filter ) . '&';
            }
        }

        return "";
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