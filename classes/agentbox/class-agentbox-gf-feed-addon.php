<?php

// Include the Gravity Forms Add-On Framework
GFForms::include_feed_addon_framework();

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
	protected $_async_feed_processing = true;

	/**
	 * Returns an instance of this class then stores it in the $_instance propety;
	 *
	 * @return GF_Agentbox;
	 */
	public static function get_instance()
	{
		if ( self::$_instance == null ) {
			self::$_instance = new GF_Agentbox;
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
		var_dump( 'addon_settings' );
		$settings = [ 
			[ 
				'title'  => esc_html__( 'Agentbox Add-On Settings', 'gravityformsagentbox' ),
				'fields' => [ 
					[ 
						'name'  => 'license-key-field',
						'label' => esc_html__( 'License Activation', 'gravityformsagentbox' ),
						'type'  => 'wpc_license_field',
					],
				],
			],
		];
		return apply_filters( 'gravityformsagentbox/plugin-settings', $settings, $this );
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
	

		$fields = [ 
			[ 
				'name'     => 'name',
				'label'    => esc_html__( 'Feed Name', 'gravityformsagentbox' ),
				'type'     => 'text',
				'required' => true,
				'class'    => 'medium',
				'tooltip'  => esc_html__( 'Enter a feed name to uniquely identify it.', 'gravityformsagentbox' ),
			],
			[ 
				'name'        => 'mapped-fields',
				'label'       => esc_html__( 'Fields Mapping', 'gravityformsagentbox' ),
				'type'        => 'dynamic_field_map',
				'required'    => true,
				'value_field' => [ 
					'title' => esc_html__( 'GravityForm Field Name', 'gravityformsagentbox' ),
				],
				'key_field'   => [ 
					'title' => esc_html__( 'Agentbox Field Name', 'gravityformsagentbox' ),
				],
				'dependency'  => '',
				// 'enable_custom_key' => empty( $field_options ),
				'field_map'         => $field_options,
				'tooltip'     => esc_html__( 'Add and select the form fields, then choose the Agentbox column where to send each piece of data to. Make sure the column names are entered identically to your database.', 'gravityformsagentbox' ),
			],
			[ 
				'name'  => 'mapping-troubleshooting',
				'label' => esc_html__( 'Agentbox records are not creating?', 'gravityformsagentbox' ),
				'type'  => 'html',
				'html'  => sprintf( '<p>%1$s</p>', wp_kses( __( 'Contact <a href="mailto:webservices@stafflink.com.au">Stafflink Web Services</a> for more information', 'gravityformsagentbox' ), [ 'a' => [] ] ) ),
			],
		];

		return [ 
			[ 
				'title'  => esc_html__( 'Integration with Agentbox', 'gravityformsagentbox' ),
				'fields' => $fields,
			],
			[ 
				'title'  => esc_html__( 'Conditional logic', 'gravityformsagentbox' ),
				'fields' => [ 
					[ 
						'type'           => 'feed_condition',
						'name'           => 'feed-condition',
						'label'          => esc_html__( 'Conditions', 'gravityformsagentbox' ),
						'checkbox_label' => esc_html__( 'Enable conditional processing', 'gravityformsagentbox' ),
					],
				],
			],
		];
	}

	/**
	 * Define feeds table columns.
	 *
	 * @return array
	 */
	public function feed_list_columns()
	{
		return [ 
			'name'           => esc_html__( 'Feed Name', 'wpc-gf-at' ),
			'table'          => esc_html__( 'Agentbox Table', 'wpc-gf-at' ),
			'has_conditions' => esc_html__( 'Condition(s)', 'wpc-gf-at' ),
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
				'type'       => 'text',
				'validation' => [ 
					'required' => true,
				],
			],
			'last_name' => [ 
				'name'       => 'last_name',
				'label'      => __( 'Last Name', 'gravityformsagentbox' ),
				'type'       => 'text',
			],
			'email' => [ 
				'name'       => 'email',
				'label'      => __( 'Email', 'gravityformsagentbox' ),
				'type'       => 'email',
				'validation' => [ 
					'required' => true,
				],
			],
			'mobile' => [ 
				'name'       => 'mobile',
				'label'      => __( 'Mobile', 'gravityformsagentbox' ),
				'type'       => 'text',
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
		return file_get_contents( GF_Agentbox_Bootstrap_PATH . 'stafflink-svg.svg' );
	}
}