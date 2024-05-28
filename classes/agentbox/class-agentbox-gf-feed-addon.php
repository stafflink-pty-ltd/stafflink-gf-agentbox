<?php

// Include the Gravity Forms Add-On Framework
GFForms::include_feed_addon_framework();

require_once GF_Agentbox_Bootstrap_PATH . '/classes/Inc/class-agentbox-logging.php';
require_once GF_Agentbox_Bootstrap_PATH . '/classes/Agentbox/class-agentbox.php';

use GFAgentbox\Agentbox\AgentboxClass;
use GFAgentbox\Inc\StafflinkLogger;

class GF_Agentbox extends GFFeedAddOn
{
	/**
	 * Holds the cached request bodies for the current submission.
	 *
	 *
	 * @var array
	 */
	private static $_current_body = array();


	/**
	 * Contains an instance of this class
	 * 
	 * @var GF_Agentbox $_instance if available, contains an instance of this class
	 */

	private static $_instance = null;

	/**
	 * Defines the version of the Gravity Form Agentbox Addon
	 * 
	 * @var string $_version
	 */
	protected $_version = GF_Agentbox_Bootstrap_VERSION;

	/**
	 * Defines the minimum Gravity Forms version required.
	 * 
	 * @var string
	 */

	protected $_min_gravityforms_version = "2.7.6";


	/**
	 * Plugin Slug
	 *
	 * @var string
	 */
	protected $_slug = "gravityformsagentbox";

	/**
	 * Main plugin file
	 *
	 * @var string
	 */
	protected $_path = "gravityformsagentbox/stafflink-gf-agentbox.php";

	/**
	 * Store all options here
	 *
	 * @var array
	 */
	protected $options = [];


	/**
	 * Full path of the $this
	 *
	 * @var string
	 */
	protected $_full_path = __FILE__;

	/**
	 * URL for this add-on
	 *
	 * @var string
	 */
	protected $_url = "https://stafflink.com.au";

	/**
	 * Title of this add-on
	 *
	 * @var string
	 */
	protected $_title = "Gravity Forms - Agentbox";

	/**
	 * Short title of this add-on
	 *
	 * @var string
	 */
	protected $_short_title = "Stafflink";


	/**
	 * Enable background feed processing.
	 *
	 * @var bool
	 */
	protected $_async_feed_processing = false;


	/**
	 * Create a logger for this addon
	 *
	 * @var StafflinkLogger
	 */
	private static $logger;

	/**
	 * Returns an instance of this class then stores it in the $_instance property;
	 *
	 * @return GF_Agentbox;
	 */
	public static function get_instance()
	{
		if ( self::$_instance == null ) {
			self::$_instance = new GF_Agentbox;
			self::$logger = new StafflinkLogger;
		}

		return self::$_instance;
	}

	/**
	 * Admin initialization
	 * 
	 * Checks for deprecated api key
	 */
	public function init_admin()
	{
		parent::init_admin();
	}

	/**
	 * Global plugin settings.
	 *
	 * @return array
	 */
	public function plugin_settings_fields()
	{
		$addon_settings = $this->agentbox_addon_settings_field();
		$settings       = [ 
			[ 
				'title'  => esc_html__( 'Agentbox Add-On Settings', 'gravityformsagentbox' ),
				'fields' => $addon_settings,
			],
		];
		return apply_filters( 'gravityformsagentbox/plugin-settings', $settings, $this );
	}

	/**
	 * Addon settings for agentbox
	 *
	 * @return void
	 */
	public function agentbox_addon_settings_field()
	{
		$settings = [ 
			[ 
				'name'    => 'global_default_primary_owner',
				'label'   => esc_html__( 'Default Primary Owner', 'gravityformsagentbox' ),
				'type'    => 'text',
				'tooltip' => esc_html__( 'Add a staff email or staff id string to the textbox to add default primary owner for enquiries without primary owner set in Agentbox', 'gravityformsagentbox' ),
			],
			[ 
				'name'          => 'global_related_staff_appendable',
				'label'         => esc_html__( 'Append new registered agent', 'gravityformsagentbox' ),
				'type'          => 'checkbox',
				'default_value' => true,
				'tooltip'       => esc_html__( 'When set to false, the new registered agent will override the existing agent of the contact', 'gravityformsagentbox' ),
				'choices'       => [ 
					[ 
						'label' => 'Enabled',
						'name'  => 'enabled',
					],
				],
			],
		];

		return apply_filters( 'gravityformsagentbox/agentbox-addon-settings', $settings, $this );
	}

