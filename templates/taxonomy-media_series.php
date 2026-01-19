<?php
/**
 * Playlist Archive Template - YouTube Modal Style
 * Displays all items in a playlist with modal-style black background
 */

// Suppress header deprecation warning
@get_header();

$term = get_queried_object();

// Get archive URL from settings
$settings = get_option('mindful_media_settings', array());
$archive_url = !empty($settings['archive_back_url']) ? $settings['archive_back_url'] : home_url('/media');

// Try to use the referrer URL if it looks like a browse page (to maintain context)
$browse_page_url = $archive_url;
if (!empty($_SERVER['HTTP_REFERER'])) {
    $referer = $_SERVER['HTTP_REFERER'];
    // If referrer is from same site and not already a taxonomy page, use it
    if (strpos($referer, home_url()) === 0 && 
        strpos($referer, '/teacher/') === false && 
        strpos($referer, '/topic/') === false &&
        strpos($referer, '/playlist/') === false &&
        strpos($referer, '/category/') === false) {
        $browse_page_url = strtok($referer, '?');
    }
}

// ===== PASSWORD PROTECTION CHECK =====
$is_password_protected = get_term_meta($term->term_id, 'password_enabled', true) === '1';
$has_access = true;
$password_error = '';

// Also check if this is a child of a password-protected parent
if (!$is_password_protected) {
    $parent_id = $term->parent;
    while ($parent_id) {
        $parent_protected = get_term_meta($parent_id, 'password_enabled', true) === '1';
        if ($parent_protected) {
            $is_password_protected = true;
            // Use parent's password protection
            $term_id_for_password = $parent_id;
            break;
        }
        $parent_term = get_term($parent_id, 'media_series');
        $parent_id = $parent_term ? $parent_term->parent : 0;
    }
}

$term_id_for_password = isset($term_id_for_password) ? $term_id_for_password : $term->term_id;

if ($is_password_protected) {
    // Check cookie for access
    $cookie_name = 'mindful_media_playlist_access_' . $term_id_for_password;
    $expected_cookie = wp_hash($term_id_for_password . 'mindful_media_playlist_access');
    $has_access = isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name] === $expected_cookie;
    
    // Check for password submission
    if (!$has_access && isset($_POST['playlist_password']) && isset($_POST['playlist_password_nonce'])) {
        if (wp_verify_nonce($_POST['playlist_password_nonce'], 'playlist_password_check')) {
            $submitted_password = sanitize_text_field($_POST['playlist_password']);
            $stored_password = get_term_meta($term_id_for_password, 'playlist_password', true);
            
            if ($submitted_password === $stored_password) {
                // Set cookie for 24 hours
                setcookie($cookie_name, $expected_cookie, time() + (24 * 60 * 60), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
                $has_access = true;
                // Refresh the page to pick up the cookie
                wp_redirect($_SERVER['REQUEST_URI']);
                exit;
            } else {
                $password_error = __('Incorrect password. Please try again.', 'mindful-media');
            }
        }
    }
}

// If password protected and no access, show password form
if ($is_password_protected && !$has_access):
    wp_enqueue_style('mindful-media-frontend', plugins_url('../../public/css/frontend.css', __FILE__), array(), MINDFUL_MEDIA_VERSION);
?>
<style>
body.tax-media_series {
    margin: 0;
    padding: 0;
    background: #f9f9f9 !important;
}
body.tax-media_series #primary,
body.tax-media_series .site-main {
    margin: 0 !important;
    padding: 0 !important;
    max-width: none !important;
    width: 100% !important;
}
.mindful-media-password-page {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    min-height: 100vh !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    background: #f9f9f9 !important;
    padding: 20px !important;
    font-family: 'Roboto', -apple-system, BlinkMacSystemFont, sans-serif;
    z-index: 9999 !important;
    box-sizing: border-box !important;
}
.mindful-media-password-container {
    background: white;
    padding: 48px;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.1);
    max-width: 420px;
    width: 100%;
    text-align: center;
}
.mindful-media-password-icon {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #8B0000 0%, #a01010 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
}
.mindful-media-password-icon svg {
    width: 32px;
    height: 32px;
    stroke: white;
}
.mindful-media-password-title {
    font-size: 24px;
    font-weight: 600;
    color: #0f0f0f;
    margin: 0 0 8px;
}
.mindful-media-password-subtitle {
    font-size: 14px;
    color: #606060;
    margin: 0 0 24px;
}
.mindful-media-password-form {
    text-align: left;
}
.mindful-media-password-input {
    width: 100%;
    padding: 14px 16px;
    font-size: 16px;
    border: 1px solid #e5e5e5;
    border-radius: 8px;
    margin-bottom: 16px;
    box-sizing: border-box;
    transition: border-color 0.2s ease;
}
.mindful-media-password-input:focus {
    outline: none;
    border-color: #065fd4;
}
.mindful-media-password-submit {
    width: 100%;
    padding: 14px 24px;
    background: #0f0f0f;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s ease;
}
.mindful-media-password-submit:hover {
    background: #272727;
}
.mindful-media-password-error {
    color: #dc2626;
    font-size: 14px;
    margin-bottom: 16px;
    padding: 12px;
    background: #fef2f2;
    border-radius: 8px;
}
.mindful-media-password-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 24px;
    color: #606060;
    text-decoration: none;
    font-size: 14px;
    transition: color 0.2s ease;
}
.mindful-media-password-back:hover {
    color: #0f0f0f;
}
</style>

