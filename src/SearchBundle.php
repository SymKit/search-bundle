<?php

declare(strict_types=1);

namespace Symkit\SearchBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symkit\SearchBundle\Contract\SearchProviderInterface;
use Symkit\SearchBundle\Contract\SearchServiceInterface;
use Symkit\SearchBundle\Service\SearchService;
use Symkit\SearchBundle\Twig\Component\GlobalSearch;

class SearchBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->booleanNode('search')
                    ->defaultTrue()
                    ->info('Enable the search service and provider registration (API).')
                ->end()
                ->booleanNode('ui')
                    ->defaultTrue()
                    ->info('Enable the GlobalSearch component, Twig namespace, and AssetMapper paths.')
                ->end()
            ->end();
    }

    /**
     * @param array{search: bool, ui: bool} $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if ($config['search']) {
            $builder->register(SearchService::class)
                ->setArgument('$providers', tagged_iterator('symkit_search.provider'))
            ;

            $builder->setAlias(SearchServiceInterface::class, SearchService::class)
                ->setPublic(true)
            ;

            $builder->registerForAutoconfiguration(SearchProviderInterface::class)
                ->addTag('symkit_search.provider')
            ;
        }

        if ($config['search'] && $config['ui']) {
            $builder->register(GlobalSearch::class)
                ->setAutoconfigured(true)
                ->setAutowired(true)
            ;
        }
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $extension = $this->getContainerExtension();
        \assert(null !== $extension);
        $config = $builder->getExtensionConfig($extension->getAlias());
        $uiEnabled = true;

        foreach ($config as $subConfig) {
            if (isset($subConfig['ui'])) {
                $uiEnabled = (bool) $subConfig['ui'];
            }
        }

        if (!$uiEnabled) {
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

        if ($builder->hasExtension('framework')) {
            $frameworkConfig = $builder->getExtensionConfig('framework');
            $hasAssetMapper = class_exists(\Symfony\Component\AssetMapper\AssetMapperInterface::class);

            if ($hasAssetMapper) {
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
}
