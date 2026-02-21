<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Tests\Integration\Fixtures;

use Symkit\SearchBundle\Contract\SearchProviderInterface;
use Symkit\SearchBundle\Model\SearchResult;

final readonly class TestSearchProvider implements SearchProviderInterface
{
    /**
     * @return iterable<SearchResult>
     */
    public function search(string $query): iterable
    {
        if (str_contains('homepage', $query)) {
            yield new SearchResult('Homepage', 'Main page', '/', 'home');
        }
    }

    public function getCategory(): string
    {
        return 'Pages';
    }

    public function getPriority(): int
    {
        return 10;
    }
}
