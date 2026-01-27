/**
 * MindfulMedia Admin JavaScript
 * Handles live media source detection in the admin
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        /**
         * Move all WordPress admin notices to before the branding header
         * This ensures notices appear above the header, not between header and content
         */
        function moveNoticesToTop() {
            // Find all notices - both those inside wrap divs AND those rendered by WordPress before the wrap
            var $allNotices = $('#wpbody-content > .notice, #wpbody-content > .updated, #wpbody-content > .error, #wpbody-content > .fs-notice, ' +
                              '.wrap .notice, .wrap .updated, .wrap .error')
                              .not('.inline, .notice-positioned');
            
            if ($allNotices.length) {
                // Find the branding header
                var $header = $('.mindful-media-branding-header');
                
                if ($header.length) {
                    // Move notices before the branding header (outside and above the white background)
                    $allNotices.each(function() {
                        var $notice = $(this);
                        $notice.insertBefore($header);
                        $notice.addClass('notice-positioned');
                    });
                }
            }
        }
        
        // Run notice repositioning on page load
        moveNoticesToTop();
        
        // Also run after a short delay in case notices are added by other scripts
        setTimeout(moveNoticesToTop, 100);
        setTimeout(moveNoticesToTop, 500);
        
        // Watch for new notices being added dynamically
        var noticesObserver = new MutationObserver(function(mutations) {
            moveNoticesToTop();
        });
        
        // Observe the wpbody-content for new notices
        var wpbodyContent = document.getElementById('wpbody-content');
        if (wpbodyContent) {
            noticesObserver.observe(wpbodyContent, {
                childList: true,
                subtree: true
            });
        }
        
        // Media URL field auto-detection
        var $mediaUrlField = $('#mindful_media_url');
        var $mediaSourceSelect = $('#mindful_media_source');
        var $detectionMessage = null;
        
        if ($mediaUrlField.length && $mediaSourceSelect.length) {
            
            // Create detection message container if it doesn't exist
            if (!$('#mindful-media-detection-message').length) {
                $mediaUrlField.closest('td').find('p.description').first().after(
                    '<p id="mindful-media-detection-message" class="description" style="display:none; color: #46b450; font-weight: 600;"></p>'
                );
            }
            $detectionMessage = $('#mindful-media-detection-message');
            
            // Function to detect media source from URL
            function detectMediaSource(url) {
                if (!url) {
                    return 'none';
                }
                
                // YouTube detection
                if (/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i.test(url)) {
                    return 'youtube';
                }
                
                // Vimeo detection
                if (/vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|)(\d+)(?:$|\/|\?)/i.test(url)) {
                    return 'vimeo';
                }
                
                // SoundCloud detection
                if (url.indexOf('soundcloud.com') !== -1) {
                    return 'soundcloud';
                }
                
                // Archive.org detection
                if (url.indexOf('archive.org') !== -1) {
                    return 'archive';
                }
                
                // Check for direct media file extensions
                var extension = url.split('.').pop().split('?')[0].toLowerCase();
                if (['mp4', 'webm', 'ogg', 'ogv'].indexOf(extension) !== -1) {
                    return 'video';
                }
                if (['mp3', 'wav', 'ogg', 'oga', 'm4a'].indexOf(extension) !== -1) {
                    return 'audio';
                }
                
                return 'unknown';
            }
            
            // Function to get source label
            function getSourceLabel(source) {
                var labels = {
                    'youtube': 'YouTube',
                    'vimeo': 'Vimeo',
                    'soundcloud': 'SoundCloud',
                    'archive': 'Archive.org',
                    'video': 'Video File',
                    'audio': 'Audio File',
                    'unknown': 'Unknown',
                    'none': 'None'
                };
                return labels[source] || 'Unknown';
            }
            
            // Function to auto-fill CTA text
            function autoFillCTA(source) {
                var $ctaField = $('#mindful_media_cta_text');
                if (!$ctaField.length) return;
                
                var ctaText = 'View';
                if (['soundcloud', 'audio'].indexOf(source) !== -1) {
                    ctaText = 'Listen';
                } else if (['youtube', 'vimeo', 'video', 'archive'].indexOf(source) !== -1) {
                    ctaText = 'Watch';
                }
                
                // Update field and add visual indicator
                $ctaField.val(ctaText).css('border-color', '#46b450');
                setTimeout(function() {
                    $ctaField.css('border-color', '');
                }, 1000);
            }
            
            // Function to update detection display
            function updateDetection() {
                var url = $mediaUrlField.val();
                var source = detectMediaSource(url);
                
                if (source && source !== 'unknown' && source !== 'none') {
                    $detectionMessage.html('✓ Detected source: ' + getSourceLabel(source)).show();
                    
                    // Auto-select in dropdown if it's set to auto-detect
                    if ($mediaSourceSelect.val() === '') {
                        $mediaSourceSelect.val(source);
                    }
                    
                    // Auto-fill CTA text
                    autoFillCTA(source);
                } else if (url) {
                    $detectionMessage.html('⚠ Could not detect media source. Please select manually.').css('color', '#dc3232').show();
                } else {
                    $detectionMessage.hide();
                }
            }
            
            // Detect on page load
            updateDetection();
            
            // Detect on URL field change
            $mediaUrlField.on('input change blur', function() {
                updateDetection();
            });
            
            // If user manually selects source, respect their choice
            $mediaSourceSelect.on('change', function() {
                if ($(this).val()) {
                    var selectedLabel = $(this).find('option:selected').text();
                    $detectionMessage.html('✓ Source set to: ' + selectedLabel).css('color', '#46b450').show();
                    
                    // Auto-fill CTA when source changes
                    autoFillCTA($(this).val());
                }
                
                // Show/hide custom embed field
                if ($(this).val() === 'custom_embed') {
                    $('#mindful-media-custom-embed-row').show();
                } else {
                    $('#mindful-media-custom-embed-row').hide();
                }
            });
        }
        
        // Media Preview Button
        $('.mindful-media-preview-btn').on('click', function(e) {
            e.preventDefault();
            
            var mediaUrl = $('#mindful_media_url').val();
            var customEmbed = $('#mindful_media_custom_embed').val();
            var mediaSource = $('#mindful_media_source').val();
            
            if (!mediaUrl && !customEmbed) {
                alert('Please enter a media URL or custom embed code first.');
                return;
            }
            
            // Create modal for preview
            var modalHtml = '<div id="mindful-media-preview-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 100000; display: flex; align-items: center; justify-content: center;">' +
                '<div style="background: white; padding: 20px; border-radius: 8px; max-width: 800px; width: 90%; max-height: 90%; overflow: auto; position: relative; z-index: 100001;">' +
                '<button id="close-preview-modal" style="position: absolute; top: 10px; right: 10px; background: #dc3232; color: white; border: none; padding: 8px 12px; border-radius: 3px; cursor: pointer; font-size: 20px; font-weight: bold; line-height: 1; z-index: 100002; min-width: 36px; min-height: 36px; display: flex; align-items: center; justify-content: center;">×</button>' +
                '<h2 style="margin-top: 0; position: relative; z-index: 100002;">Media Preview</h2>' +
                '<div id="mindful-media-preview-content" style="margin-top: 20px; position: relative; z-index: 1;"></div>' +
                '</div>' +
                '</div>';
            
            $('body').append(modalHtml);
            
            // Load preview content
            var previewContent = '';
            
            if (mediaSource === 'custom_embed' && customEmbed) {
                previewContent = customEmbed;
            } else if (mediaUrl) {
                // Use AJAX to get the rendered player
                $.post(ajaxurl, {
                    action: 'mindful_media_preview',
                    url: mediaUrl,
                    source: mediaSource
                }, function(response) {
                    if (response.success) {
                        $('#mindful-media-preview-content').html(response.data);
                    } else {
                        $('#mindful-media-preview-content').html('<p style="color: #dc3232;">Could not load preview. ' + (response.data || '') + '</p>');
                    }
                });
                previewContent = '<p>Loading preview...</p>';
            }
            
            $('#mindful-media-preview-content').html(previewContent);
            
            // Close modal
            $('#close-preview-modal').on('click', function(e) {
                e.preventDefault();
                $('#mindful-media-preview-modal').remove();
            });
            
            // Close modal when clicking on backdrop
            $('#mindful-media-preview-modal').on('click', function(e) {
                if (e.target === this) {
                    $('#mindful-media-preview-modal').remove();
                }
            });
        });
        
        // Fetch Duration Button
        $('#mindful-media-fetch-duration').on('click', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var $status = $('#mindful-media-duration-status');
            var mediaUrl = $('#mindful_media_url').val();
            
            if (!mediaUrl) {
                $status.html('<span style="color: #dc3232;">Please enter a media URL first.</span>');
                return;
            }
            
            $btn.prop('disabled', true);
            $status.html('<span style="color: #666;">Fetching duration...</span>');
            
            $.post(ajaxurl, {
                action: 'mindful_media_fetch_duration',
                nonce: mindfulMediaAdmin.nonce,
                url: mediaUrl
            }, function(response) {
                $btn.prop('disabled', false);
                
                if (response.success) {
                    $('#mindful_media_duration_hours').val(response.data.hours);
                    $('#mindful_media_duration_minutes').val(response.data.minutes);
                    $status.html('<span style="color: #46b450;">✓ ' + response.data.message + '</span>');
                    
                    // Flash the fields
                    $('#mindful_media_duration_hours, #mindful_media_duration_minutes')
                        .css('border-color', '#46b450');
                    setTimeout(function() {
                        $('#mindful_media_duration_hours, #mindful_media_duration_minutes')
                            .css('border-color', '');
                    }, 1500);
                } else {
                    $status.html('<span style="color: #dc3232;">' + response.data + '</span>');
                }
            }).fail(function() {
                $btn.prop('disabled', false);
                $status.html('<span style="color: #dc3232;">Failed to fetch duration. Please enter manually.</span>');
            });
        });
        
        // WordPress Media Library Uploader
        var mediaUploader;
        
        $('.mindful-media-upload-btn').on('click', function(e) {
            e.preventDefault();
            
            // If the uploader object has already been created, reopen the dialog
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            // Create the media uploader
            mediaUploader = wp.media({
                title: 'Select or Upload Media',
                button: {
                    text: 'Use this media'
                },
                library: {
                    type: ['audio', 'video'] // Only show audio and video files
                },
                multiple: false
            });
            
            // When a file is selected
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                
                // Set the URL in the media URL field
                $('#mindful_media_url').val(attachment.url).trigger('change');
                
                // Auto-detect and set the media source
                var fileType = attachment.type; // 'audio' or 'video'
                if (fileType === 'video') {
                    $('#mindful_media_source').val('video');
                } else if (fileType === 'audio') {
                    $('#mindful_media_source').val('audio');
                }
                
                // Show success message
                if ($detectionMessage) {
                    $detectionMessage.html('✓ Media file selected: ' + attachment.filename).css('color', '#46b450').show();
                }
            });
            
            // Open the uploader
            mediaUploader.open();
        });
        
        // Color Preset Selection
        var $colorPreset = $('#mindful-media-color-preset');
        var $primaryColor = $('#primary_color');
        var $secondaryColor = $('#secondary_color');
        var $textColorLight = $('#text_color_light');
        var $textColorDark = $('#text_color_dark');
        
        if ($colorPreset.length && $primaryColor.length && $secondaryColor.length) {
            $colorPreset.on('change', function() {
                var $selected = $(this).find('option:selected');
                var primaryColor = $selected.data('primary');
                var secondaryColor = $selected.data('secondary');
                
                if (primaryColor && secondaryColor) {
                    $primaryColor.val(primaryColor);
                    $secondaryColor.val(secondaryColor);
                    
                    // Set appropriate text colors based on preset
                    if ($textColorLight.length) {
                        $textColorLight.val('#FFFFFF'); // Always white for dark backgrounds
                    }
                    if ($textColorDark.length) {
                        $textColorDark.val('#333333'); // Always dark for light backgrounds
                    }
                    
                    // Visual feedback
                    var $allColorInputs = $primaryColor.add($secondaryColor).add($textColorLight).add($textColorDark);
                    $allColorInputs.css('border', '2px solid #46b450');
                    
                    setTimeout(function() {
                        $allColorInputs.css('border', '');
                    }, 1000);
                }
            });
        }
        
        // Settings Tabs
        $(document).on('click', '.mindful-media-settings-tabs .nav-tab', function(e) {
            e.preventDefault();
            
            var targetTab = $(this).data('tab');
            
            // Remove active class from all tabs and content
            $('.nav-tab').removeClass('nav-tab-active');
            $('.mindful-media-tab-content').removeClass('active');
            
            // Add active class to clicked tab and corresponding content
            $(this).addClass('nav-tab-active');
            $('#' + targetTab + '-tab').addClass('active');
            
            // Update hidden field to preserve tab after form submission
            $('#active_tab_field').val(targetTab);
        });
        
        // Font Size Sliders
        $('#font_size_title, #font_size_teacher, #font_size_content, #font_size_filter_heading, #font_size_filter_options, #font_size_filter_buttons, #font_size_single_title, #font_size_single_content, #font_size_single_meta').on('input', function() {
            var sliderId = $(this).attr('id');
            var displayId = sliderId + '_display';
            $('#' + displayId).text($(this).val() + 'px');
        });
        
        // Font Family Preview
        var $fontSelect = $('#mindful-media-font-family');
        var $fontPreview = $('#mindful-media-font-preview');
        
        if ($fontSelect.length && $fontPreview.length) {
            
            // Google Fonts to load dynamically
            var googleFonts = [
                'Lora', 'Merriweather', 'Playfair Display', 'Crimson Text', 'EB Garamond',
                'Open Sans', 'Roboto', 'Lato', 'Montserrat', 'Nunito', 'Source Sans Pro',
                'Raleway', 'Inter', 'Cormorant Garamond', 'Libre Baskerville', 'Spectral',
                'Noto Serif'
            ];
            
            // Function to load Google Font dynamically
            function loadGoogleFont(fontName) {
                // Check if font is already loaded
                var linkId = 'mindful-media-font-preview-' + fontName.replace(/\s+/g, '-').toLowerCase();
                if ($('#' + linkId).length) {
                    return; // Already loaded
                }
                
                // Create link element to load font
                var fontUrl = 'https://fonts.googleapis.com/css2?family=' + fontName.replace(/\s+/g, '+') + ':ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap';
                $('<link>')
                    .attr('id', linkId)
                    .attr('rel', 'stylesheet')
                    .attr('href', fontUrl)
                    .appendTo('head');
            }
            
            // Function to update preview
            function updateFontPreview() {
                var selectedFont = $fontSelect.val();
                
                if (selectedFont === 'theme_default') {
                    $fontPreview.hide();
                    return;
                }
                
                // Check if it's a Google Font
                if (googleFonts.indexOf(selectedFont) !== -1) {
                    loadGoogleFont(selectedFont);
                    $fontPreview.css('font-family', "'" + selectedFont + "', sans-serif").show();
                } else if (selectedFont.indexOf(',') !== -1) {
                    // System font with fallbacks
                    $fontPreview.css('font-family', selectedFont).show();
                } else {
                    $fontPreview.hide();
                }
            }
            
            // Update preview on font selection change
            $fontSelect.on('change', function() {
                updateFontPreview();
            });
            
            // Update preview on page load
            updateFontPreview();
        }
        
        // Reset Email Body Template
        $('#mm-reset-email-body').on('click', function(e) {
            e.preventDefault();
            if (confirm('Reset email body to default template?')) {
                var defaultTemplate = 'Hi {user_name},\n\nNew content is available from <strong>{term_name}</strong>:\n\n<div style="background: #f5f5f5; padding: 15px; border-radius: 6px; margin: 20px 0;">\n<strong>{post_title}</strong>\n<p style="margin: 8px 0 0; color: #666;">{post_excerpt}</p>\n</div>\n\n<a href="{post_url}" style="display: inline-block; background: {button_color}; color: {button_text_color}; padding: 12px 24px; border-radius: 4px; text-decoration: none; font-weight: 600;">Watch Now</a>';
                $('textarea[name="email_body_template"]').val(defaultTemplate);
            }
        });
        
        // Email Logo Upload
        var emailLogoUploader;
        $('#email-logo-upload-btn').on('click', function(e) {
            e.preventDefault();
            
            if (emailLogoUploader) {
                emailLogoUploader.open();
                return;
            }
            
            emailLogoUploader = wp.media({
                title: 'Select Email Logo',
                button: { text: 'Use this image' },
                library: { type: 'image' },
                multiple: false
            });
            
            emailLogoUploader.on('select', function() {
                var attachment = emailLogoUploader.state().get('selection').first().toJSON();
                $('#email_logo_id').val(attachment.id);
                $('#email-logo-preview').show().find('img').attr('src', attachment.url);
                $('#email-logo-remove-btn').show();
                
                // Update the email preview
                $('#mm-email-preview-logo').attr('src', attachment.url).show();
                $('#mm-email-preview-text').hide();
            });
            
            emailLogoUploader.open();
        });
        
        $('#email-logo-remove-btn').on('click', function(e) {
            e.preventDefault();
            $('#email_logo_id').val('');
            $('#email-logo-preview').hide();
            $(this).hide();
            
            // Update the email preview - show text instead
            $('#mm-email-preview-logo').hide();
            $('#mm-email-preview-text').show();
        });
        
        // Send Test Email
        $('#mm-send-test-email').on('click', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var $spinner = $('#mm-test-email-spinner');
            var $result = $('#mm-test-email-result');
            var email = $('#mm-test-email-address').val();
            
            if (!email) {
                $result.html('<p style="color: #d63638; margin: 0;">Please enter an email address.</p>');
                return;
            }
            
            // Show loading state
            $btn.prop('disabled', true).text(mindfulMediaAdmin.strings.sending);
            $spinner.addClass('is-active');
            $result.html('');
            
            $.post(mindfulMediaAdmin.ajaxUrl, {
                action: 'mindful_media_send_test_email',
                nonce: mindfulMediaAdmin.nonce,
                email: email
            }, function(response) {
                $btn.prop('disabled', false).text(mindfulMediaAdmin.strings.send_test);
                $spinner.removeClass('is-active');
                
                if (response.success) {
                    $result.html('<p style="color: #00a32a; margin: 0; padding: 10px; background: #d1f7d1; border-radius: 4px;">✓ ' + response.data.message + '</p>');
                } else {
                    $result.html('<p style="color: #d63638; margin: 0; padding: 10px; background: #ffd1d1; border-radius: 4px;">✗ ' + response.data.message + '</p>');
                }
            }).fail(function() {
                $btn.prop('disabled', false).text(mindfulMediaAdmin.strings.send_test);
                $spinner.removeClass('is-active');
                $result.html('<p style="color: #d63638; margin: 0; padding: 10px; background: #ffd1d1; border-radius: 4px;">✗ ' + mindfulMediaAdmin.strings.error + '</p>');
            });
        });
        
        // Email Preview Color Updates (live preview)
        // Header text live update
        $('input[name="email_header_text"]').on('input change', function() {
            $('#mm-email-preview-text').text($(this).val());
        });
        
        $('input[name="email_header_bg"]').on('input change', function() {
            $('#mm-email-preview-header').css('background-color', $(this).val());
            $('input[name="email_header_bg_text"]').val($(this).val());
        });
        $('input[name="email_header_bg_text"]').on('input change', function() {
            var color = $(this).val();
            if (/^#[0-9A-Fa-f]{6}$/.test(color)) {
                $('input[name="email_header_bg"]').val(color);
                $('#mm-email-preview-header').css('background-color', color);
            }
        });
        
        $('input[name="email_header_text_color"]').on('input change', function() {
            $('#mm-email-preview-header').css('color', $(this).val());
            $('input[name="email_header_text_color_text"]').val($(this).val());
        });
        $('input[name="email_header_text_color_text"]').on('input change', function() {
            var color = $(this).val();
            if (/^#[0-9A-Fa-f]{6}$/.test(color)) {
                $('input[name="email_header_text_color"]').val(color);
                $('#mm-email-preview-header').css('color', color);
            }
        });
        
        $('input[name="email_button_bg"]').on('input change', function() {
            $('#mm-email-preview-button').css('background-color', $(this).val());
            $('input[name="email_button_bg_text"]').val($(this).val());
        });
        $('input[name="email_button_bg_text"]').on('input change', function() {
            var color = $(this).val();
            if (/^#[0-9A-Fa-f]{6}$/.test(color)) {
                $('input[name="email_button_bg"]').val(color);
                $('#mm-email-preview-button').css('background-color', color);
            }
        });
        
        $('input[name="email_button_text_color"]').on('input change', function() {
            $('#mm-email-preview-button').css('color', $(this).val());
            $('input[name="email_button_text_color_text"]').val($(this).val());
        });
        $('input[name="email_button_text_color_text"]').on('input change', function() {
            var color = $(this).val();
            if (/^#[0-9A-Fa-f]{6}$/.test(color)) {
                $('input[name="email_button_text_color"]').val(color);
                $('#mm-email-preview-button').css('color', color);
            }
        });
        
    });
    
})(jQuery);

