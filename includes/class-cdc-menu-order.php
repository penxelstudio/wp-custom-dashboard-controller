<?php
/**
 * Menu Order class - Handles reordering of parent admin menus
 * 
 * This class manages:
 * - Applying custom menu order
 * - AJAX handlers for saving/resetting order
 * - Providing ordered menu data for settings page
 * 
 * @package CustomDashboardController
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDC_Menu_Order {
    
    /**
     * Constructor - Set up hooks for menu ordering
     */
    public function __construct() {
        // Enable custom menu order
        add_filter('custom_menu_order', '__return_true');
        
        // Apply our custom order (late priority)
        add_filter('menu_order', array($this, 'apply_custom_order'), 9999);
        
        // AJAX handlers
        add_action('wp_ajax_cdc_save_menu_order', array($this, 'ajax_save_order'));
        add_action('wp_ajax_cdc_reset_menu_order', array($this, 'ajax_reset_order'));
    }
    
    /**
     * Apply saved custom menu order
     * 
     * @param array $menu_order Default menu order
     * @return array Modified menu order
     */
    public function apply_custom_order($menu_order) {
        $saved_order = get_option('cdc_menu_order', array());
        
        // Return default if no custom order saved
        if (empty($saved_order) || !is_array($saved_order)) {
            return $menu_order;
        }
        
        // Get current menu slugs
        global $menu;
        $current_slugs = array();
        
        if (!empty($menu) && is_array($menu)) {
            foreach ($menu as $item) {
                if (!empty($item[2])) {
                    $current_slugs[] = $item[2];
                }
            }
        }
        
        // Build new order
        $new_order = array();
        
        // First: add saved items that still exist
        foreach ($saved_order as $slug) {
            if (in_array($slug, $current_slugs)) {
                $new_order[] = $slug;
            }
        }
        
        // Then: add any new items not in saved order
        foreach ($current_slugs as $slug) {
            if (!in_array($slug, $new_order)) {
                $new_order[] = $slug;
            }
        }
        
        return $new_order;
    }
    
    /**
     * AJAX handler: Save menu order
     */
    public function ajax_save_order() {
        // Verify nonce
        if (!check_ajax_referer('cdc_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed', 'custom-dashboard-controller')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'custom-dashboard-controller')));
        }
        
        // Get and validate order data
        $order = isset($_POST['order']) ? $_POST['order'] : array();
        
        if (empty($order) || !is_array($order)) {
            wp_send_json_error(array('message' => __('Invalid order data', 'custom-dashboard-controller')));
        }
        
        // Sanitize each menu slug
        $sanitized_order = array_map('sanitize_text_field', $order);
        $sanitized_order = array_filter($sanitized_order);
        $sanitized_order = array_values($sanitized_order);
        
        // Save to database
        update_option('cdc_menu_order', $sanitized_order);
        
        wp_send_json_success(array(
            'message' => __('Menu order saved! Refreshing...', 'custom-dashboard-controller')
        ));
    }
    
    /**
     * AJAX handler: Reset menu order to default
     */
    public function ajax_reset_order() {
        // Verify nonce
        if (!check_ajax_referer('cdc_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed', 'custom-dashboard-controller')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'custom-dashboard-controller')));
        }
        
        // Delete saved order
        delete_option('cdc_menu_order');
        
        wp_send_json_success(array(
            'message' => __('Menu order reset! Refreshing...', 'custom-dashboard-controller')
        ));
    }
    
    /**
     * Get ordered menus for display in settings page
     * 
     * @return array Ordered menu items
     */
    public static function get_ordered_menus() {
        $original_menu = CDC_Menu_Visibility::get_original_menu();
        global $menu;
        
        $menu_source = !empty($original_menu) ? $original_menu : $menu;
        $saved_order = get_option('cdc_menu_order', array());
        
        $menu_items = array();
        
        if (!empty($menu_source) && is_array($menu_source)) {
            foreach ($menu_source as $item) {
                if (empty($item[2])) {
                    continue;
                }
                
                $clean_name = CDC_Menu_Visibility::get_clean_name($item[0]);
                
                // Skip separators
                if (empty($clean_name)) {
                    continue;
                }
                
                $menu_items[$item[2]] = array(
                    'name' => $clean_name,
                    'slug' => $item[2],
                    'icon' => isset($item[6]) ? $item[6] : ''
                );
            }
        }
        
        // Sort by saved order if exists
        if (!empty($saved_order)) {
            $ordered = array();
            
            // Add items in saved order
            foreach ($saved_order as $slug) {
                if (isset($menu_items[$slug])) {
                    $ordered[] = $menu_items[$slug];
                    unset($menu_items[$slug]);
                }
            }
            
            // Add remaining items
            foreach ($menu_items as $item) {
                $ordered[] = $item;
            }
            
            return $ordered;
        }
        
        return array_values($menu_items);
    }
}
