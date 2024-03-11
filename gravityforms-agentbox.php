<?php
use Stafflink\Lib\StafflinkAgentBox;
/**
 * Plugin Name:     Stafflink Agentbox Integration
 * Plugin URI:      https://stafflink.com.au/
 * Description:     Allows pulling and pushing of data via the Agentbox API.
 * Author:          Matthew Neal
 * Author URI:      https://stafflink.com.au/
 * Text Domain:     agentbox-integration
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         SLAB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require "vendor/autoload.php";

if ( ! class_exists( 'SLAB' ) ) {

	/**
	 * The main slab class
	 */
	class SLAB {

		/**
		 * The plugin version number.
		 *
		 * @var string
		 */
		public $version = '0.3.0';

		/**
		 * The plugin settings array.
		 *
		 * @var array
		 */
		public $settings = array();

		/**
		 * The plugin data array.
		 *
		 * @var array
		 */
		public $data = array();

		/**
		 * Storage for class instances.
		 *
		 * @var array
		 */
		public $instances = array();

		/**
		 * A dummy constructor to ensure SLAB is only setup once.
		 *
		 * @return  void
		 */
		public function __construct() {
			// Do nothing.
		}

		/**
		 * Sets up the SLAB plugin.
		 *
		 * @return  void
		 */
		public function initialize() {

			// Define constants.
			$this->define( 'SLAB', true );
			$this->define( 'SLAB_PATH', plugin_dir_path( __FILE__ ) );
			$this->define( 'SLAB_BASENAME', plugin_basename( __FILE__ ) );
			$this->define( 'SLAB_VERSION', $this->version );
			$this->define( 'SLAB_MAJOR_VERSION', 1 );

			// Define settings.
		$this->settings = array(
				'name'                    => __( 'Gravity Forms - Agentbox Integration', 'SLAB' ),
				'slug'                    => dirname( SLAB_BASENAME ),
				'version'                 => SLAB_VERSION,
				'basename'                => SLAB_BASENAME,
				'path'                    => SLAB_PATH,
				'file'                    => __FILE__,
				'url'                     => plugin_dir_url( __FILE__ ),
				'show_admin'              => true,
				'show_updates'            => true,
				'stripslashes'            => false,
				'default_language'        => '',
				'current_language'        => '',
				'capability'              => 'manage_options',
				'uploader'                => 'wp',
				'autoload'                => false,
				'remove_wp_meta_box'      => true,
			);

			// Include utility functions. 
            // require_once dirname( __FILE__ ) . '/classes/class-agentbox-integration.php';
			// require_once dirname( __FILE__ ) . '/classes/class-gravity-form-integration.php';

			// Include admin.
			if ( is_admin() ) {
                // Don't do anything yet...
				require_once dirname( __FILE__ ) . '/classes/class-agentbox-integration.php';

				// $agentbox = new \Stafflink\Lib\AgentBoxClient( 'test' );
				// $agentbox->get( '', array( 'email' => 'test@test.com' ) );
			}
		}

		/**
		 * Completes the setup process on "init" of earlier.
		 *
		 * @return  void
		 */
		public function init() {

			// Bail early if called directly from functions.php or plugin file.
			if ( ! did_action( 'plugins_loaded' ) ) {
				return;
			}

			/**
			 * Fires after SLAB is completely "initialized".
			 *
			 * @date    28/09/13
			 * @since   5.0.0
			 *
			 * @param   int SLAB_MAJOR_VERSION The major version of SLAB.
			 */
			do_action( 'SLAB/init', SLAB_MAJOR_VERSION );
		}

		/**
		 * Defines a constant if doesnt already exist.
		 *
		 *
		 * @param   string $name The constant name.
		 * @param   mixed  $value The constant value.
		 * @return  void
		 */
		public function define( $name, $value = true ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Returns true if a setting exists for this name.
		 *
		 * @param   string $name The setting name.
		 * @return  boolean
		 */
		public function has_setting( $name ) {
			return isset( $this->settings[ $name ] );
		}

		/**
		 * Returns a setting or null if doesn't exist.
		 *
		 * @param   string $name The setting name.
		 * @return  mixed
		 */
		public function get_setting( $name ) {
			return isset( $this->settings[ $name ] ) ? $this->settings[ $name ] : null;
		}

		/**
		 * Updates a setting for the given name and value.
		 *
		 * @param   string $name The setting name.
		 * @param   mixed  $value The setting value.
		 * @return  true
		 */
		public function update_setting( $name, $value ) {
			$this->settings[ $name ] = $value;
			return true;
		}

		/**
		 * Returns data or null if doesn't exist.
		 *
		 * @param   string $name The data name.
		 * @return  mixed
		 */
		public function get_data( $name ) {
			return isset( $this->data[ $name ] ) ? $this->data[ $name ] : null;
		}

		/**
		 * Sets data for the given name and value.
		 *
		 * @param   string $name The data name.
		 * @param   mixed  $value The data value.
		 * @return  void
		 */
		public function set_data( $name, $value ) {
			$this->data[ $name ] = $value;
		}

		/**
		 * Returns an instance or null if doesn't exist.
		 *
		 * @param   string $class The instance class name.
		 * @return  object
		 */
		public function get_instance( $class ) {
			$name = strtolower( $class );
			return isset( $this->instances[ $name ] ) ? $this->instances[ $name ] : null;
		}

		/**
		 * Creates and stores an instance of the given class.
		 *
		 * @param   string $class The instance class name.
		 * @return  object
		 */
		public function new_instance( $class ) {
			$instance                 = new $class();
			$name                     = strtolower( $class );
			$this->instances[ $name ] = $instance;
			return $instance;
		}
    }

	/**
	 * The main function responsible for returning the one true SLAB Instance to functions everywhere.
	 * Use this function like you would a global variable, except without needing to declare the global.
	 *
	 * Example: <?php $SLAB = SLAB(); ?>
	 *
	 * @return  SLAB
	 */
	function SLAB() {
		global $SLAB;

		// Instantiate only once.
		if ( ! isset( $SLAB ) ) {
			$SLAB = new SLAB();
			$SLAB->initialize();
		}
		return $SLAB;
	}

	// Instantiate.
	SLAB();

} // class_exists check