<div class="mindful-media-password-page">
    <div class="mindful-media-password-container">
        <div class="mindful-media-password-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
        </div>
        <h1 class="mindful-media-password-title"><?php echo esc_html($term->name); ?></h1>
        <p class="mindful-media-password-subtitle"><?php _e('This playlist is password protected. Please enter the password to continue.', 'mindful-media'); ?></p>
        
        <?php if ($password_error): ?>
            <div class="mindful-media-password-error"><?php echo esc_html($password_error); ?></div>
        <?php endif; ?>
        
        <form method="post" class="mindful-media-password-form">
            <?php wp_nonce_field('playlist_password_check', 'playlist_password_nonce'); ?>
            <input type="password" name="playlist_password" class="mindful-media-password-input" placeholder="<?php esc_attr_e('Enter password', 'mindful-media'); ?>" required autofocus>
            <button type="submit" class="mindful-media-password-submit"><?php _e('Unlock Playlist', 'mindful-media'); ?></button>
        </form>
        
        <a href="<?php echo esc_url($browse_page_url); ?>" class="mindful-media-password-back">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            <?php _e('Back to All Media', 'mindful-media'); ?>
        </a>
    </div>
</div>

<?php 
@get_footer();
return; // Stop processing if password protected
endif;
// ===== END PASSWORD PROTECTION CHECK =====

// Check if this is a parent series (has children)
$children = get_terms(array(
    'taxonomy' => 'media_series',
    'parent' => $term->term_id,
    'hide_empty' => false
));
$is_parent_series = !empty($children) && !is_wp_error($children);

// Get total items count (including from children if parent)
$item_count = $term->count;
if ($is_parent_series) {
    foreach ($children as $child) {
        $item_count += $child->count;
    }
}

// Enqueue frontend assets for inline player
wp_enqueue_style('mindful-media-frontend', plugins_url('../../public/css/frontend.css', __FILE__), array(), MINDFUL_MEDIA_VERSION);
wp_enqueue_script('mindful-media-frontend', plugins_url('../../public/js/frontend.js', __FILE__), array('jquery'), MINDFUL_MEDIA_VERSION, true);
wp_localize_script('mindful-media-frontend', 'mindfulMediaAjax', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('mindful_media_ajax_nonce')
));
?>

<style>
/* YouTube-style Light Theme for Playlist Archive */
:root {
    --mm-bg-primary: #ffffff;
    --mm-bg-secondary: #f9f9f9;
    --mm-bg-elevated: #ffffff;
    --mm-text-primary: #0f0f0f;
    --mm-text-secondary: #606060;
    --mm-text-muted: #909090;
    --mm-border: #e5e5e5;
    --mm-hover: rgba(0,0,0,0.05);
    --mm-accent: #065fd4;
}

/* Remove default WordPress spacing */
body.tax-media_series {
    margin: 0;
    padding: 0;
    background: var(--mm-bg-secondary) !important;
}

body.tax-media_series #primary,
body.tax-media_series .site-main,
body.tax-media_series .entry-content,
body.tax-media_series article,
body.tax-media_series .ast-container,
body.tax-media_series #content,
body.tax-media_series .site-content {
    margin: 0 !important;
    padding: 0 !important;
    max-width: none !important;
    width: 100% !important;
}

/* Astra theme specific overrides */
body.tax-media_series .ast-separate-container .ast-article-single {
    padding: 0 !important;
}

