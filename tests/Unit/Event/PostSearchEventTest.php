<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Tests\Unit\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symkit\SearchBundle\Event\PostSearchEvent;
use Symkit\SearchBundle\Model\SearchResultGroup;

#[CoversClass(PostSearchEvent::class)]
final class PostSearchEventTest extends TestCase
{
    public function testGettersReturnConstructorValues(): void
    {
        $group = new SearchResultGroup('Pages', [], 10);
        $event = new PostSearchEvent('query', [$group], 'main');

        self::assertSame('query', $event->getQuery());
        self::assertSame([$group], $event->getResults());
        self::assertSame('main', $event->getEngine());
    }

    public function testResultsCanBeModified(): void
    {
        $event = new PostSearchEvent('query', [], 'main');
        $group = new SearchResultGroup('New', [], 5);

        $event->setResults([$group]);

        self::assertCount(1, $event->getResults());
        self::assertSame('New', $event->getResults()[0]->category);
    }
}
