<?php 

namespace Stafflink\Lib;

class EndpointConfiguration {

    /**
     * Configration collection
     *
     * @var array
     */
    public $config;


    public function __construct( $config )
    {
        $this->config = $config;
    }

    /**
     * Undocumented function
     *
     * @param array $options
     * @return void
     */
    public function set( $options )
    {
        $this->config = array_replace_recursive( $this->config, $options );
    }

    /**
     * Check if the key is available inside the config
     *
     * @param [type] $key
     * @return boolean
     */
    public function has( $key )
    {
        return in_array( $key, array_keys( $this->config ) );
    }
    

    /**
     * GET magic method
     *
     * @param [type] $key
     * @return void
     */
    public function __get( $key )
    {
        return $this->config[$key];
    }

    /**
     * SET magic method,
     *
     * @param [type] $key
     * @param [type] $value
     */
    public function __set( $key, $value )
    {
        $this->config[$key] = $value;
    }


}