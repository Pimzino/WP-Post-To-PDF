<?php
/**
 * Helper functions for WP Post to PDF
 *
 * @package WP_Post_to_PDF
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Clean and sanitize a filename
 *
 * @param string $filename The filename to clean.
 * @return string
 */
function wp_post_to_pdf_clean_filename($filename) {
    // Remove any characters that aren't letters, numbers, hyphens, or spaces
    $filename = preg_replace('/[^a-zA-Z0-9\s-]/', '', $filename);
    
    // Convert spaces to hyphens
    $filename = str_replace(' ', '-', $filename);
    
    // Convert to lowercase
    $filename = strtolower($filename);
    
    // Replace multiple hyphens with single hyphen
    $filename = preg_replace('/-+/', '-', $filename);
    
    // Remove hyphens from start and end
    $filename = trim($filename, '-');
    
    return $filename;
}

/**
 * Process HTML content for PDF generation
 *
 * @param string $content The HTML content to process.
 * @return string
 */
function wp_post_to_pdf_process_html_content($content) {
    // Process shortcodes and filters
    $content = do_shortcode($content);
    $content = apply_filters('the_content', $content);
    $content = str_replace(']]>', ']]&gt;', $content);
    
    return $content;
}

/**
 * Process images in content for PDF generation
 *
 * @param string $content The content with images to process.
 * @return string
 */
function wp_post_to_pdf_process_images($content) {
    // Convert relative image paths to absolute
    $site_url = get_site_url();

    // Handle relative URLs
    $content = preg_replace(
        '/(src=[\'"])(\/[^\/][^"\']+["\'])/i',
        '$1' . $site_url . '$2',
        $content
    );

    // Process image sizes and quality
    $content = preg_replace_callback(
        '/<img[^>]+>/i',
        function($matches) {
            $img = $matches[0];
            
            // Add max-width if not present
            if (strpos($img, 'max-width') === false) {
                $img = str_replace('<img', '<img style="max-width: 100%; height: auto;"', $img);
            }

            // Add loading="lazy" for better performance
            if (strpos($img, 'loading=') === false) {
                $img = str_replace('<img', '<img loading="lazy"', $img);
            }

            return $img;
        },
        $content
    );

    return $content;
}

/**
 * Process media embeds for PDF generation
 *
 * @param string $content The content with media embeds.
 * @return string
 */
function wp_post_to_pdf_process_media($content) {
    // Replace video embeds with a placeholder
    $content = preg_replace(
        '/<iframe[^>]+youtube\.com[^>]+>.*?<\/iframe>/i',
        '<div class="video-placeholder">' . esc_html__('Video content is not available in PDF format', 'wp-post-to-pdf') . '</div>',
        $content
    );

    // Replace audio players with a note
    $content = preg_replace(
        '/<audio[^>]*>.*?<\/audio>/is',
        '<div class="audio-placeholder">' . esc_html__('Audio content is not available in PDF format', 'wp-post-to-pdf') . '</div>',
        $content
    );

    return $content;
}

/**
 * Process code blocks for PDF generation
 *
 * @param string $content The content with code blocks.
 * @return string
 */
function wp_post_to_pdf_process_code($content) {
    // Process standard <pre> and <code> blocks
    $content = preg_replace_callback(
        '/<pre([^>]*)>(.*?)<\/pre>/is',
        function($matches) {
            $attrs = $matches[1];
            $code = $matches[2];
            
            // Decode HTML entities
            $code = html_entity_decode($code);
            // Escape HTML
            $code = htmlspecialchars($code);
            
            return sprintf(
                '<pre%s style="background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; font-family: monospace; font-size: 13px; line-height: 1.4;">%s</pre>',
                $attrs,
                $code
            );
        },
        $content
    );

    // Process inline code
    $content = preg_replace(
        '/<code([^>]*)>(.*?)<\/code>/i',
        '<code$1 style="background: #f5f5f5; padding: 2px 5px; border-radius: 3px; font-family: monospace; font-size: 0.9em;">$2</code>',
        $content
    );

    return $content;
}

/**
 * Get user-friendly error message
 *
 * @param string $error_message The original error message.
 * @return string
 */
function wp_post_to_pdf_get_friendly_error($error_message) {
    $known_errors = array(
        'memory' => __('Not enough memory to generate PDF. Please contact your site administrator.', 'wp-post-to-pdf'),
        'timeout' => __('The operation timed out. Please try again.', 'wp-post-to-pdf'),
        'permission' => __('Permission denied. Please check file permissions.', 'wp-post-to-pdf')
    );

    foreach ($known_errors as $key => $message) {
        if (stripos($error_message, $key) !== false) {
            return $message;
        }
    }

    return __('An error occurred while generating the PDF. Please try again later.', 'wp-post-to-pdf');
}