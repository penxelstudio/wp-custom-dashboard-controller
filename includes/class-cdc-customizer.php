<?php
/**
 * Customizer class - Handles colors and logo customization
 * 
 * This class manages:
 * - Admin sidebar color customization
 * - Custom logo placement at top of sidebar
 * - Separator line hiding
 * 
 * @package CustomDashboardController
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDC_Customizer {
    
    /**
     * Constructor - Set up hooks for customization
     */
    public function __construct() {
        // Apply custom styles to admin head
        add_action('admin_head', array($this, 'apply_custom_styles'));
        
        // Add logo to sidebar (runs after admin menu is rendered)
        add_action('adminmenu', array($this, 'add_sidebar_logo'));
    }
    
    /**
     * Apply custom CSS styles to admin dashboard
     * Outputs inline CSS for color customization
     */
    public function apply_custom_styles() {
        $settings = get_option('cdc_settings', array());

        // Get color values with defaults
        $menu_color = isset($settings['menu_color']) ? sanitize_hex_color($settings['menu_color']) : '#1d2327';
        $text_color = isset($settings['text_color']) ? sanitize_hex_color($settings['text_color']) : '#f0f0f1';
        $custom_logo = isset($settings['custom_logo']) ? esc_url($settings['custom_logo']) : '';
        $logo_text = isset($settings['logo_text']) ? $settings['logo_text'] : '';

        // Hover and active state colors (v1.5.0)
        $hover_bg_color = isset($settings['hover_bg_color']) && !empty($settings['hover_bg_color'])
            ? sanitize_hex_color($settings['hover_bg_color'])
            : $this->adjust_brightness($menu_color, 20);
        $hover_text_color = isset($settings['hover_text_color']) && !empty($settings['hover_text_color'])
            ? sanitize_hex_color($settings['hover_text_color'])
            : '#72aee6';
        $active_bg_color = isset($settings['active_bg_color']) && !empty($settings['active_bg_color'])
            ? sanitize_hex_color($settings['active_bg_color'])
            : '#2271b1';
        $active_text_color = isset($settings['active_text_color']) && !empty($settings['active_text_color'])
            ? sanitize_hex_color($settings['active_text_color'])
            : '#ffffff';

        // Submenu colors (v1.5.1)
        $submenu_bg_color = isset($settings['submenu_bg_color']) && !empty($settings['submenu_bg_color'])
            ? sanitize_hex_color($settings['submenu_bg_color'])
            : $this->adjust_brightness($menu_color, -10);
        $submenu_text_color = isset($settings['submenu_text_color']) && !empty($settings['submenu_text_color'])
            ? sanitize_hex_color($settings['submenu_text_color'])
            : $text_color;
        $submenu_hover_bg_color = isset($settings['submenu_hover_bg_color']) && !empty($settings['submenu_hover_bg_color'])
            ? sanitize_hex_color($settings['submenu_hover_bg_color'])
            : $menu_color;
        $submenu_hover_text_color = isset($settings['submenu_hover_text_color']) && !empty($settings['submenu_hover_text_color'])
            ? sanitize_hex_color($settings['submenu_hover_text_color'])
            : '#72aee6';
        $submenu_active_bg_color = isset($settings['submenu_active_bg_color']) && !empty($settings['submenu_active_bg_color'])
            ? sanitize_hex_color($settings['submenu_active_bg_color'])
            : '#2271b1';
        $submenu_active_text_color = isset($settings['submenu_active_text_color']) && !empty($settings['submenu_active_text_color'])
            ? sanitize_hex_color($settings['submenu_active_text_color'])
            : '#ffffff';

        ?>
        <style type="text/css">
            /* ===========================================
               Admin Menu Background Colors
               =========================================== */
            #adminmenuback,
            #adminmenuwrap,
            #adminmenu {
                background-color: <?php echo esc_attr($menu_color); ?>;
            }
            
            /* ===========================================
               Menu Text Colors
               =========================================== */
            #adminmenu a,
            #adminmenu div.wp-menu-name,
            #adminmenu .wp-submenu a,
            #collapse-button,
            #collapse-button .collapse-button-label {
                color: <?php echo esc_attr($text_color); ?>;
            }
            
            /* ===========================================
               Menu Hover States (v1.5.0 - Custom colors)
               =========================================== */
            #adminmenu li.menu-top:hover,
            #adminmenu li.opensub > a.menu-top,
            #adminmenu li > a.menu-top:focus {
                background-color: <?php echo esc_attr($hover_bg_color); ?>;
            }

            #adminmenu li.menu-top:hover a,
            #adminmenu li.menu-top:hover div.wp-menu-name,
            #adminmenu li.opensub > a.menu-top,
            #adminmenu li > a.menu-top:focus,
            #adminmenu li.menu-top:hover div.wp-menu-image:before {
                color: <?php echo esc_attr($hover_text_color); ?>;
            }
            
            /* ===========================================
               Submenu Colors (v1.5.1 - Custom colors)
               =========================================== */
            #adminmenu .wp-submenu,
            #adminmenu .wp-has-current-submenu .wp-submenu,
            #adminmenu .wp-has-current-submenu.opensub .wp-submenu {
                background-color: <?php echo esc_attr($submenu_bg_color); ?>;
            }

            #adminmenu .wp-submenu a {
                color: <?php echo esc_attr($submenu_text_color); ?> !important;
            }

            /* Submenu Hover States */
            #adminmenu .wp-submenu a:hover,
            #adminmenu .wp-submenu a:focus {
                background-color: <?php echo esc_attr($submenu_hover_bg_color); ?> !important;
                color: <?php echo esc_attr($submenu_hover_text_color); ?> !important;
            }

            /* Submenu Active/Current Item */
            #adminmenu .wp-submenu li.current a,
            #adminmenu .wp-submenu li.current a:hover {
                background-color: <?php echo esc_attr($submenu_active_bg_color); ?> !important;
                color: <?php echo esc_attr($submenu_active_text_color); ?> !important;
            }
            
            /* ===========================================
               Active/Current Menu Item (v1.5.0 - Custom colors)
               =========================================== */
            #adminmenu .wp-has-current-submenu > a,
            #adminmenu .current a.menu-top,
            #adminmenu li.current a.menu-top {
                background-color: <?php echo esc_attr($active_bg_color); ?> !important;
                color: <?php echo esc_attr($active_text_color); ?> !important;
            }

            #adminmenu .wp-has-current-submenu > a div.wp-menu-name,
            #adminmenu .current a.menu-top div.wp-menu-name,
            #adminmenu li.current a.menu-top div.wp-menu-name,
            #adminmenu .wp-has-current-submenu > a div.wp-menu-image:before,
            #adminmenu .current a.menu-top div.wp-menu-image:before,
            #adminmenu li.current a.menu-top div.wp-menu-image:before {
                color: <?php echo esc_attr($active_text_color); ?> !important;
            }
            
            /* ===========================================
               Menu Icons
               =========================================== */
            #adminmenu div.wp-menu-image:before {
                color: <?php echo esc_attr($text_color); ?>;
            }
            
            /* ===========================================
               Hide Separator Lines
               =========================================== */
            #adminmenu li.wp-menu-separator {
                display: none !important;
            }
            
            /* ===========================================
               Custom Sidebar Logo Container
               =========================================== */
            <?php if (!empty($custom_logo) || !empty($logo_text)): ?>
            #cdc-sidebar-logo {
                padding: 10px 16px;
                text-align: center;
                border-bottom: 1px solid <?php echo esc_attr($this->adjust_brightness($menu_color, 20)); ?>;
                background-color: <?php echo esc_attr($this->adjust_brightness($menu_color, -5)); ?>;
            }

            #cdc-sidebar-logo img {
                width: 100%;
                height: auto;
                display: block;
            }

            #cdc-sidebar-logo .cdc-logo-text {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                font-weight: 700;
                font-size: 16px;
                color: <?php echo esc_attr($text_color); ?>;
                margin-top: 8px;
                display: block;
                line-height: 1.4;
            }

            /* Collapsed sidebar - smaller logo */
            .folded #cdc-sidebar-logo img {
                width: 100%;
                max-width: 32px;
                margin: 0 auto;
            }

            .folded #cdc-sidebar-logo {
                padding: 10px 5px;
            }

            .folded #cdc-sidebar-logo .cdc-logo-text {
                display: none;
            }
            <?php endif; ?>
        </style>
        <?php
    }
    
    /**
     * Add custom logo at the top of admin sidebar
     * Uses JavaScript to insert logo div above the menu
     */
    public function add_sidebar_logo() {
        $settings = get_option('cdc_settings', array());
        $custom_logo = isset($settings['custom_logo']) ? $settings['custom_logo'] : '';
        $logo_text = isset($settings['logo_text']) ? $settings['logo_text'] : '';

        // Only output if logo or text is set
        if (!empty($custom_logo) || !empty($logo_text)) {
            ?>
            <script type="text/javascript">
                (function() {
                    // Find the admin menu wrapper
                    var adminMenu = document.getElementById('adminmenuwrap');
                    if (adminMenu) {
                        // Create logo container
                        var logoDiv = document.createElement('div');
                        logoDiv.id = 'cdc-sidebar-logo';

                        var logoContent = '<a href="<?php echo esc_url(admin_url()); ?>">';
                        <?php if (!empty($custom_logo)): ?>
                        logoContent += '<img src="<?php echo esc_url($custom_logo); ?>" alt="Logo">';
                        <?php endif; ?>
                        <?php if (!empty($logo_text)): ?>
                        logoContent += '<span class="cdc-logo-text"><?php echo esc_js($logo_text); ?></span>';
                        <?php endif; ?>
                        logoContent += '</a>';

                        logoDiv.innerHTML = logoContent;

                        // Insert logo at the top of the menu
                        adminMenu.insertBefore(logoDiv, adminMenu.firstChild);
                    }
                })();
            </script>
            <?php
        }
    }
    
    /**
     * Adjust color brightness
     * 
     * @param string $hex Hex color code
     * @param int $steps Amount to adjust (-255 to 255)
     * @return string Adjusted hex color
     */
    private function adjust_brightness($hex, $steps) {
        // Remove # if present
        $hex = ltrim($hex, '#');
        
        // Convert to RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Adjust each channel
        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));
        
        // Convert back to hex
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
