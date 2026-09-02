<?php
/**
 * Settings class - Handles admin settings pages with tabbed interface
 *
 * UPDATED in v1.4.0 - New tabbed interface
 * UPDATED in v1.4.1 - Added Admin Bar tab
 * UPDATED in v1.4.2 - Added Dashboard Widgets tab
 * UPDATED in v1.5.2 - Added Login Page Branding tab
 * UPDATED in v1.6.0 - Added Tools tab (Preset Schemes, Import/Export, Reset)
 *
 * Tab Structure:
 * - Tab 1: Basic Settings (Colors, Logo, Preset Color Schemes)
 * - Tab 2: Menu Visibility (Parent menus, Submenus)
 * - Tab 3: Menu Order (Parent menus, Submenus)
 * - Tab 4: Admin Bar (Visibility, Frontend, Custom Items)
 * - Tab 5: Dashboard Widgets (Shortcode widgets)
 * - Tab 6: Login Page (Logo, Background, Layout)
 * - Tab 7: Tools (Import/Export, Reset to Default)
 *
 * @package CustomDashboardController
 * @since 1.0.0
 * @updated 1.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDC_Settings {
    
    /**
     * Available tabs configuration
     * @var array
     */
    private $tabs = array();

    /**
     * Get the tab labels, building them on first use.
     *
     * Built lazily rather than in the constructor: this class is instantiated on
     * plugins_loaded, and calling __() that early triggers WordPress's
     * _load_textdomain_just_in_time notice (WP 6.7+). Every caller runs inside an
     * admin callback, which is well after init.
     *
     * @since 1.6.2
     * @return array Tab key => translated label.
     */
    private function get_tabs() {
        if (empty($this->tabs)) {
            $this->tabs = array(
                'basic'      => __('Basic Settings', 'custom-dashboard-controller'),
                'visibility' => __('Menu Visibility', 'custom-dashboard-controller'),
                'order'      => __('Menu Order', 'custom-dashboard-controller'),
                'adminbar'   => __('Admin Bar', 'custom-dashboard-controller'),
                'widgets'    => __('Dashboard Widgets', 'custom-dashboard-controller'),
                'login'      => __('Login Page', 'custom-dashboard-controller'),
                'tools'      => __('Tools', 'custom-dashboard-controller')
            );
        }

        return $this->tabs;
    }
    
    /**
     * Constructor - Set up admin menu and settings
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));

        // AJAX handlers for v1.6.0 features
        add_action('wp_ajax_cdc_apply_color_scheme', array($this, 'ajax_apply_color_scheme'));
        add_action('wp_ajax_cdc_export_settings', array($this, 'ajax_export_settings'));
        add_action('wp_ajax_cdc_import_settings', array($this, 'ajax_import_settings'));
        add_action('wp_ajax_cdc_reset_all_settings', array($this, 'ajax_reset_all_settings'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Dashboard Controller', 'custom-dashboard-controller'),
            __('Dashboard Controller', 'custom-dashboard-controller'),
            'manage_options',
            'dashboard-controller',
            array($this, 'render_settings_page'),
            'dashicons-admin-customizer',
            100
        );
    }
    
    /**
     * Register all settings
     */
    public function register_settings() {
        // Basic settings (colors, logo)
        register_setting('cdc_basic_settings', 'cdc_settings', array(
            'sanitize_callback' => array($this, 'sanitize_basic_settings')
        ));
        
        // Menu visibility settings
        register_setting('cdc_visibility_settings', 'cdc_menu_visibility', array(
            'sanitize_callback' => array($this, 'sanitize_menu_visibility')
        ));
        
        // Submenu visibility settings
        register_setting('cdc_visibility_settings', 'cdc_submenu_visibility', array(
            'sanitize_callback' => array($this, 'sanitize_submenu_visibility')
        ));
        
        // Admin bar visibility settings (NEW v1.4.1)
        register_setting('cdc_adminbar_settings', 'cdc_adminbar_visibility', array(
            'sanitize_callback' => array($this, 'sanitize_adminbar_visibility')
        ));
        
        // Admin bar frontend settings (NEW v1.4.1)
        register_setting('cdc_adminbar_settings', 'cdc_adminbar_frontend', array(
            'sanitize_callback' => array($this, 'sanitize_adminbar_frontend')
        ));

        // Login page branding settings (NEW v1.5.2)
        register_setting('cdc_login_settings', 'cdc_login_settings', array(
            'sanitize_callback' => array($this, 'sanitize_login_settings')
        ));
    }

    /**
     * Sanitize login page settings (NEW v1.5.2)
     */
    public function sanitize_login_settings($input) {
        $sanitized = array();

        // Logo settings
        $sanitized['custom_logo'] = isset($input['custom_logo'])
            ? esc_url_raw($input['custom_logo'])
            : '';

        $sanitized['logo_width'] = isset($input['logo_width'])
            ? absint($input['logo_width'])
            : 320;

        $sanitized['logo_height'] = isset($input['logo_height'])
            ? absint($input['logo_height'])
            : 80;

        $sanitized['logo_url'] = isset($input['logo_url'])
            ? esc_url_raw($input['logo_url'])
            : '';

        // Layout settings
        $valid_layouts = array('center', 'two-column-left', 'two-column-right');
        $sanitized['layout_style'] = isset($input['layout_style']) && in_array($input['layout_style'], $valid_layouts)
            ? $input['layout_style']
            : 'center';

        // Background settings
        $valid_bg_types = array('color', 'image', 'gradient');
        $sanitized['bg_type'] = isset($input['bg_type']) && in_array($input['bg_type'], $valid_bg_types)
            ? $input['bg_type']
            : 'color';

        $sanitized['bg_color'] = isset($input['bg_color'])
            ? sanitize_hex_color($input['bg_color'])
            : '#f0f0f1';

        $sanitized['bg_image'] = isset($input['bg_image'])
            ? esc_url_raw($input['bg_image'])
            : '';

        $valid_bg_sizes = array('cover', 'contain', 'auto');
        $sanitized['bg_size'] = isset($input['bg_size']) && in_array($input['bg_size'], $valid_bg_sizes)
            ? $input['bg_size']
            : 'cover';

        $sanitized['bg_position'] = isset($input['bg_position'])
            ? sanitize_text_field($input['bg_position'])
            : 'center center';

        $valid_bg_repeats = array('no-repeat', 'repeat', 'repeat-x', 'repeat-y');
        $sanitized['bg_repeat'] = isset($input['bg_repeat']) && in_array($input['bg_repeat'], $valid_bg_repeats)
            ? $input['bg_repeat']
            : 'no-repeat';

        $sanitized['bg_gradient_start'] = isset($input['bg_gradient_start'])
            ? sanitize_hex_color($input['bg_gradient_start'])
            : '#667eea';

        $sanitized['bg_gradient_end'] = isset($input['bg_gradient_end'])
            ? sanitize_hex_color($input['bg_gradient_end'])
            : '#764ba2';

        $sanitized['bg_gradient_direction'] = isset($input['bg_gradient_direction'])
            ? sanitize_text_field($input['bg_gradient_direction'])
            : '135deg';

        // Form styling
        $sanitized['form_bg_color'] = isset($input['form_bg_color'])
            ? sanitize_hex_color($input['form_bg_color'])
            : '#ffffff';

        $sanitized['form_text_color'] = isset($input['form_text_color'])
            ? sanitize_hex_color($input['form_text_color'])
            : '#3c434a';

        $sanitized['form_border_radius'] = isset($input['form_border_radius'])
            ? absint($input['form_border_radius'])
            : 4;

        $sanitized['form_shadow'] = isset($input['form_shadow']) && $input['form_shadow'] === 'yes'
            ? 'yes'
            : 'no';

        // Button styling
        $sanitized['button_bg_color'] = isset($input['button_bg_color'])
            ? sanitize_hex_color($input['button_bg_color'])
            : '#2271b1';

        $sanitized['button_text_color'] = isset($input['button_text_color'])
            ? sanitize_hex_color($input['button_text_color'])
            : '#ffffff';

        $sanitized['button_hover_bg'] = isset($input['button_hover_bg'])
            ? sanitize_hex_color($input['button_hover_bg'])
            : '#135e96';

        $sanitized['button_hover_text'] = isset($input['button_hover_text'])
            ? sanitize_hex_color($input['button_hover_text'])
            : '#ffffff';

        $sanitized['button_border_radius'] = isset($input['button_border_radius'])
            ? absint($input['button_border_radius'])
            : 3;

        // Link styling
        $sanitized['link_color'] = isset($input['link_color'])
            ? sanitize_hex_color($input['link_color'])
            : '#50575e';

        $sanitized['link_hover_color'] = isset($input['link_hover_color'])
            ? sanitize_hex_color($input['link_hover_color'])
            : '#135e96';

        // Two-column settings
        $sanitized['column_bg_color'] = isset($input['column_bg_color'])
            ? sanitize_hex_color($input['column_bg_color'])
            : '#ffffff';

        $sanitized['column_image'] = isset($input['column_image'])
            ? esc_url_raw($input['column_image'])
            : '';

        $sanitized['column_overlay'] = isset($input['column_overlay']) && $input['column_overlay'] === 'yes'
            ? 'yes'
            : 'no';

        $sanitized['column_overlay_color'] = isset($input['column_overlay_color'])
            ? sanitize_text_field($input['column_overlay_color'])
            : 'rgba(0,0,0,0.3)';

        // Custom CSS
        $sanitized['custom_css'] = isset($input['custom_css'])
            ? wp_strip_all_tags($input['custom_css'])
            : '';

        return $sanitized;
    }
    
    /**
     * Sanitize basic settings
     */
    public function sanitize_basic_settings($input) {
        $sanitized = array();

        $sanitized['menu_color'] = isset($input['menu_color'])
            ? sanitize_hex_color($input['menu_color'])
            : '#1d2327';

        $sanitized['text_color'] = isset($input['text_color'])
            ? sanitize_hex_color($input['text_color'])
            : '#f0f0f1';

        $sanitized['custom_logo'] = isset($input['custom_logo'])
            ? esc_url_raw($input['custom_logo'])
            : '';

        $sanitized['logo_text'] = isset($input['logo_text'])
            ? sanitize_text_field($input['logo_text'])
            : '';

        $sanitized['hover_bg_color'] = isset($input['hover_bg_color'])
            ? sanitize_hex_color($input['hover_bg_color'])
            : '';

        $sanitized['hover_text_color'] = isset($input['hover_text_color'])
            ? sanitize_hex_color($input['hover_text_color'])
            : '';

        $sanitized['active_bg_color'] = isset($input['active_bg_color'])
            ? sanitize_hex_color($input['active_bg_color'])
            : '';

        $sanitized['active_text_color'] = isset($input['active_text_color'])
            ? sanitize_hex_color($input['active_text_color'])
            : '';

        // Submenu colors (v1.5.1)
        $sanitized['submenu_bg_color'] = isset($input['submenu_bg_color'])
            ? sanitize_hex_color($input['submenu_bg_color'])
            : '';

        $sanitized['submenu_text_color'] = isset($input['submenu_text_color'])
            ? sanitize_hex_color($input['submenu_text_color'])
            : '';

        $sanitized['submenu_hover_bg_color'] = isset($input['submenu_hover_bg_color'])
            ? sanitize_hex_color($input['submenu_hover_bg_color'])
            : '';

        $sanitized['submenu_hover_text_color'] = isset($input['submenu_hover_text_color'])
            ? sanitize_hex_color($input['submenu_hover_text_color'])
            : '';

        $sanitized['submenu_active_bg_color'] = isset($input['submenu_active_bg_color'])
            ? sanitize_hex_color($input['submenu_active_bg_color'])
            : '';

        $sanitized['submenu_active_text_color'] = isset($input['submenu_active_text_color'])
            ? sanitize_hex_color($input['submenu_active_text_color'])
            : '';

        return $sanitized;
    }
    
    /**
     * Sanitize menu visibility settings
     */
    public function sanitize_menu_visibility($input) {
        $sanitized = array();
        
        if (is_array($input)) {
            foreach ($input as $role => $menus) {
                $role = sanitize_key($role);
                $sanitized[$role] = array();
                
                if (is_array($menus)) {
                    foreach ($menus as $menu_slug) {
                        $sanitized[$role][] = sanitize_text_field($menu_slug);
                    }
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize submenu visibility settings
     */
    public function sanitize_submenu_visibility($input) {
        $sanitized = array();
        
        if (is_array($input)) {
            foreach ($input as $role => $parents) {
                $role = sanitize_key($role);
                $sanitized[$role] = array();
                
                if (is_array($parents)) {
                    foreach ($parents as $parent_slug => $submenus) {
                        $parent_slug = sanitize_text_field($parent_slug);
                        $sanitized[$role][$parent_slug] = array();
                        
                        if (is_array($submenus)) {
                            foreach ($submenus as $submenu_slug) {
                                $sanitized[$role][$parent_slug][] = sanitize_text_field($submenu_slug);
                            }
                        }
                    }
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize admin bar visibility settings (NEW v1.4.1)
     */
    public function sanitize_adminbar_visibility($input) {
        $sanitized = array();
        
        if (is_array($input)) {
            foreach ($input as $role => $items) {
                $role = sanitize_key($role);
                $sanitized[$role] = array();
                
                if (is_array($items)) {
                    foreach ($items as $item_id) {
                        $sanitized[$role][] = sanitize_text_field($item_id);
                    }
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize admin bar frontend settings (NEW v1.4.1)
     */
    public function sanitize_adminbar_frontend($input) {
        $sanitized = array();
        
        if (is_array($input)) {
            foreach ($input as $role => $value) {
                $role = sanitize_key($role);
                $sanitized[$role] = ($value === 'hide') ? 'hide' : 'show';
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize a single imported option by name (NEW v1.6.1)
     *
     * Reuses the registered sanitize callbacks where available and applies
     * dedicated sanitizers for options that are managed via AJAX (and therefore
     * have no register_setting callback).
     *
     * @param string $option_name
     * @param mixed  $value
     * @return mixed Sanitized value
     */
    private function sanitize_imported_option($option_name, $value) {
        switch ($option_name) {
            case 'cdc_settings':
                return $this->sanitize_basic_settings((array) $value);
            case 'cdc_menu_visibility':
                return $this->sanitize_menu_visibility((array) $value);
            case 'cdc_submenu_visibility':
                return $this->sanitize_submenu_visibility((array) $value);
            case 'cdc_adminbar_visibility':
                return $this->sanitize_adminbar_visibility((array) $value);
            case 'cdc_adminbar_frontend':
                return $this->sanitize_adminbar_frontend((array) $value);
            case 'cdc_login_settings':
                return $this->sanitize_login_settings((array) $value);

            case 'cdc_menu_order':
                return is_array($value)
                    ? array_values(array_filter(array_map('sanitize_text_field', $value)))
                    : array();

            case 'cdc_submenu_order':
                $clean = array();
                if (is_array($value)) {
                    foreach ($value as $parent => $slugs) {
                        if (is_array($slugs)) {
                            $clean[sanitize_text_field($parent)] = array_values(
                                array_filter(array_map('sanitize_text_field', $slugs))
                            );
                        }
                    }
                }
                return $clean;

            case 'cdc_adminbar_custom_items':
                $clean = array();
                if (is_array($value)) {
                    foreach ($value as $item) {
                        if (!is_array($item)) {
                            continue;
                        }
                        $clean[] = array(
                            'id'      => isset($item['id']) ? sanitize_key($item['id']) : 'item-' . time() . '-' . wp_rand(100, 999),
                            'title'   => isset($item['title']) ? sanitize_text_field($item['title']) : '',
                            'url'     => isset($item['url']) ? esc_url_raw($item['url']) : '',
                            'roles'   => isset($item['roles']) && is_array($item['roles']) ? array_map('sanitize_key', $item['roles']) : array(),
                            'new_tab' => !empty($item['new_tab']),
                        );
                    }
                }
                return $clean;

            case 'cdc_dashboard_widgets':
                $clean = array();
                if (is_array($value)) {
                    foreach ($value as $widget) {
                        if (!is_array($widget)) {
                            continue;
                        }
                        $clean[] = array(
                            'id'        => isset($widget['id']) ? sanitize_key($widget['id']) : 'widget-' . time() . '-' . wp_rand(100, 999),
                            'title'     => isset($widget['title']) ? sanitize_text_field($widget['title']) : '',
                            'type'      => isset($widget['type']) ? sanitize_key($widget['type']) : 'shortcode',
                            'shortcode' => isset($widget['shortcode']) ? sanitize_textarea_field($widget['shortcode']) : '',
                            'roles'     => isset($widget['roles']) && is_array($widget['roles']) ? array_map('sanitize_key', $widget['roles']) : array(),
                            'created'   => isset($widget['created']) ? absint($widget['created']) : time(),
                        );
                    }
                }
                return $clean;

            default:
                return $value;
        }
    }

    /**
     * Get current active tab
     */
    private function get_current_tab() {
        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'basic';
        return array_key_exists($tab, $this->get_tabs()) ? $tab : 'basic';
    }
    
    /**
     * Render the main settings page with tabs
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $current_tab = $this->get_current_tab();
        
        ?>
        <div class="wrap cdc-settings-wrap">
            <h1>
                <span class="dashicons dashicons-admin-customizer"></span>
                <?php _e('Dashboard Controller', 'custom-dashboard-controller'); ?>
                <span class="cdc-version">v<?php echo CDC_VERSION; ?></span>
            </h1>
            
            <?php settings_errors('cdc_messages'); ?>
            
            <!-- Tab Navigation -->
            <nav class="nav-tab-wrapper cdc-nav-tabs">
                <?php foreach ($this->get_tabs() as $tab_key => $tab_label): ?>
                    <a href="?page=dashboard-controller&tab=<?php echo esc_attr($tab_key); ?>" 
                       class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($tab_label); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            
            <!-- Tab Content -->
            <div class="cdc-tab-content">
                <?php
                switch ($current_tab) {
                    case 'visibility':
                        $this->render_visibility_tab();
                        break;
                    case 'order':
                        $this->render_order_tab();
                        break;
                    case 'adminbar':
                        $this->render_adminbar_tab();
                        break;
                    case 'widgets':
                        $this->render_widgets_tab();
                        break;
                    case 'login':
                        $this->render_login_tab();
                        break;
                    case 'tools':
                        $this->render_tools_tab();
                        break;
                    default:
                        $this->render_basic_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Basic Settings tab
     */
    private function render_basic_tab() {
        $settings = get_option('cdc_settings', array());
        $menu_color = isset($settings['menu_color']) ? $settings['menu_color'] : '#1d2327';
        $text_color = isset($settings['text_color']) ? $settings['text_color'] : '#f0f0f1';
        $custom_logo = isset($settings['custom_logo']) ? $settings['custom_logo'] : '';
        $logo_text = isset($settings['logo_text']) ? $settings['logo_text'] : '';
        $hover_bg_color = isset($settings['hover_bg_color']) ? $settings['hover_bg_color'] : '';
        $hover_text_color = isset($settings['hover_text_color']) ? $settings['hover_text_color'] : '';
        $active_bg_color = isset($settings['active_bg_color']) ? $settings['active_bg_color'] : '';
        $active_text_color = isset($settings['active_text_color']) ? $settings['active_text_color'] : '';
        // Submenu colors (v1.5.1)
        $submenu_bg_color = isset($settings['submenu_bg_color']) ? $settings['submenu_bg_color'] : '';
        $submenu_text_color = isset($settings['submenu_text_color']) ? $settings['submenu_text_color'] : '';
        $submenu_hover_bg_color = isset($settings['submenu_hover_bg_color']) ? $settings['submenu_hover_bg_color'] : '';
        $submenu_hover_text_color = isset($settings['submenu_hover_text_color']) ? $settings['submenu_hover_text_color'] : '';
        $submenu_active_bg_color = isset($settings['submenu_active_bg_color']) ? $settings['submenu_active_bg_color'] : '';
        $submenu_active_text_color = isset($settings['submenu_active_text_color']) ? $settings['submenu_active_text_color'] : '';
        
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('cdc_basic_settings'); ?>

            <!-- Preset Color Schemes Section (Quick Start) -->
            <div class="cdc-settings-section">
                <h2><?php _e('Preset Color Schemes', 'custom-dashboard-controller'); ?></h2>

                <div class="cdc-info-box cdc-info-blue">
                    <span class="dashicons dashicons-admin-appearance"></span>
                    <div>
                        <strong><?php _e('Quick Start:', 'custom-dashboard-controller'); ?></strong>
                        <?php _e('Choose a pre-built color theme for your admin sidebar. Click to apply instantly.', 'custom-dashboard-controller'); ?>
                    </div>
                </div>

                <?php $schemes = self::get_color_schemes(); ?>
                <div class="cdc-color-schemes-grid">
                    <?php foreach ($schemes as $scheme_id => $scheme): ?>
                        <div class="cdc-scheme-card" data-scheme="<?php echo esc_attr($scheme_id); ?>">
                            <div class="cdc-scheme-preview" style="background-color: <?php echo esc_attr($scheme['preview']); ?>;">
                                <div class="cdc-scheme-preview-item" style="background-color: <?php echo esc_attr($scheme['colors']['active_bg_color']); ?>;"></div>
                                <div class="cdc-scheme-preview-item" style="background-color: <?php echo esc_attr($scheme['colors']['hover_bg_color']); ?>;"></div>
                            </div>
                            <div class="cdc-scheme-name"><?php echo esc_html($scheme['name']); ?></div>
                            <button type="button" class="button button-small cdc-apply-scheme" data-scheme="<?php echo esc_attr($scheme_id); ?>">
                                <?php _e('Apply', 'custom-dashboard-controller'); ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <span id="cdc-scheme-status" class="cdc-status-message"></span>
            </div>

            <!-- Color Settings Section -->
            <div class="cdc-settings-section">
                <h2><?php _e('Color Settings', 'custom-dashboard-controller'); ?></h2>
                <p class="description"><?php _e('Customize the colors of your WordPress admin sidebar.', 'custom-dashboard-controller'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="menu_color"><?php _e('Menu Background Color', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="color" 
                                   id="menu_color" 
                                   name="cdc_settings[menu_color]" 
                                   value="<?php echo esc_attr($menu_color); ?>" 
                                   class="cdc-color-picker">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="text_color"><?php _e('Menu Text Color', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="color"
                                   id="text_color"
                                   name="cdc_settings[text_color]"
                                   value="<?php echo esc_attr($text_color); ?>"
                                   class="cdc-color-picker">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="hover_bg_color"><?php _e('Hover Background Color', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="color"
                                   id="hover_bg_color"
                                   name="cdc_settings[hover_bg_color]"
                                   value="<?php echo esc_attr($hover_bg_color ? $hover_bg_color : '#3c434a'); ?>"
                                   class="cdc-color-picker">
                            <p class="description"><?php _e('Background color when hovering over menu items.', 'custom-dashboard-controller'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="hover_text_color"><?php _e('Hover Text Color', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="color"
                                   id="hover_text_color"
                                   name="cdc_settings[hover_text_color]"
                                   value="<?php echo esc_attr($hover_text_color ? $hover_text_color : '#72aee6'); ?>"
                                   class="cdc-color-picker">
                            <p class="description"><?php _e('Text color when hovering over menu items.', 'custom-dashboard-controller'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="active_bg_color"><?php _e('Active Menu Background Color', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="color"
                                   id="active_bg_color"
                                   name="cdc_settings[active_bg_color]"
                                   value="<?php echo esc_attr($active_bg_color ? $active_bg_color : '#2271b1'); ?>"
                                   class="cdc-color-picker">
                            <p class="description"><?php _e('Background color of the currently active menu item.', 'custom-dashboard-controller'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="active_text_color"><?php _e('Active Menu Text Color', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="color"
                                   id="active_text_color"
                                   name="cdc_settings[active_text_color]"
                                   value="<?php echo esc_attr($active_text_color ? $active_text_color : '#ffffff'); ?>"
                                   class="cdc-color-picker">
                            <p class="description"><?php _e('Text color of the currently active menu item.', 'custom-dashboard-controller'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Submenu Color Settings Section (v1.5.1) -->
            <div class="cdc-settings-section">
                <h2><?php _e('Submenu Color Settings', 'custom-dashboard-controller'); ?></h2>
                <p class="description"><?php _e('Customize the colors of dropdown submenu items.', 'custom-dashboard-controller'); ?></p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="submenu_bg_color"><?php _e('Submenu Background Color', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="color"
                                   id="submenu_bg_color"
                                   name="cdc_settings[submenu_bg_color]"
                                   value="<?php echo esc_attr($submenu_bg_color ? $submenu_bg_color : '#2c3338'); ?>"
                                   class="cdc-color-picker">
                            <p class="description"><?php _e('Background color of the submenu dropdown.', 'custom-dashboard-controller'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="submenu_text_color"><?php _e('Submenu Text Color', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="color"
                                   id="submenu_text_color"
                                   name="cdc_settings[submenu_text_color]"
                                   value="<?php echo esc_attr($submenu_text_color ? $submenu_text_color : '#f0f0f1'); ?>"
                                   class="cdc-color-picker">
                            <p class="description"><?php _e('Text color of submenu items.', 'custom-dashboard-controller'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="submenu_hover_bg_color"><?php _e('Submenu Hover Background', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="color"
                                   id="submenu_hover_bg_color"
                                   name="cdc_settings[submenu_hover_bg_color]"
                                   value="<?php echo esc_attr($submenu_hover_bg_color ? $submenu_hover_bg_color : '#1d2327'); ?>"
                                   class="cdc-color-picker">
                            <p class="description"><?php _e('Background color when hovering over submenu items.', 'custom-dashboard-controller'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="submenu_hover_text_color"><?php _e('Submenu Hover Text Color', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="color"
                                   id="submenu_hover_text_color"
                                   name="cdc_settings[submenu_hover_text_color]"
                                   value="<?php echo esc_attr($submenu_hover_text_color ? $submenu_hover_text_color : '#72aee6'); ?>"
                                   class="cdc-color-picker">
                            <p class="description"><?php _e('Text color when hovering over submenu items.', 'custom-dashboard-controller'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="submenu_active_bg_color"><?php _e('Submenu Active Background', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="color"
                                   id="submenu_active_bg_color"
                                   name="cdc_settings[submenu_active_bg_color]"
                                   value="<?php echo esc_attr($submenu_active_bg_color ? $submenu_active_bg_color : '#2271b1'); ?>"
                                   class="cdc-color-picker">
                            <p class="description"><?php _e('Background color of the currently active submenu item.', 'custom-dashboard-controller'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="submenu_active_text_color"><?php _e('Submenu Active Text Color', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="color"
                                   id="submenu_active_text_color"
                                   name="cdc_settings[submenu_active_text_color]"
                                   value="<?php echo esc_attr($submenu_active_text_color ? $submenu_active_text_color : '#ffffff'); ?>"
                                   class="cdc-color-picker">
                            <p class="description"><?php _e('Text color of the currently active submenu item.', 'custom-dashboard-controller'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Logo Settings Section -->
            <div class="cdc-settings-section">
                <h2><?php _e('Logo Settings', 'custom-dashboard-controller'); ?></h2>
                <p class="description"><?php _e('Add your custom logo at the top of the admin sidebar menu.', 'custom-dashboard-controller'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label><?php _e('Custom Logo', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <div class="cdc-logo-upload">
                                <input type="hidden" 
                                       name="cdc_settings[custom_logo]" 
                                       id="cdc_custom_logo" 
                                       value="<?php echo esc_attr($custom_logo); ?>">
                                
                                <div class="cdc-logo-preview" id="cdc_logo_preview">
                                    <?php if (!empty($custom_logo)): ?>
                                        <img src="<?php echo esc_url($custom_logo); ?>" alt="Logo">
                                    <?php else: ?>
                                        <span class="cdc-no-logo"><?php _e('No logo selected', 'custom-dashboard-controller'); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <button type="button" class="button button-secondary" id="cdc_upload_logo">
                                    <span class="dashicons dashicons-upload"></span>
                                    <?php _e('Upload Logo', 'custom-dashboard-controller'); ?>
                                </button>
                                
                                <button type="button" 
                                        class="button button-secondary" 
                                        id="cdc_remove_logo" 
                                        <?php echo empty($custom_logo) ? 'style="display:none;"' : ''; ?>>
                                    <span class="dashicons dashicons-trash"></span>
                                    <?php _e('Remove', 'custom-dashboard-controller'); ?>
                                </button>
                                
                                <p class="description">
                                    <?php _e('Recommended size: 150-200px width. The logo will appear at the top of the sidebar.', 'custom-dashboard-controller'); ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="logo_text"><?php _e('Logo Text', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="text"
                                   id="logo_text"
                                   name="cdc_settings[logo_text]"
                                   value="<?php echo esc_attr($logo_text); ?>"
                                   class="regular-text"
                                   placeholder="<?php _e('Enter logo text (optional)', 'custom-dashboard-controller'); ?>">
                            <p class="description">
                                <?php _e('Add text to display as logo.', 'custom-dashboard-controller'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <?php submit_button(__('Save Settings', 'custom-dashboard-controller')); ?>
        </form>
        <?php
    }

    /**
     * Render Menu Visibility tab
     */
    private function render_visibility_tab() {
        $menu_visibility = get_option('cdc_menu_visibility', array());
        $submenu_visibility = get_option('cdc_submenu_visibility', array());
        $roles = get_editable_roles();
        $menus = CDC_Menu_Visibility::get_all_admin_menus();
        $all_submenus = CDC_Submenu_Visibility::get_all_submenus();
        
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('cdc_visibility_settings'); ?>
            
            <!-- Parent Menu Visibility Section -->
            <div class="cdc-settings-section">
                <h2><?php _e('Parent Menu Visibility', 'custom-dashboard-controller'); ?></h2>
                
                <div class="cdc-info-box cdc-info-green">
                    <span class="dashicons dashicons-info"></span>
                    <div>
                        <strong><?php _e('How it works:', 'custom-dashboard-controller'); ?></strong>
                        <?php _e('Check the boxes to HIDE menus. Checked = Hidden, Unchecked = Visible.', 'custom-dashboard-controller'); ?>
                    </div>
                </div>

                <div class="cdc-info-box cdc-info-orange">
                    <span class="dashicons dashicons-lock"></span>
                    <div>
                        <strong><?php _e('Protection:', 'custom-dashboard-controller'); ?></strong>
                        <?php _e('Dashboard Controller menu cannot be hidden for Administrators to prevent accidental lockout. Items marked with 🔒 are protected.', 'custom-dashboard-controller'); ?>
                    </div>
                </div>
                
                <div class="cdc-roles-grid">
                    <?php foreach ($roles as $role_slug => $role_data): ?>
                        <?php
                        $role_hidden = isset($menu_visibility[$role_slug]) ? $menu_visibility[$role_slug] : array();
                        $hidden_count = count($role_hidden);
                        ?>
                        <div class="cdc-role-card">
                            <div class="cdc-role-header">
                                <span class="cdc-role-name"><?php echo esc_html($role_data['name']); ?></span>
                                <span class="cdc-hidden-count"><?php echo $hidden_count; ?> <?php _e('hidden', 'custom-dashboard-controller'); ?></span>
                            </div>
                            
                            <div class="cdc-role-actions">
                                <button type="button" class="button button-small cdc-check-all" data-target="menu-<?php echo esc_attr($role_slug); ?>">
                                    <?php _e('Hide All', 'custom-dashboard-controller'); ?>
                                </button>
                                <button type="button" class="button button-small cdc-uncheck-all" data-target="menu-<?php echo esc_attr($role_slug); ?>">
                                    <?php _e('Show All', 'custom-dashboard-controller'); ?>
                                </button>
                            </div>
                            
                            <div class="cdc-menu-list" data-group="menu-<?php echo esc_attr($role_slug); ?>">
                                <?php foreach ($menus as $menu_item): ?>
                                    <?php
                                    $is_hidden = in_array($menu_item['slug'], $role_hidden);
                                    $is_protected = CDC_Menu_Visibility::is_menu_protected($menu_item['slug'], $role_slug);
                                    $card_class = $is_hidden ? 'cdc-menu-item cdc-hidden' : 'cdc-menu-item';
                                    if ($is_protected) {
                                        $card_class .= ' cdc-protected';
                                    }
                                    ?>
                                    <label class="<?php echo esc_attr($card_class); ?>" <?php echo $is_protected ? 'title="' . esc_attr__('Protected: Cannot be hidden for Administrators', 'custom-dashboard-controller') . '"' : ''; ?>>
                                        <input type="checkbox"
                                               name="cdc_menu_visibility[<?php echo esc_attr($role_slug); ?>][]"
                                               value="<?php echo esc_attr($menu_item['slug']); ?>"
                                               <?php checked($is_hidden && !$is_protected); ?>
                                               <?php disabled($is_protected); ?>>
                                        <span class="cdc-menu-name"><?php echo esc_html($menu_item['name']); ?></span>
                                        <span class="cdc-menu-status"><?php echo $is_protected ? '🔒' : ($is_hidden ? '🚫' : '✅'); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Submenu Visibility Section (NEW v1.4.0) -->
            <div class="cdc-settings-section">
                <h2><?php _e('Submenu Visibility', 'custom-dashboard-controller'); ?></h2>
                
                <div class="cdc-info-box cdc-info-blue">
                    <span class="dashicons dashicons-info"></span>
                    <div>
                        <strong><?php _e('New in v1.4.0:', 'custom-dashboard-controller'); ?></strong>
                        <?php _e('Control visibility of individual submenu items. Select a parent menu, then choose which submenus to hide for each role.', 'custom-dashboard-controller'); ?>
                    </div>
                </div>
                
                <!-- Parent Menu Selector -->
                <div class="cdc-submenu-selector">
                    <label for="cdc-parent-select">
                        <strong><?php _e('Select Parent Menu:', 'custom-dashboard-controller'); ?></strong>
                    </label>
                    <select id="cdc-parent-select" class="cdc-select">
                        <option value=""><?php _e('-- Choose a menu --', 'custom-dashboard-controller'); ?></option>
                        <?php foreach ($menus as $menu_item): ?>
                            <?php if (isset($all_submenus[$menu_item['slug']]) && !empty($all_submenus[$menu_item['slug']])): ?>
                                <option value="<?php echo esc_attr($menu_item['slug']); ?>">
                                    <?php echo esc_html($menu_item['name']); ?>
                                    (<?php echo count($all_submenus[$menu_item['slug']]); ?> <?php _e('submenus', 'custom-dashboard-controller'); ?>)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Submenu Panels (one per parent, shown/hidden via JS) -->
                <?php foreach ($all_submenus as $parent_slug => $submenus): ?>
                    <?php if (empty($submenus)) continue; ?>
                    
                    <div class="cdc-submenu-panel" data-parent="<?php echo esc_attr($parent_slug); ?>" style="display: none;">
                        <h3>
                            <?php echo esc_html(CDC_Submenu_Visibility::get_parent_name($parent_slug)); ?>
                            <?php _e('Submenus', 'custom-dashboard-controller'); ?>
                        </h3>
                        
                        <div class="cdc-roles-grid">
                            <?php foreach ($roles as $role_slug => $role_data): ?>
                                <?php
                                $role_hidden = isset($submenu_visibility[$role_slug][$parent_slug]) 
                                    ? $submenu_visibility[$role_slug][$parent_slug] 
                                    : array();
                                $hidden_count = count($role_hidden);
                                ?>
                                <div class="cdc-role-card">
                                    <div class="cdc-role-header">
                                        <span class="cdc-role-name"><?php echo esc_html($role_data['name']); ?></span>
                                        <span class="cdc-hidden-count"><?php echo $hidden_count; ?> <?php _e('hidden', 'custom-dashboard-controller'); ?></span>
                                    </div>
                                    
                                    <div class="cdc-role-actions">
                                        <button type="button" class="button button-small cdc-check-all" data-target="submenu-<?php echo esc_attr($parent_slug); ?>-<?php echo esc_attr($role_slug); ?>">
                                            <?php _e('Hide All', 'custom-dashboard-controller'); ?>
                                        </button>
                                        <button type="button" class="button button-small cdc-uncheck-all" data-target="submenu-<?php echo esc_attr($parent_slug); ?>-<?php echo esc_attr($role_slug); ?>">
                                            <?php _e('Show All', 'custom-dashboard-controller'); ?>
                                        </button>
                                    </div>
                                    
                                    <div class="cdc-menu-list" data-group="submenu-<?php echo esc_attr($parent_slug); ?>-<?php echo esc_attr($role_slug); ?>">
                                        <?php foreach ($submenus as $submenu_item): ?>
                                            <?php
                                            $is_hidden = in_array($submenu_item['slug'], $role_hidden);
                                            $card_class = $is_hidden ? 'cdc-menu-item cdc-hidden' : 'cdc-menu-item';
                                            ?>
                                            <label class="<?php echo esc_attr($card_class); ?>">
                                                <input type="checkbox" 
                                                       name="cdc_submenu_visibility[<?php echo esc_attr($role_slug); ?>][<?php echo esc_attr($parent_slug); ?>][]" 
                                                       value="<?php echo esc_attr($submenu_item['slug']); ?>"
                                                       <?php checked($is_hidden); ?>>
                                                <span class="cdc-menu-name"><?php echo esc_html($submenu_item['name']); ?></span>
                                                <span class="cdc-menu-status"><?php echo $is_hidden ? '🚫' : '✅'; ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php submit_button(__('Save Visibility Settings', 'custom-dashboard-controller')); ?>
        </form>
        <?php
    }
    
    /**
     * Render Menu Order tab
     */
    private function render_order_tab() {
        $menus = CDC_Menu_Order::get_ordered_menus();
        $all_submenus = CDC_Submenu_Visibility::get_all_submenus();
        
        ?>
        <!-- Parent Menu Order Section -->
        <div class="cdc-settings-section">
            <h2><?php _e('Parent Menu Order', 'custom-dashboard-controller'); ?></h2>
            
            <div class="cdc-info-box cdc-info-blue">
                <span class="dashicons dashicons-info"></span>
                <div>
                    <strong><?php _e('How to use:', 'custom-dashboard-controller'); ?></strong>
                    <?php _e('Drag and drop menu items to reorder. Changes apply to all users.', 'custom-dashboard-controller'); ?>
                </div>
            </div>
            
            <div class="cdc-order-layout">
                <div class="cdc-order-panel">
                    <h3><?php _e('Drag to Reorder', 'custom-dashboard-controller'); ?></h3>
                    
                    <ul id="cdc-sortable-menu" class="cdc-sortable-list">
                        <?php foreach ($menus as $menu_item): ?>
                            <li class="cdc-sortable-item" data-slug="<?php echo esc_attr($menu_item['slug']); ?>">
                                <span class="cdc-drag-handle">
                                    <span class="dashicons dashicons-menu"></span>
                                </span>
                                <span class="cdc-item-icon">
                                    <?php if (!empty($menu_item['icon']) && strpos($menu_item['icon'], 'dashicons') !== false): ?>
                                        <span class="dashicons <?php echo esc_attr($menu_item['icon']); ?>"></span>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-admin-generic"></span>
                                    <?php endif; ?>
                                </span>
                                <span class="cdc-item-name"><?php echo esc_html($menu_item['name']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <div class="cdc-order-actions">
                        <button type="button" id="cdc-save-menu-order" class="button button-primary">
                            <span class="dashicons dashicons-saved"></span>
                            <?php _e('Save Order', 'custom-dashboard-controller'); ?>
                        </button>
                        <button type="button" id="cdc-reset-menu-order" class="button button-secondary">
                            <span class="dashicons dashicons-image-rotate"></span>
                            <?php _e('Reset to Default', 'custom-dashboard-controller'); ?>
                        </button>
                        <span id="cdc-menu-order-status" class="cdc-status-message"></span>
                    </div>
                </div>
                
                <div class="cdc-preview-panel">
                    <h3><?php _e('Live Preview', 'custom-dashboard-controller'); ?></h3>
                    <div id="cdc-menu-preview" class="cdc-sidebar-preview">
                        <?php foreach ($menus as $menu_item): ?>
                            <div class="cdc-preview-item" data-slug="<?php echo esc_attr($menu_item['slug']); ?>">
                                <?php if (!empty($menu_item['icon']) && strpos($menu_item['icon'], 'dashicons') !== false): ?>
                                    <span class="dashicons <?php echo esc_attr($menu_item['icon']); ?>"></span>
                                <?php else: ?>
                                    <span class="dashicons dashicons-admin-generic"></span>
                                <?php endif; ?>
                                <span><?php echo esc_html($menu_item['name']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Submenu Order Section (NEW v1.4.0) -->
        <div class="cdc-settings-section">
            <h2><?php _e('Submenu Order', 'custom-dashboard-controller'); ?></h2>
            
            <div class="cdc-info-box cdc-info-green">
                <span class="dashicons dashicons-info"></span>
                <div>
                    <strong><?php _e('New in v1.4.0:', 'custom-dashboard-controller'); ?></strong>
                    <?php _e('Reorder submenu items within each parent menu. Select a parent menu to reorder its submenus.', 'custom-dashboard-controller'); ?>
                </div>
            </div>
            
            <!-- Parent Menu Selector for Submenu Order -->
            <div class="cdc-submenu-selector">
                <label for="cdc-submenu-order-select">
                    <strong><?php _e('Select Parent Menu:', 'custom-dashboard-controller'); ?></strong>
                </label>
                <select id="cdc-submenu-order-select" class="cdc-select">
                    <option value=""><?php _e('-- Choose a menu --', 'custom-dashboard-controller'); ?></option>
                    <?php foreach ($menus as $menu_item): ?>
                        <?php if (isset($all_submenus[$menu_item['slug']]) && !empty($all_submenus[$menu_item['slug']])): ?>
                            <option value="<?php echo esc_attr($menu_item['slug']); ?>">
                                <?php echo esc_html($menu_item['name']); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Submenu Order Panels -->
            <?php foreach ($all_submenus as $parent_slug => $submenus): ?>
                <?php 
                if (empty($submenus)) continue;
                $ordered_submenus = CDC_Submenu_Order::get_ordered_submenus($parent_slug);
                ?>
                
                <div class="cdc-submenu-order-panel" data-parent="<?php echo esc_attr($parent_slug); ?>" style="display: none;">
                    <div class="cdc-order-layout">
                        <div class="cdc-order-panel">
                            <h3>
                                <?php echo esc_html(CDC_Submenu_Visibility::get_parent_name($parent_slug)); ?>
                                - <?php _e('Submenus', 'custom-dashboard-controller'); ?>
                            </h3>
                            
                            <ul class="cdc-sortable-list cdc-sortable-submenu" data-parent="<?php echo esc_attr($parent_slug); ?>">
                                <?php foreach ($ordered_submenus as $submenu_item): ?>
                                    <li class="cdc-sortable-item" data-slug="<?php echo esc_attr($submenu_item['slug']); ?>">
                                        <span class="cdc-drag-handle">
                                            <span class="dashicons dashicons-menu"></span>
                                        </span>
                                        <span class="cdc-item-name"><?php echo esc_html($submenu_item['name']); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            
                            <div class="cdc-order-actions">
                                <button type="button" class="button button-primary cdc-save-submenu-order" data-parent="<?php echo esc_attr($parent_slug); ?>">
                                    <span class="dashicons dashicons-saved"></span>
                                    <?php _e('Save Order', 'custom-dashboard-controller'); ?>
                                </button>
                                <button type="button" class="button button-secondary cdc-reset-submenu-order" data-parent="<?php echo esc_attr($parent_slug); ?>">
                                    <span class="dashicons dashicons-image-rotate"></span>
                                    <?php _e('Reset', 'custom-dashboard-controller'); ?>
                                </button>
                                <span class="cdc-status-message cdc-submenu-status" data-parent="<?php echo esc_attr($parent_slug); ?>"></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render Admin Bar tab (NEW v1.4.1)
     */
    private function render_adminbar_tab() {
        $adminbar_visibility = get_option('cdc_adminbar_visibility', array());
        $adminbar_frontend = get_option('cdc_adminbar_frontend', array());
        $custom_items = get_option('cdc_adminbar_custom_items', array());
        $roles = get_editable_roles();
        $adminbar_items = CDC_Admin_Bar::get_all_admin_bar_items();
        
        ?>
        <form method="post" action="options.php" id="cdc-adminbar-form">
            <?php settings_fields('cdc_adminbar_settings'); ?>
            
            <!-- Admin Bar Item Visibility Section -->
            <div class="cdc-settings-section">
                <h2><?php _e('Admin Bar Item Visibility', 'custom-dashboard-controller'); ?></h2>
                
                <div class="cdc-info-box cdc-info-blue">
                    <span class="dashicons dashicons-info"></span>
                    <div>
                        <strong><?php _e('How it works:', 'custom-dashboard-controller'); ?></strong>
                        <?php _e('Check the boxes to HIDE admin bar items. This controls the top toolbar in WordPress admin.', 'custom-dashboard-controller'); ?>
                    </div>
                </div>
                
                <?php if (empty($adminbar_items)): ?>
                    <div class="cdc-info-box cdc-info-orange">
                        <span class="dashicons dashicons-warning"></span>
                        <?php _e('Admin bar items will be detected after page reload. Please save and refresh.', 'custom-dashboard-controller'); ?>
                    </div>
                <?php else: ?>
                    <div class="cdc-roles-grid">
                        <?php foreach ($roles as $role_slug => $role_data): ?>
                            <?php
                            $role_hidden = isset($adminbar_visibility[$role_slug]) ? $adminbar_visibility[$role_slug] : array();
                            $hidden_count = count($role_hidden);
                            ?>
                            <div class="cdc-role-card">
                                <div class="cdc-role-header">
                                    <span class="cdc-role-name"><?php echo esc_html($role_data['name']); ?></span>
                                    <span class="cdc-hidden-count"><?php echo $hidden_count; ?> <?php _e('hidden', 'custom-dashboard-controller'); ?></span>
                                </div>
                                
                                <div class="cdc-role-actions">
                                    <button type="button" class="button button-small cdc-check-all" data-target="adminbar-<?php echo esc_attr($role_slug); ?>">
                                        <?php _e('Hide All', 'custom-dashboard-controller'); ?>
                                    </button>
                                    <button type="button" class="button button-small cdc-uncheck-all" data-target="adminbar-<?php echo esc_attr($role_slug); ?>">
                                        <?php _e('Show All', 'custom-dashboard-controller'); ?>
                                    </button>
                                </div>
                                
                                <div class="cdc-menu-list" data-group="adminbar-<?php echo esc_attr($role_slug); ?>">
                                    <?php foreach ($adminbar_items as $item): ?>
                                        <?php
                                        $is_hidden = in_array($item['id'], $role_hidden);
                                        $card_class = $is_hidden ? 'cdc-menu-item cdc-hidden' : 'cdc-menu-item';
                                        ?>
                                        <label class="<?php echo esc_attr($card_class); ?>">
                                            <input type="checkbox" 
                                                   name="cdc_adminbar_visibility[<?php echo esc_attr($role_slug); ?>][]" 
                                                   value="<?php echo esc_attr($item['id']); ?>"
                                                   <?php checked($is_hidden); ?>>
                                            <span class="cdc-menu-name"><?php echo esc_html($item['title']); ?></span>
                                            <span class="cdc-menu-status"><?php echo $is_hidden ? '🚫' : '✅'; ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Frontend Admin Bar Visibility Section -->
            <div class="cdc-settings-section">
                <h2><?php _e('Frontend Admin Bar', 'custom-dashboard-controller'); ?></h2>
                
                <div class="cdc-info-box cdc-info-green">
                    <span class="dashicons dashicons-info"></span>
                    <div>
                        <strong><?php _e('Hide on Frontend:', 'custom-dashboard-controller'); ?></strong>
                        <?php _e('Choose which roles should NOT see the admin bar when viewing the frontend of the website.', 'custom-dashboard-controller'); ?>
                    </div>
                </div>
                
                <table class="form-table cdc-frontend-table">
                    <thead>
                        <tr>
                            <th><?php _e('User Role', 'custom-dashboard-controller'); ?></th>
                            <th><?php _e('Frontend Admin Bar', 'custom-dashboard-controller'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $role_slug => $role_data): ?>
                            <?php $current_value = isset($adminbar_frontend[$role_slug]) ? $adminbar_frontend[$role_slug] : 'show'; ?>
                            <tr>
                                <td><strong><?php echo esc_html($role_data['name']); ?></strong></td>
                                <td>
                                    <label class="cdc-toggle-switch">
                                        <input type="hidden" name="cdc_adminbar_frontend[<?php echo esc_attr($role_slug); ?>]" value="show">
                                        <input type="checkbox" 
                                               name="cdc_adminbar_frontend[<?php echo esc_attr($role_slug); ?>]" 
                                               value="hide"
                                               <?php checked($current_value, 'hide'); ?>>
                                        <span class="cdc-toggle-slider"></span>
                                        <span class="cdc-toggle-label"><?php _e('Hide on Frontend', 'custom-dashboard-controller'); ?></span>
                                    </label>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php submit_button(__('Save Admin Bar Settings', 'custom-dashboard-controller')); ?>
        </form>
        
        <!-- Custom Admin Bar Items Section -->
        <div class="cdc-settings-section">
            <h2><?php _e('Custom Admin Bar Items', 'custom-dashboard-controller'); ?></h2>
            
            <div class="cdc-info-box cdc-info-purple">
                <span class="dashicons dashicons-info"></span>
                <div>
                    <strong><?php _e('Add Custom Links:', 'custom-dashboard-controller'); ?></strong>
                    <?php _e('Add your own custom links to the admin bar. You can control which roles can see each link.', 'custom-dashboard-controller'); ?>
                </div>
            </div>
            
            <!-- Add New Item Form -->
            <div class="cdc-custom-item-form">
                <h3><?php _e('Add New Item', 'custom-dashboard-controller'); ?></h3>
                
                <table class="form-table">
                    <tr>
                        <th><label for="cdc-custom-title"><?php _e('Title', 'custom-dashboard-controller'); ?></label></th>
                        <td>
                            <input type="text" id="cdc-custom-title" class="regular-text" placeholder="<?php _e('My Custom Link', 'custom-dashboard-controller'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="cdc-custom-url"><?php _e('URL', 'custom-dashboard-controller'); ?></label></th>
                        <td>
                            <input type="url" id="cdc-custom-url" class="regular-text" placeholder="https://example.com">
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Open in New Tab', 'custom-dashboard-controller'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" id="cdc-custom-newtab">
                                <?php _e('Yes, open link in new tab', 'custom-dashboard-controller'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Visible to Roles', 'custom-dashboard-controller'); ?></th>
                        <td>
                            <div class="cdc-role-checkboxes">
                                <?php foreach ($roles as $role_slug => $role_data): ?>
                                    <label>
                                        <input type="checkbox" class="cdc-custom-role" value="<?php echo esc_attr($role_slug); ?>" checked>
                                        <?php echo esc_html($role_data['name']); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <p class="description"><?php _e('Leave all unchecked to show to everyone.', 'custom-dashboard-controller'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p>
                    <button type="button" id="cdc-add-custom-item" class="button button-primary">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Add Item', 'custom-dashboard-controller'); ?>
                    </button>
                    <span id="cdc-custom-item-status" class="cdc-status-message"></span>
                </p>
            </div>
            
            <!-- Existing Custom Items List -->
            <?php if (!empty($custom_items)): ?>
                <div class="cdc-custom-items-list">
                    <h3><?php _e('Existing Custom Items', 'custom-dashboard-controller'); ?></h3>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Title', 'custom-dashboard-controller'); ?></th>
                                <th><?php _e('URL', 'custom-dashboard-controller'); ?></th>
                                <th><?php _e('New Tab', 'custom-dashboard-controller'); ?></th>
                                <th><?php _e('Roles', 'custom-dashboard-controller'); ?></th>
                                <th><?php _e('Actions', 'custom-dashboard-controller'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($custom_items as $item): ?>
                                <tr data-item-id="<?php echo esc_attr($item['id']); ?>">
                                    <td><strong><?php echo esc_html($item['title']); ?></strong></td>
                                    <td>
                                        <a href="<?php echo esc_url($item['url']); ?>" target="_blank">
                                            <?php echo esc_html($item['url']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo !empty($item['new_tab']) ? '✅' : '—'; ?></td>
                                    <td>
                                        <?php 
                                        if (empty($item['roles'])) {
                                            _e('All Roles', 'custom-dashboard-controller');
                                        } else {
                                            echo esc_html(implode(', ', array_map('ucfirst', $item['roles'])));
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-small cdc-delete-custom-item" data-item-id="<?php echo esc_attr($item['id']); ?>">
                                            <span class="dashicons dashicons-trash"></span>
                                            <?php _e('Delete', 'custom-dashboard-controller'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render Dashboard Widgets tab (NEW v1.4.2)
     */
    private function render_widgets_tab() {
        $widgets = CDC_Dashboard_Widgets::get_all_widgets();
        $roles = get_editable_roles();
        
        ?>
        <!-- Hidden field for edit mode -->
        <input type="hidden" id="cdc-widget-edit-id" value="">
        
        <!-- Add New Widget Section -->
        <div class="cdc-settings-section">
            <h2 id="cdc-widget-form-title"><?php _e('Add Shortcode Widget', 'custom-dashboard-controller'); ?></h2>
            
            <div class="cdc-info-box cdc-info-blue">
                <span class="dashicons dashicons-info"></span>
                <div>
                    <strong><?php _e('Shortcode Widget:', 'custom-dashboard-controller'); ?></strong>
                    <?php _e('Add any shortcode to display on your WordPress dashboard. The widget will auto-refresh every hour.', 'custom-dashboard-controller'); ?>
                </div>
            </div>
            
            <div class="cdc-widget-form">
                <table class="form-table">
                    <tr>
                        <th><label for="cdc-widget-title"><?php _e('Widget Title', 'custom-dashboard-controller'); ?> <span class="required">*</span></label></th>
                        <td>
                            <input type="text" id="cdc-widget-title" class="regular-text" placeholder="<?php _e('My Custom Widget', 'custom-dashboard-controller'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="cdc-widget-shortcode"><?php _e('Shortcode', 'custom-dashboard-controller'); ?> <span class="required">*</span></label></th>
                        <td>
                            <textarea id="cdc-widget-shortcode" class="large-text code" rows="3" placeholder="[your_shortcode]"></textarea>
                            <p class="description"><?php _e('Enter the shortcode including the square brackets. Example: [wpforms id="89"]', 'custom-dashboard-controller'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Visible to Roles', 'custom-dashboard-controller'); ?></th>
                        <td>
                            <div class="cdc-role-checkboxes">
                                <?php foreach ($roles as $role_slug => $role_data): ?>
                                    <label>
                                        <input type="checkbox" class="cdc-widget-role" value="<?php echo esc_attr($role_slug); ?>" checked>
                                        <?php echo esc_html($role_data['name']); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <p class="description"><?php _e('Select which user roles can see this widget. Uncheck all to show to everyone.', 'custom-dashboard-controller'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="button" id="cdc-add-widget" class="button button-primary">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <span class="cdc-btn-text"><?php _e('Add Widget', 'custom-dashboard-controller'); ?></span>
                    </button>
                    <button type="button" id="cdc-cancel-edit" class="button button-secondary" style="display: none;">
                        <?php _e('Cancel Edit', 'custom-dashboard-controller'); ?>
                    </button>
                    <span id="cdc-widget-status" class="cdc-status-message"></span>
                </p>
            </div>
        </div>
        
        <!-- Existing Widgets List -->
        <div class="cdc-settings-section">
            <h2><?php _e('Existing Dashboard Widgets', 'custom-dashboard-controller'); ?></h2>
            
            <?php if (empty($widgets)): ?>
                <div class="cdc-info-box cdc-info-orange">
                    <span class="dashicons dashicons-warning"></span>
                    <?php _e('No custom dashboard widgets yet. Add one above!', 'custom-dashboard-controller'); ?>
                </div>
            <?php else: ?>
                <div class="cdc-info-box cdc-info-green">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php printf(__('You have %d custom widget(s). Visit your Dashboard to see them.', 'custom-dashboard-controller'), count($widgets)); ?>
                    <a href="<?php echo admin_url('index.php'); ?>" class="button button-small" style="margin-left: 10px;">
                        <?php _e('Go to Dashboard', 'custom-dashboard-controller'); ?>
                    </a>
                </div>
                
                <table class="wp-list-table widefat fixed striped cdc-widgets-table">
                    <thead>
                        <tr>
                            <th style="width: 18%;"><?php _e('Title', 'custom-dashboard-controller'); ?></th>
                            <th style="width: 32%;"><?php _e('Shortcode', 'custom-dashboard-controller'); ?></th>
                            <th style="width: 25%;"><?php _e('Visible to', 'custom-dashboard-controller'); ?></th>
                            <th style="width: 25%;"><?php _e('Actions', 'custom-dashboard-controller'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($widgets as $widget): ?>
                            <tr data-widget-id="<?php echo esc_attr($widget['id']); ?>">
                                <td>
                                    <strong><?php echo esc_html($widget['title']); ?></strong>
                                    <div class="row-actions">
                                        <span class="widget-type"><?php _e('Shortcode Widget', 'custom-dashboard-controller'); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <code class="cdc-shortcode-preview"><?php echo esc_html($widget['shortcode']); ?></code>
                                </td>
                                <td>
                                    <?php 
                                    if (empty($widget['roles'])) {
                                        echo '<span class="cdc-all-roles">' . __('All Roles', 'custom-dashboard-controller') . '</span>';
                                    } else {
                                        $role_names = array();
                                        foreach ($widget['roles'] as $role_slug) {
                                            if (isset($roles[$role_slug])) {
                                                $role_names[] = $roles[$role_slug]['name'];
                                            }
                                        }
                                        echo esc_html(implode(', ', $role_names));
                                    }
                                    ?>
                                </td>
                                <td>
                                    <button type="button" class="button button-small cdc-edit-widget" data-widget-id="<?php echo esc_attr($widget['id']); ?>">
                                        <span class="dashicons dashicons-edit"></span>
                                        <?php _e('Edit', 'custom-dashboard-controller'); ?>
                                    </button>
                                    <button type="button" class="button button-small cdc-delete-widget" data-widget-id="<?php echo esc_attr($widget['id']); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                        <?php _e('Delete', 'custom-dashboard-controller'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Simple Note Section -->
        <div class="cdc-settings-section">
            <div class="cdc-info-box cdc-info-purple">
                <span class="dashicons dashicons-info"></span>
                <div>
                    <strong><?php _e('Note:', 'custom-dashboard-controller'); ?></strong>
                    <?php _e('Most plugin shortcodes are designed for frontend pages only. Some shortcodes like WPForms work in the admin dashboard. Check your plugin documentation for shortcode compatibility.', 'custom-dashboard-controller'); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render Login Page Branding tab (NEW v1.5.2)
     */
    private function render_login_tab() {
        $login_branding = new CDC_Login_Branding();
        $defaults = $login_branding->get_defaults();
        $settings = get_option('cdc_login_settings', $defaults);
        $settings = wp_parse_args($settings, $defaults);

        ?>
        <form method="post" action="options.php" id="cdc-login-form">
            <?php settings_fields('cdc_login_settings'); ?>

            <!-- Layout Style Section -->
            <div class="cdc-settings-section">
                <h2><?php _e('Layout Style', 'custom-dashboard-controller'); ?></h2>

                <div class="cdc-info-box cdc-info-blue">
                    <span class="dashicons dashicons-info"></span>
                    <div>
                        <strong><?php _e('New in v1.5.2:', 'custom-dashboard-controller'); ?></strong>
                        <?php _e('Customize your WordPress login page with custom logo, colors, background, and layout styles.', 'custom-dashboard-controller'); ?>
                    </div>
                </div>

                <div class="cdc-layout-selector">
                    <label class="cdc-layout-option <?php echo $settings['layout_style'] === 'center' ? 'active' : ''; ?>">
                        <input type="radio" name="cdc_login_settings[layout_style]" value="center" <?php checked($settings['layout_style'], 'center'); ?>>
                        <div class="cdc-layout-preview cdc-layout-center-preview">
                            <div class="cdc-layout-form-box"></div>
                        </div>
                        <span class="cdc-layout-label"><?php _e('Center', 'custom-dashboard-controller'); ?></span>
                    </label>

                    <label class="cdc-layout-option <?php echo $settings['layout_style'] === 'two-column-left' ? 'active' : ''; ?>">
                        <input type="radio" name="cdc_login_settings[layout_style]" value="two-column-left" <?php checked($settings['layout_style'], 'two-column-left'); ?>>
                        <div class="cdc-layout-preview cdc-layout-two-col-preview">
                            <div class="cdc-layout-col cdc-layout-form-col"></div>
                            <div class="cdc-layout-col cdc-layout-image-col"></div>
                        </div>
                        <span class="cdc-layout-label"><?php _e('Two Column (Form Left)', 'custom-dashboard-controller'); ?></span>
                    </label>

                    <label class="cdc-layout-option <?php echo $settings['layout_style'] === 'two-column-right' ? 'active' : ''; ?>">
                        <input type="radio" name="cdc_login_settings[layout_style]" value="two-column-right" <?php checked($settings['layout_style'], 'two-column-right'); ?>>
                        <div class="cdc-layout-preview cdc-layout-two-col-preview">
                            <div class="cdc-layout-col cdc-layout-image-col"></div>
                            <div class="cdc-layout-col cdc-layout-form-col"></div>
                        </div>
                        <span class="cdc-layout-label"><?php _e('Two Column (Form Right)', 'custom-dashboard-controller'); ?></span>
                    </label>
                </div>
            </div>

            <!-- Logo Settings Section -->
            <div class="cdc-settings-section">
                <h2><?php _e('Login Logo', 'custom-dashboard-controller'); ?></h2>
                <p class="description"><?php _e('Replace the default WordPress logo on the login page.', 'custom-dashboard-controller'); ?></p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label><?php _e('Custom Logo', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <div class="cdc-logo-upload">
                                <input type="hidden"
                                       name="cdc_login_settings[custom_logo]"
                                       id="cdc_login_logo"
                                       value="<?php echo esc_attr($settings['custom_logo']); ?>">

                                <div class="cdc-logo-preview" id="cdc_login_logo_preview">
                                    <?php if (!empty($settings['custom_logo'])): ?>
                                        <img src="<?php echo esc_url($settings['custom_logo']); ?>" alt="Logo">
                                    <?php else: ?>
                                        <span class="cdc-no-logo"><?php _e('No logo selected', 'custom-dashboard-controller'); ?></span>
                                    <?php endif; ?>
                                </div>

                                <button type="button" class="button button-secondary" id="cdc_upload_login_logo">
                                    <span class="dashicons dashicons-upload"></span>
                                    <?php _e('Upload Logo', 'custom-dashboard-controller'); ?>
                                </button>

                                <button type="button"
                                        class="button button-secondary"
                                        id="cdc_remove_login_logo"
                                        <?php echo empty($settings['custom_logo']) ? 'style="display:none;"' : ''; ?>>
                                    <span class="dashicons dashicons-trash"></span>
                                    <?php _e('Remove', 'custom-dashboard-controller'); ?>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="login_logo_width"><?php _e('Logo Width (px)', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="number"
                                   id="login_logo_width"
                                   name="cdc_login_settings[logo_width]"
                                   value="<?php echo esc_attr($settings['logo_width']); ?>"
                                   class="small-text"
                                   min="50"
                                   max="500">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="login_logo_height"><?php _e('Logo Height (px)', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="number"
                                   id="login_logo_height"
                                   name="cdc_login_settings[logo_height]"
                                   value="<?php echo esc_attr($settings['logo_height']); ?>"
                                   class="small-text"
                                   min="30"
                                   max="300">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="login_logo_url"><?php _e('Logo Link URL', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="url"
                                   id="login_logo_url"
                                   name="cdc_login_settings[logo_url]"
                                   value="<?php echo esc_attr($settings['logo_url']); ?>"
                                   class="regular-text"
                                   placeholder="<?php echo esc_attr(home_url('/')); ?>">
                            <p class="description"><?php _e('URL when clicking the logo. Leave empty to use site homepage.', 'custom-dashboard-controller'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Background Settings Section -->
            <div class="cdc-settings-section">
                <h2><?php _e('Background Settings', 'custom-dashboard-controller'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Background Type', 'custom-dashboard-controller'); ?></th>
                        <td>
                            <fieldset class="cdc-bg-type-selector">
                                <label class="cdc-radio-card <?php echo $settings['bg_type'] === 'color' ? 'active' : ''; ?>">
                                    <input type="radio" name="cdc_login_settings[bg_type]" value="color" <?php checked($settings['bg_type'], 'color'); ?>>
                                    <span class="dashicons dashicons-art"></span>
                                    <span><?php _e('Solid Color', 'custom-dashboard-controller'); ?></span>
                                </label>
                                <label class="cdc-radio-card <?php echo $settings['bg_type'] === 'image' ? 'active' : ''; ?>">
                                    <input type="radio" name="cdc_login_settings[bg_type]" value="image" <?php checked($settings['bg_type'], 'image'); ?>>
                                    <span class="dashicons dashicons-format-image"></span>
                                    <span><?php _e('Image', 'custom-dashboard-controller'); ?></span>
                                </label>
                                <label class="cdc-radio-card <?php echo $settings['bg_type'] === 'gradient' ? 'active' : ''; ?>">
                                    <input type="radio" name="cdc_login_settings[bg_type]" value="gradient" <?php checked($settings['bg_type'], 'gradient'); ?>>
                                    <span class="dashicons dashicons-image-rotate"></span>
                                    <span><?php _e('Gradient', 'custom-dashboard-controller'); ?></span>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>

                <!-- Solid Color Options -->
                <div class="cdc-bg-options cdc-bg-color-options" <?php echo $settings['bg_type'] !== 'color' ? 'style="display:none;"' : ''; ?>>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="login_bg_color"><?php _e('Background Color', 'custom-dashboard-controller'); ?></label>
                            </th>
                            <td>
                                <input type="color"
                                       id="login_bg_color"
                                       name="cdc_login_settings[bg_color]"
                                       value="<?php echo esc_attr($settings['bg_color']); ?>"
                                       class="cdc-color-picker">
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Image Options -->
                <div class="cdc-bg-options cdc-bg-image-options" <?php echo $settings['bg_type'] !== 'image' ? 'style="display:none;"' : ''; ?>>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label><?php _e('Background Image', 'custom-dashboard-controller'); ?></label>
                            </th>
                            <td>
                                <div class="cdc-image-upload">
                                    <input type="hidden"
                                           name="cdc_login_settings[bg_image]"
                                           id="cdc_login_bg_image"
                                           value="<?php echo esc_attr($settings['bg_image']); ?>">

                                    <div class="cdc-image-preview" id="cdc_login_bg_preview">
                                        <?php if (!empty($settings['bg_image'])): ?>
                                            <img src="<?php echo esc_url($settings['bg_image']); ?>" alt="Background">
                                        <?php else: ?>
                                            <span class="cdc-no-image"><?php _e('No image selected', 'custom-dashboard-controller'); ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <button type="button" class="button button-secondary" id="cdc_upload_login_bg">
                                        <span class="dashicons dashicons-upload"></span>
                                        <?php _e('Upload Image', 'custom-dashboard-controller'); ?>
                                    </button>

                                    <button type="button"
                                            class="button button-secondary"
                                            id="cdc_remove_login_bg"
                                            <?php echo empty($settings['bg_image']) ? 'style="display:none;"' : ''; ?>>
                                        <span class="dashicons dashicons-trash"></span>
                                        <?php _e('Remove', 'custom-dashboard-controller'); ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="login_bg_size"><?php _e('Background Size', 'custom-dashboard-controller'); ?></label>
                            </th>
                            <td>
                                <select id="login_bg_size" name="cdc_login_settings[bg_size]">
                                    <option value="cover" <?php selected($settings['bg_size'], 'cover'); ?>><?php _e('Cover', 'custom-dashboard-controller'); ?></option>
                                    <option value="contain" <?php selected($settings['bg_size'], 'contain'); ?>><?php _e('Contain', 'custom-dashboard-controller'); ?></option>
                                    <option value="auto" <?php selected($settings['bg_size'], 'auto'); ?>><?php _e('Auto', 'custom-dashboard-controller'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="login_bg_position"><?php _e('Background Position', 'custom-dashboard-controller'); ?></label>
                            </th>
                            <td>
                                <select id="login_bg_position" name="cdc_login_settings[bg_position]">
                                    <option value="center center" <?php selected($settings['bg_position'], 'center center'); ?>><?php _e('Center', 'custom-dashboard-controller'); ?></option>
                                    <option value="top center" <?php selected($settings['bg_position'], 'top center'); ?>><?php _e('Top Center', 'custom-dashboard-controller'); ?></option>
                                    <option value="bottom center" <?php selected($settings['bg_position'], 'bottom center'); ?>><?php _e('Bottom Center', 'custom-dashboard-controller'); ?></option>
                                    <option value="left center" <?php selected($settings['bg_position'], 'left center'); ?>><?php _e('Left Center', 'custom-dashboard-controller'); ?></option>
                                    <option value="right center" <?php selected($settings['bg_position'], 'right center'); ?>><?php _e('Right Center', 'custom-dashboard-controller'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="login_bg_repeat"><?php _e('Background Repeat', 'custom-dashboard-controller'); ?></label>
                            </th>
                            <td>
                                <select id="login_bg_repeat" name="cdc_login_settings[bg_repeat]">
                                    <option value="no-repeat" <?php selected($settings['bg_repeat'], 'no-repeat'); ?>><?php _e('No Repeat', 'custom-dashboard-controller'); ?></option>
                                    <option value="repeat" <?php selected($settings['bg_repeat'], 'repeat'); ?>><?php _e('Repeat', 'custom-dashboard-controller'); ?></option>
                                    <option value="repeat-x" <?php selected($settings['bg_repeat'], 'repeat-x'); ?>><?php _e('Repeat Horizontally', 'custom-dashboard-controller'); ?></option>
                                    <option value="repeat-y" <?php selected($settings['bg_repeat'], 'repeat-y'); ?>><?php _e('Repeat Vertically', 'custom-dashboard-controller'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Gradient Options -->
                <div class="cdc-bg-options cdc-bg-gradient-options" <?php echo $settings['bg_type'] !== 'gradient' ? 'style="display:none;"' : ''; ?>>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="login_gradient_start"><?php _e('Gradient Start Color', 'custom-dashboard-controller'); ?></label>
                            </th>
                            <td>
                                <input type="color"
                                       id="login_gradient_start"
                                       name="cdc_login_settings[bg_gradient_start]"
                                       value="<?php echo esc_attr($settings['bg_gradient_start']); ?>"
                                       class="cdc-color-picker">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="login_gradient_end"><?php _e('Gradient End Color', 'custom-dashboard-controller'); ?></label>
                            </th>
                            <td>
                                <input type="color"
                                       id="login_gradient_end"
                                       name="cdc_login_settings[bg_gradient_end]"
                                       value="<?php echo esc_attr($settings['bg_gradient_end']); ?>"
                                       class="cdc-color-picker">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="login_gradient_direction"><?php _e('Gradient Direction', 'custom-dashboard-controller'); ?></label>
                            </th>
                            <td>
                                <select id="login_gradient_direction" name="cdc_login_settings[bg_gradient_direction]">
                                    <option value="to right" <?php selected($settings['bg_gradient_direction'], 'to right'); ?>><?php _e('Left to Right', 'custom-dashboard-controller'); ?></option>
                                    <option value="to left" <?php selected($settings['bg_gradient_direction'], 'to left'); ?>><?php _e('Right to Left', 'custom-dashboard-controller'); ?></option>
                                    <option value="to bottom" <?php selected($settings['bg_gradient_direction'], 'to bottom'); ?>><?php _e('Top to Bottom', 'custom-dashboard-controller'); ?></option>
                                    <option value="to top" <?php selected($settings['bg_gradient_direction'], 'to top'); ?>><?php _e('Bottom to Top', 'custom-dashboard-controller'); ?></option>
                                    <option value="135deg" <?php selected($settings['bg_gradient_direction'], '135deg'); ?>><?php _e('Diagonal (135°)', 'custom-dashboard-controller'); ?></option>
                                    <option value="45deg" <?php selected($settings['bg_gradient_direction'], '45deg'); ?>><?php _e('Diagonal (45°)', 'custom-dashboard-controller'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <div class="cdc-gradient-preview" id="cdc-gradient-preview" style="background: linear-gradient(<?php echo esc_attr($settings['bg_gradient_direction']); ?>, <?php echo esc_attr($settings['bg_gradient_start']); ?>, <?php echo esc_attr($settings['bg_gradient_end']); ?>);"></div>
                </div>
            </div>

            <!-- Two Column Settings (shown only for two-column layouts) -->
            <div class="cdc-settings-section cdc-two-column-settings" <?php echo $settings['layout_style'] === 'center' ? 'style="display:none;"' : ''; ?>>
                <h2><?php _e('Two Column Settings', 'custom-dashboard-controller'); ?></h2>
                <p class="description"><?php _e('Configure the image/content column for two-column layouts.', 'custom-dashboard-controller'); ?></p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="login_column_bg_color"><?php _e('Form Column Background', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="color"
                                   id="login_column_bg_color"
                                   name="cdc_login_settings[column_bg_color]"
                                   value="<?php echo esc_attr($settings['column_bg_color']); ?>"
                                   class="cdc-color-picker">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label><?php _e('Image Column Background', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <div class="cdc-image-upload">
                                <input type="hidden"
                                       name="cdc_login_settings[column_image]"
                                       id="cdc_login_column_image"
                                       value="<?php echo esc_attr($settings['column_image']); ?>">

                                <div class="cdc-image-preview" id="cdc_login_column_preview">
                                    <?php if (!empty($settings['column_image'])): ?>
                                        <img src="<?php echo esc_url($settings['column_image']); ?>" alt="Column Background">
                                    <?php else: ?>
                                        <span class="cdc-no-image"><?php _e('No image selected', 'custom-dashboard-controller'); ?></span>
                                    <?php endif; ?>
                                </div>

                                <button type="button" class="button button-secondary" id="cdc_upload_column_image">
                                    <span class="dashicons dashicons-upload"></span>
                                    <?php _e('Upload Image', 'custom-dashboard-controller'); ?>
                                </button>

                                <button type="button"
                                        class="button button-secondary"
                                        id="cdc_remove_column_image"
                                        <?php echo empty($settings['column_image']) ? 'style="display:none;"' : ''; ?>>
                                    <span class="dashicons dashicons-trash"></span>
                                    <?php _e('Remove', 'custom-dashboard-controller'); ?>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Image Overlay', 'custom-dashboard-controller'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox"
                                       name="cdc_login_settings[column_overlay]"
                                       value="yes"
                                       <?php checked($settings['column_overlay'], 'yes'); ?>>
                                <?php _e('Add dark overlay on image', 'custom-dashboard-controller'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Form Styling Section -->
            <div class="cdc-settings-section">
                <h2><?php _e('Form Styling', 'custom-dashboard-controller'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="login_form_bg_color"><?php _e('Form Background Color', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="color"
                                   id="login_form_bg_color"
                                   name="cdc_login_settings[form_bg_color]"
                                   value="<?php echo esc_attr($settings['form_bg_color']); ?>"
                                   class="cdc-color-picker">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="login_form_text_color"><?php _e('Form Text Color', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="color"
                                   id="login_form_text_color"
                                   name="cdc_login_settings[form_text_color]"
                                   value="<?php echo esc_attr($settings['form_text_color']); ?>"
                                   class="cdc-color-picker">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="login_form_border_radius"><?php _e('Form Border Radius (px)', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="number"
                                   id="login_form_border_radius"
                                   name="cdc_login_settings[form_border_radius]"
                                   value="<?php echo esc_attr($settings['form_border_radius']); ?>"
                                   class="small-text"
                                   min="0"
                                   max="50">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Form Shadow', 'custom-dashboard-controller'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox"
                                       name="cdc_login_settings[form_shadow]"
                                       value="yes"
                                       <?php checked($settings['form_shadow'], 'yes'); ?>>
                                <?php _e('Add shadow to login form', 'custom-dashboard-controller'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Button Styling Section -->
            <div class="cdc-settings-section">
                <h2><?php _e('Button Styling', 'custom-dashboard-controller'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="login_button_bg_color"><?php _e('Button Background Color', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="color"
                                   id="login_button_bg_color"
                                   name="cdc_login_settings[button_bg_color]"
                                   value="<?php echo esc_attr($settings['button_bg_color']); ?>"
                                   class="cdc-color-picker">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="login_button_text_color"><?php _e('Button Text Color', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="color"
                                   id="login_button_text_color"
                                   name="cdc_login_settings[button_text_color]"
                                   value="<?php echo esc_attr($settings['button_text_color']); ?>"
                                   class="cdc-color-picker">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="login_button_hover_bg"><?php _e('Button Hover Background', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="color"
                                   id="login_button_hover_bg"
                                   name="cdc_login_settings[button_hover_bg]"
                                   value="<?php echo esc_attr($settings['button_hover_bg']); ?>"
                                   class="cdc-color-picker">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="login_button_hover_text"><?php _e('Button Hover Text Color', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="color"
                                   id="login_button_hover_text"
                                   name="cdc_login_settings[button_hover_text]"
                                   value="<?php echo esc_attr($settings['button_hover_text']); ?>"
                                   class="cdc-color-picker">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="login_button_border_radius"><?php _e('Button Border Radius (px)', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="number"
                                   id="login_button_border_radius"
                                   name="cdc_login_settings[button_border_radius]"
                                   value="<?php echo esc_attr($settings['button_border_radius']); ?>"
                                   class="small-text"
                                   min="0"
                                   max="50">
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Link Styling Section -->
            <div class="cdc-settings-section">
                <h2><?php _e('Link Styling', 'custom-dashboard-controller'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="login_link_color"><?php _e('Link Color', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="color"
                                   id="login_link_color"
                                   name="cdc_login_settings[link_color]"
                                   value="<?php echo esc_attr($settings['link_color']); ?>"
                                   class="cdc-color-picker">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="login_link_hover_color"><?php _e('Link Hover Color', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <input type="color"
                                   id="login_link_hover_color"
                                   name="cdc_login_settings[link_hover_color]"
                                   value="<?php echo esc_attr($settings['link_hover_color']); ?>"
                                   class="cdc-color-picker">
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Custom CSS Section -->
            <div class="cdc-settings-section">
                <h2><?php _e('Custom CSS', 'custom-dashboard-controller'); ?></h2>
                <p class="description"><?php _e('Add your own CSS to further customize the login page.', 'custom-dashboard-controller'); ?></p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="login_custom_css"><?php _e('Custom CSS', 'custom-dashboard-controller'); ?></label>
                        </th>
                        <td>
                            <textarea id="login_custom_css"
                                      name="cdc_login_settings[custom_css]"
                                      class="large-text code"
                                      rows="8"
                                      placeholder="/* Your custom CSS here */"><?php echo esc_textarea($settings['custom_css']); ?></textarea>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Preview Button -->
            <div class="cdc-settings-section">
                <a href="<?php echo esc_url(wp_login_url()); ?>" target="_blank" class="button button-secondary button-hero">
                    <span class="dashicons dashicons-visibility"></span>
                    <?php _e('Preview Login Page', 'custom-dashboard-controller'); ?>
                </a>
            </div>

            <?php submit_button(__('Save Login Page Settings', 'custom-dashboard-controller')); ?>
        </form>
        <?php
    }

    /**
     * Get preset color schemes (NEW v1.6.0)
     *
     * @return array
     */
    public static function get_color_schemes() {
        return array(
            'default' => array(
                'name' => __('Default (Dark)', 'custom-dashboard-controller'),
                'preview' => '#1d2327',
                'colors' => array(
                    'menu_color' => '#1d2327',
                    'text_color' => '#f0f0f1',
                    'hover_bg_color' => '#3c434a',
                    'hover_text_color' => '#72aee6',
                    'active_bg_color' => '#2271b1',
                    'active_text_color' => '#ffffff',
                    'submenu_bg_color' => '#2c3338',
                    'submenu_text_color' => '#f0f0f1',
                    'submenu_hover_bg_color' => '#1d2327',
                    'submenu_hover_text_color' => '#72aee6',
                    'submenu_active_bg_color' => '#2271b1',
                    'submenu_active_text_color' => '#ffffff',
                )
            ),
            'light' => array(
                'name' => __('Light', 'custom-dashboard-controller'),
                'preview' => '#f0f0f1',
                'colors' => array(
                    'menu_color' => '#f0f0f1',
                    'text_color' => '#1d2327',
                    'hover_bg_color' => '#e0e0e0',
                    'hover_text_color' => '#0073aa',
                    'active_bg_color' => '#0073aa',
                    'active_text_color' => '#ffffff',
                    'submenu_bg_color' => '#e5e5e5',
                    'submenu_text_color' => '#1d2327',
                    'submenu_hover_bg_color' => '#d5d5d5',
                    'submenu_hover_text_color' => '#0073aa',
                    'submenu_active_bg_color' => '#0073aa',
                    'submenu_active_text_color' => '#ffffff',
                )
            ),
            'blue' => array(
                'name' => __('Blue', 'custom-dashboard-controller'),
                'preview' => '#1e3a5f',
                'colors' => array(
                    'menu_color' => '#1e3a5f',
                    'text_color' => '#ffffff',
                    'hover_bg_color' => '#2c5282',
                    'hover_text_color' => '#90cdf4',
                    'active_bg_color' => '#3182ce',
                    'active_text_color' => '#ffffff',
                    'submenu_bg_color' => '#2a4365',
                    'submenu_text_color' => '#e2e8f0',
                    'submenu_hover_bg_color' => '#1e3a5f',
                    'submenu_hover_text_color' => '#90cdf4',
                    'submenu_active_bg_color' => '#3182ce',
                    'submenu_active_text_color' => '#ffffff',
                )
            ),
            'green' => array(
                'name' => __('Green', 'custom-dashboard-controller'),
                'preview' => '#1a4731',
                'colors' => array(
                    'menu_color' => '#1a4731',
                    'text_color' => '#ffffff',
                    'hover_bg_color' => '#276749',
                    'hover_text_color' => '#9ae6b4',
                    'active_bg_color' => '#38a169',
                    'active_text_color' => '#ffffff',
                    'submenu_bg_color' => '#22543d',
                    'submenu_text_color' => '#e2e8f0',
                    'submenu_hover_bg_color' => '#1a4731',
                    'submenu_hover_text_color' => '#9ae6b4',
                    'submenu_active_bg_color' => '#38a169',
                    'submenu_active_text_color' => '#ffffff',
                )
            ),
            'purple' => array(
                'name' => __('Purple', 'custom-dashboard-controller'),
                'preview' => '#44337a',
                'colors' => array(
                    'menu_color' => '#44337a',
                    'text_color' => '#ffffff',
                    'hover_bg_color' => '#553c9a',
                    'hover_text_color' => '#d6bcfa',
                    'active_bg_color' => '#805ad5',
                    'active_text_color' => '#ffffff',
                    'submenu_bg_color' => '#4c3d7a',
                    'submenu_text_color' => '#e9d8fd',
                    'submenu_hover_bg_color' => '#44337a',
                    'submenu_hover_text_color' => '#d6bcfa',
                    'submenu_active_bg_color' => '#805ad5',
                    'submenu_active_text_color' => '#ffffff',
                )
            ),
            'red' => array(
                'name' => __('Red', 'custom-dashboard-controller'),
                'preview' => '#742a2a',
                'colors' => array(
                    'menu_color' => '#742a2a',
                    'text_color' => '#ffffff',
                    'hover_bg_color' => '#9b2c2c',
                    'hover_text_color' => '#feb2b2',
                    'active_bg_color' => '#e53e3e',
                    'active_text_color' => '#ffffff',
                    'submenu_bg_color' => '#822727',
                    'submenu_text_color' => '#fed7d7',
                    'submenu_hover_bg_color' => '#742a2a',
                    'submenu_hover_text_color' => '#feb2b2',
                    'submenu_active_bg_color' => '#e53e3e',
                    'submenu_active_text_color' => '#ffffff',
                )
            ),
            'orange' => array(
                'name' => __('Orange', 'custom-dashboard-controller'),
                'preview' => '#7b341e',
                'colors' => array(
                    'menu_color' => '#7b341e',
                    'text_color' => '#ffffff',
                    'hover_bg_color' => '#9c4221',
                    'hover_text_color' => '#fbd38d',
                    'active_bg_color' => '#dd6b20',
                    'active_text_color' => '#ffffff',
                    'submenu_bg_color' => '#8b4513',
                    'submenu_text_color' => '#feebc8',
                    'submenu_hover_bg_color' => '#7b341e',
                    'submenu_hover_text_color' => '#fbd38d',
                    'submenu_active_bg_color' => '#dd6b20',
                    'submenu_active_text_color' => '#ffffff',
                )
            ),
            'midnight' => array(
                'name' => __('Midnight', 'custom-dashboard-controller'),
                'preview' => '#0d1117',
                'colors' => array(
                    'menu_color' => '#0d1117',
                    'text_color' => '#c9d1d9',
                    'hover_bg_color' => '#161b22',
                    'hover_text_color' => '#58a6ff',
                    'active_bg_color' => '#238636',
                    'active_text_color' => '#ffffff',
                    'submenu_bg_color' => '#161b22',
                    'submenu_text_color' => '#c9d1d9',
                    'submenu_hover_bg_color' => '#0d1117',
                    'submenu_hover_text_color' => '#58a6ff',
                    'submenu_active_bg_color' => '#238636',
                    'submenu_active_text_color' => '#ffffff',
                )
            ),
        );
    }

    /**
     * Render Tools tab (NEW v1.6.0)
     */
    private function render_tools_tab() {
        ?>
        <!-- Import/Export Section -->
        <div class="cdc-settings-section">
            <h2><?php _e('Import / Export Settings', 'custom-dashboard-controller'); ?></h2>

            <div class="cdc-info-box cdc-info-green">
                <span class="dashicons dashicons-database"></span>
                <div>
                    <strong><?php _e('Backup & Restore:', 'custom-dashboard-controller'); ?></strong>
                    <?php _e('Export your settings as a JSON file to backup or transfer to another site. Import previously exported settings.', 'custom-dashboard-controller'); ?>
                </div>
            </div>

            <div class="cdc-import-export-grid">
                <!-- Export -->
                <div class="cdc-tool-card">
                    <h3><span class="dashicons dashicons-download"></span> <?php _e('Export Settings', 'custom-dashboard-controller'); ?></h3>
                    <p><?php _e('Download all plugin settings as a JSON file.', 'custom-dashboard-controller'); ?></p>
                    <button type="button" id="cdc-export-btn" class="button button-primary">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export Settings', 'custom-dashboard-controller'); ?>
                    </button>
                </div>

                <!-- Import -->
                <div class="cdc-tool-card">
                    <h3><span class="dashicons dashicons-upload"></span> <?php _e('Import Settings', 'custom-dashboard-controller'); ?></h3>
                    <p><?php _e('Upload a previously exported JSON file to restore settings.', 'custom-dashboard-controller'); ?></p>
                    <input type="file" id="cdc-import-file" accept=".json" style="display: none;">
                    <button type="button" id="cdc-import-btn" class="button button-secondary">
                        <span class="dashicons dashicons-upload"></span>
                        <?php _e('Choose File & Import', 'custom-dashboard-controller'); ?>
                    </button>
                    <span id="cdc-import-status" class="cdc-status-message"></span>
                </div>
            </div>
        </div>

        <!-- Reset to Default Section -->
        <div class="cdc-settings-section">
            <h2><?php _e('Reset to Default', 'custom-dashboard-controller'); ?></h2>

            <div class="cdc-info-box cdc-info-orange">
                <span class="dashicons dashicons-warning"></span>
                <div>
                    <strong><?php _e('Warning:', 'custom-dashboard-controller'); ?></strong>
                    <?php _e('This will reset ALL plugin settings to their default values. This action cannot be undone. Consider exporting your settings first.', 'custom-dashboard-controller'); ?>
                </div>
            </div>

            <div class="cdc-tool-card cdc-reset-card">
                <h3><span class="dashicons dashicons-image-rotate"></span> <?php _e('Reset All Settings', 'custom-dashboard-controller'); ?></h3>
                <p><?php _e('Restore all settings to their original default values.', 'custom-dashboard-controller'); ?></p>
                <button type="button" id="cdc-reset-all-btn" class="button button-secondary cdc-danger-btn">
                    <span class="dashicons dashicons-trash"></span>
                    <?php _e('Reset to Defaults', 'custom-dashboard-controller'); ?>
                </button>
                <span id="cdc-reset-status" class="cdc-status-message"></span>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: Apply color scheme (NEW v1.6.0)
     */
    public function ajax_apply_color_scheme() {
        check_ajax_referer('cdc_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'custom-dashboard-controller')));
        }

        $scheme_id = isset($_POST['scheme']) ? sanitize_key($_POST['scheme']) : '';
        $schemes = self::get_color_schemes();

        if (!isset($schemes[$scheme_id])) {
            wp_send_json_error(array('message' => __('Invalid color scheme', 'custom-dashboard-controller')));
        }

        $settings = get_option('cdc_settings', array());
        $settings = array_merge($settings, $schemes[$scheme_id]['colors']);
        update_option('cdc_settings', $settings);

        wp_send_json_success(array(
            'message' => sprintf(__('Applied "%s" color scheme! Refreshing...', 'custom-dashboard-controller'), $schemes[$scheme_id]['name']),
            'colors' => $schemes[$scheme_id]['colors']
        ));
    }

    /**
     * AJAX: Export all settings (NEW v1.6.0)
     */
    public function ajax_export_settings() {
        check_ajax_referer('cdc_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'custom-dashboard-controller')));
        }

        $export_data = array(
            'plugin_version' => CDC_VERSION,
            'export_date' => current_time('mysql'),
            'site_url' => get_site_url(),
            'settings' => array(
                'cdc_settings' => get_option('cdc_settings', array()),
                'cdc_menu_visibility' => get_option('cdc_menu_visibility', array()),
                'cdc_submenu_visibility' => get_option('cdc_submenu_visibility', array()),
                'cdc_menu_order' => get_option('cdc_menu_order', array()),
                'cdc_submenu_order' => get_option('cdc_submenu_order', array()),
                'cdc_adminbar_visibility' => get_option('cdc_adminbar_visibility', array()),
                'cdc_adminbar_frontend' => get_option('cdc_adminbar_frontend', array()),
                'cdc_adminbar_custom_items' => get_option('cdc_adminbar_custom_items', array()),
                'cdc_dashboard_widgets' => get_option('cdc_dashboard_widgets', array()),
                'cdc_login_settings' => get_option('cdc_login_settings', array()),
            )
        );

        wp_send_json_success(array(
            'data' => $export_data,
            'filename' => 'cdc-settings-' . gmdate('Y-m-d-His') . '.json'
        ));
    }

    /**
     * AJAX: Import settings (NEW v1.6.0)
     */
    public function ajax_import_settings() {
        check_ajax_referer('cdc_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'custom-dashboard-controller')));
        }

        $json_data = isset($_POST['import_data']) ? wp_unslash($_POST['import_data']) : '';

        if (empty($json_data)) {
            wp_send_json_error(array('message' => __('No import data provided', 'custom-dashboard-controller')));
        }

        $import_data = json_decode($json_data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(array('message' => __('Invalid JSON format', 'custom-dashboard-controller')));
        }

        if (!isset($import_data['settings']) || !is_array($import_data['settings'])) {
            wp_send_json_error(array('message' => __('Invalid settings format', 'custom-dashboard-controller')));
        }

        $valid_options = array(
            'cdc_settings',
            'cdc_menu_visibility',
            'cdc_submenu_visibility',
            'cdc_menu_order',
            'cdc_submenu_order',
            'cdc_adminbar_visibility',
            'cdc_adminbar_frontend',
            'cdc_adminbar_custom_items',
            'cdc_dashboard_widgets',
            'cdc_login_settings',
        );

        $imported = 0;
        foreach ($import_data['settings'] as $option_name => $option_value) {
            if (in_array($option_name, $valid_options, true)) {
                // Re-sanitize imported values before persisting. Import data comes
                // from an uploaded file, so it must not be trusted even though only
                // administrators can reach this handler (prevents stored XSS via
                // fields such as login custom_css or custom item titles/URLs).
                update_option($option_name, $this->sanitize_imported_option($option_name, $option_value));
                $imported++;
            }
        }

        wp_send_json_success(array(
            'message' => sprintf(__('Successfully imported %d settings! Refreshing...', 'custom-dashboard-controller'), $imported)
        ));
    }

    /**
     * AJAX: Reset all settings to default (NEW v1.6.0)
     */
    public function ajax_reset_all_settings() {
        check_ajax_referer('cdc_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'custom-dashboard-controller')));
        }

        $defaults = array(
            'cdc_settings' => array(
                'menu_color' => '#1d2327',
                'text_color' => '#f0f0f1',
                'custom_logo' => '',
                'logo_text' => '',
                'hover_bg_color' => '#3c434a',
                'hover_text_color' => '#72aee6',
                'active_bg_color' => '#2271b1',
                'active_text_color' => '#ffffff',
                'submenu_bg_color' => '#2c3338',
                'submenu_text_color' => '#f0f0f1',
                'submenu_hover_bg_color' => '#1d2327',
                'submenu_hover_text_color' => '#72aee6',
                'submenu_active_bg_color' => '#2271b1',
                'submenu_active_text_color' => '#ffffff',
            ),
            'cdc_menu_visibility' => array(),
            'cdc_submenu_visibility' => array(),
            'cdc_menu_order' => array(),
            'cdc_submenu_order' => array(),
            'cdc_adminbar_visibility' => array(),
            'cdc_adminbar_frontend' => array(),
            'cdc_adminbar_custom_items' => array(),
            'cdc_dashboard_widgets' => array(),
            'cdc_login_settings' => array(),
        );

        foreach ($defaults as $option_name => $default_value) {
            update_option($option_name, $default_value);
        }

        wp_send_json_success(array(
            'message' => __('All settings have been reset to defaults! Refreshing...', 'custom-dashboard-controller')
        ));
    }
}
