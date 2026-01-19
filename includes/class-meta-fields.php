<?php
/**
 * Meta Fields Class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MindfulMedia_Meta_Fields {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_fields'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_mindful_media_preview', array($this, 'ajax_preview_media'));
    }
    
    /**
     * AJAX handler for media preview
     */
    public function ajax_preview_media() {
        // Security check
        check_ajax_referer('mindful_media_ajax_nonce', 'nonce');
        
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : '';
        
        if (empty($url)) {
            wp_send_json_error('No media URL provided');
        }
        
        // Use the media player class to render
        if (class_exists('MindfulMedia_Players')) {
            $player = new MindfulMedia_Players();
            $preview_html = $player->render_player($url, array(
                'source' => $source,
                'is_preview' => true,
                'class' => 'preview-mode'
            ));
            wp_send_json_success($preview_html);
        } else {
            wp_send_json_error('Media player class not found');
        }
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'mindful_media_details',
            __('Media Details', 'mindful-media'),
            array($this, 'media_details_callback'),
            'mindful_media',
            'normal',
            'high'
        );
        
        add_meta_box(
            'mindful_media_custom_fields',
            __('Custom Fields', 'mindful-media'),
            array($this, 'custom_fields_callback'),
            'mindful_media',
            'normal',
            'high'
        );
        
        // Add image guidance box ABOVE the featured image box
        add_meta_box(
            'mindful_media_image_guidance',
            __('📐 Image Size Guidance', 'mindful-media'),
            array($this, 'image_guidance_callback'),
            'mindful_media',
            'side',
            'high' // High priority so it appears above featured image
        );
        
        // Add visibility and protection meta box
        add_meta_box(
            'mindful_media_visibility_protection',
            __('👁️ Visibility & Protection', 'mindful-media'),
            array($this, 'visibility_protection_callback'),
            'mindful_media',
            'side',
            'default'
        );
    }
    
    /**
     * Image guidance meta box (separate from featured image)
     */
    public function image_guidance_callback($post) {
        // Display guidance with simple text format
        echo '<div style="padding: 10px 12px; background: #f9f9f9; border-left: 4px solid #0073aa; margin-bottom: 12px;">';
        echo '<p style="margin: 0 0 10px 0; font-size: 14px; font-weight: 600; color: #333;">📐 Featured Image Guidelines</p>';
        echo '<p style="margin: 0 0 8px 0; font-size: 13px; color: #666; line-height: 1.5;">';
        echo '<strong>Recommended:</strong> 1920×1080 pixels (16:9 ratio)';
        echo '</p>';
        echo '<p style="margin: 0; font-size: 12px; color: #777; line-height: 1.5;">';
        echo 'All images are displayed in 16:9 containers. Images with different dimensions will be letterboxed (black borders) to prevent cropping.';
        echo '</p>';
        echo '</div>';
    }
    
    /**
     * Visibility and protection meta box callback
     */
    public function visibility_protection_callback($post) {
        wp_nonce_field('mindful_media_password_nonce', 'mindful_media_password_nonce_field');
        
        // Get saved values
        $is_protected = get_post_meta($post->ID, '_mindful_media_password_protected', true);
        $stored_password = get_post_meta($post->ID, '_mindful_media_password_hash', true);
        $is_hidden = get_post_meta($post->ID, '_mindful_media_hide_from_archive', true);
        
        ?>
        <div style="padding: 5px 0;">
            <!-- Visibility Section -->
            <p style="margin-bottom: 15px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" id="mindful_media_hide_from_archive" 
                           name="mindful_media_hide_from_archive" value="1" 
                           <?php checked($is_hidden, '1'); ?> 
                           style="margin: 0;" />
                    <strong><?php _e('Hide from Archive', 'mindful-media'); ?></strong>
                </label>
                <small style="display: block; margin-left: 28px; color: #666; margin-top: 4px;">
                    <?php _e('If enabled, this item will not appear in the main media archive grids.', 'mindful-media'); ?>
                </small>
            </p>

            <hr style="margin: 15px 0; border: none; border-top: 1px solid #eee;">
            
            <!-- Protection Section -->
            <p style="margin-top: 0; color: #666; font-size: 13px;">
                <?php _e('Restrict access to this content with a password.', 'mindful-media'); ?>
            </p>
            
            <p>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" id="mindful_media_password_protected" 
                           name="mindful_media_password_protected" value="1" 
                           <?php checked($is_protected, '1'); ?> 
                           style="margin: 0;" />
                    <strong><?php _e('Restrict with password', 'mindful-media'); ?></strong>
                </label>
            </p>
            
            <div id="password-fields" style="<?php echo $is_protected ? '' : 'display:none;'; ?>">
                <p>
                    <label for="mindful_media_password">
                        <strong><?php _e('Password:', 'mindful-media'); ?></strong>
                    </label><br>
                    <input type="password" id="mindful_media_password" 
                           name="mindful_media_password" 
                           placeholder="<?php _e('Enter password', 'mindful-media'); ?>" 
                           style="width: 100%; margin-top: 5px;" 
                           autocomplete="new-password" />
                    <?php if ($stored_password): ?>
                        <small style="color: #666; display: block; margin-top: 5px;">
                            <?php _e('Leave blank to keep current', 'mindful-media'); ?>
                        </small>
                    <?php endif; ?>
                </p>
                
                <p>
                    <label for="mindful_media_password_confirm">
                        <strong><?php _e('Confirm Password:', 'mindful-media'); ?></strong>
                    </label><br>
                    <input type="password" id="mindful_media_password_confirm" 
                           name="mindful_media_password_confirm" 
                           placeholder="<?php _e('Confirm password', 'mindful-media'); ?>" 
                           style="width: 100%; margin-top: 5px;" 
                           autocomplete="new-password" />
                </p>
                
                <div id="password-strength" style="margin-top: 10px; padding: 8px; border-radius: 4px; display: none;">
                    <small id="password-strength-text"></small>
                </div>
                
                <?php if ($stored_password): ?>
                    <p style="background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-top: 10px;">
                        <span style="color: #155724;">🔒 <?php _e('Password is set', 'mindful-media'); ?></span>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Toggle password fields
            $('#mindful_media_password_protected').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#password-fields').slideDown();
                } else {
                    $('#password-fields').slideUp();
                }
            });
            
            // Password strength indicator
            $('#mindful_media_password').on('input', function() {
                var password = $(this).val();
                var strength = 0;
                var strengthText = '';
                var strengthColor = '';
                
                if (password.length === 0) {
                    $('#password-strength').hide();
                    return;
                }
                
                // Calculate strength
                if (password.length >= 8) strength++;
                if (password.length >= 12) strength++;
                if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
                if (/\d/.test(password)) strength++;
                if (/[^a-zA-Z0-9]/.test(password)) strength++;
                
                // Set text and color
                if (strength <= 2) {
                    strengthText = '<?php _e('Weak', 'mindful-media'); ?>';
                    strengthColor = '#dc3545';
                } else if (strength <= 3) {
                    strengthText = '<?php _e('Medium', 'mindful-media'); ?>';
                    strengthColor = '#ffc107';
                } else {
                    strengthText = '<?php _e('Strong', 'mindful-media'); ?>';
                    strengthColor = '#28a745';
                }
                
                $('#password-strength').show().css('background-color', strengthColor + '22').css('border', '1px solid ' + strengthColor);
                $('#password-strength-text').text('<?php _e('Password Strength:', 'mindful-media'); ?> ' + strengthText).css('color', strengthColor);
            });
        });
        </script>
        <?php
    }
    
    /**
     * Media details meta box callback
     */
    public function media_details_callback($post) {
        wp_nonce_field('mindful_media_meta_nonce', 'mindful_media_meta_nonce_field');
        
        // Get saved values
        $duration_hours = get_post_meta($post->ID, '_mindful_media_duration_hours', true);
        $duration_minutes = get_post_meta($post->ID, '_mindful_media_duration_minutes', true);
        $teacher_name = get_post_meta($post->ID, '_mindful_media_teacher_name', true);
        $recording_date = get_post_meta($post->ID, '_mindful_media_recording_date', true);
        $media_url = get_post_meta($post->ID, '_mindful_media_url', true);
        $media_source = get_post_meta($post->ID, '_mindful_media_source', true);
        $is_featured = get_post_meta($post->ID, '_mindful_media_featured', true);
        $cta_button_text = get_post_meta($post->ID, '_mindful_media_cta_text', true);
        $external_link = get_post_meta($post->ID, '_mindful_media_external_link', true);
        
        // Detect source if not set
        if (empty($media_source) && !empty($media_url)) {
            // Load the media player class to detect
            if (class_exists('MindfulMedia_Players')) {
                $player = new MindfulMedia_Players();
                $media_source = $player->detect_media_source($media_url);
            }
        }
        
        // Auto-detect CTA text if empty
        if (empty($cta_button_text)) {
            $cta_button_text = $this->auto_detect_cta_text($post->ID, $media_source);
        }
        ?>
        <table class="form-table">
            <tr>
                <th><label for="mindful_media_url"><?php _e('Media URL', 'mindful-media'); ?></label></th>
                <td>
                    <input type="url" id="mindful_media_url" name="mindful_media_url" 
                           value="<?php echo esc_attr($media_url); ?>" style="width: calc(100% - 180px);" />
                    <button type="button" class="button mindful-media-upload-btn" style="margin-left: 10px;">
                        <?php _e('Upload Media', 'mindful-media'); ?>
                    </button>
                    <button type="button" class="button mindful-media-preview-btn" id="mindful-media-preview-btn" style="margin-left: 10px;">
                        <?php _e('Preview Media', 'mindful-media'); ?>
                    </button>
                    <p class="description">
                        <?php _e('Paste URL from YouTube, Vimeo, SoundCloud, Archive.org, or click "Upload Media" to select from library', 'mindful-media'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="mindful_media_source"><?php _e('Media Source', 'mindful-media'); ?></label></th>
                <td>
                    <select id="mindful_media_source" name="mindful_media_source" style="width: 200px;">
                        <option value=""><?php _e('Auto-detect', 'mindful-media'); ?></option>
                        <option value="youtube" <?php selected($media_source, 'youtube'); ?>><?php _e('YouTube', 'mindful-media'); ?></option>
                        <option value="vimeo" <?php selected($media_source, 'vimeo'); ?>><?php _e('Vimeo', 'mindful-media'); ?></option>
                        <option value="soundcloud" <?php selected($media_source, 'soundcloud'); ?>><?php _e('SoundCloud', 'mindful-media'); ?></option>
                        <option value="archive" <?php selected($media_source, 'archive'); ?>><?php _e('Archive.org', 'mindful-media'); ?></option>
                        <option value="video" <?php selected($media_source, 'video'); ?>><?php _e('Video File', 'mindful-media'); ?></option>
                        <option value="audio" <?php selected($media_source, 'audio'); ?>><?php _e('Audio File', 'mindful-media'); ?></option>
                        <option value="custom_embed" <?php selected($media_source, 'custom_embed'); ?>><?php _e('Custom Embed Code', 'mindful-media'); ?></option>
                    </select>
                    <p class="description"><?php _e('Override auto-detection if needed', 'mindful-media'); ?></p>
                </td>
            </tr>
            <tr id="mindful-media-custom-embed-row" style="<?php echo ($media_source === 'custom_embed') ? '' : 'display:none;'; ?>">
                <th><label for="mindful_media_custom_embed"><?php _e('Custom Embed Code', 'mindful-media'); ?></label></th>
                <td>
                    <?php $custom_embed = get_post_meta($post->ID, '_mindful_media_custom_embed', true); ?>
                    <textarea id="mindful_media_custom_embed" name="mindful_media_custom_embed" rows="6" style="width: 100%; font-family: monospace;"><?php echo esc_textarea($custom_embed); ?></textarea>
                    <p class="description"><?php _e('Paste iframe or embed code from platforms not auto-detected. This will be used instead of the Media URL.', 'mindful-media'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="mindful_media_duration_hours"><?php _e('Duration', 'mindful-media'); ?></label></th>
                <td>
                    <input type="number" id="mindful_media_duration_hours" name="mindful_media_duration_hours" 
                           value="<?php echo esc_attr($duration_hours); ?>" min="0" max="23" style="width: 60px;" />
                    <label for="mindful_media_duration_hours"><?php _e('hours', 'mindful-media'); ?></label>
                    
                    <input type="number" id="mindful_media_duration_minutes" name="mindful_media_duration_minutes" 
                           value="<?php echo esc_attr($duration_minutes); ?>" min="0" max="59" style="width: 60px;" />
                    <label for="mindful_media_duration_minutes"><?php _e('minutes', 'mindful-media'); ?></label>
                </td>
            </tr>
            <tr>
                <th><label for="mindful_media_recording_date"><?php _e('Recording Date', 'mindful-media'); ?></label></th>
                <td>
                    <input type="date" id="mindful_media_recording_date" name="mindful_media_recording_date" 
                           value="<?php echo esc_attr($recording_date); ?>" />
                </td>
            </tr>
            <tr>
                <th><label for="mindful_media_external_link"><?php _e('External Link', 'mindful-media'); ?></label></th>
                <td>
                    <input type="url" id="mindful_media_external_link" name="mindful_media_external_link" 
                           value="<?php echo esc_attr($external_link); ?>" style="width: 100%;" />
                    <p class="description"><?php _e('Optional: Link to external page (overrides single post view)', 'mindful-media'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="mindful_media_cta_text"><?php _e('Call-to-Action Button Text', 'mindful-media'); ?></label></th>
                <td>
                    <input type="text" id="mindful_media_cta_text" name="mindful_media_cta_text" 
                           value="<?php echo esc_attr($cta_button_text); ?>" style="width: 200px;" placeholder="Auto-detects from media type" />
                    <p class="description"><?php _e('Auto-fills based on media type. Audio = "Listen", Video = "Watch". Leave blank for auto-detect or type custom text.', 'mindful-media'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="mindful_media_featured"><?php _e('Featured Content', 'mindful-media'); ?></label></th>
                <td>
                    <input type="checkbox" id="mindful_media_featured" name="mindful_media_featured" value="1" 
                           <?php checked($is_featured, '1'); ?> />
                    <label for="mindful_media_featured"><?php _e('Mark as featured content for hero section', 'mindful-media'); ?></label>
                </td>
            </tr>
            <tr>
                <th><label for="mindful_media_series_order"><?php _e('Playlist Order', 'mindful-media'); ?></label></th>
                <td>
                    <?php $series_order = get_post_meta($post->ID, '_mindful_media_series_order', true); ?>
                    <input type="number" id="mindful_media_series_order" name="mindful_media_series_order" 
                           value="<?php echo esc_attr($series_order); ?>" min="1" max="999" style="width: 80px;" />
                    <p class="description"><?php _e('If this item is part of a playlist, enter its order number (e.g., 1, 2, 3...). Select the playlist in the Playlists taxonomy box on the right.', 'mindful-media'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Custom fields meta box callback
     */
    public function custom_fields_callback($post) {
        $settings = get_option('mindful_media_settings', array());
        $custom_fields = isset($settings['custom_fields']) ? $settings['custom_fields'] : array();
        $saved_custom_data = get_post_meta($post->ID, '_mindful_media_custom_fields', true);
        
        if (empty($saved_custom_data)) {
            $saved_custom_data = array();
        }
        ?>
        <div id="mindful-media-custom-fields">
            <?php if (!empty($custom_fields)): ?>
                <table class="form-table">
                    <?php foreach ($custom_fields as $field_key => $field_config): ?>
                        <tr>
                            <th><label for="custom_field_<?php echo esc_attr($field_key); ?>">
                                <?php echo esc_html($field_config['label']); ?>
                            </label></th>
                            <td>
                                <?php $this->render_custom_field($field_key, $field_config, $saved_custom_data); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p><?php _e('No custom fields configured. You can add custom fields in the plugin settings.', 'mindful-media'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render custom field based on type
     */
    private function render_custom_field($field_key, $field_config, $saved_data) {
        $value = isset($saved_data[$field_key]) ? $saved_data[$field_key] : '';
        $field_name = "mindful_media_custom_fields[{$field_key}]";
        
        switch ($field_config['type']) {
            case 'text':
                echo '<input type="text" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '" style="width: 100%;" />';
                break;
                
            case 'textarea':
                echo '<textarea name="' . esc_attr($field_name) . '" rows="4" style="width: 100%;">' . esc_textarea($value) . '</textarea>';
                break;
                
            case 'select':
                echo '<select name="' . esc_attr($field_name) . '">';
                echo '<option value="">' . __('Select...', 'mindful-media') . '</option>';
                if (isset($field_config['options'])) {
                    foreach ($field_config['options'] as $option_value => $option_label) {
                        echo '<option value="' . esc_attr($option_value) . '" ' . selected($value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
                    }
                }
                echo '</select>';
                break;
                
            case 'checkbox':
                echo '<input type="checkbox" name="' . esc_attr($field_name) . '" value="1" ' . checked($value, '1', false) . ' />';
                break;
                
            case 'url':
                echo '<input type="url" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '" style="width: 100%;" />';
                break;
                
            case 'date':
                echo '<input type="date" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '" />';
                break;
                
            default:
                echo '<input type="text" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '" style="width: 100%;" />';
                break;
        }
        
        if (isset($field_config['description'])) {
            echo '<p class="description">' . esc_html($field_config['description']) . '</p>';
        }
    }
    
    /**
     * Save meta fields
     */
    public function save_meta_fields($post_id) {
        // Check for autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check user permissions
        if (isset($_POST['post_type']) && 'mindful_media' == $_POST['post_type']) {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }
        
        // Save main meta fields if nonce is valid
        if (isset($_POST['mindful_media_meta_nonce_field']) && 
            wp_verify_nonce($_POST['mindful_media_meta_nonce_field'], 'mindful_media_meta_nonce')) {
        
        // Save duration
        if (isset($_POST['mindful_media_duration_hours'])) {
            update_post_meta($post_id, '_mindful_media_duration_hours', sanitize_text_field($_POST['mindful_media_duration_hours']));
        }
        
        if (isset($_POST['mindful_media_duration_minutes'])) {
            update_post_meta($post_id, '_mindful_media_duration_minutes', sanitize_text_field($_POST['mindful_media_duration_minutes']));
        }
        
        // Save recording date
        if (isset($_POST['mindful_media_recording_date'])) {
            update_post_meta($post_id, '_mindful_media_recording_date', sanitize_text_field($_POST['mindful_media_recording_date']));
        }
        
        // Save media URL
        if (isset($_POST['mindful_media_url'])) {
            update_post_meta($post_id, '_mindful_media_url', esc_url_raw($_POST['mindful_media_url']));
        }
        
        // Save media source (or auto-detect)
        if (isset($_POST['mindful_media_source'])) {
            $source = sanitize_text_field($_POST['mindful_media_source']);
            
            // If empty and we have a URL, auto-detect
            if (empty($source) && !empty($_POST['mindful_media_url'])) {
                if (class_exists('MindfulMedia_Players')) {
                    $player = new MindfulMedia_Players();
                    $source = $player->detect_media_source(esc_url_raw($_POST['mindful_media_url']));
                }
            }
            
            update_post_meta($post_id, '_mindful_media_source', $source);
        }
        
        // Save external link
        if (isset($_POST['mindful_media_external_link'])) {
            update_post_meta($post_id, '_mindful_media_external_link', esc_url_raw($_POST['mindful_media_external_link']));
        }
        
        // Save CTA text
        if (isset($_POST['mindful_media_cta_text'])) {
            update_post_meta($post_id, '_mindful_media_cta_text', sanitize_text_field($_POST['mindful_media_cta_text']));
        }
        
        // Save featured status
        $featured = isset($_POST['mindful_media_featured']) ? '1' : '0';
        update_post_meta($post_id, '_mindful_media_featured', $featured);
        
        // Save series order
        if (isset($_POST['mindful_media_series_order'])) {
            $series_order = absint($_POST['mindful_media_series_order']);
            update_post_meta($post_id, '_mindful_media_series_order', $series_order);
        }
        
        // Auto-assign year taxonomy from recording date (Feature #7)
        $recording_date = get_post_meta($post_id, '_mindful_media_recording_date', true);
        if (!empty($recording_date)) {
            $year = date('Y', strtotime($recording_date));
            if ($year) {
                $term = get_term_by('name', $year, 'media_year');
                if (!$term) {
                    // wp_insert_term returns array with 'term_id'
                    $result = wp_insert_term($year, 'media_year');
                    if (!is_wp_error($result)) {
                        $term_id = $result['term_id'];
                        wp_set_object_terms($post_id, (int)$term_id, 'media_year', false);
                    }
                } else {
                    // get_term_by returns object with term_id property
                    wp_set_object_terms($post_id, (int)$term->term_id, 'media_year', false);
                }
            }
        }
        
        // Save custom embed code
        if (isset($_POST['mindful_media_custom_embed'])) {
            // Allow iframe and basic HTML tags for embed codes
            $allowed_tags = array(
                'iframe' => array(
                    'src' => array(),
                    'width' => array(),
                    'height' => array(),
                    'frameborder' => array(),
                    'allow' => array(),
                    'allowfullscreen' => array(),
                    'style' => array(),
                    'title' => array(),
                    'class' => array(),
                ),
                'script' => array(
                    'src' => array(),
                    'type' => array(),
                ),
            );
            update_post_meta($post_id, '_mindful_media_custom_embed', wp_kses($_POST['mindful_media_custom_embed'], $allowed_tags));
        }
        
        // Save custom fields
        if (isset($_POST['mindful_media_custom_fields']) && is_array($_POST['mindful_media_custom_fields'])) {
            $custom_fields = array();
            foreach ($_POST['mindful_media_custom_fields'] as $key => $value) {
                $custom_fields[sanitize_key($key)] = sanitize_text_field($value);
            }
            update_post_meta($post_id, '_mindful_media_custom_fields', $custom_fields);
        }
        } // End main meta nonce check
        
        // Save password protection (independent nonce check)
        if (isset($_POST['mindful_media_password_nonce_field']) && 
            wp_verify_nonce($_POST['mindful_media_password_nonce_field'], 'mindful_media_password_nonce')) {
            
            // Save protected status
            $is_protected = isset($_POST['mindful_media_password_protected']) ? '1' : '0';
            update_post_meta($post_id, '_mindful_media_password_protected', $is_protected);
            
            // Handle password
            if ($is_protected === '1') {
                // Check if new password is provided
                if (!empty($_POST['mindful_media_password'])) {
                    $password = sanitize_text_field($_POST['mindful_media_password']);
                    $password_confirm = isset($_POST['mindful_media_password_confirm']) ? sanitize_text_field($_POST['mindful_media_password_confirm']) : '';
                    
                    // Validate passwords match
                    if ($password === $password_confirm) {
                        // Hash the password using WordPress function
                        $password_hash = wp_hash_password($password);
                        update_post_meta($post_id, '_mindful_media_password_hash', $password_hash);
                    }
                }
                // No new password provided - keep existing if any
            } else {
                // Protection disabled - remove password
                delete_post_meta($post_id, '_mindful_media_password_hash');
            }
            
            // Save "hide from archive" option
            $hide_from_archive = isset($_POST['mindful_media_hide_from_archive']) ? '1' : '0';
            if ($hide_from_archive === '1') {
                update_post_meta($post_id, '_mindful_media_hide_from_archive', '1');
            } else {
                delete_post_meta($post_id, '_mindful_media_hide_from_archive');
            }
        }
    }
    
    /**
     * Auto-detect CTA text based on media type
     */
    private function auto_detect_cta_text($post_id, $media_source = '') {
        // Check media_type taxonomy first
        $media_types = get_the_terms($post_id, 'media_type');
        if ($media_types && !is_wp_error($media_types)) {
            $type_name = strtolower($media_types[0]->name);
            if (strpos($type_name, 'audio') !== false) {
                return 'Listen';
            } elseif (strpos($type_name, 'video') !== false) {
                return 'Watch';
            }
        }
        
        // Check source as fallback
        if (in_array($media_source, ['soundcloud', 'audio'])) {
            return 'Listen';
        } elseif (in_array($media_source, ['youtube', 'vimeo', 'video', 'archive'])) {
            return 'Watch';
        }
        
        return 'View';
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if ($hook == 'post-new.php' || $hook == 'post.php') {
            if ('mindful_media' === $post_type) {
                wp_enqueue_media();
                
                // Enqueue admin JS for live media detection
                wp_enqueue_script(
                    'mindful-media-admin',
                    MINDFUL_MEDIA_PLUGIN_URL . 'admin/js/admin.js',
                    array('jquery'),
                    MINDFUL_MEDIA_VERSION,
                    true
                );
                
                // Enqueue frontend CSS for preview functionality
                wp_enqueue_style(
                    'mindful-media-frontend-preview',
                    MINDFUL_MEDIA_PLUGIN_URL . 'public/css/frontend.css',
                    array(),
                    MINDFUL_MEDIA_VERSION
                );
            }
        }
    }
} 