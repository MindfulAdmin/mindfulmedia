# MindfulMedia Plugin - Development Guide

**Version:** 2.13.0  
**Last Updated:** January 27, 2026

---

## Overview

MindfulMedia is a WordPress media management plugin with YouTube-inspired styling. It provides:
- Custom post type `mindful_media` for media items
- Taxonomies: Categories, Types (Audio/Video), Topics, Teachers, Playlists (Series), Duration
- Modal player with playlist sidebar
- Password protection for content
- Responsive YouTube-style card layouts
- User engagement features (likes, comments, subscriptions)
- MemberPress integration for membership-gated content
- My Library page with watch history and continue watching

---

## Design Philosophy

### Contextual Theming (like YouTube)
- **Browse/Archive pages**: Light theme (white/gray backgrounds)
- **Modal Player**: Dark theme (black backgrounds)
- **Single Media Pages**: Dark theme (black backgrounds, like modal player)
- **Taxonomy Archive Pages**: Light theme (playlists, teachers)

### Card Design (YouTube Style)
- 16:9 aspect ratio thumbnails
- Rounded corners (12px)
- Duration badge (bottom right of thumbnail)
- Play overlay on hover
- Clean typography below (14px title, 12px meta)
- No borders, clean card design

---

## Shortcodes (5 total)

### 1. `[mindful_media_archive]` - Media Archive
Displays a YouTube-style grid of media items with horizontal filter chips.

| Attribute | Default | Description |
|-----------|---------|-------------|
| `per_page` | 12 | Items per page |
| `show_filters` | true | Show filter chips |
| `show_pagination` | true | Show pagination |
| `category` | '' | Filter by category slug |
| `type` | '' | Filter by media type (audio/video) |
| `topic` | '' | Filter by topic slug |
| `teacher` | '' | Filter by teacher slug |
| `featured` | '' | Set to 'true' for featured only |

**Example:** `[mindful_media_archive per_page="12"]`

### 2. `[mindful_media id="123"]` - Single Media Embed
Embeds a single media item with thumbnail that opens modal player.

| Attribute | Default | Description |
|-----------|---------|-------------|
| `id` | '' | Media item post ID |
| `playlist` | '' | Playlist slug (alternative to id) |
| `show_thumbnail` | true | Show clickable thumbnail |
| `autoplay` | false | Auto-play when opened |
| `size` | 'medium' | Embed size: small (320px), medium (560px), large (800px), full (100%), or custom px/% value |

**Example:** `[mindful_media id="123"]` or `[mindful_media playlist="beginner-course" size="large"]`

### 3. `[mindful_media_browse]` - Browse/Landing Page
Full browse experience with navigation and category sections.

| Attribute | Default | Description |
|-----------|---------|-------------|
| `show_featured` | true | Show featured hero section |
| `show_nav` | true | Show category navigation |
| `sections` | 'teachers,topics,playlists,types' | Sections to display |

**Example:** `[mindful_media_browse]`

#### Browse Block Tab Behavior (IMPORTANT)

The browse block has two distinct display modes depending on which tab is active:

**HOME Tab (All Sections):**
- Shows taxonomy TERM CARDS in horizontal sliders
- Example: Teachers section shows [Teacher A Card] [Teacher B Card] [Teacher C Card]...
- Clicking a card navigates to that term's archive page
- This is a "discovery" view to browse available categories

**Individual Tabs (Teachers, Topics, Playlists, Categories):**
- Shows Netflix-style VIDEO ROWS for each term in that taxonomy
- Example: Teachers tab shows:
  - "Teacher A" row → [Video 1] [Video 2] [Video 3]...
  - "Teacher B" row → [Video 1] [Video 2] [Video 3]...
  - "Teacher C" row → [Video 1] [Video 2] [Video 3]...
- Each term gets its own horizontal slider row containing that term's media items
- Clicking a video opens the modal player
- This is a "browse content" view to see all videos organized by category

**Implementation Notes:**
- HOME tab uses `render_browse_section()` - renders term cards
- Individual tabs use `render_browse_section_with_videos()` - renders video rows per term
- The JavaScript tab handler must call the appropriate render function based on section

### 4. `[mindful_media_row]` - Netflix-Style Category Row
Displays a horizontal slider of items from a specific taxonomy.

| Attribute | Default | Description |
|-----------|---------|-------------|
| `taxonomy` | 'media_teacher' | Taxonomy to display (media_teacher, media_topic, media_category, media_series) |
| `title` | '' | Custom section title (uses taxonomy label if empty) |
| `limit` | 12 | Maximum items per term |
| `show_link` | true | Show "View All" link to archive |

**Example:** `[mindful_media_row taxonomy="media_topic" title="Browse by Topic"]`

### 5. `[mindful_media_taxonomy_archive]` - Full Taxonomy Archive
Displays a full page of all terms from a taxonomy, each as a Netflix-style row with their media items. Supports password protection for playlists.

| Attribute | Default | Description |
|-----------|---------|-------------|
| `taxonomy` | 'media_teacher' | Taxonomy: media_teacher, media_topic, media_category, media_series |
| `title` | '' | Custom page title |
| `items_per_row` | 10 | Number of items per row |
| `orderby` | 'name' | Term order: name, count, slug |
| `order` | 'ASC' | ASC or DESC |
| `hide_empty` | 'true' | Hide terms with no items |

**Example:** `[mindful_media_taxonomy_archive taxonomy="media_series" title="All Playlists"]`

**Note:** When displaying playlists (`media_series`), password-protected playlists show a lock icon and require password entry before revealing content.

### 6. `[mindful_media_library]` - My Library Page
Displays the user's personal library with liked videos, watch history, subscriptions, and continue watching sections.

| Attribute | Default | Description |
|-----------|---------|-------------|
| (none) | - | No attributes required |

**Example:** `[mindful_media_library]`

**Sections displayed:**
- **Continue Watching** - Videos with saved playback progress (< 90% complete)
- **Liked Videos** - Videos the user has liked
- **Watch History** - Recently viewed videos
- **Subscriptions** - Followed teachers, topics, playlists, categories with unsubscribe toggles

**Note:** Requires user to be logged in. Guests see a login prompt. A My Library page is automatically created on plugin activation.

---

## Gutenberg Blocks (4 total)

Found under **MindfulMedia** category in block inserter (search "mind" or "mindful"):

