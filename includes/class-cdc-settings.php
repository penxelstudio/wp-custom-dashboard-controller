<?php
/**
 * Settings class - Handles admin settings pages with tabbed interface
 * 
 * UPDATED in v1.4.0 - New tabbed interface
 * UPDATED in v1.4.1 - Added Admin Bar tab
 * UPDATED in v1.4.2 - Added Dashboard Widgets tab
 * 
 * Tab Structure:
 * - Tab 1: Basic Settings (Colors, Logo)
 * - Tab 2: Menu Visibility (Parent menus, Submenus)
 * - Tab 3: Menu Order (Parent menus, Submenus)
 * - Tab 4: Admin Bar (Visibility, Frontend, Custom Items)
 * - Tab 5: Dashboard Widgets (Shortcode widgets)
 * 
 * @package CustomDashboardController
 * @since 1.0.0
 * @updated 1.4.2
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
     * Constructor - Set up admin menu and settings
     */
    public function __construct() {
        // Define tabs
        $this->tabs = array(
            'basic'      => __('Basic Settings', 'custom-dashboard-controller'),
            'visibility' => __('Menu Visibility', 'custom-dashboard-controller'),
            'order'      => __('Menu Order', 'custom-dashboard-controller'),
            'adminbar'   => __('Admin Bar', 'custom-dashboard-controller'),
            'widgets'    => __('Dashboard Widgets', 'custom-dashboard-controller')
        );
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
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
     * Get current active tab
     */
    private function get_current_tab() {
        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'basic';
        return array_key_exists($tab, $this->tabs) ? $tab : 'basic';
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
                <?php foreach ($this->tabs as $tab_key => $tab_label): ?>
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
                                    $card_class = $is_hidden ? 'cdc-menu-item cdc-hidden' : 'cdc-menu-item';
                                    ?>
                                    <label class="<?php echo esc_attr($card_class); ?>">
                                        <input type="checkbox" 
                                               name="cdc_menu_visibility[<?php echo esc_attr($role_slug); ?>][]" 
                                               value="<?php echo esc_attr($menu_item['slug']); ?>"
                                               <?php checked($is_hidden); ?>>
                                        <span class="cdc-menu-name"><?php echo esc_html($menu_item['name']); ?></span>
                                        <span class="cdc-menu-status"><?php echo $is_hidden ? '🚫' : '✅'; ?></span>
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
}
