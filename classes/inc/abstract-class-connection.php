<?php
namespace GFAgentbox\Inc;

use GFAgentbox\Inc\EndpointConfiguration;

abstract class Base_Connection
{
    /**
     * Undocumented variable
     *
     * @var EndpointConfiguration
     */
    protected $config;

    /**
     * Agentbox endpoint
     *
     * @var string
     */
    protected $endpoint;

     /**
     * String of the version.
     *
     * @var string $domain Sets the main domain of the endpoint
     */
    protected $domain;

    /**
     * String of the version.
     *
     * @var string $version Sets the version of the endpoint
     */
    protected $version;

    /**
     * Endpoint query string
     *
     * @var array $query_string contains the query strings
     */
    
    protected $query_string = [];

    /**
     * Connection headers
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Class consturctor
     *
     * @param array $headers
     */
    public function __construct( $headers = [] )
    {
        $this->headers = $headers;
        $this->config  = new EndpointConfiguration( $this->headers );

    } 

    /**
     * Undocumented function
     *
     * @param [type] $resource
     * @return string
     */
    protected function create_endpoint( $resource )
    {
        $params = $this->create_http_query_params();

        return $this->endpoint = "{$this->domain}/{$resource}?{$params}";
    }

    /** */
    protected function create_http_query_params()
    {
        return http_build_query( $this->config->params );
    }
}