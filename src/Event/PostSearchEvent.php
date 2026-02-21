<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Event;

use Symkit\SearchBundle\Model\SearchResultGroup;

final class PostSearchEvent
{
    /**
     * @param array<SearchResultGroup> $results
     */
    public function __construct(
        private readonly string $query,
        private array $results,
        private readonly string $engine,
    ) {
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @return array<SearchResultGroup>
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * @param array<SearchResultGroup> $results
     */
    public function setResults(array $results): void
    {
        $this->results = $results;
    }

    public function getEngine(): string
    {
        return $this->engine;
    }
}
