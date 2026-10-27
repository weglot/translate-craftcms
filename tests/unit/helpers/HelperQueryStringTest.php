<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\helpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use weglot\craftweglot\helpers\HelperQueryString;

final class HelperQueryStringTest extends TestCase
{
    #[DataProvider('queryProvider')]
    public function testRemoveTrackingParams(string $query, string $expected): void
    {
        self::assertSame($expected, HelperQueryString::removeTrackingParams($query));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function queryProvider(): array
    {
        return [
            'google ads params' => ['page=2&gad_source=1&gad_campaignid=123', 'page=2'],
            'click ids' => ['gclid=a&fbclid=b&msclkid=c&q=shoes', 'q=shoes'],
            'utm prefix' => ['utm_source=news&utm_medium=email&utm_whatever=x&id=7', 'id=7'],
            'only tracking params' => ['gclid=a&utm_source=b', ''],
            'empty query' => ['', ''],
            'order kept' => ['b=2&gclid=x&a=1&c=3', 'b=2&a=1&c=3'],
            'raw encoding kept' => ['q=hello%20world&tag=a+b&gclid=x', 'q=hello%20world&tag=a+b'],
            'array and dotted keys kept' => ['a[]=1&a[]=2&x.y=3&gclid=x', 'a[]=1&a[]=2&x.y=3'],
            'uppercase key' => ['UTM_Source=x&GCLID=y&page=2', 'page=2'],
            'encoded key' => ['utm%5Fsource=x&page=2', 'page=2'],
            'key without value' => ['gclid&page=2&flag', 'page=2&flag'],
            'empty segments dropped' => ['a=1&&b=2&', 'a=1&b=2'],
            'no false positive on similar names' => ['notutm_x=1&gclid2=2&utm=3', 'notutm_x=1&gclid2=2&utm=3'],
        ];
    }

    #[DataProvider('urlProvider')]
    public function testRemoveTrackingParamsFromUrl(string $url, string $expected): void
    {
        self::assertSame($expected, HelperQueryString::removeTrackingParamsFromUrl($url));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function urlProvider(): array
    {
        return [
            'no query' => ['https://example.com/page', 'https://example.com/page'],
            'mixed query' => ['https://example.com/page?page=2&gclid=x', 'https://example.com/page?page=2'],
            'only tracking params' => ['https://example.com/page?gad_source=1', 'https://example.com/page'],
            'fragment kept' => ['https://example.com/page?gclid=x&a=1#top', 'https://example.com/page?a=1#top'],
            'fragment kept without query left' => ['https://example.com/page?gclid=x#top', 'https://example.com/page#top'],
            'question mark in fragment untouched' => ['https://example.com/page#/route?gclid=x', 'https://example.com/page#/route?gclid=x'],
            'question mark in a value kept' => ['https://example.com/?return=/a?b=1&gclid=x', 'https://example.com/?return=/a?b=1'],
        ];
    }
}
