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
     * @var GF_Agentbox
     */

    private static $_instance = null;

    /**
     * Defines the version of the Gravity Form Agentbox Addon
     * 
     * @var string $_version
     */
    protected $_version = SLAB_VERSION;

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
     protected $_slug = "stafflink-gf-agentbox";

     /**
      * Main plugin file
      *
      * @var string
      */
     protected $_path = "stafflink-gf-agentbox/stafflink-gf-agentbox.php";


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
     protected $_short_title = "GF Agentbox";

     /**
      * Returns an instance of this class then stores it in the $_instance propety;
      *
      * @return GF_Agentbox;
      */
     public static function get_instance() {
        if( self::$_instance == null ) {
            self::$_instance = new GF_Agentbox;
        }

        return self::$_instance;
     }

     

     public function init_admin() {
        parent::init();


     }
} 