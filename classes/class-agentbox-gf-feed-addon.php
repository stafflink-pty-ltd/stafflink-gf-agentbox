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
     * @var GF_Agentbox $_instance
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
     */

     protected $_min_gravityforms_version = "2.7.6";


     protected $_slug = "agentbox-integration";

}