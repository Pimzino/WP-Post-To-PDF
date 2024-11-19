<?php
/**
 * PDF Generator functionality
 *
 * @package WP_Post_to_PDF
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Class WP_Post_to_PDF_Generator
 */
class WP_Post_to_PDF_Generator {

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        add_action('wp_ajax_generate_post_pdf', array($this, 'handle_pdf_generation'));
        add_action('wp_ajax_nopriv_generate_post_pdf', array($this, 'handle_pdf_generation'));

        // Include Composer autoloader
        require_once WP_POST_TO_PDF_PATH . 'vendor/autoload.php';
    }

    /**
     * Handle AJAX request for PDF generation
     *
     * @return void
     */
    public function handle_pdf_generation() {
        try {
            // Verify nonce
            if (!isset($_POST['nonce']) || 
                !wp_verify_nonce($_POST['nonce'], 'generate_pdf_' . $_POST['post_id'])) {
                throw new Exception(__('Security check failed. Please refresh the page and try again.', 'wp-post-to-pdf'));
            }

            // Verify post ID
            $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
            if (!$post_id) {
                throw new Exception(__('Invalid post ID provided.', 'wp-post-to-pdf'));
            }

            // Get post content
            $post = get_post($post_id);
            if (!$post || $post->post_status !== 'publish') {
                throw new Exception(__('Post not found or not published.', 'wp-post-to-pdf'));
            }

            // Generate filename from post title
            $filename = wp_post_to_pdf_clean_filename($post->post_title);
            if (empty($filename)) {
                $filename = 'document';
            }
            $filename .= '.pdf';

            // Prepare content
            $content = $this->prepare_content($post);

            // Generate PDF
            $pdf_content = $this->generate_pdf($content);

            // Return PDF content as base64
            wp_send_json_success(array(
                'pdf_content' => base64_encode($pdf_content),
                'filename' => $filename
            ));

        } catch (Exception $e) {
            error_log('WP Post to PDF - Error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => wp_post_to_pdf_get_friendly_error($e->getMessage())
            ));
        }
    }

    /**
     * Generate clean filename from post title
     *
     * @param string $title The post title.
     * @return string
     */
    private function generate_clean_filename($title) {
        // Remove any characters that aren't letters, numbers, hyphens, or spaces
        $filename = preg_replace('/[^a-zA-Z0-9\s-]/', '', $title);
        
        // Convert spaces to hyphens
        $filename = str_replace(' ', '-', $filename);
        
        // Convert to lowercase
        $filename = strtolower($filename);
        
        // Replace multiple hyphens with single hyphen
        $filename = preg_replace('/-+/', '-', $filename);
        
        // Remove hyphens from start and end
        $filename = trim($filename, '-');
        
        // Add .pdf extension
        return $filename . '.pdf';
    }

    /**
     * Clean up old PDF files
     *
     * @param string $directory Directory path.
     * @return void
     */
    private function cleanup_old_pdfs($directory) {
        if (!is_dir($directory)) {
            return;
        }

        // Get all PDF files in directory
        $files = glob($directory . '/*.pdf');
        
        // Keep only files from the last 24 hours
        $yesterday = time() - (24 * 60 * 60);
        
        foreach ($files as $file) {
            if (filemtime($file) < $yesterday) {
                @unlink($file);
            }
        }
    }

    /**
     * Prepare post content for PDF generation
     *
     * @param WP_Post $post The post object.
     * @return string
     */
    private function prepare_content($post) {
        // Get the raw content
        $content = $post->post_content;

        // Process content using helper functions
        $content = wp_post_to_pdf_process_html_content($content);
        $content = wp_post_to_pdf_process_images($content);
        $content = wp_post_to_pdf_process_media($content);
        $content = wp_post_to_pdf_process_code($content);

        // Get featured image if exists
        $featured_image = get_the_post_thumbnail_url($post->ID, 'full');

        // Build HTML structure
        $html = '<!DOCTYPE html><html><head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<title>' . esc_html($post->post_title) . '</title>';
        $html .= '<style>' . $this->get_pdf_styles() . '</style>';
        $html .= '</head><body>';

        // Add header with featured image
        $html .= $this->get_pdf_header($post->post_title, $featured_image);

        // Add content
        $html .= '<div class="post-content">' . $content . '</div>';

        // Add footer
        $html .= $this->get_pdf_footer();

        $html .= '</body></html>';

        return $html;
    }

    /**
     * Process images in content
     *
     * @param string $content The post content.
     * @return string
     */
    private function process_images($content) {
        // Convert relative image paths to absolute
        $upload_dir = wp_upload_dir();
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
     * Process media embeds
     *
     * @param string $content The post content.
     * @return string
     */
    private function process_media_embeds($content) {
        // Replace video embeds with a placeholder
        $content = preg_replace(
            '/<iframe[^>]+youtube\.com[^>]+>.*?<\/iframe>/i',
            '<div class="video-placeholder">Video content is not available in PDF format</div>',
            $content
        );

        // Replace audio players with a note
        $content = preg_replace(
            '/<audio[^>]*>.*?<\/audio>/is',
            '<div class="audio-placeholder">Audio content is not available in PDF format</div>',
            $content
        );

        return $content;
    }

    /**
     * Process code blocks
     *
     * @param string $content The post content.
     * @return string
     */
    private function process_code_blocks($content) {
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
     * Get PDF header HTML
     *
     * @param string $title The post title.
     * @param string $featured_image Featured image URL.
     * @return string
     */
    private function get_pdf_header($title, $featured_image) {
        $html = '<!DOCTYPE html><html><head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<title>' . esc_html($title) . '</title>';
        $html .= '<style>' . $this->get_pdf_styles() . '</style>';
        $html .= '</head><body>';

        if ($featured_image) {
            $html .= sprintf(
                '<div class="featured-image"><img src="%s" alt="%s"></div>',
                esc_url($featured_image),
                esc_attr($title)
            );
        }

        return $html;
    }

    /**
     * Get post metadata HTML
     *
     * @param string $author_name Author name.
     * @param string $post_date Post date.
     * @param string $categories Categories list.
     * @param string $tags Tags list.
     * @return string
     */
    private function get_post_meta($author_name, $post_date, $categories, $tags) {
        return sprintf(
            '<div class="post-meta">
                <p class="author">%s</p>
                <p class="date">%s</p>
                <p class="categories">%s</p>
                %s
            </div>',
            sprintf(
                /* translators: %s: Author name */
                esc_html__('By %s', 'wp-post-to-pdf'),
                esc_html($author_name)
            ),
            esc_html($post_date),
            sprintf(
                /* translators: %s: Categories list */
                esc_html__('Categories: %s', 'wp-post-to-pdf'),
                wp_kses_post($categories)
            ),
            $tags ? sprintf(
                /* translators: %s: Tags list */
                esc_html__('Tags: %s', 'wp-post-to-pdf'),
                wp_kses_post($tags)
            ) : ''
        );
    }

    /**
     * Get PDF footer HTML
     *
     * @return string
     */
    private function get_pdf_footer() {
        return sprintf(
            '<div class="pdf-footer">
                <p>%s</p>
                <p>%s</p>
            </div>
            </body>
            </html>',
            esc_html(get_bloginfo('name')),
            esc_html__('Generated with WP Post to PDF', 'wp-post-to-pdf')
        );
    }

    /**
     * Get styles for PDF
     *
     * @return string
     */
    private function get_pdf_styles() {
        return '
            @page {
                margin: 2cm;
                size: A4;
            }
            body {
                font-family: DejaVu Sans, Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                font-size: 11pt; /* Slightly smaller for better performance */
            }
            .post-header {
                margin-bottom: 30px;
            }
            h1 {
                font-size: 24pt;
                color: #1a1a1a;
                margin-bottom: 10px;
            }
            .post-meta {
                color: #666;
                font-size: 10pt;
                margin-bottom: 20px;
            }
            .post-content {
                margin-bottom: 30px;
            }
            img {
                max-width: 100%;
                height: auto;
                margin: 15px 0;
            }
            pre, code {
                font-family: DejaVu Sans Mono, monospace;
                font-size: 9pt; /* Smaller size for code blocks */
            }
            .pdf-footer {
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #ddd;
                font-size: 9pt;
                color: #666;
                text-align: center;
            }
            a {
                color: #2271b1;
                text-decoration: underline;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 15px 0;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
            }
            th {
                background: #f5f5f5;
            }

            /* Optimize image rendering */
            img {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                max-resolution: 300dpi;
            }

            /* Optimize font loading */
            * {
                font-family: DejaVu Sans, Arial, sans-serif;
            }
        ';
    }

    /**
     * Generate PDF content
     *
     * @param string $html The HTML content.
     * @return string PDF content
     * @throws Exception If PDF generation fails.
     */
    private function generate_pdf($html) {
        try {
            // Configure Dompdf
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', false);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('chroot', WP_CONTENT_DIR);
            $options->set('tempDir', get_temp_dir());
            $options->set('defaultMediaType', 'print');
            $options->set('isFontSubsettingEnabled', true);

            // Initialize Dompdf
            $dompdf = new Dompdf($options);
            
            // Set paper size and orientation
            $dompdf->setPaper('A4', 'portrait');

            // Load HTML content
            $dompdf->loadHtml($html);

            // Render PDF
            $dompdf->render();

            // Return the PDF content
            return $dompdf->output();

        } catch (Exception $e) {
            throw new Exception(
                sprintf(
                    /* translators: %s: Error message */
                    __('PDF Generation failed: %s', 'wp-post-to-pdf'),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Optimize images in HTML content
     *
     * @param string $html The HTML content.
     * @return string
     */
    private function optimize_images($html) {
        // Load HTML into DOMDocument
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        // Find all images
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            
            // Skip external images
            if (strpos($src, 'http') === 0 && strpos($src, get_site_url()) !== 0) {
                continue;
            }

            // Get image path
            $img_path = str_replace(get_site_url(), ABSPATH, $src);
            
            if (file_exists($img_path)) {
                // Get image info
                $info = getimagesize($img_path);
                if ($info === false) {
                    continue;
                }

                // Calculate optimal dimensions
                $max_width = 1200; // Maximum width in pixels
                $width = $info[0];
                $height = $info[1];

                if ($width > $max_width) {
                    $ratio = $max_width / $width;
                    $new_width = $max_width;
                    $new_height = $height * $ratio;

                    // Update image attributes
                    $img->setAttribute('width', $new_width);
                    $img->setAttribute('height', $new_height);
                }

                // Add loading attribute for optimization
                $img->setAttribute('loading', 'lazy');
            }
        }

        return $dom->saveHTML();
    }

    /**
     * Check and adjust memory limit if needed
     *
     * @throws Exception If memory limit cannot be increased.
     * @return void
     */
    private function check_memory_limit() {
        $memory_limit = ini_get('memory_limit');
        $memory_limit_bytes = wp_convert_hr_to_bytes($memory_limit);
        
        // If memory limit is less than 256M, try to increase it
        if ($memory_limit_bytes < 268435456) { // 256M in bytes
            $result = ini_set('memory_limit', '256M');
            if ($result === false) {
                error_log('WP Post to PDF - Warning: Could not increase memory limit');
            }
        }

        // Check if we have enough memory after adjustment
        $new_memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        if ($new_memory_limit < 67108864) { // 64M minimum
            throw new Exception(__('Insufficient memory available for PDF generation.', 'wp-post-to-pdf'));
        }
    }

    /**
     * Get user-friendly error message
     *
     * @param string $error_message The original error message.
     * @return string
     */
    private function get_user_friendly_error($error_message) {
        $known_errors = array(
            'DOMPDF_FONT_CACHE' => __('Font cache directory is not writable.', 'wp-post-to-pdf'),
            'allowed memory size' => __('Memory limit exceeded. Please contact your administrator.', 'wp-post-to-pdf'),
            'Permission denied' => __('Server permission error. Please contact your administrator.', 'wp-post-to-pdf'),
            'HTTP request failed' => __('Failed to load remote content. Please check your internet connection.', 'wp-post-to-pdf')
        );

        foreach ($known_errors as $key => $message) {
            if (stripos($error_message, $key) !== false) {
                return $message;
            }
        }

        // Return generic error message for unknown errors
        return __('An error occurred while generating the PDF. Please try again or contact support.', 'wp-post-to-pdf');
    }
}

// Initialize the PDF Generator
new WP_Post_to_PDF_Generator();
