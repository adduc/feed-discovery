<?php declare(strict_types=1);

namespace Adduc\Feed\Discovery;

use function GuzzleHttp\Psr7\uri_for;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

class FeedTest extends TestCase
{
    /**
     * @dataProvider provideConstruct
     */
    public function testConstruct(UriInterface $uri, string $type, ?string $title): void
    {
        $feed = new Feed($uri, $type, $title);

        $this->assertEquals($uri, $feed->uri);
        $this->assertEquals($type, $feed->type);
        $this->assertEquals($title, $feed->title);
    }

    public function provideConstruct(): array
    {
        $tests = [
            [uri_for('/'), 'asdf', 'Title'],
        ];

        return $tests;
    }
}
