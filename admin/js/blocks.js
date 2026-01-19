/**
 * MindfulMedia Gutenberg Blocks
 */

(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, useBlockProps, PanelColorSettings } = wp.blockEditor;
    const { PanelBody, SelectControl, ToggleControl, RangeControl, TextControl, FontSizePicker } = wp.components;
    const { __ } = wp.i18n;
    const { serverSideRender: ServerSideRender } = wp;
    const { createElement: el } = wp.element;
    
    // Default font sizes
    const fontSizes = [
        { name: 'Small', slug: 'small', size: 12 },
        { name: 'Normal', slug: 'normal', size: 16 },
        { name: 'Medium', slug: 'medium', size: 20 },
        { name: 'Large', slug: 'large', size: 24 },
        { name: 'Extra Large', slug: 'xlarge', size: 32 }
    ];
    
    // Get data passed from PHP
    const { mediaItems, playlists, teachers, categories, types, topics } = window.mindfulMediaBlocks || {};
    
    // ==========================================
    // Browse Block
    // ==========================================
    registerBlockType('mindful-media/browse', {
        title: __('MindfulMedia Browse', 'mindful-media'),
        description: __('Display a browse/landing page with clickable category headers for Teachers, Topics, Playlists, etc.', 'mindful-media'),
        icon: 'grid-view',
        category: 'media',
        keywords: [__('media', 'mindful-media'), __('browse', 'mindful-media'), __('landing', 'mindful-media'), __('navigation', 'mindful-media')],
        supports: {
            align: ['wide', 'full'],
            html: false
        },
        attributes: {
            show: { type: 'string', default: 'all' },
            layout: { type: 'string', default: 'cards' },
            featured: { type: 'boolean', default: false },
            columns: { type: 'number', default: 4 },
            limit: { type: 'number', default: 12 },
            title: { type: 'string', default: '' },
            showCounts: { type: 'boolean', default: true },
            className: { type: 'string', default: '' },
            // Style attributes
            backgroundColor: { type: 'string', default: '#ffffff' },
            textColor: { type: 'string', default: '#0f0f0f' },
            headingColor: { type: 'string', default: '#0f0f0f' },
            cardBgColor: { type: 'string', default: '#ffffff' },
            navBgColor: { type: 'string', default: '#f2f2f2' },
            titleFontSize: { type: 'number', default: 24 }
        },
        
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const blockProps = useBlockProps({
                className: 'mindful-media-block-browse'
            });
            
            const showOptions = [
                { value: 'all', label: __('All Sections', 'mindful-media') },
                { value: 'navigation', label: __('Navigation Only', 'mindful-media') },
                { value: 'teachers', label: __('Teachers', 'mindful-media') },
                { value: 'topics', label: __('Topics', 'mindful-media') },
                { value: 'playlists', label: __('Playlists & Series', 'mindful-media') },
                { value: 'types', label: __('Media Types', 'mindful-media') },
                { value: 'categories', label: __('Categories', 'mindful-media') },
                { value: 'teachers,topics', label: __('Teachers & Topics', 'mindful-media') },
                { value: 'playlists,types', label: __('Playlists & Types', 'mindful-media') }
            ];
            
            const layoutOptions = [
                { value: 'cards', label: __('Cards', 'mindful-media') },
                { value: 'banners', label: __('Banners', 'mindful-media') },
                { value: 'list', label: __('List', 'mindful-media') }
            ];
            
            return el('div', blockProps,
                el(InspectorControls, {},
                    el(PanelBody, { title: __('Display Settings', 'mindful-media'), initialOpen: true },
                        el(SelectControl, {
                            label: __('Sections to Show', 'mindful-media'),
                            value: attributes.show,
                            options: showOptions,
                            onChange: function(value) { setAttributes({ show: value }); }
                        }),
                        el(SelectControl, {
                            label: __('Layout', 'mindful-media'),
                            value: attributes.layout,
                            options: layoutOptions,
                            onChange: function(value) { setAttributes({ layout: value }); }
                        }),
                        el(RangeControl, {
                            label: __('Columns', 'mindful-media'),
                            value: attributes.columns,
                            onChange: function(value) { setAttributes({ columns: value }); },
                            min: 2,
                            max: 6
                        }),
                        el(RangeControl, {
                            label: __('Items per Section', 'mindful-media'),
                            value: attributes.limit,
                            onChange: function(value) { setAttributes({ limit: value }); },
                            min: 4,
                            max: 24
                        })
                    ),
                    el(PanelBody, { title: __('Featured Content', 'mindful-media'), initialOpen: false },
                        el(ToggleControl, {
                            label: __('Show Featured Hero', 'mindful-media'),
                            checked: attributes.featured,
                            onChange: function(value) { setAttributes({ featured: value }); },
                            help: __('Display a hero section with featured content', 'mindful-media')
                        })
                    ),
                    el(PanelBody, { title: __('Additional Options', 'mindful-media'), initialOpen: false },
                        el(TextControl, {
                            label: __('Custom Title', 'mindful-media'),
                            value: attributes.title,
                            onChange: function(value) { setAttributes({ title: value }); },
                            help: __('Optional title above the browse content', 'mindful-media')
                        }),
                        el(ToggleControl, {
                            label: __('Show Item Counts', 'mindful-media'),
                            checked: attributes.showCounts,
                            onChange: function(value) { setAttributes({ showCounts: value }); }
                        })
                    ),
                    el(PanelBody, { title: __('Style Settings', 'mindful-media'), initialOpen: false },
                        el(FontSizePicker, {
                            fontSizes: fontSizes,
                            value: attributes.titleFontSize,
                            onChange: function(value) { setAttributes({ titleFontSize: value }); },
                            fallbackFontSize: 24
                        })
                    ),
                    PanelColorSettings && el(PanelColorSettings, {
                        title: __('Color Settings', 'mindful-media'),
                        initialOpen: false,
                        colorSettings: [
                            {
                                value: attributes.backgroundColor,
                                onChange: function(value) { setAttributes({ backgroundColor: value }); },
                                label: __('Background Color', 'mindful-media')
                            },
                            {
                                value: attributes.textColor,
                                onChange: function(value) { setAttributes({ textColor: value }); },
                                label: __('Text Color', 'mindful-media')
                            },
                            {
                                value: attributes.headingColor,
                                onChange: function(value) { setAttributes({ headingColor: value }); },
                                label: __('Heading Color', 'mindful-media')
                            },
                            {
                                value: attributes.cardBgColor,
                                onChange: function(value) { setAttributes({ cardBgColor: value }); },
                                label: __('Card Background', 'mindful-media')
                            },
                            {
                                value: attributes.navBgColor,
                                onChange: function(value) { setAttributes({ navBgColor: value }); },
                                label: __('Navigation Background', 'mindful-media')
                            }
                        ]
                    })
                ),
                el(ServerSideRender, {
                    block: 'mindful-media/browse',
                    attributes: attributes
                })
            );
        },
        
        save: function() {
            return null; // Server-side rendered
        }
    });
    
    // ==========================================
    // Media Embed Block
    // ==========================================
    registerBlockType('mindful-media/embed', {
        title: __('MindfulMedia Embed', 'mindful-media'),
        description: __('Embed a single media item or playlist with modal player support.', 'mindful-media'),
        icon: 'format-video',
        category: 'media',
        keywords: [__('media', 'mindful-media'), __('video', 'mindful-media'), __('audio', 'mindful-media'), __('embed', 'mindful-media')],
        supports: {
            align: ['left', 'center', 'right', 'wide'],
            html: false
        },
        attributes: {
            mediaId: { type: 'number', default: 0 },
            playlistSlug: { type: 'string', default: '' },
            showThumbnail: { type: 'boolean', default: true },
            autoplay: { type: 'boolean', default: false },
            size: { type: 'string', default: 'medium' },
            className: { type: 'string', default: '' }
        },
        
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const blockProps = useBlockProps({
                className: 'mindful-media-block-embed'
            });
            
            return el('div', blockProps,
                el(InspectorControls, {},
                    el(PanelBody, { title: __('Media Selection', 'mindful-media'), initialOpen: true },
                        el(SelectControl, {
                            label: __('Select Media Item', 'mindful-media'),
                            value: attributes.mediaId,
                            options: mediaItems || [],
                            onChange: function(value) { 
                                setAttributes({ mediaId: parseInt(value), playlistSlug: '' }); 
                            },
                            help: __('Choose a single media item to embed', 'mindful-media')
                        }),
                        el('p', { style: { textAlign: 'center', margin: '15px 0', color: '#666' } }, __('— or —', 'mindful-media')),
                        el(SelectControl, {
                            label: __('Select Playlist', 'mindful-media'),
                            value: attributes.playlistSlug,
                            options: playlists || [],
                            onChange: function(value) { 
                                setAttributes({ playlistSlug: value, mediaId: 0 }); 
                            },
                            help: __('Choose a playlist to embed', 'mindful-media')
                        })
                    ),
                    el(PanelBody, { title: __('Display Options', 'mindful-media'), initialOpen: true },
                        el(SelectControl, {
                            label: __('Size', 'mindful-media'),
                            value: attributes.size,
                            options: [
                                { value: 'small', label: __('Small (320px)', 'mindful-media') },
                                { value: 'medium', label: __('Medium (560px)', 'mindful-media') },
                                { value: 'large', label: __('Large (800px)', 'mindful-media') },
                                { value: 'full', label: __('Full Width', 'mindful-media') }
                            ],
                            onChange: function(value) { setAttributes({ size: value }); },
                            help: __('Choose the maximum width of the embed', 'mindful-media')
                        }),
                        el(ToggleControl, {
                            label: __('Show Thumbnail with Play Button', 'mindful-media'),
                            checked: attributes.showThumbnail,
                            onChange: function(value) { setAttributes({ showThumbnail: value }); },
                            help: __('If disabled, embeds player directly on the page', 'mindful-media')
                        }),
                        el(ToggleControl, {
                            label: __('Autoplay', 'mindful-media'),
                            checked: attributes.autoplay,
                            onChange: function(value) { setAttributes({ autoplay: value }); }
                        })
                    )
                ),
                el(ServerSideRender, {
                    block: 'mindful-media/embed',
                    attributes: attributes
                })
            );
        },
        
        save: function() {
            return null; // Server-side rendered
        }
    });
    
    // ==========================================
    // Archive Block
    // ==========================================
    registerBlockType('mindful-media/archive', {
        title: __('MindfulMedia Archive', 'mindful-media'),
        description: __('Display the full media archive with filters and pagination.', 'mindful-media'),
        icon: 'portfolio',
        category: 'media',
        keywords: [__('media', 'mindful-media'), __('archive', 'mindful-media'), __('library', 'mindful-media'), __('gallery', 'mindful-media')],
        supports: {
            align: ['wide', 'full'],
            html: false
        },
        attributes: {
            perPage: { type: 'number', default: 12 },
            showFilters: { type: 'boolean', default: true },
            showPagination: { type: 'boolean', default: true },
            category: { type: 'string', default: '' },
            type: { type: 'string', default: '' },
            teacher: { type: 'string', default: '' },
            className: { type: 'string', default: '' },
            // Style attributes
            backgroundColor: { type: 'string', default: '#ffffff' },
            textColor: { type: 'string', default: '#333333' },
            accentColor: { type: 'string', default: '#8B0000' }
        },
        
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const blockProps = useBlockProps({
                className: 'mindful-media-block-archive'
            });
            
            return el('div', blockProps,
                el(InspectorControls, {},
                    el(PanelBody, { title: __('Display Settings', 'mindful-media'), initialOpen: true },
                        el(RangeControl, {
                            label: __('Items per Page', 'mindful-media'),
                            value: attributes.perPage,
                            onChange: function(value) { setAttributes({ perPage: value }); },
                            min: 4,
                            max: 48,
                            step: 4
                        }),
                        el(ToggleControl, {
                            label: __('Show Filters Sidebar', 'mindful-media'),
                            checked: attributes.showFilters,
                            onChange: function(value) { setAttributes({ showFilters: value }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show Pagination', 'mindful-media'),
                            checked: attributes.showPagination,
                            onChange: function(value) { setAttributes({ showPagination: value }); }
                        })
                    ),
                    el(PanelBody, { title: __('Pre-filter Content', 'mindful-media'), initialOpen: false },
                        el(SelectControl, {
                            label: __('Category', 'mindful-media'),
                            value: attributes.category,
                            options: categories || [],
                            onChange: function(value) { setAttributes({ category: value }); }
                        }),
                        el(SelectControl, {
                            label: __('Media Type', 'mindful-media'),
                            value: attributes.type,
                            options: types || [],
                            onChange: function(value) { setAttributes({ type: value }); }
                        }),
                        el(SelectControl, {
                            label: __('Teacher', 'mindful-media'),
                            value: attributes.teacher,
                            options: teachers || [],
                            onChange: function(value) { setAttributes({ teacher: value }); }
                        })
                    ),
                    PanelColorSettings && el(PanelColorSettings, {
                        title: __('Color Settings', 'mindful-media'),
                        initialOpen: false,
                        colorSettings: [
                            {
                                value: attributes.backgroundColor,
                                onChange: function(value) { setAttributes({ backgroundColor: value }); },
                                label: __('Background Color', 'mindful-media')
                            },
                            {
                                value: attributes.textColor,
                                onChange: function(value) { setAttributes({ textColor: value }); },
                                label: __('Text Color', 'mindful-media')
                            },
                            {
                                value: attributes.accentColor,
                                onChange: function(value) { setAttributes({ accentColor: value }); },
                                label: __('Accent/Link Color', 'mindful-media')
                            }
                        ]
                    })
                ),
                el(ServerSideRender, {
                    block: 'mindful-media/archive',
                    attributes: attributes
                })
            );
        },
        
        save: function() {
            return null; // Server-side rendered
        }
    });
    
})(window.wp);
