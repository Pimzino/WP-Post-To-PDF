(function($) {
    'use strict';

    $(document).ready(function() {
        const pdfButtons = $('.pdf-download-button');

        // Wrap icon and text in a wrapper div if not already wrapped
        pdfButtons.each(function() {
            const button = $(this);
            const icon = button.find('i');
            const text = button.find('.button-text');
            
            if (!button.find('.button-wrapper').length) {
                icon.add(text).wrapAll('<div class="button-wrapper"></div>');
            }
        });

        // Handle button click
        pdfButtons.on('click', function(e) {
            e.preventDefault();
            const button = $(this);
            
            // Prevent multiple clicks
            if (button.hasClass('loading')) {
                return;
            }

            // Add loading state with the same styling as preview
            button.addClass('loading')
                 .css({
                     'background': button.css('--button-bg-color-hover'),
                     'transform': 'translateY(-2px)'
                 });
            const originalText = button.find('.button-text').text();
            button.find('.button-text').text(wpPostToPDF.i18n.generating);

            // Get post ID and nonce
            const postId = button.data('post-id');
            const nonce = button.data('nonce');

            // Make AJAX request to generate PDF
            $.ajax({
                url: wpPostToPDF.ajaxurl,
                type: 'POST',
                data: {
                    action: 'generate_post_pdf',
                    post_id: postId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Convert base64 to blob
                        const pdfContent = atob(response.data.pdf_content);
                        const pdfBlob = new Blob([new Uint8Array([...pdfContent].map(char => char.charCodeAt(0)))], {
                            type: 'application/pdf'
                        });

                        // Create download link
                        const downloadUrl = window.URL.createObjectURL(pdfBlob);
                        const link = document.createElement('a');
                        link.href = downloadUrl;
                        link.download = response.data.filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        window.URL.revokeObjectURL(downloadUrl);
                    } else {
                        alert(wpPostToPDF.i18n.error);
                    }
                },
                error: function(jqXHR) {
                    let errorMessage = wpPostToPDF.i18n.error;
                    if (jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message) {
                        errorMessage = jqXHR.responseJSON.data.message;
                    }
                    alert(errorMessage);
                },
                complete: function() {
                    // Remove loading state and restore original styles
                    button.removeClass('loading')
                          .css({
                              'background': '',
                              'transform': ''
                          });
                    button.find('.button-text').text(originalText);
                }
            });
        });
    });
})(jQuery);
