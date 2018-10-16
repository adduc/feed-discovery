<?php declare(strict_types=1);

namespace Adduc\Feed\Discovery;

use function GuzzleHttp\Psr7\parse_header;
use function GuzzleHttp\Psr7\uri_for;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use GuzzleHttp\Psr7\UriResolver;

class Discovery
{
    /**
     * @return Feed[]
     */
    public function discover(ResponseInterface $response, ?UriInterface $base = null): array
    {
        $feeds = array_merge(
            $this->parseLinkHeaders($response, $base),
            $this->parseBody($response, $base)
        );

        return array_unique($feeds, SORT_REGULAR);
    }

    /**
     * @return Feed[]
     */
    protected function parseLinkHeaders(ResponseInterface $response, ?UriInterface $base = null): array
    {
        if (!$response->hasHeader('Link')) {
            return [];
        }

        $links = parse_header($response->getHeader('Link'));
        $feeds = [];

        foreach ($links as $link) {
            switch (true) {
                case empty($link[0]):
                case empty($link['rel']):
                case $link['rel'] != 'alternate':
                case empty($link['type']):
                    continue 2;
            }

            $link[0] = preg_replace('/^<(.*)>$/', '\1', $link[0]);
            $feeds[] = new Feed(uri_for($link[0]), $link['type'], $link['title'] ?? null);
        }

        return $feeds;
    }

    /**
     * @return Feed[]
     */
    protected function parseBody(ResponseInterface $response, ?UriInterface $base = null): array
    {
        $body = trim($response->getBody()->__toString());

        if (!$body) {
            return [];
        }

        $doc = new \DOMDocument();
        $doc->loadHTML($body, LIBXML_COMPACT | LIBXML_NONET);

        $xpath = new \DOMXPath($doc);
        $elements = $xpath->query('//*[@rel=\'alternate\']');

        $feeds = [];
        foreach ($elements as $element) {
            $uri = uri_for($element->getAttribute('href'));
            if ($base !== null) {
                $uri = UriResolver::resolve($base, $uri);
            }

            $feeds[] = new Feed($uri, $element->getAttribute('type'), $element->getAttribute('title'));
        }

        return $feeds;
    }
}
