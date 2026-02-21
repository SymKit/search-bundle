<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symkit\SearchBundle\Contract\SearchServiceInterface;
use Symkit\SearchBundle\Exception\EngineNotFoundException;
use Symkit\SearchBundle\Service\SearchEngineRegistry;

#[CoversClass(SearchEngineRegistry::class)]
final class SearchEngineRegistryTest extends TestCase
{
    public function testGetReturnsEngine(): void
    {
        $engine = $this->createMock(SearchServiceInterface::class);
        $registry = new SearchEngineRegistry(
            new ServiceLocator(['main' => static fn () => $engine]),
            'main',
        );

        self::assertSame($engine, $registry->get('main'));
    }

    public function testGetThrowsForUnknownEngine(): void
    {
        $engine = $this->createMock(SearchServiceInterface::class);
        $registry = new SearchEngineRegistry(
            new ServiceLocator(['main' => static fn () => $engine]),
            'main',
        );

        $this->expectException(EngineNotFoundException::class);
        $this->expectExceptionMessage('Search engine "unknown" is not registered. Available engines: main.');

        $registry->get('unknown');
    }

    public function testHasReturnsTrueForKnownEngine(): void
    {
        $engine = $this->createMock(SearchServiceInterface::class);
        $registry = new SearchEngineRegistry(
            new ServiceLocator(['main' => static fn () => $engine]),
            'main',
        );

        self::assertTrue($registry->has('main'));
        self::assertFalse($registry->has('admin'));
    }

    public function testGetDefaultReturnsFirstEngine(): void
    {
        $main = $this->createMock(SearchServiceInterface::class);
        $admin = $this->createMock(SearchServiceInterface::class);
        $registry = new SearchEngineRegistry(
            new ServiceLocator([
                'main' => static fn () => $main,
                'admin' => static fn () => $admin,
            ]),
            'main',
        );

        self::assertSame($main, $registry->getDefault());
    }
}
