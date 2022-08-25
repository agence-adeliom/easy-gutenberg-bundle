import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useBlockProps, BlockControls } from '@wordpress/block-editor';
import {
    ToolbarGroup,
    ToolbarButton
} from '@wordpress/components';
import EasyServerSideRender from "./editor/components/EasyServerSideRender";
import {createElement, createRef, RawHTML, useRef} from '@wordpress/element'
import {isEqual} from "lodash";
import isSvg from 'is-svg';
import transform from '@balajmarius/svg2jsx';
import InnerHTML from "dangerously-set-html-content";

export const buildObject = (obj, name, value) => {
    // replace [] with placeholder
    name = name.replace('[]', '[%%index%%]'); // vars

    var keys = name.match(/([^\[\]])+/g);
    if (!keys) return;
    var length = keys.length;
    var ref = obj; // loop

    for (var i = 0; i < length; i++) {
        // vars
        var key = String(keys[i]); // value

        if (i == length - 1) {
            // %%index%%
            if (key === '%%index%%') {
                ref.push(value); // default
            } else {
                ref[key] = value;
            } // path

        } else {
            // array
            if (keys[i + 1] === '%%index%%') {
                if (!Array.isArray(ref[key])) {
                    ref[key] = [];
                } // object

            } else {
                if (!(typeof ref[key] === "object")) {
                    ref[key] = {};
                }
            } // crawl

            ref = ref[key];
        }
    }
}

type SerializedValue = {
    name: string;
    value: any;
};

export const serializeArray = ($el): SerializedValue[] => {
    let arr = [];
    Array.prototype.slice.call($el.querySelectorAll('select, textarea, input')).forEach((field: any) => {
        if (!field.name || field.disabled || ['file', 'reset', 'submit', 'button'].indexOf(field.type) > -1) return;
        if (field.type === 'select-multiple') {
            Array.prototype.slice.call(field.options).forEach(function (option) {
                if (!option.selected) return;
                // @ts-ignore
                arr.push({ name: field.name, value: option.value });
            });
            return;
        }
        if (['checkbox', 'radio'].indexOf(field.type) >-1 && !field.checked) return;

        // @ts-ignore
        arr.push({ name: field.name, value: field.value });
    });

    return arr;
}

export const serialize = ($el, prefix?:string) => {
    // vars
    let obj = {};
    let inputs = serializeArray($el); // prefix

    if (prefix) {
        // filter and modify
        inputs = inputs.filter((item: SerializedValue) => {
            return item.name.indexOf(prefix) === 0;
        }).map((item: SerializedValue) => {
            item.name = item.name.slice(prefix.length);
            return item;
        });
    } // loop

    for (let i = 0; i < inputs.length; i++) {
        buildObject(obj, inputs[i].name, inputs[i].value);
    } // return

    let data = obj[Object.keys(obj)[0]];
    if(data?.hasOwnProperty('_token')){
        delete data._token;
    }
    return data;
}

export const registerServerBlockType = (blockTypeName, options) => {
    const blockAttributes = options.attributes ? options.attributes : {};
    let blockVariations = options.variations ? options.variations : [];
    let icon = options.icon ?? 'default';
    if (isSvg(icon)){
        icon = <InnerHTML html={icon} />;
    }

    if(blockVariations){
        blockVariations.map((variation) => {
            if(variation.icon){
                variation.icon = <InnerHTML html={variation.icon} />;
            }
            return variation;
        })
    }

    delete options.icon;
    delete options.attributes;
    delete options.variations;

    const defaultOptions = {
        apiVersion: 2,
        attributes: {
            mode: {
                type: 'string',
                default: 'edit'
            },
            variant: {
                type: 'string'
            },
            data: {
                type: 'object'
            },
            ...blockAttributes
        },
        variations: blockVariations,
        icon: icon,
        examples: {mode: "preview"},
        edit: (props) => {
            const { attributes, setAttributes, isSelected } = props
            let formElement = useRef(null);
            let timeout;

            if(!attributes.data && formElement?.current && attributes.mode == 'edit'){
                const form = formElement.current;
                setAttributes({...attributes, data: serialize(form)});
            }

            const getIcon = () => {
                return attributes.mode == "edit" ?
                    '<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" role="img" width="24" height="24" preserveAspectRatio="xMidYMid meet" viewBox="0 0 20 20"><path fill="currentColor" d="M18 14V4c0-.55-.45-1-1-1H3c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h14c.55 0 1-.45 1-1zm-8-8c2.3 0 4.4 1.14 6 3c-1.6 1.86-3.7 3-6 3s-4.4-1.14-6-3c1.6-1.86 3.7-3 6-3zm2 3c0-1.1-.9-2-2-2s-2 .9-2 2s.9 2 2 2s2-.9 2-2zm2 8h3v1H3v-1h3v-1h8v1z"/></svg>'
                    : '<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" role="img" width="24" height="24" preserveAspectRatio="xMidYMid meet" viewBox="0 0 20 20"><path fill="currentColor" d="m13.89 3.39l2.71 2.72c.46.46.42 1.24.03 1.64l-8.01 8.02l-5.56 1.16l1.16-5.58s7.6-7.63 7.99-8.03c.39-.39 1.22-.39 1.68.07zm-2.73 2.79l-5.59 5.61l1.11 1.11l5.54-5.65zm-2.97 8.23l5.58-5.6l-1.07-1.08l-5.59 5.6z"/></svg>';
            }

            const refreshEvent = (event) => {
                event.preventDefault();
                event.stopPropagation();
                clearTimeout(timeout);
                if(attributes.mode == "edit"){
                    timeout = setTimeout(() => {
                        const form = formElement.current;
                        setAttributes({...attributes, data: serialize(form)});
                    }, 300);
                }
            }

            const setupFormEvent = () => {
                if (formElement?.current){
                    const form = formElement.current;
                    ('change keyup'.split(' ')).forEach((eventName: string) => {
                        //@ts-ignore
                        form.removeEventListener(eventName, refreshEvent);
                        //@ts-ignore
                        form.addEventListener(eventName, refreshEvent);
                    });
                }
            }
            setupFormEvent();

            return (
                <div { ...useBlockProps() }>
                    {
                        isSelected ?
                            <BlockControls>
                                <ToolbarGroup>
                                    <ToolbarButton
                                        label={ attributes.mode == "preview" ? __("Edit") : __("Preview") }
                                        onClick={() => {
                                            let attrs: { mode?: string; data?: object|unknown } = { mode: attributes.mode == 'preview' ? 'edit' : 'preview'}

                                            if (formElement?.current && attributes.mode == 'edit'){
                                                const form = formElement.current;
                                                attrs = {...attrs, data: serialize(form)}
                                            }

                                            setAttributes(attrs)
                                        }}
                                    >
                                        <RawHTML>{getIcon()}</RawHTML>
                                    </ToolbarButton>
                                </ToolbarGroup>
                            </BlockControls>
                            : null
                    }
                    <div
                        ref={formElement}
                    >
                        <EasyServerSideRender
                            block={blockTypeName}
                            attributes={attributes}
                            onRequestComplete={(e) => {
                                if(!isEqual( attributes, e.attributes )){
                                    setAttributes(e.attributes)
                                }
                            }}
                        />
                    </div>
                </div>
            );
        },
        save: (props) => {
            return null;
        }
    };

    registerBlockType(blockTypeName, {...defaultOptions, ...options})
}
