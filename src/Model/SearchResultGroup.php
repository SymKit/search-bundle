<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Model;

final readonly class SearchResultGroup
{
    /**
     * @param array<SearchResult> $results
     */
    public function __construct(
        public string $category,
        public array $results,
        public int $priority,
    ) {
    }
}
