<?php
/**
 * Uninstall script - Removes all plugin data when plugin is deleted
 * 
 * This file runs when the plugin is uninstalled (deleted) from WordPress.
 * It removes all options created by the plugin to clean up the database.
 * 
 * @package CustomDashboardController
 * @since 1.0.0
 * @updated 1.4.2
 */

// Exit if not called by WordPress uninstall
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Remove all plugin options
delete_option('cdc_settings');
delete_option('cdc_menu_visibility');
delete_option('cdc_submenu_visibility');
delete_option('cdc_menu_order');
delete_option('cdc_submenu_order');
delete_option('cdc_adminbar_visibility');
delete_option('cdc_adminbar_frontend');
delete_option('cdc_adminbar_custom_items');
delete_option('cdc_dashboard_widgets');

// Clean up any transients for dashboard widgets
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cdc_widget_refresh_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_cdc_widget_refresh_%'");
