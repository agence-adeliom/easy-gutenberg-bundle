import EditorSettings from "./editor/interfaces/editor-settings";
import { initializeEditor, wordpress, registerBlockType } from "./editor";
import defaultSettings from "./default-settings";
import { defaultI18n, __ } from '@wordpress/i18n';
import {registerServerBlockType} from "./utils";

export const init = (
    target: string|HTMLInputElement|HTMLTextAreaElement,
    settings: EditorSettings = {}
) => {
    let element

    if (typeof target === 'string') {
        element = document.getElementById(target) || document.querySelector(target)
    } else {
        element = target
    }

    if (!element) {
        return
    }

    const editorSettings = { ...defaultSettings, ...settings };
    const lang_fr = require("./lang/fr.json");
    defaultI18n.setLocaleData(lang_fr);

    fetch('/bundles/easy-gutenberg/fetch-blocks', {
        headers: {
            'Accept': 'application/json'
        }
    }).then((response) => response.json())
    .then((data) => {
        for (const [key, options] of Object.entries(data)) {
            registerServerBlockType(key, options);
        }
        initializeEditor(element, editorSettings)
    })

}
