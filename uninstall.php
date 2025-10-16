<?php
/**
 * Uninstall Advanced Disable Comments
 */

// Exit if accessed directly or not uninstalling
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete plugin options
delete_option( 'adv_disable_comments_options' );

// Delete transients if any
delete_transient( 'adv_disable_comments_cache' );

// Clear any cached data
wp_cache_flush();