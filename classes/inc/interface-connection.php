<?php 

namespace Stafflink\Interface;
use Stafflink\Lib\EndpointConfiguration;

interface ConnectionInterface {


    
    /**
     * Endpoint get request
     *
     * @return string|array
     */
    public function get( $endpoint, $options = [] ) : string|array;


    /**
     * Endpoint post request
     *
     * @return array|false
     */
    public function post( $endpoint, $body ) : array|false;


    /**
     * Endpoint put request
     *
     * @return void
     */
    public function put( $endpoint, $body ) : array|false;
}