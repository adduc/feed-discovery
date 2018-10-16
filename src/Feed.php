<?php declare(strict_types=1);

namespace Adduc\Feed\Discovery;

use Psr\Http\Message\UriInterface;

class Feed
{
    /** @var ?string */
    public $title;

    /** @var string */
    public $type;

    /** @var UriInterface */
    public $uri;

    public function __construct(UriInterface $uri, string $type, ?string $title = null)
    {
        $this->uri = $uri;
        $this->type = $type;
        $this->title = $title;
    }
}
