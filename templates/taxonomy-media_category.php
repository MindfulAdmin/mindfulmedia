<?php
/**
 * Category Archive Template - Grid Layout with Filters
 * Shows all media items in this category
 */

@get_header();

$term = get_queried_object();
$item_count = $term->count;

// Get archive URL from settings
$settings = get_option('mindful_media_settings', array());
$archive_url = !empty($settings['archive_back_url']) ? $settings['archive_back_url'] : home_url('/media');

// Try to use the referrer URL if it looks like a browse page (to maintain context)
$browse_page_url = $archive_url;
if (!empty($_SERVER['HTTP_REFERER'])) {
    $referer = $_SERVER['HTTP_REFERER'];
    if (strpos($referer, home_url()) === 0 && 
        strpos($referer, '/teacher/') === false && 
        strpos($referer, '/topic/') === false &&
        strpos($referer, '/playlist/') === false &&
        strpos($referer, '/category/') === false) {
        $browse_page_url = strtok($referer, '?');
    }
}

// Tab visibility settings
$show_home = isset($settings['archive_show_home_tab']) ? $settings['archive_show_home_tab'] === '1' : true;
$show_teachers = isset($settings['archive_show_teachers_tab']) ? $settings['archive_show_teachers_tab'] === '1' : true;
$show_topics = isset($settings['archive_show_topics_tab']) ? $settings['archive_show_topics_tab'] === '1' : true;
$show_playlists = isset($settings['archive_show_playlists_tab']) ? $settings['archive_show_playlists_tab'] === '1' : true;
$show_categories = isset($settings['archive_show_categories_tab']) ? $settings['archive_show_categories_tab'] === '1' : false;

// Filter chip settings
$show_duration = isset($settings['archive_show_duration_filter']) ? $settings['archive_show_duration_filter'] === '1' : true;
$show_year = isset($settings['archive_show_year_filter']) ? $settings['archive_show_year_filter'] === '1' : true;
$show_type = isset($settings['archive_show_type_filter']) ? $settings['archive_show_type_filter'] === '1' : true;

// Enqueue frontend assets for inline player
wp_enqueue_style('mindful-media-frontend', plugins_url('../../public/css/frontend.css', __FILE__), array(), MINDFUL_MEDIA_VERSION);
wp_enqueue_script('mindful-media-frontend', plugins_url('../../public/js/frontend.js', __FILE__), array('jquery'), MINDFUL_MEDIA_VERSION, true);
wp_localize_script('mindful-media-frontend', 'mindfulMediaAjax', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('mindful_media_ajax_nonce'),
    'categoryId' => $term->term_id
));

// Get IDs of videos in protected playlists to exclude
$protected_video_ids = array();
$protected_playlists = get_terms(array(
    'taxonomy' => 'media_series',
    'hide_empty' => false,
    'meta_query' => array(
        array(
            'key' => 'password_enabled',
            'value' => '1',
            'compare' => '='
        )
    )
));

if (!empty($protected_playlists) && !is_wp_error($protected_playlists)) {
    foreach ($protected_playlists as $playlist) {
        $cookie_name = 'mindful_media_playlist_access_' . $playlist->term_id;
        $has_access = isset($_COOKIE[$cookie_name]);
        
        if (!$has_access) {
            $playlist_videos = get_posts(array(
                'post_type' => 'mindful_media',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'media_series',
                        'field' => 'term_id',
                        'terms' => $playlist->term_id,
                        'include_children' => true
                    )
                )
            ));
            
            if (!empty($playlist_videos)) {
                $protected_video_ids = array_merge($protected_video_ids, $playlist_videos);
            }
        }
    }
    $protected_video_ids = array_unique($protected_video_ids);
}

// Get all posts for this category (excluding protected playlist videos)
$query_args = array(
    'post_type' => 'mindful_media',
    'posts_per_page' => -1,
    'tax_query' => array(
        array(
            'taxonomy' => 'media_category',
            'field' => 'term_id',
            'terms' => $term->term_id,
        )
    )
);

if (!empty($protected_video_ids)) {
    $query_args['post__not_in'] = $protected_video_ids;
}