| Block Name | Description | Shortcode Used |
|------------|-------------|----------------|
| **MindfulMedia Browse** | Full browse page layout | `[mindful_media_browse]` |
| **MindfulMedia Archive** | Media grid with filters | `[mindful_media_archive]` |
| **MindfulMedia Embed** | Single media/playlist embed with size options | `[mindful_media]` |
| **MindfulMedia Category Row** | Netflix-style horizontal slider | `[mindful_media_row]` |

**Embed Block Size Options:** Small (320px), Medium (560px), Large (800px), Full Width

---

## Elementor Widgets (4 total)

Found under **MindfulMedia** category in Elementor panel (search "mindful"):

| Widget Name | Description | Shortcode Used |
|-------------|-------------|----------------|
| **MindfulMedia Browse** | Full browse page layout | `[mindful_media_browse]` |
| **MindfulMedia Embed** | Single media/playlist embed | `[mindful_media]` |
| **MindfulMedia Archive** | Media grid with filters | `[mindful_media_archive]` |
| **MindfulMedia Category Row** | Netflix-style horizontal slider | `[mindful_media_row]` |

---

## Template Files

### `/templates/taxonomy-media_series.php`
**Playlist/Series Archive Page**
- Light theme (white background)
- YouTube-style card grid
- Supports parent series with child modules
- Click opens modal player

### `/templates/taxonomy-media_teacher.php`
**Teacher Archive Page**
- Light theme (white background)
- Shows teacher featured image and bio
- Netflix-style horizontal sliders grouped by topic
- Click opens modal player

### `/templates/taxonomy-media_topic.php`
**Topic Archive Page**
- Light theme (white background)
- Netflix-style horizontal sliders grouped by teacher
- Click opens modal player

### `/templates/single-mindful-media.php`
**Single Media Item Page**
- DARK theme (black background, like modal player)
- Full-width player with admin bar support
- Meta information below (date, duration, type, teacher)
- Chip-style categories and topics
- "More Media" section with related items
- Immersive viewing experience

---

## CSS Architecture

### Theme Compatibility

The plugin CSS is designed to isolate styles from theme interference:

- **High-priority loading**: CSS enqueued at priority 9999 to load after theme styles
- **Explicit colors**: Links use explicit colors (not `inherit`) to prevent theme overrides
- **Scoped reset**: CSS reset applied only within plugin containers
- **CSS containment**: Uses `contain: layout style` for performance and isolation

### CSS Variables (`:root`)

```css
/* Light theme (browse/archive) */
--mm-bg-primary: #ffffff;
--mm-bg-secondary: #f2f2f2;
--mm-text-primary: #0f0f0f;
--mm-text-secondary: #606060;

/* Dark theme (modal/single) */
--mm-dark-bg-primary: #0f0f0f;
--mm-dark-bg-secondary: #212121;
--mm-dark-text-primary: #f1f1f1;
--mm-dark-text-secondary: #aaaaaa;

/* Z-index Scale (documented layering system) */
--mm-z-base: 1;        /* Default elements, cards */
--mm-z-dropdown: 100;  /* Dropdowns, menus */
--mm-z-sticky: 500;    /* Sticky headers, navigation */
--mm-z-overlay: 1000;  /* Overlays, backdrops */
--mm-z-modal: 10000;   /* Modal dialogs, full-screen players */
--mm-z-modal-content: 10001;  /* Content above modal (close buttons, sidebars) */
--mm-z-toast: 99999;   /* Toast notifications, alerts (always on top) */
```

### System Dark Mode Support

Add the class `mm-auto-dark-mode` to any `.mindful-media-container` to enable automatic dark mode when the user's OS is set to dark mode:

```html
<div class="mindful-media-container mm-auto-dark-mode">
    <!-- Content will automatically use dark theme when OS prefers dark -->
</div>
```

### Print Styles

The plugin includes print-optimized styles that:
- Hide interactive elements (players, controls, modals)
- Convert sliders to printable grids
- Prevent page breaks inside cards
- Show clean, readable output

### Key Classes
| Class | Description |
|-------|-------------|
| `.mindful-media-container` | Main container |
| `.mindful-media-archive` | Grid of cards |
| `.mindful-media-card` | Individual card |
| `.mindful-media-inline-player` | Modal player |
| `.mm-chip` | Filter chip |
| `.mindful-media-filter-chips` | Filter chips container |
| `.mindful-media-single` | Single page wrapper |
| `.mm-auto-dark-mode` | Enables system dark mode support |

---

## File Structure

```
mindfulmedia/
├── mindful-media.php          # Main plugin file
├── instructions.md            # This file
├── uninstall.php              # Cleanup on plugin deletion
├── includes/
│   ├── class-shortcodes.php   # All shortcodes & AJAX handlers
│   ├── class-post-types.php   # Custom post type & single filter
│   ├── class-taxonomies.php   # Taxonomy registration & admin
│   ├── class-meta-fields.php  # Custom meta boxes
│   ├── class-players.php      # Media player rendering
│   ├── class-settings.php     # Plugin settings + access helpers
│   ├── class-admin.php        # Admin customizations
│   ├── class-blocks.php       # Gutenberg blocks
│   ├── class-elementor.php    # Elementor integration
│   ├── class-engagement.php   # Likes, comments, subscriptions, watch history
│   ├── class-notifications.php # Email notifications for subscriptions
│   └── elementor/
│       ├── widget-browse.php
│       ├── widget-embed.php
│       ├── widget-archive.php
│       └── widget-row.php
├── templates/
│   ├── single-mindful-media.php
│   ├── taxonomy-media_series.php
│   ├── taxonomy-media_teacher.php
│   └── taxonomy-media_topic.php
├── public/
│   ├── css/frontend.css       # All frontend styles (incl. engagement)
│   └── js/frontend.js         # All frontend JavaScript (incl. engagement)
├── admin/
│   ├── css/admin.css
│   ├── css/blocks-editor.css
│   └── js/blocks.js
└── assets/
    ├── icon-gold.svg
    ├── icon-white.svg
    └── logo-white.svg
```

---

## Extensibility Hooks

The plugin provides filters for customization by themes and other plugins:

### Available Filters

#### `mindful_media_archive_query_args`
Modify the WP_Query arguments for archive listings.

```php
add_filter('mindful_media_archive_query_args', function($query_args, $atts) {
    // Example: Only show featured items
    $query_args['meta_query'][] = array(
        'key' => '_mindful_media_featured',
        'value' => '1'
    );
    return $query_args;
}, 10, 2);
```

