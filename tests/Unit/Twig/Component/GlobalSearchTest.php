<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Tests\Unit\Twig\Component;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symkit\SearchBundle\Contract\SearchEngineRegistryInterface;
use Symkit\SearchBundle\Contract\SearchServiceInterface;
use Symkit\SearchBundle\Model\SearchResult;
use Symkit\SearchBundle\Model\SearchResultGroup;
use Symkit\SearchBundle\Twig\Component\GlobalSearch;

#[CoversClass(GlobalSearch::class)]
final class GlobalSearchTest extends TestCase
{
    public function testOpenSetsIsOpenToTrue(): void
    {
        $component = $this->createComponent();

        $component->open();

        self::assertTrue($component->isOpen);
    }

    public function testCloseResetsState(): void
    {
        $component = $this->createComponent();
        $component->isOpen = true;
        $component->query = 'something';

        $component->close();

        self::assertFalse($component->isOpen);
        self::assertSame('', $component->query);
    }

    public function testPrepareResultsClearsResultsForEmptyQuery(): void
    {
        $component = $this->createComponent();
        $component->query = '';
        $component->results = [new SearchResultGroup('Old', [], 10)];

        $component->prepareResults();

        self::assertSame([], $component->results);
    }

    public function testPrepareResultsClearsResultsForWhitespaceQuery(): void
    {
        $component = $this->createComponent();
        $component->query = '   ';
        $component->results = [new SearchResultGroup('Old', [], 10)];

        $component->prepareResults();

        self::assertSame([], $component->results);
    }

    public function testPrepareResultsFetchesFromEngine(): void
    {
        $group = new SearchResultGroup('Pages', [
            new SearchResult('Home', '/home', 'https://example.com', 'home'),
        ], 10);

        $searchService = $this->createMock(SearchServiceInterface::class);
        $searchService->method('search')->with('test')->willReturn([$group]);

        $registry = $this->createMock(SearchEngineRegistryInterface::class);
        $registry->method('get')->with('default')->willReturn($searchService);

        $component = new GlobalSearch($registry);
        $component->query = 'test';

        $component->prepareResults();

        self::assertCount(1, $component->results);
        self::assertSame('Pages', $component->results[0]->category);
    }

    public function testPrepareResultsConvertsIterableToArray(): void
    {
        $group = new SearchResultGroup('Pages', [], 10);

        $searchService = $this->createMock(SearchServiceInterface::class);
        $searchService->method('search')->willReturnCallback(static function () use ($group): Generator {
            yield $group;
        });

        $registry = $this->createMock(SearchEngineRegistryInterface::class);
        $registry->method('get')->willReturn($searchService);

        $component = new GlobalSearch($registry);
        $component->query = 'test';

        $component->prepareResults();

        self::assertIsArray($component->results);
        self::assertCount(1, $component->results);
    }

    public function testPrepareResultsUsesConfiguredEngine(): void
    {
        $searchService = $this->createMock(SearchServiceInterface::class);
        $searchService->method('search')->willReturn([]);

        $registry = $this->createMock(SearchEngineRegistryInterface::class);
        $registry->expects(self::once())->method('get')->with('admin')->willReturn($searchService);

        $component = new GlobalSearch($registry);
        $component->engine = 'admin';
        $component->query = 'test';

        $component->prepareResults();
    }

    public function testHasResultsReturnsFalseWhenEmpty(): void
    {
        $component = $this->createComponent();

        self::assertFalse($component->hasResults());
    }

    public function testHasResultsReturnsTrueWhenPopulated(): void
    {
        $component = $this->createComponent();
        $component->results = [new SearchResultGroup('Pages', [], 10)];

        self::assertTrue($component->hasResults());
    }

    private function createComponent(): GlobalSearch
    {
        $registry = $this->createMock(SearchEngineRegistryInterface::class);

        return new GlobalSearch($registry);
    }
}
