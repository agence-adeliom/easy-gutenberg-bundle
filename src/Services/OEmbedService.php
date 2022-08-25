<?php

namespace Adeliom\EasyGutenbergBundle\Services;

use Adeliom\EasyGutenbergBundle\Exceptions\OEmbedFetchException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OEmbedService
{
    public const FORMATS = ['json', 'xml'];
    private HttpClientInterface $httpClient;
    private CacheInterface $cache;
    private ParameterBagInterface $parameters;

    public function __construct(HttpClientInterface $httpClient, CacheInterface $cache, ParameterBagInterface $parameters)
    {
        $this->cache = $cache;
        $this->httpClient = $httpClient;
        $this->parameters = $parameters;
    }

    /**
     * Get OEmbed data from a URL.
     *
     * @throws OEmbedFetchException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function parse(string $url): ?array
    {
        $key = "gutenberg_embed_parse_{$url}";

        return $this->cache->get($key, function (ItemInterface $item) use ($url) {
            $item->expiresAfter($this->parameters->get('easy_gutenberg.embed.cache.duration'));

            return $this->fetch(
                $this->getEndpointUrl($url),
                compact('url')
            );
        });
    }

    /**
     * Get default args for OEmbed requests.
     */
    protected function defaultArgs(): array
    {
        $maxwidth = (int) $this->parameters->get('easy_gutenberg.embed.maxwidth');
        $maxheight = (int) ($this->parameters->get('easy_gutenberg.embed.maxheight') ?? min($maxwidth * 1.5, 1000));
        $dnt = 1;

        return compact('maxwidth', 'maxheight', 'dnt');
    }

    /**
     * Fetch OEmbed data for a url and args.
     *
     * @throws OEmbedFetchException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    protected function fetch(string $url, array $args): array
    {
        $data = null;

        foreach (static::FORMATS as $format) {
            $res = $this->httpClient->request(
                'GET',
                str_replace('{format}', $format, $url),
                array_merge($this->defaultArgs(), ['format' => $format], $args)
            );

            if (200 === $res->getStatusCode()) {
                $data = $res->toArray();
                break;
            }
        }

        if (!is_array($data)) {
            throw new OEmbedFetchException();
        }

        return $data;
    }

    /**
     * Get the OEmbed endpoint for a URL.
     */
    protected function getEndpointUrl(string $url): ?string
    {
        $first = current(array_filter($this->getProviders(), function ($arr, $regex) use ($url) {
            return preg_match($regex, $url);
        }, ARRAY_FILTER_USE_BOTH));

        return $first[0] ?? null;
    }

    /**
     * Get all OEmbed providers
     * Taken from: https://github.com/WordPress/WordPress/blob/master/wp-includes/class-wp-oembed.php.
     *
     * @return array[]
     */
    protected function getProviders(): array
    {
        return [
            '#https?://((m|www)\.)?youtube\.com/watch.*#i' => ['https://www.youtube.com/oembed', true],
            '#https?://((m|www)\.)?youtube\.com/playlist.*#i' => ['https://www.youtube.com/oembed', true],
            '#https?://youtu\.be/.*#i' => ['https://www.youtube.com/oembed', true],
            '#https?://(.+\.)?vimeo\.com/.*#i' => ['https://vimeo.com/api/oembed.{format}', true],
            '#https?://(www\.)?dailymotion\.com/.*#i' => ['https://www.dailymotion.com/services/oembed', true],
            '#https?://dai\.ly/.*#i' => ['https://www.dailymotion.com/services/oembed', true],
            '#https?://(www\.)?flickr\.com/.*#i' => ['https://www.flickr.com/services/oembed/', true],
            '#https?://flic\.kr/.*#i' => ['https://www.flickr.com/services/oembed/', true],
            '#https?://(.+\.)?smugmug\.com/.*#i' => ['https://api.smugmug.com/services/oembed/', true],
            '#https?://(www\.)?scribd\.com/(doc|document)/.*#i' => ['https://www.scribd.com/services/oembed', true],
            '#https?://wordpress\.tv/.*#i' => ['https://wordpress.tv/oembed/', true],
            '#https?://(.+\.)?polldaddy\.com/.*#i' => ['https://api.crowdsignal.com/oembed', true],
            '#https?://poll\.fm/.*#i' => ['https://api.crowdsignal.com/oembed', true],
            '#https?://(.+\.)?survey\.fm/.*#i' => ['https://api.crowdsignal.com/oembed', true],
            '#https?://(www\.)?twitter\.com/\w{1,15}/status(es)?/.*#i' => ['https://publish.twitter.com/oembed', true],
            '#https?://(www\.)?twitter\.com/\w{1,15}$#i' => ['https://publish.twitter.com/oembed', true],
            '#https?://(www\.)?twitter\.com/\w{1,15}/likes$#i' => ['https://publish.twitter.com/oembed', true],
            '#https?://(www\.)?twitter\.com/\w{1,15}/lists/.*#i' => ['https://publish.twitter.com/oembed', true],
            '#https?://(www\.)?twitter\.com/\w{1,15}/timelines/.*#i' => ['https://publish.twitter.com/oembed', true],
            '#https?://(www\.)?twitter\.com/i/moments/.*#i' => ['https://publish.twitter.com/oembed', true],
            '#https?://(www\.)?soundcloud\.com/.*#i' => ['https://soundcloud.com/oembed', true],
            '#https?://(.+?\.)?slideshare\.net/.*#i' => ['https://www.slideshare.net/api/oembed/2', true],
            '#https?://(open|play)\.spotify\.com/.*#i' => ['https://embed.spotify.com/oembed/', true],
            '#https?://(.+\.)?imgur\.com/.*#i' => ['https://api.imgur.com/oembed', true],
            '#https?://(www\.)?meetu(\.ps|p\.com)/.*#i' => ['https://api.meetup.com/oembed', true],
            '#https?://(www\.)?issuu\.com/.+/docs/.+#i' => ['https://issuu.com/oembed_wp', true],
            '#https?://(www\.)?mixcloud\.com/.*#i' => ['https://www.mixcloud.com/oembed', true],
            '#https?://(www\.|embed\.)?ted\.com/talks/.*#i' => ['https://www.ted.com/services/v1/oembed.{format}', true],
            '#https?://(www\.)?(animoto|video214)\.com/play/.*#i' => ['https://animoto.com/oembeds/create', true],
            '#https?://(.+)\.tumblr\.com/post/.*#i' => ['https://www.tumblr.com/oembed/1.0', true],
            '#https?://(www\.)?kickstarter\.com/projects/.*#i' => ['https://www.kickstarter.com/services/oembed', true],
            '#https?://kck\.st/.*#i' => ['https://www.kickstarter.com/services/oembed', true],
            '#https?://cloudup\.com/.*#i' => ['https://cloudup.com/oembed', true],
            '#https?://(www\.)?reverbnation\.com/.*#i' => ['https://www.reverbnation.com/oembed', true],
            '#https?://(www\.)?reddit\.com/r/[^/]+/comments/.*#i' => ['https://www.reddit.com/oembed', true],
            '#https?://(www\.)?speakerdeck\.com/.*#i' => ['https://speakerdeck.com/oembed.{format}', true],
            '#https?://(www\.)?screencast\.com/.*#i' => ['https://api.screencast.com/external/oembed', true],
            '#https?://([a-z0-9-]+\.)?amazon\.(com|com\.mx|com\.br|ca)/.*#i' => ['https://read.amazon.com/kp/api/oembed', true],
            '#https?://([a-z0-9-]+\.)?amazon\.(co\.uk|de|fr|it|es|in|nl|ru)/.*#i' => ['https://read.amazon.co.uk/kp/api/oembed', true],
            '#https?://([a-z0-9-]+\.)?amazon\.(co\.jp|com\.au)/.*#i' => ['https://read.amazon.com.au/kp/api/oembed', true],
            '#https?://([a-z0-9-]+\.)?amazon\.cn/.*#i' => ['https://read.amazon.cn/kp/api/oembed', true],
            '#https?://(www\.)?a\.co/.*#i' => ['https://read.amazon.com/kp/api/oembed', true],
            '#https?://(www\.)?amzn\.to/.*#i' => ['https://read.amazon.com/kp/api/oembed', true],
            '#https?://(www\.)?amzn\.eu/.*#i' => ['https://read.amazon.co.uk/kp/api/oembed', true],
            '#https?://(www\.)?amzn\.in/.*#i' => ['https://read.amazon.in/kp/api/oembed', true],
            '#https?://(www\.)?amzn\.asia/.*#i' => ['https://read.amazon.com.au/kp/api/oembed', true],
            '#https?://(www\.)?z\.cn/.*#i' => ['https://read.amazon.cn/kp/api/oembed', true],
            '#https?://www\.someecards\.com/.+-cards/.+#i' => ['https://www.someecards.com/v2/oembed/', true],
            '#https?://www\.someecards\.com/usercards/viewcard/.+#i' => ['https://www.someecards.com/v2/oembed/', true],
            '#https?://some\.ly\/.+#i' => ['https://www.someecards.com/v2/oembed/', true],
            '#https?://(www\.)?tiktok\.com/.*/video/.*#i' => ['https://www.tiktok.com/oembed', true],
        ];
    }
}
