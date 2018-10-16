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
        $this->assertEquals($expected, $result);
    }

    public function provideDiscover(): array
    {
        $uris = [
            'rss-1' => uri_for('https://example.com/rss.xml'),
            'atom-1' => uri_for('https://example.com/atom.xml'),
        ];

        $headers = [
            'rss-1' => "<{$uris['rss-1']}>; rel=alternate; type=application/rss+xml",
            'atom-1' => "<{$uris['atom-1']}>; rel=alternate; type=application/atom+xml",
            'rss-title' => "<{$uris['rss-1']}>; rel=alternate; type=application/rss+xml; title=\"Example Title\"",
        ];

        $bodies = [
            'rss-1' => file_get_contents(FIXTURES . '/single.rss.html'),
            'atom-1' => file_get_contents(FIXTURES . '/single.atom.html'),
            'rss-title' => file_get_contents(FIXTURES . '/single.rss.title.html'),
            'feeds' => file_get_contents(FIXTURES . '/multiple.feeds.html'),
        ];

        $feeds = [
            'rss-1' => new Feed($uris['rss-1'], 'application/rss+xml'),
            'atom-1' => new Feed($uris['atom-1'], 'application/atom+xml'),
            'rss-title' => new Feed($uris['rss-1'], 'application/rss+xml', 'Example Title'),
        ];

        $tests = [
            // Single RSS link in header
            [new Response(200, ['Link' => $headers['rss-1']]), [$feeds['rss-1']]],

            // Single Atom link in header
            [new Response(200, ['Link' => $headers['atom-1']]), [$feeds['atom-1']]],

            // Single RSS link with title in header
            [new Response(200, ['Link' => $headers['rss-title']]), [$feeds['rss-title']]],

            // Multiple links in header
            [
                new Response(200, ['Link' => [$headers['rss-1'], $headers['atom-1'], $headers['rss-title']]]),
                [$feeds['rss-1'], $feeds['atom-1'], $feeds['rss-title']],
            ],

            // Single RSS link in body
            [new Response(200, [], $bodies['rss-1']), [$feeds['rss-1']]],

            // Single Atom link in body
            [new Response(200, [], $bodies['atom-1']), [$feeds['atom-1']]],

            // Single RSS link with title in body
            [new Response(200, [], $bodies['rss-title']), [$feeds['rss-title']]],

            // Multiple links in body
            [
                new Response(200, [], $bodies['feeds']),
                [$feeds['rss-1'], $feeds['atom-1'], $feeds['rss-title']],
            ],

            // Single link in header and body
            [
                new Response(200, ['Link' => $headers['rss-1']], $bodies['atom-1']),
                [$feeds['rss-1'], $feeds['atom-1']],
            ],

            // Single link in header and body (duplicate)
            [
                new Response(200, ['Link' => $headers['rss-1']], $bodies['rss-1']),
                [$feeds['rss-1']],
            ],

            // Multiple links in header and body (duplicate)
            [
                new Response(
                    200,
                    ['Link' => [$headers['rss-1'], $headers['atom-1'], $headers['rss-title']]],
                    $bodies['feeds']
                ),
                [
                    $feeds['rss-1'],
                    $feeds['atom-1'],
                    $feeds['rss-title'],
                ],
            ],
        ];

        return $tests;
    }
}


/**
 * Need to test:
 * * relative URLs
 * * link in response headers
 */
