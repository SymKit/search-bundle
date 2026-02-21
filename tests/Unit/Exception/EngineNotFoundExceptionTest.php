<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Tests\Unit\Exception;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symkit\SearchBundle\Exception\EngineNotFoundException;

#[CoversClass(EngineNotFoundException::class)]
final class EngineNotFoundExceptionTest extends TestCase
{
    public function testCreateFormatsMessage(): void
    {
        $exception = EngineNotFoundException::create('unknown', ['main', 'admin']);

        self::assertSame(
            'Search engine "unknown" is not registered. Available engines: main, admin.',
            $exception->getMessage(),
        );
    }

    public function testIsInvalidArgumentException(): void
    {
        $exception = EngineNotFoundException::create('x', []);

        self::assertInstanceOf(InvalidArgumentException::class, $exception);
    }
}
