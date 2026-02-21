<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final readonly class SearchProviderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('symkit_search.engines')) {
            return;
        }

        /** @var array<string, array{ui: bool}> $engines */
        $engines = $container->getParameter('symkit_search.engines');
        $taggedProviders = $container->findTaggedServiceIds('symkit_search.provider');

        /** @var array<string, list<Reference>> $providersByEngine */
        $providersByEngine = [];

        foreach (array_keys($engines) as $engineName) {
            $providersByEngine[$engineName] = [];
        }

        foreach ($taggedProviders as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                $engine = $attributes['engine'] ?? null;

                if (null === $engine) {
                    foreach (array_keys($engines) as $engineName) {
                        $providersByEngine[$engineName][] = new Reference($serviceId);
                    }
                } elseif (isset($providersByEngine[$engine])) {
                    $providersByEngine[$engine][] = new Reference($serviceId);
                }
            }
        }

        foreach ($engines as $engineName => $engineConfig) {
            $serviceId = 'symkit_search.engine.'.$engineName;

            if (!$container->hasDefinition($serviceId)) {
                continue;
            }

            $definition = $container->getDefinition($serviceId);
            $definition->setArgument('$providers', new IteratorArgument($providersByEngine[$engineName] ?? []));
        }
    }
}
