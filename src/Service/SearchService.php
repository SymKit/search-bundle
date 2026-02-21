<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Service;

use Symkit\SearchBundle\Contract\SearchProviderInterface;
use Symkit\SearchBundle\Contract\SearchServiceInterface;
use Symkit\SearchBundle\Model\SearchResultGroup;

final readonly class SearchService implements SearchServiceInterface
{
    /**
     * @param iterable<SearchProviderInterface> $providers
     */
    public function __construct(
        private iterable $providers,
    ) {
    }

    /**
     * @return iterable<SearchResultGroup>
     */
    public function search(string $query): iterable
    {
        if ('' === trim($query)) {
            return;
        }

        foreach ($this->getSortedProviders() as $provider) {
            $resultsArray = [...$provider->search($query)];

            if ([] !== $resultsArray) {
                yield new SearchResultGroup(
                    category: $provider->getCategory(),
                    results: $resultsArray,
                    priority: $provider->getPriority(),
                );
            }
        }
    }

    /**
     * @return array<SearchProviderInterface>
     */
    private function getSortedProviders(): array
    {
        $providers = [...$this->providers];

        usort($providers, static fn (SearchProviderInterface $a, SearchProviderInterface $b): int => $a->getPriority() <=> $b->getPriority());

        return $providers;
    }
}
