<?php
/**
 * Elementor Archive Widget
 */

if (!defined('ABSPATH')) {
    exit;
}

class MindfulMedia_Elementor_Archive_Widget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'mindful_media_archive';
    }
    
    public function get_title() {
        return __('MindfulMedia Archive', 'mindful-media');
    }
    
    public function get_icon() {
        return 'eicon-posts-grid';
    }
    
    public function get_categories() {
        return array('mindful-media');
    }
    
    public function get_keywords() {
        return array('media', 'archive', 'library', 'gallery', 'grid');
    }
    
    protected function register_controls() {
        // Display Settings Section
        $this->start_controls_section(
            'display_section',
            array(
                'label' => __('Display Settings', 'mindful-media'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'per_page',
            array(
                'label' => __('Items per Page', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 4,
                'max' => 48,
                'step' => 4,
                'default' => 12,
            )
        );
        
        $this->add_control(
            'show_filters',
            array(
                'label' => __('Show Filters Sidebar', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'mindful-media'),
                'label_off' => __('Hide', 'mindful-media'),
                'return_value' => 'true',
                'default' => 'true',
            )
        );
        
        $this->add_control(
            'show_pagination',
            array(
                'label' => __('Show Pagination', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'mindful-media'),
                'label_off' => __('Hide', 'mindful-media'),
                'return_value' => 'true',
                'default' => 'true',
            )
        );
        
        $this->end_controls_section();
        
        // Pre-filter Section
        $this->start_controls_section(
            'filter_section',
            array(
                'label' => __('Pre-filter Content', 'mindful-media'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        // Get categories for select
        $category_options = array('' => __('-- All Categories --', 'mindful-media'));
        $categories = get_terms(array(
            'taxonomy' => 'media_category',
            'hide_empty' => false,
        ));
        if (!empty($categories) && !is_wp_error($categories)) {
            foreach ($categories as $cat) {
                $category_options[$cat->slug] = $cat->name;
            }
        }
        
        $this->add_control(
            'category',
            array(
                'label' => __('Category', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $category_options,
                'default' => '',
            )
        );
        
        // Get types for select
        $type_options = array('' => __('-- All Types --', 'mindful-media'));
        $types = get_terms(array(
            'taxonomy' => 'media_type',
            'hide_empty' => false,
        ));
        if (!empty($types) && !is_wp_error($types)) {
            foreach ($types as $type) {
                $type_options[$type->slug] = $type->name;
            }
        }
        
        $this->add_control(
            'type',
            array(
                'label' => __('Media Type', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $type_options,
                'default' => '',
            )
        );
        
        // Get teachers for select
        $teacher_options = array('' => __('-- All Teachers --', 'mindful-media'));
        $teachers = get_terms(array(
            'taxonomy' => 'media_teacher',
            'hide_empty' => false,
        ));
        if (!empty($teachers) && !is_wp_error($teachers)) {
            foreach ($teachers as $teacher) {
                $teacher_options[$teacher->slug] = $teacher->name;
            }
        }
        
        $this->add_control(
            'teacher',
            array(
                'label' => __('Teacher', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $teacher_options,
                'default' => '',
            )
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        $atts = array(
            'per_page' => $settings['per_page'],
            'show_filters' => $settings['show_filters'] === 'true' ? 'true' : 'false',
            'show_pagination' => $settings['show_pagination'] === 'true' ? 'true' : 'false',
        );
        
        if (!empty($settings['category'])) {
            $atts['category'] = $settings['category'];
        }
        
        if (!empty($settings['type'])) {
            $atts['type'] = $settings['type'];
        }
        
        if (!empty($settings['teacher'])) {
            $atts['teacher'] = $settings['teacher'];
        }
        
        $shortcode = '[mindful_media_archive';
        foreach ($atts as $key => $value) {
            $shortcode .= ' ' . $key . '="' . esc_attr($value) . '"';
        }
        $shortcode .= ']';
        
        echo do_shortcode($shortcode);
    }
}
