<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Tests\Unit\DependencyInjection\Compiler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symkit\SearchBundle\DependencyInjection\Compiler\SearchProviderPass;
use Symkit\SearchBundle\Service\SearchService;

#[CoversClass(SearchProviderPass::class)]
final class SearchProviderPassTest extends TestCase
{
    public function testDoesNothingWithoutEnginesParameter(): void
    {
        $container = new ContainerBuilder();
        $pass = new SearchProviderPass();

        $pass->process($container);

        self::assertFalse($container->hasParameter('symkit_search.engines'));
    }

    public function testRoutesProviderWithoutEngineTagToAllEngines(): void
    {
        $container = $this->createContainerWithEngines(['main' => ['ui' => true], 'admin' => ['ui' => false]]);

        $container->register('app.provider.global', 'stdClass')
            ->addTag('symkit_search.provider');

        $pass = new SearchProviderPass();
        $pass->process($container);

        $mainProviders = $this->getProviderReferences($container, 'main');
        $adminProviders = $this->getProviderReferences($container, 'admin');

        self::assertCount(1, $mainProviders);
        self::assertCount(1, $adminProviders);
        self::assertSame('app.provider.global', (string) $mainProviders[0]);
        self::assertSame('app.provider.global', (string) $adminProviders[0]);
    }

    public function testRoutesProviderWithEngineTagToSpecificEngine(): void
    {
        $container = $this->createContainerWithEngines(['main' => ['ui' => true], 'admin' => ['ui' => false]]);

        $container->register('app.provider.admin_only', 'stdClass')
            ->addTag('symkit_search.provider', ['engine' => 'admin']);

        $pass = new SearchProviderPass();
        $pass->process($container);

        $mainProviders = $this->getProviderReferences($container, 'main');
        $adminProviders = $this->getProviderReferences($container, 'admin');

        self::assertCount(0, $mainProviders);
        self::assertCount(1, $adminProviders);
        self::assertSame('app.provider.admin_only', (string) $adminProviders[0]);
    }

    public function testIgnoresProviderTaggedForUnknownEngine(): void
    {
        $container = $this->createContainerWithEngines(['main' => ['ui' => true]]);

        $container->register('app.provider.unknown', 'stdClass')
            ->addTag('symkit_search.provider', ['engine' => 'nonexistent']);

        $pass = new SearchProviderPass();
        $pass->process($container);

        $mainProviders = $this->getProviderReferences($container, 'main');

        self::assertCount(0, $mainProviders);
    }

    public function testSkipsMissingEngineDefinitionButProcessesOthers(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('symkit_search.engines', [
            'missing' => ['ui' => true],
            'present' => ['ui' => true],
        ]);

        $definition = new Definition(SearchService::class);
        $definition->setArgument('$providers', new IteratorArgument([]));
        $container->setDefinition('symkit_search.engine.present', $definition);

        $container->register('app.provider', 'stdClass')
            ->addTag('symkit_search.provider');

        $pass = new SearchProviderPass();
        $pass->process($container);

        self::assertFalse($container->hasDefinition('symkit_search.engine.missing'));

        $presentProviders = $this->getProviderReferences($container, 'present');
        self::assertCount(1, $presentProviders);
    }

    /**
     * @param array<string, array{ui: bool}> $engines
     */
    private function createContainerWithEngines(array $engines): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('symkit_search.engines', $engines);

        foreach (array_keys($engines) as $name) {
            $definition = new Definition(SearchService::class);
            $definition->setArgument('$providers', new IteratorArgument([]));
            $container->setDefinition('symkit_search.engine.'.$name, $definition);
        }

        return $container;
    }

    /**
     * @return array<Reference>
     */
    private function getProviderReferences(ContainerBuilder $container, string $engine): array
    {
        $definition = $container->getDefinition('symkit_search.engine.'.$engine);
        $argument = $definition->getArgument('$providers');
        \assert($argument instanceof IteratorArgument);

        return $argument->getValues();
    }
}
