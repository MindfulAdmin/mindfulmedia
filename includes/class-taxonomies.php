<?php
/**
 * Custom Taxonomies Class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MindfulMedia_Taxonomies {
    
    public function __construct() {
        add_action('init', array($this, 'register_taxonomies'));
        add_filter('template_include', array($this, 'load_teacher_template'));
        
        // Add featured image support for playlists (media_series)
        add_action('media_series_add_form_fields', array($this, 'add_playlist_image_field'));
        add_action('media_series_edit_form_fields', array($this, 'edit_playlist_image_field'));
        add_action('created_media_series', array($this, 'save_playlist_image'));
        add_action('edited_media_series', array($this, 'save_playlist_image'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_media_uploader'));
        add_filter('manage_edit-media_series_columns', array($this, 'add_playlist_image_column'));
        add_filter('manage_media_series_custom_column', array($this, 'playlist_image_column_content'), 10, 3);
        
        // Add featured image support for teachers (media_teacher)
        add_action('media_teacher_add_form_fields', array($this, 'add_taxonomy_image_field'));
        add_action('media_teacher_edit_form_fields', array($this, 'edit_taxonomy_image_field'));
        add_action('created_media_teacher', array($this, 'save_taxonomy_image'));
        add_action('edited_media_teacher', array($this, 'save_taxonomy_image'));
        add_filter('manage_edit-media_teacher_columns', array($this, 'add_taxonomy_image_column'));
        add_filter('manage_media_teacher_custom_column', array($this, 'taxonomy_image_column_content'), 10, 3);
        
        // Add featured image support for topics (media_topic)
        add_action('media_topic_add_form_fields', array($this, 'add_taxonomy_image_field'));
        add_action('media_topic_edit_form_fields', array($this, 'edit_taxonomy_image_field'));
        add_action('created_media_topic', array($this, 'save_taxonomy_image'));
        add_action('edited_media_topic', array($this, 'save_taxonomy_image'));
        add_filter('manage_edit-media_topic_columns', array($this, 'add_taxonomy_image_column'));
        add_filter('manage_media_topic_custom_column', array($this, 'taxonomy_image_column_content'), 10, 3);
        
        // Order playlist items by series_order ASC (oldest/first items first)
        add_action('pre_get_posts', array($this, 'order_playlist_items'));
    }
    
    /**
     * Order playlist items by series_order meta key (ASC)
     * This ensures playlist items appear in the correct order (Session 1, 2, 3...)
     */
    public function order_playlist_items($query) {
        // Only modify main query on frontend for media_series taxonomy archive
        if (!is_admin() && $query->is_main_query() && is_tax('media_series')) {
            $query->set('posts_per_page', -1); // Show ALL items in playlist
            
            // Use meta_query to include posts without the meta key
            // Order by meta_value_num with COALESCE to handle missing values
            $query->set('meta_query', array(
                'relation' => 'OR',
                array(
                    'key' => '_mindful_media_series_order',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => '_mindful_media_series_order',
                    'compare' => 'NOT EXISTS'
                )
            ));
            $query->set('orderby', array(
                'meta_value_num' => 'ASC',
                'date' => 'ASC'
            ));
        }
    }
    
    /**
     * Register custom taxonomies
     */
    public function register_taxonomies() {
        // Get enabled taxonomies from settings
        $enabled_taxonomies = $this->get_enabled_taxonomies();
        
        foreach ($enabled_taxonomies as $taxonomy_key) {
            switch ($taxonomy_key) {
                case 'media_category':
                    $this->register_media_category();
                    break;
                case 'media_type':
                    $this->register_media_type();
                    break;
                case 'media_topic':
                    $this->register_media_topic();
                    break;
                case 'media_duration':
                    $this->register_media_duration();
                    break;
                case 'media_teacher':
                    $this->register_media_teacher();
                    break;
                case 'media_tags':
                    $this->register_media_tags();
                    break;
                case 'media_series':
                    $this->register_media_series();
                    break;
                case 'media_year':
                    $this->register_media_year();
                    break;
            }
        }
        
        // Always register series and year (for new features)
        $this->register_media_series();
        $this->register_media_year();
    }
    
    /**
     * Register media category taxonomy
     */
    private function register_media_category() {
        $labels = array(
            'name'              => _x('Categories', 'taxonomy general name', 'mindful-media'),
            'singular_name'     => _x('Category', 'taxonomy singular name', 'mindful-media'),
            'search_items'      => __('Search Categories', 'mindful-media'),
            'all_items'         => __('All Categories', 'mindful-media'),
            'parent_item'       => __('Parent Category', 'mindful-media'),
            'parent_item_colon' => __('Parent Category:', 'mindful-media'),
            'edit_item'         => __('Edit Category', 'mindful-media'),
            'update_item'       => __('Update Category', 'mindful-media'),
            'add_new_item'      => __('Add New Category', 'mindful-media'),
            'new_item_name'     => __('New Category Name', 'mindful-media'),
            'menu_name'         => __('Categories', 'mindful-media'),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'media-category'),
            'show_in_rest'      => true,
        );

        register_taxonomy('media_category', array('mindful_media'), $args);
        
        // Add default categories
        $this->add_default_categories();
    }
    
    /**
     * Register media type taxonomy
     */
    private function register_media_type() {
        $labels = array(
            'name'          => _x('Media Types', 'taxonomy general name', 'mindful-media'),
            'singular_name' => _x('Media Type', 'taxonomy singular name', 'mindful-media'),
            'menu_name'     => __('Media Types', 'mindful-media'),
        );

        $args = array(
            'hierarchical'      => true, // Changed to true to get checkbox interface like Categories
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'media-type'),
            'show_in_rest'      => true,
        );

        register_taxonomy('media_type', array('mindful_media'), $args);
        
        // Add default media types
        $this->add_default_media_types();
    }
    
    /**
     * Register topic taxonomy
     */
    private function register_media_topic() {
        $labels = array(
            'name'          => _x('Topics', 'taxonomy general name', 'mindful-media'),
            'singular_name' => _x('Topic', 'taxonomy singular name', 'mindful-media'),
            'menu_name'     => __('Topics', 'mindful-media'),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'topic'),
            'show_in_rest'      => true,
        );

        register_taxonomy('media_topic', array('mindful_media'), $args);
        
        // Add default topics
        $this->add_default_topics();
    }
    
    /**
     * Register duration taxonomy
     */
    private function register_media_duration() {
        $labels = array(
            'name'          => _x('Duration', 'taxonomy general name', 'mindful-media'),
            'singular_name' => _x('Duration', 'taxonomy singular name', 'mindful-media'),
            'menu_name'     => __('Duration', 'mindful-media'),
        );

        $args = array(
            'hierarchical'      => true, // Changed to true to get checkbox interface like Categories
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'duration'),
            'show_in_rest'      => true,
        );

        register_taxonomy('media_duration', array('mindful_media'), $args);
        
        // Add default durations
        $this->add_default_durations();
    }
    
    /**
     * Register teacher taxonomy
     */
    private function register_media_teacher() {
        $labels = array(
            'name'              => _x('Teachers', 'taxonomy general name', 'mindful-media'),
            'singular_name'     => _x('Teacher', 'taxonomy singular name', 'mindful-media'),
            'search_items'      => __('Search Teachers', 'mindful-media'),
            'all_items'         => __('All Teachers', 'mindful-media'),
            'edit_item'         => __('Edit Teacher', 'mindful-media'),
            'update_item'       => __('Update Teacher', 'mindful-media'),
            'add_new_item'      => __('Add New Teacher', 'mindful-media'),
            'new_item_name'     => __('New Teacher Name', 'mindful-media'),
            'menu_name'         => __('Teachers', 'mindful-media'),
        );

        $args = array(
            'hierarchical'      => false,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'teacher'),
            'show_in_rest'      => true,
        );

        register_taxonomy('media_teacher', array('mindful_media'), $args);
    }
    
    /**
     * Register tags taxonomy
     */
    private function register_media_tags() {
        $labels = array(
            'name'          => _x('Tags', 'taxonomy general name', 'mindful-media'),
            'singular_name' => _x('Tag', 'taxonomy singular name', 'mindful-media'),
            'menu_name'     => __('Tags', 'mindful-media'),
        );

        $args = array(
            'hierarchical'      => false,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'media-tag'),
            'show_in_rest'      => true,
            'meta_box_cb'       => false, // Remove default meta box, we'll add custom ones
        );

        register_taxonomy('media_tags', array('mindful_media'), $args);
    }
    
    /**
     * Add default categories
     */
    private function add_default_categories() {
        $categories = array(
            'Articles', 'Courses', 'Meditations', 'Practice Guides', 'Quotes', 'Recipes', 'Talks'
        );
        
        foreach ($categories as $category) {
            if (!term_exists($category, 'media_category')) {
                wp_insert_term($category, 'media_category');
            }
        }
    }
    
    /**
     * Add default media types
     */
    private function add_default_media_types() {
        $types = array('Audio', 'Video', 'Writing');
        
        foreach ($types as $type) {
            if (!term_exists($type, 'media_type')) {
                wp_insert_term($type, 'media_type');
            }
        }
    }
    
    /**
     * Add default topics
     */
    private function add_default_topics() {
        $topics = array(
            'Dharma Study', 'For Beginners', 'Heart Practices', 
            'Movement', 'Psychology & Wellness', 'Social Engagement'
        );
        
        foreach ($topics as $topic) {
            if (!term_exists($topic, 'media_topic')) {
                wp_insert_term($topic, 'media_topic');
            }
        }
    }
    
    /**
     * Add default durations
     */
    private function add_default_durations() {
        $durations = array('Under 1 Hour', '1-3 Hours', '3+ Hours');
        
        foreach ($durations as $duration) {
            if (!term_exists($duration, 'media_duration')) {
                wp_insert_term($duration, 'media_duration');
            }
        }
    }
    
    /**
     * Register playlists taxonomy (Feature #3)
     * Now hierarchical to support Series > Playlist structure
     */
    private function register_media_series() {
        $labels = array(
            'name'              => _x('Playlists', 'taxonomy general name', 'mindful-media'),
            'singular_name'     => _x('Playlist', 'taxonomy singular name', 'mindful-media'),
            'search_items'      => __('Search Playlists', 'mindful-media'),
            'all_items'         => __('All Playlists', 'mindful-media'),
            'parent_item'       => __('Parent Series', 'mindful-media'),
            'parent_item_colon' => __('Parent Series:', 'mindful-media'),
            'edit_item'         => __('Edit Playlist', 'mindful-media'),
            'update_item'       => __('Update Playlist', 'mindful-media'),
            'add_new_item'      => __('Add New Playlist', 'mindful-media'),
            'new_item_name'     => __('New Playlist Name', 'mindful-media'),
            'menu_name'         => __('Playlists', 'mindful-media'),
        );

        $args = array(
            'hierarchical'      => true, // Changed to hierarchical to support Series (parent) > Playlist (child) structure
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array(
                'slug' => 'playlist',
                'hierarchical' => true // Enable hierarchical URLs: /playlist/series-name/playlist-name
            ),
            'show_in_rest'      => true,
        );

        register_taxonomy('media_series', array('mindful_media'), $args);
    }
    
    /**
     * Register year taxonomy (Feature #7)
     */
    private function register_media_year() {
        $labels = array(
            'name'              => _x('Years', 'taxonomy general name', 'mindful-media'),
            'singular_name'     => _x('Year', 'taxonomy singular name', 'mindful-media'),
            'menu_name'         => __('Years', 'mindful-media'),
        );

        $args = array(
            'hierarchical'      => true, // Allows decades as parents
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'year'),
            'show_in_rest'      => true,
            'meta_box_cb'       => false, // Hide meta box (auto-assigned)
        );

        register_taxonomy('media_year', array('mindful_media'), $args);
    }
    
    /**
     * Get enabled taxonomies from settings
     */
    private function get_enabled_taxonomies() {
        $settings = get_option('mindful_media_settings', array());
        $default_taxonomies = array(
            'media_category',
            'media_type', 
            'media_topic',
            'media_duration',
            'media_teacher',
            'media_tags'
        );
        
        return isset($settings['enabled_taxonomies']) ? $settings['enabled_taxonomies'] : $default_taxonomies;
    }
    
    /**
     * Load custom templates for taxonomy archives
     */
    public function load_teacher_template($template) {
        // Load teacher archive template
        if (is_tax('media_teacher')) {
            $plugin_template = MINDFUL_MEDIA_PLUGIN_DIR . 'templates/taxonomy-media_teacher.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        // Load topic archive template
        if (is_tax('media_topic')) {
            $plugin_template = MINDFUL_MEDIA_PLUGIN_DIR . 'templates/taxonomy-media_topic.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        // Load playlist archive template
        if (is_tax('media_series')) {
            $plugin_template = MINDFUL_MEDIA_PLUGIN_DIR . 'templates/taxonomy-media_series.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Get all available taxonomies
     */
    public static function get_available_taxonomies() {
        return array(
            'media_category' => array(
                'name' => __('Categories', 'mindful-media'),
                'description' => __('Content categories like Articles, Courses, Meditations, etc.', 'mindful-media'),
                'hierarchical' => true
            ),
            'media_type' => array(
                'name' => __('Media Types', 'mindful-media'),
                'description' => __('Type of media: Audio, Video, Writing', 'mindful-media'),
                'hierarchical' => false
            ),
            'media_topic' => array(
                'name' => __('Topics', 'mindful-media'),
                'description' => __('Subject topics like Dharma Study, Heart Practices, etc.', 'mindful-media'),
                'hierarchical' => true
            ),
            'media_duration' => array(
                'name' => __('Duration', 'mindful-media'),
                'description' => __('Length categories for content', 'mindful-media'),
                'hierarchical' => false
            ),
            'media_teacher' => array(
                'name' => __('Teachers', 'mindful-media'),
                'description' => __('Teacher/Author taxonomy for filtering and archives', 'mindful-media'),
                'hierarchical' => false
            ),
            'media_tags' => array(
                'name' => __('Tags', 'mindful-media'),
                'description' => __('Flexible tags for content organization', 'mindful-media'),
                'hierarchical' => false
            )
        );
    }
    
    /**
     * Add featured image field to playlist add form
     */
    public function add_playlist_image_field() {
        ?>
        <div class="form-field term-thumbnail-wrap">
            <label><?php _e('Featured Image', 'mindful-media'); ?></label>
            <div id="playlist-thumbnail-preview" style="margin-bottom: 10px;">
                <img src="" style="max-width: 200px; height: auto; display: none;" />
            </div>
            <input type="hidden" id="playlist-thumbnail-id" name="playlist_thumbnail_id" value="">
            <button type="button" class="button button-secondary" id="playlist-thumbnail-upload">
                <?php _e('Set Featured Image', 'mindful-media'); ?>
            </button>
            <button type="button" class="button button-secondary" id="playlist-thumbnail-remove" style="display: none;">
                <?php _e('Remove Image', 'mindful-media'); ?>
            </button>
            <p class="description"><?php _e('Set a featured image for this playlist. If not set, the first item\'s image will be used.', 'mindful-media'); ?></p>
        </div>
        
        <div class="form-field term-password-wrap">
            <label>
                <input type="checkbox" id="playlist-password-enabled" name="playlist_password_enabled" value="1">
                <?php _e('Password Protect This Playlist', 'mindful-media'); ?>
            </label>
            <p class="description"><?php _e('Require a password to view any content in this playlist.', 'mindful-media'); ?></p>
        </div>
        
        <div class="form-field term-password-field" id="playlist-password-field" style="display: none;">
            <label><?php _e('Playlist Password', 'mindful-media'); ?></label>
            <input type="text" id="playlist-password" name="playlist_password" value="" style="width: 300px;">
            <p class="description"><?php _e('Enter the password users will need to access this playlist. Note: Individual videos may have their own passwords as well.', 'mindful-media'); ?></p>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#playlist-password-enabled').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#playlist-password-field').slideDown();
                } else {
                    $('#playlist-password-field').slideUp();
                    $('#playlist-password').val('');
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Add featured image field to playlist edit form
     */
    public function edit_playlist_image_field($term) {
        $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
        $thumbnail_url = '';
        if ($thumbnail_id) {
            $thumbnail_url = wp_get_attachment_url($thumbnail_id);
        }
        
        $password_enabled = get_term_meta($term->term_id, 'password_enabled', true);
        $playlist_password = get_term_meta($term->term_id, 'playlist_password', true);
        ?>
        <tr class="form-field term-thumbnail-wrap">
            <th scope="row">
                <label><?php _e('Featured Image', 'mindful-media'); ?></label>
            </th>
            <td>
                <div id="playlist-thumbnail-preview" style="margin-bottom: 10px;">
                    <img src="<?php echo esc_url($thumbnail_url); ?>" style="max-width: 200px; height: auto; <?php echo $thumbnail_url ? '' : 'display: none;'; ?>" />
                </div>
                <input type="hidden" id="playlist-thumbnail-id" name="playlist_thumbnail_id" value="<?php echo esc_attr($thumbnail_id); ?>">
                <button type="button" class="button button-secondary" id="playlist-thumbnail-upload">
                    <?php _e($thumbnail_id ? 'Change Image' : 'Set Featured Image', 'mindful-media'); ?>
                </button>
                <button type="button" class="button button-secondary" id="playlist-thumbnail-remove" style="<?php echo $thumbnail_id ? '' : 'display: none;'; ?>">
                    <?php _e('Remove Image', 'mindful-media'); ?>
                </button>
                <p class="description"><?php _e('Set a featured image for this playlist. If not set, the first item\'s image will be used.', 'mindful-media'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-password-wrap">
            <th scope="row">
                <label><?php _e('Password Protection', 'mindful-media'); ?></label>
            </th>
            <td>
                <label>
                    <input type="checkbox" id="playlist-password-enabled" name="playlist_password_enabled" value="1" <?php checked($password_enabled, '1'); ?>>
                    <?php _e('Password Protect This Playlist', 'mindful-media'); ?>
                </label>
                <p class="description"><?php _e('Require a password to view any content in this playlist.', 'mindful-media'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-password-field" id="playlist-password-field" style="<?php echo $password_enabled ? '' : 'display: none;'; ?>">
            <th scope="row">
                <label><?php _e('Playlist Password', 'mindful-media'); ?></label>
            </th>
            <td>
                <input type="text" id="playlist-password" name="playlist_password" value="<?php echo esc_attr($playlist_password); ?>" class="regular-text">
                <p class="description"><?php _e('Enter the password users will need to access this playlist. Note: Individual videos may have their own passwords as well.', 'mindful-media'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-hide-archive-wrap">
            <th scope="row">
                <label><?php _e('Archive Display', 'mindful-media'); ?></label>
            </th>
            <td>
                <label>
                    <input type="checkbox" id="playlist-hide-from-archive" name="playlist_hide_from_archive" value="1" <?php checked(get_term_meta($term->term_id, 'hide_from_archive', true), '1'); ?>>
                    <?php _e('Hide from Archive', 'mindful-media'); ?>
                </label>
                <p class="description"><?php _e('If enabled, this playlist will not appear in the main media archive (useful for password-protected or private playlists).', 'mindful-media'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-shortcode-wrap">
            <th scope="row">
                <label><?php _e('Shortcode', 'mindful-media'); ?></label>
            </th>
            <td>
                <input type="text" value='[mindful_media playlist="<?php echo esc_attr($term->slug); ?>"]' readonly class="regular-text" onclick="this.select();" style="font-family: monospace; background: #f5f5f5; cursor: pointer;">
                <p class="description"><?php _e('Copy this shortcode to embed this playlist anywhere on your site.', 'mindful-media'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-batch-create-wrap">
            <th scope="row">
                <label><?php _e('Quick Add Sessions', 'mindful-media'); ?></label>
            </th>
            <td>
                <button type="button" class="button button-primary" id="batch-create-sessions-btn" data-playlist-id="<?php echo esc_attr($term->term_id); ?>" data-playlist-name="<?php echo esc_attr($term->name); ?>">
                    <?php _e('+ Add Multiple Sessions', 'mindful-media'); ?>
                </button>
                <p class="description"><?php _e('Quickly add multiple sessions/episodes to this playlist at once.', 'mindful-media'); ?></p>
                
                <!-- Batch Create Modal -->
                <?php
                // Get teachers and media types for dropdowns
                $teachers = get_terms(array('taxonomy' => 'media_teacher', 'hide_empty' => false, 'orderby' => 'name'));
                $media_types = get_terms(array('taxonomy' => 'media_type', 'hide_empty' => false, 'orderby' => 'name'));
                ?>
                <div id="batch-create-modal" class="mindful-media-modal" style="display: none;">
                    <div class="mindful-media-modal-overlay"></div>
                    <div class="mindful-media-modal-content">
                        <div class="mindful-media-modal-header">
                            <h2><?php _e('Add Multiple Sessions', 'mindful-media'); ?></h2>
                            <button type="button" class="mindful-media-modal-close">&times;</button>
                        </div>
                        <div class="mindful-media-modal-body">
                            <p class="description"><?php _e('Add sessions below. Each session will be created as a new media item and automatically assigned to this playlist.', 'mindful-media'); ?></p>
                            
                            <!-- Common Settings (apply to all) -->
                            <div class="batch-common-settings" style="background: #f9f9f9; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                                <h4 style="margin: 0 0 12px; color: #23282d;"><?php _e('Apply to All Sessions', 'mindful-media'); ?></h4>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500;"><?php _e('Teacher', 'mindful-media'); ?></label>
                                        <select id="batch-teacher" style="width: 100%;">
                                            <option value=""><?php _e('— Select Teacher —', 'mindful-media'); ?></option>
                                            <?php if (!empty($teachers) && !is_wp_error($teachers)): ?>
                                                <?php foreach ($teachers as $teacher): ?>
                                                    <option value="<?php echo esc_attr($teacher->term_id); ?>"><?php echo esc_html($teacher->name); ?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500;"><?php _e('Media Type', 'mindful-media'); ?></label>
                                        <select id="batch-media-type" style="width: 100%;">
                                            <option value=""><?php _e('— Select Type —', 'mindful-media'); ?></option>
                                            <?php if (!empty($media_types) && !is_wp_error($media_types)): ?>
                                                <?php foreach ($media_types as $type): ?>
                                                    <option value="<?php echo esc_attr($type->term_id); ?>"><?php echo esc_html($type->name); ?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="batch-sessions-container">
                                <!-- Session rows will be added here -->
                            </div>
                            
                            <button type="button" class="button" id="add-session-row">
                                <?php _e('+ Add Another Session', 'mindful-media'); ?>
                            </button>
                        </div>
                        <div class="mindful-media-modal-footer">
                            <button type="button" class="button" id="cancel-batch-create"><?php _e('Cancel', 'mindful-media'); ?></button>
                            <button type="button" class="button button-primary" id="submit-batch-create"><?php _e('Create Sessions', 'mindful-media'); ?></button>
                            <span class="spinner" style="float: none; margin-left: 10px;"></span>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        
        <style>
        .mindful-media-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 100100;
        }
        .mindful-media-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
        }
        .mindful-media-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 700px;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
        }
        .mindful-media-modal-header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .mindful-media-modal-header h2 {
            margin: 0;
            font-size: 20px;
        }
        .mindful-media-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            line-height: 1;
            color: #666;
        }
        .mindful-media-modal-close:hover {
            color: #000;
        }
        .mindful-media-modal-body {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
        }
        .mindful-media-modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #ddd;
            text-align: right;
        }
        .batch-session-row {
            display: grid;
            grid-template-columns: 50px 1fr auto;
            gap: 10px;
            margin-bottom: 15px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
            align-items: start;
        }
        .batch-session-row label {
            display: block;
            font-weight: 500;
            margin-bottom: 5px;
            font-size: 12px;
            color: #666;
        }
        .batch-session-row input,
        .batch-session-row textarea {
            width: 100%;
        }
        .batch-session-row textarea {
            resize: vertical;
            min-height: 60px;
        }
        .batch-session-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .batch-session-fields .field-full {
            grid-column: span 2;
        }
        .batch-session-row .session-number {
            font-weight: 600;
            font-size: 18px;
            color: #b8a064;
            padding-top: 24px;
            text-align: center;
        }
        .batch-session-row .remove-session {
            padding-top: 24px;
        }
        .batch-session-row .remove-session button {
            color: #a00;
            cursor: pointer;
        }
        #add-session-row {
            margin-top: 10px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            var sessionCount = 0;
            var playlistId = $('#batch-create-sessions-btn').data('playlist-id');
            var playlistName = $('#batch-create-sessions-btn').data('playlist-name');
            
            // Get existing item count to set starting order
            var existingCount = <?php echo $term->count; ?>;
            
            // Password toggle
            $('#playlist-password-enabled').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#playlist-password-field').slideDown();
                } else {
                    $('#playlist-password-field').slideUp();
                    $('#playlist-password').val('');
                }
            });
            
            // Open modal
            $('#batch-create-sessions-btn').on('click', function(e) {
                e.preventDefault();
                $('#batch-create-modal').show();
                if (sessionCount === 0) {
                    addSessionRow();
                    addSessionRow();
                    addSessionRow();
                }
            });
            
            // Close modal
            $('.mindful-media-modal-close, .mindful-media-modal-overlay, #cancel-batch-create').on('click', function() {
                $('#batch-create-modal').hide();
            });
            
            // Add session row
            function addSessionRow() {
                sessionCount++;
                var orderNum = existingCount + sessionCount;
                var html = '<div class="batch-session-row" data-index="' + sessionCount + '">' +
                    '<div class="session-number">' + orderNum + '</div>' +
                    '<div class="batch-session-fields">' +
                        '<div>' +
                            '<label><?php _e('Title', 'mindful-media'); ?> *</label>' +
                            '<input type="text" name="session_title[]" placeholder="<?php _e('Session title...', 'mindful-media'); ?>" required>' +
                        '</div>' +
                        '<div>' +
                            '<label><?php _e('Media URL', 'mindful-media'); ?></label>' +
                            '<input type="url" name="session_url[]" placeholder="<?php _e('YouTube, Vimeo, etc...', 'mindful-media'); ?>">' +
                        '</div>' +
                        '<div class="field-full">' +
                            '<label><?php _e('Description', 'mindful-media'); ?></label>' +
                            '<textarea name="session_description[]" placeholder="<?php _e('Optional description...', 'mindful-media'); ?>" rows="2"></textarea>' +
                        '</div>' +
                    '</div>' +
                    '<div class="remove-session">' +
                        '<button type="button" class="button remove-session-btn" title="<?php _e('Remove', 'mindful-media'); ?>">&times;</button>' +
                    '</div>' +
                '</div>';
                $('#batch-sessions-container').append(html);
                updateSessionNumbers();
            }
            
            // Update session numbers
            function updateSessionNumbers() {
                var index = 0;
                $('#batch-sessions-container .batch-session-row').each(function() {
                    index++;
                    $(this).find('.session-number').text(existingCount + index);
                    $(this).attr('data-index', index);
                });
                sessionCount = index;
            }
            
            // Add row button
            $('#add-session-row').on('click', function() {
                addSessionRow();
            });
            
            // Remove row
            $(document).on('click', '.remove-session-btn', function() {
                $(this).closest('.batch-session-row').remove();
                updateSessionNumbers();
            });
            
            // Submit batch create
            $('#submit-batch-create').on('click', function() {
                var sessions = [];
                var hasError = false;
                
                $('#batch-sessions-container .batch-session-row').each(function() {
                    var title = $(this).find('input[name="session_title[]"]').val();
                    var url = $(this).find('input[name="session_url[]"]').val();
                    var description = $(this).find('textarea[name="session_description[]"]').val();
                    
                    if (!title) {
                        $(this).find('input[name="session_title[]"]').css('border-color', 'red');
                        hasError = true;
                        return;
                    }
                    
                    sessions.push({
                        title: title,
                        url: url,
                        description: description,
                        order: parseInt($(this).find('.session-number').text())
                    });
                });
                
                if (hasError) {
                    alert('<?php _e('Please fill in all session titles.', 'mindful-media'); ?>');
                    return;
                }
                
                if (sessions.length === 0) {
                    alert('<?php _e('Please add at least one session.', 'mindful-media'); ?>');
                    return;
                }
                
                // Show spinner
                $('#submit-batch-create').prop('disabled', true);
                $('.mindful-media-modal-footer .spinner').addClass('is-active');
                
                // Get common settings
                var teacherId = $('#batch-teacher').val();
                var mediaTypeId = $('#batch-media-type').val();
                
                // Send AJAX request
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mindful_media_batch_create_sessions',
                        nonce: '<?php echo wp_create_nonce('mindful_media_batch_create'); ?>',
                        playlist_id: playlistId,
                        teacher_id: teacherId,
                        media_type_id: mediaTypeId,
                        sessions: sessions
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php _e('Sessions created successfully!', 'mindful-media'); ?> ' + response.data.count + ' <?php _e('items added.', 'mindful-media'); ?>');
                            location.reload();
                        } else {
                            alert('<?php _e('Error:', 'mindful-media'); ?> ' + response.data);
                        }
                    },
                    error: function() {
                        alert('<?php _e('An error occurred. Please try again.', 'mindful-media'); ?>');
                    },
                    complete: function() {
                        $('#submit-batch-create').prop('disabled', false);
                        $('.mindful-media-modal-footer .spinner').removeClass('is-active');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save playlist featured image and password
     */
    public function save_playlist_image($term_id) {
        // Save featured image
        if (isset($_POST['playlist_thumbnail_id'])) {
            $thumbnail_id = absint($_POST['playlist_thumbnail_id']);
            if ($thumbnail_id > 0) {
                update_term_meta($term_id, 'thumbnail_id', $thumbnail_id);
            } else {
                delete_term_meta($term_id, 'thumbnail_id');
            }
        }
        
        // Save password protection
        if (isset($_POST['playlist_password_enabled']) && $_POST['playlist_password_enabled'] === '1') {
            update_term_meta($term_id, 'password_enabled', '1');
            
            // Save password if provided
            if (isset($_POST['playlist_password']) && !empty($_POST['playlist_password'])) {
                update_term_meta($term_id, 'playlist_password', sanitize_text_field($_POST['playlist_password']));
            }
        } else {
            // Password protection disabled, remove meta
            delete_term_meta($term_id, 'password_enabled');
            delete_term_meta($term_id, 'playlist_password');
        }
        
        // Save "hide from archive" option
        if (isset($_POST['playlist_hide_from_archive']) && $_POST['playlist_hide_from_archive'] === '1') {
            update_term_meta($term_id, 'hide_from_archive', '1');
        } else {
            delete_term_meta($term_id, 'hide_from_archive');
        }
    }
    
    /**
     * Enqueue media uploader for playlist images
     */
    public function enqueue_media_uploader($hook) {
        if ('edit-tags.php' !== $hook && 'term.php' !== $hook) {
            return;
        }
        
        // Only load on MindfulMedia taxonomy pages
        $allowed_taxonomies = array('media_series', 'media_teacher', 'media_topic');
        if (!isset($_GET['taxonomy']) || !in_array($_GET['taxonomy'], $allowed_taxonomies)) {
            return;
        }
        
        // Enqueue WordPress media uploader
        wp_enqueue_media();
        
        // Add inline script for media uploader
        wp_add_inline_script('jquery', "
            jQuery(document).ready(function($) {
                var mediaUploader;
                
                $('#playlist-thumbnail-upload').on('click', function(e) {
                    e.preventDefault();
                    
                    if (mediaUploader) {
                        mediaUploader.open();
                        return;
                    }
                    
                    mediaUploader = wp.media({
                        title: '" . __('Choose Playlist Featured Image', 'mindful-media') . "',
                        button: {
                            text: '" . __('Use this image', 'mindful-media') . "'
                        },
                        multiple: false
                    });
                    
                    mediaUploader.on('select', function() {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        $('#playlist-thumbnail-id').val(attachment.id);
                        $('#playlist-thumbnail-preview img').attr('src', attachment.url).show();
                        $('#playlist-thumbnail-remove').show();
                        $('#playlist-thumbnail-upload').text('" . __('Change Image', 'mindful-media') . "');
                    });
                    
                    mediaUploader.open();
                });
                
                $('#playlist-thumbnail-remove').on('click', function(e) {
                    e.preventDefault();
                    $('#playlist-thumbnail-id').val('');
                    $('#playlist-thumbnail-preview img').attr('src', '').hide();
                    $(this).hide();
                    $('#playlist-thumbnail-upload').text('" . __('Set Featured Image', 'mindful-media') . "');
                });
                
                // Generic taxonomy image uploader (teachers, topics)
                var taxonomyMediaUploader;
                
                $('#taxonomy-thumbnail-upload').on('click', function(e) {
                    e.preventDefault();
                    
                    if (taxonomyMediaUploader) {
                        taxonomyMediaUploader.open();
                        return;
                    }
                    
                    taxonomyMediaUploader = wp.media({
                        title: '" . __('Choose Featured Image', 'mindful-media') . "',
                        button: {
                            text: '" . __('Use this image', 'mindful-media') . "'
                        },
                        multiple: false
                    });
                    
                    taxonomyMediaUploader.on('select', function() {
                        var attachment = taxonomyMediaUploader.state().get('selection').first().toJSON();
                        $('#taxonomy-thumbnail-id').val(attachment.id);
                        $('#taxonomy-thumbnail-preview img').attr('src', attachment.url).show();
                        $('#taxonomy-thumbnail-remove').show();
                        $('#taxonomy-thumbnail-upload').text('" . __('Change Image', 'mindful-media') . "');
                    });
                    
                    taxonomyMediaUploader.open();
                });
                
                $('#taxonomy-thumbnail-remove').on('click', function(e) {
                    e.preventDefault();
                    $('#taxonomy-thumbnail-id').val('');
                    $('#taxonomy-thumbnail-preview img').attr('src', '').hide();
                    $(this).hide();
                    $('#taxonomy-thumbnail-upload').text('" . __('Set Featured Image', 'mindful-media') . "');
                });
            });
        ");
    }
    
    /**
     * Add image column to playlist admin list
     */
    public function add_playlist_image_column($columns) {
        // Insert image column after checkbox
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'cb') {
                $new_columns['image'] = __('Image', 'mindful-media');
            }
        }
        $new_columns['visibility'] = __('Visibility', 'mindful-media');
        return $new_columns;
    }

    /**
     * Display image in playlist admin column
     */
    public function playlist_image_column_content($content, $column_name, $term_id) {
        if ($column_name === 'image') {
            $thumbnail_id = get_term_meta($term_id, 'thumbnail_id', true);
            if ($thumbnail_id) {
                $thumbnail_url = wp_get_attachment_image_url($thumbnail_id, 'thumbnail');
                if ($thumbnail_url) {
                    $content = '<img src="' . esc_url($thumbnail_url) . '" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;" />';
                }
            } else {
                $content = '<span style="color: #999;">—</span>';
            }
        }

        if ($column_name === 'visibility') {
            $hidden = get_term_meta($term_id, 'hide_from_archive', true);
            if ($hidden === '1') {
                $content = '<span class="dashicons dashicons-hidden" title="' . __('Hidden from Archive', 'mindful-media') . '" style="color: #999;"></span> ' . __('Hidden', 'mindful-media');
            } else {
                $content = '<span class="dashicons dashicons-visibility" title="' . __('Visible in Archive', 'mindful-media') . '" style="color: #46b450;"></span> ' . __('Visible', 'mindful-media');
            }
        }

        return $content;
    }
    
    /**
     * Generic add image field for taxonomies (teachers, topics)
     */
    public function add_taxonomy_image_field() {
        ?>
        <div class="form-field term-thumbnail-wrap">
            <label><?php _e('Featured Image', 'mindful-media'); ?></label>
            <div id="taxonomy-thumbnail-preview" style="margin-bottom: 10px;">
                <img src="" style="max-width: 150px; height: auto; display: none;" />
            </div>
            <input type="hidden" name="taxonomy_thumbnail_id" id="taxonomy-thumbnail-id" value="">
            <button type="button" class="button" id="taxonomy-thumbnail-upload"><?php _e('Set Featured Image', 'mindful-media'); ?></button>
            <button type="button" class="button" id="taxonomy-thumbnail-remove" style="display: none;"><?php _e('Remove Image', 'mindful-media'); ?></button>
            <p class="description"><?php _e('Select an image to display for this item.', 'mindful-media'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Generic edit image field for taxonomies (teachers, topics)
     */
    public function edit_taxonomy_image_field($term) {
        $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
        $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : '';
        ?>
        <tr class="form-field term-thumbnail-wrap">
            <th scope="row">
                <label><?php _e('Featured Image', 'mindful-media'); ?></label>
            </th>
            <td>
                <div id="taxonomy-thumbnail-preview" style="margin-bottom: 10px;">
                    <img src="<?php echo esc_url($thumbnail_url); ?>" style="max-width: 200px; height: auto; <?php echo $thumbnail_url ? '' : 'display: none;'; ?>" />
                </div>
                <input type="hidden" name="taxonomy_thumbnail_id" id="taxonomy-thumbnail-id" value="<?php echo esc_attr($thumbnail_id); ?>">
                <button type="button" class="button" id="taxonomy-thumbnail-upload"><?php echo $thumbnail_id ? __('Change Image', 'mindful-media') : __('Set Featured Image', 'mindful-media'); ?></button>
                <button type="button" class="button" id="taxonomy-thumbnail-remove" style="<?php echo $thumbnail_id ? '' : 'display: none;'; ?>"><?php _e('Remove Image', 'mindful-media'); ?></button>
                <p class="description"><?php _e('Select an image to display for this item.', 'mindful-media'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Save taxonomy image (teachers, topics)
     */
    public function save_taxonomy_image($term_id) {
        if (isset($_POST['taxonomy_thumbnail_id'])) {
            $thumbnail_id = absint($_POST['taxonomy_thumbnail_id']);
            if ($thumbnail_id) {
                update_term_meta($term_id, 'thumbnail_id', $thumbnail_id);
            } else {
                delete_term_meta($term_id, 'thumbnail_id');
            }
        }
    }
    
    /**
     * Add image column for taxonomies
     */
    public function add_taxonomy_image_column($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'cb') {
                $new_columns['image'] = __('Image', 'mindful-media');
            }
        }
        return $new_columns;
    }
    
    /**
     * Display image in taxonomy admin column
     */
    public function taxonomy_image_column_content($content, $column_name, $term_id) {
        if ($column_name === 'image') {
            $thumbnail_id = get_term_meta($term_id, 'thumbnail_id', true);
            if ($thumbnail_id) {
                $thumbnail_url = wp_get_attachment_image_url($thumbnail_id, 'thumbnail');
                if ($thumbnail_url) {
                    $content = '<img src="' . esc_url($thumbnail_url) . '" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;" />';
                }
            } else {
                $content = '<span style="color: #999;">—</span>';
            }
        }
        return $content;
    }
    
} 