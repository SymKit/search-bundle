<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Service;

use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symkit\SearchBundle\Contract\SearchEngineRegistryInterface;
use Symkit\SearchBundle\Contract\SearchServiceInterface;

final readonly class SearchEngineRegistry implements SearchEngineRegistryInterface
{
    /**
     * @param ServiceLocator<SearchServiceInterface> $engines
     */
    public function __construct(
        private ServiceLocator $engines,
        private string $defaultEngine,
    ) {
    }

    public function get(string $engine): SearchServiceInterface
    {
        if (!$this->engines->has($engine)) {
            throw new InvalidArgumentException(\sprintf('Search engine "%s" is not registered. Available engines: %s.', $engine, implode(', ', array_keys($this->engines->getProvidedServices()))));
        }

        return $this->engines->get($engine);
    }

    public function has(string $engine): bool
    {
        return $this->engines->has($engine);
    }

    public function getDefault(): SearchServiceInterface
    {
        return $this->get($this->defaultEngine);
    }
}
