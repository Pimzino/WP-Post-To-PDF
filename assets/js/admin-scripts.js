(function($) {
    'use strict';

    // Declare updatePreview at a scope accessible to all functions
    let updatePreview;

    $(document).ready(function() {
        // Initialize preview updates
        initPreviewUpdates();

        // Reset functionality
        initResetFunctionality();

        // Initialize icon select
        initIconSelect();
    });

    /**
     * Initialize real-time preview updates
     */
    function initPreviewUpdates() {
        const previewButton = $('.preview-button-wrapper .pdf-download-button');
        const buttonText = $('#button_text');
        const buttonFont = $('#button_font');
        const buttonIcon = $('#button_icon');
        const colorPicker = $('#wp_post_to_pdf_button_bg_color');
        const hoverColorPicker = $('#button_bg_color_hover');
        const fontColorPicker = $('#button_font_color');
        const fontColorHoverPicker = $('#button_font_color_hover');
        const hoverEffectSwitch = $('#button_hover_effect');
        const fontSizeSlider = $('#button_font_size');
        const fontSizeValue = fontSizeSlider.closest('.range-slider-wrapper').find('.font-size-value');
        const buttonSizeSelect = $('#button_size');

        updatePreview = function() {
            const settings = {
                text: buttonText.val(),
                font: buttonFont.val(),
                icon: buttonIcon.val(),
                fontSize: fontSizeSlider.val(),
                buttonSize: buttonSizeSelect.val(),
                backgroundColor: colorPicker.val(),
                backgroundColorHover: hoverColorPicker.val(),
                fontColor: fontColorPicker.val(),
                fontColorHover: fontColorHoverPicker.val(),
                hoverEffect: hoverEffectSwitch.is(':checked')
            };

            // Update font size value display
            fontSizeValue.text(settings.fontSize + 'px');

            // Update text content
            previewButton.find('.button-text').text(settings.text);

            // Update all button styles at once using CSS variables
            previewButton.css({
                'font-family': `'${settings.font}'`,
                'font-weight': '700',
                '--button-font-size': `${settings.fontSize}px`,
                '--button-bg-color': settings.backgroundColor,
                '--button-bg-color-hover': settings.backgroundColorHover,
                '--button-font-color': settings.fontColor,
                '--button-font-color-hover': settings.fontColorHover
            });

            // Update button size class
            previewButton.removeClass('size-small size-medium size-large').addClass(`size-${settings.buttonSize}`);

            // Update icon
            const iconElement = previewButton.find('i');
            if (settings.icon === 'none') {
                iconElement.hide();
            } else {
                iconElement
                    .show()
                    .attr('class', `fa-solid ${settings.icon}`);
            }

            // Update hover effect
            if (settings.hoverEffect) {
                previewButton.addClass('hover-effect');
            } else {
                previewButton.removeClass('hover-effect');
            }
        };

        // Bind update preview to all input changes
        buttonText.on('input', updatePreview);
        buttonFont.on('change', updatePreview);
        buttonIcon.on('change', updatePreview);
        colorPicker.on('input', updatePreview);
        hoverColorPicker.on('input', updatePreview);
        fontColorPicker.on('input', updatePreview);
        fontColorHoverPicker.on('input', updatePreview);
        hoverEffectSwitch.on('change', updatePreview);
        fontSizeSlider.on('input', updatePreview);
        buttonSizeSelect.on('change', updatePreview);

        // Initial preview update
        updatePreview();
    }

    /**
     * Initialize reset functionality
     */
    function initResetFunctionality() {
        const resetButton = $('#reset-settings');
        const form = $('#wp-post-to-pdf-settings-form');

        resetButton.on('click', function(e) {
            e.preventDefault();

            if (confirm('Are you sure you want to reset all settings to default?')) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'reset_pdf_settings',
                        _wpnonce: $('#_wpnonce').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update form fields with default values
                            $.each(response.data.settings, function(key, value) {
                                let field = $(`[name="wp_post_to_pdf_settings[${key}]"]`);
                                if (field.length) {
                                    if (field.is(':checkbox')) {
                                        field.prop('checked', value);
                                    } else {
                                        field.val(value);
                                    }
                                }
                            });

                            // Trigger change event to update preview
                            form.find('input, select').trigger('change');

                            // Show success message
                            showNotice('success', response.data.message);
                        } else {
                            showNotice('error', response.data.message);
                        }
                    }
                });
            }
        });
    }

    /**
     * Show notification
     */
    function showNotice(type, message) {
        const notice = $(`<div class="notice notice-${type} is-dismissible"><p>${message}</p></div>`);
        $('.wrap h1').after(notice);
    }

    /**
     * Adjust color brightness
     */
    function adjustColor(color, amount) {
        return '#' + color.replace(/^#/, '').replace(/../g, color => 
            ('0' + Math.min(255, Math.max(0, parseInt(color, 16) + amount)).toString(16)).slice(-2)
        );
    }

    /**
     * Initialize icon select
     */
    function initIconSelect() {
        const iconSelect = $('.icon-select-wrapper');
        
        // Toggle dropdown
        iconSelect.on('click', '.selected-option', function(e) {
            const wrapper = $(this).closest('.icon-select-wrapper');
            wrapper.toggleClass('active');
            e.stopPropagation();
        });

        // Select option
        iconSelect.on('click', '.option', function() {
            const option = $(this);
            const wrapper = option.closest('.icon-select-wrapper');
            const value = option.data('value');
            const html = option.html();
            
            // Update hidden input
            wrapper.find('input[type="hidden"]').val(value).trigger('change');
            
            // Update selected option display
            wrapper.find('.selected-option').html(html);
            
            // Update selected state
            wrapper.find('.option').removeClass('selected');
            option.addClass('selected');
            
            // Close dropdown
            wrapper.removeClass('active');
        });

        // Close dropdown when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.icon-select-wrapper').length) {
                $('.icon-select-wrapper').removeClass('active');
            }
        });
    }

})(jQuery);
