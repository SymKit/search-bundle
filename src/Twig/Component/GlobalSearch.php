<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Twig\Component;

use Symkit\SearchBundle\Contract\SearchServiceInterface;
use Symkit\SearchBundle\Model\SearchResultGroup;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PreReRender;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('GlobalSearch', template: '@SymkitSearch/components/GlobalSearch.html.twig')]
final class GlobalSearch
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $query = '';

    #[LiveProp(writable: true)]
    public bool $isOpen = false;

    /**
     * @var array<SearchResultGroup>
     */
    public array $results = [];

    public function __construct(
        private readonly SearchServiceInterface $searchService,
    ) {
    }

    #[LiveAction]
    public function open(): void
    {
        $this->isOpen = true;
    }

    #[LiveAction]
    public function close(): void
    {
        $this->isOpen = false;
        $this->query = '';
    }

    #[PreReRender]
    public function prepareResults(): void
    {
        if ('' === mb_trim($this->query)) {
            $this->results = [];

            return;
        }

        $results = $this->searchService->search($this->query);
        $this->results = \is_array($results) ? $results : iterator_to_array($results);
    }

    public function hasResults(): bool
    {
        return [] !== $this->results;
    }
}