body.tax-media_series .ast-separate-container .ast-article-post {
    padding: 0 !important;
}

/* Override Astra flex/grid styles on playlist */
body.tax-media_series .mindful-media-module-slider-track {
    display: flex !important;
    flex-wrap: nowrap !important;
}

body.tax-media_series .mindful-media-module-slider-item {
    flex: 0 0 280px !important;
}

/* Override Astra button styles */
body.tax-media_series button.mindful-media-module-nav {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
}

body.tax-media_series button.mindful-media-module-nav:hover {
    background: transparent !important;
}

/* Override dark theme module header styles */
body.tax-media_series .mindful-media-module-header {
    background: transparent !important;
    padding: 0 8px !important;
    position: relative !important;
    cursor: default !important;
}

body.tax-media_series .mindful-media-module-header:hover {
    background: transparent !important;
}

/* Override Astra image styles */
body.tax-media_series .mindful-media-playlist-item-thumbnail img {
    max-width: none !important;
    height: 100% !important;
}

/* Full-screen light background container - YouTube style */
.mindful-media-playlist-page {
    background: var(--mm-bg-secondary);
    min-height: 100vh;
    color: var(--mm-text-primary);
    padding: 0;
    margin: 0;
    width: 100%;
    font-family: 'Roboto', -apple-system, BlinkMacSystemFont, sans-serif;
}

/* Header - clean white with subtle shadow */
.mindful-media-playlist-page-header {
    position: sticky;
    top: 0;
    left: 0;
    right: 0;
    z-index: 100;
    background: var(--mm-bg-primary);
    border-bottom: 1px solid var(--mm-border);
    padding: 24px 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.mindful-media-playlist-page-title-section {
    flex: 1;
    min-width: 0;
}

.mindful-media-playlist-page-title {
    margin: 0 0 4px !important;
    font-size: 24px !important;
    font-weight: 600 !important;
    color: #0f0f0f !important;
}

/* Astra theme h1 override */
body.tax-media_series h1.mindful-media-playlist-page-title {
    color: #0f0f0f !important;
}

.mindful-media-playlist-page-meta {
    font-size: 14px;
    color: var(--mm-text-secondary);
}

<?php if ($term->description): ?>
.mindful-media-playlist-page-description {
    margin-top: 8px;
    font-size: 14px;
    color: var(--mm-text-secondary);
    line-height: 1.5;
}
<?php endif; ?>

.mindful-media-playlist-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: var(--mm-bg-secondary);
    border: 1px solid var(--mm-border);
    border-radius: 20px;
    color: var(--mm-text-primary);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.mindful-media-playlist-back-btn:hover {
    background: var(--mm-hover);
    border-color: var(--mm-text-muted);
}

/* Media items container - responsive grid */
.mindful-media-playlist-items-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 24px 40px 80px;
    width: 100%;
    box-sizing: border-box;
    overflow: visible;
}

/* For parent series with module sliders, allow full width */
.mindful-media-playlist-items-container:has(.mindful-media-module-section) {
    max-width: none;
    padding: 24px 20px 80px;
}

.mindful-media-playlist-items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 16px;
}

/* Individual media item - YouTube card style */
.mindful-media-playlist-grid-item {
    background: transparent;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
    position: relative;
}

.mindful-media-playlist-grid-item:hover {
    transform: translateY(-2px);
}

.mindful-media-playlist-item-thumbnail {
    width: 100%;
    aspect-ratio: 16 / 9;
    overflow: hidden;
    background: var(--mm-border);
    position: relative;
    border-radius: 12px;
}

.mindful-media-playlist-item-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.2s ease;
}

.mindful-media-playlist-grid-item:hover .mindful-media-playlist-item-thumbnail img {
    transform: scale(1.02);
}

/* Play button hover effect on thumbnail */
.mindful-media-playlist-item-thumbnail::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 56px;
    height: 56px;
    background: rgba(0, 0, 0, 0.8);
    border-radius: 50%;
    opacity: 0;
    transition: opacity 0.2s ease;
    pointer-events: none; /* Allow clicks to pass through to button */
}

.mindful-media-playlist-grid-item:hover .mindful-media-playlist-item-thumbnail::after {
    opacity: 1;
}

.mindful-media-playlist-item-thumbnail::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-40%, -50%);
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 10px 0 10px 18px;
    border-color: transparent transparent transparent #fff;
    z-index: 1;
    opacity: 0;
    transition: opacity 0.2s ease;
    pointer-events: none; /* Allow clicks to pass through to button */
}

