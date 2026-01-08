<?php
/**
 * Menu Visibility class - Handles hiding parent menus per user role
 * 
 * This class manages:
 * - Capturing original menu list before modifications
 * - Hiding menus based on user role settings
 * - Providing menu data for settings page display
 * 
 * @package CustomDashboardController
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDC_Menu_Visibility {
    
    /**
     * Store the original menu list before any hiding occurs
     * @var array
     */
    private static $original_menu = array();
    
    /**
     * Constructor - Set up hooks for menu visibility
     */
    public function __construct() {
        // Capture menu list early (before hiding)
        add_action('admin_menu', array($this, 'capture_original_menu'), 9998);
        
        // Hide menus late (after all plugins register their menus)
        add_action('admin_menu', array($this, 'hide_menus'), 9999);
    }
    
    /**
     * Capture the original menu list before any hiding
     * This ensures the settings page always shows all available menus
     */
    public function capture_original_menu() {
        global $menu;
        
        if (!empty($menu) && is_array($menu)) {
            self::$original_menu = $menu;
        }
    }
    
    /**
     * Get clean menu name (removes notification bubbles, counts, etc.)
     * 
     * @param string $raw_name Raw menu name with possible HTML
     * @return string Clean menu name
     */
    public static function get_clean_name($raw_name) {
        // Extract text before any HTML tag
        // This handles: "Comments <span class='count'>5</span>"
        if (preg_match('/^([^<]+)/', $raw_name, $matches)) {
            $clean = trim($matches[1]);
            if (!empty($clean)) {
                return $clean;
            }
        }
        
        // Fallback: strip all tags and clean up
        $clean = wp_strip_all_tags($raw_name);
        $clean = preg_replace('/^(\w+)\s+\d+.*$/i', '$1', $clean);
        $clean = preg_replace('/\s+\d+$/', '', $clean);
        
        return trim($clean);
    }
    
    /**
     * Get all admin menus from the captured original list
     * 
     * @return array Array of menu items with name, slug, and icon
     */
    public static function get_all_admin_menus() {
        global $menu;
        
        // Use captured menu if available, otherwise use current
        $menu_source = !empty(self::$original_menu) ? self::$original_menu : $menu;
        
        $menu_items = array();
        
        if (!empty($menu_source) && is_array($menu_source)) {
            foreach ($menu_source as $item) {
                // Skip separators and items without slug
                if (empty($item[0]) || $item[0] === '' || empty($item[2])) {
                    continue;
                }
                
                $clean_name = self::get_clean_name($item[0]);
                
                // Skip if name is empty after cleaning
                if (empty($clean_name)) {
                    continue;
                }
                
                $menu_items[] = array(
                    'name' => $clean_name,
                    'slug' => $item[2],
                    'icon' => isset($item[6]) ? $item[6] : ''
                );
            }
        }
        
        return $menu_items;
    }
    
    /**
     * Hide menus based on saved settings for current user role
     */
    public function hide_menus() {
        $hidden_menus = get_option('cdc_menu_visibility', array());
        
        // Exit if no menus are hidden
        if (empty($hidden_menus)) {
            return;
        }
        
        // Get current user's roles
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        
        // Remove menus for each role the user has
        foreach ($user_roles as $role) {
            if (isset($hidden_menus[$role]) && is_array($hidden_menus[$role])) {
                foreach ($hidden_menus[$role] as $menu_slug) {
                    remove_menu_page($menu_slug);
                }
            }
        }
    }
    
    /**
     * Get the original captured menu
     * Used by other classes that need the original menu list
     * 
     * @return array Original menu array
     */
    public static function get_original_menu() {
        return self::$original_menu;
    }
}
