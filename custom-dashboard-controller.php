<?php
/**
 * Plugin Name: Custom Dashboard Controller
 * Plugin URI: https://penxelstudio.com
 * Description: Customize WordPress dashboard colors, logo, menu visibility, submenu control, admin bar, custom dashboard widgets, login page branding, preset color schemes, and import/export settings
 * Version: 1.6.1
 * Author: Penxel Studio
 * Author URI: https://penxelstudio.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: custom-dashboard-controller
 * Requires at least: 6.0
 * Tested up to: 7.0
 * Requires PHP: 7.4
 *
 * @package CustomDashboardController
 * @since 1.0.0
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Define plugin constants
 * These constants are used throughout the plugin for paths and version
 */
define('CDC_VERSION', '1.6.1');
define('CDC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CDC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CDC_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Include required class files
 * Each class handles a specific functionality of the plugin
 */
require_once CDC_PLUGIN_DIR . 'includes/class-cdc-core.php';
require_once CDC_PLUGIN_DIR . 'includes/class-cdc-customizer.php';
require_once CDC_PLUGIN_DIR . 'includes/class-cdc-menu-visibility.php';
require_once CDC_PLUGIN_DIR . 'includes/class-cdc-submenu-visibility.php';
require_once CDC_PLUGIN_DIR . 'includes/class-cdc-menu-order.php';
require_once CDC_PLUGIN_DIR . 'includes/class-cdc-submenu-order.php';
require_once CDC_PLUGIN_DIR . 'includes/class-cdc-admin-bar.php';
require_once CDC_PLUGIN_DIR . 'includes/class-cdc-dashboard-widgets.php';
require_once CDC_PLUGIN_DIR . 'includes/class-cdc-login-branding.php';
require_once CDC_PLUGIN_DIR . 'includes/class-cdc-settings.php';

/**
 * Initialize the plugin
 * Hooked to 'plugins_loaded' to ensure all dependencies are available
 */
function cdc_init() {
    new CDC_Core();
}
add_action('plugins_loaded', 'cdc_init');

/**
 * Plugin activation hook
 * Sets up default options when plugin is first activated
 */
register_activation_hook(__FILE__, 'cdc_activate');
function cdc_activate() {
    // Default settings for styling tab
    if (!get_option('cdc_settings')) {
        $defaults = array(
            'menu_color'    => '#1d2327',
            'text_color'    => '#f0f0f1',
            'custom_logo'   => ''
        );
        add_option('cdc_settings', $defaults);
    }
    
    // Default settings for menu visibility
    if (!get_option('cdc_menu_visibility')) {
        add_option('cdc_menu_visibility', array());
    }
    
    // Default settings for submenu visibility (NEW in v1.4.0)
    if (!get_option('cdc_submenu_visibility')) {
        add_option('cdc_submenu_visibility', array());
    }
    
    // Default settings for menu order
    if (!get_option('cdc_menu_order')) {
        add_option('cdc_menu_order', array());
    }
    
    // Default settings for submenu order (NEW in v1.4.0)
    if (!get_option('cdc_submenu_order')) {
        add_option('cdc_submenu_order', array());
    }
    
    // Default settings for admin bar visibility (NEW in v1.4.1)
    if (!get_option('cdc_adminbar_visibility')) {
        add_option('cdc_adminbar_visibility', array());
    }
    
    // Default settings for admin bar frontend visibility (NEW in v1.4.1)
    if (!get_option('cdc_adminbar_frontend')) {
        add_option('cdc_adminbar_frontend', array());
    }
    
    // Default settings for custom admin bar items (NEW in v1.4.1)
    if (!get_option('cdc_adminbar_custom_items')) {
        add_option('cdc_adminbar_custom_items', array());
    }
    
    // Default settings for dashboard widgets (NEW in v1.4.2)
    if (!get_option('cdc_dashboard_widgets')) {
        add_option('cdc_dashboard_widgets', array());
    }

    // Default settings for login page branding (NEW in v1.5.2)
    if (!get_option('cdc_login_settings')) {
        add_option('cdc_login_settings', array());
    }
}

/**
 * Plugin deactivation hook
 * Clean up temporary data if needed
 */
register_deactivation_hook(__FILE__, 'cdc_deactivate');
function cdc_deactivate() {
    // Currently no cleanup needed on deactivation
    // Options are preserved for reactivation
}

/**
 * Add Settings link to plugin action links on Plugins page
 * This provides quick access to plugin settings from the Plugins list
 *
 * @param array $links Existing plugin action links
 * @return array Modified plugin action links
 */
add_filter('plugin_action_links_' . CDC_PLUGIN_BASENAME, 'cdc_add_settings_link');
function cdc_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=dashboard-controller') . '">' . __('Settings', 'custom-dashboard-controller') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
