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

// Delete plugin settings
delete_option('mindful_media_settings');

// Delete any transients the plugin may have created
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mindful_media_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_mindful_media_%'");

// Clear any scheduled events
wp_clear_scheduled_hook('mindful_media_cleanup');

// Note: Custom post type data (mindful_media posts) and taxonomy terms are NOT deleted
// to prevent accidental data loss. Users can delete these manually if needed.
