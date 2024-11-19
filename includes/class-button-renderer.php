<?php
/**
 * Button Renderer functionality
 *
 * @package WP_Post_to_PDF
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class WP_Post_to_PDF_Button_Renderer
 */
class WP_Post_to_PDF_Button_Renderer {

    /**
     * Render the PDF download button
     *
     * @param array  $settings The button settings.
     * @param array  $config   Configuration options for the renderer.
     * @return string
     */
    public static function render($settings = array(), $config = array()) {
        // Default configuration
        $default_config = array(
            'context'     => 'frontend', // frontend, preview
            'post_id'     => 0,
            'placement'   => 'bottom-left',   // top-left, top-center, top-right, bottom-left, bottom-center, bottom-right, none
            'wrapper_class' => '',       // Additional classes for wrapper
            'button_class'  => '',       // Additional classes for button
            'show_screen_reader_text' => true,
            'show_placement' => true     // Whether to respect placement setting
        );

        // Merge configs
        $config = wp_parse_args($config, $default_config);

        // Get default settings
        $defaults = array(
            'button_text' => __('Download PDF', 'wp-post-to-pdf'),
            'button_font' => 'Arial, sans-serif',
            'button_font_weight' => '500',
            'button_font_size' => 16,
            'button_size' => 'medium',
            'button_icon' => 'fa-file-pdf',
            'button_bg_color' => '#1C1A1C',
            'button_bg_color_hover' => '#683FEA',
            'button_font_color' => '#AAAAAA',
            'button_font_color_hover' => '#FFFFFF',
            'button_hover_effect' => true,
            'button_placement' => 'bottom-left'
        );

        // Merge with defaults
        $settings = wp_parse_args($settings, $defaults);

        // Get placement from settings if not overridden in config
        if (empty($config['placement']) && !empty($settings['button_placement'])) {
            $config['placement'] = $settings['button_placement'];
        }

        // Common button styles
        $button_styles = array(
            'font-family' => "'{$settings['button_font']}'",
            'font-weight' => '700',
            '--button-bg-color' => $settings['button_bg_color'],
            '--button-bg-color-hover' => $settings['button_bg_color_hover'],
            '--button-font-size' => $settings['button_font_size'] . 'px',
            '--button-font-color' => $settings['button_font_color'],
            '--button-font-color-hover' => $settings['button_font_color_hover']
        );

        // Build style strings
        $button_style = self::build_style_string($button_styles);

        // Add button size class
        $button_size_class = 'size-' . $settings['button_size'];

        // Create wrapper classes array
        $wrapper_classes = array('pdf-button-wrapper');
        
        // Add placement class
        if ($config['placement'] !== 'none') {
            $wrapper_classes[] = esc_attr($config['placement']);
        }
        
        // Add any additional wrapper classes
        if (!empty($config['wrapper_class'])) {
            $wrapper_classes[] = esc_attr($config['wrapper_class']);
        }

        // Start output buffering
        ob_start();
        ?>
        <div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>">
            <button type="button" 
                class="pdf-download-button <?php echo esc_attr($config['button_class'] . ' ' . $button_size_class); ?> <?php echo !empty($settings['button_hover_effect']) ? 'hover-effect' : ''; ?>" 
                style="<?php echo esc_attr($button_style); ?>"
                <?php if ($config['context'] === 'frontend' && $config['post_id']): ?>
                    data-post-id="<?php echo esc_attr($config['post_id']); ?>"
                    data-nonce="<?php echo wp_create_nonce('generate_pdf_' . $config['post_id']); ?>"
                <?php endif; ?>
            >
                <?php if (!empty($settings['button_icon']) && $settings['button_icon'] !== 'none'): ?>
                    <i class="fa-solid <?php echo esc_attr($settings['button_icon']); ?>" 
                       aria-hidden="true"
                    ></i>
                <?php endif; ?>
                <span class="button-text"><?php echo esc_html($settings['button_text']); ?></span>
                <?php if ($config['show_screen_reader_text'] && $config['post_id']): ?>
                    <span class="screen-reader-text">
                        <?php 
                        printf(
                            /* translators: %s: Post title */
                            esc_html__('Download PDF version of %s', 'wp-post-to-pdf'),
                            get_the_title($config['post_id'])
                        ); 
                        ?>
                    </span>
                <?php endif; ?>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Build a CSS style string from an array of properties
     *
     * @param array $styles Array of CSS properties
     * @return string
     */
    private static function build_style_string($styles) {
        $style_string = '';
        foreach ($styles as $property => $value) {
            $style_string .= "{$property}: {$value}; ";
        }
        return trim($style_string);
    }
} 