<?php

declare(strict_types=1);

namespace Symkit\SearchBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symkit\SearchBundle\Attribute\AsSearchProvider;
use Symkit\SearchBundle\Contract\SearchEngineRegistryInterface;
use Symkit\SearchBundle\Contract\SearchProviderInterface;
use Symkit\SearchBundle\Contract\SearchServiceInterface;
use Symkit\SearchBundle\DependencyInjection\Compiler\SearchProviderPass;
use Symkit\SearchBundle\Service\SearchEngineRegistry;
use Symkit\SearchBundle\Service\SearchService;
use Symkit\SearchBundle\Twig\Component\GlobalSearch;

class SearchBundle extends AbstractBundle
{
    protected string $extensionAlias = 'symkit_search';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('default_engine')
                    ->defaultNull()
                    ->info('Explicit default engine name. Falls back to the first declared engine.')
                ->end()
                ->arrayNode('engines')
                    ->info('Named search engines. Each engine groups its own providers.')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->booleanNode('ui')
                                ->defaultTrue()
                                ->info('Enable the GlobalSearch component for this engine.')
                            ->end()
                        ->end()
                    ->end()
                    ->defaultValue(['default' => ['ui' => true]])
                ->end()
            ->end();
    }

    /**
     * @param array{default_engine: string|null, engines: array<string, array{ui: bool}>} $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $engines = $config['engines'];
        $defaultEngine = $config['default_engine'] ?? array_key_first($engines);

        $builder->setParameter('symkit_search.engines', $engines);

        $builder->registerForAutoconfiguration(SearchProviderInterface::class)
            ->addTag('symkit_search.provider')
        ;

        $builder->registerAttributeForAutoconfiguration(
            AsSearchProvider::class,
            static function (\Symfony\Component\DependencyInjection\ChildDefinition $definition, AsSearchProvider $attribute): void {
                $tag = ['engine' => $attribute->engine];
                $definition->addTag('symkit_search.provider', array_filter($tag, static fn (?string $v): bool => null !== $v));
            },
        );

        $engineRefs = [];

        foreach ($engines as $name => $engineConfig) {
            $serviceId = 'symkit_search.engine.'.$name;

            $definition = $builder->register($serviceId, SearchService::class)
                ->setArgument('$providers', new IteratorArgument([]))
                ->setArgument('$engineName', $name)
            ;

            if (interface_exists(\Symfony\Contracts\EventDispatcher\EventDispatcherInterface::class)) {
                $definition->setArgument('$dispatcher', new Reference(
                    \Symfony\Contracts\EventDispatcher\EventDispatcherInterface::class,
                    ContainerBuilder::NULL_ON_INVALID_REFERENCE,
                ));
            }

            $engineRefs[$name] = new Reference($serviceId);
        }

        $builder->register(SearchEngineRegistry::class)
            ->setArgument('$engines', new ServiceLocatorArgument($engineRefs))
            ->setArgument('$defaultEngine', $defaultEngine)
        ;

        $builder->setAlias(SearchEngineRegistryInterface::class, SearchEngineRegistry::class)
            ->setPublic(true)
        ;

        if (null !== $defaultEngine) {
            $builder->setAlias(SearchServiceInterface::class, 'symkit_search.engine.'.$defaultEngine)
                ->setPublic(true)
            ;
        }

        $hasUi = array_filter($engines, static fn (array $c): bool => $c['ui']);

        if ([] !== $hasUi) {
            $liveComponentConfig = [
                'key' => 'GlobalSearch',
                'template' => '@SymkitSearch/components/GlobalSearch.html.twig',
                'expose_public_props' => true,
                'attributes_var' => 'attributes',
                'live' => true,
                'route' => 'ux_live_component',
                'method' => 'post',
                'url_reference_type' => 1, // UrlGeneratorInterface::ABSOLUTE_PATH
            ];
            $builder->register(GlobalSearch::class)
                ->setAutoconfigured(true)
                ->setAutowired(true)
                ->addTag('twig.component', $liveComponentConfig)
                ->addTag('controller.service_arguments')
            ;
        }
    }

    public function build(ContainerBuilder $builder): void
    {
        $builder->addCompilerPass(new SearchProviderPass());
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $config = $builder->getExtensionConfig($this->extensionAlias);
        $hasUi = false;

        foreach ($config as $subConfig) {
            if (!isset($subConfig['engines']) || !\is_array($subConfig['engines'])) {
                continue;
            }

            foreach ($subConfig['engines'] as $engineConfig) {
                if (\is_array($engineConfig) && ($engineConfig['ui'] ?? true)) {
                    $hasUi = true;
                    break 2;
                }
            }
        }

        if ([] === $config) {
            $hasUi = true;
        }

        if (!$hasUi) {
            return;
        }

        $bundleDir = $this->getPath();

        if ($builder->hasExtension('twig')) {
            $builder->prependExtensionConfig('twig', [
                'paths' => [
                    $bundleDir.'/templates' => 'SymkitSearch',
                ],
            ]);
        }

        if ($builder->hasExtension('twig_component')) {
            $builder->prependExtensionConfig('twig_component', [
                'defaults' => [
                    'Symkit\SearchBundle\Twig\Component\\' => 'components/',
                ],
            ]);
        }

        if ($builder->hasExtension('framework') && class_exists(\Symfony\Component\AssetMapper\AssetMapperInterface::class)) {
            $builder->prependExtensionConfig('framework', [
                'asset_mapper' => [
                    'paths' => [
                        $bundleDir.'/assets/controllers' => 'search',
                    ],
                ],
            ]);
        }
    }
}
