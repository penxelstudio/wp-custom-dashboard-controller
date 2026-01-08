<?php
/**
 * Submenu Order class - Handles reordering of submenus
 * 
 * NEW in v1.4.0
 * 
 * This class manages:
 * - Applying custom submenu order globally
 * - AJAX handlers for saving/resetting submenu order
 * - Providing ordered submenu data for settings page
 * 
 * @package CustomDashboardController
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDC_Submenu_Order {
    
    /**
     * Constructor - Set up hooks for submenu ordering
     */
    public function __construct() {
        // Apply custom submenu order (late priority)
        add_action('admin_menu', array($this, 'apply_custom_order'), 99999);
        
        // AJAX handlers
        add_action('wp_ajax_cdc_save_submenu_order', array($this, 'ajax_save_order'));
        add_action('wp_ajax_cdc_reset_submenu_order', array($this, 'ajax_reset_order'));
    }
    
    /**
     * Apply saved custom submenu order
     */
    public function apply_custom_order() {
        global $submenu;
        
        $saved_order = get_option('cdc_submenu_order', array());
        
        // Exit if no custom order saved
        if (empty($saved_order) || !is_array($saved_order)) {
            return;
        }
        
        // Process each parent menu
        foreach ($saved_order as $parent_slug => $order) {
            // Skip if parent doesn't exist or order is empty
            if (!isset($submenu[$parent_slug]) || empty($order) || !is_array($order)) {
                continue;
            }
            
            // Get current submenu items indexed by slug
            $current_items = array();
            foreach ($submenu[$parent_slug] as $item) {
                if (isset($item[2])) {
                    $current_items[$item[2]] = $item;
                }
            }
            
            // Build new ordered array
            $new_submenu = array();
            $position = 0;
            
            // First: add items in saved order
            foreach ($order as $slug) {
                if (isset($current_items[$slug])) {
                    $new_submenu[$position] = $current_items[$slug];
                    unset($current_items[$slug]);
                    $position++;
                }
            }
            
            // Then: add any remaining items (new submenus)
            foreach ($current_items as $item) {
                $new_submenu[$position] = $item;
                $position++;
            }
            
            // Replace the submenu
            $submenu[$parent_slug] = $new_submenu;
        }
    }
    
    /**
     * AJAX handler: Save submenu order
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
        
        // Get data
        $parent_slug = isset($_POST['parent_slug']) ? sanitize_text_field($_POST['parent_slug']) : '';
        $order = isset($_POST['order']) ? $_POST['order'] : array();
        
        if (empty($parent_slug) || empty($order) || !is_array($order)) {
            wp_send_json_error(array('message' => __('Invalid data', 'custom-dashboard-controller')));
        }
        
        // Sanitize order
        $sanitized_order = array_map('sanitize_text_field', $order);
        $sanitized_order = array_filter($sanitized_order);
        $sanitized_order = array_values($sanitized_order);
        
        // Get existing saved orders
        $all_orders = get_option('cdc_submenu_order', array());
        
        // Update order for this parent
        $all_orders[$parent_slug] = $sanitized_order;
        
        // Save to database
        update_option('cdc_submenu_order', $all_orders);
        
        wp_send_json_success(array(
            'message' => __('Submenu order saved! Refreshing...', 'custom-dashboard-controller')
        ));
    }
    
    /**
     * AJAX handler: Reset submenu order for a specific parent
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
        
        // Get parent slug (optional - if not provided, reset all)
        $parent_slug = isset($_POST['parent_slug']) ? sanitize_text_field($_POST['parent_slug']) : '';
        
        if (!empty($parent_slug)) {
            // Reset specific parent
            $all_orders = get_option('cdc_submenu_order', array());
            if (isset($all_orders[$parent_slug])) {
                unset($all_orders[$parent_slug]);
                update_option('cdc_submenu_order', $all_orders);
            }
        } else {
            // Reset all
            delete_option('cdc_submenu_order');
        }
        
        wp_send_json_success(array(
            'message' => __('Submenu order reset! Refreshing...', 'custom-dashboard-controller')
        ));
    }
    
    /**
     * Get ordered submenus for a specific parent
     * 
     * @param string $parent_slug Parent menu slug
     * @return array Ordered submenu items
     */
    public static function get_ordered_submenus($parent_slug) {
        $original_submenu = CDC_Submenu_Visibility::get_original_submenu();
        global $submenu;
        
        $submenu_source = !empty($original_submenu) ? $original_submenu : $submenu;
        $saved_order = get_option('cdc_submenu_order', array());
        $parent_order = isset($saved_order[$parent_slug]) ? $saved_order[$parent_slug] : array();
        
        // Get items for this parent
        $items = array();
        if (isset($submenu_source[$parent_slug]) && is_array($submenu_source[$parent_slug])) {
            foreach ($submenu_source[$parent_slug] as $item) {
                if (!isset($item[0]) || !isset($item[2])) {
                    continue;
                }
                
                $clean_name = CDC_Submenu_Visibility::get_clean_name($item[0]);
                
                if (empty($clean_name)) {
                    continue;
                }
                
                $items[$item[2]] = array(
                    'name'        => $clean_name,
                    'slug'        => $item[2],
                    'parent_slug' => $parent_slug
                );
            }
        }
        
        // Sort by saved order if exists
        if (!empty($parent_order)) {
            $ordered = array();
            
            foreach ($parent_order as $slug) {
                if (isset($items[$slug])) {
                    $ordered[] = $items[$slug];
                    unset($items[$slug]);
                }
            }
            
            // Add remaining
            foreach ($items as $item) {
                $ordered[] = $item;
            }
            
            return $ordered;
        }
        
        return array_values($items);
    }
}
