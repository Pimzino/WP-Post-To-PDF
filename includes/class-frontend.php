<?php
/**
 * Frontend functionality
 *
 * @package WP_Post_to_PDF
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class WP_Post_to_PDF_Frontend
 */
class WP_Post_to_PDF_Frontend {

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('init', array($this, 'register_shortcode'));
        add_filter('the_content', array($this, 'maybe_add_button_to_content'));
    }

    /**
     * Enqueue frontend scripts and styles
     *
     * @return void
     */
    public function enqueue_assets() {
        // Only enqueue on single posts
        if (!is_single()) {
            return;
        }

        // Get plugin settings
        $settings = get_option('wp_post_to_pdf_settings', array());
        
        // Enqueue our custom fonts CSS first
        wp_enqueue_style(
            'wp-post-to-pdf-fonts',
            WP_POST_TO_PDF_URL . 'assets/css/fonts.css',
            array(),
            WP_POST_TO_PDF_VERSION
        );

        // Enqueue frontend styles after fonts
        wp_enqueue_style(
            'wp-post-to-pdf-frontend',
            WP_POST_TO_PDF_URL . 'assets/css/frontend-styles.css',
            array('wp-post-to-pdf-fonts'), // Make sure frontend styles depend on fonts
            WP_POST_TO_PDF_VERSION
        );

        // Enqueue Font Awesome if icon is selected
        if (!empty($settings['button_icon']) && $settings['button_icon'] !== 'none') {
            wp_enqueue_style(
                'font-awesome',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
                array(),
                '6.4.0'
            );
        }

        // Enqueue frontend scripts
        wp_enqueue_script(
            'wp-post-to-pdf-frontend',
            WP_POST_TO_PDF_URL . 'assets/js/frontend-scripts.js',
            array('jquery'),
            WP_POST_TO_PDF_VERSION,
            true
        );

        // Localize script with settings and translations
        wp_localize_script(
            'wp-post-to-pdf-frontend',
            'wpPostToPDF',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_post_to_pdf_nonce'),
                'settings' => $settings,
                'i18n' => array(
                    'generating' => __('Generating PDF...', 'wp-post-to-pdf'),
                    'error' => __('Error generating PDF. Please try again.', 'wp-post-to-pdf'),
                ),
            )
        );
    }

    /**
     * Register the shortcode
     *
     * @return void
     */
    public function register_shortcode() {
        add_shortcode('post_to_pdf', array($this, 'render_button'));
    }

    /**
     * Render the PDF download button
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_button($atts = array()) {
        // Get plugin settings
        $settings = get_option('wp_post_to_pdf_settings', array());
        
        // Get current post ID
        $post_id = get_the_ID();
        
        // Get placement for wrapper class
        $placement = !empty($settings['button_placement']) ? $settings['button_placement'] : 'bottom-left';
        
        // Configure the button renderer
        $config = array(
            'context' => 'frontend',
            'post_id' => $post_id,
            'placement' => $placement,
            'wrapper_class' => $placement,
            'show_screen_reader_text' => true,
            'show_placement' => true
        );
        
        // Use the shared button renderer
        return WP_Post_to_PDF_Button_Renderer::render($settings, $config);
    }

    /**
     * Maybe add button to content based on placement setting
     *
     * @param string $content The post content.
     * @return string
     */
    public function maybe_add_button_to_content($content) {
        // Only add button on single posts
        if (!is_single()) {
            return $content;
        }

        // Get settings
        $settings = get_option('wp_post_to_pdf_settings', array());
        $placement = !empty($settings['button_placement']) ? $settings['button_placement'] : 'bottom-left';

        // If placement is 'none', return content unchanged
        if ($placement === 'none') {
            return $content;
        }

        // Get button HTML
        $button = $this->render_button();

        // Add button based on placement
        if (strpos($placement, 'top-') === 0) {
            return $button . $content;
        } else if (strpos($placement, 'bottom-') === 0) {
            return $content . $button;
        }

        return $content;
    }
}

// Initialize the frontend
new WP_Post_to_PDF_Frontend();
