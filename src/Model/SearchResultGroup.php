<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Model;

final readonly class SearchResultGroup
{
    public function __construct(
        public string $category,
        public iterable $results,
        public int $priority,
    ) {
    }
}
