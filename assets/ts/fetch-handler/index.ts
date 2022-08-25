import { APIFetchOptions, FetchHandler } from '@wordpress/api-fetch'
import Router from './routing/router'
import routes from './routes'
import handlerNotFound from "./handlers/handler-not-found";

const router = new Router(routes)

const fetchHandler: FetchHandler = (options: APIFetchOptions): Promise<any> => {
    const route = router.match(options)

    if (!route) {
        return handlerNotFound(options)
    }

    return route.handle(options)
}

export default fetchHandler
