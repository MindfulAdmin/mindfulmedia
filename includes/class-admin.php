<?php
/**
 * Admin Interface Class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MindfulMedia_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_filter('manage_mindful_media_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_mindful_media_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        add_filter('manage_edit-mindful_media_sortable_columns', array($this, 'set_sortable_columns'));
        
        // Quick Edit
        add_action('quick_edit_custom_box', array($this, 'add_quick_edit_fields'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_quick_edit_js'));
        add_action('all_admin_notices', array($this, 'show_branding_header'));
        
        // Classic Editor - Add Media button
        add_action('media_buttons', array($this, 'add_shortcode_generator_button'), 15);
        add_action('admin_footer', array($this, 'shortcode_generator_modal'));
    }
    
    /**
     * Generate branding header HTML - Mindful Design Style
     */
    private function get_branding_header($title = 'MindfulMedia') {
        $icon_url = plugins_url('../assets/icon-gold.svg', __FILE__) . '?v=' . MINDFUL_MEDIA_VERSION;
        $logo_url = plugins_url('../assets/logo-white.svg', __FILE__) . '?v=' . MINDFUL_MEDIA_VERSION;
        $version = MINDFUL_MEDIA_VERSION;
        
        ob_start();
        ?>
        <div class="mindful-media-branding-header">
            <div class="mindful-media-brand-content">
                <img src="<?php echo esc_url($icon_url); ?>" 
                     alt="Mindful Design Icon" 
                     style="height: 40px; width: auto;"
                     onerror="this.style.display='none';">
                <div class="mindful-media-brand-text">
                    <h1><?php echo esc_html($title); ?></h1>
                    <p class="mindful-media-brand-tagline">
                        v<?php echo esc_html($version); ?> • By <a href="https://mindfuldesign.me" target="_blank">Mindful Design</a>
                    </p>
                </div>
            </div>
            <div class="mindful-media-brand-logo">
                <img src="<?php echo esc_url($logo_url); ?>" 
                     alt="Mindful Design Logo" 
                     style="height: 32px; width: auto;"
                     onerror="this.style.display='none';">
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=mindful_media',
            __('Getting Started', 'mindful-media'),
            __('Getting Started', 'mindful-media'),
            'manage_options',
            'mindful-media-getting-started',
            array($this, 'getting_started_page')
        );
        
        add_submenu_page(
            'edit.php?post_type=mindful_media',
            __('Settings', 'mindful-media'),
            __('Settings', 'mindful-media'),
            'manage_options',
            'mindful-media-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'edit.php?post_type=mindful_media',
            __('Import/Export', 'mindful-media'),
            __('Import/Export', 'mindful-media'),
            'manage_options',
            'mindful-media-import-export',
            array($this, 'import_export_page')
        );
    }
    
    /**
     * Getting Started documentation page - WooCommerce-style design
     */
    public function getting_started_page() {
        // Get counts for status indicators
        $media_count = wp_count_posts('mindful_media');
        $published_count = isset($media_count->publish) ? $media_count->publish : 0;
        $playlist_count = wp_count_terms('media_series', array('hide_empty' => false));
        $teacher_count = wp_count_terms('media_teacher', array('hide_empty' => false));
        $topic_count = wp_count_terms('media_topic', array('hide_empty' => false));
        
        // Handle WP_Error from wp_count_terms
        $playlist_count = is_wp_error($playlist_count) ? 0 : $playlist_count;
        $teacher_count = is_wp_error($teacher_count) ? 0 : $teacher_count;
        $topic_count = is_wp_error($topic_count) ? 0 : $topic_count;
        
        // Check if settings have been configured
        $settings = get_option('mindful_media_settings', array());
        $settings_configured = !empty($settings) && isset($settings['primary_color']);
        
        // Output branding header
        echo $this->get_branding_header('Getting Started');
        ?>
        <style>
        /* Mindful Design Gold Color Scheme - WooCommerce Style */
        :root {
            --mm-gold-primary: #e1ca8e;
            --mm-gold-medium: #b8a064;
            --mm-gold-dark: #93845e;
            --mm-text: #50575e;
            --mm-text-dark: #1d2327;
            --mm-bg-white: #ffffff;
            --mm-bg-light: #f9f9f9;
            --mm-border: #dcdcde;
            --mm-success: #46b450;
            --mm-warning: #dba617;
        }
        .mm-getting-started {
            max-width: 1200px;
            margin: 20px 20px 20px 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        /* Welcome Card - Clean white card like MindfulSEO */
        .mm-welcome-card {
            background: var(--mm-bg-white);
            border: 1px solid var(--mm-border);
            border-radius: 4px;
            padding: 30px;
            margin-bottom: 24px;
        }
        .mm-welcome-card h2 {
            margin: 0 0 12px;
            font-size: 20px;
            font-weight: 400;
            color: var(--mm-text-dark);
        }
        .mm-welcome-card > p {
            font-size: 14px;
            color: #646970;
            margin: 0 0 20px;
        }
        .mm-welcome-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }
        .mm-welcome-grid h3 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
            color: var(--mm-text-dark);
        }
        .mm-welcome-grid ul {
            margin: 0;
            padding: 0;
            list-style: none;
            line-height: 2;
        }
        .mm-welcome-grid a {
            text-decoration: none;
            color: var(--mm-gold-dark);
        }
        .mm-welcome-grid a:hover {
            color: var(--mm-gold-medium);
            text-decoration: underline;
        }
        /* Stats Cards */
        .mm-stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .mm-stat-card {
            background: var(--mm-bg-white);
            border: 1px solid var(--mm-border);
            border-radius: 4px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .mm-stat-icon-wrap {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        .mm-stat-icon-wrap.success {
            background: rgba(70, 180, 80, 0.1);
            color: var(--mm-success);
        }
        .mm-stat-icon-wrap.warning {
            background: rgba(219, 166, 23, 0.1);
            color: var(--mm-warning);
        }
        .mm-stat-icon-wrap.neutral {
            background: var(--mm-bg-light);
            color: #999;
        }
        .mm-stat-content {
            flex: 1;
        }
        .mm-stat-value {
            font-size: 24px;
            font-weight: 600;
            color: var(--mm-text-dark);
            line-height: 1.2;
        }
        .mm-stat-label {
            font-size: 13px;
            color: #646970;
        }
        .mm-stat-action {
            font-size: 12px;
            color: var(--mm-gold-dark);
            text-decoration: none;
        }
        .mm-stat-action:hover {
            text-decoration: underline;
        }
        /* Setup Steps */
        .mm-setup-steps {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }
        .mm-step-card {
            background: var(--mm-bg-white);
            border: 1px solid var(--mm-border);
            border-radius: 4px;
            padding: 24px;
            text-align: center;
            transition: box-shadow 0.2s;
        }
        .mm-step-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .mm-step-number {
            width: 40px;
            height: 40px;
            background: var(--mm-gold-primary);
            color: var(--mm-text-dark);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 600;
            margin: 0 auto 16px;
        }
        .mm-step-card h3 {
            margin: 0 0 8px;
            font-size: 15px;
            color: var(--mm-text-dark);
        }
        .mm-step-card p {
            margin: 0 0 16px;
            color: #646970;
            font-size: 13px;
        }
        .mm-step-card .button {
            display: inline-block;
        }
        /* Section Cards */
        .mm-section {
            background: var(--mm-bg-white);
            border: 1px solid var(--mm-border);
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .mm-section-header {
            padding: 16px 20px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .mm-section-header h2 {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: var(--mm-text-dark);
        }
        .mm-section-icon {
            width: 20px;
            height: 20px;
            color: var(--mm-gold-dark);
        }
        .mm-section-body {
            padding: 20px;
        }
        /* Shortcodes */
        .mm-shortcode-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        .mm-shortcode-item {
            background: var(--mm-bg-light);
            border-radius: 4px;
            padding: 16px;
        }
        .mm-shortcode-item h4 {
            margin: 0 0 8px;
            color: var(--mm-text-dark);
            font-size: 13px;
        }
        .mm-shortcode-code {
            background: #2c3338;
            color: #e1ca8e;
            padding: 10px 14px;
            border-radius: 4px;
            font-family: Monaco, Consolas, monospace;
            font-size: 12px;
            margin: 8px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .mm-shortcode-item p {
            margin: 8px 0 0;
            color: #646970;
            font-size: 12px;
        }
        .mm-copy-btn {
            background: transparent;
            border: 1px solid var(--mm-gold-primary);
            color: var(--mm-gold-primary);
            padding: 4px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 11px;
        }
        .mm-copy-btn:hover {
            background: var(--mm-gold-primary);
            color: #2c3338;
        }
        /* Accordions */
        .mm-accordion {
            border-top: 1px solid #f0f0f0;
        }
        .mm-accordion:first-child {
            border-top: none;
        }
        .mm-accordion-header {
            padding: 14px 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 500;
            font-size: 13px;
            color: var(--mm-text-dark);
        }
        .mm-accordion-header:hover {
            background: var(--mm-bg-light);
        }
        .mm-accordion-content {
            display: none;
            padding: 0 20px 20px;
            font-size: 13px;
            color: #646970;
        }
        .mm-accordion.active .mm-accordion-content {
            display: block;
        }
        .mm-accordion-arrow {
            transition: transform 0.2s;
        }
        .mm-accordion.active .mm-accordion-arrow {
            transform: rotate(180deg);
        }
        /* Setup Checklist */
        .mm-checklist-items {
            padding: 0 20px 20px;
        }
        .mm-checklist-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
        }
        .mm-checklist-item:last-child {
            border-bottom: none;
        }
        .mm-checklist-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            flex-shrink: 0;
        }
        .mm-checklist-icon.complete {
            background: var(--mm-success);
            color: white;
        }
        .mm-checklist-icon.warning {
            background: var(--mm-warning);
            color: white;
        }
        .mm-checklist-icon.pending {
            background: #ddd;
            color: #666;
        }
        .mm-checklist-text {
            flex: 1;
            color: var(--mm-text-dark);
        }
        .mm-checklist-action {
            font-size: 12px;
            color: var(--mm-gold-dark);
            text-decoration: none;
        }
        .mm-checklist-action:hover {
            text-decoration: underline;
        }
        /* Responsive */
        @media (max-width: 782px) {
            .mm-setup-steps { grid-template-columns: 1fr; }
            .mm-shortcode-grid { grid-template-columns: 1fr; }
            .mm-stats-row { grid-template-columns: 1fr; }
            .mm-welcome-grid { grid-template-columns: 1fr; gap: 20px; }
        }
        </style>
        
        <div class="mm-getting-started">
            <!-- Welcome Card - Clean white card like MindfulSEO -->
            <div class="mm-welcome-card">
                <h2><?php _e('🎬 Welcome to MindfulMedia!', 'mindful-media'); ?></h2>
                <p><?php _e('Organize and display your audio, video, and multimedia content beautifully.', 'mindful-media'); ?></p>
                
                <div class="mm-welcome-grid">
                    <div>
                        <h3>⚙️ <?php _e('Quick Setup', 'mindful-media'); ?></h3>
                        <ul>
                            <li><a href="<?php echo admin_url('post-new.php?post_type=mindful_media'); ?>"><?php _e('Add New Media Item', 'mindful-media'); ?></a></li>
                            <li><a href="<?php echo admin_url('edit.php?post_type=mindful_media&page=mindful-media-settings'); ?>"><?php _e('Configure Settings', 'mindful-media'); ?></a></li>
                            <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=media_series&post_type=mindful_media'); ?>"><?php _e('Create Playlists', 'mindful-media'); ?></a></li>
                        </ul>
                    </div>
                    <div>
                        <h3>📚 <?php _e('Taxonomies', 'mindful-media'); ?></h3>
                        <ul>
                            <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=media_teacher&post_type=mindful_media'); ?>"><?php _e('Teachers', 'mindful-media'); ?></a></li>
                            <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=media_topic&post_type=mindful_media'); ?>"><?php _e('Topics', 'mindful-media'); ?></a></li>
                            <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=media_category&post_type=mindful_media'); ?>"><?php _e('Categories', 'mindful-media'); ?></a></li>
                        </ul>
                    </div>
                    <div>
                        <h3>📊 <?php _e('Your Content', 'mindful-media'); ?></h3>
                        <p style="margin: 0 0 8px 0;">
                            <?php if ($published_count > 0): ?>
                                <span style="color: #46b450;">✓</span>
                            <?php else: ?>
                                <span style="color: #dba617;">○</span>
                            <?php endif; ?>
                            <strong><?php echo $published_count; ?></strong> <?php _e('Media Items', 'mindful-media'); ?>
                        </p>
                        <p style="margin: 0 0 8px 0;">
                            <?php if ($playlist_count > 0): ?>
                                <span style="color: #46b450;">✓</span>
                            <?php else: ?>
                                <span style="color: #999;">○</span>
                            <?php endif; ?>
                            <strong><?php echo $playlist_count; ?></strong> <?php _e('Playlists', 'mindful-media'); ?>
                        </p>
                        <p style="margin: 0;">
                            <?php if ($teacher_count > 0): ?>
                                <span style="color: #46b450;">✓</span>
                            <?php else: ?>
                                <span style="color: #999;">○</span>
                            <?php endif; ?>
                            <strong><?php echo $teacher_count; ?></strong> <?php _e('Teachers', 'mindful-media'); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Setup Steps - Clean cards -->
            <div class="mm-setup-steps">
                <div class="mm-step-card">
                    <div class="mm-step-number">1</div>
                    <h3><?php _e('Add Media', 'mindful-media'); ?></h3>
                    <p><?php _e('Create media items with YouTube, Vimeo, SoundCloud, or audio URLs.', 'mindful-media'); ?></p>
                    <a href="<?php echo admin_url('post-new.php?post_type=mindful_media'); ?>" class="button button-primary"><?php _e('Add New', 'mindful-media'); ?></a>
                </div>
                <div class="mm-step-card">
                    <div class="mm-step-number">2</div>
                    <h3><?php _e('Configure Settings', 'mindful-media'); ?></h3>
                    <p><?php _e('Customize colors, enable taxonomies, and configure display options.', 'mindful-media'); ?></p>
                    <a href="<?php echo admin_url('edit.php?post_type=mindful_media&page=mindful-media-settings'); ?>" class="button"><?php _e('Settings', 'mindful-media'); ?></a>
                </div>
                <div class="mm-step-card">
                    <div class="mm-step-number">3</div>
                    <h3><?php _e('Display on Site', 'mindful-media'); ?></h3>
                    <p><?php _e('Add the archive shortcode to any page to display your media library.', 'mindful-media'); ?></p>
                    <a href="#shortcodes" class="button"><?php _e('View Shortcodes', 'mindful-media'); ?></a>
                </div>
            </div>
            
            <!-- Shortcodes Section -->
            <div class="mm-section" id="shortcodes">
                <div class="mm-section-header">
                    <svg class="mm-section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                    <h2><?php _e('Shortcodes', 'mindful-media'); ?></h2>
                </div>
                <div class="mm-section-body">
                    <div class="mm-shortcode-grid">
                        <div class="mm-shortcode-item">
                            <h4><?php _e('Media Archive', 'mindful-media'); ?></h4>
                            <div class="mm-shortcode-code">
                                <code>[mindful_media_archive]</code>
                                <button class="mm-copy-btn" onclick="navigator.clipboard.writeText('[mindful_media_archive]')"><?php _e('Copy', 'mindful-media'); ?></button>
                            </div>
                            <p><?php _e('Displays a filterable grid of all your media with YouTube-style filter chips.', 'mindful-media'); ?></p>
                        </div>
                        <div class="mm-shortcode-item">
                            <h4><?php _e('Browse Page', 'mindful-media'); ?></h4>
                            <div class="mm-shortcode-code">
                                <code>[mindful_media_browse]</code>
                                <button class="mm-copy-btn" onclick="navigator.clipboard.writeText('[mindful_media_browse]')"><?php _e('Copy', 'mindful-media'); ?></button>
                            </div>
                            <p><?php _e('Full landing page with featured media player and content below.', 'mindful-media'); ?></p>
                        </div>
                        <div class="mm-shortcode-item">
                            <h4><?php _e('Embed Single Item', 'mindful-media'); ?></h4>
                            <div class="mm-shortcode-code">
                                <code>[mindful_media id="123"]</code>
                                <button class="mm-copy-btn" onclick="navigator.clipboard.writeText('[mindful_media id=\"\"]')"><?php _e('Copy', 'mindful-media'); ?></button>
                            </div>
                            <p><?php _e('Embed a specific media item by its post ID.', 'mindful-media'); ?></p>
                        </div>
                        <div class="mm-shortcode-item">
                            <h4><?php _e('Embed Playlist', 'mindful-media'); ?></h4>
                            <div class="mm-shortcode-code">
                                <code>[mindful_media playlist="slug"]</code>
                                <button class="mm-copy-btn" onclick="navigator.clipboard.writeText('[mindful_media playlist=\"\"]')"><?php _e('Copy', 'mindful-media'); ?></button>
                            </div>
                            <p><?php _e('Embed an entire playlist by its slug.', 'mindful-media'); ?></p>
                        </div>
                        <div class="mm-shortcode-item">
                            <h4><?php _e('Category Row', 'mindful-media'); ?></h4>
                            <div class="mm-shortcode-code">
                                <code>[mindful_media_row taxonomy="media_teacher"]</code>
                                <button class="mm-copy-btn" onclick="navigator.clipboard.writeText('[mindful_media_row taxonomy=\"media_teacher\"]')"><?php _e('Copy', 'mindful-media'); ?></button>
                            </div>
                            <p><?php _e('Netflix-style horizontal slider row for any taxonomy (teachers, topics, categories, etc.).', 'mindful-media'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Troubleshooting Section -->
            <div class="mm-section">
                <div class="mm-section-header">
                    <svg class="mm-section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <h2><?php _e('Troubleshooting', 'mindful-media'); ?></h2>
                </div>
                <div class="mm-accordion">
                    <div class="mm-accordion-header" onclick="this.parentElement.classList.toggle('active')">
                        <?php _e('Media not displaying?', 'mindful-media'); ?>
                        <span class="mm-accordion-arrow">▼</span>
                    </div>
                    <div class="mm-accordion-content">
                        <ul>
                            <li><?php _e('Ensure posts are published (not draft)', 'mindful-media'); ?></li>
                            <li><?php _e('Check that you have created media items', 'mindful-media'); ?></li>
                            <li><?php _e('Clear any caching plugins', 'mindful-media'); ?></li>
                        </ul>
                    </div>
                </div>
                <div class="mm-accordion">
                    <div class="mm-accordion-header" onclick="this.parentElement.classList.toggle('active')">
                        <?php _e('404 error on single media pages?', 'mindful-media'); ?>
                        <span class="mm-accordion-arrow">▼</span>
                    </div>
                    <div class="mm-accordion-content">
                        <ul>
                            <li><?php _e('Go to Settings → Permalinks and click Save', 'mindful-media'); ?></li>
                            <li><?php _e('Deactivate and reactivate the plugin', 'mindful-media'); ?></li>
                        </ul>
                    </div>
                </div>
                <div class="mm-accordion">
                    <div class="mm-accordion-header" onclick="this.parentElement.classList.toggle('active')">
                        <?php _e('Modal player not working?', 'mindful-media'); ?>
                        <span class="mm-accordion-arrow">▼</span>
                    </div>
                    <div class="mm-accordion-content">
                        <ul>
                            <li><?php _e('Check browser console for JavaScript errors', 'mindful-media'); ?></li>
                            <li><?php _e('Ensure no plugin conflicts', 'mindful-media'); ?></li>
                            <li><?php _e('Verify the media URL is valid', 'mindful-media'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Help Section -->
            <div class="mm-section">
                <div class="mm-section-header">
                    <svg class="mm-section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <h2><?php _e('Need Help?', 'mindful-media'); ?></h2>
                </div>
                <div class="mm-section-body">
                    <p><?php _e('For questions and support, email', 'mindful-media'); ?> <a href="mailto:support@mindfuldesign.me">support@mindfuldesign.me</a> <?php _e('or visit', 'mindful-media'); ?> <a href="https://mindfuldesign.me" target="_blank" rel="noopener">mindfuldesign.me</a> <?php _e('for documentation.', 'mindful-media'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        $settings_saved = false;
        $active_tab = 'colors'; // Default tab
        
        if (isset($_POST['submit'])) {
            $this->save_settings();
            $settings_saved = true;
            // Preserve the active tab after save
            $active_tab = isset($_POST['active_tab']) ? sanitize_key($_POST['active_tab']) : 'colors';
        }
        
        $settings = get_option('mindful_media_settings', array());
        echo $this->get_branding_header('Settings');
        ?>
        <div class="wrap">
            
            <?php if ($settings_saved): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Settings saved successfully!', 'mindful-media'); ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Settings Tabs -->
            <div class="mindful-media-settings-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#colors" class="nav-tab <?php echo $active_tab === 'colors' ? 'nav-tab-active' : ''; ?>" data-tab="colors"><?php _e('Colors & Branding', 'mindful-media'); ?></a>
                    <a href="#typography" class="nav-tab <?php echo $active_tab === 'typography' ? 'nav-tab-active' : ''; ?>" data-tab="typography"><?php _e('Typography', 'mindful-media'); ?></a>
                    <a href="#layout" class="nav-tab <?php echo $active_tab === 'layout' ? 'nav-tab-active' : ''; ?>" data-tab="layout"><?php _e('Layout & Grid', 'mindful-media'); ?></a>
                    <a href="#player" class="nav-tab <?php echo $active_tab === 'player' ? 'nav-tab-active' : ''; ?>" data-tab="player"><?php _e('Player Settings', 'mindful-media'); ?></a>
                    <a href="#api" class="nav-tab <?php echo $active_tab === 'api' ? 'nav-tab-active' : ''; ?>" data-tab="api"><?php _e('API Keys', 'mindful-media'); ?></a>
                    <a href="#archive" class="nav-tab <?php echo $active_tab === 'archive' ? 'nav-tab-active' : ''; ?>" data-tab="archive"><?php _e('Archive Display', 'mindful-media'); ?></a>
                    <a href="#advanced" class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>" data-tab="advanced"><?php _e('Advanced', 'mindful-media'); ?></a>
                </nav>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('mindful_media_settings_nonce', 'mindful_media_settings_nonce_field'); ?>
                <input type="hidden" name="active_tab" id="active_tab_field" value="<?php echo esc_attr($active_tab); ?>" />
                
                <!-- Colors & Branding Tab -->
                <div id="colors-tab" class="mindful-media-tab-content <?php echo $active_tab === 'colors' ? 'active' : ''; ?>">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Color Scheme', 'mindful-media'); ?></th>
                            <td>
                                <select id="mindful-media-color-preset" style="margin-bottom: 15px;">
                                    <option value=""><?php _e('-- Choose a Preset Color Scheme --', 'mindful-media'); ?></option>
                                    <option value="dharma-red-gold" data-primary="#8B0000" data-secondary="#DAA520"><?php _e('Dharma Red & Gold (Traditional)', 'mindful-media'); ?></option>
                                    <option value="ocean-blue" data-primary="#1E90FF" data-secondary="#20B2AA"><?php _e('Ocean Blue (Calm & Peaceful)', 'mindful-media'); ?></option>
                                    <option value="forest-green" data-primary="#228B22" data-secondary="#9ACD32"><?php _e('Forest Green (Nature & Growth)', 'mindful-media'); ?></option>
                                    <option value="zen-purple" data-primary="#663399" data-secondary="#9370DB"><?php _e('Zen Purple (Spiritual)', 'mindful-media'); ?></option>
                                    <option value="earth-brown" data-primary="#8B4513" data-secondary="#D2691E"><?php _e('Earth Brown (Grounded)', 'mindful-media'); ?></option>
                                    <option value="lotus-pink" data-primary="#DB7093" data-secondary="#FFB6C1"><?php _e('Lotus Pink (Compassion)', 'mindful-media'); ?></option>
                                    <option value="mountain-gray" data-primary="#708090" data-secondary="#B0C4DE"><?php _e('Mountain Gray (Balanced)', 'mindful-media'); ?></option>
                                    <option value="sunrise-orange" data-primary="#FF8C00" data-secondary="#FFA500"><?php _e('Sunrise Orange (Energy)', 'mindful-media'); ?></option>
                                    <option value="midnight-blue" data-primary="#191970" data-secondary="#4169E1"><?php _e('Midnight Blue (Deep Wisdom)', 'mindful-media'); ?></option>
                                </select>
                                <p class="description"><?php _e('Quick start with a preset color scheme, or choose custom colors below', 'mindful-media'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Primary Color', 'mindful-media'); ?></th>
                            <td>
                                <input type="color" name="primary_color" id="primary_color" value="<?php echo esc_attr($settings['primary_color'] ?? '#8B0000'); ?>" />
                                <p class="description"><?php _e('Main color used for buttons and highlights', 'mindful-media'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Secondary Color', 'mindful-media'); ?></th>
                            <td>
                                <input type="color" name="secondary_color" id="secondary_color" value="<?php echo esc_attr($settings['secondary_color'] ?? '#DAA520'); ?>" />
                                <p class="description"><?php _e('Secondary color for accents and borders', 'mindful-media'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Text Colors', 'mindful-media'); ?></th>
                            <td>
                                <label for="text_color_light" style="display: block; margin-bottom: 8px; font-weight: 600;"><?php _e('Light Text Color (for dark backgrounds):', 'mindful-media'); ?></label>
                                <input type="color" id="text_color_light" name="text_color_light" value="<?php echo esc_attr($settings['text_color_light'] ?? '#FFFFFF'); ?>" style="margin-bottom: 8px;" />
                                <p class="description" style="margin-bottom: 15px;"><?php _e('Text color for buttons, modal headers, and other dark backgrounds', 'mindful-media'); ?></p>
                                
                                <label for="text_color_dark" style="display: block; margin-bottom: 8px; font-weight: 600;"><?php _e('Dark Text Color (for light backgrounds):', 'mindful-media'); ?></label>
                                <input type="color" id="text_color_dark" name="text_color_dark" value="<?php echo esc_attr($settings['text_color_dark'] ?? '#333333'); ?>" />
                                <p class="description"><?php _e('Text color for cards, content areas, and other light backgrounds', 'mindful-media'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Badge Colors', 'mindful-media'); ?></th>
                            <td>
                                <label for="audio_badge_color" style="display: block; margin-bottom: 8px; font-weight: 600;"><?php _e('Audio Badge Color:', 'mindful-media'); ?></label>
                                <input type="color" id="audio_badge_color" name="audio_badge_color" value="<?php echo esc_attr($settings['audio_badge_color'] ?? '#D4AF37'); ?>" style="margin-bottom: 8px;" />
                                <p class="description" style="margin-bottom: 15px;"><?php _e('Color for audio media type badges (default: monastic gold)', 'mindful-media'); ?></p>
                                
                                <label for="video_badge_color" style="display: block; margin-bottom: 8px; font-weight: 600;"><?php _e('Video Badge Color:', 'mindful-media'); ?></label>
                                <input type="color" id="video_badge_color" name="video_badge_color" value="<?php echo esc_attr($settings['video_badge_color'] ?? '#8B0000'); ?>" />
                                <p class="description"><?php _e('Color for video media type badges (default: deep monastic red)', 'mindful-media'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Player Progress Bar Color', 'mindful-media'); ?></th>
                            <td>
                                <input type="color" id="progress_bar_color" name="progress_bar_color" value="<?php echo esc_attr($settings['progress_bar_color'] ?? '#ff0000'); ?>" />
                                <p class="description"><?php _e('Color for the video/audio player progress bar (default: YouTube red #ff0000)', 'mindful-media'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Featured Image Dimensions', 'mindful-media'); ?></th>
                            <td>
                                <label for="audio_image_width" style="display: block; margin-bottom: 8px; font-weight: 600;"><?php _e('Audio Image Size (Square):', 'mindful-media'); ?></label>
                                <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 8px;">
                                    <input type="number" id="audio_image_width" name="audio_image_width" value="<?php echo esc_attr($settings['audio_image_width'] ?? 1000); ?>" style="width: 100px;" min="100" max="5000" />
                                    <span>×</span>
                                    <input type="number" id="audio_image_height" name="audio_image_height" value="<?php echo esc_attr($settings['audio_image_height'] ?? 1000); ?>" style="width: 100px;" min="100" max="5000" />
                                    <span>px</span>
                                </div>
                                <p class="description" style="margin-bottom: 15px;"><?php _e('Recommended dimensions for audio media (default: 1000×1000px, 1:1 aspect ratio)', 'mindful-media'); ?></p>
                                
                                <label for="video_image_width" style="display: block; margin-bottom: 8px; font-weight: 600;"><?php _e('Video Image Size (Widescreen):', 'mindful-media'); ?></label>
                                <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 8px;">
                                    <input type="number" id="video_image_width" name="video_image_width" value="<?php echo esc_attr($settings['video_image_width'] ?? 1920); ?>" style="width: 100px;" min="100" max="5000" />
                                    <span>×</span>
                                    <input type="number" id="video_image_height" name="video_image_height" value="<?php echo esc_attr($settings['video_image_height'] ?? 1080); ?>" style="width: 100px;" min="100" max="5000" />
                                    <span>px</span>
                                </div>
                                <p class="description"><?php _e('Recommended dimensions for video media (default: 1920×1080px, 16:9 aspect ratio)', 'mindful-media'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Typography Tab -->
                <div id="typography-tab" class="mindful-media-tab-content <?php echo $active_tab === 'typography' ? 'active' : ''; ?>">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Font Family', 'mindful-media'); ?></th>
                        <td>
                            <select name="font_family" id="mindful-media-font-family">
                                <option value="theme_default" <?php selected($settings['font_family'] ?? 'theme_default', 'theme_default'); ?>>
                                    <?php _e('Use Theme Font', 'mindful-media'); ?>
                                </option>
                                <optgroup label="<?php _e('System Fonts', 'mindful-media'); ?>">
                                    <option value="Arial, sans-serif" <?php selected($settings['font_family'] ?? 'theme_default', 'Arial, sans-serif'); ?>>
                                        Arial
                                    </option>
                                    <option value="Georgia, serif" <?php selected($settings['font_family'] ?? 'theme_default', 'Georgia, serif'); ?>>
                                        Georgia
                                    </option>
                                    <option value="Times, serif" <?php selected($settings['font_family'] ?? 'theme_default', 'Times, serif'); ?>>
                                        Times
                                    </option>
                                    <option value="-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif" <?php selected($settings['font_family'] ?? 'theme_default', '-apple-system, BlinkMacSystemFont, \'Segoe UI\', sans-serif'); ?>>
                                        System UI
                                    </option>
                                </optgroup>
                                <optgroup label="<?php _e('Google Fonts - Serif', 'mindful-media'); ?>">
                                    <option value="Lora" <?php selected($settings['font_family'] ?? 'theme_default', 'Lora'); ?>>
                                        Lora (Elegant Serif)
                                    </option>
                                    <option value="Merriweather" <?php selected($settings['font_family'] ?? 'theme_default', 'Merriweather'); ?>>
                                        Merriweather (Classic Serif)
                                    </option>
                                    <option value="Playfair Display" <?php selected($settings['font_family'] ?? 'theme_default', 'Playfair Display'); ?>>
                                        Playfair Display (Dramatic Serif)
                                    </option>
                                    <option value="Crimson Text" <?php selected($settings['font_family'] ?? 'theme_default', 'Crimson Text'); ?>>
                                        Crimson Text (Book Serif)
                                    </option>
                                    <option value="EB Garamond" <?php selected($settings['font_family'] ?? 'theme_default', 'EB Garamond'); ?>>
                                        EB Garamond (Classical Serif)
                                    </option>
                                </optgroup>
                                <optgroup label="<?php _e('Google Fonts - Sans Serif', 'mindful-media'); ?>">
                                    <option value="Open Sans" <?php selected($settings['font_family'] ?? 'theme_default', 'Open Sans'); ?>>
                                        Open Sans (Clean & Modern)
                                    </option>
                                    <option value="Roboto" <?php selected($settings['font_family'] ?? 'theme_default', 'Roboto'); ?>>
                                        Roboto (Contemporary)
                                    </option>
                                    <option value="Lato" <?php selected($settings['font_family'] ?? 'theme_default', 'Lato'); ?>>
                                        Lato (Friendly Sans)
                                    </option>
                                    <option value="Montserrat" <?php selected($settings['font_family'] ?? 'theme_default', 'Montserrat'); ?>>
                                        Montserrat (Geometric Sans)
                                    </option>
                                    <option value="Nunito" <?php selected($settings['font_family'] ?? 'theme_default', 'Nunito'); ?>>
                                        Nunito (Rounded Sans)
                                    </option>
                                    <option value="Source Sans Pro" <?php selected($settings['font_family'] ?? 'theme_default', 'Source Sans Pro'); ?>>
                                        Source Sans Pro (Professional)
                                    </option>
                                    <option value="Raleway" <?php selected($settings['font_family'] ?? 'theme_default', 'Raleway'); ?>>
                                        Raleway (Elegant Sans)
                                    </option>
                                    <option value="Inter" <?php selected($settings['font_family'] ?? 'theme_default', 'Inter'); ?>>
                                        Inter (UI Optimized)
                                    </option>
                                </optgroup>
                                <optgroup label="<?php _e('Google Fonts - Spiritual/Zen', 'mindful-media'); ?>">
                                    <option value="Cormorant Garamond" <?php selected($settings['font_family'] ?? 'theme_default', 'Cormorant Garamond'); ?>>
                                        Cormorant Garamond (Spiritual Serif)
                                    </option>
                                    <option value="Libre Baskerville" <?php selected($settings['font_family'] ?? 'theme_default', 'Libre Baskerville'); ?>>
                                        Libre Baskerville (Meditative)
                                    </option>
                                    <option value="Spectral" <?php selected($settings['font_family'] ?? 'theme_default', 'Spectral'); ?>>
                                        Spectral (Peaceful Reading)
                                    </option>
                                    <option value="Noto Serif" <?php selected($settings['font_family'] ?? 'theme_default', 'Noto Serif'); ?>>
                                        Noto Serif (Universal)
                                    </option>
                                </optgroup>
                            </select>
                            <p class="description"><?php _e('Choose the font family for content display. Google Fonts are automatically loaded.', 'mindful-media'); ?></p>
                            <div id="mindful-media-font-preview" style="margin-top: 15px; padding: 20px; background: #f5f5f5; border-radius: 4px; display: none;">
                                <p style="margin: 0 0 10px 0; font-size: 24px; font-weight: 700;">The Quick Brown Fox Jumps Over</p>
                                <p style="margin: 0 0 10px 0; font-size: 18px;">A journey of a thousand miles begins with a single step</p>
                                <p style="margin: 0; font-size: 14px; font-style: italic;">This is how your content will look with the selected font</p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Font Weight', 'mindful-media'); ?></th>
                        <td>
                            <select name="font_weight">
                                <option value="400" <?php selected($settings['font_weight'] ?? '400', '400'); ?>>
                                    <?php _e('Regular (400)', 'mindful-media'); ?>
                                </option>
                                <option value="300" <?php selected($settings['font_weight'] ?? '400', '300'); ?>>
                                    <?php _e('Light (300)', 'mindful-media'); ?>
                                </option>
                                <option value="500" <?php selected($settings['font_weight'] ?? '400', '500'); ?>>
                                    <?php _e('Medium (500)', 'mindful-media'); ?>
                                </option>
                                <option value="600" <?php selected($settings['font_weight'] ?? '400', '600'); ?>>
                                    <?php _e('Semi-Bold (600)', 'mindful-media'); ?>
                                </option>
                                <option value="700" <?php selected($settings['font_weight'] ?? '400', '700'); ?>>
                                    <?php _e('Bold (700)', 'mindful-media'); ?>
                                </option>
                            </select>
                            <p class="description"><?php _e('Font weight for body text (headings will be automatically bolder)', 'mindful-media'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Font Sizes', 'mindful-media'); ?></th>
                        <td>
                            <label for="font_size_title" style="display: block; margin-bottom: 8px; font-weight: 600;"><?php _e('Title Font Size:', 'mindful-media'); ?></label>
                            <input type="range" id="font_size_title" name="font_size_title" min="16" max="36" value="<?php echo esc_attr($settings['font_size_title'] ?? '22'); ?>" style="width: 200px;" />
                            <span id="font_size_title_display" style="margin-left: 10px; font-weight: 600;"><?php echo esc_html($settings['font_size_title'] ?? '22'); ?>px</span>
                            <p class="description" style="margin-bottom: 15px;"><?php _e('Font size for media item titles', 'mindful-media'); ?></p>
                            
                            <label for="font_size_teacher" style="display: block; margin-bottom: 8px; font-weight: 600;"><?php _e('Teacher/Author Font Size:', 'mindful-media'); ?></label>
                            <input type="range" id="font_size_teacher" name="font_size_teacher" min="14" max="24" value="<?php echo esc_attr($settings['font_size_teacher'] ?? '18'); ?>" style="width: 200px;" />
                            <span id="font_size_teacher_display" style="margin-left: 10px; font-weight: 600;"><?php echo esc_html($settings['font_size_teacher'] ?? '18'); ?>px</span>
                            <p class="description" style="margin-bottom: 15px;"><?php _e('Font size for teacher/author names', 'mindful-media'); ?></p>
                            
                            <label for="font_size_content" style="display: block; margin-bottom: 8px; font-weight: 600;"><?php _e('Content Font Size:', 'mindful-media'); ?></label>
                            <input type="range" id="font_size_content" name="font_size_content" min="12" max="20" value="<?php echo esc_attr($settings['font_size_content'] ?? '14'); ?>" style="width: 200px;" />
                            <span id="font_size_content_display" style="margin-left: 10px; font-weight: 600;"><?php echo esc_html($settings['font_size_content'] ?? '14'); ?>px</span>
                            <p class="description"><?php _e('Font size for excerpts and content text', 'mindful-media'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Filter Bar Font Sizes', 'mindful-media'); ?></th>
                        <td>
                            <label for="font_size_filter_heading" style="display: block; margin-bottom: 8px; font-weight: 600;"><?php _e('Filter Headings (Categories, Teachers, etc.):', 'mindful-media'); ?></label>
                            <input type="range" id="font_size_filter_heading" name="font_size_filter_heading" min="12" max="20" value="<?php echo esc_attr($settings['font_size_filter_heading'] ?? '16'); ?>" style="width: 200px;" />
                            <span id="font_size_filter_heading_display" style="margin-left: 10px; font-weight: 600;"><?php echo esc_html($settings['font_size_filter_heading'] ?? '16'); ?>px</span>
                            <p class="description" style="margin-bottom: 15px;"><?php _e('Font size for filter section headings', 'mindful-media'); ?></p>
                            
                            <label for="font_size_filter_options" style="display: block; margin-bottom: 8px; font-weight: 600;"><?php _e('Filter Options (Individual items):', 'mindful-media'); ?></label>
                            <input type="range" id="font_size_filter_options" name="font_size_filter_options" min="11" max="18" value="<?php echo esc_attr($settings['font_size_filter_options'] ?? '14'); ?>" style="width: 200px;" />
                            <span id="font_size_filter_options_display" style="margin-left: 10px; font-weight: 600;"><?php echo esc_html($settings['font_size_filter_options'] ?? '14'); ?>px</span>
                            <p class="description" style="margin-bottom: 15px;"><?php _e('Font size for individual filter options and checkboxes', 'mindful-media'); ?></p>
                            
                            <label for="font_size_filter_buttons" style="display: block; margin-bottom: 8px; font-weight: 600;"><?php _e('Filter Buttons (Apply/Clear):', 'mindful-media'); ?></label>
                            <input type="range" id="font_size_filter_buttons" name="font_size_filter_buttons" min="11" max="18" value="<?php echo esc_attr($settings['font_size_filter_buttons'] ?? '13'); ?>" style="width: 200px;" />
                            <span id="font_size_filter_buttons_display" style="margin-left: 10px; font-weight: 600;"><?php echo esc_html($settings['font_size_filter_buttons'] ?? '13'); ?>px</span>
                            <p class="description"><?php _e('Font size for Apply Filters and Clear Filters buttons', 'mindful-media'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Single Page Font Sizes', 'mindful-media'); ?></th>
                        <td>
                            <label for="font_size_single_title" style="display: block; margin-bottom: 8px; font-weight: 600;"><?php _e('Single Page Title:', 'mindful-media'); ?></label>
                            <input type="range" id="font_size_single_title" name="font_size_single_title" min="20" max="48" value="<?php echo esc_attr($settings['font_size_single_title'] ?? '32'); ?>" style="width: 200px;" />
                            <span id="font_size_single_title_display" style="margin-left: 10px; font-weight: 600;"><?php echo esc_html($settings['font_size_single_title'] ?? '32'); ?>px</span>
                            <p class="description" style="margin-bottom: 15px;"><?php _e('Font size for the main title on single media pages', 'mindful-media'); ?></p>
                            
                            <label for="font_size_single_content" style="display: block; margin-bottom: 8px; font-weight: 600;"><?php _e('Single Page Content:', 'mindful-media'); ?></label>
                            <input type="range" id="font_size_single_content" name="font_size_single_content" min="14" max="24" value="<?php echo esc_attr($settings['font_size_single_content'] ?? '16'); ?>" style="width: 200px;" />
                            <span id="font_size_single_content_display" style="margin-left: 10px; font-weight: 600;"><?php echo esc_html($settings['font_size_single_content'] ?? '16'); ?>px</span>
                            <p class="description" style="margin-bottom: 15px;"><?php _e('Font size for the main content text on single media pages', 'mindful-media'); ?></p>
                            
                            <label for="font_size_single_meta" style="display: block; margin-bottom: 8px; font-weight: 600;"><?php _e('Single Page Meta Info (Teacher, Tags, etc.):', 'mindful-media'); ?></label>
                            <input type="range" id="font_size_single_meta" name="font_size_single_meta" min="12" max="20" value="<?php echo esc_attr($settings['font_size_single_meta'] ?? '14'); ?>" style="width: 200px;" />
                            <span id="font_size_single_meta_display" style="margin-left: 10px; font-weight: 600;"><?php echo esc_html($settings['font_size_single_meta'] ?? '14'); ?>px</span>
                            <p class="description"><?php _e('Font size for teacher names, tags, and other meta information', 'mindful-media'); ?></p>
                        </td>
                    </tr>
                    </table>
                </div>
                
                <!-- Layout & Grid Tab -->
                <div id="layout-tab" class="mindful-media-tab-content <?php echo $active_tab === 'layout' ? 'active' : ''; ?>">
                    <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Filter Layout', 'mindful-media'); ?></th>
                        <td>
                            <select name="filter_layout">
                                <option value="horizontal" <?php selected($settings['filter_layout'] ?? 'horizontal', 'horizontal'); ?>>
                                    <?php _e('Horizontal', 'mindful-media'); ?>
                                </option>
                                <option value="vertical" <?php selected($settings['filter_layout'] ?? 'horizontal', 'vertical'); ?>>
                                    <?php _e('Vertical', 'mindful-media'); ?>
                                </option>
                            </select>
                            <p class="description"><?php _e('How to display the filter sections', 'mindful-media'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Grid Columns (Desktop)', 'mindful-media'); ?></th>
                        <td>
                            <select name="grid_columns">
                                <option value="2" <?php selected($settings['grid_columns'] ?? '3', '2'); ?>>
                                    <?php _e('2 Columns', 'mindful-media'); ?>
                                </option>
                                <option value="3" <?php selected($settings['grid_columns'] ?? '3', '3'); ?>>
                                    <?php _e('3 Columns', 'mindful-media'); ?>
                                </option>
                                <option value="4" <?php selected($settings['grid_columns'] ?? '3', '4'); ?>>
                                    <?php _e('4 Columns', 'mindful-media'); ?>
                                </option>
                            </select>
                            <p class="description"><?php _e('Number of columns on desktop screens (1025px+)', 'mindful-media'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Grid Columns (Tablet)', 'mindful-media'); ?></th>
                        <td>
                            <select name="grid_columns_tablet">
                                <option value="1" <?php selected($settings['grid_columns_tablet'] ?? '2', '1'); ?>>
                                    <?php _e('1 Column', 'mindful-media'); ?>
                                </option>
                                <option value="2" <?php selected($settings['grid_columns_tablet'] ?? '2', '2'); ?>>
                                    <?php _e('2 Columns', 'mindful-media'); ?>
                                </option>
                                <option value="3" <?php selected($settings['grid_columns_tablet'] ?? '2', '3'); ?>>
                                    <?php _e('3 Columns', 'mindful-media'); ?>
                                </option>
                            </select>
                            <p class="description"><?php _e('Number of columns on tablet screens (769px - 1024px)', 'mindful-media'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Grid Columns (Mobile)', 'mindful-media'); ?></th>
                        <td>
                            <select name="grid_columns_mobile">
                                <option value="1" <?php selected($settings['grid_columns_mobile'] ?? '1', '1'); ?>>
                                    <?php _e('1 Column', 'mindful-media'); ?>
                                </option>
                                <option value="2" <?php selected($settings['grid_columns_mobile'] ?? '1', '2'); ?>>
                                    <?php _e('2 Columns', 'mindful-media'); ?>
                                </option>
                            </select>
                            <p class="description"><?php _e('Number of columns on mobile screens (768px and below)', 'mindful-media'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Card Spacing', 'mindful-media'); ?></th>
                        <td>
                            <input type="number" name="card_spacing" value="<?php echo esc_attr($settings['card_spacing'] ?? 30); ?>" min="10" max="50" step="5" />
                            <span>px</span>
                            <p class="description"><?php _e('Space between cards in the grid layout', 'mindful-media'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Archive Page Link', 'mindful-media'); ?></th>
                        <td>
                            <input type="text" name="archive_link" value="<?php echo esc_attr($settings['archive_link'] ?? '/media'); ?>" style="width: 300px;" />
                            <p class="description"><?php _e('URL for the back button on single media pages (e.g., /media)', 'mindful-media'); ?></p>
                        </td>
                    </tr>
                    </table>
                </div>
                
                <!-- Player Settings Tab -->
                <div id="player-tab" class="mindful-media-tab-content <?php echo $active_tab === 'player' ? 'active' : ''; ?>">
                    <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Auto-Play Media', 'mindful-media'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="player_autoplay" value="1" <?php checked($settings['player_autoplay'] ?? '0', '1'); ?> />
                                <?php _e('Automatically play media when page loads', 'mindful-media'); ?>
                            </label>
                            <p class="description"><?php _e('Note: Most browsers block autoplay with sound. Muted autoplay may work.', 'mindful-media'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Default Volume', 'mindful-media'); ?></th>
                        <td>
                            <input type="range" name="player_volume" id="player_volume" min="0" max="100" value="<?php echo esc_attr($settings['player_volume'] ?? '80'); ?>" style="width: 300px;" />
                            <span id="player_volume_display"><?php echo esc_attr($settings['player_volume'] ?? '80'); ?>%</span>
                            <p class="description"><?php _e('Default volume level for media players (0-100%)', 'mindful-media'); ?></p>
                            <script>
                                jQuery(document).ready(function($) {
                                    $('#player_volume').on('input', function() {
                                        $('#player_volume_display').text($(this).val() + '%');
                                    });
                                });
                            </script>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Player Size', 'mindful-media'); ?></th>
                        <td>
                            <select name="player_size">
                                <option value="normal" <?php selected($settings['player_size'] ?? 'normal', 'normal'); ?>>
                                    <?php _e('Normal (800px max width)', 'mindful-media'); ?>
                                </option>
                                <option value="large" <?php selected($settings['player_size'] ?? 'normal', 'large'); ?>>
                                    <?php _e('Large (1000px max width)', 'mindful-media'); ?>
                                </option>
                                <option value="full" <?php selected($settings['player_size'] ?? 'normal', 'full'); ?>>
                                    <?php _e('Full Width (100%)', 'mindful-media'); ?>
                                </option>
                            </select>
                            <p class="description"><?php _e('Maximum width for media players on single pages', 'mindful-media'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Show Player Controls', 'mindful-media'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="player_controls" value="1" <?php checked($settings['player_controls'] ?? '1', '1'); ?> />
                                <?php _e('Display playback controls (play, pause, volume, etc.)', 'mindful-media'); ?>
                            </label>
                            <p class="description"><?php _e('Uncheck to hide controls (not recommended for most uses)', 'mindful-media'); ?></p>
                        </td>
                    </tr>
                    </table>
                </div>
                
                <!-- API Keys Tab -->
                <div id="api-tab" class="mindful-media-tab-content <?php echo $active_tab === 'api' ? 'active' : ''; ?>">
                    <div style="background: #f0f6fc; border-left: 4px solid #0073aa; padding: 15px; margin-bottom: 20px;">
                        <h3 style="margin-top: 0;"><?php _e('Platform API Integration', 'mindful-media'); ?></h3>
                        <p><?php _e('Enable smart platform detection to automatically identify privacy status (Public, Private, Unlisted) for SoundCloud and Vimeo content. API keys are optional but enhance functionality.', 'mindful-media'); ?></p>
                    </div>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('SoundCloud Client ID', 'mindful-media'); ?></th>
                            <td>
                                <input type="text" name="soundcloud_client_id" 
                                       value="<?php echo esc_attr($settings['soundcloud_client_id'] ?? ''); ?>" 
                                       class="regular-text" 
                                       placeholder="<?php _e('Enter your SoundCloud Client ID', 'mindful-media'); ?>" />
                                <p class="description">
                                    <?php _e('Get your Client ID from', 'mindful-media'); ?> 
                                    <a href="https://developers.soundcloud.com/" target="_blank">SoundCloud Developers</a>
                                    <br><?php _e('Used to detect track privacy status and fetch metadata.', 'mindful-media'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Vimeo Access Token', 'mindful-media'); ?></th>
                            <td>
                                <input type="text" name="vimeo_access_token" 
                                       value="<?php echo esc_attr($settings['vimeo_access_token'] ?? ''); ?>" 
                                       class="regular-text" 
                                       placeholder="<?php _e('Enter your Vimeo Access Token', 'mindful-media'); ?>" />
                                <p class="description">
                                    <?php _e('Get your Access Token from', 'mindful-media'); ?> 
                                    <a href="https://developer.vimeo.com/" target="_blank">Vimeo Developer Portal</a>
                                    <br><?php _e('Used to detect video privacy status (Public, Private, Unlisted).', 'mindful-media'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Privacy Badges', 'mindful-media'); ?></th>
                            <td>
                                <p style="margin-top: 0;">
                                    <?php _e('When API keys are configured, privacy badges will automatically appear on media items:', 'mindful-media'); ?>
                                </p>
                                <ul style="list-style: disc; margin-left: 20px;">
                                    <li><strong>🔒 Private</strong> - <?php _e('Content is private', 'mindful-media'); ?></li>
                                    <li><strong>🌐 Public</strong> - <?php _e('Content is publicly accessible', 'mindful-media'); ?></li>
                                    <li><strong>👁️ Unlisted</strong> - <?php _e('Content is unlisted (link-only access)', 'mindful-media'); ?></li>
                                </ul>
                                <p class="description">
                                    <?php _e('Privacy status is cached for 24 hours to improve performance.', 'mindful-media'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Archive Display Tab -->
                <div id="archive-tab" class="mindful-media-tab-content <?php echo $active_tab === 'archive' ? 'active' : ''; ?>">
                    <h3><?php _e('Filter Tabs Visibility', 'mindful-media'); ?></h3>
                    <p><?php _e('Choose which filter tabs to display on the archive page:', 'mindful-media'); ?></p>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Browse Navigation Tabs', 'mindful-media'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="archive_show_home_tab" value="1" <?php checked($settings['archive_show_home_tab'] ?? '1', '1'); ?>>
                                        <?php _e('Home (All)', 'mindful-media'); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="archive_show_teachers_tab" value="1" <?php checked($settings['archive_show_teachers_tab'] ?? '1', '1'); ?>>
                                        <?php _e('Teachers', 'mindful-media'); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="archive_show_topics_tab" value="1" <?php checked($settings['archive_show_topics_tab'] ?? '1', '1'); ?>>
                                        <?php _e('Topics', 'mindful-media'); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="archive_show_playlists_tab" value="1" <?php checked($settings['archive_show_playlists_tab'] ?? '1', '1'); ?>>
                                        <?php _e('Playlists', 'mindful-media'); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="archive_show_categories_tab" value="1" <?php checked($settings['archive_show_categories_tab'] ?? '0', '1'); ?>>
                                        <?php _e('Categories', 'mindful-media'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Filter Chips', 'mindful-media'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="archive_show_duration_filter" value="1" <?php checked($settings['archive_show_duration_filter'] ?? '1', '1'); ?>>
                                        <?php _e('Show Duration Filter', 'mindful-media'); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="archive_show_year_filter" value="1" <?php checked($settings['archive_show_year_filter'] ?? '1', '1'); ?>>
                                        <?php _e('Show Year Filter', 'mindful-media'); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="archive_show_type_filter" value="1" <?php checked($settings['archive_show_type_filter'] ?? '1', '1'); ?>>
                                        <?php _e('Show Media Type Filter (Video/Audio)', 'mindful-media'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                    
                    <h3><?php _e('Counter Display', 'mindful-media'); ?></h3>
                    <p><?php _e('Control whether item counts are displayed:', 'mindful-media'); ?></p>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Show Counters', 'mindful-media'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="archive_show_filter_counts" value="1" <?php checked($settings['archive_show_filter_counts'] ?? '1', '1'); ?>>
                                        <?php _e('Show counts on filter chips (e.g., "Video (24)")', 'mindful-media'); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="archive_show_taxonomy_counts" value="1" <?php checked($settings['archive_show_taxonomy_counts'] ?? '1', '1'); ?>>
                                        <?php _e('Show counts on taxonomy cards (e.g., "12 items")', 'mindful-media'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                    
                    <h3><?php _e('Browse Page Layout', 'mindful-media'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Featured Section', 'mindful-media'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="archive_show_featured" value="1" <?php checked($settings['archive_show_featured'] ?? '1', '1'); ?>>
                                    <?php _e('Show Featured section on Browse page', 'mindful-media'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Items Per Row', 'mindful-media'); ?></th>
                            <td>
                                <select name="archive_items_per_row">
                                    <option value="4" <?php selected($settings['archive_items_per_row'] ?? '5', '4'); ?>>4 items</option>
                                    <option value="5" <?php selected($settings['archive_items_per_row'] ?? '5', '5'); ?>>5 items (Default)</option>
                                    <option value="6" <?php selected($settings['archive_items_per_row'] ?? '5', '6'); ?>>6 items</option>
                                </select>
                                <p class="description"><?php _e('Number of items visible per row in sliders (on desktop)', 'mindful-media'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Browse Page Sections', 'mindful-media'); ?></th>
                            <td>
                                <fieldset>
                                    <label style="display: block; margin-bottom: 8px;">
                                        <input type="checkbox" name="browse_show_teachers" value="1" <?php checked($settings['browse_show_teachers'] ?? '1', '1'); ?>>
                                        <?php _e('Show Teachers section', 'mindful-media'); ?>
                                    </label>
                                    <label style="display: block; margin-bottom: 8px;">
                                        <input type="checkbox" name="browse_show_topics" value="1" <?php checked($settings['browse_show_topics'] ?? '1', '1'); ?>>
                                        <?php _e('Show Topics section', 'mindful-media'); ?>
                                    </label>
                                    <label style="display: block; margin-bottom: 8px;">
                                        <input type="checkbox" name="browse_show_playlists" value="1" <?php checked($settings['browse_show_playlists'] ?? '1', '1'); ?>>
                                        <?php _e('Show Playlists section', 'mindful-media'); ?>
                                    </label>
                                    <label style="display: block; margin-bottom: 8px;">
                                        <input type="checkbox" name="browse_show_categories" value="1" <?php checked($settings['browse_show_categories'] ?? '1', '1'); ?>>
                                        <?php _e('Show Categories section', 'mindful-media'); ?>
                                    </label>
                                    <label style="display: block; margin-bottom: 8px;">
                                        <input type="checkbox" name="browse_show_media_types" value="1" <?php checked($settings['browse_show_media_types'] ?? '0', '1'); ?>>
                                        <?php _e('Show Media Types section', 'mindful-media'); ?>
                                    </label>
                                </fieldset>
                                <p class="description"><?php _e('Control which sections appear on the Browse page Home tab.', 'mindful-media'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <h3><?php _e('Navigation', 'mindful-media'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Browse Page URL', 'mindful-media'); ?></th>
                            <td>
                                <input type="text" name="archive_back_url" value="<?php echo esc_attr($settings['archive_back_url'] ?? '/media'); ?>" class="regular-text" placeholder="/media">
                                <p class="description"><?php _e('URL for navigation links on taxonomy pages (teacher, topic, category, playlist). This is where "Home", "Topics", "Playlists", and "Back to All Media" buttons will navigate to. Use the page URL where your [mindful_media_browse] shortcode is placed (e.g., /browse, /media, or /test).', 'mindful-media'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Advanced Tab -->
                <div id="advanced-tab" class="mindful-media-tab-content <?php echo $active_tab === 'advanced' ? 'active' : ''; ?>">
                    <h3><?php _e('Taxonomies', 'mindful-media'); ?></h3>
                    <p><?php _e('Select which taxonomies to use for organizing your content:', 'mindful-media'); ?></p>
                    <table class="form-table">
                        <?php $this->render_taxonomy_settings($settings); ?>
                    </table>
                    
                    <h3><?php _e('Custom Fields', 'mindful-media'); ?></h3>
                    <div id="custom-fields-container">
                        <?php $this->render_custom_fields_editor($settings); ?>
                    </div>
                </div>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" value="<?php _e('Save Settings', 'mindful-media'); ?>" />
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render taxonomy settings
     */
    private function render_taxonomy_settings($settings) {
        $available_taxonomies = MindfulMedia_Taxonomies::get_available_taxonomies();
        $enabled_taxonomies = isset($settings['enabled_taxonomies']) ? $settings['enabled_taxonomies'] : array_keys($available_taxonomies);
        
        foreach ($available_taxonomies as $taxonomy_key => $taxonomy_info) {
            $checked = in_array($taxonomy_key, $enabled_taxonomies) ? 'checked' : '';
            ?>
            <tr>
                <th scope="row"><?php echo esc_html($taxonomy_info['name']); ?></th>
                <td>
                    <input type="checkbox" name="enabled_taxonomies[]" value="<?php echo esc_attr($taxonomy_key); ?>" <?php echo $checked; ?> />
                    <label><?php echo esc_html($taxonomy_info['description']); ?></label>
                </td>
            </tr>
            <?php
        }
    }
    
    /**
     * Render custom fields editor
     */
    private function render_custom_fields_editor($settings) {
        $custom_fields = isset($settings['custom_fields']) ? $settings['custom_fields'] : array();
        ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('Field Key', 'mindful-media'); ?></th>
                    <th><?php _e('Label', 'mindful-media'); ?></th>
                    <th><?php _e('Type', 'mindful-media'); ?></th>
                    <th><?php _e('Description', 'mindful-media'); ?></th>
                    <th><?php _e('Actions', 'mindful-media'); ?></th>
                </tr>
            </thead>
            <tbody id="custom-fields-list">
                <?php if (!empty($custom_fields)): ?>
                    <?php foreach ($custom_fields as $key => $field): ?>
                        <tr>
                            <td><input type="text" name="custom_fields[<?php echo esc_attr($key); ?>][key]" value="<?php echo esc_attr($key); ?>" readonly /></td>
                            <td><input type="text" name="custom_fields[<?php echo esc_attr($key); ?>][label]" value="<?php echo esc_attr($field['label']); ?>" /></td>
                            <td>
                                <select name="custom_fields[<?php echo esc_attr($key); ?>][type]">
                                    <option value="text" <?php selected($field['type'], 'text'); ?>><?php _e('Text', 'mindful-media'); ?></option>
                                    <option value="textarea" <?php selected($field['type'], 'textarea'); ?>><?php _e('Textarea', 'mindful-media'); ?></option>
                                    <option value="select" <?php selected($field['type'], 'select'); ?>><?php _e('Select', 'mindful-media'); ?></option>
                                    <option value="checkbox" <?php selected($field['type'], 'checkbox'); ?>><?php _e('Checkbox', 'mindful-media'); ?></option>
                                    <option value="url" <?php selected($field['type'], 'url'); ?>><?php _e('URL', 'mindful-media'); ?></option>
                                    <option value="date" <?php selected($field['type'], 'date'); ?>><?php _e('Date', 'mindful-media'); ?></option>
                                </select>
                            </td>
                            <td><input type="text" name="custom_fields[<?php echo esc_attr($key); ?>][description]" value="<?php echo esc_attr($field['description'] ?? ''); ?>" /></td>
                            <td><button type="button" class="button remove-field"><?php _e('Remove', 'mindful-media'); ?></button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <p>
            <button type="button" id="add-custom-field" class="button"><?php _e('Add Custom Field', 'mindful-media'); ?></button>
        </p>
        
        <script>
        jQuery(document).ready(function($) {
            $('#add-custom-field').on('click', function() {
                var key = prompt('<?php _e('Enter field key (no spaces, lowercase with underscores):', 'mindful-media'); ?>');
                if (key && key.match(/^[a-z_]+$/)) {
                    var row = '<tr>' +
                        '<td><input type="text" name="custom_fields[' + key + '][key]" value="' + key + '" readonly /></td>' +
                        '<td><input type="text" name="custom_fields[' + key + '][label]" value="" /></td>' +
                        '<td><select name="custom_fields[' + key + '][type]">' +
                        '<option value="text"><?php _e('Text', 'mindful-media'); ?></option>' +
                        '<option value="textarea"><?php _e('Textarea', 'mindful-media'); ?></option>' +
                        '<option value="select"><?php _e('Select', 'mindful-media'); ?></option>' +
                        '<option value="checkbox"><?php _e('Checkbox', 'mindful-media'); ?></option>' +
                        '<option value="url"><?php _e('URL', 'mindful-media'); ?></option>' +
                        '<option value="date"><?php _e('Date', 'mindful-media'); ?></option>' +
                        '</select></td>' +
                        '<td><input type="text" name="custom_fields[' + key + '][description]" value="" /></td>' +
                        '<td><button type="button" class="button remove-field"><?php _e('Remove', 'mindful-media'); ?></button></td>' +
                        '</tr>';
                    $('#custom-fields-list').append(row);
                }
            });
            
            $(document).on('click', '.remove-field', function() {
                $(this).closest('tr').remove();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Import/Export page
     */
    public function import_export_page() {
        echo $this->get_branding_header('Import/Export');
        ?>
        <div class="wrap">
            
            <h2><?php _e('Export', 'mindful-media'); ?></h2>
            <p><?php _e('Export all your mindful media content and settings.', 'mindful-media'); ?></p>
            <a href="<?php echo wp_nonce_url(admin_url('admin-ajax.php?action=mindful_media_export'), 'mindful_media_export_nonce'); ?>" class="button button-primary">
                <?php _e('Download Export File', 'mindful-media'); ?>
            </a>
            
            <h2><?php _e('Import', 'mindful-media'); ?></h2>
            <p><?php _e('Import mindful media content from a previously exported file.', 'mindful-media'); ?></p>
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('mindful_media_import_nonce', 'mindful_media_import_nonce_field'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Import File', 'mindful-media'); ?></th>
                        <td>
                            <input type="file" name="import_file" accept=".json" />
                            <p class="description"><?php _e('Choose a JSON export file to import.', 'mindful-media'); ?></p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="import_submit" class="button-primary" value="<?php _e('Import', 'mindful-media'); ?>" />
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'mindful-media'));
        }
        
        // Verify nonce
        if (!isset($_POST['mindful_media_settings_nonce_field']) || 
            !wp_verify_nonce($_POST['mindful_media_settings_nonce_field'], 'mindful_media_settings_nonce')) {
            wp_die(__('Security check failed.', 'mindful-media'));
        }
        
        $settings = array(
            'primary_color' => sanitize_hex_color($_POST['primary_color']),
            'secondary_color' => sanitize_hex_color($_POST['secondary_color']),
            'text_color_light' => sanitize_hex_color($_POST['text_color_light'] ?? '#FFFFFF'),
            'text_color_dark' => sanitize_hex_color($_POST['text_color_dark'] ?? '#333333'),
            'audio_badge_color' => sanitize_hex_color($_POST['audio_badge_color'] ?? '#D4AF37'),
            'video_badge_color' => sanitize_hex_color($_POST['video_badge_color'] ?? '#8B0000'),
            'progress_bar_color' => sanitize_hex_color($_POST['progress_bar_color'] ?? '#ff0000'),
            'audio_image_width' => intval($_POST['audio_image_width'] ?? 1000),
            'audio_image_height' => intval($_POST['audio_image_height'] ?? 1000),
            'video_image_width' => intval($_POST['video_image_width'] ?? 1920),
            'video_image_height' => intval($_POST['video_image_height'] ?? 1080),
            'font_family' => sanitize_text_field($_POST['font_family']),
            'font_weight' => sanitize_text_field($_POST['font_weight'] ?? '400'),
            'font_size_title' => intval($_POST['font_size_title'] ?? 22),
            'font_size_teacher' => intval($_POST['font_size_teacher'] ?? 18),
            'font_size_content' => intval($_POST['font_size_content'] ?? 14),
            'font_size_filter_heading' => intval($_POST['font_size_filter_heading'] ?? 16),
            'font_size_filter_options' => intval($_POST['font_size_filter_options'] ?? 14),
            'font_size_filter_buttons' => intval($_POST['font_size_filter_buttons'] ?? 13),
            'font_size_single_title' => intval($_POST['font_size_single_title'] ?? 32),
            'font_size_single_content' => intval($_POST['font_size_single_content'] ?? 16),
            'font_size_single_meta' => intval($_POST['font_size_single_meta'] ?? 14),
            'filter_layout' => sanitize_text_field($_POST['filter_layout']),
            'grid_columns' => intval($_POST['grid_columns']),
            'grid_columns_tablet' => intval($_POST['grid_columns_tablet']),
            'grid_columns_mobile' => intval($_POST['grid_columns_mobile']),
            'card_spacing' => intval($_POST['card_spacing']),
            'archive_link' => sanitize_text_field($_POST['archive_link']),
            'player_autoplay' => isset($_POST['player_autoplay']) ? '1' : '0',
            'player_volume' => intval($_POST['player_volume'] ?? 80),
            'player_size' => sanitize_text_field($_POST['player_size'] ?? 'normal'),
            'player_controls' => isset($_POST['player_controls']) ? '1' : '0',
            'soundcloud_client_id' => sanitize_text_field($_POST['soundcloud_client_id'] ?? ''),
            'vimeo_access_token' => sanitize_text_field($_POST['vimeo_access_token'] ?? ''),
            // Archive Display settings
            'archive_show_home_tab' => isset($_POST['archive_show_home_tab']) ? '1' : '0',
            'archive_show_teachers_tab' => isset($_POST['archive_show_teachers_tab']) ? '1' : '0',
            'archive_show_topics_tab' => isset($_POST['archive_show_topics_tab']) ? '1' : '0',
            'archive_show_playlists_tab' => isset($_POST['archive_show_playlists_tab']) ? '1' : '0',
            'archive_show_categories_tab' => isset($_POST['archive_show_categories_tab']) ? '1' : '0',
            'archive_show_duration_filter' => isset($_POST['archive_show_duration_filter']) ? '1' : '0',
            'archive_show_year_filter' => isset($_POST['archive_show_year_filter']) ? '1' : '0',
            'archive_show_type_filter' => isset($_POST['archive_show_type_filter']) ? '1' : '0',
            'archive_show_filter_counts' => isset($_POST['archive_show_filter_counts']) ? '1' : '0',
            'archive_show_taxonomy_counts' => isset($_POST['archive_show_taxonomy_counts']) ? '1' : '0',
            'archive_show_featured' => isset($_POST['archive_show_featured']) ? '1' : '0',
            'archive_items_per_row' => sanitize_text_field($_POST['archive_items_per_row'] ?? '5'),
            'archive_back_url' => esc_url_raw($_POST['archive_back_url'] ?? '/media'),
            // Browse page section visibility
            'browse_show_teachers' => isset($_POST['browse_show_teachers']) ? '1' : '0',
            'browse_show_topics' => isset($_POST['browse_show_topics']) ? '1' : '0',
            'browse_show_playlists' => isset($_POST['browse_show_playlists']) ? '1' : '0',
            'browse_show_categories' => isset($_POST['browse_show_categories']) ? '1' : '0',
            'browse_show_media_types' => isset($_POST['browse_show_media_types']) ? '1' : '0',
            'enabled_taxonomies' => array(),
            'custom_fields' => array()
        );
        
        // Process enabled taxonomies
        if (isset($_POST['enabled_taxonomies']) && is_array($_POST['enabled_taxonomies'])) {
            $settings['enabled_taxonomies'] = array_map('sanitize_key', $_POST['enabled_taxonomies']);
        }
        
        // Process custom fields
        if (isset($_POST['custom_fields']) && is_array($_POST['custom_fields'])) {
            foreach ($_POST['custom_fields'] as $key => $field_data) {
                $settings['custom_fields'][sanitize_key($key)] = array(
                    'label' => sanitize_text_field($field_data['label']),
                    'type' => sanitize_text_field($field_data['type']),
                    'description' => sanitize_text_field($field_data['description'])
                );
            }
        }
        
        update_option('mindful_media_settings', $settings);
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        global $post_type;
        
        if ($post_type === 'mindful_media' || strpos($hook, 'mindful-media') !== false) {
            wp_enqueue_script('jquery');
            wp_enqueue_style(
                'mindful-media-admin',
                MINDFUL_MEDIA_PLUGIN_URL . 'admin/css/admin.css',
                array(),
                MINDFUL_MEDIA_VERSION
            );
            wp_enqueue_script(
                'mindful-media-admin',
                MINDFUL_MEDIA_PLUGIN_URL . 'admin/js/admin.js',
                array('jquery'),
                MINDFUL_MEDIA_VERSION,
                true
            );
        }
    }
    
    /**
     * Set custom columns for post list
     */
    public function set_custom_columns($columns) {
        unset($columns['date']);
        
        $columns['featured_image'] = __('Image', 'mindful-media');
        $columns['duration'] = __('Duration', 'mindful-media');
        $columns['media_type'] = __('Type', 'mindful-media');
        $columns['visibility'] = __('Visibility', 'mindful-media');
        $columns['featured'] = __('Featured', 'mindful-media');
        $columns['date'] = __('Date', 'mindful-media');
        
        return $columns;
    }
    
    /**
     * Custom column content
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'featured_image':
                if (has_post_thumbnail($post_id)) {
                    echo get_the_post_thumbnail($post_id, array(50, 50));
                } else {
                    echo '—';
                }
                break;
                
            case 'duration':
                $hours = get_post_meta($post_id, '_mindful_media_duration_hours', true);
                $minutes = get_post_meta($post_id, '_mindful_media_duration_minutes', true);
                
                if ($hours || $minutes) {
                    $duration = '';
                    if ($hours) $duration .= $hours . 'h ';
                    if ($minutes) $duration .= $minutes . 'm';
                    echo trim($duration);
                } else {
                    echo '—';
                }
                break;
                
            case 'media_type':
                $terms = get_the_terms($post_id, 'media_type');
                if ($terms && !is_wp_error($terms)) {
                    echo esc_html($terms[0]->name);
                } else {
                    echo '—';
                }
                break;
                
            case 'featured':
                $featured = get_post_meta($post_id, '_mindful_media_featured', true);
                echo $featured ? '★' : '—';
                break;

            case 'visibility':
                $hidden = get_post_meta($post_id, '_mindful_media_hide_from_archive', true);
                if ($hidden === '1') {
                    echo '<span class="dashicons dashicons-hidden" title="' . __('Hidden from Archive', 'mindful-media') . '" style="color: #999;"></span> ' . __('Hidden', 'mindful-media');
                } else {
                    echo '<span class="dashicons dashicons-visibility" title="' . __('Visible in Archive', 'mindful-media') . '" style="color: #46b450;"></span> ' . __('Visible', 'mindful-media');
                }
                break;
        }
    }
    
    /**
     * Set sortable columns
     */
    public function set_sortable_columns($columns) {
        $columns['featured'] = 'featured';
        return $columns;
    }

    /**
     * Add Quick Edit fields
     */
    public function add_quick_edit_fields($column_name, $post_type) {
        if ($post_type !== 'mindful_media' || $column_name !== 'visibility') {
            return;
        }

        ?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <div class="inline-edit-group wp-clearfix">
                    <label class="alignleft">
                        <input type="checkbox" name="mindful_media_hide_from_archive" value="1">
                        <span class="checkbox-title"><?php _e('Hide from Archive', 'mindful-media'); ?></span>
                    </label>
                </div>
            </div>
        </fieldset>
        <?php
    }

    /**
     * Enqueue Quick Edit JS
     */
    public function enqueue_quick_edit_js($hook) {
        if ($hook !== 'edit.php' || get_post_type() !== 'mindful_media') {
            return;
        }

        wp_enqueue_script(
            'mindful-media-quick-edit',
            MINDFUL_MEDIA_PLUGIN_URL . 'admin/js/quick-edit.js',
            array('jquery', 'inline-edit-post'),
            MINDFUL_MEDIA_VERSION,
            true
        );
    }
    
    /**
     * Show branding header on all MindfulMedia admin pages
     */
    public function show_branding_header() {
        $screen = get_current_screen();
        
        // Only show on MindfulMedia pages
        if (!$screen || (strpos($screen->id, 'mindful_media') === false && strpos($screen->id, 'mindful-media') === false)) {
            return;
        }
        
        // Don't show on our custom pages (they have their own headers)
        if (strpos($screen->id, 'mindful-media-getting-started') !== false || 
            strpos($screen->id, 'mindful-media-settings') !== false || 
            strpos($screen->id, 'mindful-media-import-export') !== false) {
            return;
        }
        
        echo $this->get_branding_header('MindfulMedia');
    }
    
    /**
     * Add shortcode generator button to Classic Editor
     */
    public function add_shortcode_generator_button($editor_id) {
        // Only add to main content editor
        if ($editor_id !== 'content') {
            return;
        }
        ?>
        <button type="button" class="button mindful-media-shortcode-btn" id="mindful-media-shortcode-btn" title="<?php esc_attr_e('Insert MindfulMedia', 'mindful-media'); ?>">
            <span class="dashicons dashicons-format-video" style="vertical-align: text-top; margin-right: 3px;"></span>
            <?php _e('MindfulMedia', 'mindful-media'); ?>
        </button>
        <?php
    }
    
    /**
     * Output shortcode generator modal
     */
    public function shortcode_generator_modal() {
        $screen = get_current_screen();
        
        // Only show on post/page edit screens
        if (!$screen || !in_array($screen->base, array('post', 'page'))) {
            return;
        }
        
        // Get media items for select
        $media_items = get_posts(array(
            'post_type' => 'mindful_media',
            'posts_per_page' => 100,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => 'publish'
        ));
        
        // Get playlists for select
        $playlists = get_terms(array(
            'taxonomy' => 'media_series',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        // Get teachers for select
        $teachers = get_terms(array(
            'taxonomy' => 'media_teacher',
            'hide_empty' => false,
        ));
        
        // Get categories for select
        $categories = get_terms(array(
            'taxonomy' => 'media_category',
            'hide_empty' => false,
        ));
        
        // Get types for select
        $types = get_terms(array(
            'taxonomy' => 'media_type',
            'hide_empty' => false,
        ));
        ?>
        <div id="mindful-media-shortcode-modal" class="mindful-media-modal" style="display: none;">
            <div class="mindful-media-modal-overlay"></div>
            <div class="mindful-media-modal-content" style="max-width: 600px;">
                <div class="mindful-media-modal-header">
                    <h2><?php _e('Insert MindfulMedia Shortcode', 'mindful-media'); ?></h2>
                    <button type="button" class="mindful-media-modal-close">&times;</button>
                </div>
                <div class="mindful-media-modal-body">
                    
                    <!-- Shortcode Type Tabs -->
                    <div class="mindful-media-shortcode-tabs">
                        <button type="button" class="shortcode-tab active" data-tab="embed"><?php _e('Embed Media', 'mindful-media'); ?></button>
                        <button type="button" class="shortcode-tab" data-tab="browse"><?php _e('Browse Page', 'mindful-media'); ?></button>
                        <button type="button" class="shortcode-tab" data-tab="archive"><?php _e('Archive', 'mindful-media'); ?></button>
                    </div>
                    
                    <!-- Embed Media Tab -->
                    <div class="shortcode-tab-content active" data-tab="embed">
                        <p class="description"><?php _e('Embed a single media item or playlist.', 'mindful-media'); ?></p>
                        
                        <div class="shortcode-field">
                            <label><?php _e('Media Item', 'mindful-media'); ?></label>
                            <select id="shortcode-media-id">
                                <option value=""><?php _e('-- Select Media Item --', 'mindful-media'); ?></option>
                                <?php foreach ($media_items as $item): ?>
                                    <option value="<?php echo $item->ID; ?>"><?php echo esc_html($item->post_title); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <p style="text-align: center; color: #666;">— <?php _e('or', 'mindful-media'); ?> —</p>
                        
                        <div class="shortcode-field">
                            <label><?php _e('Playlist', 'mindful-media'); ?></label>
                            <select id="shortcode-playlist">
                                <option value=""><?php _e('-- Select Playlist --', 'mindful-media'); ?></option>
                                <?php if (!empty($playlists) && !is_wp_error($playlists)): ?>
                                    <?php foreach ($playlists as $playlist): ?>
                                        <option value="<?php echo esc_attr($playlist->slug); ?>"><?php echo esc_html($playlist->name); ?> (<?php echo $playlist->count; ?> items)</option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="shortcode-field">
                            <label>
                                <input type="checkbox" id="shortcode-show-thumbnail" checked>
                                <?php _e('Show Thumbnail with Play Button', 'mindful-media'); ?>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Browse Page Tab -->
                    <div class="shortcode-tab-content" data-tab="browse">
                        <p class="description"><?php _e('Create a browse/landing page with category navigation.', 'mindful-media'); ?></p>
                        
                        <div class="shortcode-field">
                            <label><?php _e('Sections to Show', 'mindful-media'); ?></label>
                            <select id="shortcode-browse-show">
                                <option value="all"><?php _e('All Sections', 'mindful-media'); ?></option>
                                <option value="navigation"><?php _e('Navigation Only', 'mindful-media'); ?></option>
                                <option value="teachers"><?php _e('Teachers', 'mindful-media'); ?></option>
                                <option value="topics"><?php _e('Topics', 'mindful-media'); ?></option>
                                <option value="playlists"><?php _e('Playlists & Series', 'mindful-media'); ?></option>
                                <option value="types"><?php _e('Media Types', 'mindful-media'); ?></option>
                            </select>
                        </div>
                        
                        <div class="shortcode-field">
                            <label><?php _e('Columns', 'mindful-media'); ?></label>
                            <select id="shortcode-browse-columns">
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4" selected>4</option>
                                <option value="5">5</option>
                                <option value="6">6</option>
                            </select>
                        </div>
                        
                        <div class="shortcode-field">
                            <label>
                                <input type="checkbox" id="shortcode-browse-featured">
                                <?php _e('Show Featured Hero Section', 'mindful-media'); ?>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Archive Tab -->
                    <div class="shortcode-tab-content" data-tab="archive">
                        <p class="description"><?php _e('Display the full media archive with filters.', 'mindful-media'); ?></p>
                        
                        <div class="shortcode-field">
                            <label><?php _e('Items per Page', 'mindful-media'); ?></label>
                            <select id="shortcode-archive-per-page">
                                <option value="8">8</option>
                                <option value="12" selected>12</option>
                                <option value="16">16</option>
                                <option value="24">24</option>
                            </select>
                        </div>
                        
                        <div class="shortcode-field">
                            <label>
                                <input type="checkbox" id="shortcode-archive-filters" checked>
                                <?php _e('Show Filters Sidebar', 'mindful-media'); ?>
                            </label>
                        </div>
                        
                        <div class="shortcode-field">
                            <label><?php _e('Pre-filter by Category (optional)', 'mindful-media'); ?></label>
                            <select id="shortcode-archive-category">
                                <option value=""><?php _e('-- All Categories --', 'mindful-media'); ?></option>
                                <?php if (!empty($categories) && !is_wp_error($categories)): ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo esc_attr($cat->slug); ?>"><?php echo esc_html($cat->name); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="shortcode-field">
                            <label><?php _e('Pre-filter by Type (optional)', 'mindful-media'); ?></label>
                            <select id="shortcode-archive-type">
                                <option value=""><?php _e('-- All Types --', 'mindful-media'); ?></option>
                                <?php if (!empty($types) && !is_wp_error($types)): ?>
                                    <?php foreach ($types as $type): ?>
                                        <option value="<?php echo esc_attr($type->slug); ?>"><?php echo esc_html($type->name); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Generated Shortcode Preview -->
                    <div class="shortcode-preview">
                        <label><?php _e('Generated Shortcode:', 'mindful-media'); ?></label>
                        <div class="shortcode-preview-code">
                            <input type="text" id="shortcode-preview-text" readonly onclick="this.select();">
                            <button type="button" class="button" id="copy-shortcode-btn" title="<?php esc_attr_e('Copy to clipboard', 'mindful-media'); ?>">
                                <span class="dashicons dashicons-clipboard"></span>
                            </button>
                        </div>
                    </div>
                    
                </div>
                <div class="mindful-media-modal-footer">
                    <button type="button" class="button" id="cancel-shortcode"><?php _e('Cancel', 'mindful-media'); ?></button>
                    <button type="button" class="button button-primary" id="insert-shortcode"><?php _e('Insert Shortcode', 'mindful-media'); ?></button>
                </div>
            </div>
        </div>
        
        <style>
        .mindful-media-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 100100;
        }
        .mindful-media-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
        }
        .mindful-media-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
        }
        .mindful-media-modal-header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .mindful-media-modal-header h2 {
            margin: 0;
            font-size: 20px;
        }
        .mindful-media-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            line-height: 1;
            color: #666;
        }
        .mindful-media-modal-close:hover {
            color: #000;
        }
        .mindful-media-modal-body {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
        }
        .mindful-media-modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #ddd;
            text-align: right;
        }
        .mindful-media-shortcode-tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }
        .shortcode-tab {
            padding: 8px 16px;
            border: 1px solid #ddd;
            border-radius: 4px 4px 0 0;
            background: #f5f5f5;
            cursor: pointer;
            font-weight: 500;
        }
        .shortcode-tab.active {
            background: #fff;
            border-bottom-color: #fff;
            margin-bottom: -12px;
            padding-bottom: 20px;
        }
        .shortcode-tab-content {
            display: none;
        }
        .shortcode-tab-content.active {
            display: block;
        }
        .shortcode-field {
            margin-bottom: 15px;
        }
        .shortcode-field label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .shortcode-field select,
        .shortcode-field input[type="text"] {
            width: 100%;
        }
        .shortcode-field input[type="checkbox"] {
            margin-right: 5px;
        }
        .shortcode-preview {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .shortcode-preview label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .shortcode-preview-code {
            display: flex;
            gap: 5px;
        }
        .shortcode-preview-code input {
            flex: 1;
            font-family: monospace;
            background: #f9f9f9;
            padding: 10px;
        }
        .shortcode-preview-code button {
            flex-shrink: 0;
        }
        .mindful-media-shortcode-btn {
            background: #b8a064;
            border-color: #93845e;
            color: #fff;
        }
        .mindful-media-shortcode-btn:hover,
        .mindful-media-shortcode-btn:focus {
            background: #93845e;
            border-color: #93845e;
            color: #fff;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            var currentTab = 'embed';
            
            // Open modal
            $('#mindful-media-shortcode-btn').on('click', function(e) {
                e.preventDefault();
                $('#mindful-media-shortcode-modal').show();
                updateShortcodePreview();
            });
            
            // Close modal
            $('.mindful-media-modal-close, .mindful-media-modal-overlay, #cancel-shortcode').on('click', function() {
                $('#mindful-media-shortcode-modal').hide();
            });
            
            // Tab switching
            $('.shortcode-tab').on('click', function() {
                currentTab = $(this).data('tab');
                $('.shortcode-tab').removeClass('active');
                $(this).addClass('active');
                $('.shortcode-tab-content').removeClass('active');
                $('.shortcode-tab-content[data-tab="' + currentTab + '"]').addClass('active');
                updateShortcodePreview();
            });
            
            // Update preview on any change
            $('#mindful-media-shortcode-modal select, #mindful-media-shortcode-modal input').on('change', function() {
                updateShortcodePreview();
            });
            
            // Generate shortcode preview
            function updateShortcodePreview() {
                var shortcode = '';
                
                if (currentTab === 'embed') {
                    var mediaId = $('#shortcode-media-id').val();
                    var playlist = $('#shortcode-playlist').val();
                    var showThumbnail = $('#shortcode-show-thumbnail').is(':checked');
                    
                    shortcode = '[mindful_media';
                    if (mediaId) {
                        shortcode += ' id="' + mediaId + '"';
                    } else if (playlist) {
                        shortcode += ' playlist="' + playlist + '"';
                    }
                    shortcode += ' show_thumbnail="' + (showThumbnail ? 'true' : 'false') + '"';
                    shortcode += ']';
                    
                } else if (currentTab === 'browse') {
                    var show = $('#shortcode-browse-show').val();
                    var columns = $('#shortcode-browse-columns').val();
                    var featured = $('#shortcode-browse-featured').is(':checked');
                    
                    shortcode = '[mindful_media_browse';
                    if (show !== 'all') shortcode += ' show="' + show + '"';
                    if (columns !== '4') shortcode += ' columns="' + columns + '"';
                    if (featured) shortcode += ' featured="true"';
                    shortcode += ']';
                    
                } else if (currentTab === 'archive') {
                    var perPage = $('#shortcode-archive-per-page').val();
                    var showFilters = $('#shortcode-archive-filters').is(':checked');
                    var category = $('#shortcode-archive-category').val();
                    var type = $('#shortcode-archive-type').val();
                    
                    shortcode = '[mindful_media_archive';
                    if (perPage !== '12') shortcode += ' per_page="' + perPage + '"';
                    if (!showFilters) shortcode += ' show_filters="false"';
                    if (category) shortcode += ' category="' + category + '"';
                    if (type) shortcode += ' type="' + type + '"';
                    shortcode += ']';
                }
                
                $('#shortcode-preview-text').val(shortcode);
            }
            
            // Copy to clipboard
            $('#copy-shortcode-btn').on('click', function() {
                var $input = $('#shortcode-preview-text');
                $input.select();
                document.execCommand('copy');
                
                var $btn = $(this);
                $btn.find('.dashicons').removeClass('dashicons-clipboard').addClass('dashicons-yes');
                setTimeout(function() {
                    $btn.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-clipboard');
                }, 2000);
            });
            
            // Insert shortcode
            $('#insert-shortcode').on('click', function() {
                var shortcode = $('#shortcode-preview-text').val();
                
                if (!shortcode || shortcode === '[mindful_media]' || shortcode === '[mindful_media show_thumbnail="true"]') {
                    alert('<?php _e('Please select a media item or playlist first.', 'mindful-media'); ?>');
                    return;
                }
                
                // Insert into editor
                if (window.tinymce && tinymce.activeEditor && !tinymce.activeEditor.isHidden()) {
                    tinymce.activeEditor.insertContent(shortcode);
                } else {
                    // Text mode fallback
                    var $textarea = $('#content');
                    if ($textarea.length) {
                        var textarea = $textarea[0];
                        var startPos = textarea.selectionStart;
                        var endPos = textarea.selectionEnd;
                        var text = $textarea.val();
                        $textarea.val(text.substring(0, startPos) + shortcode + text.substring(endPos));
                        textarea.selectionStart = textarea.selectionEnd = startPos + shortcode.length;
                        $textarea.focus();
                    }
                }
                
                $('#mindful-media-shortcode-modal').hide();
            });
            
            // Initialize preview
            updateShortcodePreview();
        });
        </script>
        <?php
    }
}
