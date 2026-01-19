<?php
/**
 * Shortcodes Class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MindfulMedia_Shortcodes {
    
    /**
     * Cache for protected playlist video IDs
     */
    private $protected_video_ids = null;
    
    public function __construct() {
        add_action('init', array($this, 'register_shortcodes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('wp_ajax_mindful_media_filter', array($this, 'ajax_filter_content'));
        add_action('wp_ajax_nopriv_mindful_media_filter', array($this, 'ajax_filter_content'));
        add_action('wp_ajax_mindful_media_load_inline', array($this, 'ajax_load_inline_player'));
        add_action('wp_ajax_nopriv_mindful_media_load_inline', array($this, 'ajax_load_inline_player'));
        add_action('wp_ajax_mindful_media_check_password', array($this, 'ajax_check_password'));
        add_action('wp_ajax_nopriv_mindful_media_check_password', array($this, 'ajax_check_password'));
        add_action('wp_ajax_mindful_media_check_playlist_password', array($this, 'ajax_check_playlist_password'));
        add_action('wp_ajax_nopriv_mindful_media_check_playlist_password', array($this, 'ajax_check_playlist_password'));
        add_action('wp_ajax_mindful_media_browse', array($this, 'ajax_browse_content'));
        add_action('wp_ajax_nopriv_mindful_media_browse', array($this, 'ajax_browse_content'));
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('mindful_media_archive', array($this, 'archive_shortcode'));
        add_shortcode('mindful_media', array($this, 'embed_shortcode')); // Unified embed shortcode
        add_shortcode('mindful_media_browse', array($this, 'browse_shortcode')); // Browse/landing page shortcode
        add_shortcode('mindful_media_row', array($this, 'row_shortcode')); // Netflix-style category row
        add_shortcode('mindful_media_taxonomy_archive', array($this, 'taxonomy_archive_shortcode')); // Full taxonomy archive with Netflix rows
    }
    
    /**
     * Get IDs of media items that belong to protected playlists the user doesn't have access to
     * Results are cached to avoid repeated queries
     * 
     * @return array Array of post IDs to exclude
     */
    private function get_protected_playlist_video_ids() {
        // Return cached result if available
        if ($this->protected_video_ids !== null) {
            return $this->protected_video_ids;
        }
        
        $protected_ids = array();
        
        // Get all protected playlists
        $protected_playlists = get_terms(array(
            'taxonomy' => 'media_series',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => 'password_enabled',
                    'value' => '1',
                    'compare' => '='
                )
            )
        ));
        
        if (empty($protected_playlists) || is_wp_error($protected_playlists)) {
            $this->protected_video_ids = array();
            return $this->protected_video_ids;
        }
        
        // Check which protected playlists the user has access to
        foreach ($protected_playlists as $playlist) {
            $cookie_name = 'mindful_media_playlist_access_' . $playlist->term_id;
            $has_access = isset($_COOKIE[$cookie_name]);
            
            // If user doesn't have access, get all videos in this playlist
            if (!$has_access) {
                $playlist_videos = get_posts(array(
                    'post_type' => 'mindful_media',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'media_series',
                            'field' => 'term_id',
                            'terms' => $playlist->term_id,
                            'include_children' => true
                        )
                    )
                ));
                
                if (!empty($playlist_videos)) {
                    $protected_ids = array_merge($protected_ids, $playlist_videos);
                }
            }
        }
        
        // Remove duplicates
        $this->protected_video_ids = array_unique($protected_ids);
        
        return $this->protected_video_ids;
    }
    
    /**
     * Get the playlist(s) a media item belongs to
     * Returns the first playlist (parent series preferred) for display purposes
     * Shows all playlists regardless of protection status (for discovery)
     * 
     * @param int $post_id The media post ID
     * @return array|null Array with 'name', 'slug', 'url' or null if not in a playlist
     */
    private function get_media_playlist_info($post_id) {
        $playlists = get_the_terms($post_id, 'media_series');
        
        if (!$playlists || is_wp_error($playlists)) {
            return null;
        }
        
        // Find parent series first (preferred)
        foreach ($playlists as $playlist) {
            if ($playlist->parent == 0) {
                return array(
                    'name' => $playlist->name,
                    'slug' => $playlist->slug,
                    'url' => get_term_link($playlist)
                );
            }
        }
        
        // If only child playlists, find and return the parent
        foreach ($playlists as $playlist) {
            if ($playlist->parent != 0) {
                $parent = get_term($playlist->parent, 'media_series');
                if ($parent && !is_wp_error($parent)) {
                    return array(
                        'name' => $parent->name,
                        'slug' => $parent->slug,
                        'url' => get_term_link($parent)
                    );
                }
            }
        }
        
        // Fallback: return first playlist if it's a child (module)
        $first = $playlists[0];
        return array(
            'name' => $first->name,
            'slug' => $first->slug,
            'url' => get_term_link($first)
        );
    }
    
    /**
     * Get category-specific SVG icon
     * Returns an SVG icon for known category/taxonomy slugs, or null for unknown ones
     * 
     * @param string $slug The term slug
     * @param string $taxonomy The taxonomy name
     * @return string|null SVG icon HTML or null
     */
    private function get_category_icon($slug, $taxonomy) {
        // Define icons for specific category slugs
        $category_icons = array(
            // Meditations - person in lotus position
            'meditations' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="5" r="2.5"/><path d="M12 8v2"/><path d="M8 20c0-2 1-4 4-4s4 2 4 4"/><path d="M6 16c1-1 2-2 6-2s5 1 6 2"/><path d="M4 14c1.5-1.5 3.5-2.5 8-2.5s6.5 1 8 2.5"/><circle cx="12" cy="12" r="1" fill="currentColor"/></svg>',
            'meditation' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="5" r="2.5"/><path d="M12 8v2"/><path d="M8 20c0-2 1-4 4-4s4 2 4 4"/><path d="M6 16c1-1 2-2 6-2s5 1 6 2"/><path d="M4 14c1.5-1.5 3.5-2.5 8-2.5s6.5 1 8 2.5"/><circle cx="12" cy="12" r="1" fill="currentColor"/></svg>',
            
            // Talks - speech bubble / microphone
            'talks' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 00-3 3v6a3 3 0 006 0V5a3 3 0 00-3-3z"/><path d="M19 10v1a7 7 0 01-14 0v-1"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="8" y1="22" x2="16" y2="22"/></svg>',
            'talk' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 00-3 3v6a3 3 0 006 0V5a3 3 0 00-3-3z"/><path d="M19 10v1a7 7 0 01-14 0v-1"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="8" y1="22" x2="16" y2="22"/></svg>',
            
            // Teachings - open book
            'teachings' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg>',
            'teaching' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg>',
            
            // Retreats - mountain/sunrise
            'retreats' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21l4-10 4 10"/><path d="M2 21h20"/><circle cx="12" cy="6" r="2"/><path d="M12 2v2"/><path d="M4.22 10.22l1.42 1.42"/><path d="M18.36 10.22l-1.42 1.42"/></svg>',
            'retreat' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21l4-10 4 10"/><path d="M2 21h20"/><circle cx="12" cy="6" r="2"/><path d="M12 2v2"/><path d="M4.22 10.22l1.42 1.42"/><path d="M18.36 10.22l-1.42 1.42"/></svg>',
            
            // Courses - graduation cap
            'courses' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10l-10-5-10 5 10 5 10-5z"/><path d="M6 12v5c0 1.5 2.5 3 6 3s6-1.5 6-3v-5"/><path d="M22 10v6"/></svg>',
            'course' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10l-10-5-10 5 10 5 10-5z"/><path d="M6 12v5c0 1.5 2.5 3 6 3s6-1.5 6-3v-5"/><path d="M22 10v6"/></svg>',
            
            // Podcasts - broadcast
            'podcasts' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="2"/><path d="M16.24 7.76a6 6 0 010 8.49m-8.48-.01a6 6 0 010-8.49m11.31-2.82a10 10 0 010 14.14m-14.14 0a10 10 0 010-14.14"/></svg>',
            'podcast' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="2"/><path d="M16.24 7.76a6 6 0 010 8.49m-8.48-.01a6 6 0 010-8.49m11.31-2.82a10 10 0 010 14.14m-14.14 0a10 10 0 010-14.14"/></svg>',
            
            // Interviews - two people
            'interviews' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="7" r="3"/><circle cx="17" cy="7" r="3"/><path d="M3 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/><path d="M17 11a4 4 0 014 4v2"/></svg>',
            'interview' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="7" r="3"/><circle cx="17" cy="7" r="3"/><path d="M3 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/><path d="M17 11a4 4 0 014 4v2"/></svg>',
        );
        
        // Also check for media type icons
        $media_type_icons = array(
            'audio' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>',
            'video' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>',
        );
        
        // Check category icons first
        $slug_lower = strtolower($slug);
        if (isset($category_icons[$slug_lower])) {
            return $category_icons[$slug_lower];
        }
        
        // Check media type icons if this is the media_type taxonomy
        if ($taxonomy === 'media_type' && isset($media_type_icons[$slug_lower])) {
            return $media_type_icons[$slug_lower];
        }
        
        return null;
    }
    
    /**
     * MindfulMedia Archive Shortcode
     */
    public function archive_shortcode($atts) {
        // Ensure assets are loaded
        wp_enqueue_style('mindful-media-frontend');
        wp_enqueue_script('mindful-media-frontend');
        
        // Default attributes
        $atts = shortcode_atts(array(
            'per_page' => 12,
            'limit' => 12, // Support both limit and per_page for backwards compatibility
            'category' => '',
            'type' => '',
            'topic' => '',
            'duration' => '',
            'teacher' => '',
            'featured' => '',
            'show_filters' => 'true',
            'show_pagination' => 'true',
            'order' => 'DESC',
            'orderby' => 'date'
        ), $atts, 'mindful_media_archive');

        // Support backwards compatibility for 'limit' parameter
        if (isset($atts['limit']) && !isset($atts['per_page'])) {
            $atts['per_page'] = $atts['limit'];
        }

        // Start output buffering
        ob_start();
        
        // Query setup
        $paged = max(1, get_query_var('paged', 1));
        
        // Build query arguments
        $query_args = array(
            'post_type' => 'mindful_media',
            'post_status' => 'publish',
            'posts_per_page' => $atts['per_page'],
            'paged' => $paged,
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'meta_query' => array(),
            'tax_query' => array()
        );

        // Handle search
        if (!empty($_GET['search'])) {
            $query_args['s'] = sanitize_text_field($_GET['search']);
        }

        // Handle featured filter
        if (!empty($atts['featured']) && $atts['featured'] === 'true') {
            $query_args['meta_query'][] = array(
                'key' => '_mindful_media_featured',
                'value' => '1',
                'compare' => '='
            );
        }

        // Handle taxonomy filters
        $tax_queries = array();

        // Categories
        if (!empty($_GET['media_category']) || !empty($atts['category'])) {
            $categories = !empty($_GET['media_category']) ? (array)$_GET['media_category'] : explode(',', $atts['category']);
            $categories = array_map('sanitize_title', array_filter($categories));
            
            if (!empty($categories)) {
                $tax_queries[] = array(
                    'taxonomy' => 'media_category',
                    'field' => 'slug',
                    'terms' => $categories,
                    'operator' => 'IN'
                );
            }
        }

        // Media Types
        if (!empty($_GET['media_type']) || !empty($atts['type'])) {
            $types = !empty($_GET['media_type']) ? (array)$_GET['media_type'] : explode(',', $atts['type']);
            $types = array_map('sanitize_title', array_filter($types));
            
            if (!empty($types)) {
                $tax_queries[] = array(
                    'taxonomy' => 'media_type',
                    'field' => 'slug',
                    'terms' => $types,
                    'operator' => 'IN'
                );
            }
        }

        // Topics
        if (!empty($_GET['media_topic']) || !empty($atts['topic'])) {
            $topics = !empty($_GET['media_topic']) ? (array)$_GET['media_topic'] : explode(',', $atts['topic']);
            $topics = array_map('sanitize_title', array_filter($topics));
            
            if (!empty($topics)) {
                $tax_queries[] = array(
                    'taxonomy' => 'media_topic',
                    'field' => 'slug',
                    'terms' => $topics,
                    'operator' => 'IN'
                );
            }
        }

        // Duration
        if (!empty($_GET['media_duration']) || !empty($atts['duration'])) {
            $durations = !empty($_GET['media_duration']) ? (array)$_GET['media_duration'] : explode(',', $atts['duration']);
            $durations = array_map('sanitize_title', array_filter($durations));
            
            if (!empty($durations)) {
                $tax_queries[] = array(
                    'taxonomy' => 'media_duration',
                    'field' => 'slug',
                    'terms' => $durations,
                    'operator' => 'IN'
                );
            }
        }

        // Teacher taxonomy
        if (!empty($_GET['media_teacher']) || !empty($atts['teacher'])) {
            $teachers = !empty($_GET['media_teacher']) ? (array)$_GET['media_teacher'] : explode(',', $atts['teacher']);
            $teachers = array_map('sanitize_title', array_filter($teachers));
            
            if (!empty($teachers)) {
                $tax_queries[] = array(
                    'taxonomy' => 'media_teacher',
                    'field' => 'slug',
                    'terms' => $teachers,
                    'operator' => 'IN'
                );
            }
        }

        // Playlists taxonomy
        if (!empty($_GET['media_series'])) {
            $series = (array)$_GET['media_series'];
            $series = array_map('sanitize_title', array_filter($series));
            
            if (!empty($series)) {
                $tax_queries[] = array(
                    'taxonomy' => 'media_series',
                    'field' => 'slug',
                    'terms' => $series,
                    'operator' => 'IN'
                );
            }
        }

        // Year taxonomy (Feature #7)
        if (!empty($_GET['media_year'])) {
            $years = (array)$_GET['media_year'];
            $years = array_map('sanitize_title', array_filter($years));
            
            if (!empty($years)) {
                $tax_queries[] = array(
                    'taxonomy' => 'media_year',
                    'field' => 'slug',
                    'terms' => $years,
                    'operator' => 'IN'
                );
            }
        }

        // Add tax queries if we have any
        if (!empty($tax_queries)) {
            $query_args['tax_query'] = array_merge(array('relation' => 'AND'), $tax_queries);
        }
        
        // FEATURE: Exclude ALL items that belong to playlists from main archive
        // We'll show playlist cards separately instead
        $exclude_ids = array();
        
        // Get ALL playlists for exclusion (including children)
        $all_playlists = get_terms(array(
            'taxonomy' => 'media_series',
            'hide_empty' => true,
        ));
        
        // For display, only get TOP-LEVEL playlists (not children of a series)
        $playlists = get_terms(array(
            'taxonomy' => 'media_series',
            'hide_empty' => true,
            'parent' => 0, // Only top-level playlists/series
        ));
        
        // Use all playlists (including children) for item exclusion
        if (!empty($all_playlists) && !is_wp_error($all_playlists)) {
            foreach ($all_playlists as $playlist) {
                // Get ALL items in this playlist
                $playlist_items = get_posts(array(
                    'post_type' => 'mindful_media',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'media_series',
                            'field' => 'term_id',
                            'terms' => $playlist->term_id,
                        )
                    )
                ));
                
                // Exclude all playlist items
                if (!empty($playlist_items)) {
                    $exclude_ids = array_merge($exclude_ids, $playlist_items);
                }
            }
        }
        
        if (!empty($exclude_ids)) {
            $query_args['post__not_in'] = $exclude_ids;
        }
        
        // Exclude individual items marked as "hide from archive"
        $query_args['meta_query'] = array(
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
        );

        // Execute query
        $mindful_query = new WP_Query($query_args);
        
        // Also get total count for debug (admin only)
        $all_posts_query = new WP_Query(array(
            'post_type' => 'mindful_media',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));

        // Output the container
        echo '<div class="mindful-media-container">';
        
        // Modal player container (initially hidden)
        echo '<div id="mindful-media-inline-player" class="mindful-media-inline-player">';
        echo '<div class="mindful-media-modal-content">';
        echo '<div class="mindful-media-inline-player-header">';
        echo '<div class="mindful-media-inline-player-info">';
        echo '<h3 class="mindful-media-inline-player-title"></h3>';
        echo '<a href="#" class="mindful-media-inline-view-full" style="display:none;">View Full Page →</a>';
        echo '</div>';
        echo '<button class="mindful-media-inline-close" aria-label="Close player">×</button>';
        echo '</div>';
        echo '<div class="mindful-media-inline-player-content"></div>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="mindful-media-wrapper">';

        // Content area (full width, no sidebar)
        echo '<div class="mindful-media-content mindful-media-content-full">';
        
        // YouTube-style filter chips (replaces old sidebar)
        if ($atts['show_filters'] === 'true') {
            echo $this->render_filter_chips();
        }
        
        // Archive - simple grid layout only
        echo '<div class="mindful-media-archive">';
        
        if ($mindful_query->have_posts() || !empty($playlists)) {
            // First, render playlist cards (exclude playlists with hide_from_archive = true)
            if (!empty($playlists) && !is_wp_error($playlists)) {
                foreach ($playlists as $playlist) {
                    // Skip playlists marked as "hide from archive"
                    $hide_from_archive = get_term_meta($playlist->term_id, 'hide_from_archive', true);
                    if ($hide_from_archive === '1') {
                        continue;
                    }
                    
                    echo $this->render_playlist_card($playlist);
                }
            }
            
            // Then render individual media items
            while ($mindful_query->have_posts()) {
                $mindful_query->the_post();
                echo $this->render_media_item(get_the_ID(), $atts);
            }
            wp_reset_postdata();
        } else {
            echo '<div class="mindful-media-no-results">';
            echo '<p>' . __('No media items found matching your criteria.', 'mindful-media') . '</p>';
            echo '</div>';
        }
        
        echo '</div>'; // .mindful-media-archive

        // Pagination (if enabled)
        if ($atts['show_pagination'] === 'true' && $mindful_query->max_num_pages > 1) {
            echo '<div class="mindful-media-pagination">';
            echo paginate_links(array(
                'total' => $mindful_query->max_num_pages,
                'current' => $paged,
                'format' => '?paged=%#%',
                'prev_text' => '&laquo; Previous',
                'next_text' => 'Next &raquo;',
            ));
            echo '</div>';
        }

        echo '</div>'; // .mindful-media-content
        echo '</div>'; // .mindful-media-wrapper  
        echo '</div>'; // .mindful-media-container

        return ob_get_clean();
    }
    
    /**
     * Unified embed shortcode: [mindful_media id="123"] or [mindful_media playlist="playlist-slug"]
     * Supports: id, playlist, show_thumbnail
     */
    public function embed_shortcode($atts) {
        // Ensure assets are loaded
        wp_enqueue_style('mindful-media-frontend');
        wp_enqueue_script('mindful-media-frontend');
        
        $atts = shortcode_atts(array(
            'id' => '',
            'playlist' => '',
            'show_thumbnail' => 'true',
            'autoplay' => 'false',
            'class' => '',
            'size' => 'medium' // small (320px), medium (560px), large (800px), full (100%), or custom px value
        ), $atts);
        
        // Validate: must have either id or playlist
        if (empty($atts['id']) && empty($atts['playlist'])) {
            return '<p>' . __('Please specify either an id or playlist parameter.', 'mindful-media') . '</p>';
        }
        
        ob_start();
        
        // Output the modal player container if not already on the page
        // This is needed for the thumbnail click to work
        static $modal_output = false;
        if (!$modal_output) {
            $modal_output = true;
            echo $this->get_modal_player_html();
        }
        
        // Handle playlist embedding
        if (!empty($atts['playlist'])) {
            echo $this->render_playlist_embed($atts);
        }
        // Handle individual media embedding
        elseif (!empty($atts['id'])) {
            echo $this->render_media_embed($atts);
        }
        
        return ob_get_clean();
    }
    
    /**
     * Get modal player HTML container (public wrapper)
     */
    public function get_modal_player_html_public() {
        return $this->get_modal_player_html();
    }
    
    /**
     * Get modal player HTML container
     */
    private function get_modal_player_html() {
        $html = '<div id="mindful-media-inline-player" class="mindful-media-inline-player">';
        $html .= '<div class="mindful-media-modal-content">';
        $html .= '<div class="mindful-media-inline-player-header">';
        $html .= '<div class="mindful-media-inline-player-info">';
        $html .= '<h3 class="mindful-media-inline-player-title"></h3>';
        $html .= '<a href="#" class="mindful-media-inline-view-full" style="display:none;">View Full Page →</a>';
        $html .= '</div>';
        $html .= '<button class="mindful-media-inline-close" aria-label="Close player">×</button>';
        $html .= '</div>';
        $html .= '<div class="mindful-media-inline-player-content"></div>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Render individual media item embed
     * Uses the same click handler as archive cards for consistency
     */
    private function render_media_embed($atts) {
        $post_id = intval($atts['id']);
        $show_thumbnail = ($atts['show_thumbnail'] === 'true' || $atts['show_thumbnail'] === '1');
        $autoplay = ($atts['autoplay'] === 'true' || $atts['autoplay'] === '1');
        $size = isset($atts['size']) ? $atts['size'] : 'medium';
        
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'mindful_media') {
            return '<p>' . __('Media item not found.', 'mindful-media') . '</p>';
        }
        
        $media_url = get_post_meta($post_id, '_mindful_media_url', true);
        
        // Determine width based on size parameter
        $size_class = 'mm-embed-medium'; // default
        $inline_style = '';
        switch ($size) {
            case 'small':
                $size_class = 'mm-embed-small';
                break;
            case 'medium':
                $size_class = 'mm-embed-medium';
                break;
            case 'large':
                $size_class = 'mm-embed-large';
                break;
            case 'full':
                $size_class = 'mm-embed-full';
                break;
            default:
                // Custom value - check if it's a number (px) or percentage
                if (is_numeric($size)) {
                    $inline_style = 'max-width: ' . intval($size) . 'px;';
                } elseif (strpos($size, '%') !== false) {
                    $inline_style = 'max-width: ' . esc_attr($size) . ';';
                } elseif (strpos($size, 'px') !== false) {
                    $inline_style = 'max-width: ' . esc_attr($size) . ';';
                }
                break;
        }
        
        // If show_thumbnail is true, show thumbnail with play button that opens modal
        if ($show_thumbnail) {
            $thumbnail_url = get_the_post_thumbnail_url($post_id, 'large');
            if (!$thumbnail_url) {
                $thumbnail_url = plugins_url('../assets/default-thumbnail.jpg', __FILE__);
            }
            
            // Get duration for badge
            $duration_hours = get_post_meta($post_id, '_mindful_media_duration_hours', true);
            $duration_minutes = get_post_meta($post_id, '_mindful_media_duration_minutes', true);
            $duration_badge = '';
            if ($duration_hours || $duration_minutes) {
                if ($duration_hours) {
                    $duration_badge .= $duration_hours . ':';
                    $duration_badge .= str_pad($duration_minutes ?: '00', 2, '0', STR_PAD_LEFT);
                } else {
                    $duration_badge = $duration_minutes . ':00';
                }
            }
            
            // Use the SAME structure as render_media_item() so click handlers work
            $style_attr = $inline_style ? ' style="' . esc_attr($inline_style) . '"' : '';
            $output = '<div class="mindful-media-embed ' . esc_attr($size_class) . ' ' . esc_attr($atts['class']) . '"' . $style_attr . '>';
            $output .= '<article class="mindful-media-card" data-post-id="' . esc_attr($post_id) . '">';
            
            // Thumbnail container - uses .mindful-media-thumb-trigger (no text replacement)
            $output .= '<div class="mindful-media-card-thumbnail">';
            $output .= '<button type="button" class="mindful-media-thumb-trigger" ';
            $output .= 'data-post-id="' . esc_attr($post_id) . '" ';
            $output .= 'data-title="' . esc_attr($post->post_title) . '">';
            $output .= '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($post->post_title) . '" loading="lazy">';
            
            // Duration badge
            if ($duration_badge) {
                $output .= '<span class="mindful-media-card-duration">' . esc_html($duration_badge) . '</span>';
            }
            
            // Play overlay
            $output .= '<div class="mindful-media-card-play-overlay">';
            $output .= '<svg width="64" height="64" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>';
            $output .= '</div>';
            
            $output .= '</button>';
            $output .= '</div>'; // .mindful-media-card-thumbnail
            
            // Content
            $output .= '<div class="mindful-media-card-content">';
            $output .= '<h3 class="mindful-media-card-title">';
            $output .= '<button type="button" class="mindful-media-title-trigger" ';
            $output .= 'data-post-id="' . esc_attr($post_id) . '" ';
            $output .= 'data-title="' . esc_attr($post->post_title) . '">';
            $output .= esc_html($post->post_title);
            $output .= '</button>';
            $output .= '</h3>';
            
            // Teacher if available
            $teachers = get_the_terms($post_id, 'media_teacher');
            if ($teachers && !is_wp_error($teachers)) {
                $output .= '<div class="mindful-media-card-meta">';
                $output .= '<span class="mindful-media-card-teacher">' . esc_html($teachers[0]->name) . '</span>';
                $output .= '</div>';
            }
            
            $output .= '</div>'; // .mindful-media-card-content
            $output .= '</article>';
            $output .= '</div>';
            
            return $output;
        }
        
        // Otherwise, embed the player directly
        if (empty($media_url)) {
            return '<p>' . __('No media URL found for this item.', 'mindful-media') . '</p>';
        }
        
        // Get player HTML
        $media_players = new MindfulMedia_Media_Players();
        $player_html = $media_players->render_player($media_url, array(
            'autoplay' => $autoplay,
            'class' => $atts['class']
        ));
        
        $style_attr = $inline_style ? ' style="' . esc_attr($inline_style) . '"' : '';
        $output = '<div class="mindful-media-embed-player ' . esc_attr($size_class) . '"' . $style_attr . '>';
        $output .= $player_html;
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Render playlist embed
     */
    private function render_playlist_embed($atts) {
        $playlist_slug = sanitize_title($atts['playlist']);
        $show_thumbnail = ($atts['show_thumbnail'] === 'true' || $atts['show_thumbnail'] === '1');
        $size = isset($atts['size']) ? $atts['size'] : 'medium';
        
        // Determine width based on size parameter
        $size_class = 'mm-embed-medium'; // default
        $inline_style = '';
        switch ($size) {
            case 'small':
                $size_class = 'mm-embed-small';
                break;
            case 'medium':
                $size_class = 'mm-embed-medium';
                break;
            case 'large':
                $size_class = 'mm-embed-large';
                break;
            case 'full':
                $size_class = 'mm-embed-full';
                break;
            default:
                if (is_numeric($size)) {
                    $inline_style = 'max-width: ' . intval($size) . 'px;';
                } elseif (strpos($size, '%') !== false || strpos($size, 'px') !== false) {
                    $inline_style = 'max-width: ' . esc_attr($size) . ';';
                }
                break;
        }
        
        // Get playlist term
        $playlist = get_term_by('slug', $playlist_slug, 'media_series');
        if (!$playlist || is_wp_error($playlist)) {
            return '<p>' . __('Playlist not found.', 'mindful-media') . '</p>';
        }
        
        // Check if playlist is password protected
        $is_password_protected = get_term_meta($playlist->term_id, 'password_enabled', true) === '1';
        
        // Calculate total count including child playlists
        $total_count = $playlist->count;
        $all_term_ids = array($playlist->term_id);
        
        // Get child playlists
        $children = get_terms(array(
            'taxonomy' => 'media_series',
            'parent' => $playlist->term_id,
            'hide_empty' => false
        ));
        if (!empty($children) && !is_wp_error($children)) {
            foreach ($children as $child) {
                $total_count += $child->count;
                $all_term_ids[] = $child->term_id;
            }
        }
        
        // Get first item from playlist (including child playlists)
        $first_item = get_posts(array(
            'post_type' => 'mindful_media',
            'posts_per_page' => 1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'media_series',
                    'field' => 'term_id',
                    'terms' => $all_term_ids,
                )
            ),
            'meta_key' => '_mindful_media_series_order',
            'orderby' => 'meta_value_num',
            'order' => 'ASC'
        ));
        
        if (empty($first_item)) {
            return '<p>' . __('No media items found in this playlist.', 'mindful-media') . '</p>';
        }
        
        $first_post = $first_item[0];
        
        // If show_thumbnail, show playlist thumbnail with play button
        if ($show_thumbnail) {
            $thumbnail_url = get_the_post_thumbnail_url($first_post->ID, 'large');
            if (!$thumbnail_url) {
                $thumbnail_url = plugins_url('../assets/default-thumbnail.jpg', __FILE__);
            }
            
            $media_url = get_post_meta($first_post->ID, '_mindful_media_url', true);
            
            $style_attr = $inline_style ? ' style="' . esc_attr($inline_style) . '"' : '';
            $output = '<div class="mindful-media-embed-thumbnail mindful-media-embed-playlist ' . esc_attr($size_class) . ' ' . esc_attr($atts['class']) . '" data-post-id="' . $first_post->ID . '" data-media-url="' . esc_attr($media_url) . '" data-playlist-slug="' . esc_attr($playlist_slug) . '"' . $style_attr . '>';
            $output .= '<div class="mindful-media-embed-thumbnail-wrapper">';
            $output .= '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($playlist->name) . '">';
            
            // Playlist badge - use total count including children
            $output .= '<div class="mindful-media-embed-playlist-badge">';
            $output .= '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M15 6H3v2h12V6zm0 4H3v2h12v-2zM3 16h8v-2H3v2zM17 6v8.18c-.31-.11-.65-.18-1-.18-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3V8h3V6h-5z"/></svg>';
            $output .= '<span>' . $total_count . ' items</span>';
            $output .= '</div>';
            
            // Lock icon for protected playlists
            if ($is_password_protected) {
                $output .= '<div class="mindful-media-embed-lock-badge">';
                $output .= '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>';
                $output .= '</div>';
            }
            
            $output .= '<div class="mindful-media-embed-play-overlay">';
            $output .= '<button class="mindful-media-embed-play-btn" aria-label="' . esc_attr__('Play Playlist', 'mindful-media') . '">';
            $output .= '<svg width="64" height="64" viewBox="0 0 24 24" fill="white"><path d="M8 5v14l11-7z"/></svg>';
            $output .= '</button>';
            $output .= '</div>';
            $output .= '</div>';
            $output .= '<div class="mindful-media-embed-title">' . esc_html($playlist->name) . '</div>';
            $output .= '</div>';
            
            return $output;
        }
        
        // Otherwise, embed the first video with playlist sidebar automatically
        $media_url = get_post_meta($first_post->ID, '_mindful_media_url', true);
        if (empty($media_url)) {
            return '<p>' . __('No media URL found for the first item in this playlist.', 'mindful-media') . '</p>';
        }
        
        $media_players = new MindfulMedia_Media_Players();
        $player_html = $media_players->render_player($media_url, array(
            'autoplay' => false,
            'class' => 'mindful-media-embed-playlist-player ' . $atts['class']
        ));
        
        $output = '<div class="mindful-media-embed-player" data-playlist-slug="' . esc_attr($playlist_slug) . '" data-post-id="' . $first_post->ID . '">';
        $output .= $player_html;
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Browse/Landing Page Shortcode
     * Renders clickable category headers for navigating media content
     * 
     * Usage:
     * [mindful_media_browse] - Full browse page with all categories
     * [mindful_media_browse show="teachers"] - Just teachers grid
     * [mindful_media_browse show="topics,playlists"] - Multiple sections
     * [mindful_media_browse layout="cards"] - Card layout vs banner
     * [mindful_media_browse featured="true"] - Show featured content hero
     * [mindful_media_browse columns="4"] - Number of columns in grid
     */
    public function browse_shortcode($atts) {
        // Ensure assets are loaded
        wp_enqueue_style('mindful-media-frontend');
        wp_enqueue_script('mindful-media-frontend');
        
        $atts = shortcode_atts(array(
            'show' => 'all', // all, teachers, topics, playlists, types, categories, or comma-separated
            'layout' => 'cards', // cards, banners, list
            'featured' => 'false', // Show featured content hero section
            'columns' => '4', // Number of columns (2, 3, 4, 5, 6)
            'limit' => '12', // Max items to show per section
            'title' => '', // Custom title for the page
            'show_counts' => 'true', // Show item counts
            'class' => '' // Additional CSS classes
        ), $atts, 'mindful_media_browse');
        
        // Get archive display settings
        $settings = get_option('mindful_media_settings', array());
        
        // Tab visibility settings (default to showing all)
        $show_home = isset($settings['archive_show_home_tab']) ? $settings['archive_show_home_tab'] === '1' : true;
        $show_teachers_tab = isset($settings['archive_show_teachers_tab']) ? $settings['archive_show_teachers_tab'] === '1' : true;
        $show_topics_tab = isset($settings['archive_show_topics_tab']) ? $settings['archive_show_topics_tab'] === '1' : true;
        $show_playlists_tab = isset($settings['archive_show_playlists_tab']) ? $settings['archive_show_playlists_tab'] === '1' : true;
        $show_categories_tab = isset($settings['archive_show_categories_tab']) ? $settings['archive_show_categories_tab'] === '1' : false;
        $show_featured_setting = isset($settings['archive_show_featured']) ? $settings['archive_show_featured'] === '1' : true;
        $show_taxonomy_counts = isset($settings['archive_show_taxonomy_counts']) ? $settings['archive_show_taxonomy_counts'] === '1' : true;
        
        // Section visibility on Home tab (controls what shows on the browse page)
        $browse_show_teachers = isset($settings['browse_show_teachers']) ? $settings['browse_show_teachers'] === '1' : true;
        $browse_show_topics = isset($settings['browse_show_topics']) ? $settings['browse_show_topics'] === '1' : true;
        $browse_show_playlists = isset($settings['browse_show_playlists']) ? $settings['browse_show_playlists'] === '1' : true;
        $browse_show_categories = isset($settings['browse_show_categories']) ? $settings['browse_show_categories'] === '1' : true;
        $browse_show_media_types = isset($settings['browse_show_media_types']) ? $settings['browse_show_media_types'] === '1' : false;
        
        // Override show_counts with settings
        if (!$show_taxonomy_counts) {
            $atts['show_counts'] = 'false';
        }
        
        // Parse which sections to show (based on shortcode attrs)
        $show_sections = $atts['show'] === 'all' 
            ? array('featured', 'navigation', 'teachers', 'topics', 'playlists', 'types', 'categories')
            : array_map('trim', explode(',', $atts['show']));
        
        // Filter sections based on admin settings when showing 'all'
        if ($atts['show'] === 'all') {
            // Section visibility on Home tab (browse page sections setting)
            if (!$browse_show_teachers) {
                $show_sections = array_diff($show_sections, array('teachers'));
            }
            if (!$browse_show_topics) {
                $show_sections = array_diff($show_sections, array('topics'));
            }
            if (!$browse_show_playlists) {
                $show_sections = array_diff($show_sections, array('playlists'));
            }
            if (!$browse_show_categories) {
                $show_sections = array_diff($show_sections, array('categories'));
            }
            if (!$browse_show_media_types) {
                $show_sections = array_diff($show_sections, array('types'));
            }
            if (!$show_featured_setting) {
                $show_sections = array_diff($show_sections, array('featured'));
            }
        }
        
        ob_start();
        
        $columns = intval($atts['columns']);
        $columns = max(2, min(6, $columns)); // Clamp between 2-6
        
        echo '<div class="mindful-media-browse ' . esc_attr($atts['class']) . '" data-columns="' . $columns . '">';
        
        // Custom title if provided
        if (!empty($atts['title'])) {
            echo '<h2 class="mindful-media-browse-title">' . esc_html($atts['title']) . '</h2>';
        }
        
        // Featured content hero section
        if ($atts['featured'] === 'true' && in_array('featured', $show_sections) && $show_featured_setting) {
            echo $this->render_featured_hero();
        }
        
        // Navigation bar (Home | Teachers | Topics | Playlists) - includes search on right
        if (in_array('navigation', $show_sections) || $atts['show'] === 'all') {
            echo $this->render_browse_navigation();
        }
        
        // Browse sections container (for dynamic filtering)
        echo '<div class="mindful-media-browse-sections">';
        
        // Teachers section - contains BOTH card view (HOME) and video rows view (TAB)
        if (in_array('teachers', $show_sections)) {
            echo '<div class="mindful-media-browse-section-wrapper" data-section-id="teachers">';
            // Card view - shown on HOME tab (all sections visible)
            echo '<div class="mm-browse-cards-view">';
            echo $this->render_browse_section('media_teacher', __('Teachers', 'mindful-media'), $atts);
            echo '</div>';
            // Video rows view - shown when Teachers tab is active (individual tab)
            echo '<div class="mm-browse-videos-view" style="display: none;">';
            echo $this->render_browse_section_with_videos('media_teacher', __('Teachers', 'mindful-media'), $atts);
            echo '</div>';
            echo '</div>';
        }
        
        // Topics section - contains BOTH views
        if (in_array('topics', $show_sections)) {
            echo '<div class="mindful-media-browse-section-wrapper" data-section-id="topics">';
            // Card view
            echo '<div class="mm-browse-cards-view">';
            echo $this->render_browse_section('media_topic', __('Topics', 'mindful-media'), $atts);
            echo '</div>';
            // Video rows view
            echo '<div class="mm-browse-videos-view" style="display: none;">';
            echo $this->render_browse_section_with_videos('media_topic', __('Topics', 'mindful-media'), $atts);
            echo '</div>';
            echo '</div>';
        }
        
        // Playlists section - contains BOTH views
        if (in_array('playlists', $show_sections)) {
            echo '<div class="mindful-media-browse-section-wrapper" data-section-id="playlists">';
            // Card view
            echo '<div class="mm-browse-cards-view">';
            echo $this->render_playlists_browse_section($atts);
            echo '</div>';
            // Video rows view
            echo '<div class="mm-browse-videos-view" style="display: none;">';
            echo $this->render_playlists_section_with_videos($atts);
            echo '</div>';
            echo '</div>';
        }
        
        // Media Types section - contains BOTH views
        if (in_array('types', $show_sections)) {
            echo '<div class="mindful-media-browse-section-wrapper" data-section-id="types">';
            // Card view
            echo '<div class="mm-browse-cards-view">';
            echo $this->render_browse_section('media_type', __('Media Types', 'mindful-media'), $atts);
            echo '</div>';
            // Video rows view
            echo '<div class="mm-browse-videos-view" style="display: none;">';
            echo $this->render_browse_section_with_videos('media_type', __('Media Types', 'mindful-media'), $atts);
            echo '</div>';
            echo '</div>';
        }
        
        // Categories section - contains BOTH views
        if (in_array('categories', $show_sections)) {
            echo '<div class="mindful-media-browse-section-wrapper" data-section-id="categories">';
            // Card view
            echo '<div class="mm-browse-cards-view">';
            echo $this->render_browse_section('media_category', __('Categories', 'mindful-media'), $atts);
            echo '</div>';
            // Video rows view
            echo '<div class="mm-browse-videos-view" style="display: none;">';
            echo $this->render_browse_section_with_videos('media_category', __('Categories', 'mindful-media'), $atts);
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>'; // .mindful-media-browse-sections
        
        echo '</div>'; // .mindful-media-browse
        
        // Output modal player container for video playback
        echo $this->get_modal_player_html_public();
        
        return ob_get_clean();
    }
    
    /**
     * Netflix-style Row Shortcode
     * 
     * Displays a horizontal slider row of media items filtered by taxonomy
     * 
     * Usage:
     * [mindful_media_row] - Shows recent media
     * [mindful_media_row taxonomy="media_teacher" term="teacher-slug"] - Items by teacher
     * [mindful_media_row taxonomy="media_topic" title="Meditation"] - Items by topic
     * [mindful_media_row limit="8" orderby="title" order="ASC"]
     */
    public function row_shortcode($atts) {
        // Ensure assets are loaded
        wp_enqueue_style('mindful-media-frontend');
        wp_enqueue_script('mindful-media-frontend');
        
        $atts = shortcode_atts(array(
            'taxonomy' => '', // media_teacher, media_topic, media_category, media_series
            'term' => '', // specific term slug
            'title' => '', // custom title (defaults to taxonomy/term name)
            'limit' => '10', // number of items
            'orderby' => 'date', // date, title, menu_order, rand
            'order' => 'DESC', // ASC, DESC
            'featured' => '', // true to show only featured items
            'show_link' => 'true', // show "See All" link
            'class' => '' // additional CSS classes
        ), $atts, 'mindful_media_row');
        
        ob_start();
        
        // Build query args
        $query_args = array(
            'post_type' => 'mindful_media',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['limit']),
            'orderby' => $atts['orderby'],
            'order' => $atts['order']
        );
        
        // Filter by taxonomy/term
        if (!empty($atts['taxonomy']) && !empty($atts['term'])) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => sanitize_key($atts['taxonomy']),
                    'field' => 'slug',
                    'terms' => sanitize_title($atts['term'])
                )
            );
        } elseif (!empty($atts['taxonomy'])) {
            // Just filter to items that have this taxonomy assigned
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => sanitize_key($atts['taxonomy']),
                    'operator' => 'EXISTS'
                )
            );
        }
        
        // Filter by featured
        if ($atts['featured'] === 'true') {
            $query_args['meta_query'] = array(
                array(
                    'key' => '_mindful_media_featured',
                    'value' => '1',
                    'compare' => '='
                )
            );
        }
        
        // Exclude videos from protected playlists
        $protected_ids = $this->get_protected_playlist_video_ids();
        if (!empty($protected_ids)) {
            $query_args['post__not_in'] = $protected_ids;
        }
        
        $query = new WP_Query($query_args);
        
        if (!$query->have_posts()) {
            wp_reset_postdata();
            return ob_get_clean();
        }
        
        // Determine title
        $title = $atts['title'];
        if (empty($title)) {
            if (!empty($atts['term'])) {
                $term_obj = get_term_by('slug', $atts['term'], $atts['taxonomy']);
                if ($term_obj) {
                    $title = $term_obj->name;
                }
            } elseif (!empty($atts['taxonomy'])) {
                $taxonomy_obj = get_taxonomy($atts['taxonomy']);
                if ($taxonomy_obj) {
                    $title = $taxonomy_obj->labels->name;
                }
            } else {
                $title = __('Recent Media', 'mindful-media');
            }
        }
        
        // Build archive link
        $archive_link = get_post_type_archive_link('mindful_media');
        if (!empty($atts['taxonomy']) && !empty($atts['term'])) {
            $term_obj = get_term_by('slug', $atts['term'], $atts['taxonomy']);
            if ($term_obj) {
                $archive_link = get_term_link($term_obj);
            }
        }
        
        // Output modal player container - REQUIRED for videos to play
        // Only output if not already on page (check for existing container)
        static $modal_output = false;
        if (!$modal_output) {
            echo $this->get_modal_player_html();
            $modal_output = true;
        }
        
        // Output slider row
        echo '<section class="mm-slider-row mindful-media-row ' . esc_attr($atts['class']) . '">';
        
        // Header with title
        echo '<div class="mm-slider-header">';
        echo '<h3 class="mm-slider-title">';
        if ($atts['show_link'] === 'true' && !is_wp_error($archive_link)) {
            echo '<a href="' . esc_url($archive_link) . '">';
            echo esc_html($title);
            echo '<svg class="mm-slider-title-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>';
            echo '</a>';
        } else {
            echo esc_html($title);
        }
        echo '</h3>';
        echo '</div>';
        
        // Slider container
        echo '<div class="mm-slider-container">';
        
        // Navigation arrows
        echo '<button class="mm-slider-nav mm-slider-nav--prev" aria-label="Previous">';
        echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>';
        echo '</button>';
        echo '<button class="mm-slider-nav mm-slider-nav--next" aria-label="Next">';
        echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>';
        echo '</button>';
        
        // Slider track
        echo '<div class="mm-slider-track">';
        
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            // Get meta
            $thumbnail_url = get_the_post_thumbnail_url($post_id, 'medium_large');
            if (!$thumbnail_url) {
                $thumbnail_url = MINDFUL_MEDIA_PLUGIN_URL . 'assets/default-thumbnail.jpg';
            }
            
            // Duration badge
            $duration_hours = get_post_meta($post_id, '_mindful_media_duration_hours', true);
            $duration_minutes = get_post_meta($post_id, '_mindful_media_duration_minutes', true);
            $duration_badge = '';
            if ($duration_hours || $duration_minutes) {
                if ($duration_hours) {
                    $duration_badge = $duration_hours . ':' . str_pad($duration_minutes ?: '0', 2, '0', STR_PAD_LEFT);
                } else {
                    $duration_badge = $duration_minutes . ':00';
                }
            }
            
            // Teacher
            $teachers = get_the_terms($post_id, 'media_teacher');
            $teacher_name = ($teachers && !is_wp_error($teachers)) ? $teachers[0]->name : '';
            
            echo '<div class="mm-slider-item">';
            echo '<div class="mindful-media-card mindful-media-thumb-trigger" data-post-id="' . $post_id . '">';
            
            // Thumbnail
            echo '<div class="mindful-media-card-thumb">';
            echo '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr(get_the_title()) . '" loading="lazy">';
            
            // Play overlay
            echo '<div class="mindful-media-card-play-overlay">';
            echo '<svg width="48" height="48" viewBox="0 0 24 24" fill="white"><path d="M8 5v14l11-7z"/></svg>';
            echo '</div>';
            
            // Duration badge
            if ($duration_badge) {
                echo '<span class="mindful-media-duration-badge">' . esc_html($duration_badge) . '</span>';
            }
            
            echo '</div>'; // .mindful-media-card-thumb
            
            // Content
            echo '<div class="mindful-media-card-content">';
            echo '<h4 class="mindful-media-card-title">' . esc_html(get_the_title()) . '</h4>';
            if ($teacher_name) {
                echo '<p class="mindful-media-card-teacher">' . esc_html($teacher_name) . '</p>';
            }
            echo '</div>';
            
            echo '</div>'; // .mindful-media-card
            echo '</div>'; // .mm-slider-item
        }
        
        echo '</div>'; // .mm-slider-track
        echo '</div>'; // .mm-slider-container
        echo '</section>';
        
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    /**
     * Taxonomy Archive Shortcode - Netflix Multi-Row Layout
     * 
     * Displays ALL terms from a taxonomy, each as a separate Netflix-style row
     * with that term's media items in a horizontal slider.
     * 
     * Usage:
     * [mindful_media_taxonomy_archive taxonomy="media_teacher"] - All teachers, each with their videos
     * [mindful_media_taxonomy_archive taxonomy="media_topic"] - All topics, each with their videos
     * [mindful_media_taxonomy_archive taxonomy="media_series"] - All playlists, each with their videos
     * [mindful_media_taxonomy_archive taxonomy="media_category" title="Browse by Category"]
     */
    public function taxonomy_archive_shortcode($atts) {
        // Ensure assets are loaded
        wp_enqueue_style('mindful-media-frontend');
        wp_enqueue_script('mindful-media-frontend');
        
        $atts = shortcode_atts(array(
            'taxonomy' => 'media_teacher', // media_teacher, media_topic, media_series, media_category
            'title' => '', // Custom page title
            'items_per_row' => '10', // Number of items per row
            'orderby' => 'name', // Term orderby: name, count, slug
            'order' => 'ASC', // Term order
            'hide_empty' => 'true', // Hide terms with no items
            'class' => '' // Additional CSS classes
        ), $atts, 'mindful_media_taxonomy_archive');
        
        ob_start();
        
        // Get taxonomy label for default title
        $taxonomy_obj = get_taxonomy($atts['taxonomy']);
        $page_title = !empty($atts['title']) ? $atts['title'] : ($taxonomy_obj ? $taxonomy_obj->labels->name : __('Browse', 'mindful-media'));
        
        // Get all terms from the taxonomy
        $terms = get_terms(array(
            'taxonomy' => $atts['taxonomy'],
            'hide_empty' => $atts['hide_empty'] === 'true',
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'parent' => 0, // Top-level terms only for hierarchical taxonomies
        ));
        
        if (empty($terms) || is_wp_error($terms)) {
            echo '<div class="mindful-media-no-results">';
            echo '<p>' . __('No items found.', 'mindful-media') . '</p>';
            echo '</div>';
            return ob_get_clean();
        }
        
        // Output modal player container - REQUIRED for videos to play
        echo $this->get_modal_player_html();
        
        // Container
        echo '<div class="mindful-media-taxonomy-archive ' . esc_attr($atts['class']) . '">';
        
        // Page title and search bar
        echo '<div class="mindful-media-taxonomy-archive-header">';
        if (!empty($page_title)) {
            echo '<h2 class="mindful-media-taxonomy-archive-title">' . esc_html($page_title) . '</h2>';
        }
        
        // Search bar
        echo '<div class="mm-search-container mm-taxonomy-archive-search">';
        echo '<svg class="mm-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>';
        echo '<input type="text" class="mindful-media-search-input mm-taxonomy-archive-search-input" placeholder="' . esc_attr__('Search...', 'mindful-media') . '" />';
        echo '<button type="button" class="mm-search-clear" aria-label="' . esc_attr__('Clear search', 'mindful-media') . '">&times;</button>';
        echo '</div>';
        echo '</div>';
        
        // Content container for search filtering
        echo '<div class="mindful-media-taxonomy-archive-content">';
        
        // Render each term as a Netflix-style row
        foreach ($terms as $term) {
            // Skip hidden terms
            $hide_from_archive = get_term_meta($term->term_id, 'hide_from_archive', true);
            if ($hide_from_archive === '1') {
                continue;
            }
            
            // For playlists (media_series), check password protection
            $is_protected = false;
            $has_access = true;
            
            if ($atts['taxonomy'] === 'media_series') {
                $is_protected = get_term_meta($term->term_id, 'password_enabled', true) === '1';
                
                if ($is_protected) {
                    // Check cookie for access - must match cookie name from template
                    $cookie_name = 'mindful_media_playlist_access_' . $term->term_id;
                    $has_access = isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name] === wp_hash($term->term_id . 'mindful_media_playlist_access');
                }
            }
            
            // For playlists (media_series), include items from child playlists
            $term_ids_to_query = array($term->term_id);
            $total_count = $term->count;
            
            if ($atts['taxonomy'] === 'media_series') {
                $children = get_terms(array(
                    'taxonomy' => 'media_series',
                    'parent' => $term->term_id,
                    'hide_empty' => false
                ));
                if (!empty($children) && !is_wp_error($children)) {
                    foreach ($children as $child) {
                        $term_ids_to_query[] = $child->term_id;
                        $total_count += $child->count;
                    }
                }
            }
            
            // Term archive link
            $term_link = get_term_link($term);
            
            // Row section
            echo '<section class="mm-slider-row mindful-media-row">';
            
            // Row header with term name (use total count for playlists)
            echo '<div class="mm-slider-header">';
            echo '<h3 class="mm-slider-title">';
            if (!is_wp_error($term_link)) {
                echo '<a href="' . esc_url($term_link) . '">';
                if ($is_protected) {
                    echo '<svg class="mm-lock-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 6px; vertical-align: middle;"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>';
                }
                echo esc_html($term->name);
                if ($total_count > 0) {
                    echo ' <span class="mm-term-count">(' . $total_count . ')</span>';
                }
                echo '<svg class="mm-slider-title-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>';
                echo '</a>';
            } else {
                echo esc_html($term->name);
            }
            echo '</h3>';
            echo '</div>';
            
            // If protected and no access, show locked placeholder (compact version)
            if ($is_protected && !$has_access) {
                echo '<div class="mm-playlist-locked">';
                echo '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>';
                echo '<p class="mm-playlist-locked-text">' . __('This playlist is password protected.', 'mindful-media') . '</p>';
                echo '<a href="' . esc_url($term_link) . '" class="mm-playlist-locked-link">' . __('Enter password to view', 'mindful-media') . '</a>';
                echo '</div>';
                echo '</section>';
                continue;
            }
            
            // Get media items for this term (and children for playlists)
            $query_args = array(
                'post_type' => 'mindful_media',
                'post_status' => 'publish',
                'posts_per_page' => intval($atts['items_per_row']),
                'tax_query' => array(
                    array(
                        'taxonomy' => $atts['taxonomy'],
                        'field' => 'term_id',
                        'terms' => $term_ids_to_query,
                    )
                ),
                'orderby' => 'menu_order date',
                'order' => 'ASC'
            );
            
            // CRITICAL: Exclude videos from protected playlists
            $protected_ids = $this->get_protected_playlist_video_ids();
            if (!empty($protected_ids)) {
                $query_args['post__not_in'] = $protected_ids;
            }
            
            // For playlists, order by series_order
            if ($atts['taxonomy'] === 'media_series') {
                $query_args['meta_key'] = '_mindful_media_series_order';
                $query_args['orderby'] = 'meta_value_num';
                $query_args['order'] = 'ASC';
            }
            
            $query = new WP_Query($query_args);
            
            // Skip if no items
            if (!$query->have_posts()) {
                wp_reset_postdata();
                echo '</section>';
                continue;
            }
            
            // Slider container
            echo '<div class="mm-slider-container">';
            
            // Navigation arrows
            echo '<button class="mm-slider-nav mm-slider-nav--prev" aria-label="Previous">';
            echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>';
            echo '</button>';
            echo '<button class="mm-slider-nav mm-slider-nav--next" aria-label="Next">';
            echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>';
            echo '</button>';
            
            // Slider track
            echo '<div class="mm-slider-track">';
            
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                // Get thumbnail
                $thumbnail_url = get_the_post_thumbnail_url($post_id, 'medium_large');
                if (!$thumbnail_url) {
                    $thumbnail_url = MINDFUL_MEDIA_PLUGIN_URL . 'assets/default-thumbnail.jpg';
                }
                
                // Duration badge
                $duration_hours = get_post_meta($post_id, '_mindful_media_duration_hours', true);
                $duration_minutes = get_post_meta($post_id, '_mindful_media_duration_minutes', true);
                $duration_badge = '';
                if ($duration_hours || $duration_minutes) {
                    if ($duration_hours) {
                        $duration_badge = $duration_hours . ':' . str_pad($duration_minutes ?: '0', 2, '0', STR_PAD_LEFT);
                    } else {
                        $duration_badge = $duration_minutes . ':00';
                    }
                }
                
                // Teacher
                $teachers = get_the_terms($post_id, 'media_teacher');
                $teacher_name = ($teachers && !is_wp_error($teachers)) ? $teachers[0]->name : '';
                
                echo '<div class="mm-slider-item">';
                echo '<div class="mindful-media-card mindful-media-thumb-trigger" data-post-id="' . $post_id . '">';
                
                // Thumbnail
                echo '<div class="mindful-media-card-thumb">';
                echo '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr(get_the_title()) . '" loading="lazy">';
                
                // Play overlay
                echo '<div class="mindful-media-card-play-overlay">';
                echo '<svg width="48" height="48" viewBox="0 0 24 24" fill="white"><path d="M8 5v14l11-7z"/></svg>';
                echo '</div>';
                
                // Duration badge
                if ($duration_badge) {
                    echo '<span class="mindful-media-duration-badge">' . esc_html($duration_badge) . '</span>';
                }
                
                echo '</div>'; // .mindful-media-card-thumb
                
                // Content
                echo '<div class="mindful-media-card-content">';
                echo '<h4 class="mindful-media-card-title">' . esc_html(get_the_title()) . '</h4>';
                if ($teacher_name && $atts['taxonomy'] !== 'media_teacher') {
                    echo '<p class="mindful-media-card-teacher">' . esc_html($teacher_name) . '</p>';
                }
                
                // Playlist badge
                $playlist_info = $this->get_media_playlist_info($post_id);
                if ($playlist_info) {
                    echo '<div class="mindful-media-card-playlist-badge">';
                    echo '<a href="' . esc_url($playlist_info['url']) . '" class="mindful-media-playlist-link">';
                    echo '<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h10v2H4zm14 0v6l5-3-5-3z"/></svg>';
                    echo '<span>' . esc_html($playlist_info['name']) . '</span>';
                    echo '</a>';
                    echo '</div>';
                }
                
                echo '</div>';
                
                echo '</div>'; // .mindful-media-card
                echo '</div>'; // .mm-slider-item
            }
            
            echo '</div>'; // .mm-slider-track
            echo '</div>'; // .mm-slider-container
            echo '</section>'; // .mm-slider-row
            
            wp_reset_postdata();
        }
        
        echo '</div>'; // .mindful-media-taxonomy-archive-content
        echo '</div>'; // .mindful-media-taxonomy-archive
        
        return ob_get_clean();
    }
    
    /**
     * Render navigation bar for browse page
     * Navigation now uses data attributes for dynamic filtering
     * Respects Archive Display settings from admin
     */
    private function render_browse_navigation() {
        // Get archive display settings
        $settings = get_option('mindful_media_settings', array());
        
        // Tab visibility settings (default to showing all)
        $show_home = isset($settings['archive_show_home_tab']) ? $settings['archive_show_home_tab'] === '1' : true;
        $show_teachers = isset($settings['archive_show_teachers_tab']) ? $settings['archive_show_teachers_tab'] === '1' : true;
        $show_topics = isset($settings['archive_show_topics_tab']) ? $settings['archive_show_topics_tab'] === '1' : true;
        $show_playlists = isset($settings['archive_show_playlists_tab']) ? $settings['archive_show_playlists_tab'] === '1' : true;
        $show_categories = isset($settings['archive_show_categories_tab']) ? $settings['archive_show_categories_tab'] === '1' : false;
        
        $nav_items = array();
        
        // Clean, simple SVG icons - YouTube/Material Design inspired
        if ($show_home) {
            $nav_items[] = array(
                'label' => __('Home', 'mindful-media'),
                'url' => '#',
                'section' => 'all',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1H4a1 1 0 01-1-1V9.5z"/><path d="M9 21V12h6v9"/></svg>'
            );
        }
        
        if ($show_teachers) {
            $nav_items[] = array(
                'label' => __('Teachers', 'mindful-media'),
                'url' => '#',
                'section' => 'teachers',
                'taxonomy' => 'media_teacher',
                'archive_url' => get_post_type_archive_link('mindful_media') . '?filter=teachers',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/></svg>'
            );
        }
        
        if ($show_topics) {
            $nav_items[] = array(
                'label' => __('Topics', 'mindful-media'),
                'url' => '#',
                'section' => 'topics',
                'taxonomy' => 'media_topic',
                'archive_url' => get_post_type_archive_link('mindful_media') . '?filter=topics',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><circle cx="7" cy="7" r="1.5" fill="currentColor"/></svg>'
            );
        }
        
        if ($show_playlists) {
            $nav_items[] = array(
                'label' => __('Playlists', 'mindful-media'),
                'url' => '#',
                'section' => 'playlists',
                'taxonomy' => 'media_series',
                'archive_url' => get_post_type_archive_link('mindful_media') . '?filter=playlists',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M3 12h12M3 18h18"/><path d="M19 10l3 2-3 2v-4z" fill="currentColor" stroke="none"/></svg>'
            );
        }
        
        if ($show_categories) {
            $nav_items[] = array(
                'label' => __('Categories', 'mindful-media'),
                'url' => '#',
                'section' => 'categories',
                'taxonomy' => 'media_category',
                'archive_url' => get_post_type_archive_link('mindful_media') . '?filter=categories',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>'
            );
        }
        
        // If no tabs enabled, return empty
        if (empty($nav_items)) {
            return '';
        }
        
        $output = '<nav class="mindful-media-browse-nav">';
        $output .= '<div class="mindful-media-browse-nav-inner">';
        
        // Navigation tabs on the left
        $output .= '<div class="mindful-media-browse-nav-tabs">';
        $first = true;
        foreach ($nav_items as $item) {
            // Build data attributes
            $data_attrs = 'data-section="' . esc_attr($item['section']) . '"';
            if (isset($item['taxonomy'])) {
                $data_attrs .= ' data-taxonomy="' . esc_attr($item['taxonomy']) . '"';
            }
            if (isset($item['filter_type'])) {
                $data_attrs .= ' data-filter-type="' . esc_attr($item['filter_type']) . '"';
            }
            
            $active_class = $first ? ' active' : '';
            $first = false;
            
            $output .= '<a href="' . esc_attr($item['url']) . '" class="mindful-media-browse-nav-item' . $active_class . '" ' . $data_attrs . '>';
            $output .= '<span class="mindful-media-browse-nav-icon">' . $item['icon'] . '</span>';
            $output .= '<span class="mindful-media-browse-nav-label">' . esc_html($item['label']) . '</span>';
            $output .= '</a>';
        }
        $output .= '</div>';
        
        // Search bar on the right
        $output .= '<div class="mm-search-container mm-browse-nav-search">';
        $output .= '<svg class="mm-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>';
        $output .= '<input type="text" class="mindful-media-search-input mm-browse-search" placeholder="' . esc_attr__('Search...', 'mindful-media') . '" />';
        $output .= '<button type="button" class="mm-search-clear" aria-label="' . esc_attr__('Clear search', 'mindful-media') . '">&times;</button>';
        $output .= '</div>';
        
        $output .= '</div>';
        $output .= '</nav>';
        
        return $output;
    }
    
    /**
     * Render a browse section for a taxonomy - Shows term cards in a horizontal slider
     * Each term is displayed as a clickable card (Teacher card, Topic card, etc.)
     */
    private function render_browse_section($taxonomy, $title, $atts) {
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
            'number' => intval($atts['limit']),
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        if (empty($terms) || is_wp_error($terms)) {
            return '';
        }
        
        $show_counts = $atts['show_counts'] === 'true';
        
        // Get archive link for this taxonomy
        $archive_link = get_post_type_archive_link('mindful_media');
        
        // Netflix-style slider row with term cards
        $output = '<section class="mindful-media-browse-section mm-slider-row" data-taxonomy="' . esc_attr($taxonomy) . '">';
        
        // Section header with clickable title
        $output .= '<div class="mm-slider-header">';
        $output .= '<h3 class="mm-slider-title">';
        $output .= '<a href="' . esc_url($archive_link) . '?browse=' . esc_attr($taxonomy) . '">';
        $output .= esc_html($title);
        $output .= '<svg class="mm-slider-title-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>';
        $output .= '</a>';
        $output .= '</h3>';
        $output .= '</div>';
        
        // Slider container
        $output .= '<div class="mm-slider-container">';
        
        // Navigation arrows
        $output .= '<button class="mm-slider-nav mm-slider-nav--prev" aria-label="Previous">';
        $output .= '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>';
        $output .= '</button>';
        $output .= '<button class="mm-slider-nav mm-slider-nav--next" aria-label="Next">';
        $output .= '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>';
        $output .= '</button>';
        
        // Slider track with term cards
        $output .= '<div class="mm-slider-track">';
        
        foreach ($terms as $term) {
            $term_link = get_term_link($term);
            if (is_wp_error($term_link)) {
                continue;
            }
            
            // Get term image if available (for teachers, playlists)
            $term_image = '';
            $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
            if ($thumbnail_id) {
                $term_image = wp_get_attachment_image_url($thumbnail_id, 'medium');
            }
            
            $output .= '<a href="' . esc_url($term_link) . '" class="mm-slider-item mindful-media-browse-card">';
            
            // Card image or placeholder
            $output .= '<div class="mindful-media-browse-card-image">';
            if ($term_image) {
                $output .= '<img src="' . esc_url($term_image) . '" alt="' . esc_attr($term->name) . '" loading="lazy">';
            } else {
                // Check for category-specific icons
                $category_icon = $this->get_category_icon($term->slug, $taxonomy);
                if ($category_icon) {
                    $output .= '<div class="mindful-media-browse-card-placeholder mindful-media-browse-card-icon">' . $category_icon . '</div>';
                } else {
                    // Generate a placeholder with first letter
                    $first_letter = strtoupper(substr($term->name, 0, 1));
                    $output .= '<div class="mindful-media-browse-card-placeholder">' . esc_html($first_letter) . '</div>';
                }
            }
            $output .= '</div>';
            
            // Card content
            $output .= '<div class="mindful-media-browse-card-content">';
            $output .= '<h4 class="mindful-media-browse-card-title">' . esc_html($term->name) . '</h4>';
            
            if ($show_counts) {
                $output .= '<span class="mindful-media-browse-card-count">';
                $output .= sprintf(_n('%d item', '%d items', $term->count, 'mindful-media'), $term->count);
                $output .= '</span>';
            }
            
            $output .= '</div>';
            $output .= '</a>';
        }
        
        $output .= '</div>'; // .mm-slider-track
        $output .= '</div>'; // .mm-slider-container
        $output .= '</section>';
        
        return $output;
    }
    
    /**
     * Render browse section with VIDEO ROWS for each term (Netflix-style)
     * Used when individual tabs (Teachers, Topics, etc.) are active
     * Shows multiple rows, one for each term, with that term's videos
     * 
     * @param string $taxonomy The taxonomy slug
     * @param string $title Section title
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    private function render_browse_section_with_videos($taxonomy, $title, $atts) {
        $limit = intval($atts['limit']);
        $items_per_row = 10;
        
        // Get IDs of videos in protected playlists to exclude
        $protected_ids = $this->get_protected_playlist_video_ids();
        
        // Get all terms from this taxonomy
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
            'number' => $limit,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        if (empty($terms) || is_wp_error($terms)) {
            return '<p class="mm-no-content">' . __('No content available.', 'mindful-media') . '</p>';
        }
        
        $output = '<div class="mm-browse-video-rows">';
        
        foreach ($terms as $term) {
            // Get videos for this term
            $query_args = array(
                'post_type' => 'mindful_media',
                'post_status' => 'publish',
                'posts_per_page' => $items_per_row,
                'orderby' => 'date',
                'order' => 'DESC',
                'tax_query' => array(
                    array(
                        'taxonomy' => $taxonomy,
                        'field' => 'term_id',
                        'terms' => $term->term_id
                    )
                )
            );
            
            // Exclude videos from protected playlists
            if (!empty($protected_ids)) {
                $query_args['post__not_in'] = $protected_ids;
            }
            
            $query = new WP_Query($query_args);
            
            if (!$query->have_posts()) {
                wp_reset_postdata();
                continue;
            }
            
            // Get term link
            $term_link = get_term_link($term);
            if (is_wp_error($term_link)) {
                $term_link = '#';
            }
            
            // Output row for this term
            $output .= '<section class="mm-slider-row" data-term="' . esc_attr($term->slug) . '">';
            
            // Header with term name
            $output .= '<div class="mm-slider-header">';
            $output .= '<h3 class="mm-slider-title">';
            $output .= '<a href="' . esc_url($term_link) . '">';
            $output .= esc_html($term->name);
            $output .= '<svg class="mm-slider-title-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>';
            $output .= '</a>';
            $output .= '</h3>';
            $output .= '</div>';
            
            // Slider container
            $output .= '<div class="mm-slider-container">';
            
            // Navigation arrows
            $output .= '<button class="mm-slider-nav mm-slider-nav--prev" aria-label="Previous">';
            $output .= '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>';
            $output .= '</button>';
            $output .= '<button class="mm-slider-nav mm-slider-nav--next" aria-label="Next">';
            $output .= '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>';
            $output .= '</button>';
            
            // Slider track with video cards
            $output .= '<div class="mm-slider-track">';
            
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                // Get thumbnail
                $thumbnail_url = get_the_post_thumbnail_url($post_id, 'medium_large');
                if (!$thumbnail_url) {
                    $thumbnail_url = MINDFUL_MEDIA_PLUGIN_URL . 'assets/default-thumbnail.jpg';
                }
                
                // Duration badge
                $duration_hours = get_post_meta($post_id, '_mindful_media_duration_hours', true);
                $duration_minutes = get_post_meta($post_id, '_mindful_media_duration_minutes', true);
                $duration_badge = '';
                if ($duration_hours || $duration_minutes) {
                    if ($duration_hours) {
                        $duration_badge = $duration_hours . ':' . str_pad($duration_minutes ?: '0', 2, '0', STR_PAD_LEFT);
                    } else {
                        $duration_badge = $duration_minutes . ':00';
                    }
                }
                
                // Get teacher name
                $teachers = get_the_terms($post_id, 'media_teacher');
                $teacher_name = ($teachers && !is_wp_error($teachers)) ? $teachers[0]->name : '';
                
                // Media type icon
                $media_types = get_the_terms($post_id, 'media_type');
                $is_audio = false;
                if ($media_types && !is_wp_error($media_types)) {
                    foreach ($media_types as $type) {
                        if (strtolower($type->name) === 'audio') {
                            $is_audio = true;
                            break;
                        }
                    }
                }
                
                // Card output - uses thumb-trigger button structure for click handling
                $output .= '<div class="mm-slider-item mindful-media-card" data-post-id="' . esc_attr($post_id) . '">';
                $output .= '<div class="mindful-media-card-thumb">';
                $output .= '<button type="button" class="mindful-media-thumb-trigger" data-post-id="' . esc_attr($post_id) . '" data-title="' . esc_attr(get_the_title()) . '">';
                $output .= '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr(get_the_title()) . '" loading="lazy">';
                $output .= '<div class="mindful-media-card-play"><svg viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg></div>';
                
                if ($duration_badge) {
                    $output .= '<span class="mindful-media-duration-badge">' . esc_html($duration_badge) . '</span>';
                }
                
                // Media type icon
                if ($is_audio) {
                    $output .= '<span class="mindful-media-card-type-icon mindful-media-card-type-audio"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg></span>';
                } else {
                    $output .= '<span class="mindful-media-card-type-icon mindful-media-card-type-video"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg></span>';
                }
                
                $output .= '</button>'; // .mindful-media-thumb-trigger
                $output .= '</div>'; // .mindful-media-card-thumb
                
                $output .= '<div class="mindful-media-card-info">';
                $output .= '<h4 class="mindful-media-card-title">' . esc_html(get_the_title()) . '</h4>';
                if ($teacher_name) {
                    $output .= '<p class="mindful-media-card-teacher">' . esc_html($teacher_name) . '</p>';
                }
                
                // Playlist badge
                $playlist_info = $this->get_media_playlist_info($post_id);
                if ($playlist_info) {
                    $output .= '<div class="mindful-media-card-playlist-badge">';
                    $output .= '<a href="' . esc_url($playlist_info['url']) . '" class="mindful-media-playlist-link">';
                    $output .= '<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h10v2H4zm14 0v6l5-3-5-3z"/></svg>';
                    $output .= '<span>' . esc_html($playlist_info['name']) . '</span>';
                    $output .= '</a>';
                    $output .= '</div>';
                }
                
                $output .= '</div>'; // .mindful-media-card-info
                
                $output .= '</div>'; // .mm-slider-item
            }
            
            wp_reset_postdata();
            
            $output .= '</div>'; // .mm-slider-track
            $output .= '</div>'; // .mm-slider-container
            $output .= '</section>'; // .mm-slider-row
        }
        
        $output .= '</div>'; // .mm-browse-video-rows
        
        return $output;
    }
    
    /**
     * Render playlists browse section with VIDEO ROWS for each playlist
     * Used when Playlists tab is active
     */
    private function render_playlists_section_with_videos($atts) {
        $limit = intval($atts['limit']);
        $items_per_row = 10;
        
        // Get top-level playlists
        $terms = get_terms(array(
            'taxonomy' => 'media_series',
            'hide_empty' => false,
            'parent' => 0,
            'number' => $limit,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        if (empty($terms) || is_wp_error($terms)) {
            return '<p class="mm-no-content">' . __('No playlists available.', 'mindful-media') . '</p>';
        }
        
        $output = '<div class="mm-browse-video-rows">';
        
        foreach ($terms as $term) {
            // Skip hidden playlists
            $hide_from_archive = get_term_meta($term->term_id, 'hide_from_archive', true);
            if ($hide_from_archive === '1') {
                continue;
            }
            
            // Check if password protected
            $is_protected = get_term_meta($term->term_id, 'password_enabled', true) === '1';
            $cookie_name = 'mindful_media_playlist_access_' . $term->term_id;
            $has_access = isset($_COOKIE[$cookie_name]);
            
            // Get term link
            $term_link = get_term_link($term);
            if (is_wp_error($term_link)) {
                $term_link = '#';
            }
            
            // Output row for this playlist
            $output .= '<section class="mm-slider-row" data-term="' . esc_attr($term->slug) . '">';
            
            // Header with playlist name
            $output .= '<div class="mm-slider-header">';
            $output .= '<h3 class="mm-slider-title">';
            $output .= '<a href="' . esc_url($term_link) . '">';
            $output .= esc_html($term->name);
            if ($is_protected) {
                $output .= ' <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle; opacity: 0.7;"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>';
            }
            $output .= '<svg class="mm-slider-title-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>';
            $output .= '</a>';
            $output .= '</h3>';
            $output .= '</div>';
            
            // If protected and no access, show lock placeholder (compact version)
            if ($is_protected && !$has_access) {
                $output .= '<div class="mm-playlist-locked">';
                $output .= '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>';
                $output .= '<p class="mm-playlist-locked-text">' . __('This playlist is password protected.', 'mindful-media') . '</p>';
                $output .= '<a href="' . esc_url($term_link) . '" class="mm-playlist-locked-link">' . __('Enter password to view', 'mindful-media') . '</a>';
                $output .= '</div>';
                $output .= '</section>';
                continue;
            }
            
            // Get videos for this playlist
            $query_args = array(
                'post_type' => 'mindful_media',
                'post_status' => 'publish',
                'posts_per_page' => $items_per_row,
                'orderby' => 'date',
                'order' => 'DESC',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'media_series',
                        'field' => 'term_id',
                        'terms' => $term->term_id,
                        'include_children' => true
                    )
                )
            );
            
            $query = new WP_Query($query_args);
            
            if (!$query->have_posts()) {
                wp_reset_postdata();
                $output .= '<p class="mm-no-items">' . __('No items in this playlist yet.', 'mindful-media') . '</p>';
                $output .= '</section>';
                continue;
            }
            
            // Slider container
            $output .= '<div class="mm-slider-container">';
            
            // Navigation arrows
            $output .= '<button class="mm-slider-nav mm-slider-nav--prev" aria-label="Previous">';
            $output .= '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>';
            $output .= '</button>';
            $output .= '<button class="mm-slider-nav mm-slider-nav--next" aria-label="Next">';
            $output .= '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>';
            $output .= '</button>';
            
            // Slider track with video cards
            $output .= '<div class="mm-slider-track">';
            
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                // Get thumbnail
                $thumbnail_url = get_the_post_thumbnail_url($post_id, 'medium_large');
                if (!$thumbnail_url) {
                    $thumbnail_url = MINDFUL_MEDIA_PLUGIN_URL . 'assets/default-thumbnail.jpg';
                }
                
                // Duration badge
                $duration_hours = get_post_meta($post_id, '_mindful_media_duration_hours', true);
                $duration_minutes = get_post_meta($post_id, '_mindful_media_duration_minutes', true);
                $duration_badge = '';
                if ($duration_hours || $duration_minutes) {
                    if ($duration_hours) {
                        $duration_badge = $duration_hours . ':' . str_pad($duration_minutes ?: '0', 2, '0', STR_PAD_LEFT);
                    } else {
                        $duration_badge = $duration_minutes . ':00';
                    }
                }
                
                // Get teacher name
                $teachers = get_the_terms($post_id, 'media_teacher');
                $teacher_name = ($teachers && !is_wp_error($teachers)) ? $teachers[0]->name : '';
                
                // Card output - uses thumb-trigger button structure for click handling
                $output .= '<div class="mm-slider-item mindful-media-card" data-post-id="' . esc_attr($post_id) . '">';
                $output .= '<div class="mindful-media-card-thumb">';
                $output .= '<button type="button" class="mindful-media-thumb-trigger" data-post-id="' . esc_attr($post_id) . '" data-title="' . esc_attr(get_the_title()) . '">';
                $output .= '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr(get_the_title()) . '" loading="lazy">';
                $output .= '<div class="mindful-media-card-play"><svg viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg></div>';
                
                if ($duration_badge) {
                    $output .= '<span class="mindful-media-duration-badge">' . esc_html($duration_badge) . '</span>';
                }
                
                $output .= '</button>'; // .mindful-media-thumb-trigger
                $output .= '</div>'; // .mindful-media-card-thumb
                
                $output .= '<div class="mindful-media-card-info">';
                $output .= '<h4 class="mindful-media-card-title">' . esc_html(get_the_title()) . '</h4>';
                if ($teacher_name) {
                    $output .= '<p class="mindful-media-card-teacher">' . esc_html($teacher_name) . '</p>';
                }
                $output .= '</div>'; // .mindful-media-card-info
                
                $output .= '</div>'; // .mm-slider-item
            }
            
            wp_reset_postdata();
            
            $output .= '</div>'; // .mm-slider-track
            $output .= '</div>'; // .mm-slider-container
            $output .= '</section>'; // .mm-slider-row
        }
        
        $output .= '</div>'; // .mm-browse-video-rows
        
        return $output;
    }
    
    /**
     * Render playlists browse section - Slider with playlist cards (consistent with other sections)
     */
    private function render_playlists_browse_section($atts) {
        $show_counts = $atts['show_counts'] === 'true';
        $limit = intval($atts['limit']);
        
        // Get top-level playlists/series
        $terms = get_terms(array(
            'taxonomy' => 'media_series',
            'hide_empty' => false,
            'parent' => 0,
            'number' => $limit,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        if (empty($terms) || is_wp_error($terms)) {
            return '';
        }
        
        // Get archive link for playlists
        $archive_link = get_post_type_archive_link('mindful_media');
        
        // Netflix-style slider row with playlist cards (consistent with other sections)
        $output = '<section class="mindful-media-browse-section mm-slider-row" data-taxonomy="media_series">';
        
        // Section header with clickable title
        $output .= '<div class="mm-slider-header">';
        $output .= '<h3 class="mm-slider-title">';
        $output .= '<a href="' . esc_url($archive_link) . '?browse=media_series">';
        $output .= esc_html__('Playlists', 'mindful-media');
        $output .= '<svg class="mm-slider-title-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>';
        $output .= '</a>';
        $output .= '</h3>';
        $output .= '</div>';
        
        // Slider container
        $output .= '<div class="mm-slider-container">';
        
        // Navigation arrows
        $output .= '<button class="mm-slider-nav mm-slider-nav--prev" aria-label="Previous">';
        $output .= '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>';
        $output .= '</button>';
        $output .= '<button class="mm-slider-nav mm-slider-nav--next" aria-label="Next">';
        $output .= '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>';
        $output .= '</button>';
        
        // Slider track with playlist cards
        $output .= '<div class="mm-slider-track">';
        
        foreach ($terms as $term) {
            // Skip hidden playlists
            $hide_from_archive = get_term_meta($term->term_id, 'hide_from_archive', true);
            if ($hide_from_archive === '1') {
                continue;
            }
            
            $term_link = get_term_link($term);
            if (is_wp_error($term_link)) {
                continue;
            }
            
            // Check if this is a series (has children)
            $children = get_terms(array(
                'taxonomy' => 'media_series',
                'parent' => $term->term_id,
                'hide_empty' => false
            ));
            $is_series = !empty($children) && !is_wp_error($children);
            
            // Get term image
            $term_image = '';
            $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
            if ($thumbnail_id) {
                $term_image = wp_get_attachment_image_url($thumbnail_id, 'medium');
            }
            
            // If no image, try to get from first item
            if (!$term_image && $term->count > 0) {
                $first_item = get_posts(array(
                    'post_type' => 'mindful_media',
                    'posts_per_page' => 1,
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'media_series',
                            'field' => 'term_id',
                            'terms' => $term->term_id,
                        )
                    )
                ));
                if (!empty($first_item) && has_post_thumbnail($first_item[0]->ID)) {
                    $term_image = get_the_post_thumbnail_url($first_item[0]->ID, 'medium');
                }
            }
            
            $output .= '<a href="' . esc_url($term_link) . '" class="mm-slider-item mindful-media-browse-card">';
            
            // Card image or placeholder
            $output .= '<div class="mindful-media-browse-card-image">';
            if ($term_image) {
                $output .= '<img src="' . esc_url($term_image) . '" alt="' . esc_attr($term->name) . '" loading="lazy">';
            } else {
                $first_letter = strtoupper(substr($term->name, 0, 1));
                $output .= '<div class="mindful-media-browse-card-placeholder">' . esc_html($first_letter) . '</div>';
            }
            
            // Series badge
            if ($is_series) {
                $output .= '<div class="mindful-media-browse-card-badge">' . __('Series', 'mindful-media') . '</div>';
            }
            
            // Password protected badge
            $is_protected = get_term_meta($term->term_id, 'password_enabled', true) === '1';
            if ($is_protected) {
                $output .= '<div class="mindful-media-browse-card-lock">';
                $output .= '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
                $output .= '</div>';
            }
            $output .= '</div>';
            
            // Card content
            $output .= '<div class="mindful-media-browse-card-content">';
            $output .= '<h4 class="mindful-media-browse-card-title">' . esc_html($term->name) . '</h4>';
            
            if ($show_counts) {
                if ($is_series) {
                    $child_count = count($children);
                    $output .= '<span class="mindful-media-browse-card-count">';
                    $output .= sprintf(_n('%d playlist', '%d playlists', $child_count, 'mindful-media'), $child_count);
                    $output .= '</span>';
                } else {
                    $output .= '<span class="mindful-media-browse-card-count">';
                    $output .= sprintf(_n('%d item', '%d items', $term->count, 'mindful-media'), $term->count);
                    $output .= '</span>';
                }
            }
            
            $output .= '</div>';
            $output .= '</a>';
        }
        
        $output .= '</div>'; // .mm-slider-track
        $output .= '</div>'; // .mm-slider-container
        $output .= '</section>';
        
        return $output;
    }
    
    /**
     * Render featured content section - 3 items in a row (Netflix style)
     */
    private function render_featured_hero() {
        // Get 3 featured media items
        $featured = get_posts(array(
            'post_type' => 'mindful_media',
            'posts_per_page' => 3,
            'meta_query' => array(
                array(
                    'key' => '_mindful_media_featured',
                    'value' => '1',
                    'compare' => '='
                )
            )
        ));
        
        if (empty($featured)) {
            return '';
        }
        
        $output = '<section class="mindful-media-featured-section mm-slider-row">';
        
        // Section header
        $output .= '<div class="mm-slider-header">';
        $output .= '<h3 class="mm-slider-title">';
        $output .= '<span class="mindful-media-featured-badge-inline">' . __('Featured', 'mindful-media') . '</span>';
        $output .= '</h3>';
        $output .= '</div>';
        
        // Featured items row
        $output .= '<div class="mindful-media-featured-row">';
        
        foreach ($featured as $post) {
            $thumbnail_url = get_the_post_thumbnail_url($post->ID, 'large');
            if (!$thumbnail_url) {
                $thumbnail_url = MINDFUL_MEDIA_PLUGIN_URL . 'assets/default-thumbnail.jpg';
            }
            
            $media_url = get_post_meta($post->ID, '_mindful_media_url', true);
            $teachers = get_the_terms($post->ID, 'media_teacher');
            $teacher_name = ($teachers && !is_wp_error($teachers)) ? $teachers[0]->name : '';
            
            // Get duration
            $duration_hours = get_post_meta($post->ID, '_mindful_media_duration_hours', true);
            $duration_minutes = get_post_meta($post->ID, '_mindful_media_duration_minutes', true);
            $duration_badge = '';
            if ($duration_hours || $duration_minutes) {
                if ($duration_hours) {
                    $duration_badge .= $duration_hours . ':';
                    $duration_badge .= str_pad($duration_minutes ?: '0', 2, '0', STR_PAD_LEFT);
                } else {
                    $duration_badge = $duration_minutes . ':00';
                }
            }
            
            $output .= '<div class="mindful-media-featured-card">';
            
            // Card thumbnail with play overlay
            $output .= '<div class="mindful-media-featured-card-thumb mindful-media-thumb-trigger" data-post-id="' . $post->ID . '">';
            $output .= '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($post->post_title) . '" loading="lazy">';
            
            // Play overlay
            $output .= '<div class="mindful-media-featured-card-overlay">';
            $output .= '<div class="mindful-media-featured-card-play">';
            $output .= '<svg width="48" height="48" viewBox="0 0 24 24" fill="white"><path d="M8 5v14l11-7z"/></svg>';
            $output .= '</div>';
            $output .= '</div>';
            
            // Duration badge
            if ($duration_badge) {
                $output .= '<span class="mindful-media-duration-badge">' . esc_html($duration_badge) . '</span>';
            }
            
            // Featured badge
            $output .= '<span class="mindful-media-featured-card-badge">' . __('Featured', 'mindful-media') . '</span>';
            
            $output .= '</div>'; // .mindful-media-featured-card-thumb
            
            // Card content
            $output .= '<div class="mindful-media-featured-card-content">';
            $output .= '<h4 class="mindful-media-featured-card-title">' . esc_html($post->post_title) . '</h4>';
            if ($teacher_name) {
                $output .= '<p class="mindful-media-featured-card-teacher">' . esc_html($teacher_name) . '</p>';
            }
            $output .= '</div>';
            
            $output .= '</div>'; // .mindful-media-featured-card
        }
        
        $output .= '</div>'; // .mindful-media-featured-row
        $output .= '</section>';
        
        return $output;
    }
    
    /**
     * Get aspect ratio class for a post
     */
    private function get_aspect_ratio_class($post_id) {
        // Check media_type taxonomy
        $media_types = get_the_terms($post_id, 'media_type');
        if ($media_types && !is_wp_error($media_types)) {
            $type_name = strtolower($media_types[0]->name);
            if (strpos($type_name, 'audio') !== false) {
                return 'aspect-ratio-square';
            } elseif (strpos($type_name, 'video') !== false) {
                return 'aspect-ratio-widescreen';
            }
        }
        
        // Check source as fallback
        $media_source = get_post_meta($post_id, '_mindful_media_source', true);
        if (in_array($media_source, ['soundcloud', 'audio'])) {
            return 'aspect-ratio-square';
        } elseif (in_array($media_source, ['youtube', 'vimeo', 'video', 'archive'])) {
            return 'aspect-ratio-widescreen';
        }
        
        // Default to widescreen
        return 'aspect-ratio-widescreen';
    }
    
    /**
     * Render a single media item
     */
    /**
     * Render media item - YouTube-style card with modal-first navigation
     */
    private function render_media_item($post_id, $atts) {
        $post = get_post($post_id);
        $permalink = get_permalink($post_id);
        
        // Get meta values
        $duration_hours = get_post_meta($post_id, '_mindful_media_duration_hours', true);
        $duration_minutes = get_post_meta($post_id, '_mindful_media_duration_minutes', true);
        $media_url = get_post_meta($post_id, '_mindful_media_url', true);
        $external_link = get_post_meta($post_id, '_mindful_media_external_link', true);
        
        // Format duration for badge
        $duration_badge = '';
        if ($duration_hours || $duration_minutes) {
            if ($duration_hours) {
                $duration_badge .= $duration_hours . ':';
                $duration_badge .= str_pad($duration_minutes ?: '00', 2, '0', STR_PAD_LEFT);
            } else {
                $duration_badge = $duration_minutes . ':00';
            }
        }
        
        // Get teacher
        $teacher_terms = get_the_terms($post_id, 'media_teacher');
        $teacher_name = '';
        if ($teacher_terms && !is_wp_error($teacher_terms)) {
            $teacher_name = $teacher_terms[0]->name;
        }
        
        // Get thumbnail
        $thumbnail_url = '';
        if (has_post_thumbnail($post_id)) {
            $thumbnail_url = get_the_post_thumbnail_url($post_id, 'medium_large');
        } else {
            $thumbnail_url = MINDFUL_MEDIA_PLUGIN_URL . 'assets/default-thumbnail.jpg';
        }
        
        // Check for password protection
        $is_protected = get_post_meta($post_id, '_mindful_media_password_protected', true) === '1';
        
        // Check if part of a playlist
        $series_terms = get_the_terms($post_id, 'media_series');
        $is_in_playlist = $series_terms && !is_wp_error($series_terms);
        
        // Determine if card should be clickable for modal (has media URL and no external link)
        $opens_modal = !empty($media_url) && empty($external_link);
        
        // Start card - YouTube style
        $output = '<article class="mindful-media-card" data-post-id="' . esc_attr($post_id) . '">';
        
        // Thumbnail container - clickable for modal
        // Uses separate class "mindful-media-thumb-trigger" to avoid text replacement issue
        $output .= '<div class="mindful-media-card-thumbnail">';
        
        if ($opens_modal) {
            $output .= '<button type="button" class="mindful-media-thumb-trigger" ';
            $output .= 'data-post-id="' . esc_attr($post_id) . '" ';
            $output .= 'data-title="' . esc_attr($post->post_title) . '">';
        }
        
        $output .= '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($post->post_title) . '" loading="lazy">';
        
        // Duration badge (bottom right)
        if ($duration_badge) {
            $output .= '<span class="mindful-media-card-duration">' . esc_html($duration_badge) . '</span>';
        }
        
        // Media type icon (bottom left) - video or audio indicator
        $media_types = get_the_terms($post_id, 'media_type');
        if ($media_types && !is_wp_error($media_types)) {
            $type_name = strtolower($media_types[0]->name);
            $is_audio = (strpos($type_name, 'audio') !== false);
            
            if ($is_audio) {
                $output .= '<span class="mindful-media-card-type-icon mindful-media-card-type-audio" title="' . esc_attr__('Audio', 'mindful-media') . '">';
                $output .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>';
                $output .= '</span>';
            } else {
                $output .= '<span class="mindful-media-card-type-icon mindful-media-card-type-video" title="' . esc_attr__('Video', 'mindful-media') . '">';
                $output .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>';
                $output .= '</span>';
            }
        }
        
        // Lock icon for protected content
        if ($is_protected) {
            $output .= '<span class="mindful-media-card-lock-icon">';
            $output .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>';
            $output .= '</span>';
        }
        
        // Play overlay on hover
        $output .= '<div class="mindful-media-card-play-overlay">';
        $output .= '<svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>';
        $output .= '</div>';
        
        if ($opens_modal) {
            $output .= '</button>';
        }
        
        $output .= '</div>'; // End thumbnail
        
        // Content below thumbnail
        $output .= '<div class="mindful-media-card-content">';
        
        // Title - also clickable for modal (uses title-trigger to avoid text replacement)
        $output .= '<h3 class="mindful-media-card-title">';
        if ($opens_modal) {
            $output .= '<button type="button" class="mindful-media-title-trigger" ';
            $output .= 'data-post-id="' . esc_attr($post_id) . '" ';
            $output .= 'data-title="' . esc_attr($post->post_title) . '">';
            $output .= esc_html($post->post_title);
            $output .= '</button>';
        } else {
            $output .= '<a href="' . esc_url($external_link ?: $permalink) . '"' . ($external_link ? ' target="_blank"' : '') . '>';
            $output .= esc_html($post->post_title);
            $output .= '</a>';
        }
        $output .= '</h3>';
        
        // Meta line (teacher name)
        if ($teacher_name) {
            $output .= '<div class="mindful-media-card-meta">';
            $output .= '<span class="mindful-media-card-teacher">' . esc_html($teacher_name) . '</span>';
            $output .= '</div>';
        }
        
        // Playlist badge - show if video belongs to a playlist
        $playlist_info = $this->get_media_playlist_info($post_id);
        if ($playlist_info) {
            $output .= '<div class="mindful-media-card-playlist-badge">';
            $output .= '<a href="' . esc_url($playlist_info['url']) . '" class="mindful-media-playlist-link">';
            $output .= '<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h10v2H4zm14 0v6l5-3-5-3z"/></svg>';
            $output .= '<span>' . esc_html($playlist_info['name']) . '</span>';
            $output .= '</a>';
            $output .= '</div>';
        }
        
        $output .= '</div>'; // End content
        
        $output .= '</article>'; // End card
        
        return $output;
    }
    
    /**
     * Render playlist card for archive display
     */
    private function render_playlist_card($playlist_term) {
        $playlist_url = get_term_link($playlist_term);
        
        // Calculate total count including child playlists
        $playlist_count = $playlist_term->count;
        
        // Check if this is a parent series (has children)
        $children = get_terms(array(
            'taxonomy' => 'media_series',
            'parent' => $playlist_term->term_id,
            'hide_empty' => false
        ));
        
        if (!empty($children) && !is_wp_error($children)) {
            // Add counts from all child playlists
            foreach ($children as $child) {
                $playlist_count += $child->count;
            }
        }
        
        // Get image URL - Priority: 1) Featured image from term meta, 2) First item's image, 3) Default
        $image_url = plugins_url('../assets/default-thumbnail.jpg', __FILE__);
        
        // Check if playlist has a featured image (stored in term meta)
        $term_image_id = get_term_meta($playlist_term->term_id, 'thumbnail_id', true);
        if ($term_image_id) {
            $term_image = wp_get_attachment_image_url($term_image_id, 'medium_large');
            if ($term_image) {
                $image_url = $term_image;
            }
        }
        
        // If no featured image, fall back to first item's image
        if ($image_url === plugins_url('../assets/default-thumbnail.jpg', __FILE__)) {
            $image_query = get_posts(array(
                'post_type' => 'mindful_media',
                'posts_per_page' => 1,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'media_series',
                        'field' => 'term_id',
                        'terms' => $playlist_term->term_id,
                    )
                ),
                'orderby' => 'date',
                'order' => 'ASC'
            ));
            
            if (!empty($image_query)) {
                $first_item = $image_query[0];
                if (has_post_thumbnail($first_item->ID)) {
                    $image_url = get_the_post_thumbnail_url($first_item->ID, 'medium_large');
                }
            }
        }
        
        // Get playlist description
        $description = $playlist_term->description;
        if (empty($description)) {
            $description = sprintf(__('A collection of %d media items', 'mindful-media'), $playlist_count);
        }
        
        // Get first item ID for "WATCH" button (no meta_key filter - items may not have order set)
        $first_item_id = 0;
        $first_item_query = get_posts(array(
            'post_type' => 'mindful_media',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => 'media_series',
                    'field' => 'term_id',
                    'terms' => $playlist_term->term_id,
                )
            ),
            'orderby' => 'date',
            'order' => 'ASC'
        ));
        if (!empty($first_item_query)) {
            $first_item_id = $first_item_query[0];
        }
        
        // Check if playlist is password protected
        $is_password_protected = get_term_meta($playlist_term->term_id, 'password_enabled', true) === '1';
        
        // Start playlist card - YouTube style (same as media cards)
        $output = '<article class="mindful-media-card mindful-media-card-playlist" data-playlist-id="' . esc_attr($playlist_term->term_id) . '">';
        
        // Thumbnail container - clicking opens playlist
        $output .= '<div class="mindful-media-card-thumbnail">';
        
        if ($first_item_id) {
            $output .= '<button type="button" class="mindful-media-thumb-trigger mindful-media-playlist-watch-btn" ';
            $output .= 'data-post-id="' . esc_attr($first_item_id) . '" ';
            $output .= 'data-playlist-id="' . esc_attr($playlist_term->term_id) . '">';
        } else {
            $output .= '<a href="' . esc_url($playlist_url) . '">';
        }
        
        $output .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($playlist_term->name) . '" loading="lazy">';
        
        // Playlist count badge (bottom right)
        $output .= '<span class="mindful-media-card-duration mindful-media-card-playlist-count">';
        $output .= '<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M4 10h12v2H4zm0-4h12v2H4zm0 8h8v2H4zm10 0v6l5-3z"/></svg>';
        $output .= $playlist_count;
        $output .= '</span>';
        
        // Lock icon for protected content
        if ($is_password_protected) {
            $output .= '<span class="mindful-media-card-lock-icon">';
            $output .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>';
            $output .= '</span>';
        }
        
        // Play overlay on hover
        $output .= '<div class="mindful-media-card-play-overlay">';
        $output .= '<svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>';
        $output .= '</div>';
        
        if ($first_item_id) {
            $output .= '</button>';
        } else {
            $output .= '</a>';
        }
        
        $output .= '</div>'; // End thumbnail
        
        // Content below thumbnail
        $output .= '<div class="mindful-media-card-content">';
        
        // Title - same size as other cards
        $output .= '<h3 class="mindful-media-card-title">';
        $output .= '<a href="' . esc_url($playlist_url) . '">' . esc_html($playlist_term->name) . '</a>';
        $output .= '</h3>';
        
        // Meta - show as "Playlist • X items"
        $output .= '<div class="mindful-media-card-meta">';
        $output .= '<span class="mindful-media-card-teacher">Playlist • ' . sprintf(_n('%d item', '%d items', $playlist_count, 'mindful-media'), $playlist_count) . '</span>';
        $output .= '</div>';
        
        $output .= '</div>'; // End content
        
        $output .= '</article>'; // End item
        
        return $output;
    }
    
    /**
     * Render YouTube-style filter chips
     * Horizontal scrollable pills for filtering content
     */
    private function render_filter_chips() {
        // Get archive display settings
        $settings = get_option('mindful_media_settings', array());
        $show_duration = isset($settings['archive_show_duration_filter']) ? $settings['archive_show_duration_filter'] === '1' : true;
        $show_year = isset($settings['archive_show_year_filter']) ? $settings['archive_show_year_filter'] === '1' : true;
        $show_type = isset($settings['archive_show_type_filter']) ? $settings['archive_show_type_filter'] === '1' : true;
        $show_filter_counts = isset($settings['archive_show_filter_counts']) ? $settings['archive_show_filter_counts'] === '1' : true;
        
        // Inline styles for chips to override aggressive theme styles (like Hello Elementor)
        $chip_style = 'display:inline-flex;align-items:center;gap:4px;padding:8px 12px;background:#f2f2f2;border:none;border-radius:8px;font-family:inherit;font-size:14px;font-weight:500;color:#0f0f0f;cursor:pointer;white-space:nowrap;transition:all 0.2s;text-decoration:none;text-transform:none;letter-spacing:normal;line-height:1.4;box-shadow:none;outline:none;margin:0;';
        $chip_style_active = 'display:inline-flex;align-items:center;gap:4px;padding:8px 12px;background:#0f0f0f;border:none;border-radius:8px;font-family:inherit;font-size:14px;font-weight:500;color:#ffffff;cursor:pointer;white-space:nowrap;transition:all 0.2s;text-decoration:none;text-transform:none;letter-spacing:normal;line-height:1.4;box-shadow:none;outline:none;margin:0;';
        
        $output = '<div class="mindful-media-filter-chips">';
        
        // Inner wrapper for chips (allows search to be positioned on the right)
        $output .= '<div class="mm-filter-chips-inner">';
        
        // Get IDs of all items that belong to playlists (these are "private")
        $playlist_item_ids = array();
        $all_playlists = get_terms(array('taxonomy' => 'media_series', 'hide_empty' => true));
        if (!empty($all_playlists) && !is_wp_error($all_playlists)) {
            foreach ($all_playlists as $p) {
                $items = get_posts(array(
                    'post_type' => 'mindful_media',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'tax_query' => array(array(
                        'taxonomy' => 'media_series',
                        'field' => 'term_id',
                        'terms' => $p->term_id,
                    ))
                ));
                if (!empty($items)) {
                    $playlist_item_ids = array_merge($playlist_item_ids, $items);
                }
            }
        }
        $playlist_item_ids = array_unique($playlist_item_ids);
        
        // "All" chip - always first (active by default)
        $output .= '<button type="button" class="mm-chip active" style="' . $chip_style_active . '" data-filter-type="all" data-filter-value="">';
        $output .= '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>';
        $output .= __('All', 'mindful-media');
        $output .= '</button>';
        
        // Media Type chips (Audio, Video) - counts EXCLUDE playlist items (they're private)
        if ($show_type) {
            $types = get_terms(array(
                'taxonomy' => 'media_type',
                'hide_empty' => true,
                'orderby' => 'name',
                'order' => 'ASC'
            ));
            
            if (!empty($types) && !is_wp_error($types)) {
                foreach ($types as $type) {
                    $icon = '';
                    $type_slug = strtolower($type->slug);
                    
                    if (strpos($type_slug, 'audio') !== false) {
                        $icon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>';
                    } elseif (strpos($type_slug, 'video') !== false) {
                        $icon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>';
                    }
                    
                    // Calculate count EXCLUDING playlist items
                    $type_count_query = new WP_Query(array(
                        'post_type' => 'mindful_media',
                        'posts_per_page' => -1,
                        'fields' => 'ids',
                        'post__not_in' => !empty($playlist_item_ids) ? $playlist_item_ids : array(0),
                        'tax_query' => array(array(
                            'taxonomy' => 'media_type',
                            'field' => 'term_id',
                            'terms' => $type->term_id,
                        ))
                    ));
                    $type_count = $type_count_query->found_posts;
                    
                    $output .= '<button type="button" class="mm-chip" style="' . $chip_style . '" data-filter-type="media_type" data-filter-value="' . esc_attr($type->slug) . '">';
                    $output .= $icon;
                    $output .= esc_html($type->name);
                    if ($show_filter_counts && $type_count > 0) {
                        $output .= '<span class="mm-chip-count">' . $type_count . '</span>';
                    }
                    $output .= '</button>';
                }
            }
        }
        
        // Playlists chip (like YouTube) - shows only playlists/series
        $playlists = get_terms(array(
            'taxonomy' => 'media_series',
            'hide_empty' => true,
            'parent' => 0, // Only top-level playlists
        ));
        $playlist_count = !empty($playlists) && !is_wp_error($playlists) ? count($playlists) : 0;
        
        if ($playlist_count > 0) {
            $output .= '<button type="button" class="mm-chip" style="' . $chip_style . '" data-filter-type="playlists" data-filter-value="all">';
            $output .= '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>';
            $output .= __('Playlists', 'mindful-media');
            if ($show_filter_counts) {
                $output .= '<span class="mm-chip-count">' . $playlist_count . '</span>';
            }
            $output .= '</button>';
        }
        
        // Popular teachers (limit to 5)
        $teachers = get_terms(array(
            'taxonomy' => 'media_teacher',
            'hide_empty' => true,
            'orderby' => 'count',
            'order' => 'DESC',
            'number' => 5
        ));
        
        if (!empty($teachers) && !is_wp_error($teachers)) {
            $teacher_icon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
            foreach ($teachers as $teacher) {
                $output .= '<button type="button" class="mm-chip" style="' . $chip_style . '" data-filter-type="media_teacher" data-filter-value="' . esc_attr($teacher->slug) . '">';
                $output .= $teacher_icon;
                $output .= '<span>' . esc_html($teacher->name) . '</span>';
                $output .= '</button>';
            }
        }
        
        // Popular topics (limit to 5)
        $topics = get_terms(array(
            'taxonomy' => 'media_topic',
            'hide_empty' => true,
            'orderby' => 'count',
            'order' => 'DESC',
            'number' => 5
        ));
        
        if (!empty($topics) && !is_wp_error($topics)) {
            $topic_icon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>';
            foreach ($topics as $topic) {
                $output .= '<button type="button" class="mm-chip" style="' . $chip_style . '" data-filter-type="media_topic" data-filter-value="' . esc_attr($topic->slug) . '">';
                $output .= $topic_icon;
                $output .= '<span>' . esc_html($topic->name) . '</span>';
                $output .= '</button>';
            }
        }
        
        // Duration chips
        if ($show_duration) {
            $durations = get_terms(array(
                'taxonomy' => 'media_duration',
                'hide_empty' => true,
                'orderby' => 'name',
                'order' => 'ASC'
            ));

            if (!empty($durations) && !is_wp_error($durations)) {
                $duration_icon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
                foreach ($durations as $duration) {
                    $output .= '<button type="button" class="mm-chip" style="' . $chip_style . '" data-filter-type="media_duration" data-filter-value="' . esc_attr($duration->slug) . '">';
                    $output .= $duration_icon;
                    $output .= '<span>' . esc_html($duration->name) . '</span>';
                    $output .= '</button>';
                }
            }
        }

        // Year chips (most recent 5)
        if ($show_year) {
            $years = get_terms(array(
                'taxonomy' => 'media_year',
                'hide_empty' => true,
                'orderby' => 'name',
                'order' => 'DESC',
                'number' => 5
            ));

            if (!empty($years) && !is_wp_error($years)) {
                $year_icon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
                foreach ($years as $year) {
                    $output .= '<button type="button" class="mm-chip" style="' . $chip_style . '" data-filter-type="media_year" data-filter-value="' . esc_attr($year->slug) . '">';
                    $output .= $year_icon;
                    $output .= '<span>' . esc_html($year->name) . '</span>';
                    $output .= '</button>';
                }
            }
        }
        
        $output .= '</div>'; // Close .mm-filter-chips-inner
        
        // Search input - positioned on the right
        $output .= '<div class="mm-search-container mm-filter-chips-search">';
        $output .= '<svg class="mm-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>';
        $output .= '<input type="text" class="mindful-media-search-input" placeholder="' . esc_attr__('Search...', 'mindful-media') . '" />';
        $output .= '<button type="button" class="mm-search-clear" aria-label="' . esc_attr__('Clear search', 'mindful-media') . '">&times;</button>';
        $output .= '</div>';
        
        $output .= '</div>'; // Close .mindful-media-filter-chips
        
        return $output;
    }
    
    /**
     * AJAX filter content
     */
    public function ajax_filter_content() {
        check_ajax_referer('mindful_media_ajax_nonce', 'nonce');
        
        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        // Check for special "playlists" filter type (shows ONLY playlists)
        $show_playlists_only = isset($filters['playlists']);
        // Check for media_type filter (Audio/Video) - shows ONLY individual items
        $show_media_only = isset($filters['media_type']);
        
        // Build query args for individual media items
        $query_args = array(
            'post_type' => 'mindful_media',
            'posts_per_page' => intval($_POST['limit']) ?: 12,
            'post_status' => 'publish'
        );
        
        // Add search
        if (!empty($search)) {
            $query_args['s'] = $search;
        }
        
        // Add taxonomy filters (excluding special 'playlists' key)
        $tax_query = array('relation' => 'AND');
        
        foreach ($filters as $taxonomy => $terms) {
            // Skip the special "playlists" filter - it's handled separately
            if ($taxonomy === 'playlists') {
                continue;
            }
            
            if (!empty($terms)) {
                $tax_query[] = array(
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => $terms,
                    'operator' => 'IN'
                );
            }
        }
        
        if (count($tax_query) > 1) {
            $query_args['tax_query'] = $tax_query;
        }
        
        ob_start();
        $has_results = false;
        
        // CASE 1: "Playlists" filter - show ONLY playlist cards
        if ($show_playlists_only) {
            $playlists = get_terms(array(
                'taxonomy' => 'media_series',
                'hide_empty' => true,
                'parent' => 0, // Only top-level
            ));
            
            if (!empty($playlists) && !is_wp_error($playlists)) {
                foreach ($playlists as $playlist) {
                    // Skip hidden playlists
                    $hide = get_term_meta($playlist->term_id, 'hide_from_archive', true);
                    if ($hide === '1') continue;
                    
                    echo $this->render_playlist_card($playlist);
                    $has_results = true;
                }
            }
        }
        // CASE 2: Media type filter (Audio/Video) - show only NON-playlist items
        // Playlist items are private - only accessible through their playlist
        else if ($show_media_only) {
            // Exclude playlist items - they are private
            $exclude_ids = array();
            $all_playlists = get_terms(array('taxonomy' => 'media_series', 'hide_empty' => true));
            if (!empty($all_playlists) && !is_wp_error($all_playlists)) {
                foreach ($all_playlists as $p) {
                    $items = get_posts(array(
                        'post_type' => 'mindful_media',
                        'posts_per_page' => -1,
                        'fields' => 'ids',
                        'tax_query' => array(array(
                            'taxonomy' => 'media_series',
                            'field' => 'term_id',
                            'terms' => $p->term_id,
                        ))
                    ));
                    if (!empty($items)) {
                        $exclude_ids = array_merge($exclude_ids, $items);
                    }
                }
            }
            
            if (!empty($exclude_ids)) {
                $query_args['post__not_in'] = $exclude_ids;
            }
            
            $query = new WP_Query($query_args);
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    echo $this->render_media_item(get_the_ID(), array());
                    $has_results = true;
                }
            }
            wp_reset_postdata();
        }
        // CASE 3: "All" or other filters - show both playlists and individual items
        else {
            // First show playlists
            $playlists = get_terms(array(
                'taxonomy' => 'media_series',
                'hide_empty' => true,
                'parent' => 0,
            ));
            
            if (!empty($playlists) && !is_wp_error($playlists)) {
                foreach ($playlists as $playlist) {
                    $hide = get_term_meta($playlist->term_id, 'hide_from_archive', true);
                    if ($hide === '1') continue;
                    
                    // If searching, filter playlists by search term
                    if (!empty($search)) {
                        if (stripos($playlist->name, $search) === false && 
                            stripos($playlist->description, $search) === false) {
                            continue;
                        }
                    }
                    
                    echo $this->render_playlist_card($playlist);
                    $has_results = true;
                }
            }
            
            // Exclude playlist items from individual results
            $exclude_ids = array();
            $all_playlists = get_terms(array('taxonomy' => 'media_series', 'hide_empty' => true));
            if (!empty($all_playlists) && !is_wp_error($all_playlists)) {
                foreach ($all_playlists as $p) {
                    $items = get_posts(array(
                        'post_type' => 'mindful_media',
                        'posts_per_page' => -1,
                        'fields' => 'ids',
                        'tax_query' => array(array(
                            'taxonomy' => 'media_series',
                            'field' => 'term_id',
                            'terms' => $p->term_id,
                        ))
                    ));
                    if (!empty($items)) {
                        $exclude_ids = array_merge($exclude_ids, $items);
                    }
                }
            }
            
            if (!empty($exclude_ids)) {
                $query_args['post__not_in'] = $exclude_ids;
            }
            
            $query = new WP_Query($query_args);
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    echo $this->render_media_item(get_the_ID(), array());
                    $has_results = true;
                }
            }
            wp_reset_postdata();
        }
        
        if (!$has_results) {
            echo '<div class="mindful-media-no-results"><p>' . __('No content found matching your criteria.', 'mindful-media') . '</p></div>';
        }
        
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $html
        ));
    }
    
    /**
     * Check if current post/page has mindful media shortcodes
     */
    private function has_mindful_media_shortcode() {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        return (
            has_shortcode($post->post_content, 'mindful_media_archive') ||
            has_shortcode($post->post_content, 'mindful_media') ||
            has_shortcode($post->post_content, 'mindful_media_browse')
        );
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Always enqueue our assets when the class is loaded
        // This ensures they're available when shortcodes are used
        wp_enqueue_style(
            'mindful-media-frontend',
            MINDFUL_MEDIA_PLUGIN_URL . 'public/css/frontend.css',
            array(),
            MINDFUL_MEDIA_VERSION
        );
        wp_enqueue_script(
            'mindful-media-frontend',
            MINDFUL_MEDIA_PLUGIN_URL . 'public/js/frontend.js',
            array('jquery'),
            MINDFUL_MEDIA_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('mindful-media-frontend', 'mindfulMediaAjax', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mindful_media_ajax_nonce')
        ));
    }
    
    /**
     * AJAX handler for loading inline player
     */
    public function ajax_load_inline_player() {
        check_ajax_referer('mindful_media_ajax_nonce', 'nonce');
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }
        
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'mindful_media') {
            wp_send_json_error('Invalid post');
        }
        
        // Check if item belongs to a password-protected playlist FIRST
        // Also check PARENT playlists (for items in child modules)
        $playlists = get_the_terms($post_id, 'media_series');
        if ($playlists && !is_wp_error($playlists)) {
            foreach ($playlists as $playlist) {
                // Check this playlist AND its parent hierarchy for password protection
                $playlists_to_check = array($playlist);
                
                // Add parent playlist if this is a child module
                if ($playlist->parent > 0) {
                    $parent = get_term($playlist->parent, 'media_series');
                    if ($parent && !is_wp_error($parent)) {
                        $playlists_to_check[] = $parent;
                    }
                }
                
                foreach ($playlists_to_check as $check_playlist) {
                    $playlist_password_enabled = get_term_meta($check_playlist->term_id, 'password_enabled', true);
                    if ($playlist_password_enabled === '1') {
                        // Check cookie for playlist access
                        $cookie_name = 'mindful_media_playlist_access_' . $check_playlist->term_id;
                        $has_playlist_access = isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name] === wp_hash($check_playlist->term_id . 'mindful_media_playlist_access');
                        
                        if (!$has_playlist_access) {
                            wp_send_json_error(array(
                                'message' => 'playlist_password_required',
                                'post_id' => $post_id,
                                'playlist_id' => $check_playlist->term_id,
                                'playlist_name' => $check_playlist->name
                            ));
                        }
                        // If playlist access granted, skip individual password check
                        break 2; // Break out of both loops
                    }
                }
            }
        }
        
        // Check if individual item is password protected (only if not in protected playlist)
        $is_protected = get_post_meta($post_id, '_mindful_media_password_protected', true);
        if ($is_protected === '1') {
            // Check cookie for access
            $cookie_name = 'mindful_media_access_' . $post_id;
            $has_access = isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name] === wp_hash($post_id . 'mindful_media_access');
            
            if (!$has_access) {
                wp_send_json_error(array(
                    'message' => 'password_required',
                    'post_id' => $post_id
                ));
            }
        }
        
        $media_url = get_post_meta($post_id, '_mindful_media_url', true);
        $media_source = get_post_meta($post_id, '_mindful_media_source', true);
        $custom_embed = get_post_meta($post_id, '_mindful_media_custom_embed', true);
        
        if (empty($media_url) && empty($custom_embed)) {
            wp_send_json_error('No media found');
        }
        
        if (class_exists('MindfulMedia_Players')) {
            $player = new MindfulMedia_Players();
            
            // Get settings for player size
            $settings = get_option('mindful_media_settings', array());
            $player_autoplay = isset($settings['player_autoplay']) ? $settings['player_autoplay'] : '0';
            
            // Determine if audio or video for correct image size
            $media_types = get_the_terms($post_id, 'media_type');
            $is_audio = false;
            if ($media_types && !is_wp_error($media_types)) {
                $type_name = strtolower($media_types[0]->name);
                $is_audio = (strpos($type_name, 'audio') !== false);
            }
            $image_size = $is_audio ? 'mindful-media-audio' : 'mindful-media-video';
            
            // Get featured image for single-page-style rendering
            $featured_image = get_the_post_thumbnail_url($post_id, $image_size);
            
            $player_html = $player->render_player($media_url, array(
                'controls' => true,
                'autoplay' => $player_autoplay === '1',
                'source' => $media_source,
                'custom_embed' => $custom_embed,
                'class' => 'inline-player-embed',
                'featured_image' => $featured_image,
                'post_title' => $post->post_title
            ));
            
            // Wrap in a clean container for inline display
            $wrapped_html = '<div class="mindful-media-inline-embed">';
            $wrapped_html .= $player_html;
            $wrapped_html .= '</div>';
            
            // Add scroll indicator arrow (pointing DOWN)
            $wrapped_html .= '<div class="mindful-media-scroll-indicator" onclick="document.querySelector(\'.mindful-media-browse-below\').scrollIntoView({behavior: \'smooth\'});" style="cursor: pointer;">';
            $wrapped_html .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16.59 8.59L12 13.17 7.41 8.59 6 10l6 6 6-6z"/></svg>';
            $wrapped_html .= '</div>';
            
            $teachers = get_the_terms($post_id, 'media_teacher');
            $teacher_name = '';
            if ($teachers && !is_wp_error($teachers)) {
                $teacher_name = implode(', ', wp_list_pluck($teachers, 'name'));
            }
            
            // Check if this item is part of a playlist
            $playlists = get_the_terms($post_id, 'media_series');
            $playlist_data = null;
            
            if ($playlists && !is_wp_error($playlists)) {
                // Get the first playlist (assume item is only in one playlist)
                $playlist = $playlists[0];
                
                // Check if this playlist has a parent (is a child module)
                $parent_playlist = null;
                $root_playlist = $playlist;
                
                if ($playlist->parent > 0) {
                    $parent_playlist = get_term($playlist->parent, 'media_series');
                    if ($parent_playlist && !is_wp_error($parent_playlist)) {
                        $root_playlist = $parent_playlist;
                    }
                }
                
                // Check if root playlist has children (is a parent with modules)
                $child_modules = get_terms(array(
                    'taxonomy' => 'media_series',
                    'parent' => $root_playlist->term_id,
                    'hide_empty' => false,
                    'orderby' => 'term_order',
                    'order' => 'ASC'
                ));
                
                $has_modules = !empty($child_modules) && !is_wp_error($child_modules);
                
                if ($has_modules) {
                    // Build hierarchical structure with modules
                    $modules = array();
                    $total_items = 0;
                    $global_index = 0;
                    $current_module_index = 0;
                    $current_item_index = 0;
                    
                    foreach ($child_modules as $module_index => $module) {
                        // Get items in this module
                        $module_items_query = new WP_Query(array(
                            'post_type' => 'mindful_media',
                            'posts_per_page' => -1,
                            'post_status' => 'publish',
                            'tax_query' => array(
                                array(
                                    'taxonomy' => 'media_series',
                                    'field' => 'term_id',
                                    'terms' => $module->term_id,
                                )
                            ),
                            'orderby' => 'date',
                            'order' => 'ASC'
                        ));
                        
                        $module_items = array();
                        $item_index = 0;
                        
                        if ($module_items_query->have_posts()) {
                            while ($module_items_query->have_posts()) {
                                $module_items_query->the_post();
                                $item_id = get_the_ID();
                                
                                $is_current = ($item_id == $post_id);
                                if ($is_current) {
                                    $current_module_index = $module_index;
                                    $current_item_index = $item_index;
                                }
                                
                                // Get duration
                                $duration_raw = get_post_meta($item_id, '_mindful_media_duration', true);
                                $duration_formatted = '';
                                if ($duration_raw) {
                                    $parts = explode(':', $duration_raw);
                                    if (count($parts) >= 2) {
                                        $hours = intval($parts[0]);
                                        $minutes = intval($parts[1]);
                                        if ($hours > 0) {
                                            $duration_formatted = $hours . 'h ' . $minutes . 'm';
                                        } else {
                                            $duration_formatted = $minutes . ' min';
                                        }
                                    }
                                }
                                
                                $module_items[] = array(
                                    'id' => $item_id,
                                    'title' => get_the_title(),
                                    'duration' => $duration_formatted,
                                    'permalink' => get_permalink($item_id),
                                    'is_current' => $is_current,
                                    'global_index' => $global_index
                                );
                                
                                $item_index++;
                                $global_index++;
                                $total_items++;
                            }
                            wp_reset_postdata();
                        }
                        
                        if (!empty($module_items)) {
                            $modules[] = array(
                                'id' => $module->term_id,
                                'name' => $module->name,
                                'description' => $module->description,
                                'items' => $module_items,
                                'is_current_module' => ($module->term_id == $playlist->term_id)
                            );
                        }
                    }
                    
                    $playlist_data = array(
                        'name' => $root_playlist->name,
                        'description' => $root_playlist->description,
                        'has_modules' => true,
                        'modules' => $modules,
                        'current_module_index' => $current_module_index,
                        'current_item_index' => $current_item_index,
                        'total_items' => $total_items
                    );
                } else {
                    // Simple playlist without modules - get items directly
                    $playlist_items_query = new WP_Query(array(
                        'post_type' => 'mindful_media',
                        'posts_per_page' => -1,
                        'post_status' => 'publish',
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'media_series',
                                'field' => 'term_id',
                                'terms' => $root_playlist->term_id,
                            )
                        ),
                        'orderby' => 'date',
                        'order' => 'ASC'
                    ));
                    
                    $playlist_items = array();
                    $current_index = 0;
                    $index = 0;
                    
                    if ($playlist_items_query->have_posts()) {
                        while ($playlist_items_query->have_posts()) {
                            $playlist_items_query->the_post();
                            $item_id = get_the_ID();
                            
                            if ($item_id == $post_id) {
                                $current_index = $index;
                            }
                            
                            // Get duration
                            $duration_raw = get_post_meta($item_id, '_mindful_media_duration', true);
                            $duration_formatted = '';
                            if ($duration_raw) {
                                $parts = explode(':', $duration_raw);
                                if (count($parts) >= 2) {
                                    $hours = intval($parts[0]);
                                    $minutes = intval($parts[1]);
                                    if ($hours > 0) {
                                        $duration_formatted = $hours . 'h ' . $minutes . 'm';
                                    } else {
                                        $duration_formatted = $minutes . ' min';
                                    }
                                }
                            }
                            
                            $playlist_items[] = array(
                                'id' => $item_id,
                                'title' => get_the_title(),
                                'duration' => $duration_formatted,
                                'permalink' => get_permalink($item_id),
                                'is_current' => ($item_id == $post_id)
                            );
                            
                            $index++;
                        }
                        wp_reset_postdata();
                    }
                    
                    $playlist_data = array(
                        'name' => $root_playlist->name,
                        'description' => $root_playlist->description,
                        'has_modules' => false,
                        'items' => $playlist_items,
                        'current_index' => $current_index,
                        'total_items' => count($playlist_items)
                    );
                }
            }
            
            // Get additional metadata for single-page-style display
            $description = wp_trim_words(strip_tags($post->post_content), 100, '...');
            $date = get_the_date('F j, Y', $post_id);
            $duration_raw = get_post_meta($post_id, '_mindful_media_duration', true);
            $duration = '';
            if ($duration_raw) {
                $parts = explode(':', $duration_raw);
                if (count($parts) >= 2) {
                    $hours = intval($parts[0]);
                    $minutes = intval($parts[1]);
                    if ($hours > 0) {
                        $duration = $hours . 'h ' . $minutes . 'm';
                    } else {
                        $duration = $minutes . ' min';
                    }
                }
            }
            
            // Get media type
            $media_type_name = '';
            if ($media_types && !is_wp_error($media_types)) {
                $media_type_name = $media_types[0]->name;
            }
            
            // Get categories
            $categories = get_the_terms($post_id, 'media_category');
            $category_names = array();
            if ($categories && !is_wp_error($categories)) {
                foreach ($categories as $cat) {
                    $category_names[] = $cat->name;
                }
            }
            
            // Get topics
            $topics = get_the_terms($post_id, 'media_topic');
            $topic_names = array();
            if ($topics && !is_wp_error($topics)) {
                foreach ($topics as $t) {
                    $topic_names[] = $t->name;
                }
            }
            
            wp_send_json_success(array(
                'player' => $wrapped_html,
                'title' => $post->post_title,
                'teacher' => $teacher_name,
                'permalink' => get_permalink($post_id),
                'playlist' => $playlist_data,
                'description' => $description,
                'date' => $date,
                'duration' => $duration,
                'media_type' => $media_type_name,
                'categories' => $category_names,
                'topics' => $topic_names
            ));
        } else {
            wp_send_json_error('Player class not found');
        }
    }
    
    /**
     * AJAX handler for password check
     */
    public function ajax_check_password() {
        check_ajax_referer('mindful_media_ajax_nonce', 'nonce');
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $password = isset($_POST['password']) ? sanitize_text_field($_POST['password']) : '';
        
        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }
        
        // Check if post is protected
        $is_protected = get_post_meta($post_id, '_mindful_media_password_protected', true);
        
        if ($is_protected !== '1') {
            wp_send_json_success(array('access' => true));
        }
        
        // Check if already authenticated via cookie
        $cookie_name = 'mindful_media_access_' . $post_id;
        $has_access = isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name] === wp_hash($post_id . 'mindful_media_access');
        
        if ($has_access) {
            wp_send_json_success(array('access' => true));
        }
        
        // Verify password
        $stored_hash = get_post_meta($post_id, '_mindful_media_password_hash', true);
        
        if (empty($stored_hash)) {
            wp_send_json_error('No password set');
        }
        
        if (wp_check_password($password, $stored_hash)) {
            // Set cookie for 24 hours
            $cookie_name = 'mindful_media_access_' . $post_id;
            $cookie_value = wp_hash($post_id . 'mindful_media_access');
            setcookie($cookie_name, $cookie_value, time() + (24 * 60 * 60), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            
            wp_send_json_success(array('access' => true));
        } else {
            wp_send_json_error('Incorrect password');
        }
    }
    
    /**
     * AJAX handler for checking playlist password
     */
    public function ajax_check_playlist_password() {
        check_ajax_referer('mindful_media_ajax_nonce', 'nonce');
        
        $playlist_id = isset($_POST['playlist_id']) ? intval($_POST['playlist_id']) : 0;
        $password = isset($_POST['password']) ? sanitize_text_field($_POST['password']) : '';
        
        if (!$playlist_id) {
            wp_send_json_error('Invalid playlist ID');
        }
        
        // Check if playlist is protected
        $is_protected = get_term_meta($playlist_id, 'password_enabled', true);
        
        if ($is_protected !== '1') {
            wp_send_json_success(array('access' => true));
        }
        
        // Check if already authenticated via cookie
        $cookie_name = 'mindful_media_playlist_access_' . $playlist_id;
        $has_access = isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name] === wp_hash($playlist_id . 'mindful_media_playlist_access');
        
        if ($has_access) {
            wp_send_json_success(array('access' => true));
        }
        
        // Verify password
        $stored_password = get_term_meta($playlist_id, 'playlist_password', true);
        
        if (empty($stored_password)) {
            wp_send_json_error('No password set');
        }
        
        if ($password === $stored_password) {
            // Set cookie for 24 hours
            $cookie_name = 'mindful_media_playlist_access_' . $playlist_id;
            $cookie_value = wp_hash($playlist_id . 'mindful_media_playlist_access');
            setcookie($cookie_name, $cookie_value, time() + (24 * 60 * 60), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            
            wp_send_json_success(array('access' => true));
        } else {
            wp_send_json_error('Incorrect password');
        }
    }
    
    /**
     * AJAX handler for browse content below player
     */
    public function ajax_browse_content() {
        check_ajax_referer('mindful_media_ajax_nonce', 'nonce');
        
        $exclude_id = isset($_POST['exclude_id']) ? intval($_POST['exclude_id']) : 0;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 9;
        
        $args = array(
            'post_type' => 'mindful_media',
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'orderby' => 'rand'
        );
        
        if ($exclude_id) {
            $args['post__not_in'] = array($exclude_id);
        }
        
        $query = new WP_Query($args);
        $items = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                // Get featured image
                $image_url = '';
                if (has_post_thumbnail($post_id)) {
                    $image_url = get_the_post_thumbnail_url($post_id, 'medium_large');
                } else {
                    $image_url = plugins_url('../assets/default-thumbnail.jpg', __FILE__);
                }
                
                // Get teacher
                $teachers = get_the_terms($post_id, 'teacher');
                $teacher = '';
                if ($teachers && !is_wp_error($teachers)) {
                    $teacher = $teachers[0]->name;
                }
                
                // Get duration
                $duration_raw = get_post_meta($post_id, '_mindful_media_duration', true);
                $duration = '';
                if ($duration_raw) {
                    $parts = explode(':', $duration_raw);
                    if (count($parts) >= 2) {
                        $hours = intval($parts[0]);
                        $minutes = intval($parts[1]);
                        if ($hours > 0) {
                            $duration = $hours . 'h ' . $minutes . 'm';
                        } else {
                            $duration = $minutes . ' min';
                        }
                    }
                }
                
                $items[] = array(
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'image' => $image_url,
                    'teacher' => $teacher,
                    'duration' => $duration,
                    'permalink' => get_permalink($post_id)
                );
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success(array('items' => $items));
    }
} 