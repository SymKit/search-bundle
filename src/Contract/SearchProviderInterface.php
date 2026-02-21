<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Contract;

use Symkit\SearchBundle\Model\SearchResult;

interface SearchProviderInterface
{
    public const DEFAULT_PRIORITY = 50;

    /**
     * @return iterable<SearchResult>
     */
    public function search(string $query): iterable;

    public function getCategory(): string;

    /**
     * Lower numbers appear first.
     *
     * @return int Priority (0-100, default: 50)
     */
    public function getPriority(): int;
}
