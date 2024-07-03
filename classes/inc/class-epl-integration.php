<?php
namespace GFAgentbox\Inc;

class AgentboxEPLIntegration {


    /**
     * Check if epl is activated
     *
     * @return void
     */
    public function is_active()
    {
        include_once ( ABSPATH . 'wp-admin/includes/plugin.php' );
        return ( is_plugin_active( 'easy-property-listings/easy-property-listings.php' ) );
    }

    public function get_listing_by_url( $listing_url )
    {
        if ( preg_match('/\/?property\/(.*)$/', $listing_url, $match) ) {
            $postID = url_to_postid($listing_url);
        } else {
            $postID = "";
        }
    
        return get_post_meta($postID, 'property_unique_id', true);
    }

    public function get_unique_id( $property_address )
    {

    }


}