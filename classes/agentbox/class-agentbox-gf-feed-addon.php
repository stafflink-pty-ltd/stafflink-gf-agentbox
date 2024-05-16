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
		if ( !$this->can_create_feed() ) {
			$message = sprintf(
				/* Translators: %1$s = Gravity Forms Airtable settings page URL  */
				__( 'Please visit the <a href="%1$s">plugin settings page</a> and enter a valid Airtable API key.', 'gravityformsagentbox' ),
				admin_url( 'admin.php?page=gf_settings&subview=wpc-gravityforms-airtable' )
			);

			$fields = [ 
				[ 
					'name'  => 'at-api-key-invalid',
					'label' => esc_html__( 'Access token missing or invalid', 'gravityformsagentbox' ),
					'type'  => 'html',
					'html'  => sprintf( '<p>%1$s</p>', $message ),
				],
			];
		} else {
			$options        = $this->get_options();
			$base           = $this->get_setting( 'appid' );
			$table          = $this->get_setting( 'table' );
			$default_table  = !empty( $options ) && isset( array_values( $options )[0]['tables'] ) ? array_values( $options )[0]['tables'] : [];
			$table_options  = $base && isset( $options[ $base ]['tables'] ) ? $options[ $base ]['tables'] : $default_table;
			$default_fields = !empty( $table_options ) && isset( array_values( $table_options )[0]['fields'] ) ? array_values( $table_options )[0]['fields'] : [];
			$field_options  = $table && !empty( $table_options ) && isset( $table_options[ $table ]['fields'] ) ? $table_options[ $table ]['fields'] : $default_fields;

			$messages = [ 
				'base'  => [ 
					'label'   => !empty( $options ) ? __( 'Base name', 'gravityformsagentbox' ) : __( 'App ID', 'gravityformsagentbox' ),
					'tooltip' => !empty( $options ) ? __( 'Select the target database from the options belwo.', 'gravityformsagentbox' ) : __( 'Open your database on Airtable and look at the URL in your browser bar. The app ID is the part starting with ‘app’ and located between two slashes.', 'gravityformsagentbox' ),
				],
				'table' => [ 
					'label'   => !empty( $options ) ? __( 'Table name', 'gravityformsagentbox' ) : __( 'Table ID', 'gravityformsagentbox' ),
					'tooltip' => !empty( $options ) ? __( 'Select the target table from the options below.', 'gravityformsagentbox' ) : __( 'Enter the name of the table in which the new records will be created. You can also use the part starting with ‘tbl’ located between two slashes in the URL.', 'gravityformsagentbox' ),
				],
			];

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
					'name'        => 'appid',
					'label'       => esc_html( $messages['base']['label'] ),
					'type'        => !empty( $options ) ? 'select' : 'text',
					'required'    => true,
					'class'       => 'medium',
					'placeholder' => 'app',
					'tooltip'     => esc_html( $messages['base']['tooltip'] ),
					'choices'     => $options,
					'onchange'    => !empty( $options ) ? 'WPC_GF_AT.onBaseChange()' : '',
				],
				[ 
					'name'        => 'table',
					'label'       => esc_html( $messages['table']['label'] ),
					'type'        => !empty( $table_options ) ? 'select' : 'text',
					'required'    => true,
					'class'       => 'medium',
					'placeholder' => 'tbl',
					'tooltip'     => esc_html( $messages['table']['tooltip'] ),
					'choices'     => $table_options,
					'onchange'    => !empty( $options ) ? 'WPC_GF_AT.onTableChange()' : '',
				],
				[ 
					'name'   => 'base-table-change-notice',
					'type'   => 'html',
					'hidden' => true,
					'html'   => sprintf(
						'<div class="notice inline notice-error"><p>%1$s</p></div>',
						esc_html__( 'Please save these settings before mapping your fields.', 'gravityformsagentbox' ),
					),
				],
				[ 
					'name'              => 'mapped-fields',
					'label'             => esc_html__( 'Fields Mapping', 'gravityformsagentbox' ),
					'type'              => 'dynamic_field_map',
					'required'          => true,
					'value_field'       => [ 
						'title' => esc_html__( 'Form field', 'gravityformsagentbox' ),
					],
					'key_field'         => [ 
						'title' => esc_html__( 'Airtable Field Name', 'gravityformsagentbox' ),
					],
					'dependency'        => 'table',
					'enable_custom_key' => empty( $field_options ),
					'field_map'         => $field_options,
					'tooltip'           => esc_html__( 'Add and select the form fields, then choose the Airtable column where to send each piece of data to. Make sure the column names are entered identically to your database.', 'gravityformsagentbox' ),
				],
				[ 
					'name'  => 'mapping-troubleshooting',
					'label' => esc_html__( 'Airtable records are not creating?', 'gravityformsagentbox' ),
					'type'  => 'html',
					'html'  => sprintf( '<p>%1$s</p>', wp_kses( __( 'To troubleshoot the issue, please go the entry detail page and check the "Airtable" block.<br>It will display any error encountered while creating records.', 'gravityformsagentbox' ), [ 'br' => [] ] ) ),
				],
			];
		}

		return [ 
			[ 
				'title'  => esc_html__( 'Integration with Airtable', 'gravityformsagentbox' ),
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
	 * Build the array of bases and tables options
	 * 
	 * @return  array  $options
	 */
	public function build_options()
	{
		$options = [];
		
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