<?php

namespace Stafflink\Lib;

use Stafflink\Interface\ConnectionInterface;
use GuzzleHttp\Client;

class AgentBoxClient extends ConnectionAbstract implements ConnectionInterface
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

    protected function create_endpoint()
    {
        
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
    public function get( $resource, $options = [] ): string|array
    {
        // Set user-defined options under same configuration
        $this->config->set( $options );

        // build the params for the http request
        $url_params = http_build_query( $this->config->params );

        $request    = $this->create_request_uri( $this->config );
        $request    = $request . $url_params;

        // if filters are available, create http query for them
        if ( $this->config->has( 'filters' ) ) {
            foreach ( $this->config->filters as $key => $filter ) {
                $request .= 'filter[' . $key . ']=' . rawurlencode( $filter ) . '&';
            }
        }

        var_dump( $request );
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

    public function get_staff()
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