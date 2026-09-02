<?php
/**
 * Login Page Branding class
 * Handles custom login page styling including logo, colors, background, and layout
 *
 * @package CustomDashboardController
 * @since 1.5.2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CDC_Login_Branding {

    /**
     * Login settings
     *
     * @var array
     */
    private $settings;

    /**
     * Constructor - Set up hooks
     */
    public function __construct() {
        // Merge over the defaults rather than passing them to get_option(): the
        // option row is created empty on activation, and get_option() only falls
        // back to its default when the option does not exist at all. Without the
        // merge every key below is undefined on a fresh install.
        $this->settings = wp_parse_args(
            (array) get_option('cdc_login_settings', array()),
            $this->get_defaults()
        );

        // Login page hooks
        add_action('login_head', array($this, 'output_custom_styles'));
        add_filter('login_headerurl', array($this, 'custom_logo_url'));
        add_filter('login_headertext', array($this, 'custom_logo_title'));
        add_action('login_header', array($this, 'add_layout_wrapper_start'));
        add_action('login_footer', array($this, 'add_layout_wrapper_end'));

        // Add body class for layout
        add_filter('login_body_class', array($this, 'add_body_class'));
    }

    /**
     * Get default settings
     *
     * @return array
     */
    public function get_defaults() {
        return array(
            // Logo settings
            'custom_logo'          => '',
            'logo_width'           => '320',
            'logo_height'          => '80',
            'logo_url'             => '',

            // Layout settings
            'layout_style'         => 'center', // center, two-column-left, two-column-right

            // Background settings
            'bg_type'              => 'color', // color, image, gradient
            'bg_color'             => '#f0f0f1',
            'bg_image'             => '',
            'bg_size'              => 'cover',
            'bg_position'          => 'center center',
            'bg_repeat'            => 'no-repeat',
            'bg_gradient_start'    => '#667eea',
            'bg_gradient_end'      => '#764ba2',
            'bg_gradient_direction'=> '135deg',

            // Form styling
            'form_bg_color'        => '#ffffff',
            'form_text_color'      => '#3c434a',
            'form_border_radius'   => '4',
            'form_shadow'          => 'yes',

            // Button styling
            'button_bg_color'      => '#2271b1',
            'button_text_color'    => '#ffffff',
            'button_hover_bg'      => '#135e96',
            'button_hover_text'    => '#ffffff',
            'button_border_radius' => '3',

            // Link styling
            'link_color'           => '#50575e',
            'link_hover_color'     => '#135e96',

            // Two-column settings
            'column_bg_color'      => '#ffffff',
            'column_image'         => '',
            'column_overlay'       => 'yes',
            'column_overlay_color' => 'rgba(0,0,0,0.3)',

            // Custom CSS
            'custom_css'           => '',
        );
    }

    /**
     * Output custom CSS styles on login page
     */
    public function output_custom_styles() {
        $settings = $this->settings;

        // Start building CSS
        $css = '<style type="text/css" id="cdc-login-styles">';

        // Base styles
        $css .= '
        body.login {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }';

        // Background styles
        $css .= $this->get_background_css($settings);

        // Layout styles
        $css .= $this->get_layout_css($settings);

        // Logo styles
        $css .= $this->get_logo_css($settings);

        // Form styles
        $css .= $this->get_form_css($settings);

        // Button styles
        $css .= $this->get_button_css($settings);

        // Link styles
        $css .= $this->get_link_css($settings);

        // Custom CSS
        if (!empty($settings['custom_css'])) {
            $css .= $settings['custom_css'];
        }

        $css .= '</style>';

        echo $css;
    }

    /**
     * Get background CSS based on type
     *
     * @param array $settings
     * @return string
     */
    private function get_background_css($settings) {
        $css = '';
        $bg_type = isset($settings['bg_type']) ? $settings['bg_type'] : 'color';

        switch ($bg_type) {
            case 'image':
                if (!empty($settings['bg_image'])) {
                    $css .= sprintf('
                    body.login {
                        background-image: url("%s");
                        background-size: %s;
                        background-position: %s;
                        background-repeat: %s;
                        background-attachment: fixed;
                    }',
                        esc_url($settings['bg_image']),
                        esc_attr($settings['bg_size']),
                        esc_attr($settings['bg_position']),
                        esc_attr($settings['bg_repeat'])
                    );
                }
                break;

            case 'gradient':
                $css .= sprintf('
                body.login {
                    background: linear-gradient(%s, %s, %s);
                    background-attachment: fixed;
                }',
                    esc_attr($settings['bg_gradient_direction']),
                    esc_attr($settings['bg_gradient_start']),
                    esc_attr($settings['bg_gradient_end'])
                );
                break;

            default: // color
                $css .= sprintf('
                body.login {
                    background-color: %s;
                }',
                    esc_attr($settings['bg_color'])
                );
                break;
        }

        return $css;
    }

    /**
     * Get layout CSS based on style
     *
     * @param array $settings
     * @return string
     */
    private function get_layout_css($settings) {
        $css = '';
        $layout = isset($settings['layout_style']) ? $settings['layout_style'] : 'center';

        if ($layout === 'center') {
            // Center layout (default WordPress style, enhanced)
            $css .= '
            body.login.cdc-layout-center {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
            }

            body.login.cdc-layout-center #login {
                padding: 40px;
                margin: 0;
            }';
        } else {
            // Two-column layout
            $is_left = ($layout === 'two-column-left');
            $form_side = $is_left ? 'left' : 'right';
            $image_side = $is_left ? 'right' : 'left';

            $css .= '
            body.login.cdc-layout-two-column {
                display: flex;
                min-height: 100vh;
                margin: 0;
                padding: 0;
            }

            .cdc-login-wrapper {
                display: flex;
                width: 100%;
                min-height: 100vh;
            }

            .cdc-login-form-column {
                width: 50%;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 40px;
                box-sizing: border-box;
                background-color: ' . esc_attr($settings['column_bg_color']) . ';
                order: ' . ($is_left ? '1' : '2') . ';
            }

            .cdc-login-image-column {
                width: 50%;
                position: relative;
                order: ' . ($is_left ? '2' : '1') . ';
            }';

            // Image column background
            if (!empty($settings['column_image'])) {
                $css .= '
                .cdc-login-image-column {
                    background-image: url("' . esc_url($settings['column_image']) . '");
                    background-size: cover;
                    background-position: center;
                }';

                // Overlay
                if ($settings['column_overlay'] === 'yes') {
                    $css .= '
                    .cdc-login-image-column::before {
                        content: "";
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background: ' . esc_attr($settings['column_overlay_color']) . ';
                    }';
                }
            }

            $css .= '
            body.login.cdc-layout-two-column #login {
                width: 100%;
                max-width: 400px;
                padding: 0;
                margin: 0;
            }

            /* Responsive for two-column */
            @media screen and (max-width: 782px) {
                .cdc-login-wrapper {
                    flex-direction: column;
                }

                .cdc-login-form-column,
                .cdc-login-image-column {
                    width: 100%;
                    order: unset;
                }

                .cdc-login-image-column {
                    min-height: 200px;
                }

                .cdc-login-form-column {
                    padding: 20px;
                }
            }';
        }

        return $css;
    }

    /**
     * Get logo CSS
     *
     * @param array $settings
     * @return string
     */
    private function get_logo_css($settings) {
        $css = '';

        if (!empty($settings['custom_logo'])) {
            $width = !empty($settings['logo_width']) ? intval($settings['logo_width']) : 320;
            $height = !empty($settings['logo_height']) ? intval($settings['logo_height']) : 80;

            $css .= sprintf('
            #login h1 a, .login h1 a {
                background-image: url("%s");
                background-size: contain;
                background-position: center;
                background-repeat: no-repeat;
                width: %dpx;
                height: %dpx;
                max-width: 100%%;
            }',
                esc_url($settings['custom_logo']),
                $width,
                $height
            );
        } else {
            // Hide default WordPress logo if no custom logo
            $css .= '
            #login h1 a, .login h1 a {
                background-image: url("' . admin_url('images/wordpress-logo.svg') . '");
                background-size: 84px;
                width: 84px;
                height: 84px;
            }';
        }

        return $css;
    }

    /**
     * Get form CSS
     *
     * @param array $settings
     * @return string
     */
    private function get_form_css($settings) {
        $border_radius = isset($settings['form_border_radius']) ? intval($settings['form_border_radius']) : 4;
        $shadow = isset($settings['form_shadow']) && $settings['form_shadow'] === 'yes';

        $css = sprintf('
        .login form {
            background: %s;
            color: %s;
            border-radius: %dpx;
            border: none;
            %s
        }

        .login form .input,
        .login form input[type="text"],
        .login form input[type="password"] {
            background: %s;
            color: %s;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: %dpx;
        }

        .login form .input:focus,
        .login form input[type="text"]:focus,
        .login form input[type="password"]:focus {
            border-color: %s;
            box-shadow: 0 0 0 1px %s;
        }

        .login label {
            color: %s;
        }',
            esc_attr($settings['form_bg_color']),
            esc_attr($settings['form_text_color']),
            $border_radius,
            $shadow ? 'box-shadow: 0 4px 20px rgba(0,0,0,0.1);' : 'box-shadow: none;',
            esc_attr($settings['form_bg_color']),
            esc_attr($settings['form_text_color']),
            $border_radius,
            esc_attr($settings['button_bg_color']),
            esc_attr($settings['button_bg_color']),
            esc_attr($settings['form_text_color'])
        );

        return $css;
    }

    /**
     * Get button CSS
     *
     * @param array $settings
     * @return string
     */
    private function get_button_css($settings) {
        $border_radius = isset($settings['button_border_radius']) ? intval($settings['button_border_radius']) : 3;

        $css = sprintf('
        .login .button-primary,
        .wp-core-ui .button-primary {
            background: %s !important;
            border-color: %s !important;
            color: %s !important;
            border-radius: %dpx !important;
            text-shadow: none !important;
            box-shadow: none !important;
            transition: all 0.2s ease;
        }

        .login .button-primary:hover,
        .login .button-primary:focus,
        .wp-core-ui .button-primary:hover,
        .wp-core-ui .button-primary:focus {
            background: %s !important;
            border-color: %s !important;
            color: %s !important;
        }',
            esc_attr($settings['button_bg_color']),
            esc_attr($settings['button_bg_color']),
            esc_attr($settings['button_text_color']),
            $border_radius,
            esc_attr($settings['button_hover_bg']),
            esc_attr($settings['button_hover_bg']),
            esc_attr($settings['button_hover_text'])
        );

        return $css;
    }

    /**
     * Get link CSS
     *
     * @param array $settings
     * @return string
     */
    private function get_link_css($settings) {
        $css = sprintf('
        .login #nav a,
        .login #backtoblog a,
        .login .privacy-policy-page-link a {
            color: %s !important;
            transition: color 0.2s ease;
        }

        .login #nav a:hover,
        .login #backtoblog a:hover,
        .login .privacy-policy-page-link a:hover {
            color: %s !important;
        }',
            esc_attr($settings['link_color']),
            esc_attr($settings['link_hover_color'])
        );

        return $css;
    }

    /**
     * Custom logo URL
     *
     * @return string
     */
    public function custom_logo_url() {
        $settings = $this->settings;

        if (!empty($settings['logo_url'])) {
            return esc_url($settings['logo_url']);
        }

        return home_url('/');
    }

    /**
     * Custom logo title
     *
     * @return string
     */
    public function custom_logo_title() {
        return get_bloginfo('name');
    }

    /**
     * Add body class for layout
     *
     * @param array $classes
     * @return array
     */
    public function add_body_class($classes) {
        $layout = isset($this->settings['layout_style']) ? $this->settings['layout_style'] : 'center';

        if ($layout === 'center') {
            $classes[] = 'cdc-layout-center';
        } else {
            $classes[] = 'cdc-layout-two-column';
            $classes[] = 'cdc-layout-' . sanitize_html_class($layout);
        }

        return $classes;
    }

    /**
     * Add wrapper start for two-column layout
     */
    public function add_layout_wrapper_start() {
        $layout = isset($this->settings['layout_style']) ? $this->settings['layout_style'] : 'center';

        if ($layout !== 'center') {
            echo '<div class="cdc-login-wrapper">';
            echo '<div class="cdc-login-form-column">';
        }
    }

    /**
     * Add wrapper end for two-column layout
     */
    public function add_layout_wrapper_end() {
        $layout = isset($this->settings['layout_style']) ? $this->settings['layout_style'] : 'center';

        if ($layout !== 'center') {
            echo '</div>'; // Close form column
            echo '<div class="cdc-login-image-column"></div>';
            echo '</div>'; // Close wrapper
        }
    }
}
