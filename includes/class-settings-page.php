<?php
/**
 * Settings page functionality
 *
 * @package WP_Post_to_PDF
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class WP_Post_to_PDF_Settings
 */
class WP_Post_to_PDF_Settings {

    /**
     * Settings page slug
     *
     * @var string
     */
    private $page_slug = 'wp-post-to-pdf-settings';

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Add AJAX handler for reset settings
        add_action('wp_ajax_reset_pdf_settings', array($this, 'handle_reset_settings'));
        
        // Add AJAX handler for mass export
        add_action('wp_ajax_mass_export_pdf', array($this, 'handle_mass_export'));
    }

    /**
     * Add options page to the WordPress admin menu
     *
     * @return void
     */
    public function add_settings_page() {
        add_options_page(
            __('WP Post To PDF Settings', 'wp-post-to-pdf'),
            __('WP Post To PDF', 'wp-post-to-pdf'),
            'manage_options',
            $this->page_slug,
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register plugin settings
     *
     * @return void
     */
    public function register_settings() {
        register_setting(
            'wp_post_to_pdf_options',
            'wp_post_to_pdf_settings',
            array($this, 'sanitize_settings')
        );

        // Add Button Settings section
        add_settings_section(
            'wp_post_to_pdf_button_section',
            __('Button Settings', 'wp-post-to-pdf'),
            array($this, 'render_button_section_description'),
            $this->page_slug
        );

        // Add Button Text field
        add_settings_field(
            'button_text',
            __('Button Text', 'wp-post-to-pdf'),
            array($this, 'render_button_text_field'),
            $this->page_slug,
            'wp_post_to_pdf_button_section',
            array(
                'label_for' => 'button_text',
                'class' => 'wp-post-to-pdf-row',
            )
        );

        // Add Button Font field
        add_settings_field(
            'button_font',
            __('Button Font', 'wp-post-to-pdf'),
            array($this, 'render_button_font_field'),
            $this->page_slug,
            'wp_post_to_pdf_button_section',
            array(
                'label_for' => 'button_font',
                'class' => 'wp-post-to-pdf-row',
            )
        );

        // Add Button Font Size field
        add_settings_field(
            'button_font_size',
            __('Button Font Size', 'wp-post-to-pdf'),
            array($this, 'render_font_size_field'),
            $this->page_slug,
            'wp_post_to_pdf_button_section',
            array(
                'label_for' => 'button_font_size',
                'class' => 'wp-post-to-pdf-row',
            )
        );

        // Add Button Size field
        add_settings_field(
            'button_size',
            __('Button Size', 'wp-post-to-pdf'),
            array($this, 'render_button_size_field'),
            $this->page_slug,
            'wp_post_to_pdf_button_section',
            array(
                'label_for' => 'button_size',
                'class' => 'wp-post-to-pdf-row',
            )
        );

        // Add Button Icon field
        add_settings_field(
            'button_icon',
            __('Button Icon', 'wp-post-to-pdf'),
            array($this, 'render_button_icon_field'),
            $this->page_slug,
            'wp_post_to_pdf_button_section',
            array(
                'label_for' => 'button_icon',
                'class' => 'wp-post-to-pdf-row',
            )
        );

        // Add Button Placement field
        add_settings_field(
            'button_placement',
            __('Button Placement', 'wp-post-to-pdf'),
            array($this, 'render_button_placement_field'),
            $this->page_slug,
            'wp_post_to_pdf_button_section',
            array(
                'label_for' => 'button_placement',
                'class' => 'wp-post-to-pdf-row',
            )
        );

        // Move Button Hover Effect switch to button section
        add_settings_field(
            'button_hover_effect',
            __('Button Hover Effect', 'wp-post-to-pdf'),
            array($this, 'render_hover_effect_switch'),
            $this->page_slug,
            'wp_post_to_pdf_button_section',
            array(
                'label_for' => 'button_hover_effect',
                'description' => __('Enable or disable the glow effect when hovering over the button.', 'wp-post-to-pdf'),
                'class' => 'wp-post-to-pdf-row',
            )
        );

        // Add Color Settings section
        add_settings_section(
            'wp_post_to_pdf_color_section',
            __('Color Settings', 'wp-post-to-pdf'),
            array($this, 'render_color_section_description'),
            $this->page_slug
        );

        // Add Button Background Color field
        add_settings_field(
            'button_bg_color',
            __('Button Background Color', 'wp-post-to-pdf'),
            array($this, 'render_color_picker_field'),
            $this->page_slug,
            'wp_post_to_pdf_color_section',
            array(
                'label_for' => 'button_bg_color',
                'description' => __('Choose the background color for the PDF download button.', 'wp-post-to-pdf'),
                'class' => 'wp-post-to-pdf-row',
            )
        );

        // Add Button Hover Background Color field
        add_settings_field(
            'button_bg_color_hover',
            __('Button Hover Background Color', 'wp-post-to-pdf'),
            array($this, 'render_hover_color_picker_field'),
            $this->page_slug,
            'wp_post_to_pdf_color_section',
            array(
                'label_for' => 'button_bg_color_hover',
                'description' => __('Choose the background color when hovering over the button.', 'wp-post-to-pdf'),
                'class' => 'wp-post-to-pdf-row',
            )
        );

        // Add Button Font Color field
        add_settings_field(
            'button_font_color',
            __('Button Font Color', 'wp-post-to-pdf'),
            array($this, 'render_font_color_picker_field'),
            $this->page_slug,
            'wp_post_to_pdf_color_section',
            array(
                'label_for' => 'button_font_color',
                'description' => __('Choose the text color for the PDF download button (hover state will always be white).', 'wp-post-to-pdf'),
                'class' => 'wp-post-to-pdf-row',
            )
        );

        // Add Button Hover Font Color field after the button_font_color field
        add_settings_field(
            'button_font_color_hover',
            __('Button Hover Font Color', 'wp-post-to-pdf'),
            array($this, 'render_color_picker_field'),
            $this->page_slug,
            'wp_post_to_pdf_color_section',
            array(
                'label_for' => 'button_font_color_hover',
                'class' => 'wp-post-to-pdf-row',
                'description' => __('Choose the font color when hovering over the button', 'wp-post-to-pdf')
            )
        );

        // Add Preview Panel section
        add_settings_section(
            'wp_post_to_pdf_preview_section',
            __('Button Preview', 'wp-post-to-pdf'),
            array($this, 'render_preview_section'),
            $this->page_slug
        );

        // Add new Mass Export section
        add_settings_section(
            'wp_post_to_pdf_mass_export',
            __('Mass Export Settings', 'wp-post-to-pdf'),
            array($this, 'mass_export_section_callback'),
            $this->page_slug
        );

        // Add content type selection field
        add_settings_field(
            'wp_post_to_pdf_content_type',
            __('Content Type', 'wp-post-to-pdf'),
            array($this, 'content_type_callback'),
            $this->page_slug,
            'wp_post_to_pdf_mass_export'
        );

        // Register the new setting
        register_setting('wp_post_to_pdf_options', 'wp_post_to_pdf_content_type');

        // In the register_settings method, add this new section after the mass export section
        add_settings_section(
            'wp_post_to_pdf_mass_export_action',
            '',
            array($this, 'mass_export_action_callback'),
            $this->page_slug
        );
    }

    /**
     * Render the button section description
     *
     * @return void
     */
    public function render_button_section_description() {
        echo '<p>' . esc_html__('Configure the basic appearance, behavior, and interaction effects of your PDF download button.', 'wp-post-to-pdf') . '</p>';
        echo '<hr class="section-divider">';
    }

    /**
     * Render the color section description
     *
     * @return void
     */
    public function render_color_section_description() {
        echo '<p>' . esc_html__('Customize the color scheme of your PDF download button.', 'wp-post-to-pdf') . '</p>';
        echo '<hr class="section-divider">';
    }

    /**
     * Render the button text field
     *
     * @param array $args Field arguments.
     * @return void
     */
    public function render_button_text_field($args) {
        $options = get_option('wp_post_to_pdf_settings');
        $value = isset($options['button_text']) ? $options['button_text'] : __('Download PDF', 'wp-post-to-pdf');
        ?>
        <div class="field-wrapper">
            <div class="field-input">
                <input 
                    type="text" 
                    id="<?php echo esc_attr($args['label_for']); ?>"
                    name="wp_post_to_pdf_settings[button_text]"
                    value="<?php echo esc_attr($value); ?>"
                    class="regular-text"
                    placeholder="<?php esc_attr_e('Enter button text', 'wp-post-to-pdf'); ?>"
                >
                <span class="help-icon" data-tooltip="<?php esc_attr_e('This text will appear on the button that users click to download the PDF version of your post.', 'wp-post-to-pdf'); ?>">
                    <i class="fa-solid fa-circle-question"></i>
                </span>
            </div>
            <p class="description">
                <?php esc_html_e('The text that will appear on the PDF download button.', 'wp-post-to-pdf'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Get available fonts list
     *
     * @return array
     */
    private function get_available_fonts() {
        return array(
            'google' => array(
                'group_label' => __('Google Fonts', 'wp-post-to-pdf'),
                'fonts' => array(
                    'Roboto' => array(
                        'name' => 'Roboto',
                        'weights' => array(300, 500, 700)
                    ),
                    'Open Sans' => array(
                        'name' => 'Open Sans',
                        'weights' => array(300, 500, 700)
                    ),
                    'Lato' => array(
                        'name' => 'Lato',
                        'weights' => array(300, 500, 700)
                    ),
                    'Montserrat' => array(
                        'name' => 'Montserrat',
                        'weights' => array(300, 500, 700)
                    ),
                    'Poppins' => array(
                        'name' => 'Poppins',
                        'weights' => array(300, 500, 700)
                    ),
                ),
            ),
        );
    }

    /**
     * Render the button font field
     *
     * @param array $args Field arguments.
     * @return void
     */
    public function render_button_font_field($args) {
        $options = get_option('wp_post_to_pdf_settings');
        $selected_font = isset($options['button_font']) ? $options['button_font'] : 'Arial, sans-serif';
        $selected_weight = isset($options['button_font_weight']) ? intval($options['button_font_weight']) : 500;
        $fonts = $this->get_available_fonts();
        ?>
        <div class="field-wrapper">
            <div class="field-input">
                <select 
                    id="<?php echo esc_attr($args['label_for']); ?>"
                    name="wp_post_to_pdf_settings[button_font]"
                    class="font-select"
                    data-current-weight="<?php echo esc_attr($selected_weight); ?>"
                >
                    <?php foreach ($fonts as $group): ?>
                        <optgroup label="<?php echo esc_attr($group['group_label']); ?>">
                            <?php foreach ($group['fonts'] as $label => $font): ?>
                                <?php 
                                // Ensure weights is a simple array for JSON
                                $weights_json = wp_json_encode($font['weights']);
                                ?>
                                <option 
                                    value="<?php echo esc_attr($font['name']); ?>"
                                    <?php selected($selected_font, $font['name']); ?>
                                    data-weights="<?php echo esc_attr($weights_json); ?>"
                                    style="font-family: <?php echo esc_attr($font['name']); ?>"
                                >
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <span class="help-icon" data-tooltip="<?php esc_attr_e('Choose a font for your PDF download button.', 'wp-post-to-pdf'); ?>">
                    <i class="fa-solid fa-circle-question"></i>
                </span>
            </div>
            <p class="description">
                <?php esc_html_e('Select the font family for the PDF download button.', 'wp-post-to-pdf'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Get available icons list
     *
     * @return array
     */
    private function get_available_icons() {
        return array(
            'none' => array(
                'label' => __('No Icon', 'wp-post-to-pdf'),
                'icon' => ''
            ),
            'fa-file-pdf' => array(
                'label' => __('PDF File', 'wp-post-to-pdf'),
                'icon' => 'fa-file-pdf'
            ),
            'fa-download' => array(
                'label' => __('Download', 'wp-post-to-pdf'),
                'icon' => 'fa-download'
            ),
            'fa-file-arrow-down' => array(
                'label' => __('File Download', 'wp-post-to-pdf'),
                'icon' => 'fa-file-arrow-down'
            ),
            'fa-cloud-arrow-down' => array(
                'label' => __('Cloud Download', 'wp-post-to-pdf'),
                'icon' => 'fa-cloud-arrow-down'
            ),
            'fa-circle-down' => array(
                'label' => __('Circle Download', 'wp-post-to-pdf'),
                'icon' => 'fa-circle-down'
            ),
        );
    }

    /**
     * Render the button icon field
     *
     * @param array $args Field arguments.
     * @return void
     */
    public function render_button_icon_field($args) {
        $options = get_option('wp_post_to_pdf_settings');
        $selected = isset($options['button_icon']) ? $options['button_icon'] : 'fa-file-pdf';
        $icons = $this->get_available_icons();
        ?>
        <div class="field-wrapper">
            <div class="field-input">
                <div class="custom-select icon-select-wrapper">
                    <div class="selected-option">
                        <?php if (!empty($icons[$selected]['icon'])): ?>
                            <i class="fa-solid <?php echo esc_attr($icons[$selected]['icon']); ?>"></i>
                        <?php endif; ?>
                        <span><?php echo esc_html($icons[$selected]['label']); ?></span>
                    </div>
                    <div class="options-list">
                        <?php foreach ($icons as $value => $icon_data): ?>
                            <div class="option <?php echo $selected === $value ? 'selected' : ''; ?>" 
                                 data-value="<?php echo esc_attr($value); ?>">
                                <?php if (!empty($icon_data['icon'])): ?>
                                    <i class="fa-solid <?php echo esc_attr($icon_data['icon']); ?>"></i>
                                <?php endif; ?>
                                <span><?php echo esc_html($icon_data['label']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" 
                        name="wp_post_to_pdf_settings[button_icon]" 
                        value="<?php echo esc_attr($selected); ?>"
                        id="<?php echo esc_attr($args['label_for']); ?>"
                    >
                </div>
                <span class="help-icon" data-tooltip="<?php esc_attr_e('Choose an icon to display next to your button text, or select "No Icon" to show text only.', 'wp-post-to-pdf'); ?>">
                    <i class="fa-solid fa-circle-question"></i>
                </span>
            </div>
            <p class="description">
                <?php esc_html_e('Select an icon to display alongside the button text.', 'wp-post-to-pdf'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Get available placement options
     *
     * @return array
     */
    private function get_placement_options() {
        return array(
            'top-left' => __('Top Left', 'wp-post-to-pdf'),
            'top-center' => __('Top Center', 'wp-post-to-pdf'),
            'top-right' => __('Top Right', 'wp-post-to-pdf'),
            'bottom-left' => __('Bottom Left', 'wp-post-to-pdf'),
            'bottom-center' => __('Bottom Center', 'wp-post-to-pdf'),
            'bottom-right' => __('Bottom Right', 'wp-post-to-pdf'),
            'none' => __('Do not show automatically', 'wp-post-to-pdf'),
        );
    }

    /**
     * Render the button placement field
     *
     * @param array $args Field arguments.
     * @return void
     */
    public function render_button_placement_field($args) {
        $options = get_option('wp_post_to_pdf_settings');
        $selected = isset($options['button_placement']) ? $options['button_placement'] : 'bottom';
        $placements = $this->get_placement_options();
        ?>
        <div class="field-wrapper">
            <div class="field-input">
                <select 
                    id="<?php echo esc_attr($args['label_for']); ?>"
                    name="wp_post_to_pdf_settings[button_placement]"
                    class="placement-select"
                >
                    <?php foreach ($placements as $value => $label): ?>
                        <option 
                            value="<?php echo esc_attr($value); ?>"
                            <?php selected($selected, $value); ?>
                        >
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="help-icon" data-tooltip="<?php esc_attr_e('Choose where to display the button on your posts. Select "Do not show automatically" if you prefer to use the shortcode [post_to_pdf] for manual placement.', 'wp-post-to-pdf'); ?>">
                    <i class="fa-solid fa-circle-question"></i>
                </span>
            </div>
            <p class="description">
                <?php esc_html_e('Choose where to display the PDF download button on posts.', 'wp-post-to-pdf'); ?>
                <?php if ($selected === 'none'): ?>
                    <br>
                    <?php esc_html_e('Note: You can manually add the button using the shortcode [post_to_pdf].', 'wp-post-to-pdf'); ?>
                <?php endif; ?>
            </p>
        </div>
        <?php
    }

    /**
     * Render the preview section
     *
     * @return void
     */
    public function render_preview_section() {
        $settings = get_option('wp_post_to_pdf_settings', array());
        $config = array(
            'context' => 'preview',
            'wrapper_class' => 'preview-button-wrapper',
            'show_placement' => false // Never use placement in preview
        );
        ?>
        <div class="button-preview-panel">
            <div class="preview-header">
                <?php esc_html_e('Live Preview', 'wp-post-to-pdf'); ?>
            </div>
            <div class="preview-content">
                <?php echo WP_Post_to_PDF_Button_Renderer::render($settings, $config); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook The current admin page.
     * @return void
     */
    public function enqueue_admin_assets($hook) {
        if ("settings_page_{$this->page_slug}" !== $hook) {
            return;
        }

        // Enqueue our custom fonts CSS first
        wp_enqueue_style(
            'wp-post-to-pdf-fonts',
            WP_POST_TO_PDF_URL . 'assets/css/fonts.css',
            array(),
            WP_POST_TO_PDF_VERSION
        );

        // Enqueue admin styles after fonts
        wp_enqueue_style(
            'wp-post-to-pdf-admin',
            WP_POST_TO_PDF_URL . 'assets/css/admin-styles.css',
            array('wp-post-to-pdf-fonts'), // Make admin styles depend on fonts
            WP_POST_TO_PDF_VERSION
        );

        wp_enqueue_script(
            'wp-post-to-pdf-admin',
            WP_POST_TO_PDF_URL . 'assets/js/admin-scripts.js',
            array('jquery'),
            WP_POST_TO_PDF_VERSION,
            true
        );

        // Add nonce for mass export
        wp_localize_script('wp-post-to-pdf-admin', 'wp_post_to_pdf', array(
            'nonce' => wp_create_nonce('wp_post_to_pdf_mass_export')
        ));

        // Add Font Awesome
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            array(),
            '6.4.0'
        );
    }

    /**
     * Render the settings page content
     *
     * @return void
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>
                <?php echo esc_html(get_admin_page_title()); ?>
                <span class="help-icon" data-tooltip="<?php esc_attr_e('Configure how the PDF download button appears and behaves on your posts.', 'wp-post-to-pdf'); ?>">
                    <i class="fa-solid fa-circle-question"></i>
                </span>
            </h1>
            
            <!-- Success Message Container -->
            <div id="settings-updated" class="notice notice-success is-dismissible" style="display: none;">
                <p></p>
            </div>

            <form action="options.php" method="post" id="wp-post-to-pdf-settings-form">
                <?php
                settings_fields('wp_post_to_pdf_options');
                do_settings_sections($this->page_slug);
                ?>
                <div class="action-buttons">
                    <?php submit_button(__('Save Settings', 'wp-post-to-pdf'), 'primary', 'submit', false); ?>
                    <button type="button" id="reset-settings" class="button button-secondary">
                        <?php esc_html_e('Reset to Default', 'wp-post-to-pdf'); ?>
                    </button>
                    <a href="https://www.buymeacoffee.com/pimzino" target="_blank" class="button button-secondary coffee-button">
                        <i class="fa-solid fa-mug-hot"></i>
                        <?php esc_html_e('Buy Me A Coffee', 'wp-post-to-pdf'); ?>
                    </a>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Sanitize settings before saving
     *
     * @param array $input The input array to sanitize.
     * @return array
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Sanitize button text
        if (isset($input['button_text'])) {
            $sanitized['button_text'] = sanitize_text_field($input['button_text']);
        }

        // Sanitize button font
        if (isset($input['button_font'])) {
            $fonts = $this->get_available_fonts();
            $valid_fonts = array();
            foreach ($fonts as $group) {
                foreach ($group['fonts'] as $label => $font) {
                    $valid_fonts[] = $font['name'];
                }
            }
            
            if (in_array($input['button_font'], $valid_fonts)) {
                $sanitized['button_font'] = $input['button_font'];
            }
        }

        // Sanitize button icon
        if (isset($input['button_icon'])) {
            $valid_icons = array_keys($this->get_available_icons());
            if (in_array($input['button_icon'], $valid_icons)) {
                $sanitized['button_icon'] = $input['button_icon'];
            }
        }

        // Sanitize button placement
        if (isset($input['button_placement'])) {
            $valid_placements = array_keys($this->get_placement_options());
            if (in_array($input['button_placement'], $valid_placements)) {
                $sanitized['button_placement'] = $input['button_placement'];
            }
        }

        // Sanitize button background color
        if (isset($input['button_bg_color'])) {
            $sanitized['button_bg_color'] = sanitize_hex_color($input['button_bg_color']);
        }

        // Sanitize button hover background color
        if (isset($input['button_bg_color_hover'])) {
            $sanitized['button_bg_color_hover'] = sanitize_hex_color($input['button_bg_color_hover']);
        }

        // Sanitize button font color
        if (isset($input['button_font_color'])) {
            $sanitized['button_font_color'] = sanitize_hex_color($input['button_font_color']);
        }

        // Sanitize hover effect switch
        $sanitized['button_hover_effect'] = !empty($input['button_hover_effect']);

        // Sanitize button font size with new expanded range
        if (isset($input['button_font_size'])) {
            $size = intval($input['button_font_size']);
            $sanitized['button_font_size'] = min(max($size, 8), 48); // Clamp between 8 and 48
        }

        // Sanitize button size
        if (isset($input['button_size'])) {
            $valid_sizes = array('extra-small', 'small', 'medium', 'large', 'extra-large');
            if (in_array($input['button_size'], $valid_sizes)) {
                $sanitized['button_size'] = $input['button_size'];
            } else {
                $sanitized['button_size'] = 'medium'; // Default if invalid
            }
        }

        // Add sanitization for button font color hover
        if (isset($input['button_font_color_hover'])) {
            $sanitized['button_font_color_hover'] = sanitize_hex_color($input['button_font_color_hover']);
        }

        return $sanitized;
    }

    /**
     * Handle reset settings AJAX request
     *
     * @return void
     */
    public function handle_reset_settings() {
        // Verify nonce
        check_ajax_referer('wp_post_to_pdf_options-options');

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'wp-post-to-pdf')
            ));
        }

        // Get default settings
        $default_settings = $this->get_default_settings();

        // Update options with defaults
        update_option('wp_post_to_pdf_settings', $default_settings);

        // Send success response
        wp_send_json_success(array(
            'message' => __('Settings have been reset to default values and saved.', 'wp-post-to-pdf'),
            'settings' => $default_settings
        ));
    }

    /**
     * Get default settings
     *
     * @return array
     */
    private function get_default_settings() {
        return array(
            'button_text' => __('Download PDF', 'wp-post-to-pdf'),
            'button_font' => 'Poppins',
            'button_font_weight' => '400',
            'button_font_size' => 13,
            'button_size' => 'small',
            'button_icon' => 'fa-download',
            'button_placement' => 'bottom',
            'button_hover_effect' => true,
            'button_bg_color' => '#6262ff',
            'button_bg_color_hover' => '#8000ff',
            'button_font_color' => '#ffffff',
            'button_font_color_hover' => '#ffffff',
        );
    }

    /**
     * Render the color picker field
     *
     * @param array $args Field arguments.
     * @return void
     */
    public function render_color_picker_field($args) {
        $options = get_option('wp_post_to_pdf_settings');
        $field_name = str_replace('button_', '', $args['label_for']);
        $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : '#FFFFFF';
        ?>
        <div class="field-wrapper">
            <div class="field-input">
                <input 
                    type="color" 
                    id="<?php echo esc_attr($args['label_for']); ?>"
                    name="wp_post_to_pdf_settings[<?php echo esc_attr($args['label_for']); ?>]"
                    value="<?php echo esc_attr($value); ?>"
                    class="color-picker"
                >
                <code class="color-value"><?php echo esc_html($value); ?></code>
                <?php if (isset($args['description'])): ?>
                    <span class="help-icon" data-tooltip="<?php echo esc_attr($args['description']); ?>">
                    <i class="fa-solid fa-circle-question"></i>
                </span>
                <?php endif; ?>
            </div>
            <?php if (isset($args['description'])): ?>
                <p class="description"><?php echo esc_html($args['description']); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render the font color picker field
     *
     * @param array $args Field arguments.
     * @return void
     */
    public function render_font_color_picker_field($args) {
        $options = get_option('wp_post_to_pdf_settings');
        $value = isset($options['button_font_color']) ? $options['button_font_color'] : '#ffffff';
        ?>
        <div class="field-wrapper">
            <div class="field-input">
                <input 
                    type="color" 
                    id="button_font_color"
                    name="wp_post_to_pdf_settings[button_font_color]"
                    value="<?php echo esc_attr($value); ?>"
                    class="color-picker"
                >
                <code class="color-value"><?php echo esc_html($value); ?></code>
                <span class="help-icon" data-tooltip="<?php esc_attr_e('Choose the color for your button text and icon. Note: This color will change to white when the button is hovered over.', 'wp-post-to-pdf'); ?>">
                    <i class="fa-solid fa-circle-question"></i>
                </span>
            </div>
            <?php if (isset($args['description'])): ?>
                <p class="description"><?php echo esc_html($args['description']); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render the hover color picker field
     *
     * @param array $args Field arguments.
     * @return void
     */
    public function render_hover_color_picker_field($args) {
        $options = get_option('wp_post_to_pdf_settings');
        $value = isset($options['button_bg_color_hover']) ? $options['button_bg_color_hover'] : '#683FEA';
        ?>
        <div class="field-wrapper">
            <div class="field-input">
                <input 
                    type="color" 
                    id="button_bg_color_hover"
                    name="wp_post_to_pdf_settings[button_bg_color_hover]"
                    value="<?php echo esc_attr($value); ?>"
                    class="color-picker"
                >
                <code class="color-value"><?php echo esc_html($value); ?></code>
                <span class="help-icon" data-tooltip="<?php esc_attr_e('Select the background color that appears when users hover over the button. This will create a gradient effect with the main background color.', 'wp-post-to-pdf'); ?>">
                    <i class="fa-solid fa-circle-question"></i>
                </span>
            </div>
            <?php if (isset($args['description'])): ?>
                <p class="description"><?php echo esc_html($args['description']); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render the hover effect switch
     *
     * @param array $args Field arguments.
     * @return void
     */
    public function render_hover_effect_switch($args) {
        $options = get_option('wp_post_to_pdf_settings');
        $enabled = isset($options['button_hover_effect']) ? $options['button_hover_effect'] : true;
        ?>
        <div class="field-wrapper">
            <div class="field-input">
                <label class="switch">
                    <input 
                        type="checkbox" 
                        id="<?php echo esc_attr($args['label_for']); ?>"
                        name="wp_post_to_pdf_settings[button_hover_effect]"
                        value="1"
                        <?php checked($enabled, true); ?>
                    >
                    <span class="slider round"></span>
                </label>
                <span class="switch-label"><?php echo $enabled ? esc_html__('Enabled', 'wp-post-to-pdf') : esc_html__('Disabled', 'wp-post-to-pdf'); ?></span>
                <span class="help-icon" data-tooltip="<?php esc_attr_e('Toggle the glow effect that appears when users hover over the button. When disabled, only the color will change.', 'wp-post-to-pdf'); ?>">
                    <i class="fa-solid fa-circle-question"></i>
                </span>
            </div>
            <?php if (isset($args['description'])): ?>
                <p class="description"><?php echo esc_html($args['description']); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render the font size dropdown field
     *
     * @param array $args Field arguments.
     * @return void
     */
    public function render_font_size_field($args) {
        $options = get_option('wp_post_to_pdf_settings');
        $value = isset($options['button_font_size']) ? intval($options['button_font_size']) : 16;
        ?>
        <div class="field-wrapper">
            <div class="field-input">
                <div class="range-slider-wrapper">
                    <input 
                        type="range" 
                        id="<?php echo esc_attr($args['label_for']); ?>"
                        name="wp_post_to_pdf_settings[button_font_size]"
                        min="8"
                        max="48"
                        step="1"
                        value="<?php echo esc_attr($value); ?>"
                        class="font-size-slider"
                    >
                    <span class="font-size-value"><?php echo esc_html($value); ?>px</span>
                </div>
                <span class="help-icon" data-tooltip="<?php esc_attr_e('Adjust the size of both button text and icon (8px - 48px).', 'wp-post-to-pdf'); ?>">
                    <i class="fa-solid fa-circle-question"></i>
                </span>
            </div>
            <p class="description">
                <?php esc_html_e('Adjust the size of your button text and icon (8px - 48px).', 'wp-post-to-pdf'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Render the button size dropdown field
     *
     * @param array $args Field arguments.
     * @return void
     */
    public function render_button_size_field($args) {
        $options = get_option('wp_post_to_pdf_settings');
        $value = isset($options['button_size']) ? $options['button_size'] : 'medium';
        ?>
        <div class="field-wrapper">
            <div class="field-input">
                <select id="<?php echo esc_attr($args['label_for']); ?>"
                        name="wp_post_to_pdf_settings[button_size]">
                    <option value="extra-small" <?php selected($value, 'extra-small'); ?>><?php esc_html_e('Extra Small', 'wp-post-to-pdf'); ?></option>
                    <option value="small" <?php selected($value, 'small'); ?>><?php esc_html_e('Small', 'wp-post-to-pdf'); ?></option>
                    <option value="medium" <?php selected($value, 'medium'); ?>><?php esc_html_e('Medium', 'wp-post-to-pdf'); ?></option>
                    <option value="large" <?php selected($value, 'large'); ?>><?php esc_html_e('Large', 'wp-post-to-pdf'); ?></option>
                    <option value="extra-large" <?php selected($value, 'extra-large'); ?>><?php esc_html_e('Extra Large', 'wp-post-to-pdf'); ?></option>
                </select>
                <span class="help-icon" data-tooltip="<?php esc_attr_e('Choose from various button size presets to match your design needs.', 'wp-post-to-pdf'); ?>">
                    <i class="fa-solid fa-circle-question"></i>
                </span>
            </div>
            <p class="description">
                <?php esc_html_e('Select from various button size presets.', 'wp-post-to-pdf'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Render the Mass Export section
     *
     * @return void
     */
    public function mass_export_section_callback() {
        echo '<p>' . esc_html__('Perform bulk PDF export of your content.', 'wp-post-to-pdf') . '</p>';
    }

    /**
     * Render the content type selection field
     *
     * @return void
     */
    public function content_type_callback() {
        $content_type = get_option('wp_post_to_pdf_content_type', 'posts');
        ?>
        <div class="field-wrapper">
            <div class="field-input">
                <select name="wp_post_to_pdf_content_type" id="wp_post_to_pdf_content_type" class="regular-text">
                    <option value="posts" <?php selected($content_type, 'posts'); ?>><?php esc_html_e('Posts Only', 'wp-post-to-pdf'); ?></option>
                    <option value="pages" <?php selected($content_type, 'pages'); ?>><?php esc_html_e('Pages Only', 'wp-post-to-pdf'); ?></option>
                    <option value="both" <?php selected($content_type, 'both'); ?>><?php esc_html_e('Posts and Pages', 'wp-post-to-pdf'); ?></option>
                </select>
                <span class="help-icon" data-tooltip="<?php esc_attr_e('Choose which type of content to export to PDF.', 'wp-post-to-pdf'); ?>">
                    <i class="fa-solid fa-circle-question"></i>
                </span>
            </div>
            <p class="description">
                <?php esc_html_e('Select the type of content you want to export as PDF.', 'wp-post-to-pdf'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Render the Mass Export Action section
     *
     * @return void
     */
    public function mass_export_action_callback() {
        ?>
        <button type="button" id="wp-post-to-pdf-mass-export" class="button button-primary">
            <?php esc_html_e('Export Now', 'wp-post-to-pdf'); ?>
        </button>
        <?php
    }

    /**
     * Handle mass export of posts to PDF
     */
    public function handle_mass_export() {
        try {
            // Check nonce and permissions
            check_ajax_referer('wp_post_to_pdf_mass_export', 'nonce');
            if (!current_user_can('manage_options')) {
                throw new Exception('Insufficient permissions');
            }

            // Get content type from AJAX request
            $content_type = isset($_POST['content_type']) ? sanitize_text_field($_POST['content_type']) : 'posts';
            
            // Set up query args based on content type
            $query_args = array(
                'post_status' => 'publish',
                'posts_per_page' => -1,
            );

            switch ($content_type) {
                case 'posts':
                    $query_args['post_type'] = 'post';
                    break;
                case 'pages':
                    $query_args['post_type'] = 'page';
                    break;
                case 'both':
                    $query_args['post_type'] = array('post', 'page');
                    break;
                default:
                    throw new Exception('Invalid content type selected');
            }

            // Get all published content
            $posts = get_posts($query_args);

            if (empty($posts)) {
                throw new Exception('No content found to export');
            }

            // Check if ZipArchive is available
            if (!class_exists('ZipArchive')) {
                throw new Exception('PHP ZIP extension is not installed. Please run: sudo apt-get install php-zip');
            }

            // Create temporary directory for PDFs
            $upload_dir = wp_upload_dir();
            $temp_dir = $upload_dir['basedir'] . '/pdf_temp_' . uniqid();
            
            // Check if directory creation is successful
            if (!wp_mkdir_p($temp_dir)) {
                throw new Exception('Failed to create temporary directory');
            }

            // Initialize ZIP archive
            $zip = new ZipArchive();
            $zip_filename = $temp_dir . '/' . $content_type . '_export.zip';
            
            $zip_result = $zip->open($zip_filename, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            if ($zip_result !== TRUE) {
                throw new Exception('Could not create ZIP archive. Error code: ' . $zip_result);
            }

            // Load Dompdf
            require_once WP_POST_TO_PDF_PATH . 'vendor/autoload.php';
            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);
            $options->set('isRemoteEnabled', true);

            foreach ($posts as $post) {
                try {
                    // Initialize Dompdf for each post
                    $dompdf = new \Dompdf\Dompdf($options);
                    
                    // Get post content
                    $content = apply_filters('the_content', $post->post_content);
                    
                    // Create HTML structure
                    $html = '
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; }
                            .title { font-size: 24px; font-weight: bold; text-align: center; margin-bottom: 20px; }
                            .content { font-size: 12px; line-height: 1.6; }
                        </style>
                    </head>
                    <body>
                        <div class="title">' . esc_html($post->post_title) . '</div>
                        <div class="content">' . $content . '</div>
                    </body>
                    </html>';

                    // Load HTML into Dompdf
                    $dompdf->loadHtml($html);
                    
                    // Set paper size and orientation
                    $dompdf->setPaper('A4', 'portrait');
                    
                    // Render PDF
                    $dompdf->render();
                    
                    // Get PDF content as string
                    $pdf_content = $dompdf->output();

                    // Add PDF to ZIP
                    $safe_filename = sanitize_file_name($post->post_title . '.pdf');
                    if (!$zip->addFromString($safe_filename, $pdf_content)) {
                        throw new Exception('Failed to add PDF to ZIP for post: ' . $post->post_title);
                    }
                } catch (Exception $e) {
                    error_log('Error processing post ' . $post->ID . ': ' . $e->getMessage());
                    continue; // Continue with next post even if one fails
                }
            }

            // Close ZIP file
            if (!$zip->close()) {
                throw new Exception('Failed to close ZIP file');
            }

            // Check if ZIP file exists and is readable
            if (!file_exists($zip_filename) || !is_readable($zip_filename)) {
                throw new Exception('ZIP file not found or not readable');
            }

            // Read the ZIP file
            $zip_content = file_get_contents($zip_filename);
            if ($zip_content === false) {
                throw new Exception('Failed to read ZIP file');
            }

            // Clean up
            unlink($zip_filename);
            rmdir($temp_dir);

            // Send ZIP file content and filename
            wp_send_json_success(array(
                'content' => base64_encode($zip_content),
                'filename' => $content_type . '_export.zip'
            ));

        } catch (Exception $e) {
            error_log('WP Post to PDF Export Error: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }
}

// Initialize the settings page
new WP_Post_to_PDF_Settings();
