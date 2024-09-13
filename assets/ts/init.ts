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

    const doInit = (): void => {
        fetch('/bundles/easy-gutenberg/fetch-blocks', {headers: {accept: 'application/json'}}).then((response: Response) => response.json()).then((data): void => {
            for (const [key, options] of Object.entries(data)) {
                registerServerBlockType(key, options)
            }

            initializeEditor(element, {...defaultSettings, ...settings})
        });
    };

    fetch('/bundles/easygutenberg/translations/' + document.documentElement.lang + '.json', {redirect: 'manual'}).then((response: Response): void => {
        if (response.ok) {
            response.json().then(translation => {
                defaultI18n.setLocaleData(translation.locale_data.messages)
                doInit()
            });
        } else {
            doInit()
        }
    })
}