.mindful-media-playlist-grid-item:hover .mindful-media-playlist-item-thumbnail::before {
    opacity: 1;
}

/* Play button inside playlist grid items - override main CSS reset */
.mindful-media-playlist-grid-item .mindful-media-play-inline {
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    opacity: 0 !important;
    cursor: pointer !important;
    z-index: 10 !important;
    border: none !important;
    background: transparent !important;
}

/* Item number badge - YouTube style timestamp position */
.mindful-media-playlist-item-number {
    position: absolute;
    bottom: 8px;
    right: 8px;
    background: rgba(0, 0, 0, 0.8);
    color: #fff;
    padding: 3px 6px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    z-index: 2;
}

.mindful-media-playlist-item-content {
    padding: 12px 4px;
    width: 100%;
    box-sizing: border-box;
}

/* Ensure module slider item content is visible */
.mindful-media-module-slider-item .mindful-media-playlist-item-content {
    padding: 10px 4px;
}

.mindful-media-playlist-item-title {
    font-size: 14px;
    font-weight: 500;
    color: var(--mm-text-primary) !important;
    margin: 0 0 8px;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    line-height: 1.4;
    word-wrap: break-word;
    word-break: break-word;
}

/* Ensure titles show in module slider items */
.mindful-media-module-slider-item .mindful-media-playlist-item-title {
    color: var(--mm-text-primary) !important;
    font-size: 13px;
}

.mindful-media-playlist-item-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: var(--mm-text-secondary);
}

.mindful-media-playlist-item-type {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    background: var(--mm-bg-secondary);
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
    color: var(--mm-text-secondary);
}

.mindful-media-playlist-item-duration {
    display: flex;
    align-items: center;
    gap: 4px;
    color: var(--mm-text-secondary);
}

.mindful-media-playlist-item-duration svg {
    stroke: var(--mm-text-secondary);
}

/* No results message */
.mindful-media-playlist-no-results {
    text-align: center;
    padding: 60px 20px;
    color: var(--mm-text-secondary);
}

/* Module sections for parent series - Netflix style */
.mindful-media-module-section {
    margin-bottom: 48px;
    position: relative;
    width: 100% !important;
    max-width: 100% !important;
    overflow: visible !important;
    box-sizing: border-box !important;
}

.mindful-media-module-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 8px;
    margin-bottom: 16px;
    background: transparent !important;
    position: relative;
    cursor: default;
}

.mindful-media-module-header:hover {
    background: transparent !important;
}

/* When there's a description, add spacing */
.mindful-media-module-header + .mindful-media-module-description {
    margin-top: -8px;
}