$all_posts = get_posts($query_args);
$item_count = count($all_posts); // Update count after exclusions

// Collect filter metadata
$category_teachers = array();
$category_topics = array();
$category_durations = array();
$category_years = array();
$has_audio = false;
$has_video = false;

foreach ($all_posts as $post) {
    // Teachers
    $teachers = get_the_terms($post->ID, 'media_teacher');
    if ($teachers && !is_wp_error($teachers)) {
        foreach ($teachers as $teacher) {
            $category_teachers[$teacher->term_id] = $teacher;
        }
    }
    
    // Topics
    $topics = get_the_terms($post->ID, 'media_topic');
    if ($topics && !is_wp_error($topics)) {
        foreach ($topics as $topic) {
            $category_topics[$topic->term_id] = $topic;
        }
    }
    
    // Durations
    $durations = get_the_terms($post->ID, 'media_duration');
    if ($durations && !is_wp_error($durations)) {
        foreach ($durations as $dur) {
            $category_durations[$dur->term_id] = $dur;
        }
    }
    
    // Years
    $years = get_the_terms($post->ID, 'media_year');
    if ($years && !is_wp_error($years)) {
        foreach ($years as $year) {
            $category_years[$year->term_id] = $year;
        }
    }
    
    // Media types
    $types = get_the_terms($post->ID, 'media_type');
    if ($types && !is_wp_error($types)) {
        foreach ($types as $type) {
            if (strtolower($type->name) === 'audio') $has_audio = true;
            if (strtolower($type->name) === 'video') $has_video = true;
        }
    }
}
?>

<style>
/* Light Theme Variables */
:root {
    --mm-bg-primary: #ffffff;
    --mm-bg-secondary: #f9f9f9;
    --mm-text-primary: #0f0f0f;
    --mm-text-secondary: #606060;
    --mm-text-muted: #909090;
    --mm-border: #e5e5e5;
    --mm-accent: #065fd4;
}

body.tax-media_category {
    margin: 0;
    padding: 0;
    background: var(--mm-bg-secondary) !important;
}

body.tax-media_category #primary,
body.tax-media_category .site-main {
    margin: 0;
    padding: 0;
    max-width: none;
}

/* Main Container */
.mindful-media-category-page {
    background: var(--mm-bg-secondary);
    min-height: 100vh;
    padding: 0;
    margin: 0;
    width: 100%;
    font-family: 'Roboto', -apple-system, BlinkMacSystemFont, sans-serif;
}

/* Browse-Style Navigation Bar */
.mindful-media-category-nav {
    background: var(--mm-bg-primary);
    border-bottom: 1px solid var(--mm-border);
    padding: 0 40px;
    position: sticky;
    top: 0;
    z-index: 100;
}

.mindful-media-category-nav-inner {
    display: flex;
    gap: 8px;
    padding: 12px 0;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
}

.mindful-media-category-nav-inner::-webkit-scrollbar {
    display: none;
}

.mindful-media-category-nav-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: var(--mm-bg-secondary);
    border: none;
    border-radius: 8px;
    color: var(--mm-text-primary);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.2s ease;
}

.mindful-media-category-nav-item:hover {
    background: var(--mm-border);
}

.mindful-media-category-nav-item.active {
    background: var(--mm-text-primary);
    color: white;
}