**Parameters:**
- `$query_args` (array) - The WP_Query arguments
- `$atts` (array) - The shortcode attributes

#### `mindful_media_card_html`
Modify the HTML output for individual media cards.

```php
add_filter('mindful_media_card_html', function($html, $post_id, $atts) {
    // Example: Add custom data attribute
    return str_replace('class="mindful-media-card"', 
        'class="mindful-media-card" data-custom="value"', $html);
}, 10, 3);
```

**Parameters:**
- `$html` (string) - The card HTML
- `$post_id` (int) - The media post ID
- `$atts` (array) - The shortcode attributes

#### `mindful_media_player_html`
Modify the HTML output for media players.

```php
add_filter('mindful_media_player_html', function($html, $url, $source, $atts) {
    // Example: Wrap player in custom container
    return '<div class="my-player-wrapper">' . $html . '</div>';
}, 10, 4);
```

**Parameters:**
- `$html` (string) - The player HTML
- `$url` (string) - The media URL
- `$source` (string) - The detected source type (youtube, vimeo, etc.)
- `$atts` (array) - The player attributes

---

## Protected Playlist Content Rules (CRITICAL)

**Videos in password-protected playlists MUST be hidden everywhere until the user unlocks the playlist.**

### Implementation Requirements

When querying media items for display, ALWAYS exclude videos from protected playlists:

1. **Use `get_protected_playlist_video_ids()`** - This helper function in `class-shortcodes.php` returns an array of post IDs that belong to protected playlists the current user doesn't have access to.

2. **Add to `post__not_in`** - Every `WP_Query` that displays media items must include:
   ```php
   $protected_ids = $this->get_protected_playlist_video_ids();
   if (!empty($protected_ids)) {
       $query_args['post__not_in'] = $protected_ids;
   }
   ```

3. **Locations that MUST filter protected videos:**
   - `render_browse_section_with_videos()` - Browse block individual tabs ✓
   - `render_playlists_section_with_videos()` - Playlists tab ✓
   - `row_shortcode()` - Netflix-style row shortcode ✓
   - `taxonomy-media_teacher.php` - Teacher archive template ✓
   - `taxonomy-media_topic.php` - Topic archive template ✓
   - `taxonomy-media_category.php` - Category archive template ✓
   - Any new template or shortcode that displays media items

4. **How protection works:**
   - Playlists with `password_enabled` term meta set to '1' are protected
   - Users gain access by entering the correct password, which sets a cookie
   - Cookie name format: `mindful_media_playlist_access_{term_id}`
   - Child playlists inherit protection from parent (via `include_children => true`)

5. **Testing protected content:**
   - Create a playlist and enable password protection
   - Add videos to the playlist
   - Verify videos don't appear in browse tabs, taxonomy pages, or filters
   - Unlock the playlist and verify videos now appear

### Common Mistakes to Avoid

- ❌ Forgetting to call `get_protected_playlist_video_ids()` in new queries
- ❌ Adding protected video filtering only to some locations
- ❌ Not testing with actual protected playlists after changes
- ❌ Caching query results without considering user access state

---

## Engagement Features

### Likes
Users can like videos to save them for later. Like counts are displayed on media cards and single pages.

- **Enable/Disable:** Settings > Engagement & Access > Enable Likes
- **Display Counts:** Settings > Engagement & Access > Show counts on cards/single
- **Storage:** Custom database table `{prefix}_mindful_media_likes`
- **AJAX Endpoint:** `mindful_media_like` (toggle like status)

### Comments
Users can comment on videos. Comments can be moderated or auto-approved.

- **Enable/Disable:** Settings > Engagement & Access > Enable Comments
- **Auto-approve:** Settings > Engagement & Access > Auto-approve comments
- **Storage:** Custom database table `{prefix}_mindful_media_comments`
- **AJAX Endpoints:** `mindful_media_post_comment`, `mindful_media_get_comments`, `mindful_media_delete_comment`

### Subscriptions
Users can subscribe to teachers, topics, playlists, and categories to be notified of new content.

- **Enable/Disable:** Settings > Engagement & Access > Enable Subscriptions
- **Per-taxonomy controls:** Toggle which taxonomies users can subscribe to
- **Storage:** Custom database table `{prefix}_mindful_media_subscriptions`
- **AJAX Endpoints:** `mindful_media_subscribe` (toggle), `mindful_media_get_subscriptions`

### Watch History & Continue Watching
Track what users have watched and where they left off.

- **Watch History:** Records when a user views a video
- **Playback Progress:** Saves current playback position periodically
- **Continue Watching:** Shows videos with saved progress < 90% complete
- **Storage:** Custom tables `{prefix}_mindful_media_watch_history` and `{prefix}_mindful_media_playback_progress`

### Email Notifications
Subscribers receive email notifications when new content matches their subscriptions.

- **Enable/Disable:** Settings > Engagement & Access > Enable email notifications
- **Throttling Options:** Instant, Hourly digest, or Daily digest
- **Templates:** Customizable from name, email, and subject template
- **Placeholders:** `{term_name}`, `{site_name}`, `{post_title}`
- **Master Kill Switch:** Disable ALL notifications setting

### My Library Page
A dedicated page showing the user's engagement data.

- **Auto-created:** Page with `[mindful_media_library]` shortcode created on plugin activation
- **WooCommerce Integration:** Optional tab in WooCommerce My Account page
- **Sections:** Continue Watching, Liked Videos, Watch History, Subscriptions

---

## MemberPress Integration

### Overview
Restrict media content access based on MemberPress membership levels.

### Setup
1. Install and activate MemberPress
2. Go to Settings > Engagement & Access
3. Enable "MemberPress Gating"
4. Configure default access level and locked content behavior

### Global Settings (Settings > Engagement & Access)
- **Enable MemberPress Gating:** Master toggle for membership restrictions
- **Default Access Level:** Membership level required for all content (can be overridden per-item)
- **Locked Content Behavior:** Show lock icon + CTA, or hide content entirely
- **Locked CTA Text:** Custom message displayed on locked content
- **Join/Pricing URL:** Where to send users who need to upgrade

### Per-Item Access Control
On each media item edit screen (Visibility & Protection meta box):
- Select which membership levels can access this specific content
- Leave empty to use global default
- Select "Public" to make content available to everyone (override global)

