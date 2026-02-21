<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsSearchProvider
{
    public function __construct(
        public ?string $engine = null,
    ) {
    }
}
