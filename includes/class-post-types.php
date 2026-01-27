<?php
/**
 * Custom Post Types Class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MindfulMedia_Post_Types {
    
    public function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_action('after_setup_theme', array($this, 'register_image_sizes'));
        add_filter('single_template', array($this, 'single_template'));
        add_filter('archive_template', array($this, 'archive_template'));
        add_filter('taxonomy_template', array($this, 'taxonomy_template'));
        add_filter('the_content', array($this, 'single_content_filter'), 10);
        add_action('wp_loaded', array($this, 'flush_rewrite_rules_if_needed'));
        
        // Enhanced search functionality
        add_filter('posts_search', array($this, 'extend_search'), 10, 2);
        add_filter('posts_join', array($this, 'extend_search_join'), 10, 2);
        add_filter('posts_groupby', array($this, 'extend_search_groupby'), 10, 2);
    }
    
    /**
     * Register custom image sizes from settings
     */
    public function register_image_sizes() {
        // Ensure post thumbnails are supported
        if (!current_theme_supports('post-thumbnails')) {
            add_theme_support('post-thumbnails', array('mindful_media'));
        } else {
            add_post_type_support('mindful_media', 'thumbnail');
        }
        
        $settings = MindfulMedia_Settings::get_settings();
        
        // Audio image size (square, will be letterboxed to widescreen)
        add_image_size(
            'mindful-media-audio',
            $settings['audio_image_width'],
            $settings['audio_image_height'],
            true // Hard crop
        );
        
        // Video image size (widescreen)
        add_image_size(
            'mindful-media-video',
            $settings['video_image_width'],
            $settings['video_image_height'],
            true // Hard crop
        );
        
        // Unified display size for both (16:9 widescreen)
        add_image_size(
            'mindful-media-display',
            1920,
            1080,
            false // Soft crop to maintain aspect ratio
        );
    }
    
    /**
     * Register custom post types
     */
    public function register_post_types() {
        $this->register_mindful_media_post_type();
    }
    
    /**
     * Register the main mindful_media post type
     */
    private function register_mindful_media_post_type() {
        $labels = array(
            'name'                  => _x('Mindful Media', 'Post type general name', 'mindful-media'),
            'singular_name'         => _x('Media Item', 'Post type singular name', 'mindful-media'),
            'menu_name'             => _x('Mindful Media', 'Admin Menu text', 'mindful-media'),
            'name_admin_bar'        => _x('Media Item', 'Add New on Toolbar', 'mindful-media'),
            'add_new'               => __('Add New', 'mindful-media'),
            'add_new_item'          => __('Add New Media Item', 'mindful-media'),
            'new_item'              => __('New Media Item', 'mindful-media'),
            'edit_item'             => __('Edit Media Item', 'mindful-media'),
            'view_item'             => __('View Media Item', 'mindful-media'),
            'all_items'             => __('All Media Items', 'mindful-media'),
            'search_items'          => __('Search Media Items', 'mindful-media'),
            'parent_item_colon'     => __('Parent Media Items:', 'mindful-media'),
            'not_found'             => __('No media items found.', 'mindful-media'),
            'not_found_in_trash'    => __('No media items found in Trash.', 'mindful-media'),
            'featured_image'        => _x('Featured Image', 'Overrides the "Featured Image" phrase', 'mindful-media'),
            'set_featured_image'    => _x('Set featured image', 'Overrides the "Set featured image" phrase', 'mindful-media'),
            'remove_featured_image' => _x('Remove featured image', 'Overrides the "Remove featured image" phrase', 'mindful-media'),
            'use_featured_image'    => _x('Use as featured image', 'Overrides the "Use as featured image" phrase', 'mindful-media'),
            'archives'              => _x('Media Item archives', 'The post type archive label', 'mindful-media'),
            'insert_into_item'      => _x('Insert into media item', 'Overrides the "Insert into post" phrase', 'mindful-media'),
            'uploaded_to_this_item' => _x('Uploaded to this media item', 'Overrides the "Uploaded to this post" phrase', 'mindful-media'),
            'filter_items_list'     => _x('Filter media items list', 'Screen reader text for the filter links', 'mindful-media'),
            'items_list_navigation' => _x('Media items list navigation', 'Screen reader text for the pagination', 'mindful-media'),
            'items_list'            => _x('Media items list', 'Screen reader text for the items list', 'mindful-media'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'mindful-media'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 20,
            'menu_icon'          => 'dashicons-format-audio',
            'supports'           => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'),
            'show_in_rest'       => true,
        );

        register_post_type('mindful_media', $args);
        
        // Force rewrite rules flush if this is a fresh activation
        if (get_option('mindful_media_rewrite_rules_flushed') !== 'yes') {
            flush_rewrite_rules();
            update_option('mindful_media_rewrite_rules_flushed', 'yes');
        }
    }
    
    /**
     * Flush rewrite rules if needed
     */
    public function flush_rewrite_rules_if_needed() {
        if (get_option('mindful_media_force_rewrite_flush') === 'yes') {
            flush_rewrite_rules();
            delete_option('mindful_media_force_rewrite_flush');
        }
    }
    
    /**
     * Custom single template for mindful_media posts
     */
    public function single_template($single_template) {
        global $post;
        
        if ($post->post_type === 'mindful_media') {
            $custom_template = MINDFUL_MEDIA_PLUGIN_DIR . 'templates/single-mindful-media.php';
            
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        return $single_template;
    }
    
    /**
     * Custom archive template for mindful_media post type
     */
    public function archive_template($archive_template) {
        if (is_post_type_archive('mindful_media')) {
            $custom_template = MINDFUL_MEDIA_PLUGIN_DIR . 'templates/archive-mindful_media.php';
            
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        return $archive_template;
    }
    
    /**
     * Custom taxonomy templates for media taxonomies
     */
    public function taxonomy_template($template) {
        $term = get_queried_object();
        
        if (!$term || !isset($term->taxonomy)) {
            return $template;
        }
        
        // Map of taxonomies to their template files
        $taxonomy_templates = array(
            'media_series' => 'taxonomy-media_series.php',
            'media_teacher' => 'taxonomy-media_teacher.php',
            'media_topic' => 'taxonomy-media_topic.php',
            'media_category' => 'taxonomy-media_category.php',
        );
        
        if (isset($taxonomy_templates[$term->taxonomy])) {
            $custom_template = MINDFUL_MEDIA_PLUGIN_DIR . 'templates/' . $taxonomy_templates[$term->taxonomy];
            
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Filter single post content
     */
    public function single_content_filter($content) {
        // Only modify content on the main query for mindful_media posts
        if (!is_singular('mindful_media') || !is_main_query() || !in_the_loop()) {
            return $content;
        }
        
        global $post;
        
        // Ensure we have the right post type
        if (!$post || $post->post_type !== 'mindful_media') {
            return $content;
        }
        
        // Only modify if we're in the main content area
        if (!is_main_query()) {
            return $content;
        }
        
        // Get meta data
        $duration_hours = get_post_meta($post->ID, '_mindful_media_duration_hours', true);
        $duration_minutes = get_post_meta($post->ID, '_mindful_media_duration_minutes', true);
        $recording_date = get_post_meta($post->ID, '_mindful_media_recording_date', true);
        $cta_text = get_post_meta($post->ID, '_mindful_media_cta_text', true) ?: 'WATCH';
        $media_url = get_post_meta($post->ID, '_mindful_media_url', true);
        $external_link = get_post_meta($post->ID, '_mindful_media_external_link', true);
        
        // Get taxonomies
        $categories = get_the_terms($post->ID, 'media_category');
        $media_type = get_the_terms($post->ID, 'media_type');
        $topics = get_the_terms($post->ID, 'media_topic');
        $teachers = get_the_terms($post->ID, 'media_teacher');
        
        // Format duration
        $duration_text = '';
        if ($duration_hours || $duration_minutes) {
            if ($duration_hours) $duration_text .= $duration_hours . ' hr. ';
            if ($duration_minutes) $duration_text .= $duration_minutes . ' min.';
            $duration_text = trim($duration_text);
        }
        
        // Format date
        $formatted_date = '';
        if ($recording_date) {
            $formatted_date = date('F j, Y', strtotime($recording_date));
        }
        
        // Determine action URL
        $action_url = $external_link ?: $media_url;
        
        // Check access (password protection + MemberPress)
        $is_protected = get_post_meta($post->ID, '_mindful_media_password_protected', true);
        $has_access = false;
        $access_locked = false;
        $lock_reason = '';
        $lock_message = '';
        
        if ($is_protected === '1') {
            // Check cookie for access (password protection)
            $cookie_name = 'mindful_media_access_' . $post->ID;
            $has_access = isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name] === wp_hash($post->ID . 'mindful_media_access');
            if (!$has_access) {
                $access_locked = true;
                $lock_reason = 'password';
            }
        } else {
            $has_access = true; // Not password protected
        }
        
        // Check MemberPress access if password check passed
        $required_levels = array();
        if ($has_access && class_exists('MindfulMedia_Settings')) {
            $access_result = MindfulMedia_Settings::user_can_view($post->ID);
            if (is_array($access_result) && !empty($access_result['locked'])) {
                $has_access = false;
                $access_locked = true;
                $lock_reason = $access_result['reason'];
                $lock_message = $access_result['message'];
                $required_levels = $access_result['required_levels'] ?? array();
            }
        }
        
        // Get the media player embed using the media player class
        $media_player_embed = '';
        $media_source = get_post_meta($post->ID, '_mindful_media_source', true);
        $custom_embed = get_post_meta($post->ID, '_mindful_media_custom_embed', true);
        
        // Only render player if user has access
        if ($has_access && !empty($media_url) && class_exists('MindfulMedia_Players')) {
            // Determine if audio or video for correct image size
            $media_types = get_the_terms($post->ID, 'media_type');
            $is_audio = false;
            if ($media_types && !is_wp_error($media_types)) {
                $type_name = strtolower($media_types[0]->name);
                $is_audio = (strpos($type_name, 'audio') !== false);
            }
            $image_size = $is_audio ? 'mindful-media-audio' : 'mindful-media-video';
            
            $player = new MindfulMedia_Players();
            $media_player_embed = $player->render_player($media_url, array(
                'controls' => true,
                'autoplay' => false,
                'class' => '',
                'post_id' => $post->ID,  // Pass post ID for featured image access
                'featured_image' => MindfulMedia_Shortcodes::get_media_thumbnail_url($post->ID, $image_size),
                'source' => $media_source,
                'custom_embed' => $custom_embed,
                'post_title' => $post->post_title
            ));
        }
        
        // Get custom fields
        $custom_fields = get_post_meta($post->ID, '_mindful_media_custom_fields', true);
        
        // Get settings for archive link + theme
        $settings = MindfulMedia_Settings::get_settings();
        $archive_link = $settings['archive_link'];
        $single_theme_class = (!empty($settings['modal_player_theme']) && $settings['modal_player_theme'] === 'light') ? ' light-theme' : '';
        
        // Build the enhanced content - MODAL STYLE LAYOUT
        ob_start();
        
        // Get teacher name for header
        $teacher_name = '';
        if ($teachers && !is_wp_error($teachers)) {
            $teacher_name = $teachers[0]->name;
        }
        ?>
        
        <!-- Full Screen Single Page - Modal Style -->
        <div class="mindful-media-single-fullscreen<?php echo esc_attr($single_theme_class); ?>">
            
            <!-- Header Overlay (like modal) -->
            <div class="mindful-media-single-header">
                <a href="<?php echo esc_url($archive_link); ?>" class="mindful-media-single-back mindful-media-inline-back" aria-label="<?php echo esc_attr__('Back to Media', 'mindful-media'); ?>" title="<?php echo esc_attr__('Back to Media', 'mindful-media'); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div class="mindful-media-single-header-info">
                    <h1 class="mindful-media-single-header-title">
                        <?php echo esc_html($post->post_title); ?>
                        <?php if ($teacher_name): ?>
                            <span class="mindful-media-single-header-teacher">by <?php echo esc_html($teacher_name); ?></span>
                        <?php endif; ?>
                    </h1>
                </div>
                <div class="mindful-media-single-header-actions" aria-hidden="true">
                    <span class="mindful-media-single-header-spacer"></span>
                </div>
            </div>
            
            <!-- Full Width Player Area -->
            <div class="mindful-media-single-player-area">
                <?php if ($access_locked && $lock_reason === 'membership'): ?>
                    <!-- Membership Locked Content -->
                    <div class="mindful-media-single-locked">
                        <?php if (has_post_thumbnail($post->ID)): ?>
                            <div class="mindful-media-single-locked-bg">
                                <?php echo get_the_post_thumbnail($post->ID, 'full'); ?>
                            </div>
                        <?php endif; ?>
                        <div class="mindful-media-single-locked-overlay">
                            <div class="mindful-media-lock-icon">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                                </svg>
                            </div>
                            <h2 class="mindful-media-lock-title"><?php echo esc_html($lock_message ?: __('Members Only', 'mindful-media')); ?></h2>
                            <?php 
                            // Get join URL for the first required level (or default)
                            $first_required_level = !empty($required_levels) ? reset($required_levels) : null;
                            $join_url = MindfulMedia_Settings::get_join_url($first_required_level);
                            if ($join_url): 
                            ?>
                                <a href="<?php echo esc_url($join_url); ?>" class="mindful-media-lock-cta">
                                    <?php _e('Become a Member', 'mindful-media'); ?>
                                </a>
                            <?php endif; ?>
                            <?php if (!is_user_logged_in()): ?>
                                <p class="mindful-media-lock-login">
                                    <?php _e('Already a member?', 'mindful-media'); ?>
                                    <a href="<?php echo esc_url($settings['login_url'] ?? wp_login_url(get_permalink())); ?>"><?php _e('Log in', 'mindful-media'); ?></a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($media_player_embed): ?>
                    <?php echo $media_player_embed; ?>
                <?php elseif (has_post_thumbnail($post->ID)): ?>
                    <div class="mindful-media-single-image-container">
                        <?php echo get_the_post_thumbnail($post->ID, 'full'); ?>
                        <?php if ($is_protected === '1' && !$has_access && !empty($media_url)): ?>
                            <button type="button" class="mindful-media-big-play-overlay mindful-media-play-inline" 
                                    data-post-id="<?php echo esc_attr($post->ID); ?>" 
                                    data-title="<?php echo esc_attr($post->post_title); ?>">
                                <svg viewBox="0 0 24 24" width="80" height="80"><path fill="#fff" d="M8 5v14l11-7z"/></svg>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Scroll Indicator -->
            <div class="mindful-media-single-scroll-indicator">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 9l6 6 6-6"/>
                </svg>
            </div>
            
            <!-- Content Section Below Player -->
            <div class="mindful-media-single-content-section">
                <div class="mindful-media-single-content-inner">
                    
                    <!-- Meta Information -->
                    <div class="mindful-media-single-meta-row">
                        <?php if ($formatted_date): ?>
                            <div class="mindful-media-single-meta-item">
                                <svg class="mindful-media-meta-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg>
                                <span><?php echo esc_html($formatted_date); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($duration_text): ?>
                            <div class="mindful-media-single-meta-item">
                                <svg class="mindful-media-meta-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
                                <span><?php echo esc_html($duration_text); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($media_type && !is_wp_error($media_type)): ?>
                            <div class="mindful-media-single-meta-item">
                                <svg class="mindful-media-meta-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                                <span><?php echo esc_html($media_type[0]->name); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($teachers && !is_wp_error($teachers)): ?>
                            <div class="mindful-media-single-meta-item">
                                <svg class="mindful-media-meta-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                <span>
                                    <?php 
                                    $teacher_links = array();
                                    foreach ($teachers as $teacher) {
                                        $teacher_links[] = '<a href="' . esc_url(get_term_link($teacher)) . '">' . esc_html($teacher->name) . '</a>';
                                    }
                                    echo implode(', ', $teacher_links);
                                    ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php 
                    // Engagement Section (Likes, Subscribe)
                    if (!$access_locked && class_exists('MindfulMedia_Engagement')): 
                        $engagement = new MindfulMedia_Engagement();
                        $user_id = get_current_user_id();
                        $like_count = $engagement->get_like_count($post->ID);
                        $user_liked = $engagement->user_has_liked($user_id, $post->ID);
                        $login_url = $settings['login_url'] ?? wp_login_url(get_permalink());
                    ?>
                    <div class="mindful-media-engagement">
                        <div class="mindful-media-engagement-actions">
                            <?php if (!empty($settings['enable_likes'])): ?>
                                <?php if ($user_id): ?>
                                    <button type="button" class="mm-like-btn <?php echo $user_liked ? 'liked' : ''; ?>" data-post-id="<?php echo esc_attr($post->ID); ?>">
                                        <svg viewBox="0 0 24 24"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>
                                        <span class="mm-like-count"><?php echo $like_count > 0 ? esc_html($like_count) : ''; ?></span>
                                    </button>
                                <?php else: ?>
                                    <a href="<?php echo esc_url($login_url); ?>" class="mm-like-btn" title="<?php esc_attr_e('Sign in to like', 'mindful-media'); ?>">
                                        <svg viewBox="0 0 24 24"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>
                                        <span class="mm-like-count"><?php echo $like_count > 0 ? esc_html($like_count) : ''; ?></span>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php 
                            // Subscribe button for teacher
                            if (!empty($settings['enable_subscriptions']) && !empty($settings['allow_subscription_teachers']) && $teachers && !is_wp_error($teachers)):
                                $teacher = $teachers[0];
                                $is_subscribed = $user_id ? $engagement->user_is_subscribed($user_id, $teacher->term_id, 'media_teacher') : false;
                            ?>
                                <?php if ($user_id): ?>
                                    <button type="button" class="mm-subscribe-btn <?php echo $is_subscribed ? 'subscribed' : ''; ?>" 
                                            data-object-id="<?php echo esc_attr($teacher->term_id); ?>" 
                                            data-object-type="media_teacher">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <?php if ($is_subscribed): ?>
                                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                            <?php else: ?>
                                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                            <?php endif; ?>
                                        </svg>
                                        <span class="mm-subscribe-text"><?php echo $is_subscribed ? __('Subscribed', 'mindful-media') : __('Subscribe', 'mindful-media'); ?></span>
                                    </button>
                                <?php else: ?>
                                    <a href="<?php echo esc_url($login_url); ?>" class="mm-subscribe-btn" title="<?php esc_attr_e('Sign in to subscribe', 'mindful-media'); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                        </svg>
                                        <span class="mm-subscribe-text"><?php _e('Subscribe', 'mindful-media'); ?></span>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Description -->
                    <div class="mindful-media-single-description">
                        <?php echo wpautop($content); ?>
                    </div>
                    
                    <!-- Categories and Topics - Chip Style -->
                    <?php if (($categories && !is_wp_error($categories)) || ($topics && !is_wp_error($topics))): ?>
                        <div class="mindful-media-single-taxonomies">
                            <?php if ($categories && !is_wp_error($categories)): ?>
                                <?php foreach ($categories as $category): ?>
                                    <a href="<?php echo esc_url(get_term_link($category)); ?>" class="mindful-media-single-chip chip-category">
                                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>
                                        <?php echo esc_html($category->name); ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <?php if ($topics && !is_wp_error($topics)): ?>
                                <?php foreach ($topics as $topic): ?>
                                    <a href="<?php echo esc_url(get_term_link($topic)); ?>" class="mindful-media-single-chip chip-topic">
                                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/></svg>
                                        <?php echo esc_html($topic->name); ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Custom Fields (if any) -->
                    <?php if ($custom_fields && is_array($custom_fields) && !empty(array_filter($custom_fields))): ?>
                        <div class="mindful-media-single-custom-fields">
                            <h4><?php _e('Additional Information', 'mindful-media'); ?></h4>
                            <?php foreach ($custom_fields as $field_key => $field_value): ?>
                                <?php if (!empty($field_value)): ?>
                                    <div class="mindful-media-single-custom-field">
                                        <strong><?php echo esc_html(ucwords(str_replace('_', ' ', $field_key))); ?>:</strong>
                                        <span><?php echo esc_html($field_value); ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Playlist Information -->
                    <?php
                    $playlist_terms = get_the_terms($post->ID, 'media_series');
                    if ($playlist_terms && !is_wp_error($playlist_terms)):
                        $playlist = $playlist_terms[0];
                        $playlist_order = get_post_meta($post->ID, '_mindful_media_series_order', true);
                        
                        // Get all items in this playlist
                        $playlist_items = get_posts(array(
                            'post_type' => 'mindful_media',
                            'posts_per_page' => -1,
                            'tax_query' => array(
                                array(
                                    'taxonomy' => 'media_series',
                                    'field' => 'term_id',
                                    'terms' => $playlist->term_id,
                                )
                            ),
                            'meta_key' => '_mindful_media_series_order',
                            'orderby' => 'meta_value_num',
                            'order' => 'ASC'
                        ));
                        
                        if (!empty($playlist_items)):
                    ?>
                        <div class="mindful-media-playlist-info">
                            <h4>📋 Playlist: <?php echo esc_html($playlist->name); ?></h4>
                            <?php if ($playlist->description): ?>
                                <p class="playlist-description"><?php echo esc_html($playlist->description); ?></p>
                            <?php endif; ?>
                            
                            <div class="playlist-items">
                                <?php 
                                $current_index = 0;
                                foreach ($playlist_items as $index => $item):
                                    $item_order = get_post_meta($item->ID, '_mindful_media_series_order', true);
                                    $is_current = ($item->ID == $post->ID);
                                    if ($is_current) $current_index = $index;
                                    $item_class = $is_current ? 'playlist-item current' : 'playlist-item';
                                ?>
                                    <div class="<?php echo $item_class; ?>">
                                        <span class="item-number"><?php echo ($item_order ? $item_order : ($index + 1)); ?>.</span>
                                        <?php if ($is_current): ?>
                                            <strong><?php echo esc_html($item->post_title); ?> ▶️</strong>
                                        <?php else: ?>
                                            <a href="<?php echo get_permalink($item->ID); ?>"><?php echo esc_html($item->post_title); ?></a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="playlist-navigation">
                                <?php if ($current_index > 0): ?>
                                    <a href="<?php echo get_permalink($playlist_items[$current_index - 1]->ID); ?>" class="playlist-nav-btn prev">
                                        ← Previous
                                    </a>
                                <?php endif; ?>
                                
                                <span class="playlist-progress">
                                    <?php echo ($current_index + 1); ?> of <?php echo count($playlist_items); ?>
                                </span>
                                
                                <?php if ($current_index < count($playlist_items) - 1): ?>
                                    <a href="<?php echo get_permalink($playlist_items[$current_index + 1]->ID); ?>" class="playlist-nav-btn next">
                                        Next →
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php 
                        endif;
                    endif; 
                    ?>
                    
                    <!-- More Media Section -->
                    <?php
                    // Get related media items based on categories, topics, or teachers
                    $related_args = array(
                        'post_type' => 'mindful_media',
                        'posts_per_page' => 4,
                        'post__not_in' => array($post->ID),
                        'post_status' => 'publish',
                        'orderby' => 'rand',
                        'meta_query' => array(
                            'relation' => 'OR',
                            array(
                                'key' => '_mindful_media_hide_from_archive',
                                'compare' => 'NOT EXISTS'
                            ),
                            array(
                                'key' => '_mindful_media_hide_from_archive',
                                'value' => '1',
                                'compare' => '!='
                            )
                        )
                    );
                    
                    // Try to find related items by teacher, topic, or category
                    $tax_query = array('relation' => 'OR');
                    if ($teachers && !is_wp_error($teachers)) {
                        $tax_query[] = array(
                            'taxonomy' => 'media_teacher',
                            'field' => 'term_id',
                            'terms' => wp_list_pluck($teachers, 'term_id')
                        );
                    }
                    if ($topics && !is_wp_error($topics)) {
                        $tax_query[] = array(
                            'taxonomy' => 'media_topic',
                            'field' => 'term_id',
                            'terms' => wp_list_pluck($topics, 'term_id')
                        );
                    }
                    if ($categories && !is_wp_error($categories)) {
                        $tax_query[] = array(
                            'taxonomy' => 'media_category',
                            'field' => 'term_id',
                            'terms' => wp_list_pluck($categories, 'term_id')
                        );
                    }
                    
                    if (count($tax_query) > 1) {
                        $related_args['tax_query'] = $tax_query;
                    }
                    
                    $related_query = new WP_Query($related_args);
                    
                    // If no related items found, get any recent items
                    if (!$related_query->have_posts()) {
                        unset($related_args['tax_query']);
                        $related_args['orderby'] = 'date';
                        $related_args['order'] = 'DESC';
                        $related_query = new WP_Query($related_args);
                    }
                    
                    if ($related_query->have_posts()):
                    ?>
                    <div class="mindful-media-more-section">
                        <h3><?php _e('More Media', 'mindful-media'); ?></h3>
                        <div class="mindful-media-more-grid">
                            <?php while ($related_query->have_posts()): $related_query->the_post(); 
                                $rel_id = get_the_ID();
                                $rel_duration_h = get_post_meta($rel_id, '_mindful_media_duration_hours', true);
                                $rel_duration_m = get_post_meta($rel_id, '_mindful_media_duration_minutes', true);
                                $rel_duration = '';
                                if ($rel_duration_h) $rel_duration .= $rel_duration_h . ':';
                                if ($rel_duration_m) $rel_duration .= str_pad($rel_duration_m, 2, '0', STR_PAD_LEFT);
                                elseif ($rel_duration_h) $rel_duration .= '00';
                                
                                $rel_teacher = '';
                                $rel_teachers = get_the_terms($rel_id, 'media_teacher');
                                if ($rel_teachers && !is_wp_error($rel_teachers)) {
                                    $rel_teacher = $rel_teachers[0]->name;
                                }
                            ?>
                            <div class="mindful-media-more-card">
                                <a href="<?php the_permalink(); ?>">
                                    <div class="mindful-media-more-card-thumbnail">
                                        <?php if (has_post_thumbnail()): ?>
                                            <?php the_post_thumbnail('medium'); ?>
                                        <?php else: ?>
                                            <div style="width:100%;height:100%;background:#272727;display:flex;align-items:center;justify-content:center;">
                                                <svg width="40" height="40" viewBox="0 0 24 24" fill="#666"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($rel_duration): ?>
                                            <span class="mindful-media-more-card-duration"><?php echo esc_html($rel_duration); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mindful-media-more-card-info">
                                        <h4 class="mindful-media-more-card-title"><?php the_title(); ?></h4>
                                        <?php if ($rel_teacher): ?>
                                            <div class="mindful-media-more-card-meta"><?php echo esc_html($rel_teacher); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </div>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Comments Section
                    if (!$access_locked && !empty($settings['enable_comments']) && class_exists('MindfulMedia_Engagement')): 
                        $engagement = isset($engagement) ? $engagement : new MindfulMedia_Engagement();
                        $comment_count = $engagement->get_comment_count($post->ID);
                        $comments = $engagement->get_comments($post->ID, 10);
                        $user_id = get_current_user_id();
                        $login_url = $settings['login_url'] ?? wp_login_url(get_permalink());
                    ?>
                    <div class="mm-comments">
                        <div class="mm-comments-header">
                            <span class="mm-comments-count" data-post-id="<?php echo esc_attr($post->ID); ?>">
                                <?php echo esc_html($comment_count); ?> <?php echo $comment_count === 1 ? __('Comment', 'mindful-media') : __('Comments', 'mindful-media'); ?>
                            </span>
                        </div>
                        
                        <?php if ($user_id): ?>
                            <div class="mm-comment-composer" data-post-id="<?php echo esc_attr($post->ID); ?>">
                                <div class="mm-comment-composer-avatar">
                                    <?php echo get_avatar($user_id, 40); ?>
                                </div>
                                <div class="mm-comment-composer-form">
                                    <textarea class="mm-comment-input" placeholder="<?php esc_attr_e('Add a comment...', 'mindful-media'); ?>" rows="1"></textarea>
                                    <div class="mm-comment-composer-actions">
                                        <button type="button" class="mm-comment-cancel-btn"><?php _e('Cancel', 'mindful-media'); ?></button>
                                        <button type="button" class="mm-comment-submit-btn" disabled><?php _e('Comment', 'mindful-media'); ?></button>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="mm-engagement-guest-prompt">
                                <p><?php _e('Sign in to leave a comment', 'mindful-media'); ?></p>
                                <a href="<?php echo esc_url($login_url); ?>" class="mm-btn"><?php _e('Sign In', 'mindful-media'); ?></a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mm-comments-list" data-post-id="<?php echo esc_attr($post->ID); ?>">
                            <?php if (empty($comments)): ?>
                                <p class="mm-comments-empty"><?php _e('No comments yet. Be the first to comment!', 'mindful-media'); ?></p>
                            <?php else: ?>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="mm-comment" data-comment-id="<?php echo esc_attr($comment->id); ?>">
                                        <div class="mm-comment-avatar">
                                            <img src="<?php echo esc_url($comment->avatar_url); ?>" alt="<?php echo esc_attr($comment->display_name); ?>">
                                        </div>
                                        <div class="mm-comment-body">
                                            <div class="mm-comment-header">
                                                <span class="mm-comment-author"><?php echo esc_html($comment->display_name); ?></span>
                                                <span class="mm-comment-time"><?php echo esc_html($comment->time_ago); ?></span>
                                            </div>
                                            <div class="mm-comment-content"><?php echo esc_html($comment->content); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div><!-- .mindful-media-single-content-inner -->
            </div><!-- .mindful-media-single-content-section -->
            
        </div><!-- .mindful-media-single-fullscreen -->
        <?php
        
        // Enqueue frontend scripts for inline player functionality
        wp_enqueue_style('mindful-media-frontend', MINDFUL_MEDIA_PLUGIN_URL . 'public/css/frontend.css', array(), MINDFUL_MEDIA_VERSION);
        wp_enqueue_script('mindful-media-frontend', MINDFUL_MEDIA_PLUGIN_URL . 'public/js/frontend.js', array('jquery'), MINDFUL_MEDIA_VERSION, true);
        $settings = MindfulMedia_Settings::get_settings();
        wp_localize_script('mindful-media-frontend', 'mindfulMediaAjax', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mindful_media_ajax_nonce'),
            'modalShowMoreMedia' => $settings['modal_show_more_media'] ?? '1',
            'youtubeHideEndScreen' => $settings['youtube_hide_end_screen'] ?? '0'
        ));
        
        $enhanced_content = ob_get_clean();
        
        return $enhanced_content;
    }
    
    /**
     * Extend search to include taxonomy terms (teachers, topics)
     */
    public function extend_search($search, $query) {
        global $wpdb;
        
        // Get the search term
        $search_term = $query->get('s');
        if (empty($search_term)) {
            return $search;
        }
        
        // Only apply to mindful_media post type queries
        $post_type = $query->get('post_type');
        if ($post_type !== 'mindful_media' && !in_array('mindful_media', (array)$post_type)) {
            return $search;
        }
        
        // Only modify if it's a search query
        if (!$query->is_search()) {
            return $search;
        }
        
        // Build custom search query
        $search_term_like = $wpdb->esc_like($search_term);
        $search_term_like = '%' . $search_term_like . '%';
        
        // Create search conditions for taxonomies
        $search_conditions = array();
        
        // Original search (title and content) - remove the leading AND
        if (!empty($search)) {
            $original_search = trim($search);
            // Remove leading 'AND' if present
            if (strpos($original_search, 'AND') === 0) {
                $original_search = trim(substr($original_search, 3));
            }
            $search_conditions[] = '(' . $original_search . ')';
        }
        
        // Add taxonomy search (teacher and topic names)
        $search_conditions[] = $wpdb->prepare(
            "(tt.taxonomy IN ('media_teacher', 'media_topic') AND t.name LIKE %s)",
            $search_term_like
        );
        
        // Combine all search conditions with OR
        if (!empty($search_conditions)) {
            $search = ' AND (' . implode(' OR ', $search_conditions) . ') ';
        }
        
        
        return $search;
    }
    
    /**
     * Join taxonomy tables for search
     */
    public function extend_search_join($join, $query) {
        global $wpdb;
        
        // Get the search term
        $search_term = $query->get('s');
        if (empty($search_term)) {
            return $join;
        }
        
        // Only apply to mindful_media post type queries
        $post_type = $query->get('post_type');
        if ($post_type !== 'mindful_media' && !in_array('mindful_media', (array)$post_type)) {
            return $join;
        }
        
        // Only modify if it's a search query
        if (!$query->is_search()) {
            return $join;
        }
        
        // Join term relationships, term taxonomy, and terms tables
        $join .= " LEFT JOIN {$wpdb->term_relationships} tr ON {$wpdb->posts}.ID = tr.object_id ";
        $join .= " LEFT JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id ";
        $join .= " LEFT JOIN {$wpdb->terms} t ON t.term_id = tt.term_id ";
        
        
        return $join;
    }
    
    /**
     * Group by post ID to avoid duplicates
     */
    public function extend_search_groupby($groupby, $query) {
        global $wpdb;
        
        // Get the search term
        $search_term = $query->get('s');
        if (empty($search_term)) {
            return $groupby;
        }
        
        // Only apply to mindful_media post type queries
        $post_type = $query->get('post_type');
        if ($post_type !== 'mindful_media' && !in_array('mindful_media', (array)$post_type)) {
            return $groupby;
        }
        
        // Only modify if it's a search query
        if (!$query->is_search()) {
            return $groupby;
        }
        
        // Group by post ID to prevent duplicate results
        if (!$groupby) {
            $groupby = "{$wpdb->posts}.ID";
        }
        
        return $groupby;
    }
    
    /**
     * Helper function to check if query is for mindful_media
     */
    private function is_mindful_media_query($query) {
        $post_type = $query->get('post_type');
        return $post_type === 'mindful_media' || in_array('mindful_media', (array)$post_type);
    }
    
} 