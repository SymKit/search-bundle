<?php

declare(strict_types=1);

namespace Symkit\SearchBundle;

use Symkit\SearchBundle\Contract\SearchProviderInterface;
use Symkit\SearchBundle\Contract\SearchServiceInterface;
use Symkit\SearchBundle\Service\SearchService;
use Symkit\SearchBundle\Twig\Component\GlobalSearch;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

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

            $builder->setAlias(SearchServiceInterface::class, SearchService::class);

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
        $config = $builder->getExtensionConfig('symkit_search');
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

        $builder->prependExtensionConfig('twig', [
            'paths' => [
                $bundleDir . '/templates' => 'SymkitSearch',
            ],
        ]);

        $builder->prependExtensionConfig('twig_component', [
            'defaults' => [
                'Symkit\SearchBundle\Twig\Component\\' => 'components/',
            ],
        ]);

        $builder->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    $bundleDir . '/assets/controllers' => 'search',
                ],
            ],
        ]);
    }
}
