<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Service;

use Symkit\SearchBundle\Contract\SearchProviderInterface;
use Symkit\SearchBundle\Contract\SearchServiceInterface;
use Symkit\SearchBundle\Model\SearchResultGroup;
use Traversable;

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
        if ('' === mb_trim($query)) {
            return;
        }

        foreach ($this->getSortedProviders() as $provider) {
            $results = $provider->search($query);
            $resultsArray = $results instanceof Traversable ? iterator_to_array($results) : (array) $results;

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
        $providers = $this->providers instanceof Traversable
            ? iterator_to_array($this->providers)
            : (array) $this->providers;

        usort($providers, static fn (SearchProviderInterface $a, SearchProviderInterface $b): int => $a->getPriority() <=> $b->getPriority());

        return $providers;
    }
}
