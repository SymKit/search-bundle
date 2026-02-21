<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Contract;

interface SearchEngineRegistryInterface
{
    public function get(string $engine): SearchServiceInterface;

    public function has(string $engine): bool;

    public function getDefault(): SearchServiceInterface;
}