### Per-Taxonomy Access Control
On each taxonomy term edit screen (Playlists, Teachers, Topics, Categories):
- Select which membership levels can access all content in this term
- Useful for restricting entire playlists or all content from a teacher

### How Access Checks Work
1. Password protection is checked first
2. If MemberPress gating is enabled, membership levels are checked
3. Per-item settings override per-taxonomy settings
4. Per-taxonomy settings override global default
5. Guests see login prompt with link to join page

### Access Check Function
Use `MindfulMedia_Settings::user_can_view($post_id)` to check access programmatically:

```php
$access = MindfulMedia_Settings::user_can_view($post_id);
if ($access === true) {
    // User has access
} else {
    // $access['locked'] = true
    // $access['reason'] = 'membership' or 'password'
    // $access['message'] = Custom lock message
}
```

---

## Features Checklist

### Core Features
- [x] Custom post type with meta fields
- [x] Multiple taxonomies (categories, types, topics, teachers, playlists)
- [x] YouTube-style card grid layout
- [x] Filter chips for filtering content
- [x] Modal player (dark theme)
- [x] Single media page (dark theme)
- [x] Playlist support with ordering
- [x] Parent/child playlist hierarchy (series/modules)
- [x] Password protection (individual items and playlists)
- [x] Protected playlist videos hidden everywhere until unlocked
- [x] Featured content support
- [x] Hide from Archive (Visibility control for media and playlists)

### Engagement Features
- [x] Like videos with like counts
- [x] Comments with moderation option
- [x] Subscribe to teachers/topics/playlists/categories
- [x] Email notifications for new subscribed content
- [x] Watch history tracking
- [x] Playback progress saving (continue watching)
- [x] My Library page with all engagement data
- [x] WooCommerce My Account integration (optional)

### Access Control
- [x] MemberPress integration for membership gating
- [x] Global default access level
- [x] Per-item membership level overrides
- [x] Per-taxonomy membership level overrides
- [x] Locked content display with CTA
- [x] MemberPress controls hidden when not active

### Display Features
- [x] Responsive grid (1-5 columns based on screen)
- [x] Duration badges on thumbnails
- [x] Play button overlay on hover
- [x] Teacher names as meta
- [x] Playlist count badges
- [x] "More Media" section on single pages (related items)
- [x] Chip-style categories/topics on single pages
- [x] Netflix-style horizontal sliders
- [x] Video/Audio type icons on cards
- [x] Skeleton loading animations
- [x] Lazy-loaded images
- [x] Keyboard navigation (Escape, Space)
- [x] Mobile swipe gestures
- [x] Per-taxonomy image aspect ratios (square, landscape, portrait, custom)
- [x] Featured images for teachers, topics, categories, and playlists

### Integration
- [x] Gutenberg blocks (Archive, Embed, Browse, Category Row)
- [x] Elementor widgets (Archive, Embed, Browse, Category Row)
- [x] Shortcodes (4 total)
- [x] AJAX filtering
- [x] AJAX modal loading

### Archive Display Settings
- [x] Toggle filter tabs visibility (Home, Teachers, Topics, Playlists, Categories)
- [x] Toggle filter chips (Duration, Year, Media Type)
- [x] Show/hide counts on filter chips and taxonomy cards
- [x] Show/hide Featured section
- [x] Configurable items per row
- [x] Custom "Back to All Media" URL
- [x] Browse page section visibility (Teachers, Topics, Playlists, Categories, Media Types)

### Search Functionality
- [x] Search bar in archive filter chips (AJAX search)
- [x] Search bar in browse page (hybrid AJAX/client-side search)
- [x] Search bar in teacher archive pages (client-side filtering)
- [x] Search bar in topic archive pages (client-side filtering)
- [x] Search bar in playlist pages (client-side filtering)
- [x] Search bar in category archive pages (client-side filtering)
- [x] Search bar in taxonomy archive shortcode (client-side filtering)
- [x] Search bar in archive-mindful_media.php template (client-side filtering)
- [x] Search combines with filter chips (search within filtered results)
- [x] Clear button appears when search has text
- [x] "No results found" message with search icon
- [x] 200-300ms debounce for responsive performance

---

## Testing Checklist

### Core Functionality
- [ ] Archive page shows YouTube-style grid with filter chips
- [ ] Filter chips filter content correctly
- [ ] Filter chips respect Archive Display settings
- [ ] Clicking card opens modal player (dark theme)
- [ ] Modal player close button works
- [ ] Playlist sidebar works in modal
- [ ] Modal description section not overlapped by sidebar

### Single Pages
- [ ] Single media page has dark theme
- [ ] Single media page is full width
- [ ] Single media page title not under admin bar (when logged in)
- [ ] Single media page shows chip-style categories/topics
- [ ] Single media page shows "More Media" related items

### Archive Pages
- [ ] Teacher archive page works (light theme, Netflix sliders)
- [ ] Topic archive page works (light theme, Netflix sliders)
- [ ] Playlist archive page works (light theme, Netflix sliders)
- [ ] Password protection form centered properly

### Netflix-Style Sliders
- [ ] Slider arrows appear on hover
- [ ] Arrows are 90% height and properly positioned
- [ ] Continuous scroll (no snap)
- [ ] Mobile swipe gestures work

### Media Players
- [ ] YouTube player works, progress bar updates
- [ ] Vimeo player works
- [ ] SoundCloud player plays and pauses correctly
- [ ] Archive.org player works
- [ ] Big play button hides when playing
- [ ] Big play button shows on pause/end

### Integrations
- [ ] Gutenberg blocks (Archive, Embed, Browse, Category Row)
- [ ] Elementor widgets render correctly
- [ ] Password protection works
- [ ] Responsive on mobile

### Search Functionality
- [ ] Archive page search bar filters content via AJAX
- [ ] Browse page search bar filters cards and video rows
- [ ] Teacher archive search filters videos by title
- [ ] Topic archive search filters videos by title
- [ ] Playlist page search filters videos by title
- [ ] Category archive search filters videos by title
- [ ] Taxonomy archive shortcode search filters videos by title
- [ ] archive-mindful_media.php template search filters videos
- [ ] Search works in combination with filter chips
- [ ] Clear button clears search and restores content
- [ ] "No results found" message appears when no matches

---

## Future Enhancements

Ideas for future development:

### Moodle LMS Integration
- Single Sign-On (SSO) with Moodle
- Sync user enrollment and course progress
- Embed media directly in Moodle courses
- Track completion status between platforms

