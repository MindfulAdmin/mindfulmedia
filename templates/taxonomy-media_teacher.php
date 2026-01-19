<?php
/**
 * Teacher Archive Template - Browse-Style Layout
 * Consistent with browse page UX - same header, navigation, filter chips
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
    // If referrer is from same site and not already a teacher/topic/playlist page, use it
    if (strpos($referer, home_url()) === 0 && 
        strpos($referer, '/teacher/') === false && 
        strpos($referer, '/topic/') === false &&
        strpos($referer, '/playlist/') === false) {
        // Strip any existing query parameters from the referer
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
$show_filter_counts = isset($settings['archive_show_filter_counts']) ? $settings['archive_show_filter_counts'] === '1' : true;

// Enqueue frontend assets for inline player
wp_enqueue_style('mindful-media-frontend', plugins_url('../../public/css/frontend.css', __FILE__), array(), MINDFUL_MEDIA_VERSION);
wp_enqueue_script('mindful-media-frontend', plugins_url('../../public/js/frontend.js', __FILE__), array('jquery'), MINDFUL_MEDIA_VERSION, true);
wp_localize_script('mindful-media-frontend', 'mindfulMediaAjax', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('mindful_media_ajax_nonce'),
    'teacherId' => $term->term_id
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
        
        // If user doesn't have access, get all videos in this playlist
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

// Get all posts for this teacher (excluding protected playlist videos)
$query_args = array(
    'post_type' => 'mindful_media',
    'posts_per_page' => -1,
    'tax_query' => array(
        array(
            'taxonomy' => 'media_teacher',
            'field' => 'term_id',
            'terms' => $term->term_id,
        )
    )
);

// Exclude protected playlist videos
if (!empty($protected_video_ids)) {
    $query_args['post__not_in'] = $protected_video_ids;
}

$all_posts = get_posts($query_args);

// Organize posts by topic
$posts_by_topic = array();
$uncategorized_posts = array();

foreach ($all_posts as $post) {
    $topics = get_the_terms($post->ID, 'media_topic');
    if ($topics && !is_wp_error($topics)) {
        foreach ($topics as $topic) {
            if (!isset($posts_by_topic[$topic->term_id])) {
                $posts_by_topic[$topic->term_id] = array(
                    'term' => $topic,
                    'posts' => array()
                );
            }
            $posts_by_topic[$topic->term_id]['posts'][] = $post;
        }
    } else {
        $uncategorized_posts[] = $post;
    }
}

// Get teacher featured image
$teacher_thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
$teacher_image_url = $teacher_thumbnail_id ? wp_get_attachment_image_url($teacher_thumbnail_id, 'medium') : '';

// Get topics for this teacher (for filter chips)
$teacher_topics = array();
foreach ($posts_by_topic as $topic_data) {
    $teacher_topics[] = $topic_data['term'];
}

// Get duration terms used by this teacher's videos
$teacher_durations = array();
$teacher_years = array();
$has_audio = false;
$has_video = false;

foreach ($all_posts as $post) {
    // Durations
    $durations = get_the_terms($post->ID, 'media_duration');
    if ($durations && !is_wp_error($durations)) {
        foreach ($durations as $dur) {
            $teacher_durations[$dur->term_id] = $dur;
        }
    }
    // Years
    $years = get_the_terms($post->ID, 'media_year');
    if ($years && !is_wp_error($years)) {
        foreach ($years as $year) {
            $teacher_years[$year->term_id] = $year;
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

body.tax-media_teacher {
    margin: 0;
    padding: 0;
    background: var(--mm-bg-secondary) !important;
}

body.tax-media_teacher #primary,
body.tax-media_teacher .site-main {
    margin: 0;
    padding: 0;
    max-width: none;
}

/* Main Container */
.mindful-media-teacher-page {
    background: var(--mm-bg-secondary);
    min-height: 100vh;
    padding: 0;
    margin: 0;
    width: 100%;
    font-family: 'Roboto', -apple-system, BlinkMacSystemFont, sans-serif;
}

