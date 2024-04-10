<?php

namespace GFAgentbox\Inc;

/**
 * Use to handle configuration of the API url
 */
class EndpointConfiguration
{

    /**
     * Configration collection
     *
     * @var array $config Contains the configuration of the endpoint
     */
    public $config = [];


    public function __construct( array $config )
    {
        $this->config = $config;
    }

    public function get_configs()
    {
        return $this->config;
    }

    /**
     * Sets the new config for the endpoint
     *
     * @param array $options
     * @return void
     */
    public function set( $options )
    {
        $this->config = array_replace_recursive( $this->config, $options );
    }

    /**
     * Check if the key is available inside th config
     *
     * @param [type] $key
     * @return boolean
     */
    public function has( $key )
    {
        // check if key is set
        if(isset($this->config[$key])) {
            // return whether the array is empty or not
            return ! empty( $this->config[$key]);
        };

        return false;
    }

    /**
     * Convert key value to json
     *
     * @param [type] $key
     * @return void
     */
    public function convertToJSON( $key ) 
    {
        $saved_array = $this->config[ $key ];
        
        $this->config[$key] = json_encode($saved_array);
    }


    /**
     * GETTER magic method
     *
     * @param [type] $key
     * @return array
     */
    public function __get( $key )
    {
        return [$key => $this->config[$key]];
    }

    /**
     * SETTER magic method,
     *
     * @param string $key
     * @param string|array $value
     */
    public function __set( $key, $value )
    {
        $this->config[ $key ] = $value;
    }

    /**
     * CALL magic method to get and set params inside the configuration
     *
     * @param [type] $method
     * @param [type] $arguments
     * @return void
     */
    public function __call( $method, $arguments )
    {
        // For get methods
        if ( str_contains( $method, 'get' ) ) {
            $key = str_replace( 'get', '', $method ); // remove 'get'
            $key = $this->camel_to_kebab( $key ); // convert to proper case

            // Check if key is in array
            return isset($this->config[$key]) ? $this->config[$key] : null;
        }

        // For set methods
        if ( str_contains( $method, 'set' ) ) {
            $key = str_replace( 'set', '', $method ); // remove 'get'
            $key = $this->camel_to_kebab( $key ); // convert to proper case
            
            $merge = [];

            // save key and arguments
            foreach( $arguments as $argument ) {
                $merge = array_merge_recursive($merge, $argument);
            }
            
            $this->config[$key] = $merge;
        }


        // throw new \BadMethodCallException( "Method {$method} does not exist" );
    }

    /**
     * Outputs string version of the configuration
     *
     * @return string
     */
    public function __toString()
    {

    }

    /**
     * Converts a camelCase string to kebab-case
     *
     * @param string $string
     * @return string string in kebab format
     */
    private function camel_to_kebab( $string )
    {
        return strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', $string ) );
    }


}