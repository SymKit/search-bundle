<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symkit\SearchBundle\SearchBundle;

#[CoversClass(SearchBundle::class)]
final class SearchBundlePrependTest extends TestCase
{
    public function testPrependsTwigPathsWhenUiEnabled(): void
    {
        $builder = $this->createBuilderWithExtensions(['twig']);

        $this->callPrependExtension($builder);

        $twigConfig = $builder->getExtensionConfig('twig');
        self::assertNotEmpty($twigConfig);

        $paths = $twigConfig[0]['paths'] ?? [];
        $namespaces = array_values($paths);
        self::assertContains('SymkitSearch', $namespaces);

        $registeredPath = array_keys($paths)[0];
        self::assertStringEndsWith('/templates', $registeredPath);
    }

    public function testPrependsTwigComponentDefaultsWhenUiEnabled(): void
    {
        $builder = $this->createBuilderWithExtensions(['twig', 'twig_component']);

        $this->callPrependExtension($builder);

        $twigComponentConfig = $builder->getExtensionConfig('twig_component');
        self::assertNotEmpty($twigComponentConfig);

        $defaults = $twigComponentConfig[0]['defaults'] ?? [];
        self::assertArrayHasKey('Symkit\SearchBundle\Twig\Component\\', $defaults);
    }

    public function testSkipsPrependWhenAllEnginesHaveUiDisabled(): void
    {
        $builder = $this->createBuilderWithExtensions(['twig']);
        $builder->prependExtensionConfig('symkit_search', [
            'engines' => ['main' => ['ui' => false]],
        ]);

        $this->callPrependExtension($builder);

        $twigConfig = $builder->getExtensionConfig('twig');
        self::assertEmpty($twigConfig);
    }

    public function testSkipsPrependWhenEmptyEngines(): void
    {
        $builder = $this->createBuilderWithExtensions(['twig']);
        $builder->prependExtensionConfig('symkit_search', [
            'engines' => [],
        ]);

        $this->callPrependExtension($builder);

        $twigConfig = $builder->getExtensionConfig('twig');
        self::assertEmpty($twigConfig);
    }

    public function testPrependsWhenNoConfigProvided(): void
    {
        $builder = $this->createBuilderWithExtensions(['twig']);

        $this->callPrependExtension($builder);

        $twigConfig = $builder->getExtensionConfig('twig');
        self::assertNotEmpty($twigConfig);
    }

    public function testPrependsWhenExplicitUiTrue(): void
    {
        $builder = $this->createBuilderWithExtensions(['twig']);
        $builder->prependExtensionConfig('symkit_search', [
            'engines' => ['main' => ['ui' => true]],
        ]);

        $this->callPrependExtension($builder);

        $twigConfig = $builder->getExtensionConfig('twig');
        self::assertNotEmpty($twigConfig);
    }

    public function testPrependsWhenEngineHasNoUiKey(): void
    {
        $builder = $this->createBuilderWithExtensions(['twig']);
        $builder->prependExtensionConfig('symkit_search', [
            'engines' => ['main' => []],
        ]);

        $this->callPrependExtension($builder);

        $twigConfig = $builder->getExtensionConfig('twig');
        self::assertNotEmpty($twigConfig);
    }

    public function testPrependsAssetMapperWhenFrameworkExtensionExists(): void
    {
        $builder = $this->createBuilderWithExtensions(['twig', 'framework']);

        $this->callPrependExtension($builder);

        $frameworkConfig = $builder->getExtensionConfig('framework');

        if (class_exists(\Symfony\Component\AssetMapper\AssetMapperInterface::class)) {
            self::assertNotEmpty($frameworkConfig);
            $assetPaths = $frameworkConfig[0]['asset_mapper']['paths'] ?? [];
            self::assertNotEmpty($assetPaths);

            $registeredPath = array_keys($assetPaths)[0];
            self::assertStringEndsWith('/assets/controllers', $registeredPath);
            self::assertSame('search', array_values($assetPaths)[0]);
        } else {
            self::assertEmpty($frameworkConfig);
        }
    }

    public function testSkipsTwigWhenExtensionNotRegistered(): void
    {
        $builder = new ContainerBuilder();
        $this->registerFakeExtension($builder, 'symkit_search');

        $this->callPrependExtension($builder);

        self::assertFalse($builder->hasExtension('twig'));
    }

    public function testSkipsWhenConfigHasNonArrayEngines(): void
    {
        $builder = $this->createBuilderWithExtensions(['twig']);
        $builder->prependExtensionConfig('symkit_search', [
            'engines' => 'invalid',
        ]);

        $this->callPrependExtension($builder);

        $twigConfig = $builder->getExtensionConfig('twig');
        self::assertEmpty($twigConfig);
    }

    /**
     * @param list<string> $extensions
     */
    private function createBuilderWithExtensions(array $extensions): ContainerBuilder
    {
        $builder = new ContainerBuilder();
        $this->registerFakeExtension($builder, 'symkit_search');

        foreach ($extensions as $extensionName) {
            $this->registerFakeExtension($builder, $extensionName);
        }

        return $builder;
    }

    private function registerFakeExtension(ContainerBuilder $builder, string $alias): void
    {
        $extension = $this->createMock(ExtensionInterface::class);
        $extension->method('getAlias')->willReturn($alias);
        $builder->registerExtension($extension);
    }

    private function callPrependExtension(ContainerBuilder $builder): void
    {
        $bundle = new SearchBundle();
        $configurator = $this->createMock(ContainerConfigurator::class);
        $bundle->prependExtension($configurator, $builder);
    }
}