.mindful-media-module-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--mm-text-primary) !important;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.mindful-media-module-title a {
    color: var(--mm-text-primary) !important;
    text-decoration: none !important;
    transition: color 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.mindful-media-module-title a:hover {
    color: var(--mm-accent) !important;
}

.mindful-media-module-title-arrow {
    opacity: 0;
    transform: translateX(-8px);
    transition: all 0.2s ease;
    stroke: currentColor;
    width: 18px;
    height: 18px;
}

.mindful-media-module-title a:hover .mindful-media-module-title-arrow {
    opacity: 1;
    transform: translateX(0);
}

.mindful-media-module-count {
    font-size: 13px;
    font-weight: 400;
    color: var(--mm-text-secondary);
}

.mindful-media-module-description {
    color: var(--mm-text-secondary);
    font-size: 14px;
    margin: 0 0 16px 0;
    line-height: 1.5;
    padding: 0 8px;
}

/* Netflix-style slider for module items */
.mindful-media-module-slider {
    position: relative;
    overflow: visible !important;
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
}

.mindful-media-module-slider-track {
    display: flex !important;
    flex-wrap: nowrap !important;
    gap: 16px !important;
    overflow-x: auto !important;
    overflow-y: visible !important;
    scroll-behavior: smooth !important;
    -webkit-overflow-scrolling: touch !important;
    padding: 12px 4px 20px !important;
    scrollbar-width: none !important;
    -ms-overflow-style: none !important;
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
}

.mindful-media-module-slider-track::-webkit-scrollbar {
    display: none !important;
}

/* Ensure track children don't shrink */
.mindful-media-module-slider-track > * {
    flex-shrink: 0 !important;
}

.mindful-media-module-slider-item {
    flex: 0 0 280px !important;
    width: 280px !important;
    min-width: 280px !important;
    max-width: 280px !important;
    transition: transform 0.2s ease;
    box-sizing: border-box !important;
}

.mindful-media-module-slider-item .mindful-media-playlist-grid-item {
    width: 100% !important;
    min-width: 0 !important;
    max-width: none !important;
}

.mindful-media-module-slider-item .mindful-media-playlist-item-thumbnail {
    width: 100% !important;
    aspect-ratio: 16 / 9 !important;
}

.mindful-media-module-slider-item .mindful-media-playlist-item-thumbnail img {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
}

@media (max-width: 768px) {
    .mindful-media-module-slider-item {
        flex: 0 0 240px !important;
        width: 240px !important;
        min-width: 240px !important;
        max-width: 240px !important;
    }
}
@media (max-width: 480px) {
    .mindful-media-module-slider-item {
        flex: 0 0 200px !important;
        width: 200px !important;
        min-width: 200px !important;
        max-width: 200px !important;
    }
}

/* Slider navigation - Matching browse page style */
.mindful-media-module-nav {
    position: absolute;
    top: 4px;
    height: calc(56.25% - 8px); /* Match thumbnail height (16:9 ratio) */
    width: 60px;
    border: none;
    background: transparent !important;
    color: #333;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 10;
}

.mindful-media-module-nav--prev {
    left: 0;
    justify-content: flex-start;
    padding-left: 8px;
}

.mindful-media-module-nav--next {
    right: 0;
    justify-content: flex-end;
    padding-right: 8px;
}

.mindful-media-module-slider:hover .mindful-media-module-nav {
    opacity: 1;
}

.mindful-media-module-nav:hover {
    background: transparent !important;
}

.mindful-media-module-nav:disabled {
    opacity: 0 !important;
    cursor: default;
    pointer-events: none;
}

.mindful-media-module-nav svg {
    width: 56px;
    height: 56px;
    transition: transform 0.2s ease;
    stroke-width: 2.5;
    stroke: #333;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
}

.mindful-media-module-nav:hover svg {
    transform: scale(1.15);
}

/* Touch/Mobile - always show faded arrows */
@media (hover: none) {
    .mindful-media-module-nav {
        opacity: 0.7;
        width: 50px;
    }
    
    .mindful-media-module-nav svg {
        width: 48px;
        height: 48px;
    }
}

/* Modal player stays dark */
.mindful-media-inline-player {
    --mm-modal-bg: #0f0f0f;
    --mm-modal-text: #ffffff;
}

/* Search Input for Playlist Page */
.mm-playlist-search {
    position: relative;
    display: flex;
    align-items: center;
    margin-right: 16px;
}

.mm-playlist-search .mm-search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 16px;
    height: 16px;
    color: var(--mm-text-secondary);
    pointer-events: none;
}

.mm-playlist-search .mindful-media-search-input {
    width: 240px;
    padding: 10px 32px 10px 40px;
    border: 1px solid var(--mm-border);
    border-radius: 20px;
    font-size: 14px;
    color: var(--mm-text-primary);
    background: var(--mm-bg-primary);
    outline: none;
    transition: all 0.2s ease;
}

.mm-playlist-search .mindful-media-search-input:hover {
    border-color: var(--mm-text-muted);
}

.mm-playlist-search .mindful-media-search-input:focus {
    border-color: var(--mm-accent);
    box-shadow: 0 0 0 2px rgba(6, 95, 212, 0.15);
}

.mm-playlist-search .mm-search-clear {
    position: absolute;
    right: 10px;
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
    cursor: pointer;
    display: none;
    align-items: center;
    justify-content: center;
}

.mm-playlist-search.has-value .mm-search-clear {
    display: flex;
}

@media (max-width: 768px) {
    .mm-playlist-search {
        width: 100%;
        margin-right: 0;
        margin-bottom: 12px;
    }
    .mm-playlist-search .mindful-media-search-input {
        width: 100%;
    }
}

/* Responsive */
@media (max-width: 1200px) {
    .mindful-media-playlist-items-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .mindful-media-playlist-page-header {
        flex-direction: column;
        align-items: flex-start;
        padding: 16px 20px;
    }
    
    .mindful-media-playlist-items-container {
        padding: 16px 20px;
    }
    
    .mindful-media-playlist-items-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
}
</style>

