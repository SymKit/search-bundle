<?php

declare(strict_types=1);

namespace Symkit\SearchBundle\Tests\Unit\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symkit\SearchBundle\Model\SearchResult;

#[CoversClass(SearchResult::class)]
final class SearchResultTest extends TestCase
{
    public function testConstructWithAllProperties(): void
    {
        $result = new SearchResult(
            title: 'Home',
            subtitle: '/home',
            url: 'https://example.com',
            icon: 'heroicons-outline:home',
            badge: 'Page',
        );

        self::assertSame('Home', $result->title);
        self::assertSame('/home', $result->subtitle);
        self::assertSame('https://example.com', $result->url);
        self::assertSame('heroicons-outline:home', $result->icon);
        self::assertSame('Page', $result->badge);
    }

    public function testConstructWithDefaultBadge(): void
    {
        $result = new SearchResult(
            title: 'About',
            subtitle: '/about',
            url: 'https://example.com/about',
            icon: 'heroicons-outline:info',
        );

        self::assertNull($result->badge);
    }
}
