<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Event;

final class PreSearchEvent
{
    public function __construct(
        private string $query,
        private readonly string $engine,
    ) {
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function setQuery(string $query): void
    {
        $this->query = $query;
    }

    public function getEngine(): string
    {
        return $this->engine;
    }
}
