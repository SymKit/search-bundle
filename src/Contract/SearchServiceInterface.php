<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Contract;

use Symkit\SearchBundle\Model\SearchResultGroup;

interface SearchServiceInterface
{
    /**
     * @return iterable<SearchResultGroup>
     */
    public function search(string $query): iterable;
}
