<?php
/**
 * Archive Template for MindfulMedia
 * Displays all media items with Netflix-style layout
 */

// Suppress header deprecation warning
@get_header();

// Get archive URL from settings
$settings = get_option('mindful_media_settings', array());

// Enqueue plugin assets
wp_enqueue_style('mindful-media-frontend', plugins_url('../public/css/frontend.css', __FILE__), array(), MINDFUL_MEDIA_VERSION);
wp_enqueue_script('mindful-media-frontend', plugins_url('../public/js/frontend.js', __FILE__), array('jquery'), MINDFUL_MEDIA_VERSION, true);

// Localize script for AJAX
wp_localize_script('mindful-media-frontend', 'mindfulMediaAjax', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('mindful_media_ajax'),
    'modalShowMoreMedia' => $settings['modal_show_more_media'] ?? '1',
    'youtubeHideEndScreen' => $settings['youtube_hide_end_screen'] ?? '0'
));
?>

<style>
/* Archive page container */
.mindful-media-archive-page {
    --mm-bg-primary: #ffffff;
    --mm-bg-secondary: #f8f9fa;
    --mm-text-primary: #1a1a1a;
    --mm-text-secondary: #666666;
    --mm-accent-primary: #2563eb;
    --mm-border-color: #e5e7eb;
    
    min-height: 100vh;
    background: var(--mm-bg-primary);
    padding: 0;
}

.mindful-media-archive-header {
    background: var(--mm-bg-secondary);
    padding: 40px 20px;
    border-bottom: 1px solid var(--mm-border-color);
}

.mindful-media-archive-header-inner {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
}

.mindful-media-archive-title-section {
    flex: 1;
    min-width: 0;
}

.mm-archive-page-search {
    position: relative;
    display: flex;
    align-items: center;
}

.mm-archive-page-search .mm-search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 16px;
    height: 16px;
    color: var(--mm-text-secondary);
    pointer-events: none;
}

.mm-archive-page-search .mindful-media-search-input {
    width: 240px;
    padding: 10px 32px 10px 40px;
    border: 1px solid var(--mm-border-color);
    border-radius: 20px;
    font-size: 14px;
    color: var(--mm-text-primary);
    background: var(--mm-bg-primary);
    outline: none;
    transition: all 0.2s ease;
}

.mm-archive-page-search .mindful-media-search-input:hover {
    border-color: #ccc;
}

.mm-archive-page-search .mindful-media-search-input:focus {
    border-color: var(--mm-accent-primary);
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
}

