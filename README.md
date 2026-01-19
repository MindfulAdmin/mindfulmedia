# MindfulMedia WordPress Plugin

**Version:** 2.7.0  
**Author:** [Mindful Design](https://mindfuldesign.me)  
**Website:** [mindfuldesign.me](https://mindfuldesign.me)

---

A comprehensive media management system with YouTube-inspired styling, Netflix-style sliders, playlists, password protection, and customizable archives.

## Features

### Core Functionality
- **Custom Post Type**: Dedicated `mindful_media` post type for managing media items
- **Multiple Taxonomies**: Categories, Types (Audio/Video), Topics, Teachers, Playlists (Series)
- **YouTube-Style Card Grid**: Responsive card layouts with 16:9 thumbnails
- **Netflix-Style Sliders**: Horizontal scrolling rows with smooth navigation
- **Modal Player**: Dark-themed modal player with playlist sidebar
- **Password Protection**: Protect individual items or entire playlists
- **Multiple Player Support**: YouTube, Vimeo, SoundCloud, Archive.org
- **Search Functionality**: Dynamic search across all archives and browse pages
- **Gutenberg Blocks**: 4 blocks (Browse, Archive, Embed, Category Row)
- **Elementor Widgets**: 4 widgets matching the Gutenberg blocks
- **Shortcodes**: 5 shortcodes for flexible content placement

### Shortcodes
- `[mindful_media_archive]` - Media grid with filter chips
- `[mindful_media id="123"]` - Single media embed
- `[mindful_media_browse]` - Full browse experience with navigation
- `[mindful_media_row]` - Netflix-style category row
- `[mindful_media_taxonomy_archive]` - Full taxonomy archive page

### Perfect For
- Online course platforms
- Dharma/teaching centers
- Podcast archives
- Video libraries
- Music collections
- Educational institutions

## Installation

1. Upload the `mindfulmedia` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to MindfulMedia > Getting Started for setup guidance
4. Add your first media item via MindfulMedia > Add New
5. Use shortcodes, blocks, or widgets to display your media

## Frequently Asked Questions

### What video platforms are supported?
MindfulMedia supports YouTube, Vimeo (including unlisted videos), SoundCloud, and Archive.org.

### Can I password protect content?
Yes. You can password protect individual media items or entire playlists. Protected content remains hidden until the password is entered.

### Does it work with Elementor?
Yes. MindfulMedia includes 4 Elementor widgets that match the Gutenberg blocks functionality.

### Can I customize what appears in the browse page?
Yes. Go to MindfulMedia > Settings to control which sections appear (Teachers, Topics, Playlists, Categories, Media Types) and configure filter visibility.

### Does it support playlists?
Yes. Create playlists (called Series) with parent/child hierarchy for organizing content into modules.

## Changelog

### 2.7.0
- Getting Started page redesigned with WooCommerce-style clean interface
- Updated color scheme to gold matching Mindful Design branding
- Single branding header (removed duplicate headers)
- Simplified 3-column layout for quick setup guidance

### 2.6.5
- Security: Added nonce verification to AJAX handlers
- Player: Vimeo now uses native controls for reliable playback
- Player: Enhanced Vimeo unlisted video URL support
- Player: Added loading indicator for Archive.org player
- Settings: Added browse page section visibility controls
- UI: Fixed search text overlapping search icon
- UI: Fixed underlined titles in browse block

### 2.6.0
- Added YouTube-style search bar to all archive and browse pages
- Search combines with filter chips for refined results
- Client-side filtering for taxonomy pages
- AJAX-based search for main archive
- Clear button and "No results found" messaging
- 200-300ms debounce for responsive performance

### 2.5.2
- Added category-specific SVG icons
- Refreshed navigation tab icons (YouTube-inspired)
- Fixed playlist page layout issues with theme conflicts
- Improved settings descriptions

### 2.5.1
- Browse block individual tabs now show Netflix-style video rows
- Fixed full-width issues in Elementor containers
- Volume control now visible by default
- SoundCloud player improvements
- Modal positioning fixes

### 2.5.0
- Browse block redesign with Netflix-style rows
- New taxonomy templates for teachers, topics, categories
- Slider arrow redesign with transparent backgrounds
- Multiple player bug fixes

### 2.4.0
- New `[mindful_media_taxonomy_archive]` shortcode
- Embed size options (small, medium, large, full)
- Slider navigation improvements
- Password protection fixes

### 2.3.0
- Slider arrows now 90% height (Netflix-style)
- Modal player content fixes
- Audio player improvements
- Archive Display settings for filter chips

### 2.2.0
- Major Netflix-style redesign
- Horizontal sliders for all browse sections
- New `[mindful_media_row]` shortcode
- Gutenberg Category Row block
- Elementor Category Row widget
- Keyboard navigation and mobile swipe gestures
- Skeleton loading animations

### 2.1.25
- Production release with security hardening
- Removed debug logging
- Enhanced import/export security
- Mobile modal responsiveness fixes

## Support
For support, email [support@mindfuldesign.me](mailto:support@mindfuldesign.me) or visit [mindfuldesign.me](https://mindfuldesign.me).

## License
GPL v2 or later