### Setup Wizard
- Guided onboarding experience for new users
- Step-by-step configuration of taxonomies, colors, and default settings
- Sample content creation option
- WooCommerce-style setup flow with progress indicators

### Additional Features (Backlog)
- Batch import from CSV/JSON
- Analytics dashboard for media views
- Social sharing integration
- Custom embed player themes
- REST API endpoints for headless usage
- Autoplay next video in playlists (toggle-able)
- Progress bar indicators on cards in archive views
- Resume playback from last position in modal player
- Drip content scheduling for membership tiers

---

## Version History

- **2.13.0** - MemberPress & Navigation Enhancements
  - **Per-Membership Join URLs**
    - Global default join/pricing URL (existing)
    - Per-membership level URL overrides
    - Content locked for "Premium" redirects to Premium signup page
    - Fallback to MemberPress product registration page if no custom URL set
  - **Navigation URLs**
    - Renamed setting to "Browse Page URL" for clarity
    - New "Media Archive URL" setting for archive page links
    - Separate URLs for browse view (`[mindful_media_browse]`) and media listing (`[mindful_media_archive]`)
  - **Email Template Enhancements**
    - Added header logo option (upload or use text)
    - Customizable email body template with placeholders
    - Live preview updates for logo/text toggle
    - HTML support in footer text
    - Reset button for email body template

- **2.12.0** - Email Settings & Template Customization
  - **New Emails Tab**
    - Dedicated tab for email notifications (like WooCommerce)
    - Moved email settings from Engagement tab to new Emails tab
    - Clear organization of sender settings and template options
  - **Email Template Customization**
    - Customizable header text (site branding)
    - Customizable footer text (subscription explanation)
    - Header background color picker
    - Header text color picker
    - Button/CTA color picker
    - Button text color picker
    - Live preview of email template in settings
  - **Send Test Email**
    - One-click test email to verify configuration
    - Sends styled test email matching your template settings
    - Immediate feedback on success/failure
  - **Template Improvements**
    - Notification emails now use customizable colors
    - Digest emails updated to match template settings
    - Consistent branding across all email types

- **2.11.0** - Settings UX & Audio Player Improvements
  - **Audio Player Controls**
    - Volume slider now uses configurable progress bar color instead of browser default blue
    - Consistent gold/custom color across progress bar and volume controls
  - **Settings Page Reorganization**
    - Reduced from 8 tabs to 7 with logical grouping
    - New **Appearance** tab combining Colors, Typography, and Image Ratios
    - New **Access Control** tab split from Engagement (MemberPress, Login settings)
    - **API Keys** moved to Advanced tab
    - **Data Management** consolidated in Advanced tab
    - Added YouTube Data API key field for automatic duration fetching
  - **Getting Started Guide Enhancements**
    - Added **Page Builder Integration** section (Gutenberg blocks, Elementor widgets)
    - Added **Content Protection** section (password protection for items/playlists)
    - Added **Access Control Overview** section (MemberPress integration)
    - Added **Import/Export** section with links to tools
    - Added **What's Next?** section with suggested workflow steps
  - **UX Improvements**
    - Professional tab organization following WooCommerce patterns
    - Clear separation between engagement features and access control
    - Better discoverability of all plugin features

- **2.10.0** - Taxonomy Images & Aspect Ratios
  - **Category Featured Images**
    - Added featured image support to media_category taxonomy (matching topics)
    - Categories now display images on browse cards and archive headers
    - Media uploader integration on category add/edit screens
    - Image column in category list table
  - **Per-Taxonomy Image Aspect Ratios**
    - New settings to control aspect ratio for each taxonomy (Teachers, Topics, Categories, Playlists)
    - Preset options: Square (1:1), Landscape (16:9), Portrait (3:4)
    - Custom aspect ratio input (width:height format)
    - Applies to browse cards, term avatars, and archive templates
    - Dynamic CSS output ensures consistent styling across all views
  - **Automatic Duration Fetching**
    - Auto-fetch duration from Vimeo when saving media items (no API key needed)
    - Auto-fetch duration from YouTube when saving (requires API key)
    - "Fetch Duration" button on edit screen for manual fetch
    - Manual override fields still available for custom durations
  - **Bug Fix: Browse By Teacher Counts**
    - Fixed inaccurate media counts on browse page term cards
    - Counts now always recalculated from database instead of using cached term->count

- **2.9.0** - User Engagement & MemberPress Integration
  - **User Engagement Features**
    - Added likes system with like counts on cards and single pages
    - Added comments system with moderation/auto-approve option
    - Added subscription system for teachers, topics, playlists, categories
    - Added watch history tracking (records when users view videos)
    - Added playback progress saving (continue watching functionality)
    - Email notifications for subscribed content (instant/hourly/daily digest)
  - **My Library Page**
    - Auto-created page on plugin activation with `[mindful_media_library]` shortcode
    - Tabbed interface: Continue Watching, Liked, History, Subscriptions
    - Optional WooCommerce My Account tab integration
  - **MemberPress Integration**
    - Restrict content based on MemberPress membership levels
    - Global default access level with per-item/per-taxonomy overrides
    - Locked content displays with customizable CTA and join URL
    - MemberPress controls hidden when plugin not active
  - **New Database Tables**
    - `{prefix}_mindful_media_likes`
    - `{prefix}_mindful_media_comments`
    - `{prefix}_mindful_media_subscriptions`
    - `{prefix}_mindful_media_watch_history`
    - `{prefix}_mindful_media_playback_progress`
  - **Settings Page Updates**
    - New "Engagement & Access" tab with all engagement controls
    - Email notification settings (from name/email, subject template, throttle)
    - MemberPress gating settings (enable, default level, locked behavior)
    - My Library settings (page selector, WooCommerce integration toggle)
    - Data retention option (keep engagement data on uninstall)
  - **Frontend Updates**
    - Like button and subscribe button on single media pages
    - Comment composer and comment list on single media pages
    - Guest prompts linking to login page
    - CSS styles for all engagement components
    - JavaScript handlers for AJAX engagement actions

