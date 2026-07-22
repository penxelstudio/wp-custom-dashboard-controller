<?php
/**
 * Dashboard Widgets class - Handles custom dashboard widgets
 * 
 * NEW in v1.4.2 - Shortcode Widget
 * 
 * This class manages:
 * - Creating custom dashboard widgets
 * - Shortcode widgets with auto-refresh
 * - Role-based visibility
 * - Auto-detect available shortcodes from plugins
 * - AJAX handlers for widget management
 * 
 * @package CustomDashboardController
 * @since 1.4.2
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDC_Dashboard_Widgets {
    
    /**
     * Refresh interval in seconds (1 hour = 3600 seconds)
     */
    const REFRESH_INTERVAL = 3600;
    
    /**
     * Constructor - Set up hooks for dashboard widgets
     */
    public function __construct() {
        // Register custom dashboard widgets
        add_action('wp_dashboard_setup', array($this, 'register_dashboard_widgets'));
        
        // AJAX handlers
        add_action('wp_ajax_cdc_save_dashboard_widget', array($this, 'ajax_save_widget'));
        add_action('wp_ajax_cdc_delete_dashboard_widget', array($this, 'ajax_delete_widget'));
        add_action('wp_ajax_cdc_get_widget_data', array($this, 'ajax_get_widget_data'));
        add_action('wp_ajax_cdc_refresh_widget_content', array($this, 'ajax_refresh_widget'));
        
        // Add inline script for auto-refresh on dashboard
        add_action('admin_footer-index.php', array($this, 'add_refresh_script'));
    }
    
    /**
     * Register all custom dashboard widgets
     */
    public function register_dashboard_widgets() {
        $widgets = get_option('cdc_dashboard_widgets', array());
        
        if (empty($widgets)) {
            return;
        }
        
        // Get current user's roles
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        
        foreach ($widgets as $widget) {
            // Check role visibility
            $widget_roles = isset($widget['roles']) ? $widget['roles'] : array();
            
            // If roles are specified, check if user has permission
            if (!empty($widget_roles) && !array_intersect($user_roles, $widget_roles)) {
                continue;
            }
            
            $widget_id = 'cdc_widget_' . sanitize_key($widget['id']);
            $widget_title = isset($widget['title']) ? $widget['title'] : __('Custom Widget', 'custom-dashboard-controller');
            
            wp_add_dashboard_widget(
                $widget_id,
                esc_html($widget_title),
                array($this, 'render_widget_content'),
                null,
                $widget
            );
        }
    }
    
    /**
     * Render widget content based on type
     * 
     * @param mixed $post Not used
     * @param array $callback_args Widget configuration
     */
    public function render_widget_content($post, $callback_args) {
        $widget = $callback_args['args'];
        $widget_id = isset($widget['id']) ? $widget['id'] : '';
        $widget_type = isset($widget['type']) ? $widget['type'] : 'shortcode';
        
        // Container for AJAX refresh
        echo '<div class="cdc-widget-content" data-widget-id="' . esc_attr($widget_id) . '" data-widget-type="' . esc_attr($widget_type) . '">';
        
        switch ($widget_type) {
            case 'shortcode':
                $this->render_shortcode_widget($widget);
                break;
            default:
                echo '<p>' . __('Unknown widget type.', 'custom-dashboard-controller') . '</p>';
        }
        
        echo '</div>';
        
        // Last refresh time
        $last_refresh = get_transient('cdc_widget_refresh_' . $widget_id);
        if ($last_refresh) {
            echo '<p class="cdc-widget-refresh-time">';
            echo '<small>' . sprintf(__('Last updated: %s', 'custom-dashboard-controller'), 
                human_time_diff($last_refresh, time()) . ' ' . __('ago', 'custom-dashboard-controller')) . '</small>';
            echo '</p>';
        }
    }
    
    /**
     * Render shortcode widget content
     * 
     * @param array $widget Widget configuration
     */
    private function render_shortcode_widget($widget) {
        $shortcode = isset($widget['shortcode']) ? $widget['shortcode'] : '';
        
        if (empty($shortcode)) {
            echo '<p class="cdc-widget-empty">' . __('No shortcode configured.', 'custom-dashboard-controller') . '</p>';
            return;
        }
        
        // Make sure shortcode is not escaped
        $shortcode = wp_unslash($shortcode);
        $shortcode = html_entity_decode($shortcode, ENT_QUOTES, 'UTF-8');
        
        // Execute the shortcode
        $output = do_shortcode($shortcode);
        
        // Check if shortcode returned content
        // A shortcode returns itself if not registered
        if (empty(trim(strip_tags($output))) || trim($output) === trim($shortcode)) {
            echo '<p class="cdc-widget-error">' . __('Shortcode returned no content or is invalid.', 'custom-dashboard-controller') . '</p>';
            echo '<code>' . esc_html($shortcode) . '</code>';
        } else {
            echo '<div class="cdc-shortcode-output">' . $output . '</div>';
        }
        
        // Update refresh transient
        set_transient('cdc_widget_refresh_' . $widget['id'], time(), self::REFRESH_INTERVAL);
    }
    
    /**
     * Add auto-refresh JavaScript on dashboard page
     */
    public function add_refresh_script() {
        $widgets = get_option('cdc_dashboard_widgets', array());
        
        if (empty($widgets)) {
            return;
        }
        
        ?>
        <script type="text/javascript">
        (function($) {
            'use strict';
            
            // Auto-refresh interval (1 hour in milliseconds)
            var refreshInterval = <?php echo self::REFRESH_INTERVAL * 1000; ?>;
            
            // Refresh widget content via AJAX
            function refreshWidget($widget) {
                var widgetId = $widget.data('widget-id');
                var widgetType = $widget.data('widget-type');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cdc_refresh_widget_content',
                        nonce: '<?php echo wp_create_nonce('cdc_ajax_nonce'); ?>',
                        widget_id: widgetId
                    },
                    success: function(response) {
                        if (response.success && response.data.content) {
                            $widget.html(response.data.content);
                        }
                    }
                });
            }
            
            // Set up auto-refresh for each widget
            $(document).ready(function() {
                $('.cdc-widget-content').each(function() {
                    var $widget = $(this);
                    
                    // Refresh every hour
                    setInterval(function() {
                        refreshWidget($widget);
                    }, refreshInterval);
                });
            });
            
        })(jQuery);
        </script>
        <?php
    }
    
    /**
     * AJAX handler: Save dashboard widget
     */
    public function ajax_save_widget() {
        // Verify nonce
        if (!check_ajax_referer('cdc_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed', 'custom-dashboard-controller')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'custom-dashboard-controller')));
        }
        
        // Get and validate data - use wp_unslash to remove escaping
        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        $type = isset($_POST['type']) ? sanitize_key($_POST['type']) : 'shortcode';
        $shortcode = isset($_POST['shortcode']) ? wp_unslash($_POST['shortcode']) : '';
        $roles = isset($_POST['roles']) ? array_map('sanitize_key', (array)$_POST['roles']) : array();
        $widget_id = isset($_POST['widget_id']) ? sanitize_key($_POST['widget_id']) : '';
        
        // Clean up shortcode - remove any escaped quotes
        $shortcode = stripslashes($shortcode);
        $shortcode = str_replace(array('\"', "\'"), array('"', "'"), $shortcode);
        
        // Validate required fields
        if (empty($title)) {
            wp_send_json_error(array('message' => __('Title is required', 'custom-dashboard-controller')));
        }
        
        if ($type === 'shortcode' && empty($shortcode)) {
            wp_send_json_error(array('message' => __('Shortcode is required', 'custom-dashboard-controller')));
        }
        
        // Get existing widgets
        $widgets = get_option('cdc_dashboard_widgets', array());
        
        // Generate ID if new widget
        if (empty($widget_id)) {
            $widget_id = 'widget-' . time() . '-' . wp_rand(100, 999);
        }
        
        // Prepare widget data
        $widget_data = array(
            'id'        => $widget_id,
            'title'     => $title,
            'type'      => $type,
            'shortcode' => $shortcode,
            'roles'     => $roles,
            'created'   => time()
        );
        
        // Check if updating existing widget
        $found = false;
        foreach ($widgets as $key => $widget) {
            if (isset($widget['id']) && $widget['id'] === $widget_id) {
                $widget_data['created'] = $widget['created']; // Preserve original creation time
                $widgets[$key] = $widget_data;
                $found = true;
                break;
            }
        }
        
        // Add new widget if not found
        if (!$found) {
            $widgets[] = $widget_data;
        }
        
        // Save
        update_option('cdc_dashboard_widgets', $widgets);
        
        wp_send_json_success(array(
            'message' => __('Dashboard widget saved!', 'custom-dashboard-controller'),
            'widget_id' => $widget_id
        ));
    }
    
    /**
     * AJAX handler: Get widget data for editing
     */
    public function ajax_get_widget_data() {
        // Verify nonce
        if (!check_ajax_referer('cdc_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed', 'custom-dashboard-controller')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'custom-dashboard-controller')));
        }
        
        $widget_id = isset($_POST['widget_id']) ? sanitize_key($_POST['widget_id']) : '';
        
        if (empty($widget_id)) {
            wp_send_json_error(array('message' => __('Widget ID required', 'custom-dashboard-controller')));
        }
        
        // Find widget
        $widgets = get_option('cdc_dashboard_widgets', array());
        $target_widget = null;
        
        foreach ($widgets as $widget) {
            if (isset($widget['id']) && $widget['id'] === $widget_id) {
                $target_widget = $widget;
                break;
            }
        }
        
        if (!$target_widget) {
            wp_send_json_error(array('message' => __('Widget not found', 'custom-dashboard-controller')));
        }
        
        wp_send_json_success(array(
            'widget' => $target_widget
        ));
    }
    
    /**
     * AJAX handler: Delete dashboard widget
     */
    public function ajax_delete_widget() {
        // Verify nonce
        if (!check_ajax_referer('cdc_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed', 'custom-dashboard-controller')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'custom-dashboard-controller')));
        }
        
        $widget_id = isset($_POST['widget_id']) ? sanitize_key($_POST['widget_id']) : '';
        
        if (empty($widget_id)) {
            wp_send_json_error(array('message' => __('Widget ID required', 'custom-dashboard-controller')));
        }
        
        // Get existing widgets
        $widgets = get_option('cdc_dashboard_widgets', array());
        
        // Remove widget
        foreach ($widgets as $key => $widget) {
            if (isset($widget['id']) && $widget['id'] === $widget_id) {
                unset($widgets[$key]);
                // Also delete refresh transient
                delete_transient('cdc_widget_refresh_' . $widget_id);
                break;
            }
        }
        
        // Re-index array
        $widgets = array_values($widgets);
        
        // Save
        update_option('cdc_dashboard_widgets', $widgets);
        
        wp_send_json_success(array(
            'message' => __('Dashboard widget deleted!', 'custom-dashboard-controller')
        ));
    }
    
    /**
     * AJAX handler: Refresh widget content
     */
    public function ajax_refresh_widget() {
        // Verify nonce
        if (!check_ajax_referer('cdc_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed', 'custom-dashboard-controller')));
        }

        // Only users who can access the dashboard may refresh widget content.
        // Prevents lower-privilege users from rendering another role's widget output.
        if (!current_user_can('read')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'custom-dashboard-controller')));
        }

        $widget_id = isset($_POST['widget_id']) ? sanitize_key($_POST['widget_id']) : '';
        
        if (empty($widget_id)) {
            wp_send_json_error(array('message' => __('Widget ID required', 'custom-dashboard-controller')));
        }
        
        // Find widget
        $widgets = get_option('cdc_dashboard_widgets', array());
        $target_widget = null;
        
        foreach ($widgets as $widget) {
            if (isset($widget['id']) && $widget['id'] === $widget_id) {
                $target_widget = $widget;
                break;
            }
        }
        
        if (!$target_widget) {
            wp_send_json_error(array('message' => __('Widget not found', 'custom-dashboard-controller')));
        }

        // Enforce the widget's own role visibility so a user cannot fetch the
        // rendered output of a widget that is not shown to their role.
        $widget_roles = isset($target_widget['roles']) ? $target_widget['roles'] : array();
        if (!empty($widget_roles)) {
            $user_roles = wp_get_current_user()->roles;
            if (!array_intersect($user_roles, $widget_roles)) {
                wp_send_json_error(array('message' => __('Unauthorized', 'custom-dashboard-controller')));
            }
        }

        // Generate content
        ob_start();
        
        if ($target_widget['type'] === 'shortcode') {
            $this->render_shortcode_widget($target_widget);
        }
        
        $content = ob_get_clean();
        
        wp_send_json_success(array(
            'content' => $content
        ));
    }
    
    /**
     * Get all dashboard widgets
     * 
     * @return array Array of widget configurations
     */
    public static function get_all_widgets() {
        return get_option('cdc_dashboard_widgets', array());
    }
}
