<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Tests\Unit\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symkit\SearchBundle\Model\SearchResult;
use Symkit\SearchBundle\Model\SearchResultGroup;

#[CoversClass(SearchResultGroup::class)]
final class SearchResultGroupTest extends TestCase
{
    public function testConstruct(): void
    {
        $results = [
            new SearchResult('Home', '/home', 'https://example.com', 'home'),
        ];

        $group = new SearchResultGroup(
            category: 'Pages',
            results: $results,
            priority: 10,
        );

        self::assertSame('Pages', $group->category);
        self::assertSame($results, $group->results);
        self::assertSame(10, $group->priority);
    }

    public function testEmptyResults(): void
    {
        $group = new SearchResultGroup(
            category: 'Empty',
            results: [],
            priority: 50,
        );

        self::assertSame([], $group->results);
    }
}
