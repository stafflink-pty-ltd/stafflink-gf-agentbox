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
    protected $log_name;

    /**
     * Class Consturctor
     *
     * @param string $file_path
     * @param string $log_name
     */
    public function __construct( $file_path = "", $log_name = "sl_debug" )
    {
        $this->log_name = $log_name;
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
        return ( $file_path !== "" ) ? WP_CONTENT_DIR . "/logs/{$this->log_name}.log" : $file_path;
    }


    /**
     * Create a log entry to the log file
     *
     * @param string $message
     * @return void
     */
    public function log( $message )
    {
        $message = $this->create_message( $message );
        
        file_put_contents( $this->log_file, $message, FILE_APPEND );
    }

    /**
     * Formats the message for the log file
     *
     * @param string $message
     * @return string
     */
    public function create_message( $message ) : string
    {
        $timestamp = date('Y-m-d H:i:s');
        $message = "[{$timestamp}] {$message}" . PHP_EOL;
        
        return $message;
    }
}