- **2.8.4** - Navigation Arrows & Modal Styling Fix
  - **Browse Slider Navigation Arrows**
    - Removed circular white background from navigation arrows
    - Made chevrons big (48px instead of 20px)
    - Arrows are now just clean chevrons with subtle drop-shadow
    - Updated all 6 slider instances in shortcodes
    - Arrows only show on hover when there are items to scroll
  - **Modal Header Title**
    - Fixed weird centering of title
    - Removed text shadows from title
    - Made title more compact (16px font)
  - **Modal Buttons (Light Mode)**
    - Fixed close/share/back button backgrounds not visible
    - Changed to gray background (#e5e5e5) with border
  - **Playlist Sidebar (Light Mode)**
    - Fixed conflicting CSS rule forcing white text
    - Active item now uses subtle blue background (#e8f0fe)
    - Module names and item titles properly dark

- **2.8.3** - Single Page Video Fix & Styling Improvements
  - **Single Page Video Display Fix**
    - Fixed video not showing on single media pages (only showing black screen)
    - Added `!important` rules to iframe styles in `.mindful-media-player-aspect-ratio`
    - Ensures iframe is properly positioned (absolute) and sized (100% width/height)
  - **Light Mode Modal Styling**
    - Fixed dark header appearing on light mode modals
    - Added light theme hover styles for modal header
    - Added light theme styles for Back and Share buttons
    - Added comprehensive light theme styles for playlist modules
  - **Playlist Sidebar Color Fix**
    - Removed red accent color (`#ff0000`) - changed to neutral white
    - Fixed active module header background (was red-tinted)
    - Added proper light theme styles for playlist items and modules
  - **Bulk Upload Improvements**
    - Made form more compact with new grid layout
    - Added featured image upload capability (uses WordPress media library)
    - Added duration fields (hours/minutes)
    - Added recording date field
    - Session rows now show number badge, title, URL, image, duration, and date in compact view

- **2.8.2** - Bug Fixes & UX Improvements
  - **Bulk Video Upload Fix**
    - Restored `ajax_batch_create_sessions` AJAX handler (was removed in 2.1.25)
    - "Add Multiple Sessions" modal in playlist admin now works correctly
    - Auto-detects media source (YouTube, Vimeo, SoundCloud, Archive.org)
    - Auto-sets media type (Audio/Video) based on URL
  - **Duration Display Fix**
    - Videos over 1 hour now display correctly as H:MM:SS (e.g., "1:58:10" instead of "118:10")
    - Updated `formatTime()` JavaScript function to handle hours
    - Updated all PHP duration badge formatting to use new `format_duration_badge()` helper
    - Fixed in: shortcodes, templates (archive, teacher, topic, category pages)
  - **Playlist Title Encoding Fix**
    - Fixed HTML entity double-encoding (e.g., `&#8211;` showing as literal text)
    - Titles now properly decoded before sending to JavaScript
  - **Modal Player UX Improvements**
    - Replaced "View Full Page" link with "Back to Browse" button (top left)
    - Back button navigates to archive page (configurable via `archive_link` setting)
    - Added Share button (top right) - copies media permalink to clipboard
    - Share button shows "Link copied!" tooltip on success
    - Share button can be enabled/disabled via `modal_share_button` setting
  - **Playlist More Info Section**
    - Added collapsible "More Info" section below video player for playlist items
    - Shows description, date, duration, media type, categories, and topics
    - Initially collapsed, expands with smooth animation when clicked
    - Button text toggles between "More Info" and "Less Info"

- **2.8.1** - Bug Fixes
  - **Taxonomy Page Search Icon Fix**
    - Fixed giant search icon on teacher/topic/category pages
    - Added CSS rules for `.mm-filter-search` class (was incorrectly targeting `.mm-taxonomy-search`)
    - Search icon now properly sized at 16px
  - **Taxonomy Page Layout Fix**
    - Added explicit width rules for taxonomy page containers
    - Ensures full-width layout regardless of theme constraints
  - **Embed Card Hover Fix**
    - Removed whole-card scale transform on hover
    - Hover effect now only applies to thumbnail image, not title/description
  - **Password Modal Close Button**
    - Removed 90-degree rotation on hover (was disorienting)
  - **Browse Card Aspect Ratio**
    - Fixed browse cards on Home tab to use 16:9 aspect ratio
    - Removed conflicting inline styles from PHP

- **2.8.0** - Comprehensive Plugin Optimization
  - **CSS Theme Isolation**
    - CSS now loads at priority 9999 (after theme styles) for reliable override
    - Fixed link colors - card titles use explicit colors, other links use inherit
    - Fixed visited link styles - section titles stay black, not blue when visited
    - Added explicit styling for browse search clear button
    - Prevents theme styles from overriding plugin colors
  - **JavaScript Memory Leak Fix**
    - Fixed `progressCheckInterval` not being cleared when modal closes
    - Added proper cleanup for player intervals on modal close
    - Prevents memory accumulation during extended use
  - **Z-index System**
    - Added documented z-index CSS variable scale
    - Variables: `--mm-z-base` through `--mm-z-toast`
    - Provides consistent layering for modals, overlays, dropdowns
  - **PHP Version Check**
    - Added activation check requiring PHP 7.4+
    - Displays helpful error message if PHP version is too old
    - Prevents activation on incompatible servers
  - **Extensibility Hooks**
    - Added `mindful_media_archive_query_args` filter for query customization
    - Added `mindful_media_card_html` filter for card output customization
    - Added `mindful_media_player_html` filter for player output customization
    - Enables theme/plugin developers to extend functionality
  - **Print Styles**
    - Added `@media print` rules for clean print output
    - Hides interactive elements (players, controls, modals)
    - Converts sliders to printable grids
    - Prevents page breaks inside cards
  - **System Dark Mode Support**
    - Added `prefers-color-scheme: dark` media query support
    - Add `.mm-auto-dark-mode` class to enable automatic dark mode
    - Respects OS-level dark mode preference
  - **Documentation**
    - Added CSS architecture and theme compatibility section
    - Added z-index system documentation
    - Added extensibility hooks reference with examples
    - Updated version history

- **2.7.0** - Getting Started Page - WooCommerce Style Redesign
  - **Clean Design Matching MindfulSEO**
    - Removed red welcome banner - replaced with clean white card design
    - Updated color scheme to gold (#e1ca8e, #b8a064, #93845e) matching Mindful Design branding
    - Single branding header (no duplicate headers)
    - WooCommerce-inspired clean, minimal interface
  - **Mindful Design Branding**
    - Consistent branding header with Mindful Design logo and link
    - Updated "Need Help?" section with link to mindfuldesign.me for support
  - **Simplified Layout**
    - Welcome card with 3-column grid: Quick Setup, Taxonomies, Your Content
    - Clean step cards with gold numbered badges
    - Removed cluttered status indicators - content status shown inline

- **2.6.5** - Security Hardening & Player Improvements
  - **Security Fixes**
    - Added nonce verification to `ajax_preview_media` handler (CSRF protection)
    - Added nonce verification to `reset_settings` handler (CSRF protection)
    - Removed demo setup file from production
  - **Player Improvements**
    - Vimeo player now uses native controls for reliable playback
    - Enhanced Vimeo unlisted video URL support (hash parameter extraction)
    - Added loading indicator for Archive.org player (spinner + "Loading from Archive.org..." message)
  - **Browse Page Settings**
    - Added section visibility controls in admin settings
    - Toggle Teachers, Topics, Playlists, Categories, and Media Types sections
    - Media Types section now off by default
  - **UI Fixes**
    - Fixed search text overlapping search icon (increased left padding)
    - Fixed underlined titles in browse block
    - Fixed search bar alignment to top of filter chips row

- **2.6.0** - Search Functionality
  - **Search Bar Implementation**
    - Added YouTube-style search bar to archive filter chips (AJAX-based search)
    - Added search bar to browse page navigation (hybrid AJAX/client-side filtering)
    - Added search bar to teacher archive pages (client-side filtering)
    - Added search bar to topic archive pages (client-side filtering)
    - Added search bar to playlist/series pages (client-side filtering)
    - Added search bar to category archive pages (client-side filtering)
    - Added search bar to `[mindful_media_taxonomy_archive]` shortcode (client-side filtering)
    - Added search bar to `archive-mindful_media.php` template (client-side filtering)
  - **Search Features**
    - Search combines with existing filter chips (search within filtered results)
    - Search filters by video title
    - AJAX search on archive/browse extends to teacher/topic taxonomy names
    - 200-300ms debounce for responsive performance
    - Clear button appears when search has text
    - "No results found" message with search icon when no matches
  - **CSS Updates**
    - YouTube-style search input with rounded corners and search icon
    - Focus state with blue border and subtle shadow
    - Responsive design (full-width on mobile)
    - Dark theme support for modal/single pages
  - **JavaScript Updates**
    - `initSearch()` now initializes archive, browse, taxonomy, and clear button handlers
    - `initArchiveSearch()` for AJAX-based archive filtering
    - `initBrowseSearch()` for hybrid browse page filtering
    - `initTaxonomySearch()` for client-side taxonomy page filtering
    - `applyTaxonomyArchiveSearch()` for taxonomy archive shortcode filtering
    - `initSearchClearButtons()` for clear button functionality

- **2.5.2** - Category Icons, Playlist Page Fixes & UI Polish
  - **Category-Specific SVG Icons**
    - Added custom icons for category cards (meditations, talks, audio, video, teachings, retreats, etc.)
    - Meditations category now shows a meditator icon instead of generic letter
    - Icons auto-detected by category slug
  - **Navigation Tab Icons Refreshed**
    - Cleaner, YouTube-inspired monochrome icons for all nav tabs
    - Home (house), Teachers (person), Topics (tag), Playlists (list), Categories (folder)
    - Removed colored icons for consistency
  - **Playlist Page Layout Fixes**
    - Added inline styles as fallback for slider item widths (280px)
    - Fixed theme CSS conflicts that caused narrow/squeezed slider items
    - Added Astra theme overrides for full-width layouts
    - Module slider tracks now use flex-wrap: nowrap correctly
  - **Settings Enhancement**
    - Improved "Browse Page URL" description in admin settings
    - Clearer explanation of how navigation links work with hash-based routing
  - **Bug Fixes**
    - Fixed unlock view on playlist pages showing broken layout
    - Added stronger !important rules for slider containers

- **2.5.1** - Browse Block Individual Tab Netflix Layout & Width Fixes
  - **CRITICAL: Browse Block Individual Tabs**
    - Individual tabs (Teachers, Topics, Playlists, Categories) MUST show Netflix-style VIDEO rows
    - Each term gets its own horizontal slider row with that term's videos
    - Example: Teachers tab shows "Alison Murdoch" row with her videos, "FDCW" row with their videos, etc.
    - HOME tab continues to show taxonomy term CARDS (discovery view)
    - Added `render_browse_section_with_videos()` function for individual tab rendering
  - **Full Width Fixes**
    - Added comprehensive `!important` width rules to all slider components
    - Added Elementor-specific CSS overrides for full width inside Elementor containers
    - Fixed JavaScript tab switching to remove inline styles that interfere with CSS
    - All slider containers (mm-slider-row, mm-slider-container, mm-slider-track) now properly 100% width
  - **Volume Control Fix**
    - Volume slider now visible by default (was hidden until hover)
    - Fixed volume value parsing (parseInt for proper numerical values)
  - **SoundCloud Player Improvements**
    - Rewrote player with explicit state tracking (isCurrentlyPlaying, widgetReady)
    - Fixed pause button not working
    - Fixed double-click required to play
  - **Modal Positioning**
    - Modal now moved to body element via JavaScript to escape CSS transform containment
    - Fixes issues with position:fixed inside Elementor containers
  - **Lock Icon on Playlist Embeds**
    - Protected playlist embeds now show lock badge in top-left corner

- **2.5.0** - Browse Block Netflix Layout & Bug Fixes
  - **Browse Block Redesign**
    - Teachers, Topics, Categories tabs now show Netflix-style rows with videos
    - Each term gets its own row with that term's videos in a horizontal slider
    - Replaces previous single slider of taxonomy term cards
  - **Template Changes**
    - Teacher pages now use multi-row grid layout (was horizontal sliders)
    - Topic pages now use multi-row grid layout
    - Created `taxonomy-media_category.php` template for category archives
    - Added `taxonomy_template` filter in class-post-types.php for all taxonomy templates
  - **Slider Arrow Redesign**
    - Removed gradient background overlays (now transparent)
    - Increased arrow size to 56px for better visibility
    - Added drop shadow for contrast
  - **Bug Fixes**
    - Fixed Vimeo player not responding to clicks (missing API queue)
    - Fixed Vimeo big play button not hiding when playing
    - Fixed cookie name mismatch in password protection
    - Fixed SoundCloud image disappearing on play
    - Improved Archive.org duration display timing
    - Fixed browse page full width layout
  - **CSS Improvements**
    - Consolidated duplicate grid definitions
    - Fixed card sizing consistency

- **2.4.0** - Embed Sizes & Taxonomy Archives
  - **New Shortcode: `[mindful_media_taxonomy_archive]`**
    - Full-page Netflix-style layout for any taxonomy
    - Each taxonomy term displayed as a horizontal row
    - Password protection support for playlists (shows lock, requires password)
    - Ideal for "Browse by Teacher", "Browse by Topic", "All Playlists" pages
  - **Embed Size Options**
    - New `size` attribute for `[mindful_media]` shortcode
    - Options: small (320px), medium (560px), large (800px), full (100%)
    - Custom px or % values also supported
    - Gutenberg block includes size dropdown
    - Default is "medium" (YouTube-style size)
  - **Slider Improvements**
    - Refined arrow positioning (top: 4px, matches thumbnail height)
    - Lighter gradient overlay (white-based for light themes)
    - Smaller, refined arrow icons (36px)
  - **Bug Fixes**
    - Fixed teacher/topic page title text (white on white issue)
    - Fixed card titles using explicit dark colors with !important
    - Password-protected playlists now properly gated on All Playlists page

- **2.3.0** - UI/UX Polish & Player Fixes
  - **Slider Navigation Improvements**
    - Slider arrows now 90% height (Netflix-style, covers most of card area)
    - Increased arrow icons to 48px with bolder stroke weight
    - Arrows centered over slider content area
    - Improved gradient overlay effect on arrows
    - Removed scroll-snap for continuous smooth scrolling (all sliders)
  - **Modal Player Fixes**
    - Fixed description/meta section overlapping playlist sidebar
    - Content section now properly constrained when sidebar is visible
    - Handles collapsed sidebar state correctly
  - **Audio Player Fixes**
    - Fixed big play button remaining visible when audio plays
    - Play button now properly hides using both CSS class and inline style
    - Play button shows again on pause/end events
    - Fixed SoundCloud player pause functionality
    - Fixed Archive.org audio pause functionality
  - **Password Protection**
    - Centered password form using fixed positioning
    - Form now covers full viewport properly
  - **Archive Display Settings**
    - Filter chips (Duration, Year, Type) now properly respect admin settings
    - Counts on filter chips can be hidden via settings
  - **CSS Updates**
    - Added `.hidden` class for big play button with `!important` rules
    - Improved pointer-events handling for player controls

- **2.2.0** - MAJOR UPDATE: Netflix-Style Redesign & UX Enhancements
  - Netflix-style horizontal sliders for browse sections (with swipe/keyboard navigation)
  - Featured section now shows 3 items in row (YouTube-style)
  - Section titles are now clickable links to full archives
  - Renamed "All Media" tab to "Home"
  - Created Topic archive template (taxonomy-media_topic.php)
  - New `[mindful_media_row]` shortcode for custom category rows
  - New Gutenberg "Category Row" block
  - New Elementor "Category Row" widget
  - Playlist template updated with Netflix-style slider rows for sub-playlists
  - Added Archive Display settings tab (control filter visibility, counters)
  - Added video/audio type icons on media cards
  - Removed duplicate playlist icon from top-left of playlist cards
  - Fixed playlist modal to show ALL sub-playlists hierarchically
  - Fixed missing playlist header in modal view
  - Fixed SoundCloud player play/pause state handling
  - Added skeleton loading animations
  - Added lazy loading for thumbnail images
  - Added keyboard navigation (Escape to close, Space to play/pause)
  - Added mobile swipe gestures for sliders
  - Improved playlist sidebar with collapsible module headers
  
- **2.1.25** - PRODUCTION RELEASE: Comprehensive cleanup and security hardening
  - Removed all debug logging (253+ statements)
  - Added nonce verification to password check AJAX handlers
  - Enhanced import/export security (file type validation, size limits, object injection prevention)
  - Removed legacy shortcodes `[mindful_media_filters]` and `[mindful_media_single]`
  - Removed dead code (render_taxonomy_checkboxes, ajax_batch_create_sessions)
  - Added Year and Duration to filter chips
  - Fixed mobile modal responsiveness (header positioning, video sizing)
  - Redesigned Getting Started page with WooCommerce-style UI
  - Cleaned up duplicate CSS variables and legacy filter styles
  - Updated README.md with current feature documentation
- **2.1.24** - CRITICAL FIX: Password check now includes PARENT playlist hierarchy. Items in child modules now correctly inherit password protection from parent playlist. Updated plugin version constant to bust browser cache.
- **2.1.23** - FIX PASSWORD MODAL: Playlist button has both classes (thumb-trigger AND playlist-watch-btn) causing dual handlers to fire. Added check to skip thumb-trigger handler for playlist buttons. Reset button loading state before showing password modal.
- **2.1.22** - Fix password protection modal CSS (ensure white text on red header), fix playlist first_item_id query (removed meta_key filter that was causing 0 items to be found, breaking Watch button)
- **2.1.21** - PRIVACY FIX: Playlist items now hidden from Audio/Video tabs and counts (items in playlists are private, only accessible via playlist). Fix modal description layout to match single page (proper spacing, max-width, borders)
- **2.1.20** - Fix close buttons (replace SVG with × text), add description/tags/meta to single video modal (like single page), add 1-hour cache for archive.org metadata to speed up loads
- **2.1.19** - Fix playlist query to get ALL videos (removed meta_key requirement), fix grey WATCH button bug (use CSS loading class instead of .text()), More Media only on non-playlist modals, fix video overlap with sidebar
- **2.1.18** - Fix Vimeo/YouTube player overlap with sidebar (constrain width to calc(100vw - 420px))
- **2.1.8** - Fix single page admin bar overlap, add chip-style categories/topics, add "More Media" related items section
- **2.1.7** - Add "Hide from Archive" visibility column to media and playlist lists, add Quick Edit support for visibility, update documentation
- **2.1.6** - Fix single page dark theme styling, fix chip hover color, fix close button using SVG on all modals, update documentation
- **2.1.5** - Fix close button SVG visibility in modal player
- **2.1.4** - Fix card click destroying thumbnail, fix playlist card to match YouTube style, fix sidebar/close button overlap, show all items when filtering by type
- **2.1.3** - Add Playlists tab, fix Video/Audio to show only items (not playlists), fix red background on click, fix embed block, fix title styling
- **2.1.2** - Fix title styling, single page dark theme, removed Filters button
- **2.1.1** - YouTube-style redesign, filter chips, modal-first navigation
- **2.0.x** - Previous versions with sidebar filters