.mm-archive-page-search .mm-search-clear {
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

.mm-archive-page-search.has-value .mm-search-clear {
    display: flex;
}

@media (max-width: 768px) {
    .mindful-media-archive-header-inner {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .mm-archive-page-search {
        width: 100%;
    }
    
    .mm-archive-page-search .mindful-media-search-input {
        width: 100%;
    }
}

.mindful-media-archive-title {
    font-size: 32px;
    font-weight: 700;
    color: var(--mm-text-primary);
    margin: 0 0 8px 0;
}

.mindful-media-archive-meta {
    color: var(--mm-text-secondary);
    font-size: 16px;
}

.mindful-media-archive-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 40px 20px;
}

/* Override slider styles for light theme */
.mindful-media-archive-page .mm-slider-row {
    margin-bottom: 48px;
}

.mindful-media-archive-page .mm-slider-title {
    color: var(--mm-text-primary);
}

.mindful-media-archive-page .mm-slider-title a {
    color: var(--mm-text-primary);
}

.mindful-media-archive-page .mm-slider-title a:hover {
    color: var(--mm-accent-primary);
}

.mindful-media-archive-page .mindful-media-card-title {
    color: var(--mm-text-primary);
}

.mindful-media-archive-page .mindful-media-card-teacher {
    color: var(--mm-text-secondary);
}

/* Modal player stays dark */
.mindful-media-inline-player {
    --mm-modal-bg: #0f0f0f;
    --mm-modal-text: #ffffff;
}

/* Responsive */
@media (max-width: 768px) {
    .mindful-media-archive-header {
        padding: 24px 16px;
    }
    
    .mindful-media-archive-title {
        font-size: 24px;
    }
    
    .mindful-media-archive-content {
        padding: 24px 16px;
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

<div class="mindful-media-archive-page">
    <!-- Header -->
    <div class="mindful-media-archive-header">
        <div class="mindful-media-archive-header-inner">
            <div class="mindful-media-archive-title-section">
                <h1 class="mindful-media-archive-title"><?php _e('Media Library', 'mindful-media'); ?></h1>
                <?php 
                $total_count = wp_count_posts('mindful_media')->publish;
                ?>
                <div class="mindful-media-archive-meta">
                    <?php echo sprintf(_n('%d item', '%d items', $total_count, 'mindful-media'), $total_count); ?>
                </div>
            </div>
            <div class="mm-search-container mm-archive-page-search">
                <svg class="mm-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" class="mindful-media-search-input mm-archive-page-search-input" placeholder="<?php esc_attr_e('Search...', 'mindful-media'); ?>" />
                <button type="button" class="mm-search-clear" aria-label="<?php esc_attr_e('Clear search', 'mindful-media'); ?>">&times;</button>
            </div>
        </div>
    </div>
    
    <!-- Content -->
    <div class="mindful-media-archive-content">
        <?php
        // Get all teachers and show as rows
        $teachers = get_terms(array(
            'taxonomy' => 'media_teacher',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        if (!empty($teachers) && !is_wp_error($teachers)) {
            foreach ($teachers as $teacher) {
                // Get media items for this teacher
                $query = new WP_Query(array(
                    'post_type' => 'mindful_media',
                    'post_status' => 'publish',
                    'posts_per_page' => 10,
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'media_teacher',
                            'field' => 'term_id',
                            'terms' => $teacher->term_id,
                        )
                    )
                ));
                
                if (!$query->have_posts()) {
                    wp_reset_postdata();
                    continue;
                }
                
                $teacher_link = get_term_link($teacher);
                ?>
                <section class="mm-slider-row mindful-media-row">
                    <div class="mm-slider-header">
                        <h3 class="mm-slider-title">
                            <a href="<?php echo esc_url($teacher_link); ?>">
                                <?php echo esc_html($teacher->name); ?>
                                <span class="mm-term-count">(<?php echo $teacher->count; ?>)</span>
                                <svg class="mm-slider-title-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </a>
                        </h3>
                    </div>
                    
                    <div class="mm-slider-container">
                        <button class="mm-slider-nav mm-slider-nav--prev" aria-label="Previous">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                        </button>
                        <button class="mm-slider-nav mm-slider-nav--next" aria-label="Next">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </button>
                        
                        <div class="mm-slider-track">
                            <?php while ($query->have_posts()): $query->the_post(); 
                                $post_id = get_the_ID();
                                $thumbnail_url = MindfulMedia_Shortcodes::get_media_thumbnail_url($post_id, 'medium_large');
                                
                                // Duration
                                $duration_hours = get_post_meta($post_id, '_mindful_media_duration_hours', true);
                                $duration_minutes = get_post_meta($post_id, '_mindful_media_duration_minutes', true);
                                $duration_badge = MindfulMedia_Shortcodes::format_duration_badge($duration_hours, $duration_minutes);
                            ?>
                            <div class="mm-slider-item">
                                <div class="mindful-media-card mindful-media-thumb-trigger" data-post-id="<?php echo $post_id; ?>" data-search="<?php echo esc_attr(MindfulMedia_Shortcodes::build_search_text($post_id)); ?>">
                                    <div class="mindful-media-card-thumb">
                                        <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy">
                                        <div class="mindful-media-card-play-overlay">
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="white"><path d="M8 5v14l11-7z"/></svg>
                                        </div>
                                        <?php if ($duration_badge): ?>
                                            <span class="mindful-media-duration-badge"><?php echo esc_html($duration_badge); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mindful-media-card-content">
                                        <h4 class="mindful-media-card-title"><?php echo esc_html(get_the_title()); ?></h4>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </div>
                    </div>
                </section>
                <?php
            }
        }
        
        // Show uncategorized items
        $uncategorized_query = new WP_Query(array(
            'post_type' => 'mindful_media',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'tax_query' => array(
                array(
                    'taxonomy' => 'media_teacher',
                    'operator' => 'NOT EXISTS'
                )
            )
        ));
        
        if ($uncategorized_query->have_posts()):
        ?>
        <section class="mm-slider-row mindful-media-row">
            <div class="mm-slider-header">
                <h3 class="mm-slider-title">
                    <?php _e('Other Media', 'mindful-media'); ?>
                </h3>
            </div>
            
            <div class="mm-slider-container">
                <button class="mm-slider-nav mm-slider-nav--prev" aria-label="Previous">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>
                <button class="mm-slider-nav mm-slider-nav--next" aria-label="Next">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
                
                <div class="mm-slider-track">
                    <?php while ($uncategorized_query->have_posts()): $uncategorized_query->the_post(); 
                        $post_id = get_the_ID();
                        $thumbnail_url = MindfulMedia_Shortcodes::get_media_thumbnail_url($post_id, 'medium_large');
                        
                        $duration_hours = get_post_meta($post_id, '_mindful_media_duration_hours', true);
                        $duration_minutes = get_post_meta($post_id, '_mindful_media_duration_minutes', true);
                        $duration_badge = MindfulMedia_Shortcodes::format_duration_badge($duration_hours, $duration_minutes);
                    ?>
                    <div class="mm-slider-item">
                        <div class="mindful-media-card mindful-media-thumb-trigger" data-post-id="<?php echo $post_id; ?>" data-search="<?php echo esc_attr(MindfulMedia_Shortcodes::build_search_text($post_id)); ?>">
                            <div class="mindful-media-card-thumb">
                                <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy">
                                <div class="mindful-media-card-play-overlay">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="white"><path d="M8 5v14l11-7z"/></svg>
                                </div>
                                <?php if ($duration_badge): ?>
                                    <span class="mindful-media-duration-badge"><?php echo esc_html($duration_badge); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="mindful-media-card-content">
                                <h4 class="mindful-media-card-title"><?php echo esc_html(get_the_title()); ?></h4>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </div>
</div>

<script>
// Archive page search functionality
jQuery(document).ready(function($) {
    var $searchInput = $('.mm-archive-page-search-input');
    var $searchContainer = $searchInput.closest('.mm-search-container');
    
    if (!$searchInput.length) return;
    
    $searchInput.on('keyup', function() {
        var searchTerm = $(this).val().trim().toLowerCase();
        
        // Update clear button visibility
        $searchContainer.toggleClass('has-value', searchTerm.length > 0);
        
        // Debounce search
        clearTimeout(window.archivePageSearchTimeout);
        window.archivePageSearchTimeout = setTimeout(function() {
            applyArchivePageSearch(searchTerm);
        }, 200);
    });
    
    // Clear button
    $searchContainer.find('.mm-search-clear').on('click', function(e) {
        e.preventDefault();
        $searchInput.val('');
        $searchContainer.removeClass('has-value');
        applyArchivePageSearch('');
        $searchInput.focus();
    });
    
    function applyArchivePageSearch(searchTerm) {
        var $rows = $('.mindful-media-archive-content .mm-slider-row');
        var totalVisible = 0;
        
        if (!searchTerm) {
            // Show all when search is cleared
            $rows.show();
            $rows.find('.mm-slider-item').show();
            $('.mm-archive-page-no-results').remove();
            return;
        }
        
        // Filter each row
        $rows.each(function() {
            var $row = $(this);
            var rowVisible = 0;
            
            $row.find('.mm-slider-item').each(function() {
                var $item = $(this);
                var $card = $item.find('.mindful-media-card');
                var dataSearch = ($card.attr('data-search') || '').toLowerCase();
                var title = $card.find('.mindful-media-card-title').text().toLowerCase();
                var combined = dataSearch || title;
                
                if (combined.indexOf(searchTerm) !== -1) {
                    $item.show();
                    rowVisible++;
                } else {
                    $item.hide();
                }
            });
            
            // Hide row if no visible items
            if (rowVisible > 0) {
                $row.show();
                totalVisible += rowVisible;
            } else {
                $row.hide();
            }
        });
        
        // Show/hide no results message
        $('.mm-archive-page-no-results').remove();
        if (totalVisible === 0) {
            var noResultsHtml = '<div class="mm-archive-page-no-results" style="text-align: center; padding: 60px 20px; color: var(--mm-text-secondary);">' +
                '<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity: 0.5; margin-bottom: 16px;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>' +
                '<h3 style="margin: 0 0 8px; color: var(--mm-text-primary);">No results found</h3>' +
                '<p style="margin: 0;">Try a different search term</p>' +
                '</div>';
            $('.mindful-media-archive-content').append(noResultsHtml);
        }
    }
});
</script>

<?php @get_footer(); ?>