	/**
	 * Disables manual creation of feeds.
	 *
	 * @return bool
	 */
	public function can_create_feed()
	{
		return true;
	}


	/**
	 * Per-form settings.
	 *
	 * @return array
	 */
	public function feed_settings_fields()
	{
		$field_options = $this->get_options();
		$settings      = [];

		// Feed Name
		// Troubleshooting message
		$agentbox_mapping[] = [ 
			'name'  => 'mapping-troubleshooting',
			'label' => esc_html__( 'Agentbox records are not creating?', 'gravityformsagentbox' ),
			'type'  => 'html',
			'html'  => sprintf( '<p>%1$s</p>', wp_kses( __( '<span class="">Notice: </span>Firstname, Email, and Mobile are required fields', 'gravityformsagentbox' ), [ 'span' => [] ] ) ),
		];

		$agentbox_mapping[] = [ 
			'name'     => 'name',
			'label'    => esc_html__( 'Feed Name', 'gravityformsagentbox' ),
			'type'     => 'text',
			'required' => true,
			'class'    => 'medium',
			'tooltip'  => esc_html__( 'Enter a feed name to uniquely identify it.', 'gravityformsagentbox' ),
		];

		// Dynamic field maps
		$agentbox_mapping[] = [ 
			'name'              => 'mapped-fields',
			'label'             => esc_html__( 'Fields Mapping', 'gravityformsagentbox' ),
			'type'              => 'dynamic_field_map',
			'required'          => true,
			'value_field'       => [ 
				'title' => esc_html__( 'GravityForm Field Name', 'gravityformsagentbox' ),
			],
			'key_field'         => [ 
				'title' => esc_html__( 'Agentbox Field Name', 'gravityformsagentbox' ),
			],
			'dependency'        => '',
			'enable_custom_key' => true,
			'field_map'         => $field_options,
			'tooltip'           => esc_html__( 'Add and select the form fields, then choose the Agentbox column where to send each piece of data to. Make sure the column names are entered identically to your database.', 'gravityformsagentbox' ),
		];



		// SETTINGS TABLE
		// Create the settings table that will be shown to plugin's settings
		$settings[] = [ 
			'title'  => esc_html__( 'Agentbox Field mapping', 'gravityformsagentbox' ),
			'fields' => $agentbox_mapping,
		];

		$settings[] = [ 
			'title'  => esc_html__( 'Agentbox API Settings', 'gravityformsagentbox' ),
			'fields' => [ 
				[ 
					'name'    => 'default_primary_owner',
					'label'   => esc_html__( 'Default Primary Owner', 'gravityformsagentbox' ),
					'type'    => 'text',
					'tooltip' => esc_html__( 'This will override the global settings. Add a staff email or staff id string to the textbox to add default primary owner for enquiries without primary owner set in Agentbox', 'gravityformsagentbox' ),
				],
			],
		];

		// Add conditional logic settings
		$settings[] = [ 
			'title'  => esc_html__( 'Enable Condition', 'gravityformsagentbox' ),
			'fields' => [ 
				[ 
					'type'           => 'feed_condition',
					'name'           => 'feed-condition',
					'label'          => esc_html__( 'Conditions', 'gravityformsagentbox' ),
					'checkbox_label' => esc_html__( 'Enable conditional processing', 'gravityformsagentbox' ),
				],
			],
		];



		return $settings;
	}

	/**
	 * Define feeds table columns.
	 *
	 * @return array
	 */
	public function feed_list_columns()
	{
		return [ 
			'name'           => esc_html__( 'Feed Name', 'gravityformsagentbox' ),
			'has_conditions' => esc_html__( 'Condition(s)', 'gravityformsagentbox' ),
		];
	}

	/**
	 * Getter for bases and tables options
	 * 
	 * @return  array
	 */
	public function get_options()
	{
		if ( empty( $this->options ) ) {
			$this->options = $this->build_options();
		}
		return apply_filters( 'gravityformsagentbox/feed-bases-options', $this->options, $this );
	}