.mindful-media-category-nav-item svg {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

/* Category Header Section */
.mindful-media-category-header {
    background: var(--mm-bg-primary);
    border-bottom: 1px solid var(--mm-border);
    padding: 24px 40px;
}

.mindful-media-category-header-inner {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    gap: 16px;
}

.mindful-media-category-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    background: linear-gradient(135deg, #065fd4 0%, #0a85ed 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.mindful-media-category-icon svg {
    width: 28px;
    height: 28px;
    stroke: white;
}

.mindful-media-category-info h1 {
    margin: 0 0 4px;
    font-size: 24px;
    font-weight: 600;
    color: #0f0f0f !important;
}

.mindful-media-category-meta {
    font-size: 14px;
    color: var(--mm-text-secondary);
}

/* Filter Chips Bar */
.mindful-media-category-filters {
    background: var(--mm-bg-primary);
    border-bottom: 1px solid var(--mm-border);
    padding: 12px 40px;
}

.mindful-media-category-filters-inner {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.mm-filter-chips-left {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    flex: 1;
}

.mm-filter-search {
    flex-shrink: 0;
}

.mm-filter-search .mindful-media-search-input {
    width: 180px;
    padding: 10px 32px 10px 40px;
    border: 1px solid var(--mm-border);
    border-radius: 20px;
    font-size: 13px;
    color: var(--mm-text-primary);
    background: var(--mm-bg-primary);
    outline: none;
    transition: all 0.2s ease;
}

.mm-filter-search .mindful-media-search-input:hover {
    border-color: var(--mm-text-secondary);
}

.mm-filter-search .mindful-media-search-input:focus {
    border-color: var(--mm-accent);
    box-shadow: 0 0 0 2px rgba(6, 95, 212, 0.15);
}

.mm-category-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: var(--mm-bg-secondary);
    border: 1px solid var(--mm-border);
    border-radius: 18px;
    color: var(--mm-text-primary);
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.mm-category-chip:hover {
    background: var(--mm-border);
}

.mm-category-chip.active {
    background: var(--mm-text-primary);
    color: white;
    border-color: var(--mm-text-primary);
}

.mm-category-chip svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

/* Search Input */
.mm-taxonomy-search {
    position: relative;
    display: flex;
    align-items: center;
    margin-right: 12px;
}

.mm-taxonomy-search .mm-search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 16px;
    height: 16px;
    color: var(--mm-text-secondary);
    pointer-events: none;
}

.mm-taxonomy-search .mindful-media-search-input {
    width: 200px;
    padding: 10px 32px 10px 40px;
    border: 1px solid var(--mm-border);
    border-radius: 20px;
    font-size: 13px;
    color: var(--mm-text-primary);
    background: var(--mm-bg-primary);
    outline: none;
    transition: all 0.2s ease;
}

.mm-taxonomy-search .mindful-media-search-input:hover {
    border-color: var(--mm-text-secondary);
}

.mm-taxonomy-search .mindful-media-search-input:focus {
    border-color: var(--mm-accent);
    box-shadow: 0 0 0 2px rgba(6, 95, 212, 0.15);
}

.mm-taxonomy-search .mm-search-clear {
    position: absolute;
    right: 8px;
    width: 18px;
    height: 18px;
    padding: 0;
    border: none;
    border-radius: 50%;
    background: var(--mm-bg-secondary);
    color: var(--mm-text-secondary);
    font-size: 12px;
    cursor: pointer;
    display: none;
    align-items: center;
    justify-content: center;
}

.mm-taxonomy-search.has-value .mm-search-clear {
    display: flex;
}

@media (max-width: 768px) {
    .mm-taxonomy-search {
        width: 100%;
        margin-right: 0;
        margin-bottom: 8px;
    }
    .mm-taxonomy-search .mindful-media-search-input {
        width: 100%;
    }
}

/* Content Container */
.mindful-media-category-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 32px 40px 80px;
}

/* Grid Layout */
.mindful-media-category-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 20px;
}

@media (max-width: 1200px) {
    .mindful-media-category-grid { grid-template-columns: repeat(4, 1fr); }
}

