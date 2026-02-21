<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Service;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symkit\SearchBundle\Contract\SearchProviderInterface;
use Symkit\SearchBundle\Contract\SearchServiceInterface;
use Symkit\SearchBundle\Event\PostSearchEvent;
use Symkit\SearchBundle\Event\PreSearchEvent;
use Symkit\SearchBundle\Model\SearchResultGroup;

final readonly class SearchService implements SearchServiceInterface
{
    /**
     * @var array<SearchProviderInterface>
     */
    private array $sortedProviders;

    /**
     * @param iterable<SearchProviderInterface> $providers
     */
    public function __construct(
        iterable $providers,
        private string $engineName = 'default',
        private ?EventDispatcherInterface $dispatcher = null,
    ) {
        $sorted = [...$providers];
        usort($sorted, static fn (SearchProviderInterface $a, SearchProviderInterface $b): int => $a->getPriority() <=> $b->getPriority());
        $this->sortedProviders = $sorted;
    }

    /**
     * @return iterable<SearchResultGroup>
     */
    public function search(string $query, ?int $maxResults = null): iterable
    {
        if ('' === trim($query)) {
            return;
        }

        if (null !== $this->dispatcher) {
            $preEvent = new PreSearchEvent($query, $this->engineName);
            $this->dispatcher->dispatch($preEvent);
            $query = $preEvent->getQuery();

            if ('' === trim($query)) {
                return;
            }
        }

        $groups = [];
        $totalResults = 0;

        foreach ($this->sortedProviders as $provider) {
            $resultsArray = [...$provider->search($query)];

            if (null !== $maxResults) {
                $remaining = $maxResults - $totalResults;

                if ($remaining <= 0) {
                    break;
                }

                $resultsArray = \array_slice($resultsArray, 0, $remaining);
            }

            if ([] !== $resultsArray) {
                $groups[] = new SearchResultGroup(
                    category: $provider->getCategory(),
                    results: $resultsArray,
                    priority: $provider->getPriority(),
                );
                $totalResults += \count($resultsArray);
            }
        }

        if (null !== $this->dispatcher) {
            $postEvent = new PostSearchEvent($query, $groups, $this->engineName);
            $this->dispatcher->dispatch($postEvent);
            $groups = $postEvent->getResults();
        }

        yield from $groups;
    }
}
