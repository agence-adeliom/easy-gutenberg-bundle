import qs from 'qs'

const oembedHandler = async (options) => {
    const [path, query] = options.path.split('?')
    const params = qs.parse(query)
    const url = new URL('/bundles/easy-gutenberg/oembed', window.location.origin)
    if(params.url){
        url.searchParams.append('url', <string>params.url)
    }
    const res = await fetch(url.toString())
    return await res.json()
}

export default oembedHandler
