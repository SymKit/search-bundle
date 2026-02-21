<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Tests\Unit\Attribute;

use Attribute;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symkit\SearchBundle\Attribute\AsSearchProvider;

#[CoversClass(AsSearchProvider::class)]
final class AsSearchProviderTest extends TestCase
{
    public function testDefaultEngineIsNull(): void
    {
        $attribute = new AsSearchProvider();

        self::assertNull($attribute->engine);
    }

    public function testEngineCanBeSpecified(): void
    {
        $attribute = new AsSearchProvider(engine: 'admin');

        self::assertSame('admin', $attribute->engine);
    }

    public function testIsTargetClass(): void
    {
        $reflection = new ReflectionClass(AsSearchProvider::class);
        $attributes = $reflection->getAttributes(Attribute::class);

        self::assertCount(1, $attributes);

        $attr = $attributes[0]->newInstance();
        self::assertSame(Attribute::TARGET_CLASS, $attr->flags);
    }
}
