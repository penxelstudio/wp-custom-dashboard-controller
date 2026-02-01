<?php
/**
 * Core class - Initializes all plugin components
 *
 * This class serves as the main controller that:
 * - Initializes all feature classes
 * - Enqueues admin assets (CSS/JS)
 * - Sets up common hooks used across the plugin
 *
 * @package CustomDashboardController
 * @since 1.0.0
 * @updated 1.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDC_Core {
    
    /**
     * Constructor - Initialize all plugin components
     */
    public function __construct() {
        // Initialize feature classes
        new CDC_Customizer();           // Handles colors and logo
        new CDC_Menu_Visibility();      // Handles parent menu hiding
        new CDC_Submenu_Visibility();   // Handles submenu hiding (NEW v1.4.0)
        new CDC_Menu_Order();           // Handles parent menu reordering
        new CDC_Submenu_Order();        // Handles submenu reordering (NEW v1.4.0)
        new CDC_Admin_Bar();            // Handles admin bar customization (NEW v1.4.1)
        new CDC_Dashboard_Widgets();    // Handles custom dashboard widgets (NEW v1.4.2)
        new CDC_Login_Branding();       // Handles login page branding (NEW v1.5.2)
        new CDC_Settings();             // Handles settings pages with tabs
        
        // Enqueue admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Enqueue admin CSS and JavaScript files
     * 
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_assets($hook) {
        // Load main CSS on all admin pages (for customizer styles)
        wp_enqueue_style(
            'cdc-admin-css',
            CDC_PLUGIN_URL . 'admin/css/cdc-admin.css',
            array(),
            CDC_VERSION
        );
        
        // Load JavaScript only on our plugin pages for better performance
        if (strpos($hook, 'dashboard-controller') !== false) {
            // WordPress media uploader for logo upload
            wp_enqueue_media();
            
            // jQuery UI for sortable functionality
            wp_enqueue_script('jquery-ui-sortable');
            
            // Our custom admin JavaScript
            wp_enqueue_script(
                'cdc-admin-js',
                CDC_PLUGIN_URL . 'admin/js/cdc-admin.js',
                array('jquery', 'jquery-ui-sortable'),
                CDC_VERSION,
                true // Load in footer
            );
            
            // Pass data to JavaScript
            wp_localize_script('cdc-admin-js', 'cdcAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('cdc_ajax_nonce'),
                'strings' => array(
                    'saving'        => __('Saving...', 'custom-dashboard-controller'),
                    'saved'         => __('Saved! Refreshing...', 'custom-dashboard-controller'),
                    'error'         => __('Error occurred. Please try again.', 'custom-dashboard-controller'),
                    'confirmReset'  => __('Are you sure you want to reset to default?', 'custom-dashboard-controller')
                )
            ));
        }
    }
}
