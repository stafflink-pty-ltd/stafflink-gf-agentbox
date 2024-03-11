<?php
namespace Stafflink\Lib;
use Stafflink\Interface\ConnectionInterface;
use Stafflink\Interface\LoggerInterface;
use Stafflink\Lib\EndpointConfiguration;

abstract class ConnectionAbstract implements LoggerInterface
{
     /**
     * String URL of the API to hit.
     *
     * @var string
     */
    protected $url;

     /**
     * String of the version.
     *
     * @var string
     */
    protected $base_url;

    /**
     * String of the version.
     *
     * @var string
     */
    protected $version;

    /**
     * 
     *
     * @param EndpointConfiguration $config
     * @return string
     */
    protected function create_request_uri( EndpointConfiguration $config )
    {
        return $this->url = $this->base_url;
    }

    /**
     * Always create a log everytime a request experienced an error
     *
     * @param string $text
     * @return void
     */
    public function log_what_happened($text) : void
    {
        // Check if logs is an http request

        // check if logs is agentbox request error
    }
}