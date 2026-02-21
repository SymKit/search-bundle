<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Service;

use Symfony\Component\DependencyInjection\ServiceLocator;
use Symkit\SearchBundle\Contract\SearchEngineRegistryInterface;
use Symkit\SearchBundle\Contract\SearchServiceInterface;
use Symkit\SearchBundle\Exception\EngineNotFoundException;

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
            throw EngineNotFoundException::create($engine, array_keys($this->engines->getProvidedServices()));
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
