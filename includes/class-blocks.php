<?php
/**
 * Gutenberg Blocks Class
 * Registers and handles custom blocks for MindfulMedia
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MindfulMedia_Blocks {
    
    public function __construct() {
        add_action('init', array($this, 'register_blocks'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
    }
    
    /**
     * Register custom Gutenberg blocks
     */
    public function register_blocks() {
        // Register the browse block
        register_block_type('mindful-media/browse', array(
            'render_callback' => array($this, 'render_browse_block'),
            'attributes' => array(
                'show' => array(
                    'type' => 'string',
                    'default' => 'all'
                ),
                'layout' => array(
                    'type' => 'string',
                    'default' => 'cards'
                ),
                'featured' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'columns' => array(
                    'type' => 'number',
                    'default' => 4
                ),
                'limit' => array(
                    'type' => 'number',
                    'default' => 12
                ),
                'title' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'showCounts' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'className' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                // Style attributes
                'backgroundColor' => array(
                    'type' => 'string',
                    'default' => '#ffffff'
                ),
                'textColor' => array(
                    'type' => 'string',
                    'default' => '#0f0f0f'
                ),
                'headingColor' => array(
                    'type' => 'string',
                    'default' => '#0f0f0f'
                ),
                'cardBgColor' => array(
                    'type' => 'string',
                    'default' => '#ffffff'
                ),
                'navBgColor' => array(
                    'type' => 'string',
                    'default' => '#f2f2f2'
                ),
                'titleFontSize' => array(
                    'type' => 'number',
                    'default' => 24
                )
            )
        ));
        
        // Register the media embed block
        register_block_type('mindful-media/embed', array(
            'render_callback' => array($this, 'render_embed_block'),
            'attributes' => array(
                'mediaId' => array(
                    'type' => 'number',
                    'default' => 0
                ),
                'playlistSlug' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'showThumbnail' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'autoplay' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'size' => array(
                    'type' => 'string',
                    'default' => 'medium'
                ),
                'className' => array(
                    'type' => 'string',
                    'default' => ''
                )
            )
        ));
        
        // Register the archive block
        register_block_type('mindful-media/archive', array(
            'render_callback' => array($this, 'render_archive_block'),
            'attributes' => array(
                'perPage' => array(
                    'type' => 'number',
                    'default' => 12
                ),
                'showFilters' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showPagination' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'category' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'type' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'teacher' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'className' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                // Style attributes
                'backgroundColor' => array(
                    'type' => 'string',
                    'default' => '#ffffff'
                ),
                'textColor' => array(
                    'type' => 'string',
                    'default' => '#333333'
                ),
                'accentColor' => array(
                    'type' => 'string',
                    'default' => '#8B0000'
                )
            )
        ));
        
        // Register the category row block (Netflix-style slider)
        register_block_type('mindful-media/row', array(
            'render_callback' => array($this, 'render_row_block'),
            'attributes' => array(
                'taxonomy' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'term' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'title' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'limit' => array(
                    'type' => 'number',
                    'default' => 10
                ),
                'orderby' => array(
                    'type' => 'string',
                    'default' => 'date'
                ),
                'order' => array(
                    'type' => 'string',
                    'default' => 'DESC'
                ),
                'featured' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'showLink' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'className' => array(
                    'type' => 'string',
                    'default' => ''
                )
            )
        ));
    }
    
    /**
     * Enqueue editor assets for blocks
     */
    public function enqueue_editor_assets() {
        // Enqueue the editor script
        wp_enqueue_script(
            'mindful-media-blocks-editor',
            MINDFUL_MEDIA_PLUGIN_URL . 'admin/js/blocks.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render'),
            MINDFUL_MEDIA_VERSION,
            true
        );
        
        // Enqueue editor styles
        wp_enqueue_style(
            'mindful-media-blocks-editor',
            MINDFUL_MEDIA_PLUGIN_URL . 'admin/css/blocks-editor.css',
            array('wp-edit-blocks'),
            MINDFUL_MEDIA_VERSION
        );
        
        // Pass data to the editor
        wp_localize_script('mindful-media-blocks-editor', 'mindfulMediaBlocks', array(
            'pluginUrl' => MINDFUL_MEDIA_PLUGIN_URL,
            'mediaItems' => $this->get_media_items_for_select(),
            'playlists' => $this->get_playlists_for_select(),
            'teachers' => $this->get_taxonomy_terms_for_select('media_teacher'),
            'categories' => $this->get_taxonomy_terms_for_select('media_category'),
            'types' => $this->get_taxonomy_terms_for_select('media_type'),
            'topics' => $this->get_taxonomy_terms_for_select('media_topic'),
        ));
    }
    
    /**
     * Render browse block
     */
    public function render_browse_block($attributes) {
        $shortcode_atts = array(
            'show' => $attributes['show'] ?? 'all',
            'layout' => $attributes['layout'] ?? 'cards',
            'featured' => ($attributes['featured'] ?? false) ? 'true' : 'false',
            'columns' => $attributes['columns'] ?? 4,
            'limit' => $attributes['limit'] ?? 12,
            'title' => $attributes['title'] ?? '',
            'show_counts' => ($attributes['showCounts'] ?? true) ? 'true' : 'false',
            'class' => $attributes['className'] ?? ''
        );
        
        $shortcode_str = '[mindful_media_browse';
        foreach ($shortcode_atts as $key => $value) {
            if (!empty($value) || $value === '0') {
                $shortcode_str .= ' ' . $key . '="' . esc_attr($value) . '"';
            }
        }
        $shortcode_str .= ']';
        
        $content = do_shortcode($shortcode_str);
        
        // Apply custom styles if set
        $bg_color = $attributes['backgroundColor'] ?? '#ffffff';
        $text_color = $attributes['textColor'] ?? '#0f0f0f';
        $heading_color = $attributes['headingColor'] ?? '#0f0f0f';
        $card_bg = $attributes['cardBgColor'] ?? '#ffffff';
        $nav_bg = $attributes['navBgColor'] ?? '#f2f2f2';
        $title_size = $attributes['titleFontSize'] ?? 24;
        
        // Generate unique ID for scoped styles
        $block_id = 'mm-browse-' . uniqid();
        
        // Build inline style block
        $style = '<style>';
        $style .= "#$block_id.mindful-media-browse { background: $bg_color; color: $text_color; }";
        $style .= "#$block_id .mindful-media-browse-section-title { color: $heading_color; font-size: {$title_size}px; }";
        $style .= "#$block_id .mindful-media-browse-card { background: $card_bg; }";
        $style .= "#$block_id .mindful-media-browse-card-title { color: $heading_color; }";
        $style .= "#$block_id .mindful-media-browse-card-count { color: $text_color; }";
        $style .= "#$block_id .mindful-media-browse-nav { background: $nav_bg; }";
        $style .= "#$block_id .mindful-media-browse-nav-item { color: $text_color; }";
        $style .= '</style>';
        
        // Wrap content with ID for scoped styles
        $content = str_replace('class="mindful-media-browse', 'id="' . $block_id . '" class="mindful-media-browse', $content);
        
        return $style . $content;
    }
    
    /**
     * Render embed block
     */
    public function render_embed_block($attributes) {
        $shortcode_atts = array();
        
        if (!empty($attributes['mediaId'])) {
            $shortcode_atts['id'] = $attributes['mediaId'];
        }
        
        if (!empty($attributes['playlistSlug'])) {
            $shortcode_atts['playlist'] = $attributes['playlistSlug'];
        }
        
        $shortcode_atts['show_thumbnail'] = ($attributes['showThumbnail'] ?? true) ? 'true' : 'false';
        $shortcode_atts['autoplay'] = ($attributes['autoplay'] ?? false) ? 'true' : 'false';
        $shortcode_atts['size'] = $attributes['size'] ?? 'medium';
        
        if (!empty($attributes['className'])) {
            $shortcode_atts['class'] = $attributes['className'];
        }
        
        $shortcode_str = '[mindful_media';
        foreach ($shortcode_atts as $key => $value) {
            $shortcode_str .= ' ' . $key . '="' . esc_attr($value) . '"';
        }
        $shortcode_str .= ']';
        
        return do_shortcode($shortcode_str);
    }
    
    /**
     * Render archive block
     */
    public function render_archive_block($attributes) {
        $shortcode_atts = array(
            'per_page' => $attributes['perPage'] ?? 12,
            'show_filters' => ($attributes['showFilters'] ?? true) ? 'true' : 'false',
            'show_pagination' => ($attributes['showPagination'] ?? true) ? 'true' : 'false',
        );
        
        if (!empty($attributes['category'])) {
            $shortcode_atts['category'] = $attributes['category'];
        }
        
        if (!empty($attributes['type'])) {
            $shortcode_atts['type'] = $attributes['type'];
        }
        
        if (!empty($attributes['teacher'])) {
            $shortcode_atts['teacher'] = $attributes['teacher'];
        }
        
        if (!empty($attributes['className'])) {
            $shortcode_atts['class'] = $attributes['className'];
        }
        
        $shortcode_str = '[mindful_media_archive';
        foreach ($shortcode_atts as $key => $value) {
            $shortcode_str .= ' ' . $key . '="' . esc_attr($value) . '"';
        }
        $shortcode_str .= ']';
        
        $content = do_shortcode($shortcode_str);
        
        // Apply custom styles if set
        $bg_color = $attributes['backgroundColor'] ?? '#ffffff';
        $text_color = $attributes['textColor'] ?? '#333333';
        $accent_color = $attributes['accentColor'] ?? '#8B0000';
        
        // Generate unique ID for scoped styles
        $block_id = 'mm-archive-' . uniqid();
        
        // Build inline style block
        $style = '<style>';
        $style .= "#$block_id.mindful-media-container { background: $bg_color; }";
        $style .= "#$block_id .mindful-media-item-title { color: $text_color; }";
        $style .= "#$block_id .mindful-media-item-teacher a { color: $accent_color !important; }";
        $style .= "#$block_id .mindful-media-item-button { background: $accent_color; }";
        $style .= "#$block_id .mindful-media-item-cta { background: $accent_color; }";
        $style .= '</style>';
        
        // Wrap content with ID for scoped styles
        $content = str_replace('class="mindful-media-container', 'id="' . $block_id . '" class="mindful-media-container', $content);
        
        return $style . $content;
    }
    
    /**
     * Render category row block (Netflix-style slider)
     */
    public function render_row_block($attributes) {
        $shortcode_atts = array(
            'taxonomy' => $attributes['taxonomy'] ?? '',
            'term' => $attributes['term'] ?? '',
            'title' => $attributes['title'] ?? '',
            'limit' => $attributes['limit'] ?? 10,
            'orderby' => $attributes['orderby'] ?? 'date',
            'order' => $attributes['order'] ?? 'DESC',
            'featured' => ($attributes['featured'] ?? false) ? 'true' : 'false',
            'show_link' => ($attributes['showLink'] ?? true) ? 'true' : 'false',
            'class' => $attributes['className'] ?? ''
        );
        
        $shortcode_str = '[mindful_media_row';
        foreach ($shortcode_atts as $key => $value) {
            if (!empty($value) || $value === '0' || $value === 'false') {
                $shortcode_str .= ' ' . $key . '="' . esc_attr($value) . '"';
            }
        }
        $shortcode_str .= ']';
        
        return do_shortcode($shortcode_str);
    }
    
    /**
     * Get media items for block select control
     */
    private function get_media_items_for_select() {
        $items = get_posts(array(
            'post_type' => 'mindful_media',
            'posts_per_page' => 100,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => 'publish'
        ));
        
        $options = array(
            array('value' => 0, 'label' => __('-- Select Media Item --', 'mindful-media'))
        );
        
        foreach ($items as $item) {
            $options[] = array(
                'value' => $item->ID,
                'label' => $item->post_title
            );
        }
        
        return $options;
    }
    
    /**
     * Get playlists for block select control
     */
    private function get_playlists_for_select() {
        $playlists = get_terms(array(
            'taxonomy' => 'media_series',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        $options = array(
            array('value' => '', 'label' => __('-- Select Playlist --', 'mindful-media'))
        );
        
        if (!empty($playlists) && !is_wp_error($playlists)) {
            foreach ($playlists as $playlist) {
                $options[] = array(
                    'value' => $playlist->slug,
                    'label' => $playlist->name . ' (' . $playlist->count . ' items)'
                );
            }
        }
        
        return $options;
    }
    
    /**
     * Get taxonomy terms for block select control
     */
    private function get_taxonomy_terms_for_select($taxonomy) {
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        $options = array(
            array('value' => '', 'label' => __('-- All --', 'mindful-media'))
        );
        
        if (!empty($terms) && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $options[] = array(
                    'value' => $term->slug,
                    'label' => $term->name
                );
            }
        }
        
        return $options;
    }
}
