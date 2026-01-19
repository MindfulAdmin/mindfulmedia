<?php
/**
 * Elementor Browse Widget
 */

if (!defined('ABSPATH')) {
    exit;
}

class MindfulMedia_Elementor_Browse_Widget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'mindful_media_browse';
    }
    
    public function get_title() {
        return __('MindfulMedia Browse', 'mindful-media');
    }
    
    public function get_icon() {
        return 'eicon-gallery-grid';
    }
    
    public function get_categories() {
        return array('mindful-media');
    }
    
    public function get_keywords() {
        return array('media', 'browse', 'landing', 'navigation', 'teachers', 'playlists');
    }
    
    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __('Display Settings', 'mindful-media'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'show',
            array(
                'label' => __('Sections to Show', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'all',
                'options' => array(
                    'all' => __('All Sections', 'mindful-media'),
                    'navigation' => __('Navigation Only', 'mindful-media'),
                    'teachers' => __('Teachers', 'mindful-media'),
                    'topics' => __('Topics', 'mindful-media'),
                    'playlists' => __('Playlists & Series', 'mindful-media'),
                    'types' => __('Media Types', 'mindful-media'),
                    'categories' => __('Categories', 'mindful-media'),
                    'teachers,topics' => __('Teachers & Topics', 'mindful-media'),
                    'playlists,types' => __('Playlists & Types', 'mindful-media'),
                ),
            )
        );
        
        $this->add_control(
            'layout',
            array(
                'label' => __('Layout', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'cards',
                'options' => array(
                    'cards' => __('Cards', 'mindful-media'),
                    'banners' => __('Banners', 'mindful-media'),
                    'list' => __('List', 'mindful-media'),
                ),
            )
        );
        
        $this->add_control(
            'columns',
            array(
                'label' => __('Columns', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array(''),
                'range' => array(
                    '' => array(
                        'min' => 2,
                        'max' => 6,
                        'step' => 1,
                    ),
                ),
                'default' => array(
                    'unit' => '',
                    'size' => 4,
                ),
            )
        );
        
        $this->add_control(
            'limit',
            array(
                'label' => __('Items per Section', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 4,
                'max' => 24,
                'step' => 4,
                'default' => 12,
            )
        );
        
        $this->end_controls_section();
        
        // Featured Content Section
        $this->start_controls_section(
            'featured_section',
            array(
                'label' => __('Featured Content', 'mindful-media'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'featured',
            array(
                'label' => __('Show Featured Hero', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'mindful-media'),
                'label_off' => __('Hide', 'mindful-media'),
                'return_value' => 'true',
                'default' => '',
                'description' => __('Display a hero section with featured content', 'mindful-media'),
            )
        );
        
        $this->end_controls_section();
        
        // Additional Options
        $this->start_controls_section(
            'additional_section',
            array(
                'label' => __('Additional Options', 'mindful-media'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'title',
            array(
                'label' => __('Custom Title', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
                'placeholder' => __('Optional title...', 'mindful-media'),
            )
        );
        
        $this->add_control(
            'show_counts',
            array(
                'label' => __('Show Item Counts', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'mindful-media'),
                'label_off' => __('Hide', 'mindful-media'),
                'return_value' => 'true',
                'default' => 'true',
            )
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        $atts = array(
            'show' => $settings['show'],
            'layout' => $settings['layout'],
            'columns' => isset($settings['columns']['size']) ? $settings['columns']['size'] : 4,
            'limit' => $settings['limit'],
            'featured' => $settings['featured'] === 'true' ? 'true' : 'false',
            'title' => $settings['title'],
            'show_counts' => $settings['show_counts'] === 'true' ? 'true' : 'false',
        );
        
        $shortcode = '[mindful_media_browse';
        foreach ($atts as $key => $value) {
            if (!empty($value) || $value === '0') {
                $shortcode .= ' ' . $key . '="' . esc_attr($value) . '"';
            }
        }
        $shortcode .= ']';
        
        echo do_shortcode($shortcode);
    }
}
