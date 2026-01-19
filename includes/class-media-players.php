<?php
/**
 * Media Players Class
 * Handles detection and rendering of various media sources
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MindfulMedia_Players {
    
    public function __construct() {
        // Add filter to render player in single template
        add_filter('mindful_media_render_player', array($this, 'render_player'), 10, 2);
    }
    
    /**
     * Detect media source from URL
     * 
     * @param string $url The media URL
     * @return string The detected source type
     */
    public function detect_media_source($url) {
        if (empty($url)) {
            return 'none';
        }
        
        // YouTube detection
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $url)) {
            return 'youtube';
        }
        
        // Vimeo detection - support various URL formats
        if (preg_match('/vimeo\.com/i', $url)) {
            return 'vimeo';
        }
        
        // SoundCloud detection
        if (strpos($url, 'soundcloud.com') !== false) {
            return 'soundcloud';
        }
        
        // Archive.org detection
        if (strpos($url, 'archive.org') !== false) {
            return 'archive';
        }
        
        // Check for direct media file extensions
        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        if (in_array(strtolower($extension), array('mp4', 'webm', 'ogg', 'ogv'))) {
            return 'video';
        }
        if (in_array(strtolower($extension), array('mp3', 'wav', 'ogg', 'oga', 'm4a'))) {
            return 'audio';
        }
        
        return 'unknown';
    }
    
    /**
     * Extract media ID from URL
     * 
     * @param string $url The media URL
     * @param string $source The media source type
     * @return string The extracted ID
     */
    public function extract_media_id($url, $source) {
        switch ($source) {
            case 'youtube':
                if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $url, $matches)) {
                    return $matches[1];
                }
                break;
                
            case 'vimeo':
                // Extract video ID from various Vimeo URL formats
                
                // Pattern 1: Private/unlisted videos with hash in path: vimeo.com/123456789/abc123
                if (preg_match('/vimeo\.com\/(\d+)\/([a-f0-9]+)/i', $url, $matches)) {
                    return $matches[1] . '?h=' . $matches[2];
                }
                
                // Pattern 2: Player embed URLs with hash: player.vimeo.com/video/123456789?h=abc123
                if (preg_match('/player\.vimeo\.com\/video\/(\d+)\?h=([a-f0-9]+)/i', $url, $matches)) {
                    return $matches[1] . '?h=' . $matches[2];
                }
                
                // Pattern 3: URLs with hash as query param: vimeo.com/123456789?h=abc123
                if (preg_match('/vimeo\.com\/(\d+)\?.*h=([a-f0-9]+)/i', $url, $matches)) {
                    return $matches[1] . '?h=' . $matches[2];
                }
                
                // Pattern 4: Standard vimeo.com/123456789
                if (preg_match('/vimeo\.com\/(\d+)/i', $url, $matches)) {
                    return $matches[1];
                }
                
                // Pattern 5: player.vimeo.com/video/123456789 (public)
                if (preg_match('/player\.vimeo\.com\/video\/(\d+)/i', $url, $matches)) {
                    return $matches[1];
                }
                break;
        }
        
        return '';
    }
    
    /**
     * Render media player based on source
     * 
     * @param string $url The media URL
     * @param array $atts Additional attributes
     * @return string The rendered player HTML
     */
    public function render_player($url, $atts = array()) {
        if (empty($url)) {
            return '';
        }
        
        // Default attributes
        $atts = wp_parse_args($atts, array(
            'width' => '100%',
            'height' => 'auto',
            'autoplay' => false,
            'controls' => true,
            'class' => '',
            'source' => '' // Allow manual source override
        ));
        
        // Check if custom embed is provided
        if (!empty($atts['custom_embed'])) {
            return $this->render_custom_embed($atts['custom_embed'], $atts);
        }
        
        // Detect source (use manual override if provided)
        $source = !empty($atts['source']) ? $atts['source'] : $this->detect_media_source($url);
        
        // Get plugin settings for colors
        $settings = get_option('mindful_media_settings', array());
        $primary_color = isset($settings['primary_color']) ? $settings['primary_color'] : '#8B0000';
        $secondary_color = isset($settings['secondary_color']) ? $settings['secondary_color'] : '#DAA520';
        
        // Add colors to attributes
        $atts['primary_color'] = $primary_color;
        $atts['secondary_color'] = $secondary_color;
        
        // Route to appropriate renderer
        $player_html = '';
        switch ($source) {
            case 'youtube':
                $player_html = $this->render_youtube_player($url, $atts);
                break;
                
            case 'vimeo':
                $player_html = $this->render_vimeo_player($url, $atts);
                break;
                
            case 'soundcloud':
                $player_html = $this->render_soundcloud_player($url, $atts);
                break;
                
            case 'archive':
                $player_html = $this->render_archive_player($url, $atts);
                break;
                
            case 'video':
                $player_html = $this->render_native_video($url, $atts);
                break;
                
            case 'audio':
                $player_html = $this->render_native_audio($url, $atts);
                break;
                
            default:
                $player_html = $this->render_fallback($url, $atts);
        }
        
        /**
         * Filter the player HTML output.
         *
         * @since 2.8.0
         * @param string $player_html The player HTML.
         * @param string $url         The media URL.
         * @param string $source      The detected media source type.
         * @param array  $atts        The player attributes.
         */
        return apply_filters('mindful_media_player_html', $player_html, $url, $source, $atts);
    }
    
    /**
     * Generate unified player controls HTML
     * 
     * @return string The controls HTML
     */
    private function get_unified_controls() {
        $controls = '<div class="mindful-media-custom-controls">';
        
        // Progress bar
        $controls .= '<div class="mindful-media-progress-container">';
        $controls .= '<div class="mindful-media-progress-buffered" style="width: 0%;"></div>';
        $controls .= '<div class="mindful-media-progress-bar" style="width: 0%;">';
        $controls .= '<div class="mindful-media-progress-handle"></div>';
        $controls .= '</div>';
        $controls .= '</div>';
        
        // Control buttons row
        $controls .= '<div class="mindful-media-controls-row">';
        
        // Play/Pause button
        $controls .= '<button class="mindful-media-play-btn" aria-label="Play">';
        $controls .= '<svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>';
        $controls .= '</button>';
        
        // Volume controls
        $controls .= '<div class="mindful-media-volume-container">';
        $controls .= '<button class="mindful-media-volume-btn" aria-label="Mute">';
        $controls .= '<svg viewBox="0 0 24 24"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z"/></svg>';
        $controls .= '</button>';
        $controls .= '<div class="mindful-media-volume-slider">';
        $controls .= '<input type="range" min="0" max="100" value="100" aria-label="Volume">';
        $controls .= '</div>';
        $controls .= '</div>';
        
        // Time display
        $controls .= '<div class="mindful-media-time-display">';
        $controls .= '<span class="mindful-media-current-time">0:00</span>';
        $controls .= ' / ';
        $controls .= '<span class="mindful-media-duration">0:00</span>';
        $controls .= '</div>';
        
        // Spacer
        $controls .= '<div class="mindful-media-controls-spacer"></div>';
        
        // Fullscreen button
        $controls .= '<button class="mindful-media-fullscreen-btn" aria-label="Fullscreen">';
        $controls .= '<svg viewBox="0 0 24 24"><path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/></svg>';
        $controls .= '</button>';
        
        $controls .= '</div>'; // End controls-row
        $controls .= '</div>'; // End custom-controls
        
        return $controls;
    }
    
    /**
     * Render YouTube player
     */
    private function render_youtube_player($url, $atts) {
        $video_id = $this->extract_media_id($url, 'youtube');
        
        if (empty($video_id)) {
            return $this->render_fallback($url, $atts);
        }
        
        // Build YouTube parameters - Enable API for custom controls
        $params = array(
            'rel' => 0,
            'modestbranding' => 1,
            'showinfo' => 0,
            'controls' => 0, // Hide native controls
            'enablejsapi' => 1, // Enable JavaScript API
            'playsinline' => 1,
            'origin' => home_url() // Required for IFrame API to work properly
        );
        
        if ($atts['autoplay']) {
            $params['autoplay'] = 1;
            $params['mute'] = 1; // Autoplay requires mute
        }
        
        // Unified player wrapper - Use div placeholder for YouTube API to create iframe
        // This ensures proper API control and event firing
        $output = '<div class="mindful-media-unified-player youtube-player ' . esc_attr($atts['class']) . '" data-player-type="youtube" data-video-id="' . esc_attr($video_id) . '">';
        $output .= '<div class="mindful-media-player-aspect-ratio">';
        // Use a div placeholder - JavaScript will replace this with an iframe via YouTube API
        $output .= '<div class="mindful-media-player" id="youtube-player-' . esc_attr($video_id) . '"></div>';
        $output .= '</div>';
        
        // Add big play button
        $output .= '<div class="mindful-media-big-play-btn"></div>';
        
        // Add loading spinner
        $output .= '<div class="mindful-media-loading-spinner" style="display: none;"></div>';
        
        // Add unified controls
        $output .= $this->get_unified_controls();
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Render Vimeo player
     * Supports unlisted videos via URL hash (e.g., vimeo.com/123456789/abc123)
     */
    private function render_vimeo_player($url, $atts) {
        $video_id = $this->extract_media_id($url, 'vimeo');
        
        if (empty($video_id)) {
            return $this->render_fallback($url, $atts);
        }
        
        // Convert hex color to RGB for Vimeo
        $color_hex = str_replace('#', '', $atts['primary_color']);
        
        // Build Vimeo parameters - Show native controls for reliable playback
        $params = array(
            'color' => $color_hex,
            'byline' => 0,
            'portrait' => 0,
            'title' => 0,
            'sidedock' => 0,
            'transparent' => 0
        );
        
        if ($atts['autoplay']) {
            $params['autoplay'] = 1;
            $params['muted'] = 1; // Autoplay requires mute
        }
        
        $param_string = http_build_query($params);
        
        // Video ID may include hash for unlisted videos (e.g., "123456789?h=abc123")
        // Use appropriate separator based on whether video_id already has query params
        $separator = (strpos($video_id, '?') !== false) ? '&' : '?';
        $embed_url = "https://player.vimeo.com/video/{$video_id}{$separator}{$param_string}";
        
        // Simple player wrapper with native Vimeo controls
        $output = '<div class="mindful-media-unified-player vimeo-player vimeo-native ' . esc_attr($atts['class']) . '" data-player-type="vimeo" data-video-id="' . esc_attr($video_id) . '">';
        $output .= '<div class="mindful-media-player-aspect-ratio">';
        $output .= '<iframe class="mindful-media-player" ';
        $output .= 'id="vimeo-player-' . esc_attr(preg_replace('/[^0-9]/', '', $video_id)) . '" ';
        $output .= 'src="' . esc_url($embed_url) . '" ';
        $output .= 'frameborder="0" ';
        $output .= 'allow="autoplay; fullscreen; picture-in-picture" ';
        $output .= 'allowfullscreen>';
        $output .= '</iframe>';
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Render SoundCloud player with unified controls
     */
    private function render_soundcloud_player($url, $atts) {
        // Convert hex color to RGB for SoundCloud
        $color_hex = str_replace('#', '', $atts['primary_color']);
        
        // Build SoundCloud parameters
        $params = array(
            'url' => $url,
            'color' => $color_hex,
            'auto_play' => 'false',
            'hide_related' => 'true',
            'show_comments' => 'false',
            'show_user' => 'false',
            'show_reposts' => 'false',
            'show_teaser' => 'false'
        );
        
        $param_string = http_build_query($params);
        $embed_url = "https://w.soundcloud.com/player/?{$param_string}";
        
        // Generate unique ID from URL
        $sound_id = md5($url);
        
        // Unified player wrapper with custom controls
        $output = '<div class="mindful-media-unified-player soundcloud-player ' . esc_attr($atts['class']) . '" data-player-type="soundcloud" data-sound-id="' . esc_attr($sound_id) . '">';
        
        // Featured image background if available
        if (!empty($atts['featured_image'])) {
            $output .= '<div class="mindful-media-image-container">';
            $output .= '<img src="' . esc_url($atts['featured_image']) . '" alt="' . esc_attr($atts['post_title'] ?? '') . '" class="mindful-media-main-image">';
            $output .= '</div>';
        }
        
        // Hidden iframe for API control (tiny size, invisible, but functional)
        $output .= '<div class="mindful-media-player-aspect-ratio">';
        $output .= '<iframe class="mindful-media-player soundcloud-iframe" ';
        $output .= 'id="soundcloud-player-' . esc_attr($sound_id) . '" ';
        $output .= 'width="100%" ';
        $output .= 'height="100%" ';
        $output .= 'scrolling="no" ';
        $output .= 'frameborder="no" ';
        $output .= 'allow="autoplay" ';
        $output .= 'src="' . esc_url($embed_url) . '">';
        $output .= '</iframe>';
        $output .= '</div>';
        
        // Add big play button
        $output .= '<div class="mindful-media-big-play-btn"></div>';
        
        // Add loading spinner
        $output .= '<div class="mindful-media-loading-spinner" style="display: none;"></div>';
        
        // Add unified controls
        $output .= $this->get_unified_controls();
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Render Archive.org player - try to use direct audio file with custom controls
     */
    private function render_archive_player($url, $atts) {
        // Extract identifier from Archive.org URL
        // Matches: archive.org/details/identifier or archive.org/details/identifier/filename.ext
        $identifier = '';
        
        if (preg_match('/archive\.org\/(?:details|embed)\/([^\/\?]+)/i', $url, $matches)) {
            $identifier = $matches[1];
        }
        
        if (empty($identifier)) {
            return $this->render_fallback($url, $atts);
        }
        
        // Try to get direct audio file URL from Archive.org
        // Archive.org provides direct file access at: https://archive.org/download/{identifier}/{filename}
        // We'll try to fetch metadata to find the audio file
        $audio_file_url = null;
        $filename_from_url = '';
        
        // Check if URL includes a specific filename
        if (preg_match('/archive\.org\/details\/[^\/]+\/([^\/\?]+)/i', $url, $filename_matches)) {
            $filename_from_url = $filename_matches[1];
        }
        
        // Try to get cached metadata first (1 hour cache)
        $cache_key = 'mm_archive_' . md5($identifier);
        $metadata = get_transient($cache_key);
        
        if ($metadata === false) {
            // Try to get metadata (with short timeout to not slow down page load)
            $metadata_url = "https://archive.org/metadata/{$identifier}";
            $response = wp_remote_get($metadata_url, array(
                'timeout' => 3,
                'sslverify' => false
            ));
            
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = wp_remote_retrieve_body($response);
                $metadata = json_decode($body, true);
                
                // Cache for 1 hour
                if ($metadata) {
                    set_transient($cache_key, $metadata, HOUR_IN_SECONDS);
                }
            }
        }
        
        if ($metadata && isset($metadata['files'])) {
            // If a specific filename was in the URL, try to find the MP3 version of it
            if (!empty($filename_from_url)) {
                $basename = pathinfo($filename_from_url, PATHINFO_FILENAME);
                
                // Look for MP3 version of this specific file
                foreach ($metadata['files'] as $file) {
                    $file_name = $file['name'] ?? '';
                    $file_basename = pathinfo($file_name, PATHINFO_FILENAME);
                    $format = strtolower($file['format'] ?? '');
                    
                    if ($file_basename === $basename && in_array($format, ['vbr mp3', '128kbps mp3', 'mp3'])) {
                        $audio_file_url = "https://archive.org/download/{$identifier}/" . rawurlencode($file_name);
                        break;
                    }
                }
            }
            
            // If no specific file found, look for any audio file (mp3, ogg, flac, etc.)
            if (!$audio_file_url) {
                foreach ($metadata['files'] as $file) {
                    $filename = $file['name'] ?? '';
                    $format = strtolower($file['format'] ?? '');
                    
                    // Prefer MP3, then OGG, then other audio formats
                    if (in_array($format, ['vbr mp3', '128kbps mp3', 'mp3'])) {
                        $audio_file_url = "https://archive.org/download/{$identifier}/" . rawurlencode($filename);
                        break; // Prefer MP3
                    } else if (!$audio_file_url && in_array($format, ['ogg vorbis', 'flac'])) {
                        $audio_file_url = "https://archive.org/download/{$identifier}/" . rawurlencode($filename);
                    }
                }
            }
        }
        
        // If we found a direct audio file, use our HTML5 player!
        if ($audio_file_url) {
            return $this->render_native_audio($audio_file_url, $atts);
        }
        
        // Fallback: use Archive.org's native embed player
        $embed_url = "https://archive.org/embed/{$identifier}";
        
        $output = '<div class="mindful-media-unified-player archive-player ' . esc_attr($atts['class']) . '" data-player-type="archive" data-archive-id="' . esc_attr($identifier) . '">';
        $output .= '<div class="mindful-media-player-aspect-ratio">';
        
        // Loading overlay - shows while iframe is loading
        $output .= '<div class="mindful-media-archive-loading">';
        $output .= '<div class="mindful-media-archive-spinner"></div>';
        $output .= '<p>' . __('Loading from Archive.org...', 'mindful-media') . '</p>';
        $output .= '</div>';
        
        $output .= '<iframe class="mindful-media-player archive-iframe" ';
        $output .= 'id="archive-player-' . esc_attr($identifier) . '" ';
        $output .= 'src="' . esc_url($embed_url) . '" ';
        $output .= 'width="100%" ';
        $output .= 'height="480" ';
        $output .= 'frameborder="0" ';
        $output .= 'webkitallowfullscreen="true" ';
        $output .= 'mozallowfullscreen="true" ';
        $output .= 'allowfullscreen ';
        $output .= 'onload="this.previousElementSibling.style.display=\'none\'">';
        $output .= '</iframe>';
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Render native HTML5 video player
     */
    private function render_native_video($url, $atts) {
        // Determine video type
        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        $mime_type = 'video/mp4';
        
        switch (strtolower($extension)) {
            case 'webm':
                $mime_type = 'video/webm';
                break;
            case 'ogg':
            case 'ogv':
                $mime_type = 'video/ogg';
                break;
        }
        
        // Unified player wrapper
        $output = '<div class="mindful-media-unified-player native-video-player ' . esc_attr($atts['class']) . '" data-player-type="html5-video">';
        $output .= '<div class="mindful-media-player-aspect-ratio">';
        $output .= '<video class="mindful-media-player native-player" ';
        $output .= 'playsinline ';
        $output .= 'preload="metadata" ';
        $output .= 'webkit-playsinline ';
        $output .= 'crossorigin="anonymous">';
        $output .= '<source src="' . esc_url($url) . '" type="' . esc_attr($mime_type) . '">';
        $output .= __('Your browser does not support the video tag.', 'mindful-media');
        $output .= '</video>';
        $output .= '</div>';
        
        // Add big play button
        $output .= '<div class="mindful-media-big-play-btn"></div>';
        
        // Add loading spinner
        $output .= '<div class="mindful-media-loading-spinner" style="display: none;"></div>';
        
        // Add unified controls
        $output .= $this->get_unified_controls();
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Render native HTML5 audio player
     */
    private function render_native_audio($url, $atts) {
        // Determine audio type
        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        $mime_type = 'audio/mpeg';
        
        switch (strtolower($extension)) {
            case 'wav':
                $mime_type = 'audio/wav';
                break;
            case 'ogg':
            case 'oga':
                $mime_type = 'audio/ogg';
                break;
            case 'm4a':
                $mime_type = 'audio/mp4';
                break;
        }
        
        // Unified player wrapper - audio with featured image if available
        $output = '<div class="mindful-media-unified-player native-audio-player ' . esc_attr($atts['class']) . '" data-player-type="html5-audio">';
        
        // If featured image is available, show it
        if (!empty($atts['featured_image'])) {
            $output .= '<div class="mindful-media-image-container">';
            $output .= '<img src="' . esc_url($atts['featured_image']) . '" alt="' . esc_attr($atts['post_title'] ?? '') . '" class="mindful-media-main-image">';
            $output .= '</div>';
        }
        
        $output .= '<audio class="mindful-media-player native-player" ';
        $output .= 'preload="metadata" ';
        $output .= 'crossorigin="anonymous">';
        $output .= '<source src="' . esc_url($url) . '" type="' . esc_attr($mime_type) . '">';
        $output .= __('Your browser does not support the audio tag.', 'mindful-media');
        $output .= '</audio>';
        
        // Add big play button
        $output .= '<div class="mindful-media-big-play-btn"></div>';
        
        // Add loading spinner
        $output .= '<div class="mindful-media-loading-spinner" style="display: none;"></div>';
        
        // Add unified controls
        $output .= $this->get_unified_controls();
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Render fallback for unknown sources
     */
    private function render_fallback($url, $atts) {
        $output = '<div class="mindful-media-player-wrapper fallback-player ' . esc_attr($atts['class']) . '">';
        $output .= '<div class="mindful-media-player-fallback">';
        $output .= '<p>' . __('Media player not available for this source.', 'mindful-media') . '</p>';
        $output .= '<a href="' . esc_url($url) . '" target="_blank" rel="noopener" class="mindful-media-external-link">';
        $output .= __('View Media', 'mindful-media') . ' →';
        $output .= '</a>';
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Render custom embed code
     * 
     * @param string $embed_code The custom embed code
     * @param array $atts Additional attributes
     * @return string The rendered custom embed HTML
     */
    private function render_custom_embed($embed_code, $atts) {
        // Sanitize embed code (allow iframe and script tags)
        $allowed_tags = array(
            'iframe' => array(
                'src' => array(),
                'width' => array(),
                'height' => array(),
                'frameborder' => array(),
                'allow' => array(),
                'allowfullscreen' => array(),
                'style' => array(),
                'title' => array(),
                'class' => array(),
            ),
            'script' => array(
                'src' => array(),
                'type' => array(),
            ),
        );
        
        $safe_embed = wp_kses($embed_code, $allowed_tags);
        
        $output = '<div class="mindful-media-player-wrapper custom-embed-player ' . esc_attr($atts['class']) . '">';
        $output .= '<div class="mindful-media-player-aspect-ratio">';
        $output .= $safe_embed;
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Get media source label for admin display
     */
    public function get_source_label($source) {
        $labels = array(
            'youtube' => 'YouTube',
            'vimeo' => 'Vimeo',
            'soundcloud' => 'SoundCloud',
            'archive' => 'Archive.org',
            'video' => 'Video File',
            'audio' => 'Audio File',
            'unknown' => 'Unknown',
            'none' => 'None'
        );
        
        return isset($labels[$source]) ? $labels[$source] : 'Unknown';
    }
}