/* Browse-Style Navigation Bar */
.mindful-media-teacher-nav {
    background: var(--mm-bg-primary);
    border-bottom: 1px solid var(--mm-border);
    padding: 0 40px;
    position: sticky;
    top: 0;
    z-index: 100;
}

.mindful-media-teacher-nav-inner {
    display: flex;
    gap: 8px;
    padding: 12px 0;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
}

.mindful-media-teacher-nav-inner::-webkit-scrollbar {
    display: none;
}


.mindful-media-teacher-nav-item {
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

.mindful-media-teacher-nav-item:hover {
    background: var(--mm-border);
}

.mindful-media-teacher-nav-item.active {
    background: var(--mm-text-primary);
    color: white;
}

.mindful-media-teacher-nav-item svg {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

/* Teacher Header Section */
.mindful-media-teacher-hero {
    background: var(--mm-bg-primary);
    border-bottom: 1px solid var(--mm-border);
    padding: 24px 40px;
}

.mindful-media-teacher-hero-inner {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    gap: 20px;
}

.mindful-media-teacher-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    border: 2px solid var(--mm-border);
    background: var(--mm-bg-secondary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: 600;
    color: var(--mm-text-secondary);
}

.mindful-media-teacher-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.mindful-media-teacher-hero-info h1 {
    margin: 0 0 4px;
    font-size: 28px;
    font-weight: 600;
    color: #0f0f0f !important;
}

.mindful-media-teacher-hero-meta {
    font-size: 14px;
    color: var(--mm-text-secondary);
}

/* Filter Chips Bar */
.mindful-media-teacher-filters {
    background: var(--mm-bg-primary);
    border-bottom: 1px solid var(--mm-border);
    padding: 12px 40px;
}

.mindful-media-teacher-filters-inner {
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

.mm-teacher-chip {
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

.mm-teacher-chip:hover {
    background: var(--mm-border);
}

.mm-teacher-chip.active {
    background: var(--mm-text-primary);
    color: white;
    border-color: var(--mm-text-primary);
}

.mm-teacher-chip svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

.mm-teacher-chip-count {
    background: rgba(0,0,0,0.1);
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 11px;
    margin-left: 4px;
}

.mm-teacher-chip.active .mm-teacher-chip-count {
    background: rgba(255,255,255,0.2);
}

/* Search Input in Taxonomy Pages */
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

.mm-taxonomy-search .mindful-media-search-input::placeholder {
    color: var(--mm-text-muted);
}

.mm-taxonomy-search .mm-search-clear {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    padding: 0;
    border: none;
    border-radius: 50%;
    background: var(--mm-bg-secondary);
    color: var(--mm-text-secondary);
    font-size: 12px;
    line-height: 1;
    cursor: pointer;
    display: none;
    align-items: center;
    justify-content: center;
}

.mm-taxonomy-search .mm-search-clear:hover {
    background: var(--mm-text-secondary);
    color: white;
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
.mindful-media-teacher-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 32px 40px 80px;
}

/* Section */
.mindful-media-teacher-section {
    margin-bottom: 48px;
}

.mindful-media-teacher-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}

.mindful-media-teacher-section-title {
    font-size: 20px;
    font-weight: 600;
    color: #0f0f0f !important;
    margin: 0;
}

.mindful-media-teacher-section-title a {
    color: #0f0f0f !important;
    text-decoration: none;
}

.mindful-media-teacher-section-title a:hover {
    color: var(--mm-accent) !important;
}

.mindful-media-teacher-section-count {
    font-size: 14px;
    color: var(--mm-text-muted);
}

/* Grid Layout */
.mindful-media-teacher-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 20px;
}

@media (max-width: 1200px) {
    .mindful-media-teacher-grid { grid-template-columns: repeat(4, 1fr); }
}

@media (max-width: 992px) {
    .mindful-media-teacher-grid { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 768px) {
    .mindful-media-teacher-grid { grid-template-columns: repeat(2, 1fr); }
    .mindful-media-teacher-nav { padding: 0 20px; }
    .mindful-media-teacher-hero { padding: 20px; }
    .mindful-media-teacher-hero-inner { flex-direction: column; text-align: center; }
    .mindful-media-teacher-filters { padding: 12px 20px; }
    .mindful-media-teacher-filters-inner { flex-wrap: wrap; }
    .mm-filter-chips-left { flex: 1 1 100%; order: 2; }
    .mm-filter-search { flex: 1 1 100%; order: 1; margin-bottom: 8px; }
    .mm-filter-search .mindful-media-search-input { width: 100%; }
    .mindful-media-teacher-content { padding: 20px; }
}

@media (max-width: 480px) {
    .mindful-media-teacher-grid { grid-template-columns: 1fr; }
}

/* Card */
.mindful-media-teacher-card {
    background: transparent;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.mindful-media-teacher-card:hover {
    transform: translateY(-4px);
}

.mindful-media-teacher-card-thumb {
    width: 100%;
    aspect-ratio: 16 / 9;
    overflow: hidden;
    background: var(--mm-border);
    position: relative;
    border-radius: 12px;
    margin-bottom: 12px;
}

.mindful-media-teacher-card-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.mindful-media-teacher-card:hover .mindful-media-teacher-card-thumb img {
    transform: scale(1.05);
}

/* Play Overlay */
.mindful-media-teacher-card-play {
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

.mindful-media-teacher-card:hover .mindful-media-teacher-card-play {
    opacity: 1;
}

.mindful-media-teacher-card-play svg {
    width: 24px;
    height: 24px;
    fill: white;
    margin-left: 3px;
}

/* Badges */
.mindful-media-teacher-card-duration {
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

.mindful-media-teacher-card-type {
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

.mindful-media-teacher-card-type svg {
    width: 14px;
    height: 14px;
    fill: white;
}

/* Card Content */
.mindful-media-teacher-card-title {
    font-size: 14px;
    font-weight: 500;
    color: #0f0f0f !important;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* No results message */
.mindful-media-teacher-no-results {
    text-align: center;
    padding: 60px 20px;
    color: var(--mm-text-secondary);
}

.mindful-media-teacher-no-results svg {
    width: 64px;
    height: 64px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.mindful-media-teacher-no-results h3 {
    margin: 0 0 8px;
    color: var(--mm-text-primary);
}

/* Loading state */
.mindful-media-teacher-content.loading {
    opacity: 0.5;
    pointer-events: none;
}
</style>

<div class="mindful-media-teacher-page">
    <!-- Browse-Style Navigation -->
    <nav class="mindful-media-teacher-nav">
        <div class="mindful-media-teacher-nav-inner">
            <?php if ($show_home): ?>
            <a href="<?php echo esc_url($browse_page_url); ?>#mm-home" class="mindful-media-teacher-nav-item" data-tab="all">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <?php _e('Home', 'mindful-media'); ?>
            </a>
            <?php endif; ?>
            
            <?php if ($show_teachers): ?>
            <span class="mindful-media-teacher-nav-item active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <?php echo esc_html($term->name); ?>
            </span>
            <?php endif; ?>
            
            <?php if ($show_topics): ?>
            <a href="<?php echo esc_url($browse_page_url); ?>#mm-topics" class="mindful-media-teacher-nav-item" data-tab="topics">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                <?php _e('Topics', 'mindful-media'); ?>
            </a>
            <?php endif; ?>
            
            <?php if ($show_playlists): ?>
            <a href="<?php echo esc_url($browse_page_url); ?>#mm-playlists" class="mindful-media-teacher-nav-item" data-tab="playlists">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                <?php _e('Playlists', 'mindful-media'); ?>
            </a>
            <?php endif; ?>
            
            <?php if ($show_categories): ?>
            <a href="<?php echo esc_url($browse_page_url); ?>#mm-categories" class="mindful-media-teacher-nav-item" data-tab="categories">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <?php _e('Categories', 'mindful-media'); ?>
            </a>
            <?php endif; ?>
        </div>
    </nav>
    
    <!-- Teacher Hero Section -->
    <div class="mindful-media-teacher-hero">
        <div class="mindful-media-teacher-hero-inner">
            <div class="mindful-media-teacher-avatar">
                <?php if ($teacher_image_url): ?>
                    <img src="<?php echo esc_url($teacher_image_url); ?>" alt="<?php echo esc_attr($term->name); ?>">
                <?php else: ?>
                    <?php echo esc_html(strtoupper(substr($term->name, 0, 1))); ?>
                <?php endif; ?>
            </div>
            <div class="mindful-media-teacher-hero-info">
                <h1><?php echo esc_html($term->name); ?></h1>
                <span class="mindful-media-teacher-hero-meta"><?php echo $item_count; ?> <?php echo $item_count === 1 ? 'video' : 'videos'; ?></span>
            </div>
        </div>
    </div>
    
    <!-- Filter Chips -->
    <div class="mindful-media-teacher-filters">
        <div class="mindful-media-teacher-filters-inner">
            <div class="mm-filter-chips-left">
                <!-- All chip -->
                <button type="button" class="mm-teacher-chip active" data-filter-type="all" data-filter-value="">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <?php _e('All', 'mindful-media'); ?>
            </button>
            
            <?php if ($show_type && ($has_video || $has_audio)): ?>
                <?php if ($has_video): ?>
                <button type="button" class="mm-teacher-chip" data-filter-type="media_type" data-filter-value="video">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>
                    <?php _e('Video', 'mindful-media'); ?>
                </button>
                <?php endif; ?>
                <?php if ($has_audio): ?>
                <button type="button" class="mm-teacher-chip" data-filter-type="media_type" data-filter-value="audio">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>
                    <?php _e('Audio', 'mindful-media'); ?>
                </button>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php 
            // Topic chips
            if (!empty($teacher_topics)):
                $topic_icon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>';
                foreach ($teacher_topics as $topic): 
            ?>
                <button type="button" class="mm-teacher-chip" data-filter-type="media_topic" data-filter-value="<?php echo esc_attr($topic->slug); ?>">
                    <?php echo $topic_icon; ?>
                    <span><?php echo esc_html($topic->name); ?></span>
                </button>
            <?php 
                endforeach;
            endif; 
            ?>
            
            <?php 
            // Duration chips
            if ($show_duration && !empty($teacher_durations)):
                $duration_icon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
                foreach ($teacher_durations as $duration): 
            ?>
                <button type="button" class="mm-teacher-chip" data-filter-type="media_duration" data-filter-value="<?php echo esc_attr($duration->slug); ?>">
                    <?php echo $duration_icon; ?>
                    <span><?php echo esc_html($duration->name); ?></span>
                </button>
            <?php 
                endforeach;
            endif; 
            ?>
            
            <?php 
            // Year chips
            if ($show_year && !empty($teacher_years)):
                $year_icon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
                foreach ($teacher_years as $year): 
            ?>
                <button type="button" class="mm-teacher-chip" data-filter-type="media_year" data-filter-value="<?php echo esc_attr($year->slug); ?>">
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
    <div class="mindful-media-teacher-content" id="teacher-content">
        <?php 
        // Display posts organized by topic
        foreach ($posts_by_topic as $topic_data): 
            $topic = $topic_data['term'];
            $topic_posts = $topic_data['posts'];
            $topic_link = get_term_link($topic);
        ?>
            <section class="mindful-media-teacher-section" data-topic="<?php echo esc_attr($topic->slug); ?>">
                <div class="mindful-media-teacher-section-header">
                    <h2 class="mindful-media-teacher-section-title">
                        <?php if (!is_wp_error($topic_link)): ?>
                            <a href="<?php echo esc_url($topic_link); ?>"><?php echo esc_html($topic->name); ?></a>
                        <?php else: ?>
                            <?php echo esc_html($topic->name); ?>
                        <?php endif; ?>
                    </h2>
                    <span class="mindful-media-teacher-section-count"><?php echo count($topic_posts); ?> <?php echo count($topic_posts) === 1 ? 'item' : 'items'; ?></span>
                </div>
                
                <div class="mindful-media-teacher-grid">
                    <?php foreach ($topic_posts as $post): 
                        setup_postdata($post);
                        $post_id = $post->ID;
                        $thumbnail_url = get_the_post_thumbnail_url($post_id, 'medium_large');
                        if (!$thumbnail_url) {
                            $thumbnail_url = plugins_url('../../assets/default-thumbnail.jpg', __FILE__);
                        }
                        
                        // Duration
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
                        
                        // Media type
                        $media_types = get_the_terms($post_id, 'media_type');
                        $is_video = false;
                        $is_audio = false;
                        if ($media_types && !is_wp_error($media_types)) {
                            foreach ($media_types as $type) {
                                if (stripos($type->name, 'video') !== false) $is_video = true;
                                if (stripos($type->name, 'audio') !== false) $is_audio = true;
                            }
                        }
                        
                        // Get all taxonomies for filtering
                        $post_topics = get_the_terms($post_id, 'media_topic');
                        $post_topic_slugs = array();
                        if ($post_topics && !is_wp_error($post_topics)) {
                            foreach ($post_topics as $t) {
                                $post_topic_slugs[] = $t->slug;
                            }
                        }
                        
                        $post_durations = get_the_terms($post_id, 'media_duration');
                        $post_duration_slugs = array();
                        if ($post_durations && !is_wp_error($post_durations)) {
                            foreach ($post_durations as $d) {
                                $post_duration_slugs[] = $d->slug;
                            }
                        }
                        
                        $post_years = get_the_terms($post_id, 'media_year');
                        $post_year_slugs = array();
                        if ($post_years && !is_wp_error($post_years)) {
                            foreach ($post_years as $y) {
                                $post_year_slugs[] = $y->slug;
                            }
                        }
                        
                        $media_type_slug = '';
                        if ($is_video) $media_type_slug = 'video';
                        elseif ($is_audio) $media_type_slug = 'audio';
                    ?>
                        <article class="mindful-media-teacher-card mindful-media-card" 
                                 data-post-id="<?php echo esc_attr($post_id); ?>"
                                 data-topics="<?php echo esc_attr(implode(',', $post_topic_slugs)); ?>"
                                 data-durations="<?php echo esc_attr(implode(',', $post_duration_slugs)); ?>"
                                 data-years="<?php echo esc_attr(implode(',', $post_year_slugs)); ?>"
                                 data-type="<?php echo esc_attr($media_type_slug); ?>">
                            <div class="mindful-media-teacher-card-thumb">
                                <button type="button" class="mindful-media-thumb-trigger" data-post-id="<?php echo esc_attr($post_id); ?>" data-title="<?php echo esc_attr($post->post_title); ?>">
                                    <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($post->post_title); ?>" loading="lazy">
                                    
                                    <?php if ($duration_badge): ?>
                                    <span class="mindful-media-teacher-card-duration"><?php echo esc_html($duration_badge); ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if ($is_video): ?>
                                    <span class="mindful-media-teacher-card-type">
                                        <svg viewBox="0 0 24 24"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>
                                    </span>
                                    <?php elseif ($is_audio): ?>
                                    <span class="mindful-media-teacher-card-type">
                                        <svg viewBox="0 0 24 24"><path d="M12 3v9.28c-.47-.17-.97-.28-1.5-.28C8.01 12 6 14.01 6 16.5S8.01 21 10.5 21c2.31 0 4.2-1.75 4.45-4H15V6h4V3h-7z"/></svg>
                                    </span>
                                    <?php endif; ?>
                                    
                                    <div class="mindful-media-teacher-card-play">
                                        <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </button>
                            </div>
                            <h3 class="mindful-media-teacher-card-title"><?php echo esc_html($post->post_title); ?></h3>
                            <?php
                            // Playlist badge
                            $playlists = get_the_terms($post_id, 'media_series');
                            if ($playlists && !is_wp_error($playlists)) {
                                $playlist_info = null;
                                foreach ($playlists as $playlist) {
                                    if ($playlist->parent == 0) {
                                        $playlist_info = $playlist;
                                        break;
                                    }
                                }
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
            </section>
        <?php endforeach; ?>
        
        <?php 
        // Display uncategorized posts
        if (!empty($uncategorized_posts)): 
        ?>
            <section class="mindful-media-teacher-section" data-topic="uncategorized">
                <div class="mindful-media-teacher-section-header">
                    <h2 class="mindful-media-teacher-section-title"><?php _e('Other Videos', 'mindful-media'); ?></h2>
                    <span class="mindful-media-teacher-section-count"><?php echo count($uncategorized_posts); ?> <?php echo count($uncategorized_posts) === 1 ? 'item' : 'items'; ?></span>
                </div>
                
                <div class="mindful-media-teacher-grid">
                    <?php foreach ($uncategorized_posts as $post): 
                        setup_postdata($post);
                        $post_id = $post->ID;
                        $thumbnail_url = get_the_post_thumbnail_url($post_id, 'medium_large');
                        if (!$thumbnail_url) {
                            $thumbnail_url = plugins_url('../../assets/default-thumbnail.jpg', __FILE__);
                        }
                        
                        // Duration
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
                        
                        // Media type
                        $media_types = get_the_terms($post_id, 'media_type');
                        $is_video = false;
                        $is_audio = false;
                        if ($media_types && !is_wp_error($media_types)) {
                            foreach ($media_types as $type) {
                                if (stripos($type->name, 'video') !== false) $is_video = true;
                                if (stripos($type->name, 'audio') !== false) $is_audio = true;
                            }
                        }
                        
                        $post_durations = get_the_terms($post_id, 'media_duration');
                        $post_duration_slugs = array();
                        if ($post_durations && !is_wp_error($post_durations)) {
                            foreach ($post_durations as $d) {
                                $post_duration_slugs[] = $d->slug;
                            }
                        }
                        
                        $post_years = get_the_terms($post_id, 'media_year');
                        $post_year_slugs = array();
                        if ($post_years && !is_wp_error($post_years)) {
                            foreach ($post_years as $y) {
                                $post_year_slugs[] = $y->slug;
                            }
                        }
                        
                        $media_type_slug = '';
                        if ($is_video) $media_type_slug = 'video';
                        elseif ($is_audio) $media_type_slug = 'audio';
                    ?>
                        <article class="mindful-media-teacher-card mindful-media-card" 
                                 data-post-id="<?php echo esc_attr($post_id); ?>"
                                 data-topics=""
                                 data-durations="<?php echo esc_attr(implode(',', $post_duration_slugs)); ?>"
                                 data-years="<?php echo esc_attr(implode(',', $post_year_slugs)); ?>"
                                 data-type="<?php echo esc_attr($media_type_slug); ?>">
                            <div class="mindful-media-teacher-card-thumb">
                                <button type="button" class="mindful-media-thumb-trigger" data-post-id="<?php echo esc_attr($post_id); ?>" data-title="<?php echo esc_attr($post->post_title); ?>">
                                    <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($post->post_title); ?>" loading="lazy">
                                    
                                    <?php if ($duration_badge): ?>
                                    <span class="mindful-media-teacher-card-duration"><?php echo esc_html($duration_badge); ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if ($is_video): ?>
                                    <span class="mindful-media-teacher-card-type">
                                        <svg viewBox="0 0 24 24"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>
                                    </span>
                                    <?php elseif ($is_audio): ?>
                                    <span class="mindful-media-teacher-card-type">
                                        <svg viewBox="0 0 24 24"><path d="M12 3v9.28c-.47-.17-.97-.28-1.5-.28C8.01 12 6 14.01 6 16.5S8.01 21 10.5 21c2.31 0 4.2-1.75 4.45-4H15V6h4V3h-7z"/></svg>
                                    </span>
                                    <?php endif; ?>
                                    
                                    <div class="mindful-media-teacher-card-play">
                                        <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </button>
                            </div>
                            <h3 class="mindful-media-teacher-card-title"><?php echo esc_html($post->post_title); ?></h3>
                            <?php
                            // Playlist badge
                            $playlists = get_the_terms($post_id, 'media_series');
                            if ($playlists && !is_wp_error($playlists)) {
                                $playlist_info = null;
                                foreach ($playlists as $playlist) {
                                    if ($playlist->parent == 0) {
                                        $playlist_info = $playlist;
                                        break;
                                    }
                                }
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
            </section>
        <?php endif; ?>
        
        <!-- No results message (hidden by default) -->
        <div class="mindful-media-teacher-no-results" id="no-results" style="display: none;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <h3><?php _e('No results found', 'mindful-media'); ?></h3>
            <p><?php _e('Try adjusting your filters to find what you\'re looking for.', 'mindful-media'); ?></p>
        </div>
    </div>
</div>

<?php
// Output modal player container - needed for videos to play
$shortcodes = new MindfulMedia_Shortcodes();
echo $shortcodes->get_modal_player_html_public();
?>

<script>
(function($) {
    'use strict';
    
    // Store current filter state
    var currentFilterType = 'all';
    var currentFilterValue = '';
    var currentSearchTerm = '';
    
    $(document).ready(function() {
        // Filter chip click handler for teacher page
        $(document).on('click', '.mm-teacher-chip', function(e) {
            e.preventDefault();
            
            var $chip = $(this);
            currentFilterType = $chip.data('filter-type');
            currentFilterValue = $chip.data('filter-value');
            
            // Update active state
            $('.mm-teacher-chip').removeClass('active');
            $chip.addClass('active');
            
            // Apply combined filtering (search + chip)
            applyTeacherFilters();
        });
        
        // Search input handler
        $(document).on('keyup', '.mm-taxonomy-search-input', function(e) {
            var $input = $(this);
            currentSearchTerm = $input.val().trim().toLowerCase();
            
            // Update clear button visibility
            $input.closest('.mm-search-container').toggleClass('has-value', currentSearchTerm.length > 0);
            
            // Debounce search
            clearTimeout(window.teacherSearchTimeout);
            window.teacherSearchTimeout = setTimeout(function() {
                applyTeacherFilters();
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
            applyTeacherFilters();
            $input.focus();
        });
        
        function applyTeacherFilters() {
            var $content = $('#teacher-content');
            var $cards = $content.find('.mindful-media-teacher-card');
            var $sections = $content.find('.mindful-media-teacher-section');
            var $noResults = $('#no-results');
            var visibleCount = 0;
            
            $cards.each(function() {
                var $card = $(this);
                var show = true;
                
                // Apply chip filter
                if (currentFilterType !== 'all' && currentFilterValue) {
                    switch (currentFilterType) {
                        case 'media_type':
                            show = $card.data('type') === currentFilterValue;
                            break;
                        case 'media_topic':
                            var topics = ($card.data('topics') || '').toString().split(',');
                            show = topics.indexOf(currentFilterValue) !== -1;
                            break;
                        case 'media_duration':
                            var durations = ($card.data('durations') || '').toString().split(',');
                            show = durations.indexOf(currentFilterValue) !== -1;
                            break;
                        case 'media_year':
                            var years = ($card.data('years') || '').toString().split(',');
                            show = years.indexOf(currentFilterValue) !== -1;
                            break;
                    }
                }
                
                // Apply search filter (if passes chip filter)
                if (show && currentSearchTerm) {
                    var cardTitle = $card.find('.mindful-media-teacher-card-title').text().toLowerCase();
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
            
            // Hide/show sections based on visible cards
            $sections.each(function() {
                var $section = $(this);
                var visibleInSection = $section.find('.mindful-media-teacher-card:visible').length;
                
                if (visibleInSection > 0) {
                    $section.show();
                    // Update count
                    $section.find('.mindful-media-teacher-section-count').text(
                        visibleInSection + ' ' + (visibleInSection === 1 ? 'item' : 'items')
                    );
                } else {
                    $section.hide();
                }
            });
            
            // Show/hide no results message
            if (visibleCount === 0) {
                $noResults.show();
            } else {
                $noResults.hide();
            }
        }
    });
})(jQuery);
</script>

<?php get_footer(); ?>
