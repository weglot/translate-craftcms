<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\helpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use weglot\craftweglot\helpers\HelperReplaceUrl;

final class HelperReplaceUrlTest extends TestCase
{
    public function testAnchorPatternCapturesDoubleQuotes(): void
    {
        $patterns = HelperReplaceUrl::getReplaceModifyLink();
        $patternA = $patterns['a'];

        $html = '<a class="x" data-foo="bar" href="https://example.test/blog/article-demo" data-id="1">Lire</a>';

        $m = [];
        $count = preg_match_all($patternA, $html, $m, \PREG_PATTERN_ORDER);
        self::assertSame(1, $count);

        // Groupes attendus:
        // [1] = attributs avant href
        // [2] = quote ouvrante (et fermante via backref \2)
        // [3] = URL
        // [4] = attributs après l’URL jusqu’à '>'
        self::assertStringContainsString('class="x"', $m[1][0] ?? '');
        self::assertSame('"', $m[2][0] ?? null);
        self::assertSame('https://example.test/blog/article-demo', $m[3][0] ?? null);
        self::assertStringContainsString('data-id="1"', $m[4][0] ?? '');
    }

    public function testAnchorPatternCapturesSingleQuotes(): void
    {
        $patterns = HelperReplaceUrl::getReplaceModifyLink();
        $patternA = $patterns['a'];

        $html = "<a data-foo='bar' href='https://example.test/blog/slug' rel='nofollow'>Lire</a>";

        $m = [];
        $count = preg_match_all($patternA, $html, $m, \PREG_PATTERN_ORDER);
        self::assertSame(1, $count);

        self::assertStringContainsString("data-foo='bar'", $m[1][0] ?? '');
        self::assertSame("'", $m[2][0] ?? null);
        self::assertSame('https://example.test/blog/slug', $m[3][0] ?? null);
        self::assertStringContainsString("rel='nofollow'", $m[4][0] ?? '');
    }

    public function testHxGetPatternCapturesUrlOnArbitraryElement(): void
    {
        $patterns = HelperReplaceUrl::getReplaceModifyLink();
        $patternHxGet = $patterns['hxget'];

        $html = '<div id="htmx-rooms-453607" hx-get="https://example.test/rooms/grid/111874/453607" hx-target="#htmx-rooms-453607" hx-trigger="custom-load" class="">';

        $m = [];
        $count = preg_match_all($patternHxGet, $html, $m, \PREG_PATTERN_ORDER);
        self::assertSame(1, $count);

        self::assertStringContainsString('id="htmx-rooms-453607"', $m[1][0] ?? '');
        self::assertSame('"', $m[2][0] ?? null);
        self::assertSame('https://example.test/rooms/grid/111874/453607', $m[3][0] ?? null);
        self::assertStringContainsString('hx-target="#htmx-rooms-453607"', $m[4][0] ?? '');
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function hxVerbProvider(): array
    {
        return [
            'hx-get' => ['hxget', 'hx-get'],
            'hx-post' => ['hxpost', 'hx-post'],
            'hx-put' => ['hxput', 'hx-put'],
            'hx-patch' => ['hxpatch', 'hx-patch'],
            'hx-delete' => ['hxdelete', 'hx-delete'],
        ];
    }

    #[DataProvider('hxVerbProvider')]
    public function testHxVerbPatternsCaptureUrl(string $key, string $attribute): void
    {
        $patterns = HelperReplaceUrl::getReplaceModifyLink();
        self::assertArrayHasKey($key, $patterns);

        $html = '<button '.$attribute.'="https://example.test/rooms/book/42">Book</button>';

        $m = [];
        $count = preg_match_all($patterns[$key], $html, $m, \PREG_PATTERN_ORDER);
        self::assertSame(1, $count);
        self::assertSame('https://example.test/rooms/book/42', $m[3][0] ?? null);
    }

    public function testHxGetPatternSkipsCraftActionUrls(): void
    {
        $patterns = HelperReplaceUrl::getReplaceModifyLink();
        $patternHxGet = $patterns['hxget'];

        $html = '<div hx-get="/actions/weglot/api/validate-api-key"></div>';

        $m = [];
        $count = preg_match_all($patternHxGet, $html, $m, \PREG_PATTERN_ORDER);
        self::assertSame(0, $count);
    }
}
