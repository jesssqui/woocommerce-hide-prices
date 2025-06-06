<?php
/**
 * Uninstall script for WooCommerce Hide Prices
 *
 * This file is executed when the plugin is deleted from WordPress admin.
 * @version 1.8.4
 */

// If this file is called directly, abort.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Remove all plugin options
delete_option( 'wchp_global_restriction' );
delete_option( 'wchp_allowed_roles' );
delete_option( 'wchp_replacement_text' );
delete_option( 'wchp_enable_request_access' );
delete_option( 'wchp_request_access_message' );
delete_option( 'wchp_request_access_url' );
delete_option( 'wchp_enable_product_specific' );
delete_option( 'wchp_enable_category_restrictions' );
delete_option( 'wchp_custom_css' );

// Remove custom user roles
remove_role( 'wholesale_customer' );
remove_role( 'vip_customer' );

// Remove user meta for expiration dates
delete_metadata( 'user', 0, 'wchp_access_expires', '', true );

// Remove product meta
delete_post_meta_by_key( '_wchp_hide_price' );
delete_post_meta_by_key( '_wchp_override_global' );

// Remove category meta
$categories = get_terms( array(
    'taxonomy' => 'product_cat',
    'hide_empty' => false,
) );

if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
    foreach ( $categories as $category ) {
        delete_term_meta( $category->term_id, 'wchp_hide_prices' );
    }
}

// Log successful uninstall
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    error_log( 'WooCommerce Hide Prices: Plugin successfully uninstalled and all data removed.' );
} 