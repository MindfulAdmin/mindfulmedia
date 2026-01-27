<?php
/**
 * Notifications Class
 * 
 * Handles email notifications for subscribed content
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MindfulMedia_Notifications {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Hook into post publish
        add_action('publish_mindful_media', array($this, 'on_media_publish'), 10, 2);
        
        // Register cron event
        add_action('mindful_media_send_notifications', array($this, 'process_notification_queue'));
        
        // Schedule cron if not already scheduled
        if (!wp_next_scheduled('mindful_media_send_notifications')) {
            wp_schedule_event(time(), 'hourly', 'mindful_media_send_notifications');
        }
    }
    
    /**
     * Handle media publish event
     */
    public function on_media_publish($post_id, $post) {
        $settings = MindfulMedia_Settings::get_settings();
        
        // Check if notifications are enabled
        if (empty($settings['enable_email_notifications']) || !empty($settings['disable_all_notifications'])) {
            return;
        }
        
        // Don't notify for scheduled posts that are just being published now if they were already queued
        if (get_post_meta($post_id, '_mm_notification_sent', true)) {
            return;
        }
        
        // Get all terms for this post
        $taxonomies = array(
            'media_series' => 'allow_subscription_playlists',
            'media_teacher' => 'allow_subscription_teachers',
            'media_topic' => 'allow_subscription_topics',
            'media_category' => 'allow_subscription_categories'
        );
        
        $subscribers_to_notify = array();
        
        foreach ($taxonomies as $taxonomy => $setting_key) {
            // Check if subscription type is enabled
            if (empty($settings[$setting_key])) {
                continue;
            }
            
            $terms = get_the_terms($post_id, $taxonomy);
            if (!$terms || is_wp_error($terms)) {
                continue;
            }
            
            foreach ($terms as $term) {
                // Get subscribers for this term who want email notifications
                $subscribers = $this->get_term_subscribers($term->term_id, $taxonomy);
                
                foreach ($subscribers as $user_id) {
                    if (!isset($subscribers_to_notify[$user_id])) {
                        $subscribers_to_notify[$user_id] = array(
                            'terms' => array()
                        );
                    }
                    $subscribers_to_notify[$user_id]['terms'][] = array(
                        'term_id' => $term->term_id,
                        'taxonomy' => $taxonomy,
                        'name' => $term->name
                    );
                }
            }
        }
        
        if (empty($subscribers_to_notify)) {
            return;
        }
        
        // Handle based on throttle setting
        $throttle = $settings['notification_throttle'] ?? 'instant';
        
        if ($throttle === 'instant') {
            // Send immediately
            $this->send_notifications($post_id, $subscribers_to_notify);
        } else {
            // Queue for later
            $this->queue_notifications($post_id, $subscribers_to_notify);
        }
        
        // Mark as notified
        update_post_meta($post_id, '_mm_notification_sent', current_time('mysql'));
    }
    
    /**
     * Get subscribers for a term who want email notifications
     */
    private function get_term_subscribers($term_id, $taxonomy) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'mindful_media_subscriptions';
        
        // Map taxonomy to object_type used in subscriptions
        $object_type = $taxonomy;
        if ($taxonomy === 'media_series') {
            // Check both 'media_series' and 'playlist' object types
            return $wpdb->get_col($wpdb->prepare(
                "SELECT user_id FROM $table 
                 WHERE object_id = %d 
                 AND (object_type = %s OR object_type = 'playlist')
                 AND notify_email = 1",
                $term_id, $taxonomy
            ));
        }
        
        return $wpdb->get_col($wpdb->prepare(
            "SELECT user_id FROM $table 
             WHERE object_id = %d 
             AND object_type = %s
             AND notify_email = 1",
            $term_id, $taxonomy
        ));
    }
    
    /**
     * Send notifications immediately
     */
    private function send_notifications($post_id, $subscribers) {
        $post = get_post($post_id);
        if (!$post) {
            return;
        }
        
        $settings = MindfulMedia_Settings::get_settings();
        
        foreach ($subscribers as $user_id => $data) {
            $user = get_user_by('ID', $user_id);
            if (!$user) {
                continue;
            }
            
            // Pick the first term for the subject line
            $first_term = $data['terms'][0];
            
            $this->send_email($user, $post, $first_term, $data['terms'], $settings);
        }
    }
    
    /**
     * Queue notifications for batch processing
     */
    private function queue_notifications($post_id, $subscribers) {
        $queue = get_option('mm_notification_queue', array());
        
        $queue[] = array(
            'post_id' => $post_id,
            'subscribers' => $subscribers,
            'queued_at' => current_time('mysql')
        );
        
        update_option('mm_notification_queue', $queue);
    }
    
    /**
     * Process notification queue (called by cron)
     */
    public function process_notification_queue() {
        $settings = MindfulMedia_Settings::get_settings();
        
        if (!empty($settings['disable_all_notifications'])) {
            return;
        }
        
        $queue = get_option('mm_notification_queue', array());
        
        if (empty($queue)) {
            return;
        }
        
        $throttle = $settings['notification_throttle'] ?? 'instant';
        
        // Group by user for digest mode
        if ($throttle === 'daily') {
            $this->send_digest_notifications($queue);
        } else {
            // Process individual notifications
            foreach ($queue as $item) {
                $this->send_notifications($item['post_id'], $item['subscribers']);
            }
        }
        
        // Clear the queue
        delete_option('mm_notification_queue');
    }
    
    /**
     * Send digest notifications
     */
    private function send_digest_notifications($queue) {
        $settings = MindfulMedia_Settings::get_settings();
        
        // Group posts by user
        $user_posts = array();
        
        foreach ($queue as $item) {
            foreach ($item['subscribers'] as $user_id => $data) {
                if (!isset($user_posts[$user_id])) {
                    $user_posts[$user_id] = array();
                }
                $user_posts[$user_id][] = array(
                    'post_id' => $item['post_id'],
                    'terms' => $data['terms']
                );
            }
        }
        
        // Send one digest per user
        foreach ($user_posts as $user_id => $posts) {
            $user = get_user_by('ID', $user_id);
            if (!$user) {
                continue;
            }
            
            $this->send_digest_email($user, $posts, $settings);
        }
    }
    
    /**
     * Send a single notification email
     */
    private function send_email($user, $post, $primary_term, $all_terms, $settings) {
        $from_name = $settings['notification_from_name'] ?? get_bloginfo('name');
        $from_email = $settings['notification_from_email'] ?? get_bloginfo('admin_email');
        
        // Build subject
        $subject_template = $settings['notification_subject_template'] ?? __('New content from {term_name}', 'mindful-media');
        $subject = str_replace(
            array('{term_name}', '{site_name}', '{post_title}'),
            array($primary_term['name'], get_bloginfo('name'), $post->post_title),
            $subject_template
        );
        
        // Get thumbnail
        $thumbnail_url = '';
        if (has_post_thumbnail($post->ID)) {
            $thumbnail_url = get_the_post_thumbnail_url($post->ID, 'medium');
        }
        
        // Build email body
        $message = $this->get_email_template(array(
            'user_name' => $user->display_name,
            'post_title' => $post->post_title,
            'post_excerpt' => wp_trim_words($post->post_content, 30),
            'post_url' => get_permalink($post->ID),
            'thumbnail_url' => $thumbnail_url,
            'term_name' => $primary_term['name'],
            'all_terms' => $all_terms,
            'site_name' => get_bloginfo('name'),
            'unsubscribe_url' => $this->get_unsubscribe_url($user->ID)
        ));
        
        // Set headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>'
        );
        
        wp_mail($user->user_email, $subject, $message, $headers);
    }
    
    /**
     * Send digest email
     */
    private function send_digest_email($user, $posts, $settings) {
        $from_name = $settings['notification_from_name'] ?? get_bloginfo('name');
        $from_email = $settings['notification_from_email'] ?? get_bloginfo('admin_email');
        
        // Get template colors from settings
        $header_bg = $settings['email_header_bg'] ?? '#8B0000';
        $header_text_color = $settings['email_header_text_color'] ?? '#ffffff';
        $button_bg = $settings['email_button_bg'] ?? '#DAA520';
        $button_text_color = $settings['email_button_text_color'] ?? '#ffffff';
        $header_text = $settings['email_header_text'] ?? get_bloginfo('name');
        $footer_text = $settings['email_footer_text'] ?? __('You received this email because you subscribed to updates.', 'mindful-media');
        $logo_id = $settings['email_logo_id'] ?? 0;
        $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : '';
        
        // Build header content (logo or text)
        $header_content = '';
        if ($logo_url) {
            $header_content = '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr(get_bloginfo('name')) . '" style="max-height: 60px; width: auto;" />';
        } else {
            $header_content = '<h1 style="margin: 0; font-size: 24px; font-weight: 600;">' . esc_html($header_text) . '</h1>';
        }
        
        $subject = sprintf(
            __('%d new items from %s', 'mindful-media'),
            count($posts),
            get_bloginfo('name')
        );
        
        // Build post list
        $posts_html = '';
        foreach ($posts as $item) {
            $post = get_post($item['post_id']);
            if (!$post) {
                continue;
            }
            
            $thumbnail_url = '';
            if (has_post_thumbnail($post->ID)) {
                $thumbnail_url = get_the_post_thumbnail_url($post->ID, 'thumbnail');
            }
            
            $posts_html .= '<tr><td style="padding: 15px 0; border-bottom: 1px solid #eee;">';
            if ($thumbnail_url) {
                $posts_html .= '<img src="' . esc_url($thumbnail_url) . '" alt="" style="width: 120px; height: 68px; object-fit: cover; border-radius: 4px; float: left; margin-right: 15px;">';
            }
            $posts_html .= '<h3 style="margin: 0 0 5px;"><a href="' . esc_url(get_permalink($post->ID)) . '" style="color: #333333; text-decoration: none;">' . esc_html($post->post_title) . '</a></h3>';
            $posts_html .= '<p style="margin: 0; color: #606060; font-size: 14px;">' . esc_html(wp_trim_words($post->post_content, 20)) . '</p>';
            $posts_html .= '<div style="clear: both;"></div></td></tr>';
        }
        
        $message = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; background: #f5f5f5; padding: 20px;">
            <div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden;">
                <div style="background: ' . esc_attr($header_bg) . '; color: ' . esc_attr($header_text_color) . '; padding: 25px; text-align: center;">
                    ' . $header_content . '
                </div>
                <div style="padding: 30px;">
                    <p style="font-size: 16px; color: #333;">Hi ' . esc_html($user->display_name) . ',</p>
                    <p style="font-size: 16px; color: #333;">Here\'s what\'s new from your subscriptions:</p>
                    <table style="width: 100%; border-collapse: collapse;">' . $posts_html . '</table>
                </div>
                <div style="background: #f9f9f9; padding: 20px; text-align: center; font-size: 12px; color: #666;">
                    <p style="margin: 0 0 10px; line-height: 1.5;">' . wp_kses_post($footer_text) . '</p>
                    <a href="' . esc_url($this->get_unsubscribe_url($user->ID)) . '" style="color: #666;">Manage subscriptions</a>
                </div>
            </div>
        </body></html>';
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>'
        );
        
        wp_mail($user->user_email, $subject, $message, $headers);
    }
    
    /**
     * Get email template
     */
    private function get_email_template($vars) {
        $settings = MindfulMedia_Settings::get_settings();
        
        // Get template colors from settings
        $header_bg = $settings['email_header_bg'] ?? '#8B0000';
        $header_text_color = $settings['email_header_text_color'] ?? '#ffffff';
        $button_bg = $settings['email_button_bg'] ?? '#DAA520';
        $button_text_color = $settings['email_button_text_color'] ?? '#ffffff';
        $header_text = $settings['email_header_text'] ?? $vars['site_name'];
        $footer_text = $settings['email_footer_text'] ?? __('You received this email because you subscribed to updates.', 'mindful-media');
        $logo_id = $settings['email_logo_id'] ?? 0;
        $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : '';
        
        // Get custom body template
        $body_template = $settings['email_body_template'] ?? "Hi {user_name},\n\nNew content is available from <strong>{term_name}</strong>:\n\n<div style=\"background: #f5f5f5; padding: 15px; border-radius: 6px; margin: 20px 0;\">\n<strong>{post_title}</strong>\n<p style=\"margin: 8px 0 0; color: #666;\">{post_excerpt}</p>\n</div>\n\n<a href=\"{post_url}\" style=\"display: inline-block; background: {button_color}; color: {button_text_color}; padding: 12px 24px; border-radius: 4px; text-decoration: none; font-weight: 600;\">Watch Now</a>";
        
        // Replace placeholders in body template
        $body_content = str_replace(
            array(
                '{user_name}',
                '{post_title}',
                '{post_excerpt}',
                '{post_url}',
                '{term_name}',
                '{site_name}',
                '{thumbnail_url}',
                '{button_color}',
                '{button_text_color}'
            ),
            array(
                esc_html($vars['user_name']),
                esc_html($vars['post_title']),
                esc_html($vars['post_excerpt']),
                esc_url($vars['post_url']),
                esc_html($vars['term_name']),
                esc_html($vars['site_name']),
                esc_url($vars['thumbnail_url']),
                esc_attr($button_bg),
                esc_attr($button_text_color)
            ),
            $body_template
        );
        
        // Convert newlines to <br> for plain text areas
        $body_content = nl2br($body_content);
        
        // Build header content (logo or text)
        $header_content = '';
        if ($logo_url) {
            $header_content = '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($vars['site_name']) . '" style="max-height: 60px; width: auto;" />';
        } else {
            $header_content = '<h1 style="margin: 0; font-size: 24px; font-weight: 600;">' . esc_html($header_text) . '</h1>';
        }
        
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <!-- Header -->
        <div style="background: ' . esc_attr($header_bg) . '; color: ' . esc_attr($header_text_color) . '; padding: 25px; text-align: center;">
            ' . $header_content . '
        </div>
        
        <!-- Content -->
        <div style="padding: 30px; font-size: 16px; color: #333; line-height: 1.6;">
            ' . wp_kses_post($body_content) . '
        </div>
        
        <!-- Footer -->
        <div style="background: #f9f9f9; padding: 20px; text-align: center; font-size: 12px; color: #666;">
            <p style="margin: 0 0 10px; line-height: 1.5;">
                ' . wp_kses_post($footer_text) . '
            </p>
            <p style="margin: 0;">
                <a href="' . esc_url($vars['unsubscribe_url']) . '" style="color: #666;">Manage your subscriptions</a>
            </p>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * Get unsubscribe URL
     */
    private function get_unsubscribe_url($user_id) {
        $settings = MindfulMedia_Settings::get_settings();
        
        // Link to My Library page if available
        if (!empty($settings['library_page_id'])) {
            return get_permalink($settings['library_page_id']) . '#subscriptions';
        }
        
        // Fallback to profile page
        return get_edit_user_link($user_id);
    }
}
