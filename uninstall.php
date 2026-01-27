<?php
/**
 * MindfulMedia Uninstall
 *
 * Fired when the plugin is uninstalled.
 *
 * @package MindfulMedia
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Clean up plugin data on uninstall
 * 
 * Note: We only remove plugin settings by default.
 * Media items and taxonomies are preserved to prevent accidental data loss.
 * Users can manually delete these via WordPress admin if needed.
 */

global $wpdb;

// Get settings to check data retention preferences
$settings = get_option('mindful_media_settings', array());
$keep_engagement_data = !empty($settings['keep_engagement_data_on_uninstall']);

// Delete plugin settings
delete_option('mindful_media_settings');
delete_option('mindful_media_version');
delete_option('mindful_media_engagement_db_version');
delete_option('mindful_media_force_rewrite_flush');
delete_option('mindful_media_rewrite_rules_flushed');

// Delete any transients the plugin may have created
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mindful_media_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_mindful_media_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mm_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_mm_%'");

// Clear any scheduled events
wp_clear_scheduled_hook('mindful_media_cleanup');
wp_clear_scheduled_hook('mindful_media_send_notifications');

// Drop engagement tables if data retention is not enabled
// Filter allows developers to override: add_filter('mindful_media_keep_engagement_tables', '__return_true');
$keep_tables = apply_filters('mindful_media_keep_engagement_tables', $keep_engagement_data);

if (!$keep_tables) {
    $tables = array(
        $wpdb->prefix . 'mindful_media_likes',
        $wpdb->prefix . 'mindful_media_comments',
        $wpdb->prefix . 'mindful_media_subscriptions',
        $wpdb->prefix . 'mindful_media_watch_history',
        $wpdb->prefix . 'mindful_media_playback_progress'
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
}

// Delete the library page if it was auto-created
if (!empty($settings['library_page_id'])) {
    wp_delete_post($settings['library_page_id'], true);
}

// Note: Custom post type data (mindful_media posts) and taxonomy terms are NOT deleted
// to prevent accidental data loss. Users can delete these manually if needed.
