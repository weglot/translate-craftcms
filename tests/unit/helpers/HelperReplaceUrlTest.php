<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\helpers;

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
}
