import EditorSettings from "../interfaces/editor-settings";

const defaultSettings: EditorSettings = {
    // Laraberg settings
    height: '800px',
    mediaUpload: undefined,
    disabledCoreBlocks: [
        'core/embed',
        'core/freeform',
        'core/shortcode',
        'core/archives',
        'core/tag-cloud',
        'core/block',
        'core/rss',
        'core/search',
        'core/calendar',
        'core/categories',
        'core/more',
        'core/nextpage'
    ],

    // WordPress settings
    alignWide: true,
    hasFixedToolbar: false,
    supportsLayout: true,
}

export default defaultSettings
