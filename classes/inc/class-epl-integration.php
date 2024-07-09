<?php
namespace GFAgentbox\Inc;

class AgentboxEPLIntegration
{


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

    /**
     * Get the listing unique ID
     *
     * @param string $listing_url
     * @return string|null
     */
    public function get_unique_id_by_listing_url( $listing_url )
    {
        $post_id = url_to_postid( $listing_url );

        $post_type = get_post_type( $post_id );

        if ( 'property' !== $post_type ) {
            return null;
        }

        return get_post_meta( $post_id, 'property_unique_id', true );
    }
}