	/**
	 * Agentbox default field name for options
	 * 
	 * @return  array  $options
	 */
	public function build_options()
	{
		$options = [ 
			'first_name' => [ 
				'name'       => 'first_name',
				'label'      => __( 'First Name', 'gravityformsagentbox' ),
				'type'       => 'dynamic_field_map',
				'validation' => [ 
					'required' => true,
				],
			],
			'last_name'  => [ 
				'name'  => 'last_name',
				'label' => __( 'Last Name', 'gravityformsagentbox' ),
				'type'  => 'tedynamic_field_mapt',
			],
			'email'      => [ 
				'name'       => 'email',
				'label'      => __( 'Email', 'gravityformsagentbox' ),
				'type'       => 'dynamic_field_map',
				'validation' => [ 
					'required' => true,
				],
			],
			'mobile'     => [ 
				'name'       => 'mobile',
				'label'      => __( 'Mobile', 'gravityformsagentbox' ),
				'type'       => 'dynamic_field_map',
				'validation' => [ 
					'required' => true,
				],
			],
		];

		return $options;
	}

	/**
	 * Return the Zapier icon for the plugin/form settings menu.
	 *
	 * @since 4.0
	 *
	 * @return string
	 */
	public function get_menu_icon()
	{
		return file_get_contents( GF_Agentbox_Bootstrap_PATH . 'assets/stafflink-svg.svg' );
	}

	/**
	 * Initiate processing of feed
	 *
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 * @return array
	 */
	public function process_feed( $feed, $entry, $form )
	{
		$this->log_debug( __METHOD__ . '(): Processing feed.' );

		// Filter variables for external modifications.
		// error_log( print_r($feed, true), 0 );
		// error_log( print_r($entry, true), 0 );
		// error_log( print_r($form, true), 0 );
		

		$this->create_enquiry( $feed, $entry, $form );


		// $this->add_note( $entry['id'], 'is processing.', 'success' );
		

		return $entry;
	}

	/**
	 * Creates the Agentbox Enquiry that will be processed in the feed
	 *
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 * @return array
	 */ 
	public function create_enquiry( $feed, $entry, $form )
	{
		// Get all information from feed
		$firstName = $this->get_field_value( $form, $entry, $feed['meta']['first_name']);

		$mapped_fields = $this->get_dynamic_field_map_fields( $feed, 'mapped-fields' );

		error_log($firstName);

		self::$logger->log( 'Testing debug writing' );

		// $agentbox = new AgentboxClass( $feed );


		return [];
	}

	/**
	 * Note avatar
	 *
	 * @return void
	 */
	public function note_avatar() 
	{
		return GF_Agentbox_assets . '/assets/stafflink-48x48.png';
	}

	/**
	 * Add the meta box to the entry detail page.
	 *
	 * @param array $meta_boxes
	 * @param array $entry
	 * @param array $form
	 * @return void
	 */
	public function register_meta_box( $meta_boxes, $entry, $form )
	{
		// If the form has an active feed belonging to this add-on and the API can be initialized, add the meta box.
		if ( $this->get_active_feeds( $form['id'] ) ) {
			$meta_boxes[ $this->_slug ] = array(
				'title'    => $this->get_short_title(),
				'callback' => array( $this, 'add_details_meta_box' ),
				'context'  => 'side',
			);
		}

		return $meta_boxes;
	}

	/**
	 * Callback for echoing contents inside the metabox
	 *
	 * @param array $args
	 * @return void
	 */
	public function add_details_meta_box( $args )
	{
		$form = $args['form'];
		$entry = $args['entry'];

		$html = 'No data';
		$action = $this->slug . '_process_feeds';

		// foreach( $entry as $key => $value )
		// {
		// 	$html .= "{$key} => {$value}";
		// }

		echo $html;
	}



	// /**
	//  * Get all feeds API responses for a specific entry.
	//  *
	//  * @param integer $entry_id
	//  * @return array
	//  */
	// public function get_entry_addon_metadata( $entry_id ) {
	// 	$metas = [];

	// 	$feeds = $this->get_feeds_by_entry( $entry_id );

	// 	if ( ! is_array( $feeds ) ) {
	// 		return $metas;
	// 	}

	// 	foreach ( $this->get_feeds_by_entry( $entry_id ) as $feed_id ) {
	// 		$metas[ $feed_id ] = gform_get_meta( $entry_id, sprintf( 'wpc_airtable_feed_%s_result', $feed_id ) );
	// 	}

	// 	return $metas;
	// }
}