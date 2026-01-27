<?php
/**
 * Engagement Class
 * 
 * Handles likes, comments, subscriptions, watch history, and playback progress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MindfulMedia_Engagement {
    
    /**
     * Database version for schema updates
     */
    const DB_VERSION = '1.0.0';
    
    /**
     * Table names (without prefix)
     */
    const TABLE_LIKES = 'mindful_media_likes';
    const TABLE_COMMENTS = 'mindful_media_comments';
    const TABLE_SUBSCRIPTIONS = 'mindful_media_subscriptions';
    const TABLE_WATCH_HISTORY = 'mindful_media_watch_history';
    const TABLE_PLAYBACK_PROGRESS = 'mindful_media_playback_progress';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register AJAX endpoints
        $this->register_ajax_handlers();
        
        // Check for database upgrades
        add_action('admin_init', array($this, 'check_db_upgrade'));
    }
    
    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        // Like/Unlike
        add_action('wp_ajax_mindful_media_like', array($this, 'ajax_toggle_like'));
        add_action('wp_ajax_mindful_media_get_like_count', array($this, 'ajax_get_like_count'));
        add_action('wp_ajax_nopriv_mindful_media_get_like_count', array($this, 'ajax_get_like_count'));
        
        // Comments
        add_action('wp_ajax_mindful_media_post_comment', array($this, 'ajax_post_comment'));
        add_action('wp_ajax_mindful_media_get_comments', array($this, 'ajax_get_comments'));
        add_action('wp_ajax_nopriv_mindful_media_get_comments', array($this, 'ajax_get_comments'));
        add_action('wp_ajax_mindful_media_delete_comment', array($this, 'ajax_delete_comment'));
        
        // Subscriptions
        add_action('wp_ajax_mindful_media_subscribe', array($this, 'ajax_toggle_subscription'));
        add_action('wp_ajax_mindful_media_get_subscriptions', array($this, 'ajax_get_subscriptions'));
        
        // Watch History
        add_action('wp_ajax_mindful_media_record_watch', array($this, 'ajax_record_watch'));
        add_action('wp_ajax_mindful_media_get_watch_history', array($this, 'ajax_get_watch_history'));
        
        // Playback Progress
        add_action('wp_ajax_mindful_media_save_progress', array($this, 'ajax_save_progress'));
        add_action('wp_ajax_mindful_media_get_progress', array($this, 'ajax_get_progress'));
        
        // Library data
        add_action('wp_ajax_mindful_media_get_library', array($this, 'ajax_get_library'));
        
        // Admin actions
        add_action('wp_ajax_mindful_media_clear_engagement_cache', array($this, 'ajax_clear_cache'));
    }
    
    /**
     * Clear engagement cache (admin only)
     */
    public function ajax_clear_cache() {
        check_ajax_referer('mindful_media_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'mindful-media')));
        }
        
        global $wpdb;
        
        // Clear like count transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mm_like_count_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_mm_like_count_%'");
        
        // Clear comment count transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mm_comment_count_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_mm_comment_count_%'");
        
        wp_send_json_success(array('message' => __('Cache cleared successfully.', 'mindful-media')));
    }
    
    /**
     * Get table name with prefix
     */
    public static function get_table_name($table) {
        global $wpdb;
        return $wpdb->prefix . $table;
    }
    
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Likes table
        $table_likes = self::get_table_name(self::TABLE_LIKES);
        $sql_likes = "CREATE TABLE $table_likes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            post_id bigint(20) unsigned NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_post (user_id, post_id),
            KEY post_id (post_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql_likes);
        
        // Comments table
        $table_comments = self::get_table_name(self::TABLE_COMMENTS);
        $sql_comments = "CREATE TABLE $table_comments (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            post_id bigint(20) unsigned NOT NULL,
            content text NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            parent_id bigint(20) unsigned DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY user_id (user_id),
            KEY parent_id (parent_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_comments);
        
        // Subscriptions table
        $table_subscriptions = self::get_table_name(self::TABLE_SUBSCRIPTIONS);
        $sql_subscriptions = "CREATE TABLE $table_subscriptions (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            object_id bigint(20) unsigned NOT NULL,
            object_type varchar(50) NOT NULL,
            notify_email tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_object (user_id, object_id, object_type),
            KEY object_id (object_id),
            KEY object_type (object_type),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql_subscriptions);
        
        // Watch History table
        $table_watch_history = self::get_table_name(self::TABLE_WATCH_HISTORY);
        $sql_watch_history = "CREATE TABLE $table_watch_history (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            post_id bigint(20) unsigned NOT NULL,
            last_watched_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            watch_count int(11) unsigned NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY user_post (user_id, post_id),
            KEY post_id (post_id),
            KEY user_id (user_id),
            KEY last_watched_at (last_watched_at)
        ) $charset_collate;";
        dbDelta($sql_watch_history);
        
        // Playback Progress table
        $table_playback_progress = self::get_table_name(self::TABLE_PLAYBACK_PROGRESS);
        $sql_playback_progress = "CREATE TABLE $table_playback_progress (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            post_id bigint(20) unsigned NOT NULL,
            progress_seconds int(11) unsigned NOT NULL DEFAULT 0,
            duration_seconds int(11) unsigned NOT NULL DEFAULT 0,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_post (user_id, post_id),
            KEY post_id (post_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql_playback_progress);
        
        // Save the database version
        update_option('mindful_media_engagement_db_version', self::DB_VERSION);
    }
    
    /**
     * Drop all engagement tables
     */
    public static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            self::TABLE_LIKES,
            self::TABLE_COMMENTS,
            self::TABLE_SUBSCRIPTIONS,
            self::TABLE_WATCH_HISTORY,
            self::TABLE_PLAYBACK_PROGRESS
        );
        
        foreach ($tables as $table) {
            $table_name = self::get_table_name($table);
            $wpdb->query("DROP TABLE IF EXISTS $table_name");
        }
        
        delete_option('mindful_media_engagement_db_version');
    }
    
    /**
     * Check if database needs upgrade
     */
    public function check_db_upgrade() {
        $current_version = get_option('mindful_media_engagement_db_version', '0');
        
        if (version_compare($current_version, self::DB_VERSION, '<')) {
            self::create_tables();
        }
    }
    
    // =========================================================================
    // LIKES
    // =========================================================================
    
    /**
     * Toggle like for a post
     */
    public function ajax_toggle_like() {
        check_ajax_referer('mindful_media_ajax_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to like content.', 'mindful-media')));
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error(array('message' => __('Invalid post ID.', 'mindful-media')));
        }
        
        // Check if engagement is enabled
        $settings = MindfulMedia_Settings::get_settings();
        if (empty($settings['enable_likes'])) {
            wp_send_json_error(array('message' => __('Likes are disabled.', 'mindful-media')));
        }
        
        $user_id = get_current_user_id();
        $result = $this->toggle_like($user_id, $post_id);
        
        wp_send_json_success(array(
            'liked' => $result['liked'],
            'count' => $result['count'],
            'message' => $result['liked'] ? __('Added to your liked videos.', 'mindful-media') : __('Removed from your liked videos.', 'mindful-media')
        ));
    }
    
    /**
     * Toggle like in database
     */
    public function toggle_like($user_id, $post_id) {
        global $wpdb;
        $table = self::get_table_name(self::TABLE_LIKES);
        
        // Check if already liked
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE user_id = %d AND post_id = %d",
            $user_id, $post_id
        ));
        
        if ($existing) {
            // Unlike
            $wpdb->delete($table, array('user_id' => $user_id, 'post_id' => $post_id), array('%d', '%d'));
            $liked = false;
        } else {
            // Like
            $wpdb->insert($table, array(
                'user_id' => $user_id,
                'post_id' => $post_id,
                'created_at' => current_time('mysql')
            ), array('%d', '%d', '%s'));
            $liked = true;
        }
        
        // Clear cache
        delete_transient('mm_like_count_' . $post_id);
        
        return array(
            'liked' => $liked,
            'count' => $this->get_like_count($post_id)
        );
    }
    
    /**
     * Get like count for a post
     */
    public function get_like_count($post_id) {
        $cached = get_transient('mm_like_count_' . $post_id);
        if ($cached !== false) {
            return (int) $cached;
        }
        
        global $wpdb;
        $table = self::get_table_name(self::TABLE_LIKES);
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE post_id = %d",
            $post_id
        ));
        
        set_transient('mm_like_count_' . $post_id, $count, HOUR_IN_SECONDS);
        
        return (int) $count;
    }
    
    /**
     * Check if user has liked a post
     */
    public function user_has_liked($user_id, $post_id) {
        if (!$user_id) return false;
        
        global $wpdb;
        $table = self::get_table_name(self::TABLE_LIKES);
        
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE user_id = %d AND post_id = %d",
            $user_id, $post_id
        ));
    }
    
    /**
     * AJAX: Get like count
     */
    public function ajax_get_like_count() {
        $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error(array('message' => __('Invalid post ID.', 'mindful-media')));
        }
        
        $user_id = get_current_user_id();
        
        wp_send_json_success(array(
            'count' => $this->get_like_count($post_id),
            'liked' => $this->user_has_liked($user_id, $post_id)
        ));
    }
    
    /**
     * Get user's liked posts
     */
    public function get_user_likes($user_id, $limit = 20, $offset = 0) {
        global $wpdb;
        $table = self::get_table_name(self::TABLE_LIKES);
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT post_id, created_at FROM $table 
             WHERE user_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d OFFSET %d",
            $user_id, $limit, $offset
        ));
    }
    
    // =========================================================================
    // COMMENTS
    // =========================================================================
    
    /**
     * Post a comment
     */
    public function ajax_post_comment() {
        check_ajax_referer('mindful_media_ajax_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to comment.', 'mindful-media')));
        }
        
        $settings = MindfulMedia_Settings::get_settings();
        if (empty($settings['enable_comments'])) {
            wp_send_json_error(array('message' => __('Comments are disabled.', 'mindful-media')));
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $content = isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : '';
        $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
        
        if (!$post_id || empty($content)) {
            wp_send_json_error(array('message' => __('Invalid comment data.', 'mindful-media')));
        }
        
        $user_id = get_current_user_id();
        $status = !empty($settings['auto_approve_comments']) ? 'approved' : 'pending';
        
        $result = $this->add_comment($user_id, $post_id, $content, $parent_id, $status);
        
        if ($result) {
            wp_send_json_success(array(
                'comment_id' => $result,
                'status' => $status,
                'message' => $status === 'approved' 
                    ? __('Comment posted successfully.', 'mindful-media')
                    : __('Comment submitted for review.', 'mindful-media')
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to post comment.', 'mindful-media')));
        }
    }
    
    /**
     * Add comment to database
     */
    public function add_comment($user_id, $post_id, $content, $parent_id = 0, $status = 'pending') {
        global $wpdb;
        $table = self::get_table_name(self::TABLE_COMMENTS);
        
        $inserted = $wpdb->insert($table, array(
            'user_id' => $user_id,
            'post_id' => $post_id,
            'content' => $content,
            'parent_id' => $parent_id,
            'status' => $status,
            'created_at' => current_time('mysql')
        ), array('%d', '%d', '%s', '%d', '%s', '%s'));
        
        if ($inserted) {
            delete_transient('mm_comment_count_' . $post_id);
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Get comments for a post
     */
    public function ajax_get_comments() {
        $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $per_page = 20;
        
        if (!$post_id) {
            wp_send_json_error(array('message' => __('Invalid post ID.', 'mindful-media')));
        }
        
        $comments = $this->get_comments($post_id, $per_page, ($page - 1) * $per_page);
        $total = $this->get_comment_count($post_id);
        
        wp_send_json_success(array(
            'comments' => $comments,
            'total' => $total,
            'pages' => ceil($total / $per_page)
        ));
    }
    
    /**
     * Get comments from database
     */
    public function get_comments($post_id, $limit = 20, $offset = 0, $status = 'approved') {
        global $wpdb;
        $table = self::get_table_name(self::TABLE_COMMENTS);
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, u.display_name, u.user_email 
             FROM $table c
             LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
             WHERE c.post_id = %d AND c.status = %s
             ORDER BY c.created_at DESC
             LIMIT %d OFFSET %d",
            $post_id, $status, $limit, $offset
        ));
        
        // Add avatar URLs
        foreach ($results as &$comment) {
            $comment->avatar_url = get_avatar_url($comment->user_email, array('size' => 48));
            $comment->time_ago = human_time_diff(strtotime($comment->created_at), current_time('timestamp')) . ' ' . __('ago', 'mindful-media');
        }
        
        return $results;
    }
    
    /**
     * Get comment count for a post
     */
    public function get_comment_count($post_id, $status = 'approved') {
        $cached = get_transient('mm_comment_count_' . $post_id);
        if ($cached !== false) {
            return (int) $cached;
        }
        
        global $wpdb;
        $table = self::get_table_name(self::TABLE_COMMENTS);
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE post_id = %d AND status = %s",
            $post_id, $status
        ));
        
        set_transient('mm_comment_count_' . $post_id, $count, HOUR_IN_SECONDS);
        
        return (int) $count;
    }
    
    /**
     * Delete a comment
     */
    public function ajax_delete_comment() {
        check_ajax_referer('mindful_media_ajax_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'mindful-media')));
        }
        
        $comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
        
        if (!$comment_id) {
            wp_send_json_error(array('message' => __('Invalid comment ID.', 'mindful-media')));
        }
        
        global $wpdb;
        $table = self::get_table_name(self::TABLE_COMMENTS);
        
        // Get comment to check ownership
        $comment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $comment_id
        ));
        
        if (!$comment) {
            wp_send_json_error(array('message' => __('Comment not found.', 'mindful-media')));
        }
        
        $user_id = get_current_user_id();
        
        // Only allow deletion by owner or admin
        if ($comment->user_id != $user_id && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to delete this comment.', 'mindful-media')));
        }
        
        $wpdb->delete($table, array('id' => $comment_id), array('%d'));
        delete_transient('mm_comment_count_' . $comment->post_id);
        
        wp_send_json_success(array('message' => __('Comment deleted.', 'mindful-media')));
    }
    
    // =========================================================================
    // SUBSCRIPTIONS
    // =========================================================================
    
    /**
     * Toggle subscription
     */
    public function ajax_toggle_subscription() {
        check_ajax_referer('mindful_media_ajax_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to subscribe.', 'mindful-media')));
        }
        
        $settings = MindfulMedia_Settings::get_settings();
        if (empty($settings['enable_subscriptions'])) {
            wp_send_json_error(array('message' => __('Subscriptions are disabled.', 'mindful-media')));
        }
        
        $object_id = isset($_POST['object_id']) ? intval($_POST['object_id']) : 0;
        $object_type = isset($_POST['object_type']) ? sanitize_key($_POST['object_type']) : '';
        $notify_email = isset($_POST['notify_email']) ? (bool) $_POST['notify_email'] : true;
        
        if (!$object_id || !$object_type) {
            wp_send_json_error(array('message' => __('Invalid subscription data.', 'mindful-media')));
        }
        
        // Validate object type
        $valid_types = array('playlist', 'media_series', 'media_teacher', 'media_topic', 'media_category');
        if (!in_array($object_type, $valid_types)) {
            wp_send_json_error(array('message' => __('Invalid subscription type.', 'mindful-media')));
        }
        
        $user_id = get_current_user_id();
        $result = $this->toggle_subscription($user_id, $object_id, $object_type, $notify_email);
        
        wp_send_json_success(array(
            'subscribed' => $result['subscribed'],
            'message' => $result['subscribed'] 
                ? __('Subscribed successfully.', 'mindful-media')
                : __('Unsubscribed successfully.', 'mindful-media')
        ));
    }
    
    /**
     * Toggle subscription in database
     */
    public function toggle_subscription($user_id, $object_id, $object_type, $notify_email = true) {
        global $wpdb;
        $table = self::get_table_name(self::TABLE_SUBSCRIPTIONS);
        
        // Check if already subscribed
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE user_id = %d AND object_id = %d AND object_type = %s",
            $user_id, $object_id, $object_type
        ));
        
        if ($existing) {
            // Unsubscribe
            $wpdb->delete($table, array(
                'user_id' => $user_id,
                'object_id' => $object_id,
                'object_type' => $object_type
            ), array('%d', '%d', '%s'));
            return array('subscribed' => false);
        } else {
            // Subscribe
            $wpdb->insert($table, array(
                'user_id' => $user_id,
                'object_id' => $object_id,
                'object_type' => $object_type,
                'notify_email' => $notify_email ? 1 : 0,
                'created_at' => current_time('mysql')
            ), array('%d', '%d', '%s', '%d', '%s'));
            return array('subscribed' => true);
        }
    }
    
    /**
     * Check if user is subscribed
     */
    public function user_is_subscribed($user_id, $object_id, $object_type) {
        if (!$user_id) return false;
        
        global $wpdb;
        $table = self::get_table_name(self::TABLE_SUBSCRIPTIONS);
        
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE user_id = %d AND object_id = %d AND object_type = %s",
            $user_id, $object_id, $object_type
        ));
    }
    
    /**
     * Get user's subscriptions
     */
    public function ajax_get_subscriptions() {
        check_ajax_referer('mindful_media_ajax_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'mindful-media')));
        }
        
        $user_id = get_current_user_id();
        $subscriptions = $this->get_user_subscriptions($user_id);
        
        wp_send_json_success(array('subscriptions' => $subscriptions));
    }
    
    /**
     * Get all subscriptions for a user
     */
    public function get_user_subscriptions($user_id, $object_type = null) {
        global $wpdb;
        $table = self::get_table_name(self::TABLE_SUBSCRIPTIONS);
        
        $query = "SELECT * FROM $table WHERE user_id = %d";
        $params = array($user_id);
        
        if ($object_type) {
            $query .= " AND object_type = %s";
            $params[] = $object_type;
        }
        
        $query .= " ORDER BY created_at DESC";
        
        return $wpdb->get_results($wpdb->prepare($query, $params));
    }
    
    /**
     * Get subscribers for an object
     */
    public function get_subscribers($object_id, $object_type, $notify_email_only = false) {
        global $wpdb;
        $table = self::get_table_name(self::TABLE_SUBSCRIPTIONS);
        
        $query = "SELECT user_id FROM $table WHERE object_id = %d AND object_type = %s";
        $params = array($object_id, $object_type);
        
        if ($notify_email_only) {
            $query .= " AND notify_email = 1";
        }
        
        return $wpdb->get_col($wpdb->prepare($query, $params));
    }
    
    // =========================================================================
    // WATCH HISTORY
    // =========================================================================
    
    /**
     * Record a watch event
     */
    public function ajax_record_watch() {
        check_ajax_referer('mindful_media_ajax_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Not logged in.', 'mindful-media')));
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error(array('message' => __('Invalid post ID.', 'mindful-media')));
        }
        
        $user_id = get_current_user_id();
        $this->record_watch($user_id, $post_id);
        
        wp_send_json_success();
    }
    
    /**
     * Record watch in database
     */
    public function record_watch($user_id, $post_id) {
        global $wpdb;
        $table = self::get_table_name(self::TABLE_WATCH_HISTORY);
        
        // Check if exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE user_id = %d AND post_id = %d",
            $user_id, $post_id
        ));
        
        if ($existing) {
            // Update
            $wpdb->update($table, array(
                'last_watched_at' => current_time('mysql'),
                'watch_count' => $wpdb->get_var($wpdb->prepare(
                    "SELECT watch_count FROM $table WHERE id = %d",
                    $existing
                )) + 1
            ), array('id' => $existing), array('%s', '%d'), array('%d'));
        } else {
            // Insert
            $wpdb->insert($table, array(
                'user_id' => $user_id,
                'post_id' => $post_id,
                'last_watched_at' => current_time('mysql'),
                'watch_count' => 1
            ), array('%d', '%d', '%s', '%d'));
        }
    }
    
    /**
     * Get user's watch history
     */
    public function ajax_get_watch_history() {
        check_ajax_referer('mindful_media_ajax_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Not logged in.', 'mindful-media')));
        }
        
        $user_id = get_current_user_id();
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        
        $history = $this->get_watch_history($user_id, $limit, $offset);
        
        wp_send_json_success(array('history' => $history));
    }
    
    /**
     * Get watch history from database
     */
    public function get_watch_history($user_id, $limit = 20, $offset = 0) {
        global $wpdb;
        $table = self::get_table_name(self::TABLE_WATCH_HISTORY);
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT post_id, last_watched_at, watch_count FROM $table 
             WHERE user_id = %d 
             ORDER BY last_watched_at DESC 
             LIMIT %d OFFSET %d",
            $user_id, $limit, $offset
        ));
    }
    
    // =========================================================================
    // PLAYBACK PROGRESS
    // =========================================================================
    
    /**
     * Save playback progress
     */
    public function ajax_save_progress() {
        check_ajax_referer('mindful_media_ajax_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Not logged in.', 'mindful-media')));
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $progress = isset($_POST['progress_seconds']) ? intval($_POST['progress_seconds']) : 0;
        $duration = isset($_POST['duration_seconds']) ? intval($_POST['duration_seconds']) : 0;
        
        if (!$post_id) {
            wp_send_json_error(array('message' => __('Invalid post ID.', 'mindful-media')));
        }
        
        $user_id = get_current_user_id();
        $this->save_progress($user_id, $post_id, $progress, $duration);
        
        wp_send_json_success();
    }
    
    /**
     * Save progress to database
     */
    public function save_progress($user_id, $post_id, $progress_seconds, $duration_seconds) {
        global $wpdb;
        $table = self::get_table_name(self::TABLE_PLAYBACK_PROGRESS);
        
        // Check if exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE user_id = %d AND post_id = %d",
            $user_id, $post_id
        ));
        
        if ($existing) {
            // Update
            $wpdb->update($table, array(
                'progress_seconds' => $progress_seconds,
                'duration_seconds' => $duration_seconds,
                'updated_at' => current_time('mysql')
            ), array('id' => $existing), array('%d', '%d', '%s'), array('%d'));
        } else {
            // Insert
            $wpdb->insert($table, array(
                'user_id' => $user_id,
                'post_id' => $post_id,
                'progress_seconds' => $progress_seconds,
                'duration_seconds' => $duration_seconds,
                'updated_at' => current_time('mysql')
            ), array('%d', '%d', '%d', '%d', '%s'));
        }
    }
    
    /**
     * Get playback progress
     */
    public function ajax_get_progress() {
        if (!is_user_logged_in()) {
            wp_send_json_success(array('progress' => null));
        }
        
        $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error(array('message' => __('Invalid post ID.', 'mindful-media')));
        }
        
        $user_id = get_current_user_id();
        $progress = $this->get_progress($user_id, $post_id);
        
        wp_send_json_success(array('progress' => $progress));
    }
    
    /**
     * Get progress from database
     */
    public function get_progress($user_id, $post_id) {
        global $wpdb;
        $table = self::get_table_name(self::TABLE_PLAYBACK_PROGRESS);
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT progress_seconds, duration_seconds, updated_at FROM $table 
             WHERE user_id = %d AND post_id = %d",
            $user_id, $post_id
        ));
    }
    
    /**
     * Get continue watching list (videos < 90% complete)
     */
    public function get_continue_watching($user_id, $limit = 10) {
        global $wpdb;
        $table = self::get_table_name(self::TABLE_PLAYBACK_PROGRESS);
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT post_id, progress_seconds, duration_seconds, updated_at FROM $table 
             WHERE user_id = %d 
             AND duration_seconds > 0 
             AND (progress_seconds / duration_seconds) < 0.9
             AND progress_seconds > 10
             ORDER BY updated_at DESC 
             LIMIT %d",
            $user_id, $limit
        ));
    }
    
    // =========================================================================
    // LIBRARY
    // =========================================================================
    
    /**
     * Get full library data for user
     */
    public function ajax_get_library() {
        check_ajax_referer('mindful_media_ajax_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'mindful-media')));
        }
        
        $user_id = get_current_user_id();
        $section = isset($_GET['section']) ? sanitize_key($_GET['section']) : 'all';
        
        $data = array();
        
        if ($section === 'all' || $section === 'continue_watching') {
            $data['continue_watching'] = $this->get_continue_watching($user_id, 10);
        }
        
        if ($section === 'all' || $section === 'liked') {
            $data['liked'] = $this->get_user_likes($user_id, 20, 0);
        }
        
        if ($section === 'all' || $section === 'watch_history') {
            $data['watch_history'] = $this->get_watch_history($user_id, 20, 0);
        }
        
        if ($section === 'all' || $section === 'subscriptions') {
            $data['subscriptions'] = $this->get_user_subscriptions($user_id);
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * Get engagement data for a single post (for display)
     */
    public function get_post_engagement($post_id, $user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        $settings = MindfulMedia_Settings::get_settings();
        
        return array(
            'likes_enabled' => !empty($settings['enable_likes']),
            'comments_enabled' => !empty($settings['enable_comments']),
            'subscriptions_enabled' => !empty($settings['enable_subscriptions']),
            'like_count' => !empty($settings['enable_likes']) ? $this->get_like_count($post_id) : 0,
            'comment_count' => !empty($settings['enable_comments']) ? $this->get_comment_count($post_id) : 0,
            'user_liked' => $user_id ? $this->user_has_liked($user_id, $post_id) : false,
            'progress' => $user_id ? $this->get_progress($user_id, $post_id) : null
        );
    }
}
