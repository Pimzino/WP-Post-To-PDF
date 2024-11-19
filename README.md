# WP Post to PDF

A powerful WordPress plugin that enables users to export blog posts to beautifully formatted, printable PDFs with extensive customization options.

## Features

- **One-Click PDF Generation**: Convert any post or page to PDF with a single click
- **Extensive Customization Options**:
  - Custom PDF header and footer
  - Configurable page size and orientation
  - Font selection and sizing
  - Custom CSS styling support
  - Margin control
- **Professional PDF Layout**:
  - Preserves images and formatting
  - Supports custom post types
  - Maintains hyperlinks
- **Access Control**:
  - Role-based PDF generation permissions
  - Restrict PDF access to specific user roles
- **Multilingual Support**:
  - Fully translatable
  - RTL language support
- **Developer Friendly**:
  - Extensible architecture
  - Action and filter hooks
  - Well-documented code

## Requirements

- WordPress 5.2 or higher
- PHP 7.2 or higher
- Modern web browser

## Installation

1. Download the plugin zip file
2. Go to WordPress admin panel > Plugins > Add New
3. Click "Upload Plugin" and choose the downloaded zip file
4. Click "Install Now" and then "Activate"

## Configuration

1. Navigate to Settings > WP Post To PDF in your WordPress admin panel
2. Configure the following settings:
   - PDF Layout Options
   - Header & Footer Content
   - Font Settings
   - Access Permissions
   - Button Display Options
3. Save your changes

## Usage

### Basic Usage
- A "Download PDF" button will automatically appear on your posts/pages (location configurable in settings)
- Click the button to generate and download the PDF version of the content

### Shortcode
Add the PDF download button anywhere using the shortcode:
```
[wp_post_to_pdf]
```

### PHP Function
Developers can programmatically generate PDFs using:
```php
<?php
if (function_exists('wp_post_to_pdf_generate')) {
    wp_post_to_pdf_generate($post_id);
}
?>

## Support

- For bug reports and feature requests, please use the [GitHub Issues](https://github.com/Pimzino/wp-post-to-pdf/issues)
- For general questions, visit our [Support Forum](https://wordpress.org/support/plugin/wp-post-to-pdf/)
- Check our [Documentation](https://github.com/Pimzino/wp-post-to-pdf/wiki) for detailed guides

## Contributing

We welcome contributions! Please see our [Contributing Guidelines](CONTRIBUTING.md) for details on how to submit pull requests, report issues, and contribute to the project.

## License

This plugin is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## Credits

Created and maintained by [Pimzino](https://x.com/pimzino).

### Libraries and Resources Used

- [DOMPDF](https://github.com/dompdf/dompdf) - PHP HTML to PDF converter library
- [Font Awesome](https://fontawesome.com/) - Icons used in the PDF download button
- [Google Fonts](https://fonts.google.com/) - For additional font options in PDFs

## Changelog

### 1.0.0
- Initial release
- Core PDF generation functionality
- Customizable settings panel
- Role-based access control
- Multilingual support
