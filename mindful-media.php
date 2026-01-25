<?php
/**
 * Plugin Name: MindfulMedia
 * Plugin URI: https://mindfuldesign.me/plugins/mindful-media
 * Description: A comprehensive media management system for organizing and displaying audio, video, and multimedia content with advanced filtering, playlists, password protection, and customizable archives.
 * Version: 2.8.4
 * Author: Mindful Design
 * Author URI: https://mindfuldesign.me
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mindful-media
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin class
 */
class MindfulMedia {
    
    /**
     * Plugin version
     */
    public $version = '2.8.4';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Define constants
        $this->define_constants();
        
        // Register activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Initialize on plugins_loaded
        add_action('plugins_loaded', array($this, 'init'));
        
        // Check version and flush rewrites if needed
        add_action('admin_init', array($this, 'check_version'));
    }
    
    /**
     * Define plugin constants
     */
    private function define_constants() {
        define('MINDFUL_MEDIA_VERSION', $this->version);
        define('MINDFUL_MEDIA_PLUGIN_FILE', __FILE__);
        define('MINDFUL_MEDIA_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('MINDFUL_MEDIA_PLUGIN_URL', plugin_dir_url(__FILE__));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('mindful-media', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Load includes
        $this->load_includes();
        
        // Initialize classes
        $this->init_classes();
    }
    
    /**
     * Load required files
     */
    private function load_includes() {
        $includes = array(
            'includes/class-post-types.php',
            'includes/class-taxonomies.php',
            'includes/class-meta-fields.php',
            'includes/class-media-players.php',
            'includes/class-admin.php',
            'includes/class-shortcodes.php',
            'includes/class-settings.php',
            'includes/class-blocks.php',
            'includes/class-elementor.php'
        );
        
        foreach ($includes as $file) {
            $file_path = MINDFUL_MEDIA_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    /**
     * Initialize plugin classes
     */
    private function init_classes() {
        if (class_exists('MindfulMedia_Post_Types')) {
            new MindfulMedia_Post_Types();
        }
        
        if (class_exists('MindfulMedia_Taxonomies')) {
            new MindfulMedia_Taxonomies();
        }
        
        if (class_exists('MindfulMedia_Meta_Fields')) {
            new MindfulMedia_Meta_Fields();
        }
        
        if (class_exists('MindfulMedia_Players')) {
            new MindfulMedia_Players();
        }
        
        if (class_exists('MindfulMedia_Admin')) {
            new MindfulMedia_Admin();
        }
        
        if (class_exists('MindfulMedia_Shortcodes')) {
            new MindfulMedia_Shortcodes();
        }
        
        if (class_exists('MindfulMedia_Settings')) {
            new MindfulMedia_Settings();
        }
        
        if (class_exists('MindfulMedia_Blocks')) {
            new MindfulMedia_Blocks();
        }
        
        if (class_exists('MindfulMedia_Elementor')) {
            new MindfulMedia_Elementor();
        }
    }
    
    /**
     * Check version and flush rewrites if updated
     */
    public function check_version() {
        $saved_version = get_option('mindful_media_version');
        
        if ($saved_version !== $this->version) {
            // Version changed, flush rewrites
            flush_rewrite_rules();
            update_option('mindful_media_version', $this->version);
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Check PHP version compatibility
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                esc_html__('MindfulMedia requires PHP 7.4 or higher. Your server is running PHP ', 'mindful-media') . PHP_VERSION . '.',
                esc_html__('Plugin Activation Error', 'mindful-media'),
                array('back_link' => true)
            );
        }
        
        // Load the taxonomies class first to register them
        require_once MINDFUL_MEDIA_PLUGIN_DIR . 'includes/class-post-types.php';
        require_once MINDFUL_MEDIA_PLUGIN_DIR . 'includes/class-taxonomies.php';
        
        // Initialize them to register post types and taxonomies
        new MindfulMedia_Post_Types();
        new MindfulMedia_Taxonomies();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Save version
        update_option('mindful_media_version', $this->version);
        
        // Set activation flag for cache clearing
        update_option('mindful_media_force_rewrite_flush', 'yes');
        
        // Clear various caches
        $this->clear_caches();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Clear various WordPress and plugin caches
     */
    private function clear_caches() {
        // Clear WordPress object cache
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Clear W3 Total Cache
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }
        
        // Clear WP Rocket cache
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
        }
        
        // Clear Autoptimize cache
        if (class_exists('autoptimizeCache')) {
            autoptimizeCache::clearall();
        }
        
        // Clear WP Super Cache
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }
        
        // Clear LiteSpeed Cache
        if (class_exists('LiteSpeed_Cache_API')) {
            LiteSpeed_Cache_API::purge_all();
        }
        
        // Clear OPcache if available
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }
}

// Initialize the plugin
new MindfulMedia();
