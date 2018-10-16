<?php declare(strict_types=1);

namespace Adduc\Feed\Discovery;

use function GuzzleHttp\Psr7\uri_for;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class DiscoveryTest extends TestCase
{
    /**
     * @dataProvider provideDiscover
     */
    public function testDiscover(Response $response, array $expected): void
    {
        $discovery = new Discovery();

        $uri = uri_for('https://example.com');

        $result = $discovery->discover($response, $uri);
        $this->assertEquals(array_values($expected), $result);
    }

    public function provideDiscover(): array
    {
        $uris = [
            'rss' => uri_for('https://example.com/rss.xml'),
            'atom' => uri_for('https://example.com/atom.xml'),
            'json' => uri_for('https://example.com/feed.json'),
        ];

        $headers = [
            'rss' => "<{$uris['rss']}>; rel=alternate; type=application/rss+xml",
            'atom' => "<{$uris['atom']}>; rel=alternate; type=application/atom+xml",
            'rss-title' => "<{$uris['rss']}>; rel=alternate; type=application/rss+xml; title=\"Example Title\"",
            'json' => "<{$uris['json']}>; rel=alternate; type=application/json",
        ];

        $bodies = [
            'rss' => file_get_contents(FIXTURES . '/single.rss.html'),
            'atom' => file_get_contents(FIXTURES . '/single.atom.html'),
            'rss-title' => file_get_contents(FIXTURES . '/single.rss.title.html'),
            'json' => file_get_contents(FIXTURES . '/single.json.html'),
            'feeds' => file_get_contents(FIXTURES . '/multiple.feeds.html'),
        ];

        $feeds = [
            'rss' => new Feed($uris['rss'], 'application/rss+xml'),
            'atom' => new Feed($uris['atom'], 'application/atom+xml'),
            'rss-title' => new Feed($uris['rss'], 'application/rss+xml', 'Example Title'),
            'json' => new Feed($uris['json'], 'application/json'),
        ];

        $tests = [
            // Single RSS link in header
            [new Response(200, ['Link' => $headers['rss']]), [$feeds['rss']]],

            // Single Atom link in header
            [new Response(200, ['Link' => $headers['atom']]), [$feeds['atom']]],

            // Single RSS link with title in header
            [new Response(200, ['Link' => $headers['rss-title']]), [$feeds['rss-title']]],

            // Single JSON link in header
            [new Response(200, ['Link' => $headers['json']]), [$feeds['json']]],

            // Multiple links in header
            [new Response(200, ['Link' => $headers]), $feeds],

            // Single RSS link in body
            [new Response(200, [], $bodies['rss']), [$feeds['rss']]],

            // Single Atom link in body
            [new Response(200, [], $bodies['atom']), [$feeds['atom']]],

            // Single RSS link with title in body
            [new Response(200, [], $bodies['rss-title']), [$feeds['rss-title']]],

            // Single JSON link in body
            [new Response(200, [], $bodies['json']), [$feeds['json']]],

            // Multiple links in body
            [
                new Response(200, [], $bodies['feeds']),
                $feeds,
            ],

            // Single link in header and body
            [
                new Response(200, ['Link' => $headers['rss']], $bodies['atom']),
                [$feeds['rss'], $feeds['atom']],
            ],

            // Single link in header and body (duplicate)
            [
                new Response(200, ['Link' => $headers['rss']], $bodies['rss']),
                [$feeds['rss']],
            ],

            // Multiple links in header and body (duplicate)
            [new Response(200, ['Link' => $headers], $bodies['feeds']), $feeds],
        ];

        return $tests;
    }
}
