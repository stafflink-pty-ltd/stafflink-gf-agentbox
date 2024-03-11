<?php

namespace Stafflink\Lib;

use Stafflink\Interface\ConnectionInterface;
use Stafflink\Interface\LoggerInterface;
use GuzzleHttp\Client;

class AgentBoxClient extends ConnectionAbstract implements ConnectionInterface
{
    /**
     * Undocumented variable
     *
     * @var EndpointConfiguration
     */
    private $config;

    /**
     * Agentbox endpoint
     *
     * @var string
     */
    private $endpoint;

    /**
     * Undocumented function
     */
    public function __construct( $endpoint = "" )
    {
        $this->base_url = "https://api.agentboxcrm.com.au/";
        $this->version  = "version=2";
        $this->endpoint = $endpoint;
        $this->config   = new EndpointConfiguration( [ 
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
        ] );

        $this->create_request_uri( $this->config );
    }

    /**
     * Undocumented function
     *
     * @param EndpointConfiguration $config
     * @return string
     */
    protected function create_request_uri( EndpointConfiguration $config )
    {
        return $this->url = $this->base_url . $this->endpoint . "?" . $this->version . "&";
    }
    
    /**
     * Agentbox GET Request
     * 
     * Create a GET request to agentbox server depending on endpoint and filters
     *
     * @param string $endpoint Server endpoint on where to do the request
     * @param array $options REQUEST method and headers
     * @return string|array Returns either a JSON string or an array
     */
    public function get( $endpoint = "", $options = [] ): string|array
    {
        // Set user-defined options under same configuration
        $this->config->set( $options );
        // build the params for the http request
        $url_params = http_build_query( $this->config->params );
        $request = $this->create_request_uri( $this->config );
        $request = $request . $url_params;

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