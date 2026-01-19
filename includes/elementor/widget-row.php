<?php
/**
 * Elementor Category Row Widget
 * Netflix-style horizontal slider for media items
 */

if (!defined('ABSPATH')) {
    exit;
}

class MindfulMedia_Elementor_Row_Widget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'mindful_media_row';
    }
    
    public function get_title() {
        return __('MindfulMedia Category Row', 'mindful-media');
    }
    
    public function get_icon() {
        return 'eicon-slider-push';
    }
    
    public function get_categories() {
        return array('mindful-media');
    }
    
    public function get_keywords() {
        return array('media', 'slider', 'row', 'category', 'netflix', 'carousel');
    }
    
    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __('Content', 'mindful-media'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        // Taxonomy selector
        $this->add_control(
            'taxonomy',
            array(
                'label' => __('Filter by Taxonomy', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    '' => __('All Media (No Filter)', 'mindful-media'),
                    'media_teacher' => __('Teachers', 'mindful-media'),
                    'media_topic' => __('Topics', 'mindful-media'),
                    'media_category' => __('Categories', 'mindful-media'),
                    'media_series' => __('Playlists', 'mindful-media'),
                    'media_type' => __('Media Types', 'mindful-media'),
                ),
                'default' => '',
            )
        );
        
        // Teacher selector
        $teacher_options = array('' => __('-- All Teachers --', 'mindful-media'));
        $teachers = get_terms(array('taxonomy' => 'media_teacher', 'hide_empty' => false, 'orderby' => 'name'));
        if (!empty($teachers) && !is_wp_error($teachers)) {
            foreach ($teachers as $teacher) {
                $teacher_options[$teacher->slug] = $teacher->name;
            }
        }
        $this->add_control(
            'term_teacher',
            array(
                'label' => __('Select Teacher', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => $teacher_options,
                'default' => '',
                'condition' => array('taxonomy' => 'media_teacher'),
            )
        );
        
        // Topic selector
        $topic_options = array('' => __('-- All Topics --', 'mindful-media'));
        $topics = get_terms(array('taxonomy' => 'media_topic', 'hide_empty' => false, 'orderby' => 'name'));
        if (!empty($topics) && !is_wp_error($topics)) {
            foreach ($topics as $topic) {
                $topic_options[$topic->slug] = $topic->name;
            }
        }
        $this->add_control(
            'term_topic',
            array(
                'label' => __('Select Topic', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => $topic_options,
                'default' => '',
                'condition' => array('taxonomy' => 'media_topic'),
            )
        );
        
        // Category selector
        $category_options = array('' => __('-- All Categories --', 'mindful-media'));
        $categories = get_terms(array('taxonomy' => 'media_category', 'hide_empty' => false, 'orderby' => 'name'));
        if (!empty($categories) && !is_wp_error($categories)) {
            foreach ($categories as $category) {
                $category_options[$category->slug] = $category->name;
            }
        }
        $this->add_control(
            'term_category',
            array(
                'label' => __('Select Category', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => $category_options,
                'default' => '',
                'condition' => array('taxonomy' => 'media_category'),
            )
        );
        
        // Playlist selector
        $playlist_options = array('' => __('-- All Playlists --', 'mindful-media'));
        $playlists = get_terms(array('taxonomy' => 'media_series', 'hide_empty' => false, 'orderby' => 'name'));
        if (!empty($playlists) && !is_wp_error($playlists)) {
            foreach ($playlists as $playlist) {
                $playlist_options[$playlist->slug] = $playlist->name;
            }
        }
        $this->add_control(
            'term_series',
            array(
                'label' => __('Select Playlist', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => $playlist_options,
                'default' => '',
                'condition' => array('taxonomy' => 'media_series'),
            )
        );
        
        // Custom title
        $this->add_control(
            'title',
            array(
                'label' => __('Custom Title', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
                'placeholder' => __('Leave empty to auto-generate', 'mindful-media'),
                'description' => __('Override the section title', 'mindful-media'),
            )
        );
        
        $this->end_controls_section();
        
        // Query Section
        $this->start_controls_section(
            'query_section',
            array(
                'label' => __('Query', 'mindful-media'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'limit',
            array(
                'label' => __('Number of Items', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 10,
                'min' => 1,
                'max' => 50,
            )
        );
        
        $this->add_control(
            'orderby',
            array(
                'label' => __('Order By', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'date' => __('Date', 'mindful-media'),
                    'title' => __('Title', 'mindful-media'),
                    'menu_order' => __('Menu Order', 'mindful-media'),
                    'rand' => __('Random', 'mindful-media'),
                ),
                'default' => 'date',
            )
        );
        
        $this->add_control(
            'order',
            array(
                'label' => __('Order', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'DESC' => __('Descending', 'mindful-media'),
                    'ASC' => __('Ascending', 'mindful-media'),
                ),
                'default' => 'DESC',
            )
        );
        
        $this->add_control(
            'featured',
            array(
                'label' => __('Featured Only', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'mindful-media'),
                'label_off' => __('No', 'mindful-media'),
                'return_value' => 'yes',
                'default' => '',
            )
        );
        
        $this->end_controls_section();
        
        // Display Section
        $this->start_controls_section(
            'display_section',
            array(
                'label' => __('Display', 'mindful-media'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'show_link',
            array(
                'label' => __('Show Title Link', 'mindful-media'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'mindful-media'),
                'label_off' => __('No', 'mindful-media'),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => __('Make the section title a link to the archive', 'mindful-media'),
            )
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Determine term based on taxonomy selection
        $term = '';
        switch ($settings['taxonomy']) {
            case 'media_teacher':
                $term = $settings['term_teacher'];
                break;
            case 'media_topic':
                $term = $settings['term_topic'];
                break;
            case 'media_category':
                $term = $settings['term_category'];
                break;
            case 'media_series':
                $term = $settings['term_series'];
                break;
        }
        
        // Build shortcode
        $shortcode_atts = array(
            'taxonomy' => $settings['taxonomy'],
            'term' => $term,
            'title' => $settings['title'],
            'limit' => $settings['limit'],
            'orderby' => $settings['orderby'],
            'order' => $settings['order'],
            'featured' => $settings['featured'] === 'yes' ? 'true' : 'false',
            'show_link' => $settings['show_link'] === 'yes' ? 'true' : 'false',
        );
        
        $shortcode_str = '[mindful_media_row';
        foreach ($shortcode_atts as $key => $value) {
            if (!empty($value) || $value === '0' || $value === 'false') {
                $shortcode_str .= ' ' . $key . '="' . esc_attr($value) . '"';
            }
        }
        $shortcode_str .= ']';
        
        echo do_shortcode($shortcode_str);
    }
}
