<?php
/**
 * Plugin Name: WP Post to PDF
 * Plugin URI: https://github.com/Pimzino/wp-post-to-pdf
 * Description: A powerful WordPress plugin that enables users to export blog posts to beautifully formatted, printable PDFs with extensive customization options.
 * Version: 1.1.0
 * Author: Pimzino
 * Author URI: https://x.com/pimzino
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-post-to-pdf
 * Domain Path: /languages
 *
 * @package WP_Post_to_PDF
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Plugin version
define('WP_POST_TO_PDF_VERSION', '1.1.0');

// Plugin directory path
define('WP_POST_TO_PDF_PATH', plugin_dir_path(__FILE__));

// Plugin directory URL
define('WP_POST_TO_PDF_URL', plugin_dir_url(__FILE__));

/**
 * Convert human readable size to bytes
 *
 * @param string $size Size string (e.g., '64M').
 * @return int Size in bytes
 */
function wp_post_to_pdf_convert_hr_to_bytes($size) {
    $size = trim($size);
    $last = strtolower($size[strlen($size)-1]);
    $value = intval($size);
    
    switch($last) {
        case 'g': $value *= 1024;
        case 'm': $value *= 1024;
        case 'k': $value *= 1024;
    }
    
    return $value;
}

/**
 * Load plugin text domain for translations.
 *
 * @return void
 */
function wp_post_to_pdf_load_textdomain() {
    load_plugin_textdomain('wp-post-to-pdf', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'wp_post_to_pdf_load_textdomain');

/**
 * Register activation hook.
 *
 * @return void
 */
function wp_post_to_pdf_activate() {
    $errors = array();

    // Check PHP version
    if (version_compare(PHP_VERSION, '7.2', '<')) {
        $errors[] = sprintf(
            __('PHP %s or higher is required. Your current PHP version is %s.', 'wp-post-to-pdf'),
            '7.2',
            PHP_VERSION
        );
    }

    // Check required PHP extensions
    $required_extensions = array('mbstring', 'gd', 'xml', 'dom');
    foreach ($required_extensions as $ext) {
        if (!extension_loaded($ext)) {
            $errors[] = sprintf(
                __('PHP extension %s is required but missing.', 'wp-post-to-pdf'),
                $ext
            );
        }
    }

    // Check memory limit
    $memory_limit = wp_post_to_pdf_convert_hr_to_bytes(ini_get('memory_limit'));
    if ($memory_limit < 67108864) { // 64MB in bytes
        $errors[] = __('Memory limit must be at least 64MB.', 'wp-post-to-pdf');
    }

    // Check if vendor/autoload.php exists
    if (!file_exists(WP_POST_TO_PDF_PATH . 'vendor/autoload.php')) {
        $errors[] = __('Required dependencies are missing. Please run composer install.', 'wp-post-to-pdf');
    }

    // Check write permissions
    $upload_dir = wp_upload_dir();
    if (!wp_is_writable($upload_dir['basedir'])) {
        $errors[] = __('WordPress uploads directory is not writable.', 'wp-post-to-pdf');
    }

    // If there are any errors, deactivate the plugin and display them
    if (!empty($errors)) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            '<h1>' . __('Plugin Activation Failed', 'wp-post-to-pdf') . '</h1>' .
            '<p>' . implode('</p><p>', $errors) . '</p>' .
            '<p><a href="' . admin_url('plugins.php') . '">' . __('Back to Plugins Page', 'wp-post-to-pdf') . '</a></p>',
            __('Plugin Activation Failed', 'wp-post-to-pdf'),
            array('back_link' => false)
        );
    }

    // Add default options
    add_option('wp_post_to_pdf_settings', array(
        'button_text' => __('Download PDF', 'wp-post-to-pdf'),
        'button_font' => 'Arial, sans-serif',
        'button_font_size' => 16,
        'button_size' => 'medium',
        'button_icon' => 'fa-file-pdf',
        'button_bg_color' => '#1C1A1C',
        'button_bg_color_hover' => '#683FEA',
        'button_font_color' => '#AAAAAA',
        'button_font_color_hover' => '#FFFFFF',
        'button_hover_effect' => true,
        'button_placement' => 'bottom-left'
    ));
}
register_activation_hook(__FILE__, 'wp_post_to_pdf_activate');

/**
 * Register deactivation hook.
 *
 * @return void
 */
function wp_post_to_pdf_deactivate() {
    // Deactivation code here
    // Clean up if needed
}
register_deactivation_hook(__FILE__, 'wp_post_to_pdf_deactivate');

/**
 * Include required files.
 */
require_once WP_POST_TO_PDF_PATH . 'includes/class-settings-page.php';
require_once WP_POST_TO_PDF_PATH . 'includes/class-pdf-generator.php';
require_once WP_POST_TO_PDF_PATH . 'includes/class-frontend.php';
require_once WP_POST_TO_PDF_PATH . 'includes/helpers.php';
require_once WP_POST_TO_PDF_PATH . 'includes/class-button-renderer.php'; 