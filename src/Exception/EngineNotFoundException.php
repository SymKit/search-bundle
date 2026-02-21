<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Exception;

use InvalidArgumentException;

final class EngineNotFoundException extends InvalidArgumentException
{
    /**
     * @param list<string> $available
     */
    public static function create(string $engine, array $available): self
    {
        return new self(\sprintf(
            'Search engine "%s" is not registered. Available engines: %s.',
            $engine,
            implode(', ', $available),
        ));
    }
}