<!-- Modal player container (initially hidden) - REQUIRED for inline player -->
<div id="mindful-media-inline-player" class="mindful-media-inline-player">
    <div class="mindful-media-modal-content">
        <div class="mindful-media-inline-player-header">
            <div class="mindful-media-inline-player-info">
                <h3 class="mindful-media-inline-player-title"></h3>
                <a href="#" class="mindful-media-inline-view-full" target="_blank" style="display: none;">View Full Page →</a>
            </div>
            <button class="mindful-media-inline-close" aria-label="Close player">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="mindful-media-inline-player-content"></div>
    </div>
</div>

<div class="mindful-media-playlist-page">
    <!-- Floating Header -->
    <div class="mindful-media-playlist-page-header">
        <div class="mindful-media-playlist-page-title-section">
            <h1 class="mindful-media-playlist-page-title"><?php echo esc_html($term->name); ?></h1>
            <div class="mindful-media-playlist-page-meta">
                Playlist • <?php echo $item_count; ?> <?php echo $item_count === 1 ? 'item' : 'items'; ?>
            </div>
            <?php if ($term->description): ?>
                <div class="mindful-media-playlist-page-description"><?php echo esc_html($term->description); ?></div>
            <?php endif; ?>
        </div>
        <!-- Search -->
        <div class="mm-search-container mm-playlist-search">
            <svg class="mm-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" class="mindful-media-search-input mm-playlist-search-input" placeholder="<?php esc_attr_e('Search playlist...', 'mindful-media'); ?>" />
            <button type="button" class="mm-search-clear" aria-label="<?php esc_attr_e('Clear search', 'mindful-media'); ?>">&times;</button>
        </div>
        
        <a href="<?php echo esc_url($browse_page_url); ?>" class="mindful-media-playlist-back-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Back to All Media
        </a>
    </div>
    
    <!-- Playlist Items Grid -->
    <div class="mindful-media-playlist-items-container">
        <?php if ($is_parent_series): ?>
            <!-- This is a parent series - show child playlists as Netflix-style sliders -->
            <?php foreach ($children as $child_playlist): 
                // Get items in this child playlist
                $child_items = new WP_Query(array(
                    'post_type' => 'mindful_media',
                    'posts_per_page' => -1,
                    'post_status' => 'publish',
                    'meta_key' => '_mindful_media_series_order',
                    'orderby' => 'meta_value_num',
                    'order' => 'ASC',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'media_series',
                            'field' => 'term_id',
                            'terms' => $child_playlist->term_id
                        )
                    )
                ));
                ?>
                
                <div class="mindful-media-module-section">
                    <div class="mindful-media-module-header">
                        <h2 class="mindful-media-module-title">
                            <a href="<?php echo get_term_link($child_playlist); ?>">
                                <?php echo esc_html($child_playlist->name); ?>
                                <svg class="mindful-media-module-title-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </a>
                        </h2>
                        <span class="mindful-media-module-count"><?php echo $child_playlist->count; ?> items</span>
                    </div>
                    <?php if ($child_playlist->description): ?>
                        <p class="mindful-media-module-description"><?php echo esc_html($child_playlist->description); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($child_items->have_posts()): ?>
                        <div class="mindful-media-module-slider" style="position: relative; width: 100%; overflow: visible;">
                            <!-- Navigation arrows -->
                            <button class="mindful-media-module-nav mindful-media-module-nav--prev" aria-label="Previous">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                            </button>
                            <button class="mindful-media-module-nav mindful-media-module-nav--next" aria-label="Next">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </button>
                            
                            <div class="mindful-media-module-slider-track" style="display: flex; flex-wrap: nowrap; gap: 16px; overflow-x: auto;">
                                <?php
                                $index = 1;
                                while ($child_items->have_posts()) : $child_items->the_post();
                                    $order = get_post_meta(get_the_ID(), '_mindful_media_series_order', true);
                                    $display_number = $order ? $order : $index;
                                    
                                    $duration_hours = get_post_meta(get_the_ID(), '_mindful_media_duration_hours', true);
                                    $duration_minutes = get_post_meta(get_the_ID(), '_mindful_media_duration_minutes', true);
                                    $duration_text = '';
                                    if ($duration_hours || $duration_minutes) {
                                        if ($duration_hours) $duration_text .= $duration_hours . 'h ';
                                        if ($duration_minutes) $duration_text .= $duration_minutes . 'm';
                                        $duration_text = trim($duration_text);
                                    }
                                    
                                    $media_types = get_the_terms(get_the_ID(), 'media_type');
                                    $type_name = ($media_types && !is_wp_error($media_types)) ? $media_types[0]->name : '';
                                    
                                    $thumbnail_url = get_the_post_thumbnail_url(get_the_ID(), 'medium_large');
                                    if (!$thumbnail_url) {
                                        $thumbnail_url = plugins_url('../../assets/default-thumbnail.jpg', __FILE__);
                                    }
                                    ?>
                                    
                                    <div class="mindful-media-module-slider-item" style="flex: 0 0 280px; width: 280px; min-width: 280px;">
                                        <div class="mindful-media-playlist-grid-item" data-post-id="<?php echo get_the_ID(); ?>" style="width: 100%;">
                                            <div class="mindful-media-playlist-item-thumbnail">
                                                <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy">
                                                <div class="mindful-media-playlist-item-number"><?php echo $display_number; ?></div>
                                            </div>
                                            
                                            <div class="mindful-media-playlist-item-content">
                                                <h3 class="mindful-media-playlist-item-title"><?php the_title(); ?></h3>
                                                <div class="mindful-media-playlist-item-meta">
                                                    <?php if ($type_name): ?>
                                                        <span class="mindful-media-playlist-item-type"><?php echo esc_html($type_name); ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($duration_text): ?>
                                                        <span class="mindful-media-playlist-item-duration">
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
                                                            </svg>
                                                            <?php echo esc_html($duration_text); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <button class="mindful-media-play-inline" data-post-id="<?php echo get_the_ID(); ?>" data-original-text="Play" style="position: absolute; width: 100%; height: 100%; top: 0; left: 0; opacity: 0; cursor: pointer; border: none; background: transparent; z-index: 5;"></button>
                                        </div>
                                    </div>
                                    
                                    <?php
                                    $index++;
                                endwhile;
                                wp_reset_postdata();
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
        <?php elseif (have_posts()): ?>
            <!-- Regular playlist - show items directly -->
            <div class="mindful-media-playlist-items-grid">
                <?php
                $index = 1;
                while (have_posts()) : the_post();
                    $order = get_post_meta(get_the_ID(), '_mindful_media_series_order', true);
                    $display_number = $order ? $order : $index;
                    
                    $duration_hours = get_post_meta(get_the_ID(), '_mindful_media_duration_hours', true);
                    $duration_minutes = get_post_meta(get_the_ID(), '_mindful_media_duration_minutes', true);
                    $duration_text = '';
                    if ($duration_hours || $duration_minutes) {
                        if ($duration_hours) $duration_text .= $duration_hours . 'h ';
                        if ($duration_minutes) $duration_text .= $duration_minutes . 'm';
                        $duration_text = trim($duration_text);
                    }
                    
                    $media_types = get_the_terms(get_the_ID(), 'media_type');
                    $type_name = ($media_types && !is_wp_error($media_types)) ? $media_types[0]->name : '';
                    
                    $thumbnail_url = get_the_post_thumbnail_url(get_the_ID(), 'medium_large');
                    if (!$thumbnail_url) {
                        $thumbnail_url = plugins_url('../../assets/default-thumbnail.jpg', __FILE__);
                    }
                    ?>
                    
                    <div class="mindful-media-playlist-grid-item" data-post-id="<?php echo get_the_ID(); ?>">
                        <div class="mindful-media-playlist-item-thumbnail">
                            <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                            <div class="mindful-media-playlist-item-number"><?php echo $display_number; ?></div>
                        </div>
                        
                        <div class="mindful-media-playlist-item-content">
                            <h3 class="mindful-media-playlist-item-title"><?php the_title(); ?></h3>
                            <div class="mindful-media-playlist-item-meta">
                                <?php if ($type_name): ?>
                                    <span class="mindful-media-playlist-item-type"><?php echo esc_html($type_name); ?></span>
                                <?php endif; ?>
                                <?php if ($duration_text): ?>
                                    <span class="mindful-media-playlist-item-duration">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
                                        </svg>
                                        <?php echo esc_html($duration_text); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <button class="mindful-media-play-inline" data-post-id="<?php echo get_the_ID(); ?>" data-original-text="Play" style="position: absolute; width: 100%; height: 100%; top: 0; left: 0; opacity: 0; cursor: pointer; border: none; background: transparent; z-index: 5;"></button>
                    </div>
                    
                    <?php
                    $index++;
                endwhile;
                ?>
            </div>
        <?php else: ?>
            <div class="mindful-media-playlist-no-results">
                <p>No items found in this playlist.</p>
            </div>
        <?php endif; ?>
        <?php wp_reset_postdata(); ?>
    </div>
