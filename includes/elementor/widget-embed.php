<?php
/**
 * Elementor Embed Widget
 */

if (!defined('ABSPATH')) {
    exit;
}

class MindfulMedia_Elementor_Embed_Widget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'mindful_media_embed';
    }
    
    public function get_title() {
        return __('MindfulMedia Embed', 'mindful-media');
    }
    
    public function get_icon() {
        return 'eicon-play';
    }
    
    public function get_categories() {
        return array('mindful-media');
    }
    
    public function get_keywords() {
        return array('media', 'embed', 'video', 'audio', 'playlist');
    }
    
    protected function register_controls() {
        // Media Selection Section
        $this->start_controls_section(
            'media_section',
            array(
                'label' => __('Media Selection', 'mindful-media'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        // Get media items for select
        $media_options = array('' => __('-- Select Media Item --', 'mindful-media'));
        $media_items = get_posts(array(
            'post_type' => 'mindful_media',
            'posts_per_page' => 100,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => 'publish'
        ));
        foreach ($media_items as $item) {
            $media_options[$item->ID] = $item->post_title;
        }
        
        $this->add_control(
            'media_id',
            array(
                'label' => __('Select Media Item', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => $media_options,
                'default' => '',
                'description' => __('Choose a single media item to embed', 'mindful-media'),
            )
        );
        
        $this->add_control(
            'or_divider',
            array(
                'type' => \Elementor\Controls_Manager::DIVIDER,
            )
        );
        
        // Get playlists for select
        $playlist_options = array('' => __('-- Select Playlist --', 'mindful-media'));
        $playlists = get_terms(array(
            'taxonomy' => 'media_series',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        if (!empty($playlists) && !is_wp_error($playlists)) {
            foreach ($playlists as $playlist) {
                $playlist_options[$playlist->slug] = $playlist->name . ' (' . $playlist->count . ' items)';
            }
        }
        
        $this->add_control(
            'playlist_slug',
            array(
                'label' => __('Select Playlist', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => $playlist_options,
                'default' => '',
                'description' => __('Or choose a playlist to embed', 'mindful-media'),
            )
        );
        
        $this->end_controls_section();
        
        // Display Options Section
        $this->start_controls_section(
            'display_section',
            array(
                'label' => __('Display Options', 'mindful-media'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'show_thumbnail',
            array(
                'label' => __('Show Thumbnail with Play Button', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'mindful-media'),
                'label_off' => __('No', 'mindful-media'),
                'return_value' => 'true',
                'default' => 'true',
                'description' => __('If disabled, embeds player directly on the page', 'mindful-media'),
            )
        );
        
        $this->add_control(
            'autoplay',
            array(
                'label' => __('Autoplay', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'mindful-media'),
                'label_off' => __('No', 'mindful-media'),
                'return_value' => 'true',
                'default' => '',
            )
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        $shortcode_atts = array();
        
        if (!empty($settings['media_id'])) {
            $shortcode_atts['id'] = $settings['media_id'];
        }
        
        if (!empty($settings['playlist_slug'])) {
            $shortcode_atts['playlist'] = $settings['playlist_slug'];
        }
        
        $shortcode_atts['show_thumbnail'] = $settings['show_thumbnail'] === 'true' ? 'true' : 'false';
        $shortcode_atts['autoplay'] = $settings['autoplay'] === 'true' ? 'true' : 'false';
        
        $shortcode = '[mindful_media';
        foreach ($shortcode_atts as $key => $value) {
            $shortcode .= ' ' . $key . '="' . esc_attr($value) . '"';
        }
        $shortcode .= ']';
        
        echo do_shortcode($shortcode);
    }
}
