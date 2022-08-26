/**
 * External dependencies
 */
import { isEqual } from 'lodash';

/**
 * WordPress dependencies
 */
import { useDebounce, usePrevious } from '@wordpress/compose';
import {createElement, useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { Placeholder, Spinner } from '@wordpress/components';
import { __experimentalSanitizeBlockAttributes } from '@wordpress/blocks';
import InnerHTML from 'dangerously-set-html-content'
import ReactDOM from 'react-dom';

export function rendererPath( block, attributes = null, urlQueryArgs = {} ) {
    return addQueryArgs( `/wp/v2/block-renderer/${ block }`, {
        context: 'edit',
        ...( null !== attributes ? { attributes } : {} ),
        ...urlQueryArgs,
    } );
}

function DefaultEmptyResponsePlaceholder( { className } ) {
    return (
        <Placeholder className={ className }>
            { __( 'Block rendered as empty.' ) }
        </Placeholder>
    );
}

function DefaultErrorResponsePlaceholder( { response, className } ) {
    const errorMessage = sprintf(
        // translators: %s: error message describing the problem
        __( 'Error loading block: %s' ),
        response.errorMsg
    );
    return <Placeholder className={ className }>{ errorMessage }</Placeholder>;
}

function DefaultLoadingResponsePlaceholder( { children, showLoader } ) {
    return (
        <div style={ { position: 'relative' } }>
            { showLoader && (
                <div
                    style={ {
                        position: 'absolute',
                        top: '50%',
                        left: '50%',
                        marginTop: '-9px',
                        marginLeft: '-9px',
                    } }
                >
                    <Spinner />
                </div>
            ) }
            <div style={ { opacity: showLoader ? '0.3' : 1 } }>
                { children }
            </div>
        </div>
    );
}

function loadScript(src) {
    return new Promise<void>(function (resolve, reject) {
        let script = document.createElement('script');
        if(document.querySelectorAll(`script[src="${src}"]`).length > 0){
            resolve()
        }else {
            script.src = src;
            script.type = "text/javascript"

            // @ts-ignore
            script.onload = () => resolve(script);
            script.onerror = () => reject(new Error(`Style load error for ${src}`));
            document.head.append(script);
        }
    });
}

export default function EasyServerSideRender(this: any, props) {
    const {
        attributes,
        block,
        className,
        httpMethod = 'GET',
        urlQueryArgs,
        onRequestComplete,
        EmptyResponsePlaceholder = DefaultEmptyResponsePlaceholder,
        ErrorResponsePlaceholder = DefaultErrorResponsePlaceholder,
        LoadingResponsePlaceholder = DefaultLoadingResponsePlaceholder,
    } = props;

    const isMountedRef = useRef(true);
    let innerHtmlRef = useRef(null);
    const [showLoader, setShowLoader] = useState(false);
    const fetchRequestRef = useRef();
    const [response, setResponse] = useState(null);
    const prevProps = usePrevious(props);
    const [isLoading, setIsLoading] = useState(false);
    // @ts-ignore

    const fetchData = () => {
        if (!isMountedRef.current) {
            return;
        }
        setIsLoading(true);

        const sanitizedAttributes =
            attributes &&
            __experimentalSanitizeBlockAttributes(block, attributes);

        // If httpMethod is 'POST', send the attributes in the request body instead of the URL.
        // This allows sending a larger attributes object than in a GET request, where the attributes are in the URL.
        const isPostRequest = 'POST' === httpMethod;
        const urlAttributes = isPostRequest
            ? null
            : sanitizedAttributes ?? null;
        const path = rendererPath(block, urlAttributes, urlQueryArgs);
        const data = isPostRequest
            ? {attributes: sanitizedAttributes ?? null}
            : null;

        // Store the latest fetch request so that when we process it, we can
        // check if it is the current request, to avoid race conditions on slow networks.
        // @ts-ignore
        const fetchRequest = (fetchRequestRef.current = apiFetch({
            path,
            data,
            method: isPostRequest ? 'POST' : 'GET',
        })
            .then((fetchResponse) => {
                if (
                    isMountedRef.current &&
                    fetchRequest === fetchRequestRef.current &&
                    fetchResponse
                ) {
                    let tmpHTML = document.createElement("div");

                    // @ts-ignore
                    tmpHTML.innerHTML = fetchResponse.rendered;
                    // @ts-ignore
                    if(fetchResponse.assets){
                        // @ts-ignore
                        tmpHTML.innerHTML += fetchResponse.assets;
                    }

                    let remote = [];
                    Array.from(tmpHTML.querySelectorAll("script")).forEach(oldScript => {
                        if (oldScript.src) {
                            // @ts-ignore
                            remote.push(loadScript(oldScript.src));
                        }
                    });
                    Promise.all(remote).then(values => {
                        onRequestComplete(fetchResponse)
                        if(!document.getElementById("tailwind-config")){
                            let script = document.createElement('script');
                            script.id = "tailwind-config"
                            script.text = 'if(typeof tailwind !== \'undefined\') { tailwind.config = { corePlugins: { preflight: false } }}'
                            document.head.append(script);
                        }
                        // @ts-ignore
                        setResponse(fetchResponse.rendered);
                    })
                }
            })
            .catch((error) => {
                if (
                    isMountedRef.current &&
                    fetchRequest === fetchRequestRef.current
                ) {
                    onRequestComplete(error)
                    // @ts-ignore
                    setResponse(prevState => {
                        return {
                            error: true,
                            errorMsg: error.message,
                        }
                    });
                }
            })
            .finally(() => {
                if (
                    isMountedRef.current &&
                    fetchRequest === fetchRequestRef.current
                ) {
                    setIsLoading(false);
                }
            }));

        return fetchRequest;
    }

    const debouncedFetchData = useDebounce(fetchData, 500);

    // When the component unmounts, set isMountedRef to false. This will
    // let the async fetch callbacks know when to stop.
    useEffect(
        () => () => {
            isMountedRef.current = false;
        },
        []
    );

    useEffect(() => {
        // Don't debounce the first fetch. This ensures that the first render
        // shows data as soon as possible.
        if (prevProps === undefined) {
            fetchData();
        } else if (!isEqual(prevProps.attributes.mode, props.attributes.mode)) {
            debouncedFetchData();
        }
    });

    /**
     * Effect to handle showing the loading placeholder.
     * Show it only if there is no previous response or
     * the request takes more than one second.
     */
    useEffect(() => {
        if (!isLoading) {
            return;
        }
        const timeout = setTimeout(() => {
            setShowLoader(true);
        }, 1000);
        return () => clearTimeout(timeout);
    }, [isLoading]);

    const hasResponse = !!response;
    const hasEmptyResponse = response === '';
    // @ts-ignore
    const hasError = response?.error;

    if (isLoading) {
        return (
            <LoadingResponsePlaceholder {...props} showLoader={showLoader}>
                {hasResponse && (
                    <InnerHTML className={className} html={response}/>
                )}
            </LoadingResponsePlaceholder>
        );
    }

    if (hasEmptyResponse || !hasResponse) {
        return <EmptyResponsePlaceholder {...props} />;
    }

    if (hasError) {
        return <ErrorResponsePlaceholder response={response} {...props} />;
    }

    return <InnerHTML ref={innerHtmlRef} className={className} html={response}/>
}
