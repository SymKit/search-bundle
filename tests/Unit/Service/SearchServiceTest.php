<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Tests\Unit\Service;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symkit\SearchBundle\Contract\SearchProviderInterface;
use Symkit\SearchBundle\Model\SearchResult;
use Symkit\SearchBundle\Model\SearchResultGroup;
use Symkit\SearchBundle\Service\SearchService;

#[CoversClass(SearchService::class)]
final class SearchServiceTest extends TestCase
{
    public function testSearchReturnsEmptyForBlankQuery(): void
    {
        $service = new SearchService([]);

        $results = iterator_to_array($service->search(''));

        self::assertSame([], $results);
    }

    public function testSearchReturnsEmptyForWhitespaceQuery(): void
    {
        $provider = $this->createProvider(
            'Pages',
            10,
            [new SearchResult('Hit', 'sub', '/hit', 'icon')],
        );
        $service = new SearchService([$provider]);

        $results = iterator_to_array($service->search('   '));

        self::assertSame([], $results);
    }

    public function testSearchReturnsGroupedResults(): void
    {
        $provider = $this->createProvider(
            category: 'Pages',
            priority: 10,
            results: [
                new SearchResult('Home', '/home', '/home', 'home'),
            ],
        );

        $service = new SearchService([$provider]);
        $groups = iterator_to_array($service->search('home'));

        self::assertCount(1, $groups);
        self::assertInstanceOf(SearchResultGroup::class, $groups[0]);
        self::assertSame('Pages', $groups[0]->category);
        self::assertSame(10, $groups[0]->priority);
        self::assertCount(1, $groups[0]->results);
    }

    public function testSearchSkipsProvidersWithNoResults(): void
    {
        $emptyProvider = $this->createProvider('Empty', 10, []);
        $fullProvider = $this->createProvider(
            'Pages',
            20,
            [new SearchResult('Hit', 'sub', '/hit', 'icon')],
        );

        $service = new SearchService([$emptyProvider, $fullProvider]);
        $groups = iterator_to_array($service->search('test'));

        self::assertCount(1, $groups);
        self::assertSame('Pages', $groups[0]->category);
    }

    public function testSearchSortsProvidersByPriority(): void
    {
        $lowPriority = $this->createProvider(
            'Media',
            50,
            [new SearchResult('Image', 'sub', '/image', 'icon')],
        );
        $highPriority = $this->createProvider(
            'Pages',
            10,
            [new SearchResult('Home', 'sub', '/home', 'icon')],
        );

        $service = new SearchService([$lowPriority, $highPriority]);
        $groups = iterator_to_array($service->search('test'));

        self::assertCount(2, $groups);
        self::assertSame('Pages', $groups[0]->category);
        self::assertSame('Media', $groups[1]->category);
    }

    public function testSearchHandlesGeneratorProviders(): void
    {
        $provider = $this->createMock(SearchProviderInterface::class);
        $provider->method('getCategory')->willReturn('Gen');
        $provider->method('getPriority')->willReturn(10);
        $provider->method('search')->willReturnCallback(static function (): Generator {
            yield new SearchResult('Generated', 'sub', '/gen', 'icon');
        });

        $service = new SearchService([$provider]);
        $groups = iterator_to_array($service->search('test'));

        self::assertCount(1, $groups);
        self::assertSame('Gen', $groups[0]->category);
    }

    /**
     * @param array<SearchResult> $results
     */
    private function createProvider(string $category, int $priority, array $results): SearchProviderInterface
    {
        $provider = $this->createMock(SearchProviderInterface::class);
        $provider->method('getCategory')->willReturn($category);
        $provider->method('getPriority')->willReturn($priority);
        $provider->method('search')->willReturn($results);

        return $provider;
    }
}
