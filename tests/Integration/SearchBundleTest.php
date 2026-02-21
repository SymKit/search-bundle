<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Tests\Integration;

use Nyholm\BundleTest\TestKernel;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symkit\SearchBundle\Contract\SearchEngineRegistryInterface;
use Symkit\SearchBundle\Contract\SearchServiceInterface;
use Symkit\SearchBundle\Model\SearchResultGroup;
use Symkit\SearchBundle\SearchBundle;
use Symkit\SearchBundle\Service\SearchEngineRegistry;
use Symkit\SearchBundle\Service\SearchService;
use Symkit\SearchBundle\Tests\Integration\Fixtures\TestSearchProvider;

#[CoversClass(SearchBundle::class)]
final class SearchBundleTest extends KernelTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        restore_exception_handler();
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        /** @var TestKernel $kernel */
        $kernel = parent::createKernel($options);
        $kernel->addTestBundle(SearchBundle::class);
        $kernel->addTestConfig(static function (\Symfony\Component\DependencyInjection\ContainerBuilder $container): void {
            $container->loadFromExtension('framework', ['test' => true]);
        });
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

    public function testDefaultEngineIsRegistered(): void
    {
        self::bootKernel();

        self::assertInstanceOf(
            SearchService::class,
            self::getContainer()->get(SearchServiceInterface::class),
        );
    }

    public function testRegistryIsRegistered(): void
    {
        self::bootKernel();

        $registry = self::getContainer()->get(SearchEngineRegistryInterface::class);

        self::assertInstanceOf(SearchEngineRegistry::class, $registry);
        self::assertTrue($registry->has('default'));
        self::assertInstanceOf(SearchService::class, $registry->getDefault());
    }

    public function testMultipleEngines(): void
    {
        self::bootKernel(['config' => static function (TestKernel $kernel): void {
            $kernel->addTestConfig(__DIR__.'/Fixtures/config_multi_engines.yaml');
        }]);

        $registry = self::getContainer()->get(SearchEngineRegistryInterface::class);
        \assert($registry instanceof SearchEngineRegistryInterface);

        self::assertTrue($registry->has('main'));
        self::assertTrue($registry->has('admin'));
        self::assertFalse($registry->has('nonexistent'));
        self::assertInstanceOf(SearchService::class, $registry->get('main'));
        self::assertInstanceOf(SearchService::class, $registry->get('admin'));
    }

    public function testEmptyEnginesDisablesSearch(): void
    {
        self::bootKernel(['config' => static function (TestKernel $kernel): void {
            $kernel->addTestConfig(__DIR__.'/Fixtures/config_search_disabled.yaml');
        }]);

        $container = self::getContainer();

        self::assertFalse($container->has(SearchServiceInterface::class));
    }

    public function testSearchProviderAutoconfiguration(): void
    {
        self::bootKernel(['config' => static function (TestKernel $kernel): void {
            $kernel->addTestConfig(static function (\Symfony\Component\DependencyInjection\ContainerBuilder $container): void {
                $container->register(TestSearchProvider::class, TestSearchProvider::class)
                    ->setAutoconfigured(true);
            });
        }]);

        $service = self::getContainer()->get(SearchServiceInterface::class);
        \assert($service instanceof SearchServiceInterface);

        $groups = iterator_to_array($service->search('home'));

        self::assertCount(1, $groups);
        self::assertInstanceOf(SearchResultGroup::class, $groups[0]);
        self::assertSame('Pages', $groups[0]->category);
    }

    public function testSearchReturnsEmptyForNoMatch(): void
    {
        self::bootKernel(['config' => static function (TestKernel $kernel): void {
            $kernel->addTestConfig(static function (\Symfony\Component\DependencyInjection\ContainerBuilder $container): void {
                $container->register(TestSearchProvider::class, TestSearchProvider::class)
                    ->setAutoconfigured(true);
            });
        }]);

        $service = self::getContainer()->get(SearchServiceInterface::class);
        \assert($service instanceof SearchServiceInterface);

        $groups = iterator_to_array($service->search('zzz_no_match'));

        self::assertSame([], $groups);
    }

    public function testDefaultEngineConfigOption(): void
    {
        self::bootKernel(['config' => static function (TestKernel $kernel): void {
            $kernel->addTestConfig(__DIR__.'/Fixtures/config_default_engine.yaml');
        }]);

        $registry = self::getContainer()->get(SearchEngineRegistryInterface::class);
        \assert($registry instanceof SearchEngineRegistryInterface);

        $default = $registry->getDefault();
        $admin = $registry->get('admin');

        self::assertSame($default, $admin);
    }
}
