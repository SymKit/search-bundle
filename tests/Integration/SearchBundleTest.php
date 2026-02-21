<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Tests\Integration;

use Nyholm\BundleTest\TestKernel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symkit\SearchBundle\Contract\SearchServiceInterface;
use Symkit\SearchBundle\SearchBundle;
use Symkit\SearchBundle\Service\SearchService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

#[CoversClass(SearchBundle::class)]
final class SearchBundleTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        /** @var TestKernel $kernel */
        $kernel = parent::createKernel($options);
        $kernel->addTestBundle(SearchBundle::class);
        $kernel->handleOptions($options);

        return $kernel;
    }

    public function testBundleIsRegistered(): void
    {
        self::bootKernel();

        $bundles = self::$kernel->getBundles();

        self::assertArrayHasKey('SearchBundle', $bundles);
        self::assertInstanceOf(SearchBundle::class, $bundles['SearchBundle']);
    }

    public function testSearchServiceIsRegistered(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        self::assertTrue($container->has(SearchServiceInterface::class));
        self::assertInstanceOf(SearchService::class, $container->get(SearchServiceInterface::class));
    }

    public function testSearchServiceCanBeDisabled(): void
    {
        self::bootKernel(['config' => static function (TestKernel $kernel): void {
            $kernel->addTestConfig(__DIR__ . '/Fixtures/config_search_disabled.yaml');
        }]);

        $container = self::getContainer();

        self::assertFalse($container->has(SearchServiceInterface::class));
    }
}
