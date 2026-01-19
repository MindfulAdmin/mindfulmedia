/**
 * MindfulMedia Frontend JavaScript
 */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        initFilterChips();
        initSearch();
        initSinglePagePlayer();
        initCustomAudioPlayer();
        initInlinePlayer();
        initCardClicks();
        initNetflixSliders();
        initLazyLoading();
        initKeyboardNavigation();
        initHashNavigation();
    });
    
    /**
     * Handle hash-based tab navigation for browse block
     * Allows linking to specific tabs via URL hash (e.g., #mm-topics)
     */
    function initHashNavigation() {
        var hash = window.location.hash;
        if (!hash) return;
        
        // Map hash to section ID
        var hashMap = {
            '#mm-home': 'all',
            '#mm-teachers': 'teachers',
            '#mm-topics': 'topics',
            '#mm-playlists': 'playlists',
            '#mm-categories': 'categories',
            '#mm-types': 'types'
        };
        
        var section = hashMap[hash];
        if (!section) return;
        
        // Find the browse navigation and click the matching tab
        setTimeout(function() {
            var $navItem = $('.mindful-media-browse-nav-item[data-section="' + section + '"]');
            if ($navItem.length) {
                $navItem.trigger('click');
                // Scroll to the browse section
                var $browse = $navItem.closest('.mindful-media-browse');
                if ($browse.length) {
                    $('html, body').animate({
                        scrollTop: $browse.offset().top - 100
                    }, 400);
                }
            }
        }, 100);
    }
    
    /**
     * Initialize YouTube-style filter chips
     */
    function initFilterChips() {
        // Inline styles for theme isolation (must match PHP output)
        var chipStyleInactive = 'display:inline-flex;align-items:center;gap:4px;padding:8px 12px;background:#f2f2f2;border:none;border-radius:8px;font-family:inherit;font-size:14px;font-weight:500;color:#0f0f0f;cursor:pointer;white-space:nowrap;transition:all 0.2s;text-decoration:none;text-transform:none;letter-spacing:normal;line-height:1.4;box-shadow:none;outline:none;margin:0;';
        var chipStyleActive = 'display:inline-flex;align-items:center;gap:4px;padding:8px 12px;background:#0f0f0f;border:none;border-radius:8px;font-family:inherit;font-size:14px;font-weight:500;color:#ffffff;cursor:pointer;white-space:nowrap;transition:all 0.2s;text-decoration:none;text-transform:none;letter-spacing:normal;line-height:1.4;box-shadow:none;outline:none;margin:0;';
        
        $(document).on('click', '.mm-chip', function(e) {
            e.preventDefault();
            var $chip = $(this);
            
            // Handle "More filters" chip
            if ($chip.data('action') === 'toggle-filters') {
                $('.mindful-media-filters').toggleClass('active');
                $chip.toggleClass('active');
                return;
            }
            
            // Update active state and inline styles for all chips
            $('.mm-chip').each(function() {
                $(this).removeClass('active').attr('style', chipStyleInactive);
            });
            $chip.addClass('active').attr('style', chipStyleActive);
            
            // Get filter data
            var filterType = $chip.data('filter-type');
            var filterValue = $chip.data('filter-value');
            
            
            // Apply filter via AJAX
            applyChipFilter(filterType, filterValue);
        });
    }
    
    /**
     * Apply chip filter via AJAX
     */
    function applyChipFilter(filterType, filterValue) {
        var $archive = $('.mindful-media-archive');
        
        if (!$archive.length) {
            return;
        }
        
        // Check if AJAX is available
        if (typeof mindfulMediaAjax === 'undefined') {
            return;
        }
        
        // Build filters object and search term
        var filters = {};
        var searchTerm = '';
        
        if (filterType === 'search') {
            searchTerm = filterValue;
        } else if (filterType && filterType !== 'all' && filterValue) {
            filters[filterType] = [filterValue];
        }
        
        // Show loading state
        $archive.addClass('mindful-media-loading');
        
        // AJAX request
        $.ajax({
            url: mindfulMediaAjax.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mindful_media_filter',
                nonce: mindfulMediaAjax.nonce,
                filters: filters,
                search: searchTerm,
                limit: 24
            },
            success: function(response) {
                if (response.success) {
                    $archive.html(response.data.html);
                }
            },
            complete: function() {
                $archive.removeClass('mindful-media-loading');
            }
        });
    }
    
    /**
     * Initialize card click behavior (modal-first navigation)
     */
    function initCardClicks() {
        
        // Make entire card clickable to open modal player
        // Works for both old .mindful-media-item and new .mindful-media-card
        $(document).on('click', '.mindful-media-item, .mindful-media-card', function(e) {
            // Don't trigger if clicking on a link or button inside the card
            if ($(e.target).closest('a, button').length > 0) {
                return;
            }
            
            e.preventDefault();
            
            var $card = $(this);
            var $playBtn = $card.find('.mindful-media-play-inline');
            
            // If there's a play button, trigger it
            if ($playBtn.length > 0) {
                $playBtn.trigger('click');
                return;
            }
            
            // Otherwise, check for post ID in data attribute or find it from title link
            var postId = $card.data('post-id');
            if (!postId) {
                var $titleLink = $card.find('.mindful-media-item-title a');
                if ($titleLink.length > 0) {
                    // Navigate to the page as fallback
                    window.location.href = $titleLink.attr('href');
                }
            }
        });
        
        // Add visual feedback
        $(document).on('mouseenter', '.mindful-media-item', function() {
            $(this).addClass('card-hover');
        }).on('mouseleave', '.mindful-media-item', function() {
            $(this).removeClass('card-hover');
        });
    }
    
    /**
     * Initialize search functionality
     */
    function initSearch() {
        // Archive page search (AJAX-based)
        initArchiveSearch();
        
        // Browse page search (hybrid - AJAX for cards, client-side for video rows)
        initBrowseSearch();
        
        // Taxonomy page search (client-side filtering)
        initTaxonomySearch();
        
        // Search clear button functionality
        initSearchClearButtons();
    }
    
    /**
     * Initialize archive page search (uses AJAX)
     */
    function initArchiveSearch() {
        var $searchInput = $('.mindful-media-filter-chips .mindful-media-search-input');
        
        if (!$searchInput.length) {
            return;
        }
        
        $searchInput.on('keyup', function(e) {
            var $input = $(this);
            var searchTerm = $input.val().trim();
            
            // Update clear button visibility
            $input.closest('.mm-search-container').toggleClass('has-value', searchTerm.length > 0);
            
            // Apply search on Enter key
            if (e.keyCode === 13) {
                if (searchTerm) {
                    applyChipFilter('search', searchTerm);
                } else {
                    applyChipFilter('all', '');
                }
                return;
            }
            
            // Debounce search (300ms)
            clearTimeout(window.mindfulMediaSearchTimeout);
            window.mindfulMediaSearchTimeout = setTimeout(function() {
                if (searchTerm) {
                    applyChipFilter('search', searchTerm);
                } else {
                    applyChipFilter('all', '');
                }
            }, 300);
        });
    }
    
    /**
     * Initialize browse page search
     */
    function initBrowseSearch() {
        var $searchInput = $('.mm-browse-search');
        
        if (!$searchInput.length) {
            return;
        }
        
        $searchInput.on('keyup', function(e) {
            var $input = $(this);
            var searchTerm = $input.val().trim().toLowerCase();
            
            // Update clear button visibility
            $input.closest('.mm-search-container').toggleClass('has-value', searchTerm.length > 0);
            
            // Debounce search (300ms)
            clearTimeout(window.mindfulMediaBrowseSearchTimeout);
            window.mindfulMediaBrowseSearchTimeout = setTimeout(function() {
                applyBrowseSearch(searchTerm);
            }, 300);
        });
    }
    
    /**
     * Apply search to browse page content
     */
    function applyBrowseSearch(searchTerm) {
        var $browse = $('.mindful-media-browse');
        if (!$browse.length) return;
        
        var $sections = $browse.find('.mindful-media-browse-section-wrapper');
        
        if (!searchTerm) {
            // Show all content when search is cleared
            $sections.show();
            $browse.find('.mm-slider-item, .mindful-media-card, .mindful-media-browse-card').show();
            $browse.find('.mm-slider-row').show();
            $browse.find('.mm-browse-no-results').remove();
            return;
        }
        
        var totalMatches = 0;
        
        $sections.each(function() {
            var $section = $(this);
            var sectionMatches = 0;
            
            // Search in cards view (taxonomy term cards)
            $section.find('.mm-browse-cards-view .mindful-media-browse-card').each(function() {
                var $card = $(this);
                var cardTitle = ($card.find('.mindful-media-browse-card-title').text() || '').toLowerCase();
                
                if (cardTitle.indexOf(searchTerm) !== -1) {
                    $card.show();
                    sectionMatches++;
                } else {
                    $card.hide();
                }
            });
            
            // Search in video rows view
            $section.find('.mm-browse-videos-view .mm-slider-row').each(function() {
                var $row = $(this);
                var rowMatches = 0;
                
                $row.find('.mindful-media-card').each(function() {
                    var $card = $(this);
                    var cardTitle = ($card.find('.mindful-media-card-title').text() || 
                                    $card.attr('data-title') || 
                                    $card.find('.mindful-media-thumb-trigger').attr('data-title') || '').toLowerCase();
                    
                    if (cardTitle.indexOf(searchTerm) !== -1) {
                        $card.show();
                        rowMatches++;
                    } else {
                        $card.hide();
                    }
                });
                
                // Hide row if no matches
                if (rowMatches > 0) {
                    $row.show();
                    sectionMatches += rowMatches;
                } else {
                    $row.hide();
                }
            });
            
            totalMatches += sectionMatches;
        });
        
        // Show "no results" message if nothing found
        $browse.find('.mm-browse-no-results').remove();
        if (totalMatches === 0) {
            var noResultsHtml = '<div class="mm-browse-no-results" style="text-align: center; padding: 60px 20px; color: var(--mm-text-secondary);">' +
                '<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity: 0.5; margin-bottom: 16px;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>' +
                '<h3 style="margin: 0 0 8px; color: var(--mm-text-primary);">No results found</h3>' +
                '<p style="margin: 0;">Try a different search term</p>' +
                '</div>';
            $browse.find('.mindful-media-browse-sections').append(noResultsHtml);
        }
    }
    
    /**
     * Initialize taxonomy page search (client-side filtering)
     * Works on teacher, topic, playlist, and category archive pages
     * Also works on taxonomy archive shortcode pages
     */
    function initTaxonomySearch() {
        var $searchInput = $('.mm-taxonomy-search-input, .mm-taxonomy-archive-search-input');
        
        if (!$searchInput.length) {
            return;
        }
        
        $searchInput.on('keyup', function(e) {
            var $input = $(this);
            var searchTerm = $input.val().trim().toLowerCase();
            
            // Update clear button visibility
            $input.closest('.mm-search-container').toggleClass('has-value', searchTerm.length > 0);
            
            // Determine which search function to call based on context
            var isTaxonomyArchiveShortcode = $input.hasClass('mm-taxonomy-archive-search-input');
            
            // Debounce search (200ms for client-side - faster response)
            clearTimeout(window.mindfulMediaTaxonomySearchTimeout);
            window.mindfulMediaTaxonomySearchTimeout = setTimeout(function() {
                if (isTaxonomyArchiveShortcode) {
                    applyTaxonomyArchiveSearch(searchTerm);
                } else {
                    applyTaxonomySearch(searchTerm);
                }
            }, 200);
        });
    }
    
    /**
     * Apply client-side search filtering to taxonomy archive shortcode
     */
    function applyTaxonomyArchiveSearch(searchTerm) {
        var $container = $('.mindful-media-taxonomy-archive-content');
        if (!$container.length) return;
        
        var $rows = $container.find('.mm-slider-row');
        var $cards = $container.find('.mindful-media-card, .mm-slider-item');
        var totalVisible = 0;
        
        if (!searchTerm) {
            // Show all when search is cleared
            $rows.show();
            $cards.show();
            $container.find('.mm-taxonomy-archive-no-results').remove();
            return;
        }
        
        // Filter each row
        $rows.each(function() {
            var $row = $(this);
            var rowVisible = 0;
            
            $row.find('.mm-slider-item').each(function() {
                var $item = $(this);
                var $card = $item.find('.mindful-media-card');
                var title = $card.find('.mindful-media-card-title').text().toLowerCase();
                
                if (title.indexOf(searchTerm) !== -1) {
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
        $container.find('.mm-taxonomy-archive-no-results').remove();
        if (totalVisible === 0) {
            var noResultsHtml = '<div class="mm-taxonomy-archive-no-results" style="text-align: center; padding: 60px 20px; color: var(--mm-text-secondary);">' +
                '<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity: 0.5; margin-bottom: 16px;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>' +
                '<h3 style="margin: 0 0 8px; color: var(--mm-text-primary);">No results found</h3>' +
                '<p style="margin: 0;">Try a different search term</p>' +
                '</div>';
            $container.append(noResultsHtml);
        }
    }
    
    /**
     * Apply client-side search filtering to taxonomy pages
     */
    function applyTaxonomySearch(searchTerm) {
        // Find content containers - support multiple page types
        var $content = $('#teacher-content, #topic-content, #playlist-content, #category-content, .mindful-media-taxonomy-content');
        
        if (!$content.length) return;
        
        var $cards = $content.find('.mindful-media-card, .mindful-media-teacher-card');
        var $sections = $content.find('.mindful-media-teacher-section, .mindful-media-taxonomy-section, .mm-slider-row');
        var $noResults = $content.find('#no-results, .mm-taxonomy-no-results');
        var visibleCount = 0;
        
        if (!searchTerm) {
            // Show all when search is cleared
            $cards.show();
            $sections.show();
            $noResults.hide();
            
            // Reset section counts
            $sections.each(function() {
                var $section = $(this);
                var totalCards = $section.find('.mindful-media-card, .mindful-media-teacher-card').length;
                $section.find('.mindful-media-teacher-section-count, .mm-section-count').text(
                    totalCards + ' ' + (totalCards === 1 ? 'item' : 'items')
                );
            });
            return;
        }
        
        // Filter cards by title
        $cards.each(function() {
            var $card = $(this);
            var cardTitle = '';
            
            // Get title from various possible sources
            var $titleEl = $card.find('.mindful-media-teacher-card-title, .mindful-media-card-title, h3, h4');
            if ($titleEl.length) {
                cardTitle = $titleEl.text().toLowerCase();
            }
            
            // Also check data attributes
            var dataTitle = $card.attr('data-title') || 
                           $card.find('.mindful-media-thumb-trigger').attr('data-title') || '';
            if (dataTitle) {
                cardTitle += ' ' + dataTitle.toLowerCase();
            }
            
            if (cardTitle.indexOf(searchTerm) !== -1) {
                $card.show();
                visibleCount++;
            } else {
                $card.hide();
            }
        });
        
        // Update section visibility and counts
        $sections.each(function() {
            var $section = $(this);
            var visibleInSection = $section.find('.mindful-media-card:visible, .mindful-media-teacher-card:visible').length;
            
            if (visibleInSection > 0) {
                $section.show();
                // Update count display
                $section.find('.mindful-media-teacher-section-count, .mm-section-count').text(
                    visibleInSection + ' ' + (visibleInSection === 1 ? 'item' : 'items')
                );
            } else {
                $section.hide();
            }
        });
        
        // Show/hide no results message
        if (visibleCount === 0) {
            if ($noResults.length) {
                $noResults.show();
            } else {
                // Create no results message if it doesn't exist
                var noResultsHtml = '<div class="mm-taxonomy-no-results" style="text-align: center; padding: 60px 20px; color: var(--mm-text-secondary);">' +
                    '<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity: 0.5; margin-bottom: 16px;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>' +
                    '<h3 style="margin: 0 0 8px; color: var(--mm-text-primary);">No results found</h3>' +
                    '<p style="margin: 0;">Try a different search term or clear your filters</p>' +
                    '</div>';
                $content.append(noResultsHtml);
            }
        } else {
            $noResults.hide();
            $content.find('.mm-taxonomy-no-results').remove();
        }
    }
    
    /**
     * Initialize search clear buttons
     */
    function initSearchClearButtons() {
        $(document).on('click', '.mm-search-clear', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var $container = $btn.closest('.mm-search-container');
            var $input = $container.find('input');
            
            // Clear the input
            $input.val('').trigger('keyup').focus();
            $container.removeClass('has-value');
        });
        
        // Also update has-value class on input change
        $(document).on('input', '.mindful-media-search-input, .mm-taxonomy-search-input', function() {
            var $input = $(this);
            $input.closest('.mm-search-container').toggleClass('has-value', $input.val().length > 0);
        });
    }
    
    /**
     * Initialize single page video player
     */
    function initSinglePagePlayer() {
        // Video embeds now display directly without play button interaction
    }
    
    /**
     * Initialize Archive.org embeds
     */
    function initCustomAudioPlayer() {
        
        $('.mindful-media-archive-player').each(function() {
            var $container = $(this);
            var $iframe = $container.find('iframe');
            
            if ($iframe.length > 0) {
                
                // Handle iframe load
                $iframe.on('load', function() {
                });
            }
        });
    }
    
    /**
     * Format time in MM:SS format
     */
    function formatTime(seconds) {
        if (isNaN(seconds)) return '0:00';
        
        var minutes = Math.floor(seconds / 60);
        var secs = Math.floor(seconds % 60);
        return minutes + ':' + (secs < 10 ? '0' : '') + secs;
    }
    
    /**
     * Convert YouTube URL to embed URL
     */
    function getYouTubeEmbedUrl(url) {
        var videoId = null;
        
        // Standard YouTube URL patterns
        var patterns = [
            /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/,
            /youtube\.com\/v\/([^&\n?#]+)/
        ];
        
        for (var i = 0; i < patterns.length; i++) {
            var match = url.match(patterns[i]);
            if (match && match[1]) {
                videoId = match[1];
                break;
            }
        }
        
        if (videoId) {
            return 'https://www.youtube.com/embed/' + videoId;
        }
        
        return null;
    }
    
    /**
     * Render playlist sidebar - supports both simple playlists and hierarchical with modules
     */
    function renderPlaylistSidebar(playlistData) {
        
        // Check if sidebar is currently collapsed
        var wasCollapsed = $('.mindful-media-playlist-sidebar').hasClass('collapsed');
        
        // Remove existing sidebar if any
        $('.mindful-media-playlist-sidebar').remove();
        $('.mindful-media-playlist-toggle').remove();
        
        // Build sidebar HTML with collapsed class if it was collapsed
        var sidebarClass = 'mindful-media-playlist-sidebar';
        if (wasCollapsed) {
            sidebarClass += ' collapsed';
        }
        
        var html = '<div class="' + sidebarClass + '">';
        
        // Playlist header with name and count
        html += '<div class="mindful-media-playlist-header">';
        html += '<div class="mindful-media-playlist-header-content">';
        html += '<h3>' + escapeHtml(playlistData.name) + '</h3>';
        html += '<span class="mindful-media-playlist-count">' + playlistData.total_items + ' items</span>';
        if (playlistData.description) {
            html += '<p class="mindful-media-playlist-description">' + escapeHtml(playlistData.description) + '</p>';
        }
        html += '</div>';
        html += '</div>';
        
        html += '<div class="mindful-media-playlist-items">';
        
        // Check if hierarchical playlist with modules
        if (playlistData.has_modules && playlistData.modules) {
            // Render modules with their items
            playlistData.modules.forEach(function(module, moduleIndex) {
                var moduleClass = 'mindful-media-playlist-module';
                if (module.is_current_module) {
                    moduleClass += ' active';
                }
                
                html += '<div class="' + moduleClass + '" data-module-id="' + module.id + '">';
                
                // Module header (collapsible)
                html += '<div class="mindful-media-module-header" data-module-index="' + moduleIndex + '">';
                html += '<svg class="mindful-media-module-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>';
                html += '<span class="mindful-media-module-name">' + escapeHtml(module.name) + '</span>';
                html += '<span class="mindful-media-module-count">(' + module.items.length + ')</span>';
                html += '</div>';
                
                // Module items
                html += '<div class="mindful-media-module-items">';
                module.items.forEach(function(item, itemIndex) {
                    var itemClass = 'mindful-media-playlist-item';
                    if (item.is_current) {
                        itemClass += ' active';
                    }
                    
                    html += '<div class="' + itemClass + '" data-post-id="' + item.id + '">';
                    html += '<span class="mindful-media-playlist-item-number">' + (itemIndex + 1) + '</span>';
                    html += '<div class="mindful-media-playlist-item-info">';
                    html += '<div class="mindful-media-playlist-item-title">' + escapeHtml(item.title) + '</div>';
                    if (item.duration) {
                        html += '<div class="mindful-media-playlist-item-duration">' + escapeHtml(item.duration) + '</div>';
                    }
                    html += '</div>';
                    if (item.is_current) {
                        html += '<svg class="mindful-media-playlist-item-playing" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>';
                    }
                    html += '</div>';
                });
                html += '</div>'; // .mindful-media-module-items
                
                html += '</div>'; // .mindful-media-playlist-module
            });
        } else if (playlistData.items) {
            // Simple playlist without modules
            playlistData.items.forEach(function(item, index) {
                var itemClass = 'mindful-media-playlist-item';
                if (item.is_current) {
                    itemClass += ' active';
                }
                
                html += '<div class="' + itemClass + '" data-post-id="' + item.id + '">';
                html += '<span class="mindful-media-playlist-item-number">' + (index + 1) + '</span>';
                html += '<div class="mindful-media-playlist-item-info">';
                html += '<div class="mindful-media-playlist-item-title">' + escapeHtml(item.title) + '</div>';
                if (item.duration) {
                    html += '<div class="mindful-media-playlist-item-duration">' + escapeHtml(item.duration) + '</div>';
                }
                html += '</div>';
                if (item.is_current) {
                    html += '<svg class="mindful-media-playlist-item-playing" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>';
                }
                html += '</div>';
            });
        }
        
        html += '</div>';
        html += '</div>';
        
        // Append sidebar to modal content
        $('.mindful-media-modal-content').append(html);
        
        // Create toggle button as a SEPARATE element (sibling to sidebar)
        var toggleHtml = '<button class="mindful-media-playlist-toggle" aria-label="Toggle playlist">';
        toggleHtml += '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
        toggleHtml += '<polyline points="9 18 15 12 9 6"></polyline>';
        toggleHtml += '</svg>';
        toggleHtml += '</button>';
        
        // Append toggle button separately (NOT as child of sidebar)
        $('.mindful-media-modal-content').append(toggleHtml);
        
        // Unbind any existing handlers to prevent duplicates
        $(document).off('click', '.mindful-media-playlist-toggle');
        
        // Add toggle handler for playlist collapse/expand
        $(document).on('click', '.mindful-media-playlist-toggle', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $sidebar = $('.mindful-media-playlist-sidebar');
            var $player = $('#mindful-media-inline-player');
            
            // Toggle collapsed state on sidebar
            $sidebar.toggleClass('collapsed');
            
            // Also toggle state on player container for CSS cascade
            if ($sidebar.hasClass('collapsed')) {
                $player.addClass('playlist-collapsed');
            } else {
                $player.removeClass('playlist-collapsed');
            }
        });
        
        // Add module header toggle handler (for hierarchical playlists)
        $(document).off('click', '.mindful-media-module-header');
        $(document).on('click', '.mindful-media-module-header', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $module = $(this).closest('.mindful-media-playlist-module');
            $module.toggleClass('collapsed');
        });
        
        // Add click handlers for playlist items
        // Handle playlist item clicks (use event delegation since items are loaded dynamically)
        $(document).on('click', '.mindful-media-playlist-item', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if ($(this).hasClass('active')) {
                return;
            }
            
            var postId = $(this).data('post-id');
            
            // Load this media item into the player via AJAX
            $.ajax({
                url: mindfulMediaAjax.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mindful_media_load_inline',
                    nonce: mindfulMediaAjax.nonce,
                    post_id: postId
                },
                beforeSend: function() {
                },
                success: function(response) {
                    if (response.success) {
                        
                        // Update player content
                        $('.mindful-media-inline-player-content').html(response.data.player);
                        
                        // Update header
                        $('.mindful-media-inline-player-header h3').html(response.data.title);
                        
                        // Update active state in playlist
                        $('.mindful-media-playlist-item').removeClass('active');
                        $('.mindful-media-playlist-item[data-post-id="' + postId + '"]').addClass('active');
                        
                        // Re-initialize players
                        initUnifiedPlayers();
                        
                        // Scroll player into view
                        $('.mindful-media-inline-player').animate({ scrollTop: 0 }, 300);
                    } else {
                    }
                },
                error: function(xhr, status, error) {
                }
            });
        });
        
        // CRITICAL: If player content is empty (first load of playlist), auto-load the first item
        if ($('.mindful-media-inline-player-content').is(':empty') || $('.mindful-media-inline-player-content').html().trim() === '') {
            var firstItemId = $('.mindful-media-playlist-item.active').data('post-id');
            if (firstItemId) {
                // Trigger the play button for the first item
                $('.mindful-media-play-inline[data-post-id="' + firstItemId + '"]').trigger('click');
            }
        }
    }
    
    /**
     * Helper function to escape HTML
     */
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    /**
     * Initialize inline player
     */
    function initInlinePlayer() {
        
        var $inlinePlayer = $('#mindful-media-inline-player');
        var $playerContent = $('.mindful-media-inline-player-content');
        var $playerTitle = $('.mindful-media-inline-player-title');
        
        // Helper function to load media into modal player
        function loadMediaIntoModal(postId, callback) {
            $.ajax({
                url: mindfulMediaAjax.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mindful_media_load_inline',
                    nonce: mindfulMediaAjax.nonce,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        var titleHtml = response.data.title;
                        if (response.data.teacher) {
                            titleHtml += ' <span style="font-weight:normal;font-style:italic;color:rgba(255,255,255,0.8)">by ' + response.data.teacher + '</span>';
                        }
                        $playerTitle.html(titleHtml);
                        $('.mindful-media-inline-view-full').attr('href', response.data.permalink).show();
                        $playerContent.html(response.data.player);
                        
                        // Render playlist sidebar if playlist data exists
                        if (response.data.playlist) {
                            renderPlaylistSidebar(response.data.playlist);
                            $inlinePlayer.addClass('has-playlist');
                        } else {
                            $('.mindful-media-playlist-sidebar').remove();
                            $inlinePlayer.removeClass('has-playlist');
                            
                            // For non-playlist items, add description/tags section below player
                            renderModalContentSection(response.data);
                        }
                        
                        // Initialize unified players after AJAX load
                        setTimeout(function() {
                            initUnifiedPlayers();
                        }, 100);
                        
                        // Only load browse content if NOT a playlist (playlist has sidebar instead)
                        if (!response.data.playlist) {
                            loadBrowseContent(postId);
                        }
                        
                        $inlinePlayer.addClass('active');
                        $('body').addClass('modal-open');
                        
                        if (callback) callback(true, response);
                    } else {
                        if (callback) callback(false, response);
                    }
                },
                error: function() {
                    if (callback) callback(false, null);
                }
            });
        }
        
        // Render description, meta, and tags section in modal (matches single page layout)
        function renderModalContentSection(data) {
            // Remove existing content section
            $('.mindful-media-modal-content-section').remove();
            
            var html = '<div class="mindful-media-modal-content-section">';
            html += '<div class="mindful-media-modal-content-inner">';
            
            // Meta line (date, duration, type) - styled like single page
            var metaParts = [];
            if (data.date) metaParts.push('<span>' + data.date + '</span>');
            if (data.duration) metaParts.push('<span>' + data.duration + '</span>');
            if (data.media_type) metaParts.push('<span>' + data.media_type + '</span>');
            
            if (metaParts.length > 0) {
                html += '<div class="mindful-media-modal-meta">' + metaParts.join(' ') + '</div>';
            }
            
            // Description
            if (data.description) {
                html += '<div class="mindful-media-modal-description">' + data.description + '</div>';
            }
            
            // Categories and Topics chips
            var hasChips = (data.categories && data.categories.length > 0) || (data.topics && data.topics.length > 0);
            if (hasChips) {
                html += '<div class="mindful-media-modal-taxonomies">';
                
                if (data.categories && data.categories.length > 0) {
                    html += '<div class="mindful-media-modal-tax-group">';
                    html += '<span class="mindful-media-modal-tax-label">Categories:</span>';
                    for (var i = 0; i < data.categories.length; i++) {
                        html += '<span class="mindful-media-modal-chip">' + data.categories[i] + '</span>';
                    }
                    html += '</div>';
                }
                
                if (data.topics && data.topics.length > 0) {
                    html += '<div class="mindful-media-modal-tax-group">';
                    html += '<span class="mindful-media-modal-tax-label">Topics:</span>';
                    for (var j = 0; j < data.topics.length; j++) {
                        html += '<span class="mindful-media-modal-chip">' + data.topics[j] + '</span>';
                    }
                    html += '</div>';
                }
                
                html += '</div>';
            }
            
            html += '</div>'; // close inner
            html += '</div>'; // close section
            
            // Insert after player content
            $playerContent.after(html);
        }
        
        // Thumbnail trigger - opens modal without changing button content
        // Skip if this is a playlist button (has its own handler)
        $(document).on('click', '.mindful-media-thumb-trigger', function(e) {
            // If this is a playlist button, let the playlist handler deal with it
            if ($(this).hasClass('mindful-media-playlist-watch-btn')) {
                return;
            }
            
            e.preventDefault();
            e.stopPropagation();
            var $btn = $(this);
            var postId = $btn.data('post-id');
            
            $btn.prop('disabled', true);
            
            loadMediaIntoModal(postId, function(success, response) {
                $btn.prop('disabled', false);
                if (!success) {
                    if (response && response.data && response.data.message === 'playlist_password_required') {
                        showPasswordModal(postId, $btn, {
                            isPlaylist: true,
                            playlistId: response.data.playlist_id,
                            playlistName: response.data.playlist_name
                        });
                    } else if (response && response.data && response.data.message === 'password_required') {
                        showPasswordModal(postId, $btn);
                    }
                }
            });
        });
        
        // Title trigger - opens modal without changing button content
        $(document).on('click', '.mindful-media-title-trigger', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var $btn = $(this);
            var postId = $btn.data('post-id');
            
            
            loadMediaIntoModal(postId, function(success, response) {
                if (!success) {
                    if (response && response.data && response.data.message === 'playlist_password_required') {
                        showPasswordModal(postId, $btn, {
                            isPlaylist: true,
                            playlistId: response.data.playlist_id,
                            playlistName: response.data.playlist_name
                        });
                    } else if (response && response.data && response.data.message === 'password_required') {
                        showPasswordModal(postId, $btn);
                    }
                }
            });
        });
        
        // Embed thumbnail click handler - for [mindful_media playlist="..."] shortcode embeds
        $(document).on('click', '.mindful-media-embed-thumbnail, .mindful-media-embed-play-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Find the parent embed container
            var $embed = $(this).closest('.mindful-media-embed-thumbnail');
            if (!$embed.length) {
                $embed = $(this).closest('.mindful-media-embed-playlist');
            }
            if (!$embed.length) {
                $embed = $(this);
            }
            
            var postId = $embed.data('post-id');
            var playlistSlug = $embed.data('playlist-slug');
            
            if (!postId) {
                console.error('No post ID found for embed');
                return;
            }
            
            // Load the media into modal (password check happens in AJAX handler)
            loadMediaIntoModal(postId, function(success, response) {
                if (!success) {
                    if (response && response.data && response.data.message === 'playlist_password_required') {
                        showPasswordModal(postId, $embed, {
                            isPlaylist: true,
                            playlistId: response.data.playlist_id,
                            playlistName: response.data.playlist_name
                        });
                    } else if (response && response.data && response.data.message === 'password_required') {
                        showPasswordModal(postId, $embed);
                    }
                }
            });
        });
        
        // Legacy handler for .mindful-media-play-inline (e.g., playlist sidebar items)
        $(document).on('click', '.mindful-media-play-inline', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var postId = $btn.data('post-id');
            
            // Store original text if not already stored
            if (!$btn.data('original-text')) {
                $btn.data('original-text', $btn.text());
            }
            
            $btn.prop('disabled', true).text('Loading...');
            
            loadMediaIntoModal(postId, function(success, response) {
                $btn.prop('disabled', false);
                if (success) {
                    // Clear any existing "Now playing" indicators
                    $('.mindful-media-play-inline').each(function() {
                        var orig = $(this).data('original-text');
                        if (orig) $(this).text(orig);
                        $(this).removeClass('now-playing');
                    });
                    // Set current button to "Now playing"
                    $btn.text('Now playing').addClass('now-playing');
                } else {
                    $btn.text($btn.data('original-text'));
                    if (response && response.data && response.data.message === 'playlist_password_required') {
                        showPasswordModal(postId, $btn, {
                            isPlaylist: true,
                            playlistId: response.data.playlist_id,
                            playlistName: response.data.playlist_name
                        });
                    } else if (response && response.data && response.data.message === 'password_required') {
                        showPasswordModal(postId, $btn);
                    } else {
                        alert('Failed to load player');
                    }
                }
            });
        });
        
        // Handle playlist WATCH button clicks - load first item with playlist sidebar
        $(document).on('click', '.mindful-media-playlist-watch-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var postId = $btn.data('post-id'); // First item in playlist
            
            
            // Add loading class (don't use .text() as it destroys the thumbnail)
            $btn.addClass('loading').prop('disabled', true);
            
            $.ajax({
                url: mindfulMediaAjax.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mindful_media_load_inline',
                    nonce: mindfulMediaAjax.nonce,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        var titleHtml = response.data.title;
                        if (response.data.teacher) {
                            titleHtml += ' <span style="font-weight:normal;font-style:italic;color:rgba(255,255,255,0.8)">by ' + response.data.teacher + '</span>';
                        }
                        $playerTitle.html(titleHtml);
                        $('.mindful-media-inline-view-full').attr('href', response.data.permalink).show();
                        $playerContent.html(response.data.player);
                        
                        // Render playlist sidebar - it should always exist for playlist items
                        if (response.data.playlist) {
                            renderPlaylistSidebar(response.data.playlist);
                            $inlinePlayer.addClass('has-playlist');
                        }
                        
                        // Initialize unified players
                        setTimeout(function() {
                            initUnifiedPlayers();
                        }, 100);
                        
                        // Show player and scroll to top
                        $inlinePlayer.addClass('active');
                        $('body').addClass('modal-open');
                        
                        // Remove loading state (don't use .text() - preserves thumbnail)
                        $btn.removeClass('loading').prop('disabled', false);
                    } else {
                        // Reset button state first
                        $btn.removeClass('loading').prop('disabled', false);
                        
                        // Check if password required (playlist or individual)
                        if (response.data && response.data.message === 'playlist_password_required') {
                            showPasswordModal(postId, $btn, {
                                isPlaylist: true,
                                playlistId: response.data.playlist_id,
                                playlistName: response.data.playlist_name
                            });
                        } else if (response.data && response.data.message === 'password_required') {
                            showPasswordModal(postId, $btn);
                        } else {
                            alert('Error loading playlist');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    $btn.removeClass('loading').prop('disabled', false);
                    alert('Error loading playlist');
                },
                complete: function() {
                    $btn.removeClass('loading').prop('disabled', false);
                }
            });
        });
        
        $('.mindful-media-inline-close').on('click', function() {
            // Clear all "Now playing" indicators and reset card states
            $('.mindful-media-play-inline').each(function() {
                var $btn = $(this);
                var originalText = $btn.data('original-text');
                if (originalText) {
                    $btn.text(originalText);
                }
                $btn.removeClass('now-playing');
            });
            
            // Reset any greyed-out thumbnail triggers and playlist buttons
            $('.mindful-media-thumb-trigger, .mindful-media-title-trigger, .mindful-media-playlist-watch-btn').each(function() {
                $(this).removeClass('loading played');
                $(this).prop('disabled', false);
            });
            
            // Reset card visual states
            $('.mindful-media-item, .mindful-media-card').removeClass('played loading');
            
            $inlinePlayer.removeClass('active has-playlist playlist-collapsed controls-active');
            $('body').removeClass('modal-open');
            
            // Clear content after animation
            setTimeout(function() {
                $playerContent.html('');
                $('.mindful-media-browse-below').remove();
                $('.mindful-media-playlist-sidebar').remove();
                $('.mindful-media-playlist-toggle').remove();
            }, 300);
        });
        
        // Close modal when clicking outside
        $inlinePlayer.on('click', function(e) {
            if (e.target === this) {
                $('.mindful-media-inline-close').click();
            }
        });
        
        // Close modal with ESC key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $inlinePlayer.hasClass('active')) {
                $('.mindful-media-inline-close').click();
            }
        });
        
        $('.mindful-media-inline-minimize').on('click', function() {
            $inlinePlayer.toggleClass('minimized');
            $(this).html($inlinePlayer.hasClass('minimized') ? '+' : '−');
        });
    }
    
    /**
     * Toggle filters on mobile
     */
    $('.mindful-media-filter-toggle').on('click', function() {
        $('.mindful-media-filters').toggleClass('active');
        $(this).toggleClass('active');
    });
    
    /**
     * Show password modal for protected content
     */
    function showPasswordModal(postId, $btn, options) {
        options = options || {};
        var isPlaylist = options.isPlaylist || false;
        var playlistId = options.playlistId || 0;
        var playlistName = options.playlistName || '';
        
        // Create modal if it doesn't exist
        if ($('#mindful-media-password-modal').length === 0) {
            var modalHtml = '<div id="mindful-media-password-modal" class="mindful-media-modal">' +
                '<div class="mindful-media-modal-content password-modal-content">' +
                '<button class="mindful-media-modal-close">&times;</button>' +
                '<div class="password-modal-header">' +
                '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>' +
                '<path d="M7 11V7a5 5 0 0 1 10 0v4"></path>' +
                '</svg>' +
                '<h3 id="password-modal-title">Protected Content</h3>' +
                '<p id="password-modal-description">This content requires a password to access.</p>' +
                '</div>' +
                '<div class="password-modal-body">' +
                '<input type="password" id="mindful-media-password-input" placeholder="Enter password" />' +
                '<div id="mindful-media-password-error" class="password-error" style="display:none;"></div>' +
                '<label style="display: flex; align-items: center; gap: 8px; margin-top: 15px; font-size: 14px;">' +
                '<input type="checkbox" id="mindful-media-remember-session" checked />' +
                'Remember for this session' +
                '</label>' +
                '</div>' +
                '<div class="password-modal-footer">' +
                '<button id="mindful-media-password-submit" class="mindful-media-button">Submit</button>' +
                '<button id="mindful-media-password-cancel" class="mindful-media-button-secondary">Cancel</button>' +
                '</div>' +
                '</div>' +
                '</div>';
            $('body').append(modalHtml);
        }
        
        var $modal = $('#mindful-media-password-modal');
        var $input = $('#mindful-media-password-input');
        var $error = $('#mindful-media-password-error');
        var $submit = $('#mindful-media-password-submit');
        var $title = $('#password-modal-title');
        var $description = $('#password-modal-description');
        
        // Update modal text based on type
        if (isPlaylist) {
            $title.text('Protected Playlist');
            $description.text('The playlist "' + playlistName + '" requires a password to access.');
        } else {
            $title.text('Protected Content');
            $description.text('This content requires a password to access.');
        }
        
        // Clear previous state
        $input.val('');
        $error.hide();
        
        // Show modal
        $modal.fadeIn(300);
        $('body').addClass('modal-open');
        setTimeout(function() { $input.focus(); }, 350);
        
        // Handle submit
        $submit.off('click').on('click', function() {
            var password = $input.val();
            if (!password) {
                $error.text('Please enter a password').show();
                return;
            }
            
            $submit.prop('disabled', true).text('Checking...');
            $error.hide();
            
            var ajaxData = {
                nonce: mindfulMediaAjax.nonce,
                password: password
            };
            
            if (isPlaylist) {
                ajaxData.action = 'mindful_media_check_playlist_password';
                ajaxData.playlist_id = playlistId;
            } else {
                ajaxData.action = 'mindful_media_check_password';
                ajaxData.post_id = postId;
            }
            
            $.ajax({
                url: mindfulMediaAjax.ajaxUrl,
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    if (response.success) {
                        $modal.fadeOut(300);
                        $('body').removeClass('modal-open');
                        // Reset button state and retry loading the player
                        $btn.prop('disabled', false);
                        setTimeout(function() {
                            $btn.trigger('click');
                        }, 350); // Wait for modal to close
                    } else {
                        $error.text('Incorrect password. Please try again.').show();
                        $input.val('').focus();
                    }
                },
                complete: function() {
                    $submit.prop('disabled', false).text('Submit');
                }
            });
        });
        
        // Handle enter key
        $input.off('keypress').on('keypress', function(e) {
            if (e.which === 13) {
                $submit.trigger('click');
            }
        });
        
        // Handle cancel
        $('#mindful-media-password-cancel, .mindful-media-modal-close').off('click').on('click', function() {
            $modal.fadeOut(300);
            $('body').removeClass('modal-open');
            // Reset button state
            $btn.html($btn.data('original-text')).removeClass('now-playing').prop('disabled', false);
        });
        
        // Close on outside click
        $modal.off('click').on('click', function(e) {
            if ($(e.target).is($modal)) {
                $modal.fadeOut(300);
                $('body').removeClass('modal-open');
                // Reset button state
                $btn.html($btn.data('original-text')).removeClass('now-playing').prop('disabled', false);
            }
        });
    }
    
    /**
     * Load browse content below the player
     */
    function loadBrowseContent(currentPostId) {
        
        // Remove any existing browse section
        $('.mindful-media-browse-below').remove();
        
        // Create browse section
        var $browseSection = $('<div class="mindful-media-browse-below"><div class="mindful-media-browse-below-container"><h3>More Media</h3><div class="mindful-media-browse-grid"></div></div></div>');
        var $browseGrid = $browseSection.find('.mindful-media-browse-grid');
        
        // Add loading state
        $browseGrid.html('<div class="mindful-media-browse-loading">Loading...</div>');
        
        // Append to modal content
        $('.mindful-media-modal-content').append($browseSection);
        
        // Load media items via AJAX
        $.ajax({
            url: mindfulMediaAjax.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mindful_media_browse',
                nonce: mindfulMediaAjax.nonce,
                exclude_id: currentPostId,
                limit: 9
            },
            success: function(response) {
                if (response.success && response.data.items) {
                    renderBrowseItems($browseGrid, response.data.items);
                } else {
                    $browseGrid.html('<div class="mindful-media-browse-loading">No more media found</div>');
                }
            },
            error: function() {
                $browseGrid.html('<div class="mindful-media-browse-loading">Failed to load content</div>');
            }
        });
    }
    
    /**
     * Render browse items in the grid
     */
    function renderBrowseItems($container, items) {
        $container.empty();
        
        items.forEach(function(item) {
            var $item = $('<div class="mindful-media-browse-item" data-post-id="' + item.id + '"></div>');
            
            // Image
            var imageHtml = '<div class="mindful-media-browse-item-image">';
            if (item.image) {
                imageHtml += '<img src="' + item.image + '" alt="' + item.title + '">';
            }
            imageHtml += '</div>';
            
            // Info
            var metaText = '';
            if (item.teacher) {
                metaText = item.teacher;
            }
            if (item.duration) {
                if (metaText) metaText += ' • ';
                metaText += item.duration;
            }
            
            var infoHtml = '<div class="mindful-media-browse-item-info">' +
                '<h4 class="mindful-media-browse-item-title">' + item.title + '</h4>' +
                (metaText ? '<p class="mindful-media-browse-item-meta">' + metaText + '</p>' : '') +
                '</div>';
            
            $item.html(imageHtml + infoHtml);
            
            // Click handler - play this video
            $item.on('click', function() {
                var postId = $(this).data('post-id');
                // Simulate clicking the play button for this item
                $('.mindful-media-play-inline[data-post-id="' + postId + '"]').trigger('click');
            });
            
            $container.append($item);
        });
    }
    
    /**
     * ============================================
     * UNIFIED PLAYER CONTROLLER
     * Handles YouTube, Vimeo, SoundCloud, Archive.org, HTML5
     * ============================================
     */
    
    function initUnifiedPlayers() {
        
        $('.mindful-media-unified-player').each(function() {
            var $player = $(this);
            var playerType = $player.data('player-type');
            
            // Skip if already initialized (use attr to check actual DOM attribute)
            if ($player.attr('data-player-initialized') === 'true') {
                return;
            }
            
            switch (playerType) {
                case 'youtube':
                    initYouTubePlayer($player);
                    break;
                case 'vimeo':
                    initVimeoPlayer($player);
                    break;
                case 'soundcloud':
                    initSoundCloudPlayer($player);
                    break;
                case 'archive':
                    initArchivePlayer($player);
                    break;
                case 'html5-video':
                case 'html5-audio':
                    initHTML5Player($player);
                    break;
            }
        });
    }
    
    /**
     * YouTube Player Controller
     */
    var youtubeAPIReady = false;
    var youtubePlayersQueue = [];
    
    function initYouTubePlayer($player) {
        var videoId = $player.data('video-id');
        var playerId = 'youtube-player-' + videoId;
        
        
        // Load YouTube IFrame API if not already loaded
        if (typeof YT === 'undefined' || typeof YT.Player === 'undefined') {
            if (!window.youtubeAPILoading) {
                window.youtubeAPILoading = true;
                var tag = document.createElement('script');
                tag.src = "https://www.youtube.com/iframe_api";
                var firstScriptTag = document.getElementsByTagName('script')[0];
                firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
            }
            
            // Queue this player for initialization
            youtubePlayersQueue.push({$player: $player, videoId: videoId, playerId: playerId});
            return;
        }
        
        // API is ready, initialize player
        createYouTubePlayer($player, videoId, playerId);
    }
    
    function createYouTubePlayer($player, videoId, playerId) {
        // Mark as initialized immediately to prevent duplicate initialization
        $player.attr('data-player-initialized', 'true');
        
        // Create player with videoId - this creates the iframe properly via API
        var ytPlayer = new YT.Player(playerId, {
            videoId: videoId,
            playerVars: {
                rel: 0,
                modestbranding: 1,
                showinfo: 0,
                controls: 0, // Hide native YouTube controls
                enablejsapi: 1,
                playsinline: 1,
                origin: window.location.origin
            },
            events: {
                'onReady': function(event) {
                    var playerInstance = event.target;
                    
                    // Store player reference
                    $player.data('player-instance', playerInstance);
                    
                    // Add controls-active class immediately so controls are visible
                    $player.addClass('controls-active');
                    
                    setupUnifiedControls($player, {
                        play: function() { playerInstance.playVideo(); },
                        pause: function() { playerInstance.pauseVideo(); },
                        seek: function(seconds) { playerInstance.seekTo(seconds, true); },
                        setVolume: function(volume) { playerInstance.setVolume(volume); },
                        getVolume: function() { return playerInstance.getVolume(); },
                        getDuration: function() { return playerInstance.getDuration(); },
                        getCurrentTime: function() { return playerInstance.getCurrentTime(); },
                        mute: function() { playerInstance.mute(); },
                        unmute: function() { playerInstance.unMute(); }
                    });
                },
                'onStateChange': function(event) {
                    if (event.data == YT.PlayerState.PLAYING) {
                        $player.addClass('playing controls-active'); // Enable custom controls once playing
                        updatePlayButton($player, 'pause');
                        // Hide the big play button
                        $player.find('.mindful-media-big-play-btn').addClass('hidden').css('display', 'none');
                        $player.trigger('playing'); // Trigger jQuery event for progress updates
                    } else if (event.data == YT.PlayerState.PAUSED) {
                        $player.removeClass('playing');
                        updatePlayButton($player, 'play');
                        // Show the big play button
                        $player.find('.mindful-media-big-play-btn').removeClass('hidden').css('display', 'flex');
                        $player.trigger('pause'); // Trigger jQuery event
                    } else if (event.data == YT.PlayerState.ENDED) {
                        $player.removeClass('playing');
                        updatePlayButton($player, 'play');
                        // Show the big play button
                        $player.find('.mindful-media-big-play-btn').removeClass('hidden').css('display', 'flex');
                        $player.trigger('ended'); // Trigger jQuery event
                    }
                }
            }
        });
    }
    
    // YouTube API ready callback
    window.onYouTubeIframeAPIReady = function() {
        youtubeAPIReady = true;
        
        // Initialize all queued players
        youtubePlayersQueue.forEach(function(item) {
            createYouTubePlayer(item.$player, item.videoId, item.playerId);
        });
        
        youtubePlayersQueue = [];
    };
    
    /**
     * Vimeo Player Controller
     */
    var vimeoPlayersQueue = [];
    
    function initVimeoPlayer($player) {
        var videoId = $player.data('video-id');
        var playerId = 'vimeo-player-' + videoId;
        
        // Load Vimeo Player API if not loaded
        if (typeof Vimeo === 'undefined' || typeof Vimeo.Player === 'undefined') {
            // Queue this player for initialization once API loads
            vimeoPlayersQueue.push({$player: $player, playerId: playerId});
            
            if (!window.vimeoAPILoading) {
                window.vimeoAPILoading = true;
                var script = document.createElement('script');
                script.src = 'https://player.vimeo.com/api/player.js';
                script.onload = function() {
                    window.vimeoAPILoaded = true;
                    // Initialize all queued players
                    vimeoPlayersQueue.forEach(function(item) {
                        createVimeoPlayer(item.$player, item.playerId);
                    });
                    vimeoPlayersQueue = [];
                };
                document.head.appendChild(script);
            }
            return;
        }
        
        createVimeoPlayer($player, playerId);
    }
    
    function createVimeoPlayer($player, playerId) {
        // Mark as initialized to prevent duplicate initialization
        $player.attr('data-player-initialized', 'true');
        
        var iframe = document.getElementById(playerId);
        var player = new Vimeo.Player(iframe);
        
        // Setup unified controls
        setupUnifiedControls($player, {
            play: function() { player.play(); },
            pause: function() { player.pause(); },
            seek: function(seconds) { player.setCurrentTime(seconds); },
            setVolume: function(volume) { player.setVolume(volume / 100); },
            getVolume: function(callback) { player.getVolume().then(function(vol) { callback(vol * 100); }); },
            getDuration: function(callback) { player.getDuration().then(callback); },
            getCurrentTime: function(callback) { player.getCurrentTime().then(callback); },
            mute: function() { player.setVolume(0); },
            unmute: function() { player.setVolume(1); }
        });
        
        // Listen to player events
        player.on('play', function() {
            $player.addClass('playing controls-active'); // Enable custom controls once playing
            updatePlayButton($player, 'pause');
            // Hide big play button
            var $bigPlay = $player.find('.mindful-media-big-play-btn');
            $bigPlay.addClass('hidden').css('display', 'none');
            $player.trigger('playing'); // Trigger jQuery event for progress updates
        });
        
        player.on('pause', function() {
            $player.removeClass('playing');
            updatePlayButton($player, 'play');
            // Show big play button
            var $bigPlay = $player.find('.mindful-media-big-play-btn');
            $bigPlay.removeClass('hidden').css('display', 'flex');
            $player.trigger('pause'); // Trigger jQuery event
        });
        
        player.on('ended', function() {
            $player.removeClass('playing');
            updatePlayButton($player, 'play');
            // Show big play button
            var $bigPlay = $player.find('.mindful-media-big-play-btn');
            $bigPlay.removeClass('hidden').css('display', 'flex');
            $player.trigger('ended'); // Trigger jQuery event
        });
        
        // Store player reference
        $player.data('player-instance', player);
    }
    
    /**
     * Setup unified controls for any player
     */
    function setupUnifiedControls($player, api) {
        
        // NOTE: Don't add 'controls-active' immediately - wait until video starts playing
        // This allows the user to click on the YouTube iframe's native play button first
        // (required for browser autoplay policy compliance)
        
        var $controls = $player.find('.mindful-media-custom-controls');
        var $playBtn = $player.find('.mindful-media-play-btn');
        var $bigPlayBtn = $player.find('.mindful-media-big-play-btn');
        var $progressContainer = $player.find('.mindful-media-progress-container');
        var $progressBar = $player.find('.mindful-media-progress-bar');
        var $currentTime = $player.find('.mindful-media-current-time');
        var $duration = $player.find('.mindful-media-duration');
        var $volumeBtn = $player.find('.mindful-media-volume-btn');
        var $volumeSlider = $player.find('.mindful-media-volume-slider input');
        var $fullscreenBtn = $player.find('.mindful-media-fullscreen-btn');
        
        var duration = 0;
        var updateInterval;
        var hideControlsTimeout;
        
        // Auto-hide controls after 3 seconds of inactivity
        function showControls() {
            $controls.addClass('force-show');
            clearTimeout(hideControlsTimeout);
            hideControlsTimeout = setTimeout(function() {
                if (isPlaying()) {
                    $controls.removeClass('force-show');
                }
            }, 3000);
        }
        
        // Show controls on mouse movement
        $player.on('mousemove', function() {
            showControls();
        });
        
        // Show controls on touch
        $player.on('touchstart', function() {
            showControls();
        });
        
        // Keep controls visible when not playing
        function updateControlsVisibility() {
            if (!isPlaying()) {
                $controls.addClass('force-show');
            } else {
                // Will be hidden by timeout
            }
        }
        
        // Helper to get isPlaying state
        function isPlaying() {
            return $player.hasClass('playing');
        }
        
        // Play/Pause button click
        // Note: Don't update UI here - let the player's state change event handle UI updates
        // This ensures UI stays in sync with actual player state
        $playBtn.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            try {
                if (isPlaying()) {
                    api.pause();
                    // UI update handled by player's pause event/onStateChange
                } else {
                    api.play();
                    // UI update handled by player's play event/onStateChange
                }
            } catch (error) {
                console.error('MindfulMedia: Error in play/pause:', error);
            }
        });
        
        // Big play button click
        // Note: Don't update UI here - let the player's state change event handle UI updates
        $bigPlayBtn.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            try {
                api.play();
                // UI update handled by player's play event/onStateChange
            } catch (error) {
                console.error('MindfulMedia: Error in big play button:', error);
            }
        });
        
        // Progress bar click AND drag - Full support for seeking
        var isDraggingProgress = false;
        
        function seekToPosition(e) {
            var rect = $progressContainer[0].getBoundingClientRect();
            var percent = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
            var seekTime = percent * duration;
            if (api.seek && duration > 0) {
                api.seek(seekTime);
            }
        }
        
        $progressContainer.on('mousedown', function(e) {
            e.preventDefault();
            e.stopPropagation();
            isDraggingProgress = true;
            seekToPosition(e);
            $progressContainer.addClass('dragging');
        });
        
        $(document).on('mousemove', function(e) {
            if (isDraggingProgress) {
                seekToPosition(e);
            }
        });
        
        $(document).on('mouseup', function(e) {
            if (isDraggingProgress) {
                isDraggingProgress = false;
                $progressContainer.removeClass('dragging');
            }
        });
        
        // Volume controls
        $volumeBtn.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var currentVolume = parseInt($volumeSlider.val()) || 0;
            if (currentVolume > 0) {
                $volumeSlider.val(0);
                $volumeSlider.data('previous-volume', currentVolume);
                api.mute();
            } else {
                var previousVolume = $volumeSlider.data('previous-volume') || 100;
                $volumeSlider.val(previousVolume);
                api.setVolume(previousVolume);
            }
        });
        
        $volumeSlider.on('input change', function() {
            var volume = parseInt($(this).val(), 10) || 0;
            api.setVolume(volume);
        });
        
        // Fullscreen button
        $fullscreenBtn.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var elem = $player[0];
            if (elem.requestFullscreen) {
                elem.requestFullscreen();
            } else if (elem.webkitRequestFullscreen) {
                elem.webkitRequestFullscreen();
            } else if (elem.mozRequestFullScreen) {
                elem.mozRequestFullScreen();
            } else if (elem.msRequestFullscreen) {
                elem.msRequestFullscreen();
            }
        });
        
        // Update progress and time display
        function updateProgress() {
            if (typeof api.getCurrentTime === 'function') {
                if (api.getCurrentTime.length > 0) {
                    // Async (Vimeo, SoundCloud)
                    api.getCurrentTime(function(currentTime) {
                        updateTimeDisplay(currentTime);
                    });
                } else {
                    // Sync (YouTube, HTML5)
                    var currentTime = api.getCurrentTime();
                    updateTimeDisplay(currentTime);
                }
            }
            
            // Always try to get duration (not just when duration === 0)
            if (typeof api.getDuration === 'function') {
                if (api.getDuration.length > 0) {
                    // Async
                    api.getDuration(function(dur) {
                        if (dur && dur > 0) {
                            duration = dur;
                            $duration.text(formatTime(dur));
                        }
                    });
                } else {
                    // Sync
                    var dur = api.getDuration();
                    if (dur && dur > 0) {
                        duration = dur;
                        $duration.text(formatTime(duration));
                    }
                }
            }
        }
        
        function updateTimeDisplay(currentTime) {
            $currentTime.text(formatTime(currentTime));
            if (duration > 0) {
                var percent = (currentTime / duration) * 100;
                $progressBar.css('width', percent + '%');
            }
        }
        
        // Start update interval when playing
        $player.on('playing', function() {
            if (!updateInterval) {
                updateInterval = setInterval(updateProgress, 500);
                updateProgress(); // Immediate update
            }
        });
        
        $player.on('pause ended', function() {
            if (updateInterval) {
                clearInterval(updateInterval);
                updateInterval = null;
            }
        });
        
        // Start continuous progress checking regardless of events
        // This ensures progress updates even if event binding has issues
        var progressCheckInterval = setInterval(function() {
            // Always update progress if player exists
            updateProgress();
            
            // Start the main interval if playing but not started
            if ($player.hasClass('playing') && !updateInterval) {
                updateInterval = setInterval(updateProgress, 500);
            }
        }, 1000);
        
        // Click on player area (not controls) to toggle play/pause
        $player.on('click', function(e) {
            // Don't trigger if clicking on controls or buttons
            if ($(e.target).closest('.mindful-media-custom-controls, .mindful-media-big-play-btn, button').length > 0) {
                return;
            }
            
            // Toggle play/pause
            if (isPlaying()) {
                api.pause();
            } else {
                api.play();
            }
        });
        
        // Initial update
        setTimeout(updateProgress, 1000);
    }
    
    /**
     * Update play button icon
     */
    function updatePlayButton($player, state) {
        var $playBtn = $player.find('.mindful-media-play-btn');
        
        if (state === 'play') {
            $playBtn.html('<svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>');
            $playBtn.attr('aria-label', 'Play');
        } else {
            $playBtn.html('<svg viewBox="0 0 24 24"><path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/></svg>');
            $playBtn.attr('aria-label', 'Pause');
        }
    }
    
    /**
     * HTML5 Player Controller (video/audio)
     */
    function initHTML5Player($player) {
        
        var $media = $player.find('video, audio');
        var media = $media[0];
        
        if (!media) {
            console.error('HTML5 ERROR: No video/audio element found in player');
            return;
        }
        
        
        // Wait for metadata to be loaded and update duration display
        var metadataLoaded = false;
        
        $media.on('loadedmetadata', function() {
            metadataLoaded = true;
            // Update duration display immediately when metadata loads
            var dur = media.duration;
            if (dur && dur > 0 && !isNaN(dur)) {
                $player.find('.mindful-media-duration').text(formatTime(dur));
                $player.trigger('durationupdated', dur);
            }
        });
        
        // Also try to get duration after a short delay in case loadedmetadata already fired
        setTimeout(function() {
            var dur = media.duration;
            if (dur && dur > 0 && !isNaN(dur)) {
                $player.find('.mindful-media-duration').text(formatTime(dur));
            }
        }, 500);
        
        $media.on('error', function(e) {
            console.error('HTML5: Media error:', e, media.error);
        });
        
        // Setup unified controls
        setupUnifiedControls($player, {
            play: function() { 
                var promise = media.play();
                if (promise !== undefined) {
                    promise.then(function() {
                    }).catch(function(error) {
                        console.error('HTML5: Play failed:', error);
                    });
                }
            },
            pause: function() { 
                media.pause(); 
            },
            seek: function(seconds) { 
                if (!isNaN(seconds) && isFinite(seconds) && media.duration) {
                    media.currentTime = Math.max(0, Math.min(seconds, media.duration));
                }
            },
            setVolume: function(volume) { 
                media.volume = Math.max(0, Math.min(1, volume / 100)); 
            },
            getVolume: function() { return (media.volume || 0) * 100; },
            getDuration: function() { return media.duration || 0; },
            getCurrentTime: function() { return media.currentTime || 0; },
            mute: function() { media.muted = true; },
            unmute: function() { media.muted = false; }
        });
        
        // For HTML5 players, enable controls immediately (no cross-origin iframe restriction)
        $player.addClass('controls-active');
        
        // Listen to media events
        $media.on('play playing', function() {
            $player.addClass('playing controls-active'); // Enable custom controls once playing
            updatePlayButton($player, 'pause');
            // Hide big play button - use both CSS class and direct style
            var $bigPlay = $player.find('.mindful-media-big-play-btn');
            $bigPlay.addClass('hidden').css('display', 'none');
            $player.trigger('playing'); // Trigger jQuery event for progress updates
        });
        
        $media.on('pause', function() {
            $player.removeClass('playing');
            updatePlayButton($player, 'play');
            // Show big play button on pause
            var $bigPlay = $player.find('.mindful-media-big-play-btn');
            $bigPlay.removeClass('hidden').css('display', 'flex');
            $player.trigger('pause'); // Trigger jQuery event
        });
        
        $media.on('ended', function() {
            $player.removeClass('playing');
            updatePlayButton($player, 'play');
            // Show big play button on end
            var $bigPlay = $player.find('.mindful-media-big-play-btn');
            $bigPlay.removeClass('hidden').css('display', 'flex');
            $player.trigger('ended'); // Trigger jQuery event
        });
        
        // Force metadata load
        if (media.readyState >= 1) {
            metadataLoaded = true;
        } else {
            media.load();
        }
    }
    
    /**
     * SoundCloud Player Controller
     */
    function initSoundCloudPlayer($player) {
        var soundId = $player.data('sound-id');
        var playerId = 'soundcloud-player-' + soundId;
        
        // Show loading spinner until ready
        $player.find('.mindful-media-loading-spinner').show();
        $player.addClass('loading');
        
        // Load SoundCloud Widget API if not loaded
        if (typeof SC === 'undefined' || typeof SC.Widget === 'undefined') {
            if (!window.soundcloudAPILoading) {
                window.soundcloudAPILoading = true;
                window.soundcloudPlayersWaiting = window.soundcloudPlayersWaiting || [];
                
                var script = document.createElement('script');
                script.src = 'https://w.soundcloud.com/player/api.js';
                script.onload = function() {
                    // Initialize all waiting players
                    window.soundcloudPlayersWaiting.forEach(function(playerData) {
                        createSoundCloudPlayer(playerData.$player, playerData.playerId);
                    });
                    window.soundcloudPlayersWaiting = [];
                };
                document.head.appendChild(script);
            }
            
            // Add this player to waiting list
            window.soundcloudPlayersWaiting = window.soundcloudPlayersWaiting || [];
            window.soundcloudPlayersWaiting.push({$player: $player, playerId: playerId});
            return;
        }
        
        createSoundCloudPlayer($player, playerId);
    }
    
    function createSoundCloudPlayer($player, playerId) {
        var iframe = document.getElementById(playerId);
        if (!iframe) {
            $player.find('.mindful-media-loading-spinner').hide();
            $player.removeClass('loading');
            return;
        }
        
        // Track playing state internally
        var isCurrentlyPlaying = false;
        var widgetReady = false;
        var widget = null;
        
        // Create widget immediately
        try {
            widget = SC.Widget(iframe);
        } catch(error) {
            $player.find('.mindful-media-loading-spinner').hide();
            $player.removeClass('loading');
            return;
        }
        
        // Function to toggle play/pause
        function togglePlayPause() {
            if (!widgetReady || !widget) return;
            
            if (isCurrentlyPlaying) {
                widget.pause();
            } else {
                widget.play();
            }
        }
        
        // Bind all event handlers immediately
        widget.bind(SC.Widget.Events.READY, function() {
            widgetReady = true;
            $player.data('player-ready', true);
            $player.data('player-instance', widget);
            
            // Hide loading spinner
            $player.find('.mindful-media-loading-spinner').hide();
            $player.removeClass('loading');
            
            // For SoundCloud, enable controls immediately (no cross-origin autoplay restrictions)
            $player.addClass('controls-active');
            
            // Setup unified controls with proper API
            setupUnifiedControls($player, {
                play: function() { 
                    if (widget && widgetReady) widget.play(); 
                },
                pause: function() { 
                    if (widget && widgetReady) widget.pause(); 
                },
                seek: function(seconds) { 
                    if (widget && widgetReady) widget.seekTo(seconds * 1000); 
                },
                setVolume: function(volume) { 
                    if (widget && widgetReady) widget.setVolume(volume); 
                },
                getVolume: function(callback) { 
                    if (widget && widgetReady) widget.getVolume(callback); 
                },
                getDuration: function(callback) { 
                    if (widget && widgetReady) widget.getDuration(function(ms) { callback(ms / 1000); }); 
                },
                getCurrentTime: function(callback) { 
                    if (widget && widgetReady) widget.getPosition(function(ms) { callback(ms / 1000); }); 
                },
                mute: function() { if (widget && widgetReady) widget.setVolume(0); },
                unmute: function() { if (widget && widgetReady) widget.setVolume(100); }
            });
        });
        
        // Listen to widget events for state tracking
        widget.bind(SC.Widget.Events.PLAY, function() {
            isCurrentlyPlaying = true;
            $player.addClass('playing controls-active'); // Enable custom controls once playing
            updatePlayButton($player, 'pause');
            var $bigPlay = $player.find('.mindful-media-big-play-btn');
            $bigPlay.addClass('hidden').css('display', 'none');
            $player.trigger('playing');
        });
        
        widget.bind(SC.Widget.Events.PAUSE, function() {
            isCurrentlyPlaying = false;
            $player.removeClass('playing');
            updatePlayButton($player, 'play');
            var $bigPlay = $player.find('.mindful-media-big-play-btn');
            $bigPlay.removeClass('hidden').css('display', 'flex');
            $player.trigger('pause');
        });
        
        widget.bind(SC.Widget.Events.FINISH, function() {
            isCurrentlyPlaying = false;
            $player.removeClass('playing');
            updatePlayButton($player, 'play');
            var $bigPlay = $player.find('.mindful-media-big-play-btn');
            $bigPlay.removeClass('hidden').css('display', 'flex');
            $player.trigger('ended');
        });
        
        // Click handlers for image container and big play button
        $player.find('.mindful-media-image-container').off('click.soundcloud').on('click.soundcloud', function(e) {
            e.preventDefault();
            e.stopPropagation();
            togglePlayPause();
        });
        
        // Override big play button for SoundCloud specifically
        $player.find('.mindful-media-big-play-btn').off('click.soundcloud').on('click.soundcloud', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (widgetReady) {
                widget.play();
            }
        });
    }
    
    /**
     * Archive.org Player Controller
     */
    function initArchivePlayer($player) {
        var archiveId = $player.data('archive-id');
        var playerId = 'archive-player-' + archiveId;
        
        // Archive.org uses standard HTML5 audio/video in their iframe
        // We'll treat it similar to HTML5 but access through iframe
        var $iframe = $player.find('iframe');
        var iframe = $iframe[0];
        var $loading = $player.find('.mindful-media-archive-loading');
        
        if (!iframe) {
            console.error('Archive.org iframe not found');
            return;
        }
        
        // Hide loading indicator when iframe loads
        $iframe.on('load', function() {
            $loading.fadeOut(300);
        });
        
        // Fallback: hide loading after timeout (in case load event doesn't fire)
        setTimeout(function() {
            $loading.fadeOut(300);
        }, 8000);
        
        // Try to access the media element inside the iframe
        try {
            var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            var $media = $(iframeDoc).find('video, audio');
            var media = $media[0];
            
            if (media) {
                // Setup unified controls if we can access the media
                setupUnifiedControls($player, {
                    play: function() { media.play(); },
                    pause: function() { media.pause(); },
                    seek: function(seconds) { media.currentTime = seconds; },
                    setVolume: function(volume) { media.volume = volume / 100; },
                    getVolume: function() { return media.volume * 100; },
                    getDuration: function() { return media.duration || 0; },
                    getCurrentTime: function() { return media.currentTime || 0; },
                    mute: function() { media.muted = true; },
                    unmute: function() { media.muted = false; }
                });
                
                $media.on('play', function() {
                    $player.addClass('playing');
                    updatePlayButton($player, 'pause');
                });
                
                $media.on('pause', function() {
                    $player.removeClass('playing');
                    updatePlayButton($player, 'play');
                });
            } else {
                console.warn('Archive.org: Could not find media element in iframe');
            }
        } catch (e) {
            // CORS prevents accessing iframe contents - this is expected
            // Archive.org embed handles its own controls, so this is fine
        }
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        // Move modal to body to escape CSS transform containment issues
        // This ensures position:fixed works correctly regardless of parent transforms
        var $modal = $('#mindful-media-inline-player');
        if ($modal.length && $modal.parent()[0] !== document.body) {
            $modal.appendTo('body');
        }
        
        initInlinePlayer();
        initUnifiedPlayers();
    });
    
    // Note: Embed thumbnails now use .mindful-media-play-inline class
    // which is handled by initInlinePlayer()
    
    // Handle browse navigation clicks (dynamic filtering)
    $(document).on('click', '.mindful-media-browse-nav-item', function(e) {
        e.preventDefault();
        
        var $nav = $(this);
        var section = $nav.data('section');
        var $browse = $nav.closest('.mindful-media-browse');
        var $sections = $browse.find('.mindful-media-browse-sections');
        var $allWrappers = $sections.find('.mindful-media-browse-section-wrapper');
        
        // Update active state
        $browse.find('.mindful-media-browse-nav-item').removeClass('active');
        $nav.addClass('active');
        
        // Show/hide sections and toggle between card/video views
        if (section === 'all') {
            // HOME tab: Show ALL sections with CARD view (discovery mode)
            $allWrappers.each(function() {
                var $wrapper = $(this);
                $wrapper.removeAttr('style').show();
                // Show cards view, hide videos view
                $wrapper.find('.mm-browse-cards-view').show();
                $wrapper.find('.mm-browse-videos-view').hide();
            });
        } else {
            // Individual tab: Show only that section with VIDEO ROWS view
            $allWrappers.each(function() {
                var $wrapper = $(this);
                var sectionId = $wrapper.data('section-id');
                
                if (sectionId === section) {
                    // Show this section with VIDEO ROWS view
                    $wrapper.removeAttr('style').show();
                    $wrapper.find('.mm-browse-cards-view').hide();
                    $wrapper.find('.mm-browse-videos-view').show();
                } else {
                    // Hide other sections
                    $wrapper.hide();
                }
            });
        }
        
        // Force reflow and reinitialize sliders after showing sections
        setTimeout(function() {
            // Force browser to recalculate layouts
            $browse.find('.mm-slider-track').each(function() {
                this.offsetHeight; // Force reflow
            });
            initNetflixSliders();
        }, 100);
        
        // Smooth scroll to browse section
        $('html, body').animate({
            scrollTop: $browse.offset().top - 100
        }, 400);
        
    });
    
    /**
     * ============================================
     * NETFLIX-STYLE HORIZONTAL SLIDERS
     * ============================================
     */
    
    function initNetflixSliders() {
        $('.mm-slider-container').each(function() {
            var $container = $(this);
            var $track = $container.find('.mm-slider-track');
            var $prevBtn = $container.find('.mm-slider-nav--prev');
            var $nextBtn = $container.find('.mm-slider-nav--next');
            
            if (!$track.length) return;
            
            // Calculate scroll amount (width of visible area)
            function getScrollAmount() {
                return $track.width() * 0.8;
            }
            
            // Update nav button states
            function updateNavButtons() {
                var scrollLeft = $track.scrollLeft();
                var maxScroll = $track[0].scrollWidth - $track[0].clientWidth;
                
                $prevBtn.prop('disabled', scrollLeft <= 0);
                $nextBtn.prop('disabled', scrollLeft >= maxScroll - 5);
            }
            
            // Navigation button clicks
            $prevBtn.on('click', function() {
                $track.animate({
                    scrollLeft: $track.scrollLeft() - getScrollAmount()
                }, 300);
            });
            
            $nextBtn.on('click', function() {
                $track.animate({
                    scrollLeft: $track.scrollLeft() + getScrollAmount()
                }, 300);
            });
            
            // Update buttons on scroll
            $track.on('scroll', function() {
                updateNavButtons();
            });
            
            // Initial state
            updateNavButtons();
            
            // Touch/Swipe support
            var touchStartX = 0;
            var touchEndX = 0;
            var isSwiping = false;
            
            $track.on('touchstart', function(e) {
                touchStartX = e.originalEvent.touches[0].clientX;
                isSwiping = true;
            });
            
            $track.on('touchmove', function(e) {
                if (!isSwiping) return;
                touchEndX = e.originalEvent.touches[0].clientX;
            });
            
            $track.on('touchend', function() {
                if (!isSwiping) return;
                isSwiping = false;
                
                var diff = touchStartX - touchEndX;
                var threshold = 50;
                
                if (Math.abs(diff) > threshold) {
                    if (diff > 0) {
                        // Swipe left - go next
                        $nextBtn.trigger('click');
                    } else {
                        // Swipe right - go prev
                        $prevBtn.trigger('click');
                    }
                }
            });
        });
    }
    
    /**
     * ============================================
     * LAZY LOADING
     * ============================================
     */
    
    function initLazyLoading() {
        // Use Intersection Observer if available
        if ('IntersectionObserver' in window) {
            var imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var $img = $(entry.target);
                        var src = $img.data('src');
                        
                        if (src) {
                            $img.attr('src', src);
                            $img.on('load', function() {
                                $img.addClass('loaded');
                            });
                            $img.removeAttr('data-src');
                        }
                        
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: '100px 0px',
                threshold: 0.01
            });
            
            // Observe all lazy images
            $('.mm-lazy-image').each(function() {
                imageObserver.observe(this);
            });
        } else {
            // Fallback: load all images immediately
            $('.mm-lazy-image').each(function() {
                var $img = $(this);
                var src = $img.data('src');
                if (src) {
                    $img.attr('src', src).addClass('loaded');
                }
            });
        }
    }
    
    /**
     * ============================================
     * KEYBOARD NAVIGATION
     * ============================================
     */
    
    function initKeyboardNavigation() {
        $(document).on('keydown', function(e) {
            var $inlinePlayer = $('#mindful-media-inline-player');
            var isModalOpen = $inlinePlayer.hasClass('active');
            
            // Only handle keys when modal is open
            if (!isModalOpen) return;
            
            switch (e.key) {
                case 'Escape':
                    // Close modal (already handled, but ensure it works)
                    $('.mindful-media-inline-close').trigger('click');
                    break;
                    
                case ' ':
                case 'Spacebar':
                    // Play/pause video
                    e.preventDefault();
                    var $player = $inlinePlayer.find('.mindful-media-unified-player');
                    if ($player.length) {
                        var $playBtn = $player.find('.mindful-media-play-btn');
                        if ($playBtn.length) {
                            $playBtn.trigger('click');
                        }
                    }
                    break;
                    
                case 'ArrowLeft':
                    // Previous item in playlist
                    if (e.ctrlKey || e.metaKey) {
                        e.preventDefault();
                        var $activeItem = $('.mindful-media-playlist-item.active');
                        var $prevItem = $activeItem.prev('.mindful-media-playlist-item');
                        if ($prevItem.length) {
                            $prevItem.trigger('click');
                        }
                    }
                    break;
                    
                case 'ArrowRight':
                    // Next item in playlist
                    if (e.ctrlKey || e.metaKey) {
                        e.preventDefault();
                        var $activeItem = $('.mindful-media-playlist-item.active');
                        var $nextItem = $activeItem.next('.mindful-media-playlist-item');
                        if ($nextItem.length) {
                            $nextItem.trigger('click');
                        }
                    }
                    break;
            }
        });
        
        // Slider keyboard navigation (when slider is focused)
        $(document).on('keydown', '.mm-slider-track', function(e) {
            var $track = $(this);
            var scrollAmount = $track.width() * 0.5;
            
            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                $track.animate({ scrollLeft: $track.scrollLeft() - scrollAmount }, 200);
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                $track.animate({ scrollLeft: $track.scrollLeft() + scrollAmount }, 200);
            }
        });
        
        // Make slider tracks focusable
        $('.mm-slider-track').attr('tabindex', '0');
    }
    
})(jQuery); 