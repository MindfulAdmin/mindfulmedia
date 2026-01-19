<?php
/**
 * Elementor Widgets Class
 * Registers custom Elementor widgets for MindfulMedia
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if Elementor is active and register widgets
 */
class MindfulMedia_Elementor {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        // Check if Elementor is active - use after_setup_theme for early check
        add_action('init', array($this, 'init'), 0);
    }
    
    /**
     * Initialize Elementor integration
     */
    public function init() {
        // Check if Elementor is installed and activated
        if (!did_action('elementor/loaded')) {
            return;
        }
        
        // Register category (must happen before widgets)
        add_action('elementor/elements/categories_registered', array($this, 'register_category'));
        
        // Register widgets - use correct hook for Elementor 3.5+
        add_action('elementor/widgets/register', array($this, 'register_widgets'));
    }
    
    /**
     * Register widget category
     */
    public function register_category($elements_manager) {
        $elements_manager->add_category(
            'mindful-media',
            array(
                'title' => __('MindfulMedia', 'mindful-media'),
                'icon' => 'eicon-play',
            )
        );
    }
    
    /**
     * Register widgets
     */
    public function register_widgets($widgets_manager) {
        // Include widget files
        require_once(MINDFUL_MEDIA_PLUGIN_DIR . 'includes/elementor/widget-browse.php');
        require_once(MINDFUL_MEDIA_PLUGIN_DIR . 'includes/elementor/widget-embed.php');
        require_once(MINDFUL_MEDIA_PLUGIN_DIR . 'includes/elementor/widget-archive.php');
        require_once(MINDFUL_MEDIA_PLUGIN_DIR . 'includes/elementor/widget-row.php');
        
        // Register widgets using the new method (Elementor 3.5+)
        $widgets_manager->register(new MindfulMedia_Elementor_Browse_Widget());
        $widgets_manager->register(new MindfulMedia_Elementor_Embed_Widget());
        $widgets_manager->register(new MindfulMedia_Elementor_Archive_Widget());
        $widgets_manager->register(new MindfulMedia_Elementor_Row_Widget());
    }
}
