<?php
/**
 * Plugin Name:     Gravity Forms - Agentbox Integration
 * Plugin URI:      https://stafflink.com.au/
 * Description:     Allows pulling and pushing of data via the Agentbox API.
 * Author:          Stafflink Web Services
 * Author URI:      https://stafflink.com.au/
 * Text Domain:     gravityformsagentbox
 * Domain Path:     /languages
 * Version:         1.0.0
 *
 * @package         SLAB
 */


if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require "vendor/autoload.php";

if ( !class_exists( 'GF_Agentbox_Bootstrap' ) ) {

	/**
	 * The main slab class
	 */
	class GF_Agentbox_Bootstrap
	{

		/**
		 * The plugin version number.
		 *
		 * @var string
		 */
		public static $version = '0.3.0';

		/**
		 * The plugin settings array.
		 *
		 * @var array
		 */
		public static $settings = array();

		/**
		 * The plugin data array.
		 *
		 * @var array
		 */
		public static $data = array();

		/**
		 * Storage for class instances.
		 *
		 * @var array
		 */
		public static $instances = array();

		/**
		 * Storage for class instances.
		 *
		 * @var
		 */
		public static $instance;

		/**
		 * Action array
		 *
		 * @var array
		 */
		public static $_actions = array();

		/**
		 * Sets up the GF_Agentbox_Bootstrap plugin.
		 *
		 * @return  void
		 */
		public static function initialize()
		{

			// Define constants.
			self::define( 'GF_Agentbox_Bootstrap', true );
			self::define( 'GF_Agentbox_Bootstrap_PATH', plugin_dir_path( __FILE__ ) );
			self::define( 'GF_Agentbox_Bootstrap_BASENAME', plugin_basename( __FILE__ ) );
			self::define( 'GF_Agentbox_Bootstrap_VERSION', self::$version );
			self::define( 'GF_Agentbox_Bootstrap_MAJOR_VERSION', 1 );
			self::define( 'GF_Agentbox_assets', plugin_dir_url(__FILE__) );

			// Define settings.
			self::$settings = array(
				'name'               => __( 'Gravity Forms - Agentbox Integration', 'GF_Agentbox_Bootstrap' ),
				'slug'               => 'gravityformsagentbox',
				'version'            => GF_Agentbox_Bootstrap_VERSION,
				'basename'           => GF_Agentbox_Bootstrap_BASENAME,
				'path'               => GF_Agentbox_Bootstrap_PATH,
				'file'               => __FILE__,
				'url'                => plugin_dir_url( __FILE__ ),
				'show_admin'         => true,
				'show_updates'       => true,
				'stripslashes'       => false,
				'default_language'   => '',
				'current_language'   => '',
				'capability'         => 'manage_options',
				'uploader'           => 'wp',
				'autoload'           => false,
				'remove_wp_meta_box' => true,
			);

			// Include utility functions. 
	
			require_once dirname( __FILE__ ) . '/classes/Agentbox/class-agentbox-gf-feed-addon.php';


			// Include admin.
			if ( is_admin() ) {
	
			}

			// Define Hook
			self::action( 'gform_loaded', self::register_addon() );
		}

		/**
		 * @TODO
		 * Completes the setup process on "init" of earlier.
		 *
		 * @return  void
		 */
		public static function init()
		{
			// Bail early if called directly from functions.php or plugin file.
			if ( !did_action( 'plugins_loaded' ) ) {
				return;
			}

			/**
			 * Fires after GF_Agentbox_Bootstrap is completely "initialized".
			 *
			 * @date    28/09/13
			 * @since   5.0.0
			 *
			 * @param   int GF_Agentbox_Bootstrap_MAJOR_VERSION The major version of GF_Agentbox_Bootstrap.
			 */
			do_action( 'GF_Agentbox_Bootstrap/init', GF_Agentbox_Bootstrap_MAJOR_VERSION );
		}

		/**
		 * Undocumented function
		 *
		 * @return void
		 */
		public static function register_addon()
		{
			if ( !method_exists( 'GFForms', 'include_addon_framework' ) ) {
				return;
			}

			GFAddOn::register( 'GF_Agentbox' );
		}
 
		public static function create_actions()
		{
			$_actions = self::$_actions;

			foreach( $_actions as $_action ) {
				add_action( $_action['hook'], $_action['callback'], $_action['priority'], $_action['accepted_args'] );
			}
		}

		/**
		 *  Register plugin actions of this plugin
		 *
		 * @param string $hook
		 * @param callable $callback
		 * @param integer $priority (optional)
		 * @param integer $accepted_args (optional)
		 * @return void
		 */
		public static function action( $hook, $callback, $priority = 10, $accepted_args = 1 )
		{
			self::$_actions[] = array_merge( compact( 'hook', 'callback', 'priority', 'accepted_args' ) );
		}


		/**
		 * Defines a constant if doesnt already exist.
		 *
		 *
		 * @param   string $name The constant name.
		 * @param   mixed  $value The constant value.
		 * @return  void
		 */
		public static function define( $name, $value = true )
		{
			if ( !defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Returns true if a setting exists for this name.
		 *
		 * @param   string $name The setting name.
		 * @return  boolean
		 */
		public static function has_setting( $name )
		{
			return isset( self::$settings[ $name ] );
		}

		/**
		 * Returns a setting or null if doesn't exist.
		 *
		 * @param   string $name The setting name.
		 * @return  mixed
		 */
		public static function get_setting( $name )
		{
			return isset( self::$settings[ $name ] ) ? self::$settings[ $name ] : null;
		}

		/**
		 * Updates a setting for the given name and value.
		 *
		 * @param   string $name The setting name.
		 * @param   mixed  $value The setting value.
		 * @return  true
		 */
		public static function update_setting( $name, $value )
		{
			self::$settings[ $name ] = $value;
			return true;
		}

		/**
		 * Returns data or null if doesn't exist.
		 *
		 * @param   string $name The data name.
		 * @return  mixed
		 */
		public function get_data( $name )
		{
			return isset( self::$data[ $name ] ) ? self::$data[ $name ] : null;
		}

		/**
		 * Sets data for the given name and value.
		 *
		 * @param   string $name The data name.
		 * @param   mixed  $value The data value.
		 * @return  void
		 */
		public function set_data( $name, $value )
		{
			self::$data[ $name ] = $value;
		}

		/**
		 * Returns an instance or null if doesn't exist.
		 *
		 * @param   string $class The instance class name.
		 * @return  object
		 */
		public static function get_instance( $class )
		{
			$name = strtolower( $class );
			return isset( self::$instances[ $name ] ) ? self::$instances[ $name ] : null;
		}

		/**
		 * Undocumented function
		 *
		 * @return GF_Agentbox_Bootstrap
		 */
		public static function instance()
		{
			//Check for Instance
			if ( self::$instance === null ) {
				return self::$instance = new self;
			}

			return self::$instance;
		}

		/**
		 * Creates and stores an instance of the given class.
		 *
		 * @param   string $class The instance class name.
		 * @return  object
		 */
		public function new_instance( $class )
		{
			$instance                = new $class();
			$name                    = strtolower( $class );
			self::$instances[ $name ] = $instance;
			return $instance;
		}
	}

	GF_Agentbox_Bootstrap::initialize();


} // class_exists check
