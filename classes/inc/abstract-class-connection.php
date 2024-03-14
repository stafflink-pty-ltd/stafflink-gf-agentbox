<?php
namespace Stafflink\Lib;
use Stafflink\Interface\ConnectionInterface;
use Stafflink\Lib\EndpointConfiguration;

abstract class ConnectionAbstract
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

    protected function create_endpoint()
    {
        
    }

    protected function create_params()
    {
        return http_build_query( $this->config->params );
    }
}