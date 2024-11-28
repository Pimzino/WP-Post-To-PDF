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
    // Activation code here
    // Add default options if needed
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