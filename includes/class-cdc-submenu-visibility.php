<?php
/**
 * Submenu Visibility class - Handles hiding submenus per user role
 * 
 * NEW in v1.4.0
 * 
 * This class manages:
 * - Capturing original submenu list before modifications
 * - Hiding submenus based on user role settings
 * - Providing submenu data for settings page display
 * 
 * @package CustomDashboardController
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDC_Submenu_Visibility {
    
    /**
     * Store the original submenu list before any hiding occurs
     * @var array
     */
    private static $original_submenu = array();
    
    /**
     * Constructor - Set up hooks for submenu visibility
     */
    public function __construct() {
        // Capture submenu list early (before hiding)
        add_action('admin_menu', array($this, 'capture_original_submenu'), 9998);
        
        // Hide submenus late (after all plugins register their menus)
        add_action('admin_menu', array($this, 'hide_submenus'), 9999);
    }
    
    /**
     * Capture the original submenu list before any hiding
     */
    public function capture_original_submenu() {
        global $submenu;
        
        if (!empty($submenu) && is_array($submenu)) {
            self::$original_submenu = $submenu;
        }
    }
    
    /**
     * Get clean submenu name (removes notification bubbles, counts, etc.)
     * 
     * @param string $raw_name Raw submenu name with possible HTML
     * @return string Clean submenu name
     */
    public static function get_clean_name($raw_name) {
        // Extract text before any HTML tag
        if (preg_match('/^([^<]+)/', $raw_name, $matches)) {
            $clean = trim($matches[1]);
            if (!empty($clean)) {
                return $clean;
            }
        }
        
        // Fallback: strip all tags and clean up
        $clean = wp_strip_all_tags($raw_name);
        $clean = preg_replace('/\s+\d+$/', '', $clean);
        
        return trim($clean);
    }
    
    /**
     * Get all submenus organized by parent menu
     * 
     * @return array Associative array of parent => submenus
     */
    public static function get_all_submenus() {
        global $submenu;
        
        // Use captured submenu if available, otherwise use current
        $submenu_source = !empty(self::$original_submenu) ? self::$original_submenu : $submenu;
        
        $all_submenus = array();
        
        if (!empty($submenu_source) && is_array($submenu_source)) {
            foreach ($submenu_source as $parent_slug => $items) {
                if (!is_array($items)) {
                    continue;
                }
                
                $all_submenus[$parent_slug] = array();
                
                foreach ($items as $item) {
                    // Submenu structure: [0] => name, [1] => capability, [2] => slug
                    if (!isset($item[0]) || !isset($item[2])) {
                        continue;
                    }
                    
                    $clean_name = self::get_clean_name($item[0]);
                    
                    if (empty($clean_name)) {
                        continue;
                    }
                    
                    $all_submenus[$parent_slug][] = array(
                        'name'        => $clean_name,
                        'slug'        => $item[2],
                        'parent_slug' => $parent_slug
                    );
                }
            }
        }
        
        return $all_submenus;
    }
    
    /**
     * Get parent menu name from slug
     * 
     * @param string $parent_slug Parent menu slug
     * @return string Parent menu name
     */
    public static function get_parent_name($parent_slug) {
        $menus = CDC_Menu_Visibility::get_all_admin_menus();
        
        foreach ($menus as $menu) {
            if ($menu['slug'] === $parent_slug) {
                return $menu['name'];
            }
        }
        
        // Fallback: clean up the slug
        return ucfirst(str_replace(array('-', '_', '.php'), array(' ', ' ', ''), $parent_slug));
    }
    
    /**
     * Hide submenus based on saved settings for current user role
     */
    public function hide_submenus() {
        global $submenu;
        
        $hidden_submenus = get_option('cdc_submenu_visibility', array());
        
        // Exit if no submenus are hidden
        if (empty($hidden_submenus)) {
            return;
        }
        
        // Get current user's roles
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        
        // Process each role
        foreach ($user_roles as $role) {
            if (!isset($hidden_submenus[$role]) || !is_array($hidden_submenus[$role])) {
                continue;
            }
            
            // Process each parent menu's hidden submenus
            foreach ($hidden_submenus[$role] as $parent_slug => $hidden_items) {
                if (!is_array($hidden_items) || empty($hidden_items)) {
                    continue;
                }
                
                // Check if parent exists in submenu
                if (!isset($submenu[$parent_slug])) {
                    continue;
                }
                
                // Remove hidden submenus
                foreach ($submenu[$parent_slug] as $index => $submenu_item) {
                    if (isset($submenu_item[2]) && in_array($submenu_item[2], $hidden_items)) {
                        unset($submenu[$parent_slug][$index]);
                    }
                }
            }
        }
    }
    
    /**
     * Get the original captured submenu
     * 
     * @return array Original submenu array
     */
    public static function get_original_submenu() {
        return self::$original_submenu;
    }
}
