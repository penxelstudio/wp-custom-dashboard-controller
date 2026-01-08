<?php
/**
 * Admin Bar class - Handles admin bar customization
 * 
 * NEW in v1.4.1
 * 
 * This class manages:
 * - Capturing all admin bar nodes
 * - Hiding admin bar items per user role
 * - Hiding frontend admin bar per user role
 * - Adding custom items to admin bar
 * 
 * @package CustomDashboardController
 * @since 1.4.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDC_Admin_Bar {
    
    /**
     * Store the original admin bar nodes
     * @var array
     */
    private static $original_nodes = array();
    
    /**
     * Constructor - Set up hooks for admin bar customization
     */
    public function __construct() {
        // Capture admin bar nodes very late (after all plugins add their items)
        // Priority 9999 ensures we capture everything
        add_action('admin_bar_menu', array($this, 'capture_admin_bar_nodes'), 9999);
        
        // Hide admin bar items (even later, after capture)
        add_action('admin_bar_menu', array($this, 'hide_admin_bar_items'), 99999);
        
        // Add custom items to admin bar (early so they appear in the list)
        add_action('admin_bar_menu', array($this, 'add_custom_items'), 100);
        
        // Handle frontend admin bar visibility
        add_action('after_setup_theme', array($this, 'handle_frontend_admin_bar'));
        
        // AJAX handlers for custom items
        add_action('wp_ajax_cdc_save_custom_adminbar_item', array($this, 'ajax_save_custom_item'));
        add_action('wp_ajax_cdc_delete_custom_adminbar_item', array($this, 'ajax_delete_custom_item'));
    }
    
    /**
     * Capture all admin bar nodes before any modifications
     * 
     * @param WP_Admin_Bar $wp_admin_bar Admin bar object
     */
    public function capture_admin_bar_nodes($wp_admin_bar) {
        $nodes = $wp_admin_bar->get_nodes();
        
        if (!empty($nodes)) {
            self::$original_nodes = $nodes;
        }
    }
    
    /**
     * Get all top-level admin bar items
     * 
     * @return array Array of admin bar items
     */
    public static function get_all_admin_bar_items() {
        $items = array();
        
        if (empty(self::$original_nodes)) {
            return $items;
        }
        
        // System items that should not be controllable
        $excluded_items = array(
            'top-secondary',  // Container for right side items
            'search'          // Search is usually hidden anyway
        );
        
        foreach (self::$original_nodes as $id => $node) {
            // Skip excluded system items
            if (in_array($id, $excluded_items)) {
                continue;
            }
            
            // Get top-level items (no parent, parent is root, or parent is top-secondary)
            // This captures both left-side and right-side admin bar items
            $is_top_level = empty($node->parent) || 
                           $node->parent === 'root' || 
                           $node->parent === false ||
                           $node->parent === 'top-secondary';
            
            if ($is_top_level) {
                $title = self::get_clean_title($node->title);
                
                // Skip items with no meaningful title
                if (empty($title) || $title === '[No Title]') {
                    continue;
                }
                
                $items[] = array(
                    'id'    => $id,
                    'title' => $title,
                    'href'  => isset($node->href) ? $node->href : ''
                );
            }
        }
        
        return $items;
    }
    
    /**
     * Get clean title from admin bar node
     * 
     * @param string $title Raw title with possible HTML
     * @return string Clean title
     */
    private static function get_clean_title($title) {
        // Remove HTML tags
        $clean = wp_strip_all_tags($title);
        
        // Remove screen reader text
        $clean = preg_replace('/\s*\(.*?\)\s*/', '', $clean);
        
        // Clean up whitespace
        $clean = trim(preg_replace('/\s+/', ' ', $clean));
        
        // If empty, return the ID-based name
        if (empty($clean)) {
            return '[No Title]';
        }
        
        return $clean;
    }
    
    /**
     * Hide admin bar items based on user role settings
     * 
     * @param WP_Admin_Bar $wp_admin_bar Admin bar object
     */
    public function hide_admin_bar_items($wp_admin_bar) {
        $hidden_items = get_option('cdc_adminbar_visibility', array());
        
        if (empty($hidden_items)) {
            return;
        }
        
        // Get current user's roles
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        
        // Remove items for each role the user has
        foreach ($user_roles as $role) {
            if (isset($hidden_items[$role]) && is_array($hidden_items[$role])) {
                foreach ($hidden_items[$role] as $item_id) {
                    $wp_admin_bar->remove_node($item_id);
                }
            }
        }
    }
    
    /**
     * Handle frontend admin bar visibility per role
     */
    public function handle_frontend_admin_bar() {
        // Only apply on frontend
        if (is_admin()) {
            return;
        }
        
        $frontend_settings = get_option('cdc_adminbar_frontend', array());
        
        if (empty($frontend_settings)) {
            return;
        }
        
        // Get current user's roles
        $current_user = wp_get_current_user();
        
        if (!$current_user->exists()) {
            return;
        }
        
        $user_roles = $current_user->roles;
        
        // Check if any of user's roles should have frontend admin bar hidden
        foreach ($user_roles as $role) {
            if (isset($frontend_settings[$role]) && $frontend_settings[$role] === 'hide') {
                show_admin_bar(false);
                break;
            }
        }
    }
    
    /**
     * Add custom items to admin bar
     * 
     * @param WP_Admin_Bar $wp_admin_bar Admin bar object
     */
    public function add_custom_items($wp_admin_bar) {
        $custom_items = get_option('cdc_adminbar_custom_items', array());
        
        if (empty($custom_items)) {
            return;
        }
        
        // Get current user's roles
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        
        foreach ($custom_items as $item) {
            // Check if item should be shown for current user's role
            $item_roles = isset($item['roles']) ? $item['roles'] : array();
            
            // If no roles specified, show to all
            if (empty($item_roles) || array_intersect($user_roles, $item_roles)) {
                $wp_admin_bar->add_node(array(
                    'id'    => 'cdc-custom-' . sanitize_key($item['id']),
                    'title' => esc_html($item['title']),
                    'href'  => esc_url($item['url']),
                    'meta'  => array(
                        'target' => isset($item['new_tab']) && $item['new_tab'] ? '_blank' : '_self',
                        'class'  => 'cdc-custom-adminbar-item'
                    )
                ));
            }
        }
    }
    
    /**
     * AJAX handler: Save custom admin bar item
     */
    public function ajax_save_custom_item() {
        // Verify nonce
        if (!check_ajax_referer('cdc_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed', 'custom-dashboard-controller')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'custom-dashboard-controller')));
        }
        
        // Get and validate data
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        $roles = isset($_POST['roles']) ? array_map('sanitize_key', (array)$_POST['roles']) : array();
        $new_tab = isset($_POST['new_tab']) && $_POST['new_tab'] === 'true';
        $item_id = isset($_POST['item_id']) ? sanitize_key($_POST['item_id']) : '';
        
        if (empty($title) || empty($url)) {
            wp_send_json_error(array('message' => __('Title and URL are required', 'custom-dashboard-controller')));
        }
        
        // Get existing items
        $custom_items = get_option('cdc_adminbar_custom_items', array());
        
        // Generate ID if new item
        if (empty($item_id)) {
            $item_id = 'item-' . time() . '-' . wp_rand(100, 999);
        }
        
        // Check if updating existing item
        $found = false;
        foreach ($custom_items as $key => $item) {
            if (isset($item['id']) && $item['id'] === $item_id) {
                $custom_items[$key] = array(
                    'id'      => $item_id,
                    'title'   => $title,
                    'url'     => $url,
                    'roles'   => $roles,
                    'new_tab' => $new_tab
                );
                $found = true;
                break;
            }
        }
        
        // Add new item if not found
        if (!$found) {
            $custom_items[] = array(
                'id'      => $item_id,
                'title'   => $title,
                'url'     => $url,
                'roles'   => $roles,
                'new_tab' => $new_tab
            );
        }
        
        // Save
        update_option('cdc_adminbar_custom_items', $custom_items);
        
        wp_send_json_success(array(
            'message' => __('Custom item saved!', 'custom-dashboard-controller'),
            'item_id' => $item_id
        ));
    }
    
    /**
     * AJAX handler: Delete custom admin bar item
     */
    public function ajax_delete_custom_item() {
        // Verify nonce
        if (!check_ajax_referer('cdc_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed', 'custom-dashboard-controller')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'custom-dashboard-controller')));
        }
        
        $item_id = isset($_POST['item_id']) ? sanitize_key($_POST['item_id']) : '';
        
        if (empty($item_id)) {
            wp_send_json_error(array('message' => __('Item ID required', 'custom-dashboard-controller')));
        }
        
        // Get existing items
        $custom_items = get_option('cdc_adminbar_custom_items', array());
        
        // Remove item
        foreach ($custom_items as $key => $item) {
            if (isset($item['id']) && $item['id'] === $item_id) {
                unset($custom_items[$key]);
                break;
            }
        }
        
        // Re-index array
        $custom_items = array_values($custom_items);
        
        // Save
        update_option('cdc_adminbar_custom_items', $custom_items);
        
        wp_send_json_success(array(
            'message' => __('Custom item deleted!', 'custom-dashboard-controller')
        ));
    }
    
    /**
     * Get original captured nodes
     * 
     * @return array Original admin bar nodes
     */
    public static function get_original_nodes() {
        return self::$original_nodes;
    }
}
