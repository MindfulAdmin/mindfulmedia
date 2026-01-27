<?php
/**
 * Settings Class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MindfulMedia_Settings {
    
    public function __construct() {
        add_action('wp_head', array($this, 'output_custom_styles'), 100);
        add_action('wp_head', array($this, 'output_badge_colors'), 99);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_google_fonts'));
        add_action('wp_ajax_mindful_media_export', array($this, 'export_data'));
        add_action('wp_ajax_mindful_media_import', array($this, 'import_data'));
        add_action('wp_ajax_mindful_media_reset_settings', array($this, 'reset_settings'));
    }
    
    /**
     * Output badge colors as CSS variables
     */
    public function output_badge_colors() {
        $settings = self::get_settings();
        $audio_color = $settings['audio_badge_color'];
        $video_color = $settings['video_badge_color'];
        $progress_bar_color = $settings['progress_bar_color'];
        
        echo '<style id="mindful-media-badge-colors">';
        echo ':root {';
        echo '--mindful-media-audio-color: ' . esc_attr($audio_color) . ';';
        echo '--mindful-media-video-color: ' . esc_attr($video_color) . ';';
        echo '--mindful-media-progress-color: ' . esc_attr($progress_bar_color) . ';';
        echo '}';
        echo '</style>';
    }
    
    /**
     * Enqueue Google Fonts if selected
     */
    public function enqueue_google_fonts() {
        $settings = self::get_settings();
        $font_family = $settings['font_family'];
        $font_weight = $settings['font_weight'];
        
        // Check if it's a Google Font
        if (self::is_google_font($font_family)) {
            // Prepare font URL with proper weights
            $font_slug = str_replace(' ', '+', $font_family);
            
            // Load multiple weights for flexibility
            $weights = array('300', '400', '500', '600', '700');
            $weights_param = implode(';', $weights);
            
            // Also load italic variants
            $font_url = 'https://fonts.googleapis.com/css2?family=' . $font_slug . ':ital,wght@0,' . $weights_param . ';1,' . $weights_param . '&display=swap';
            
            wp_enqueue_style(
                'mindful-media-google-font',
                $font_url,
                array(),
                null
            );
        }
    }
    
    /**
     * Reset settings to defaults
     */
    public function reset_settings() {
        // Security checks
        check_ajax_referer('mindful_media_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'mindful-media'));
        }
        
        // Delete the option to force defaults
        delete_option('mindful_media_settings');
        
        wp_send_json_success(array(
            'message' => 'Settings reset to defaults'
        ));
    }
    
    /**
     * Get plugin settings
     */
    public static function get_settings() {
        $defaults = array(
            'primary_color' => '#8B0000',
            'secondary_color' => '#DAA520',
            'text_color_light' => '#FFFFFF',
            'text_color_dark' => '#333333',
            'audio_badge_color' => '#D4AF37', // Monastic gold
            'video_badge_color' => '#8B0000', // Deep monastic red
            'progress_bar_color' => '#ff0000', // YouTube red
            'audio_image_width' => 1000,
            'audio_image_height' => 1000,
            'video_image_width' => 1920,
            'video_image_height' => 1080,
            'font_family' => 'theme_default',
            'font_weight' => '400',
            'font_size_title' => 22,
            'font_size_teacher' => 18,
            'font_size_content' => 14,
            'font_size_filter_heading' => 16,
            'font_size_filter_options' => 14,
            'font_size_filter_buttons' => 13,
            'font_size_single_title' => 32,
            'font_size_single_content' => 16,
            'font_size_single_meta' => 14,
            'filter_layout' => 'vertical',
            'grid_columns' => 3,
            'grid_columns_tablet' => 2,
            'grid_columns_mobile' => 1,
            'card_spacing' => 30,
            'archive_link' => '/media',
            'player_autoplay' => '0',
            'player_volume' => 80,
            'soundcloud_client_id' => '',
            'vimeo_access_token' => '',
            'youtube_api_key' => '',
            'player_size' => 'normal',
            'player_controls' => '1',
            'modal_player_theme' => 'dark', // dark or light
            'modal_share_button' => '1', // Show share button in modal player
            'modal_show_more_media' => '1', // Show "More Media" recommendations in modal
            'youtube_hide_end_screen' => '0', // Cover YouTube end-screen overlay
            'custom_fields' => array(),
            // Navigation URLs
            'archive_back_url' => '/browse',
            'media_archive_url' => '/media',
            // Browse page section visibility
            'browse_show_teachers' => '1',
            'browse_show_topics' => '1',
            'browse_show_playlists' => '1',
            'browse_show_categories' => '1',
            'browse_show_media_types' => '0',
            
            // Per-taxonomy image aspect ratios
            'teacher_image_ratio' => 'landscape',     // square, landscape, portrait, custom
            'teacher_image_ratio_custom' => '16:9',
            'topic_image_ratio' => 'landscape',
            'topic_image_ratio_custom' => '16:9',
            'category_image_ratio' => 'landscape',
            'category_image_ratio_custom' => '16:9',
            'series_image_ratio' => 'landscape',
            'series_image_ratio_custom' => '16:9',
            
            // Engagement Settings
            'enable_likes' => '1',
            'enable_comments' => '1',
            'enable_subscriptions' => '1',
            'show_counts_on_cards' => '1',
            'show_counts_on_single' => '1',
            'require_login_for_engagement' => '1',
            'auto_approve_comments' => '0',
            'allow_subscription_playlists' => '1',
            'allow_subscription_teachers' => '1',
            'allow_subscription_topics' => '1',
            'allow_subscription_categories' => '1',
            
            // Notification Settings
            'enable_email_notifications' => '1',
            'notification_from_name' => get_bloginfo('name'),
            'notification_from_email' => get_bloginfo('admin_email'),
            'notification_subject_template' => __('New content from {term_name}', 'mindful-media'),
            'notification_throttle' => 'instant', // instant, hourly, daily
            'disable_all_notifications' => '0',
            
            // Email Template Settings
            'email_logo_id' => 0,
            'email_header_text' => get_bloginfo('name'),
            'email_body_template' => "Hi {user_name},\n\nNew content is available from <strong>{term_name}</strong>:\n\n<div style=\"background: #f5f5f5; padding: 15px; border-radius: 6px; margin: 20px 0;\">\n<strong>{post_title}</strong>\n<p style=\"margin: 8px 0 0; color: #666;\">{post_excerpt}</p>\n</div>\n\n<a href=\"{post_url}\" style=\"display: inline-block; background: {button_color}; color: {button_text_color}; padding: 12px 24px; border-radius: 4px; text-decoration: none; font-weight: 600;\">Watch Now</a>",
            'email_footer_text' => __('You received this email because you subscribed to updates. Click unsubscribe to stop receiving these emails.', 'mindful-media'),
            'email_header_bg' => '#8B0000',
            'email_header_text_color' => '#ffffff',
            'email_button_bg' => '#DAA520',
            'email_button_text_color' => '#ffffff',
            
            // Access Settings (MemberPress)
            'enable_memberpress_gating' => '0',
            'login_url' => wp_login_url(),
            'join_url' => '',
            'locked_content_behavior' => 'show_lock', // hide, show_lock
            'locked_cta_text' => __('Join to access this content', 'mindful-media'),
            'default_access_level' => '', // Default MemberPress level (empty = public)
            
            // My Library Settings
            'library_page_id' => '',
            'enable_woocommerce_tab' => '0',
            
            // Data Retention
            'keep_engagement_data_on_uninstall' => '1'
        );
        
        $settings = get_option('mindful_media_settings', array());
        return wp_parse_args($settings, $defaults);
    }
    
    /**
     * Check if MemberPress is active
     */
    public static function is_memberpress_active() {
        return class_exists('MeprProduct');
    }
    
    /**
     * Check if WooCommerce is active
     */
    public static function is_woocommerce_active() {
        return class_exists('WooCommerce');
    }
    
    /**
     * Get the CSS aspect ratio value for a taxonomy
     * 
     * @param string $taxonomy The taxonomy name (media_teacher, media_topic, media_category, media_series)
     * @return string CSS aspect-ratio value (e.g., "16 / 9", "1 / 1")
     */
    public static function get_taxonomy_aspect_ratio($taxonomy) {
        $settings = self::get_settings();
        
        // Map taxonomy to settings key prefix
        $prefix_map = array(
            'media_teacher' => 'teacher',
            'media_topic' => 'topic',
            'media_category' => 'category',
            'media_series' => 'series'
        );
        
        $prefix = isset($prefix_map[$taxonomy]) ? $prefix_map[$taxonomy] : 'teacher';
        $ratio_setting = isset($settings[$prefix . '_image_ratio']) ? $settings[$prefix . '_image_ratio'] : 'landscape';
        $custom_ratio = isset($settings[$prefix . '_image_ratio_custom']) ? $settings[$prefix . '_image_ratio_custom'] : '16:9';
        
        // Convert preset to CSS aspect-ratio value
        switch ($ratio_setting) {
            case 'square':
                return '1 / 1';
            case 'portrait':
                return '3 / 4';
            case 'custom':
                // Parse custom ratio (e.g., "16:9" -> "16 / 9")
                $parts = explode(':', $custom_ratio);
                if (count($parts) === 2 && is_numeric(trim($parts[0])) && is_numeric(trim($parts[1]))) {
                    return trim($parts[0]) . ' / ' . trim($parts[1]);
                }
                return '16 / 9'; // Fallback
            case 'landscape':
            default:
                return '16 / 9';
        }
    }
    
    /**
     * Get MemberPress membership levels
     */
    public static function get_memberpress_levels() {
        if (!self::is_memberpress_active()) {
            return array();
        }
        
        $products = MeprProduct::get_all();
        $levels = array();
        
        foreach ($products as $product) {
            $levels[$product->ID] = $product->post_title;
        }
        
        return $levels;
    }
    
    /**
     * Get join URL for a specific membership level (or default)
     * 
     * @param int|null $level_id Specific membership level ID, or null for default
     * @return string The join/pricing URL
     */
    public static function get_join_url($level_id = null) {
        $settings = self::get_settings();
        
        // Check for level-specific URL first
        if ($level_id && !empty($settings['membership_urls'][$level_id])) {
            return $settings['membership_urls'][$level_id];
        }
        
        // Fall back to default URL
        if (!empty($settings['join_url'])) {
            return $settings['join_url'];
        }
        
        // Last resort: MemberPress registration page if available
        if (self::is_memberpress_active() && $level_id) {
            $product = new MeprProduct($level_id);
            if ($product->ID) {
                return $product->url();
            }
        }
        
        return '';
    }
    
    /**
     * Check if user can view a media item (access control)
     * 
     * @param int $post_id The post ID to check
     * @param int|null $user_id User ID to check (defaults to current user)
     * @return bool|array True if allowed, or array with 'locked' => true and 'reason' if restricted
     */
    public static function user_can_view($post_id, $user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        $settings = self::get_settings();
        
        // Check password protection first (existing functionality)
        $is_password_protected = get_post_meta($post_id, '_mindful_media_password_protected', true);
        if ($is_password_protected === '1') {
            // Check if user has the password in session
            $session_key = 'mindful_media_unlocked_' . $post_id;
            if (!isset($_SESSION[$session_key]) || $_SESSION[$session_key] !== true) {
                // Also check cookie
                $cookie_key = 'mm_unlocked_' . $post_id;
                if (!isset($_COOKIE[$cookie_key])) {
                    return array(
                        'locked' => true,
                        'reason' => 'password',
                        'message' => __('This content is password protected.', 'mindful-media')
                    );
                }
            }
        }
        
        // Check MemberPress gating
        if (empty($settings['enable_memberpress_gating']) || !self::is_memberpress_active()) {
            return true;
        }
        
        // Get required levels for this post
        $required_levels = get_post_meta($post_id, '_mindful_media_memberpress_levels', true);
        
        // If no specific levels set, use global default
        if (empty($required_levels) && !empty($settings['default_access_level'])) {
            $required_levels = array((int) $settings['default_access_level']);
        }
        
        // If still no required levels, content is public
        if (empty($required_levels)) {
            return true;
        }
        
        // Guest users cannot access restricted content
        if (!$user_id) {
            return array(
                'locked' => true,
                'reason' => 'membership',
                'required_levels' => $required_levels,
                'message' => $settings['locked_cta_text'] ?? __('Join to access this content', 'mindful-media')
            );
        }
        
        // Check if user has any of the required membership levels
        $mepr_user = new MeprUser($user_id);
        
        foreach ($required_levels as $level_id) {
            if ($mepr_user->is_active_on_membership($level_id)) {
                return true;
            }
        }
        
        // User doesn't have required membership
        return array(
            'locked' => true,
            'reason' => 'membership',
            'required_levels' => $required_levels,
            'message' => $settings['locked_cta_text'] ?? __('Join to access this content', 'mindful-media')
        );
    }
    
    /**
     * Check if user can view a term (taxonomy access control)
     * 
     * @param int $term_id The term ID
     * @param string $taxonomy The taxonomy name
     * @param int|null $user_id User ID to check
     * @return bool|array True if allowed, or array with lock details
     */
    public static function user_can_view_term($term_id, $taxonomy, $user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        $settings = self::get_settings();
        
        // Check password protection for playlists
        if ($taxonomy === 'media_series') {
            $is_protected = get_term_meta($term_id, 'playlist_password_enabled', true);
            if ($is_protected === '1') {
                $cookie_key = 'mm_playlist_unlocked_' . $term_id;
                if (!isset($_COOKIE[$cookie_key])) {
                    return array(
                        'locked' => true,
                        'reason' => 'password',
                        'message' => __('This playlist is password protected.', 'mindful-media')
                    );
                }
            }
        }
        
        // Check MemberPress gating for terms
        if (empty($settings['enable_memberpress_gating']) || !self::is_memberpress_active()) {
            return true;
        }
        
        $required_levels = get_term_meta($term_id, '_mindful_media_memberpress_levels', true);
        
        if (empty($required_levels)) {
            return true;
        }
        
        if (!$user_id) {
            return array(
                'locked' => true,
                'reason' => 'membership',
                'required_levels' => $required_levels,
                'message' => $settings['locked_cta_text'] ?? __('Join to access this content', 'mindful-media')
            );
        }
        
        $mepr_user = new MeprUser($user_id);
        
        foreach ($required_levels as $level_id) {
            if ($mepr_user->is_active_on_membership($level_id)) {
                return true;
            }
        }
        
        return array(
            'locked' => true,
            'reason' => 'membership',
            'required_levels' => $required_levels,
            'message' => $settings['locked_cta_text'] ?? __('Join to access this content', 'mindful-media')
        );
    }
    
    /**
     * Get list of Google Fonts that need to be loaded
     */
    public static function get_google_fonts() {
        return array(
            'Lora', 'Merriweather', 'Playfair Display', 'Crimson Text', 'EB Garamond',
            'Open Sans', 'Roboto', 'Lato', 'Montserrat', 'Nunito', 'Source Sans Pro', 
            'Raleway', 'Inter', 'Cormorant Garamond', 'Libre Baskerville', 'Spectral', 
            'Noto Serif'
        );
    }
    
    /**
     * Check if a font is a Google Font
     */
    public static function is_google_font($font_family) {
        return in_array($font_family, self::get_google_fonts());
    }
    
    /**
     * Get specific setting
     */
    public static function get_setting($key, $default = '') {
        $settings = self::get_settings();
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
    
    /**
     * Output custom styles based on settings
     */
    public function output_custom_styles() {
        $settings = self::get_settings();
        
        $primary_color = $settings['primary_color'];
        $secondary_color = $settings['secondary_color'];
        $text_color_light = $settings['text_color_light'];
        $text_color_dark = $settings['text_color_dark'];
        $font_family = $settings['font_family'];
        $font_weight = $settings['font_weight'];
        $font_size_title = $settings['font_size_title'];
        $font_size_teacher = $settings['font_size_teacher'];
        $font_size_content = $settings['font_size_content'];
        $font_size_filter_heading = $settings['font_size_filter_heading'];
        $font_size_filter_options = $settings['font_size_filter_options'];
        $font_size_filter_buttons = $settings['font_size_filter_buttons'];
        $font_size_single_title = $settings['font_size_single_title'];
        $font_size_single_content = $settings['font_size_single_content'];
        $font_size_single_meta = $settings['font_size_single_meta'];
        $filter_layout = $settings['filter_layout'];
        $grid_columns = $settings['grid_columns'];
        $grid_columns_tablet = $settings['grid_columns_tablet'];
        $grid_columns_mobile = $settings['grid_columns_mobile'];
        $card_spacing = $settings['card_spacing'];
        $player_size = $settings['player_size'];
        
        // Prepare font family for CSS
        $font_family_css = $font_family;
        if (self::is_google_font($font_family)) {
            $font_family_css = "'" . $font_family . "', sans-serif";
        }
        
        // Calculate player max width based on size setting
        $player_max_width = '800px';
        if ($player_size === 'large') {
            $player_max_width = '1000px';
        } elseif ($player_size === 'full') {
            $player_max_width = '100%';
        }
        
        ?>
        <style type="text/css" id="mindful-media-custom-styles">
        /* CSS Variables for consistent theming */
        :root {
            --mindful-media-primary: <?php echo esc_html($primary_color); ?>;
            --mindful-media-secondary: <?php echo esc_html($secondary_color); ?>;
            --mindful-media-text-light: <?php echo esc_html($text_color_light); ?>;
            --mindful-media-text-dark: <?php echo esc_html($text_color_dark); ?>;
            --mindful-media-primary-rgb: <?php echo implode(', ', sscanf($primary_color, "#%02x%02x%02x")); ?>;
            --mindful-media-secondary-rgb: <?php echo implode(', ', sscanf($secondary_color, "#%02x%02x%02x")); ?>;
        }
        
        .mindful-media-container {
            <?php if ($font_family !== 'theme_default'): ?>
            font-family: <?php echo esc_html($font_family_css); ?>;
            font-weight: <?php echo esc_html($font_weight); ?>;
            <?php endif; ?>
        }
        
        /* Apply font to all mindful media elements */
        <?php if ($font_family !== 'theme_default'): ?>
        .mindful-media-item-title,
        .mindful-media-item-teacher,
        .mindful-media-item-excerpt,
        .mindful-media-single-title,
        .mindful-media-single-content {
            font-family: <?php echo esc_html($font_family_css); ?> !important;
        }
        
        .mindful-media-item-content,
        .mindful-media-single-content p {
            font-weight: <?php echo esc_html($font_weight); ?> !important;
        }
        
        /* Headings should be bolder */
        .mindful-media-item-title,
        .mindful-media-single-title {
            font-weight: <?php echo esc_html(min(700, intval($font_weight) + 300)); ?> !important;
        }
        <?php endif; ?>
        
        /* FORCE player max-width on single pages - HIGHEST PRIORITY */
        body.single-mindful_media .mindful-media-container,
        body.single-mindful_media .entry-content .mindful-media-container,
        body.single-mindful_media #primary .mindful-media-container,
        body.single-mindful_media article .mindful-media-container,
        .single-mindful_media .mindful-media-container,
        .mindful-media-single {
            max-width: <?php echo esc_html($player_max_width); ?> !important;
            width: 100% !important;
        }
        
        /* FORCE breadcrumbs to 800px - HIGHEST PRIORITY */
        body.single-mindful_media .mindful-media-breadcrumbs,
        .single-mindful_media .mindful-media-breadcrumbs,
        .mindful-media-breadcrumbs {
            max-width: 800px !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }
        
        /* FORCE Archive.org controls to proper height - HIGHEST PRIORITY */
        .mindful-media-player-controls {
            height: 80px !important;
            min-height: 80px !important;
        }
        
        .mindful-media-player-controls iframe {
            height: 80px !important;
        }
        
        /* Allow dropdown menus to expand the container when needed */
        .mindful-media-controls-area:hover,
        .mindful-media-controls-area:focus-within {
            height: auto !important;
            min-height: 70px !important;
        }
        
        .mindful-media-filters {
            <?php if ($filter_layout === 'horizontal'): ?>
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            <?php endif; ?>
        }
        
        .mindful-media-filter-section {
            <?php if ($filter_layout === 'horizontal'): ?>
            flex: 1;
            min-width: 200px;
            <?php endif; ?>
        }
        
        /* Settings-based grid columns - HIGH SPECIFICITY - Fixed columns, not auto-fit */
        .mindful-media-container .mindful-media-content .mindful-media-archive,
        .mindful-media-wrapper .mindful-media-content .mindful-media-archive,
        .mindful-media-archive {
            grid-template-columns: repeat(<?php echo (int)$grid_columns; ?>, 1fr) !important;
            gap: <?php echo esc_html($card_spacing); ?>px !important;
            background: none !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
        }
        
        /* Force desktop columns on larger screens */
        @media (min-width: 769px) {
            .mindful-media-container .mindful-media-content .mindful-media-archive,
            .mindful-media-wrapper .mindful-media-content .mindful-media-archive,
            .mindful-media-archive {
                grid-template-columns: repeat(<?php echo (int)$grid_columns; ?>, 1fr) !important;
                gap: <?php echo esc_html($card_spacing); ?>px !important;
            }
        }
        
        /* Tablet responsive */
        @media (min-width: 769px) and (max-width: 1024px) {
            .mindful-media-wrapper .mindful-media-content .mindful-media-archive,
            .mindful-media-archive {
                grid-template-columns: repeat(<?php echo (int)$grid_columns_tablet; ?>, 1fr) !important;
                gap: <?php echo esc_html($card_spacing); ?>px !important;
            }
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .mindful-media-wrapper .mindful-media-content .mindful-media-archive,
            .mindful-media-archive {
                grid-template-columns: repeat(<?php echo (int)$grid_columns_mobile; ?>, 1fr) !important;
                gap: <?php echo esc_html($card_spacing); ?>px !important;
            }
        }
        
        /* FORCE CORRECT FONT SIZES */
        .mindful-media-item .mindful-media-item-content .mindful-media-item-title,
        .mindful-media-item .mindful-media-item-content h3.mindful-media-item-title,
        h3.mindful-media-item-title {
            font-size: <?php echo esc_html($font_size_title); ?>px !important;
            font-weight: 700 !important;
            line-height: 1.3 !important;
            color: var(--mindful-media-text-dark) !important;
            margin: 0 0 10px 0 !important;
        }
        
        .mindful-media-item .mindful-media-item-content .mindful-media-item-teacher,
        .mindful-media-item-teacher,
        div.mindful-media-item-teacher {
            font-size: <?php echo esc_html($font_size_teacher); ?>px !important;
            font-style: italic !important;
            color: #666 !important;
            font-weight: 400 !important;
            line-height: 1.3 !important;
            margin-bottom: 15px !important;
        }
        
        .mindful-media-item .mindful-media-item-content .mindful-media-item-teacher span,
        .mindful-media-item-teacher span,
        div.mindful-media-item-teacher span {
            font-size: <?php echo esc_html($font_size_teacher); ?>px !important;
            font-style: italic !important;
            color: #666 !important;
            font-weight: 400 !important;
        }
        
        .mindful-media-item-excerpt,
        .mindful-media-item-content p,
        .mindful-media-single-content p {
            font-size: <?php echo esc_html($font_size_content); ?>px !important;
            color: var(--mindful-media-text-dark) !important;
        }
        
        /* Filter Bar Font Sizes */
        .mindful-media-filters h3,
        .mindful-media-filters h4,
        .mindful-media-filter-section h3,
        .mindful-media-filter-section h4 {
            font-size: <?php echo esc_html($font_size_filter_heading); ?>px !important;
            color: var(--mindful-media-text-dark) !important;
        }
        
        .mindful-media-filter-option,
        .mindful-media-filter-option label,
        .mindful-media-filters input[type="checkbox"] + label,
        .mindful-media-filters select,
        .mindful-media-filters input[type="text"],
        .mindful-media-search-input {
            font-size: <?php echo esc_html($font_size_filter_options); ?>px !important;
            color: var(--mindful-media-text-dark) !important;
        }
        
        .mindful-media-apply-filters,
        .mindful-media-clear-filters,
        .mindful-media-filter-toggle {
            font-size: <?php echo esc_html($font_size_filter_buttons); ?>px !important;
        }
        
        /* Single Page Font Sizes */
        .mindful-media-single-title,
        .single-mindful_media .entry-title,
        .single-mindful_media h1.entry-title,
        body.single-mindful_media .mindful-media-single-title {
            font-size: <?php echo esc_html($font_size_single_title); ?>px !important;
            color: var(--mindful-media-text-dark) !important;
        }
        
        .mindful-media-single-content,
        .mindful-media-single-content p,
        .single-mindful_media .entry-content,
        .single-mindful_media .entry-content p,
        body.single-mindful_media .mindful-media-single-content p {
            font-size: <?php echo esc_html($font_size_single_content); ?>px !important;
            color: var(--mindful-media-text-dark) !important;
        }
        
        .mindful-media-single-teacher,
        .mindful-media-single-meta,
        .mindful-media-single-tags,
        .mindful-media-single-tag,
        .mindful-media-single-custom-fields,
        .single-mindful_media .entry-meta,
        body.single-mindful_media .mindful-media-single-teacher,
        body.single-mindful_media .mindful-media-single-meta {
            font-size: <?php echo esc_html($font_size_single_meta); ?>px !important;
            color: var(--mindful-media-text-dark) !important;
        }
        
        /* Button and interactive element styling with proper text colors */
        .mindful-media-item-button,
        .mindful-media-apply-filters,
        .mindful-media-play-inline,
        .mindful-media-item-type-link {
            background: var(--mindful-media-primary) !important;
            color: var(--mindful-media-text-light) !important;
            border: none !important;
        }
        
        .mindful-media-item-button:hover,
        .mindful-media-apply-filters:hover,
        .mindful-media-play-inline:hover,
        .mindful-media-item-type-link:hover {
            background: var(--mindful-media-secondary) !important;
            color: var(--mindful-media-text-light) !important;
        }
        
        .mindful-media-item:hover {
            border-color: var(--mindful-media-primary) !important;
        }
        
        .mindful-media-single-tag {
            background: var(--mindful-media-primary) !important;
            color: var(--mindful-media-text-light) !important;
        }
        
        .mindful-media-single-tag:hover {
            background: var(--mindful-media-secondary) !important;
            color: var(--mindful-media-text-light) !important;
        }
        
        /* "Now playing" state */
        .mindful-media-play-inline.now-playing {
            background: var(--mindful-media-secondary) !important;
            color: var(--mindful-media-text-light) !important;
        }
        
        /* Pagination styling */
        .mindful-media-pagination .page-numbers.current {
            background: var(--mindful-media-primary) !important;
            color: var(--mindful-media-text-light) !important;
            border-color: var(--mindful-media-primary) !important;
        }
        
        /* Modal player header - keep transparent, no primary color override */
        
        /* Ensure proper text colors on light backgrounds */
        .mindful-media-item-title,
        .mindful-media-item-content,
        .mindful-media-item-excerpt {
            color: var(--mindful-media-text-dark) !important;
        }
        </style>
        <?php
    }
    
    /**
     * Export plugin data
     */
    public function export_data() {
        // Verify nonce
        check_ajax_referer('mindful_media_export_nonce', '_wpnonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'mindful-media'));
        }
        
        // Get all mindful media posts
        $posts = get_posts(array(
            'post_type' => 'mindful_media',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        $export_data = array(
            'version' => MINDFUL_MEDIA_VERSION,
            'timestamp' => current_time('mysql'),
            'settings' => get_option('mindful_media_settings', array()),
            'posts' => array(),
            'taxonomies' => array()
        );
        
        // Export posts with meta data
        foreach ($posts as $post) {
            $post_data = array(
                'title' => $post->post_title,
                'content' => $post->post_content,
                'excerpt' => $post->post_excerpt,
                'status' => $post->post_status,
                'meta' => get_post_meta($post->ID),
                'taxonomies' => array()
            );
            
            // Get taxonomy terms
            $taxonomies = get_object_taxonomies('mindful_media');
            foreach ($taxonomies as $taxonomy) {
                $terms = get_the_terms($post->ID, $taxonomy);
                if ($terms && !is_wp_error($terms)) {
                    $post_data['taxonomies'][$taxonomy] = wp_list_pluck($terms, 'name');
                }
            }
            
            $export_data['posts'][] = $post_data;
        }
        
        // Export taxonomy terms
        $taxonomies = get_object_taxonomies('mindful_media');
        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false
            ));
            
            if ($terms && !is_wp_error($terms)) {
                $export_data['taxonomies'][$taxonomy] = array();
                foreach ($terms as $term) {
                    $export_data['taxonomies'][$taxonomy][] = array(
                        'name' => $term->name,
                        'slug' => $term->slug,
                        'description' => $term->description,
                        'parent' => $term->parent
                    );
                }
            }
        }
        
        // Output JSON file
        $filename = 'mindful-media-export-' . date('Y-m-d-H-i-s') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen(json_encode($export_data)));
        
        echo json_encode($export_data, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Import plugin data
     */
    public function import_data() {
        // Verify nonce
        check_admin_referer('mindful_media_import_nonce', 'mindful_media_import_nonce_field');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'mindful-media'));
        }
        
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_die(__('No file uploaded or upload error.', 'mindful-media'));
        }
        
        // Validate file type
        $file_info = wp_check_filetype($_FILES['import_file']['name']);
        if ($file_info['ext'] !== 'json') {
            wp_die(__('Invalid file type. Please upload a JSON file.', 'mindful-media'));
        }
        
        // Validate file size (1MB max)
        if ($_FILES['import_file']['size'] > 1048576) {
            wp_die(__('File too large. Maximum size is 1MB.', 'mindful-media'));
        }
        
        $file_content = file_get_contents($_FILES['import_file']['tmp_name']);
        $import_data = json_decode($file_content, true);
        
        if (!$import_data || !isset($import_data['posts'])) {
            wp_die(__('Invalid import file format.', 'mindful-media'));
        }
        
        // Import settings
        if (isset($import_data['settings'])) {
            update_option('mindful_media_settings', $import_data['settings']);
        }
        
        // Import taxonomy terms
        if (isset($import_data['taxonomies'])) {
            foreach ($import_data['taxonomies'] as $taxonomy => $terms) {
                // Validate taxonomy name
                $taxonomy = sanitize_key($taxonomy);
                if (!taxonomy_exists($taxonomy)) {
                    continue;
                }
                
                foreach ($terms as $term_data) {
                    if (!term_exists($term_data['name'], $taxonomy)) {
                        wp_insert_term(
                            sanitize_text_field($term_data['name']),
                            $taxonomy,
                            array(
                                'slug' => sanitize_title($term_data['slug']),
                                'description' => sanitize_textarea_field($term_data['description'])
                            )
                        );
                    }
                }
            }
        }
        
        // Import posts
        $imported_count = 0;
        foreach ($import_data['posts'] as $post_data) {
            $post_id = wp_insert_post(array(
                'post_title' => sanitize_text_field($post_data['title']),
                'post_content' => wp_kses_post($post_data['content']),
                'post_excerpt' => sanitize_textarea_field($post_data['excerpt']),
                'post_type' => 'mindful_media',
                'post_status' => 'publish'
            ));
            
            if ($post_id && !is_wp_error($post_id)) {
                // Import meta data
                if (isset($post_data['meta'])) {
                    foreach ($post_data['meta'] as $meta_key => $meta_values) {
                        // Validate meta key
                        $meta_key = sanitize_key($meta_key);
                        
                        foreach ($meta_values as $meta_value) {
                            // Safely unserialize and prevent object injection
                            $unserialized = maybe_unserialize($meta_value);
                            if (is_object($unserialized)) {
                                continue; // Skip objects to prevent injection
                            }
                            add_post_meta($post_id, $meta_key, $unserialized);
                        }
                    }
                }
                
                // Import taxonomy terms
                if (isset($post_data['taxonomies'])) {
                    foreach ($post_data['taxonomies'] as $taxonomy => $term_names) {
                        $taxonomy = sanitize_key($taxonomy);
                        if (taxonomy_exists($taxonomy)) {
                            $sanitized_terms = array_map('sanitize_text_field', $term_names);
                            wp_set_object_terms($post_id, $sanitized_terms, $taxonomy);
                        }
                    }
                }
                
                $imported_count++;
            }
        }
        
        wp_redirect(admin_url('edit.php?post_type=mindful_media&page=mindful-media-import-export&imported=' . $imported_count));
        exit;
    }
} 