import fetchHandler from './fetch-handler'
import EditorSettings, {Color, FontSize, Gradient} from "./editor/interfaces/editor-settings"

const defaultSettings: EditorSettings = {
    fetchHandler,
    mediaUpload: undefined,
    alignWide: true,
    hasFixedToolbar: false,
    supportsLayout: true,
    disabledCoreBlocks: [
        'core/paragraph',
        'core/image',
        'core/heading',
        'core/gallery',
        'core/list',
        'core/quote',
        'core/audio',
        'core/button',
        'core/buttons',
        'core/code',
        'core/columns',
        'core/column',
        'core/cover',
        'core/file',
        'core/group',
        'core/html',
        'core/media-text',
        'core/missing',
        'core/preformatted',
        'core/pullquote',
        'core/separator',
        'core/social-links',
        'core/social-link',
        'core/spacer',
        'core/table',
        'core/text-columns',
        'core/verse',
        'core/video',
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
    ]
}

export default defaultSettings


