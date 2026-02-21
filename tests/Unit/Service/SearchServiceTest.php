<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Tests\Unit\Service;

use ArrayIterator;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symkit\SearchBundle\Contract\SearchProviderInterface;
use Symkit\SearchBundle\Event\PostSearchEvent;
use Symkit\SearchBundle\Event\PreSearchEvent;
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
        self::assertIsArray($groups[0]->results);
    }

    public function testSearchSkipsProviderReturningEmptyGenerator(): void
    {
        $emptyGenerator = $this->createMock(SearchProviderInterface::class);
        $emptyGenerator->method('getCategory')->willReturn('Empty');
        $emptyGenerator->method('getPriority')->willReturn(10);
        $emptyGenerator->method('search')->willReturnCallback(static function (): Generator {
            yield from [];
        });

        $service = new SearchService([$emptyGenerator]);
        $groups = iterator_to_array($service->search('test'));

        self::assertSame([], $groups);
    }

    public function testSearchAcceptsProvidersAsIterator(): void
    {
        $providerB = $this->createProvider('B', 20, [new SearchResult('Hit', 'sub', '/b', 'icon')]);
        $providerA = $this->createProvider('A', 10, [new SearchResult('Hit', 'sub', '/a', 'icon')]);

        $iterator = new ArrayIterator([$providerB, $providerA]);

        $service = new SearchService($iterator);
        $groups = iterator_to_array($service->search('test'));

        self::assertCount(2, $groups);
        self::assertSame('A', $groups[0]->category);
        self::assertSame('B', $groups[1]->category);
    }

    public function testPreSearchEventAllowsQueryModification(): void
    {
        $provider = $this->createProvider('Pages', 10, [
            new SearchResult('Home', 'sub', '/home', 'icon'),
        ]);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(static function (object $event): object {
                if ($event instanceof PreSearchEvent) {
                    $event->setQuery('modified');
                }

                return $event;
            });

        $service = new SearchService([$provider], 'main', $dispatcher);
        $groups = iterator_to_array($service->search('original'));

        self::assertCount(1, $groups);
    }

    public function testPreSearchEventCanCancelSearch(): void
    {
        $provider = $this->createProvider('Pages', 10, [
            new SearchResult('Home', 'sub', '/home', 'icon'),
        ]);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(static function (PreSearchEvent $event): PreSearchEvent {
                $event->setQuery('');

                return $event;
            });

        $service = new SearchService([$provider], 'main', $dispatcher);
        $groups = iterator_to_array($service->search('test'));

        self::assertSame([], $groups);
    }

    public function testPostSearchEventAllowsResultModification(): void
    {
        $provider = $this->createProvider('Pages', 10, [
            new SearchResult('Home', 'sub', '/home', 'icon'),
        ]);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')
            ->willReturnCallback(static function (object $event): object {
                if ($event instanceof PostSearchEvent) {
                    $event->setResults([]);
                }

                return $event;
            });

        $service = new SearchService([$provider], 'main', $dispatcher);
        $groups = iterator_to_array($service->search('test'));

        self::assertSame([], $groups);
    }

    public function testEventsReceiveCorrectEngineName(): void
    {
        $provider = $this->createProvider('Pages', 10, [
            new SearchResult('Home', 'sub', '/home', 'icon'),
        ]);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')
            ->willReturnCallback(static function (object $event): object {
                if ($event instanceof PreSearchEvent) {
                    TestCase::assertSame('admin', $event->getEngine());
                }
                if ($event instanceof PostSearchEvent) {
                    TestCase::assertSame('admin', $event->getEngine());
                }

                return $event;
            });

        $service = new SearchService([$provider], 'admin', $dispatcher);
        iterator_to_array($service->search('test'));
    }

    public function testSearchWorksWithoutDispatcher(): void
    {
        $provider = $this->createProvider('Pages', 10, [
            new SearchResult('Home', 'sub', '/home', 'icon'),
        ]);

        $service = new SearchService([$provider]);
        $groups = iterator_to_array($service->search('test'));

        self::assertCount(1, $groups);
    }

    public function testMaxResultsTruncatesWithinGroup(): void
    {
        $provider = $this->createProvider('Pages', 10, [
            new SearchResult('A', 'sub', '/a', 'icon'),
            new SearchResult('B', 'sub', '/b', 'icon'),
            new SearchResult('C', 'sub', '/c', 'icon'),
        ]);

        $service = new SearchService([$provider]);
        $groups = iterator_to_array($service->search('test', maxResults: 2));

        self::assertCount(1, $groups);
        self::assertCount(2, $groups[0]->results);
    }

    public function testMaxResultsStopsAcrossGroups(): void
    {
        $provider1 = $this->createProvider('Pages', 10, [
            new SearchResult('A', 'sub', '/a', 'icon'),
            new SearchResult('B', 'sub', '/b', 'icon'),
        ]);
        $provider2 = $this->createProvider('Media', 20, [
            new SearchResult('C', 'sub', '/c', 'icon'),
            new SearchResult('D', 'sub', '/d', 'icon'),
        ]);

        $service = new SearchService([$provider1, $provider2]);
        $groups = iterator_to_array($service->search('test', maxResults: 3));

        self::assertCount(2, $groups);
        self::assertCount(2, $groups[0]->results);
        self::assertCount(1, $groups[1]->results);
    }

    public function testMaxResultsNullReturnsAll(): void
    {
        $provider = $this->createProvider('Pages', 10, [
            new SearchResult('A', 'sub', '/a', 'icon'),
            new SearchResult('B', 'sub', '/b', 'icon'),
        ]);

        $service = new SearchService([$provider]);
        $groups = iterator_to_array($service->search('test', maxResults: null));

        self::assertCount(1, $groups);
        self::assertCount(2, $groups[0]->results);
    }

    public function testMaxResultsExactLimitStopsNextGroup(): void
    {
        $provider1 = $this->createProvider('A', 10, [
            new SearchResult('R1', 'sub', '/1', 'icon'),
            new SearchResult('R2', 'sub', '/2', 'icon'),
        ]);
        $provider2 = $this->createProvider('B', 20, [
            new SearchResult('R3', 'sub', '/3', 'icon'),
        ]);

        $service = new SearchService([$provider1, $provider2]);
        $groups = iterator_to_array($service->search('test', maxResults: 2));

        self::assertCount(1, $groups);
        self::assertCount(2, $groups[0]->results);
    }

    public function testMaxResultsAccumulatesAcrossGroups(): void
    {
        $provider1 = $this->createProvider('A', 10, [
            new SearchResult('R1', 'sub', '/1', 'icon'),
        ]);
        $provider2 = $this->createProvider('B', 20, [
            new SearchResult('R2', 'sub', '/2', 'icon'),
        ]);
        $provider3 = $this->createProvider('C', 30, [
            new SearchResult('R3', 'sub', '/3', 'icon'),
            new SearchResult('R4', 'sub', '/4', 'icon'),
        ]);

        $service = new SearchService([$provider1, $provider2, $provider3]);
        $groups = iterator_to_array($service->search('test', maxResults: 3));

        self::assertCount(3, $groups);
        self::assertCount(1, $groups[0]->results);
        self::assertCount(1, $groups[1]->results);
        self::assertCount(1, $groups[2]->results);
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