</div>

<script>
// Initialize module sliders and search
jQuery(document).ready(function($) {
    // Module slider functionality
    $('.mindful-media-module-slider').each(function() {
        var $slider = $(this);
        var $track = $slider.find('.mindful-media-module-slider-track');
        var $prevBtn = $slider.find('.mindful-media-module-nav--prev');
        var $nextBtn = $slider.find('.mindful-media-module-nav--next');
        
        function getScrollAmount() {
            return $track.width() * 0.8;
        }
        
        $prevBtn.on('click', function() {
            $track.animate({ scrollLeft: $track.scrollLeft() - getScrollAmount() }, 300);
        });
        
        $nextBtn.on('click', function() {
            $track.animate({ scrollLeft: $track.scrollLeft() + getScrollAmount() }, 300);
        });
        
        // Touch support
        var touchStartX = 0;
        var touchEndX = 0;
        
        $track.on('touchstart', function(e) {
            touchStartX = e.originalEvent.touches[0].clientX;
        });
        
        $track.on('touchend', function(e) {
            touchEndX = e.originalEvent.changedTouches[0].clientX;
            var diff = touchStartX - touchEndX;
            if (Math.abs(diff) > 50) {
                if (diff > 0) {
                    $nextBtn.trigger('click');
                } else {
                    $prevBtn.trigger('click');
                }
            }
        });
    });
    
    // Playlist search functionality
    var $searchInput = $('.mm-playlist-search-input');
    var $searchContainer = $searchInput.closest('.mm-search-container');
    
    $searchInput.on('keyup', function() {
        var searchTerm = $(this).val().trim().toLowerCase();
        
        // Update clear button visibility
        $searchContainer.toggleClass('has-value', searchTerm.length > 0);
        
        // Debounce search
        clearTimeout(window.playlistSearchTimeout);
        window.playlistSearchTimeout = setTimeout(function() {
            applyPlaylistSearch(searchTerm);
        }, 200);
    });
    
    // Clear button
    $searchContainer.find('.mm-search-clear').on('click', function(e) {
        e.preventDefault();
        $searchInput.val('');
        $searchContainer.removeClass('has-value');
        applyPlaylistSearch('');
        $searchInput.focus();
    });
    
    function applyPlaylistSearch(searchTerm) {
        var $container = $('.mindful-media-playlist-items-container');
        var $gridItems = $container.find('.mindful-media-playlist-grid-item');
        var $sliderItems = $container.find('.mindful-media-module-slider-item');
        var $moduleSections = $container.find('.mindful-media-module-section');
        var visibleCount = 0;
        
        if (!searchTerm) {
            // Show all when search is cleared
            $gridItems.show();
            $sliderItems.show();
            $moduleSections.show();
            $container.find('.mm-playlist-no-results').remove();
            return;
        }
        
        // Filter grid items (regular playlist)
        $gridItems.each(function() {
            var $item = $(this);
            var title = $item.find('.mindful-media-playlist-item-title').text().toLowerCase();
            
            if (title.indexOf(searchTerm) !== -1) {
                $item.show();
                visibleCount++;
            } else {
                $item.hide();
            }
        });
        
        // Filter slider items (parent series with modules)
        $moduleSections.each(function() {
            var $section = $(this);
            var sectionMatches = 0;
            
            $section.find('.mindful-media-module-slider-item').each(function() {
                var $item = $(this);
                var title = $item.find('.mindful-media-playlist-item-title').text().toLowerCase();
                
                if (title.indexOf(searchTerm) !== -1) {
                    $item.show();
                    sectionMatches++;
                } else {
                    $item.hide();
                }
            });
            
            // Hide section if no matches
            if (sectionMatches > 0) {
                $section.show();
                visibleCount += sectionMatches;
            } else {
                $section.hide();
            }
        });
        
        // Show/hide no results message
        $container.find('.mm-playlist-no-results').remove();
        if (visibleCount === 0) {
            var noResultsHtml = '<div class="mm-playlist-no-results" style="text-align: center; padding: 60px 20px; color: var(--mm-text-secondary);">' +
                '<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity: 0.5; margin-bottom: 16px;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>' +
                '<h3 style="margin: 0 0 8px; color: var(--mm-text-primary);">No results found</h3>' +
                '<p style="margin: 0;">Try a different search term</p>' +
                '</div>';
            $container.append(noResultsHtml);
        }
    }
});
</script>

<?php @get_footer(); ?>
