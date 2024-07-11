<?php

namespace GFAgentbox\Inc;

/**
 * Class that creates own log file
 */
class StafflinkLogger
{
    /**
     * File path of the log file
     *
     * @var string
     */
    protected $log_file;

    /**
     * File name of the log file
     *
     * @var string
     */
    protected $file_name;

    /**
     * Levels of logging information
     *
     * @var array
     */
    protected $log_level = [ 
        'DEBUG' => 0,
        'INFO'  => 1,
        'ERROR' => 2,
    ];

    /**
     * Class Consturctor
     *
     * @param string $file_path
     * @param string $file_name
     */
    public function __construct( $file_path = "", $file_name = "sl_debug" )
    {
        $this->file_name = $file_name;
        // create the log file
        $this->log_file = $this->set_log_file( $file_path );
    }

    /**
     * Set the log file and path
     *
     * @param string $file_path
     * @return string
     */
    public function set_log_file( $file_path )
    {
        return ( $file_path == "" ) ? WP_CONTENT_DIR . "/{$this->file_name}.log" : $file_path;
    }


    /**
     * Create a log entry to the log file
     *
     * @param string $message
     * @param string $level
     * @return void
     */
    public function log( $message, $level = "INFO" )
    {
        if ( !$this->log_level[ $level ] ) {
            return;
        }

        $message = $this->create_message( $message, $level );
        file_put_contents( $this->log_file, $message, FILE_APPEND );
    }

    /**
     * Create an info log
     *
     * @param string $message
     * @return void
     */
    public function log_info( $message )
    {
        $this->log( $message, 'INFO' );
    }

    /**
     * Create an debug log
     *
     * @param string $message
     * @return void
     */
    public function log_debug( $message )
    {
        $this->log( $message, 'DEBUG' );
    }

    /**
     * Create an error log
     *
     * @param string $message
     * @return void
     */
    public function log_error( $message )
    {
        $this->log( $message, 'ERROR' );
    }

    /**
     * Logs a printed array for debugging, only works if WP_DEBUG is enabled
     *
     * @param array $array
     * @return void
     */
    public function log_array( $array )
    {
        if( defined(WP_DEBUG) && WP_DEBUG ) {
            $this->log( var_export($array, true), 'DEBUG' );
        }
    }

    /**
     * Formats the message for the log file
     *
     * @param string $message
     * @param string $level
     * @return string
     */
    public function create_message( $message, $level = "INFO" ): string
    {
        $timestamp = date( 'Y-m-d H:i:s' );
        $message   = "[{$timestamp}]~log[{$this->log_level[$level]}]: {$message}" . PHP_EOL;

        return $message;
    }

    public function delete_log_file()
    {

    }

    public function get_log_dir()
    {

    }

    public function get_log_file_name()
    {

    }
}