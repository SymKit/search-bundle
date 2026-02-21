<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Tests\Unit\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symkit\SearchBundle\Event\PreSearchEvent;

#[CoversClass(PreSearchEvent::class)]
final class PreSearchEventTest extends TestCase
{
    public function testGettersReturnConstructorValues(): void
    {
        $event = new PreSearchEvent('test query', 'main');

        self::assertSame('test query', $event->getQuery());
        self::assertSame('main', $event->getEngine());
    }

    public function testQueryCanBeModified(): void
    {
        $event = new PreSearchEvent('original', 'main');

        $event->setQuery('modified');

        self::assertSame('modified', $event->getQuery());
    }
}