@media (max-width: 992px) {
    .mindful-media-category-grid { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 768px) {
    .mindful-media-category-grid { grid-template-columns: repeat(2, 1fr); }
    .mindful-media-category-nav { padding: 0 20px; }
    .mindful-media-category-filters { padding: 12px 20px; }
    .mindful-media-category-filters-inner { flex-wrap: wrap; }
    .mm-filter-chips-left { flex: 1 1 100%; order: 2; }
    .mm-filter-search { flex: 1 1 100%; order: 1; margin-bottom: 8px; }
    .mm-filter-search .mindful-media-search-input { width: 100%; }
    .mindful-media-category-header { padding: 20px; flex-direction: column; text-align: center; }
    .mindful-media-category-content { padding: 20px; }
}

@media (max-width: 480px) {
    .mindful-media-category-grid { grid-template-columns: 1fr; }
}

/* Card */
.mindful-media-category-card {
    background: transparent;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.mindful-media-category-card:hover {
    transform: translateY(-4px);
}

.mindful-media-category-card-thumb {
    width: 100%;
    aspect-ratio: 16 / 9;
    overflow: hidden;
    background: var(--mm-border);
    position: relative;
    border-radius: 12px;
    margin-bottom: 12px;
}

.mindful-media-category-card-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.mindful-media-category-card:hover .mindful-media-category-card-thumb img {
    transform: scale(1.05);
}

/* Play Overlay */
.mindful-media-category-card-play {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 56px;
    height: 56px;
    background: rgba(0, 0, 0, 0.8);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.mindful-media-category-card:hover .mindful-media-category-card-play {
    opacity: 1;
}

.mindful-media-category-card-play svg {
    width: 24px;
    height: 24px;
    fill: white;
    margin-left: 3px;
}

/* Badges */
.mindful-media-category-card-duration {
    position: absolute;
    bottom: 8px;
    right: 8px;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.mindful-media-category-card-type {
    position: absolute;
    bottom: 8px;
    left: 8px;
    background: rgba(0, 0, 0, 0.75);
    color: white;
    padding: 4px 6px;
    border-radius: 4px;
    display: flex;
    align-items: center;
}

.mindful-media-category-card-type svg {
    width: 14px;
    height: 14px;
    fill: white;
}

/* Card Content */
.mindful-media-category-card-title {
    font-size: 14px;
    font-weight: 500;
    color: #0f0f0f !important;
    margin: 0 0 4px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.mindful-media-category-card-teacher {
    font-size: 12px;
    color: var(--mm-text-secondary);
}
</style>

<div class="mindful-media-category-page">
    <!-- Browse-Style Navigation -->
    <nav class="mindful-media-category-nav">
        <div class="mindful-media-category-nav-inner">
            <?php if ($show_home): ?>
            <a href="<?php echo esc_url($browse_page_url); ?>#mm-home" class="mindful-media-category-nav-item" data-tab="all">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <?php _e('Home', 'mindful-media'); ?>
            </a>
            <?php endif; ?>
            
            <?php if ($show_teachers): ?>
            <a href="<?php echo esc_url($browse_page_url); ?>#mm-teachers" class="mindful-media-category-nav-item" data-tab="teachers">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <?php _e('Teachers', 'mindful-media'); ?>
            </a>
            <?php endif; ?>
            
            <?php if ($show_topics): ?>
            <a href="<?php echo esc_url($browse_page_url); ?>#mm-topics" class="mindful-media-category-nav-item" data-tab="topics">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                <?php _e('Topics', 'mindful-media'); ?>
            </a>
            <?php endif; ?>
            
            <?php if ($show_playlists): ?>
            <a href="<?php echo esc_url($browse_page_url); ?>#mm-playlists" class="mindful-media-category-nav-item" data-tab="playlists">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                <?php _e('Playlists', 'mindful-media'); ?>
            </a>
            <?php endif; ?>
            
            <?php if ($show_categories): ?>
            <span class="mindful-media-category-nav-item active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <?php echo esc_html($term->name); ?>
            </span>
            <?php endif; ?>
        </div>
    </nav>
    
    <!-- Category Header Section -->
    <header class="mindful-media-category-header">
        <div class="mindful-media-category-header-inner">
            <div class="mindful-media-category-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            </div>
            <div class="mindful-media-category-info">
                <h1><?php echo esc_html($term->name); ?></h1>
                <span class="mindful-media-category-meta"><?php echo $item_count; ?> <?php echo $item_count === 1 ? 'item' : 'items'; ?></span>
            </div>
        </div>
    </header>
    
    <!-- Filter Chips -->
    <div class="mindful-media-category-filters">
        <div class="mindful-media-category-filters-inner">
            <div class="mm-filter-chips-left">
                <!-- All chip -->
                <button type="button" class="mm-category-chip active" data-filter-type="all" data-filter-value="">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <?php _e('All', 'mindful-media'); ?>
            </button>
            
            <?php if ($show_type && ($has_video || $has_audio)): ?>
                <?php if ($has_video): ?>
                <button type="button" class="mm-category-chip" data-filter-type="media_type" data-filter-value="video">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>
                    <?php _e('Video', 'mindful-media'); ?>
                </button>
                <?php endif; ?>
                <?php if ($has_audio): ?>
                <button type="button" class="mm-category-chip" data-filter-type="media_type" data-filter-value="audio">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>
                    <?php _e('Audio', 'mindful-media'); ?>
                </button>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php 
            // Teacher chips
            if (!empty($category_teachers)):
                $teacher_icon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
                foreach ($category_teachers as $teacher): 
            ?>
                <button type="button" class="mm-category-chip" data-filter-type="media_teacher" data-filter-value="<?php echo esc_attr($teacher->slug); ?>">
                    <?php echo $teacher_icon; ?>
                    <span><?php echo esc_html($teacher->name); ?></span>
                </button>
            <?php 
                endforeach;
            endif; 
            ?>
            
            <?php 
            // Topic chips
            if (!empty($category_topics)):
                $topic_icon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>';
                foreach ($category_topics as $topic): 
            ?>
                <button type="button" class="mm-category-chip" data-filter-type="media_topic" data-filter-value="<?php echo esc_attr($topic->slug); ?>">
                    <?php echo $topic_icon; ?>
                    <span><?php echo esc_html($topic->name); ?></span>
                </button>
            <?php 
                endforeach;
            endif; 
            ?>
            
            <?php 
            // Duration chips
            if ($show_duration && !empty($category_durations)):
                $duration_icon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
                foreach ($category_durations as $duration): 
            ?>
                <button type="button" class="mm-category-chip" data-filter-type="media_duration" data-filter-value="<?php echo esc_attr($duration->slug); ?>">
                    <?php echo $duration_icon; ?>
                    <span><?php echo esc_html($duration->name); ?></span>
                </button>
            <?php 
                endforeach;
            endif; 
            ?>
            
            <?php 
            // Year chips
            if ($show_year && !empty($category_years)):
                $year_icon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
                foreach ($category_years as $year): 
            ?>
                <button type="button" class="mm-category-chip" data-filter-type="media_year" data-filter-value="<?php echo esc_attr($year->slug); ?>">
                    <?php echo $year_icon; ?>
                    <span><?php echo esc_html($year->name); ?></span>
                </button>
            <?php 
                endforeach;
            endif; 
            ?>
            </div>
            
            <!-- Search on the right -->
            <div class="mm-search-container mm-filter-search">
                <svg class="mm-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" class="mindful-media-search-input mm-taxonomy-search-input" placeholder="<?php esc_attr_e('Search...', 'mindful-media'); ?>" />
                <button type="button" class="mm-search-clear" aria-label="<?php esc_attr_e('Clear search', 'mindful-media'); ?>">&times;</button>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="mindful-media-category-content" id="category-content">
        <div class="mindful-media-category-grid">
            <?php foreach ($all_posts as $post): 
                setup_postdata($post);
                $post_id = $post->ID;
                $thumbnail_url = get_the_post_thumbnail_url($post_id, 'medium_large');
                if (!$thumbnail_url) {
                    $thumbnail_url = plugins_url('../../assets/default-thumbnail.jpg', __FILE__);
                }
                
                // Duration
                $duration_terms = get_the_terms($post_id, 'media_duration');
                $duration_slug = ($duration_terms && !is_wp_error($duration_terms)) ? $duration_terms[0]->slug : '';
                $duration_hours = get_post_meta($post_id, '_mindful_media_duration_hours', true);
                $duration_minutes = get_post_meta($post_id, '_mindful_media_duration_minutes', true);
                $duration_badge = '';
                if ($duration_hours || $duration_minutes) {
                    if ($duration_hours) {
                        $duration_badge = $duration_hours . ':' . str_pad($duration_minutes ?: '00', 2, '0', STR_PAD_LEFT);
                    } else {
                        $duration_badge = $duration_minutes . ':00';
                    }
                }
                
                // Year
                $year_terms = get_the_terms($post_id, 'media_year');
                $year_slug = ($year_terms && !is_wp_error($year_terms)) ? $year_terms[0]->slug : '';
                
                // Media type
                $media_types = get_the_terms($post_id, 'media_type');
                $type_slug = '';
                $is_video = false;
                $is_audio = false;
                if ($media_types && !is_wp_error($media_types)) {
                    foreach ($media_types as $type) {
                        if (stripos($type->name, 'video') !== false) { $is_video = true; $type_slug = 'video'; }
                        if (stripos($type->name, 'audio') !== false) { $is_audio = true; $type_slug = 'audio'; }
                    }
                }
                
                // Teacher
                $teachers = get_the_terms($post_id, 'media_teacher');
                $teacher_name = ($teachers && !is_wp_error($teachers)) ? $teachers[0]->name : '';
                $teacher_slug = ($teachers && !is_wp_error($teachers)) ? $teachers[0]->slug : '';
                
                // Topic
                $topics = get_the_terms($post_id, 'media_topic');
                $topic_slug = ($topics && !is_wp_error($topics)) ? $topics[0]->slug : '';
            ?>
                <article class="mindful-media-category-card mindful-media-card" data-post-id="<?php echo esc_attr($post_id); ?>" data-teacher="<?php echo esc_attr($teacher_slug); ?>" data-topic="<?php echo esc_attr($topic_slug); ?>" data-type="<?php echo esc_attr($type_slug); ?>" data-duration="<?php echo esc_attr($duration_slug); ?>" data-year="<?php echo esc_attr($year_slug); ?>">
                    <div class="mindful-media-category-card-thumb">
                        <button type="button" class="mindful-media-thumb-trigger" data-post-id="<?php echo esc_attr($post_id); ?>" data-title="<?php echo esc_attr($post->post_title); ?>" style="background: none; border: none; padding: 0; width: 100%; height: 100%; cursor: pointer;">
                            <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($post->post_title); ?>" loading="lazy">
                            
                            <?php if ($duration_badge): ?>
                            <span class="mindful-media-category-card-duration"><?php echo esc_html($duration_badge); ?></span>
                            <?php endif; ?>
                            
                            <?php if ($is_video): ?>
                            <span class="mindful-media-category-card-type">
                                <svg viewBox="0 0 24 24"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>
                            </span>
                            <?php elseif ($is_audio): ?>
                            <span class="mindful-media-category-card-type">
                                <svg viewBox="0 0 24 24"><path d="M12 3v9.28c-.47-.17-.97-.28-1.5-.28C8.01 12 6 14.01 6 16.5S8.01 21 10.5 21c2.31 0 4.2-1.75 4.45-4H15V6h4V3h-7z"/></svg>
                            </span>
                            <?php endif; ?>
                            
                            <div class="mindful-media-category-card-play">
                                <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            </div>
                        </button>
                    </div>
                    <h3 class="mindful-media-category-card-title"><?php echo esc_html($post->post_title); ?></h3>
                    <?php if ($teacher_name): ?>
                    <span class="mindful-media-category-card-teacher"><?php echo esc_html($teacher_name); ?></span>
                    <?php endif; ?>
                    <?php
                    // Playlist badge
                    $playlists = get_the_terms($post_id, 'media_series');
                    if ($playlists && !is_wp_error($playlists)) {
                        // Find parent playlist
                        $playlist_info = null;
                        foreach ($playlists as $playlist) {
                            if ($playlist->parent == 0) {
                                $playlist_info = $playlist;
                                break;
                            }
                        }
                        // If only child, get parent
                        if (!$playlist_info && !empty($playlists)) {
                            $first = $playlists[0];
                            if ($first->parent != 0) {
                                $parent = get_term($first->parent, 'media_series');
                                if ($parent && !is_wp_error($parent)) {
                                    $playlist_info = $parent;
                                }
                            }
                        }
                        if ($playlist_info) {
                            ?>
                            <div class="mindful-media-card-playlist-badge">
                                <a href="<?php echo esc_url(get_term_link($playlist_info)); ?>" class="mindful-media-playlist-link">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h10v2H4zm14 0v6l5-3-5-3z"/></svg>
                                    <span><?php echo esc_html($playlist_info->name); ?></span>
                                </a>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </article>
            <?php endforeach; wp_reset_postdata(); ?>
        </div>
    </div>
</div>

<script>
// Category page filter and search functionality
jQuery(document).ready(function($) {
    var $chips = $('.mm-category-chip');
    var $cards = $('.mindful-media-category-card');
    
    // Store current filter state
    var currentFilterType = 'all';
    var currentFilterValue = '';
    var currentSearchTerm = '';
    
    // Chip click handler
    $chips.on('click', function() {
        var $chip = $(this);
        currentFilterType = $chip.data('filter-type');
        currentFilterValue = $chip.data('filter-value');
        
        // Update active state
        $chips.removeClass('active');
        $chip.addClass('active');
        
        // Apply combined filtering
        applyFilters();
    });
    
    // Search input handler
    $(document).on('keyup', '.mm-taxonomy-search-input', function() {
        var $input = $(this);
        currentSearchTerm = $input.val().trim().toLowerCase();
        
        // Update clear button visibility
        $input.closest('.mm-search-container').toggleClass('has-value', currentSearchTerm.length > 0);
        
        // Debounce search
        clearTimeout(window.categorySearchTimeout);
        window.categorySearchTimeout = setTimeout(function() {
            applyFilters();
        }, 200);
    });
    
    // Search clear button
    $(document).on('click', '.mm-taxonomy-search .mm-search-clear', function(e) {
        e.preventDefault();
        var $container = $(this).closest('.mm-search-container');
        var $input = $container.find('input');
        currentSearchTerm = '';
        $input.val('');
        $container.removeClass('has-value');
        applyFilters();
        $input.focus();
    });
    
    function applyFilters() {
        var visibleCount = 0;
        
        $cards.each(function() {
            var $card = $(this);
            var show = true;
            
            // Apply chip filter
            if (currentFilterType !== 'all' && currentFilterValue) {
                var cardValue = '';
                
                if (currentFilterType === 'media_type') {
                    cardValue = $card.data('type');
                } else if (currentFilterType === 'media_teacher') {
                    cardValue = $card.data('teacher');
                } else if (currentFilterType === 'media_topic') {
                    cardValue = $card.data('topic');
                } else if (currentFilterType === 'media_duration') {
                    cardValue = $card.data('duration');
                } else if (currentFilterType === 'media_year') {
                    cardValue = $card.data('year');
                }
                
                show = cardValue === currentFilterValue;
            }
            
            // Apply search filter (if passes chip filter)
            if (show && currentSearchTerm) {
                var cardTitle = $card.find('.mindful-media-category-card-title').text().toLowerCase();
                var dataTitle = ($card.find('.mindful-media-thumb-trigger').data('title') || '').toLowerCase();
                show = cardTitle.indexOf(currentSearchTerm) !== -1 || dataTitle.indexOf(currentSearchTerm) !== -1;
            }
            
            if (show) {
                $card.show();
                visibleCount++;
            } else {
                $card.hide();
            }
        });
        
        // Show/hide no results message
        var $noResults = $('#category-no-results');
        if (visibleCount === 0) {
            if (!$noResults.length) {
                var noResultsHtml = '<div id="category-no-results" style="text-align: center; padding: 60px 20px; color: var(--mm-text-secondary); grid-column: 1 / -1;">' +
                    '<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity: 0.5; margin-bottom: 16px;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>' +
                    '<h3 style="margin: 0 0 8px; color: var(--mm-text-primary);">No results found</h3>' +
                    '<p style="margin: 0;">Try a different search term or filter</p>' +
                    '</div>';
                $('.mindful-media-category-grid').append(noResultsHtml);
            } else {
                $noResults.show();
            }
        } else {
            $noResults.hide();
        }
    }
});
</script>

<?php
// Output modal player container - needed for videos to play
$shortcodes = new MindfulMedia_Shortcodes();
echo $shortcodes->get_modal_player_html_public();
?>

<?php get_footer(); ?>
