<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Model;

final readonly class SearchResult
{
    public function __construct(
        public string $title,
        public string $subtitle,
        public string $url,
        public string $icon,
        public ?string $badge = null,
    ) {
    }
}
