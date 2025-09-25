<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\services;

use PHPUnit\Framework\TestCase;
use Weglot\Client\Api\LanguageEntry;
use weglot\craftweglot\services\ReplaceLinkService;
use weglot\craftweglot\services\RequestUrlService;

/**
 * Test ciblé sur la logique regex de replaceA (sans dépendre d’un contexte Craft complet).
 * On crée une sous-classe qui:
 * - fournit un LanguageEntry “fr”
 * - override replaceUrl() pour préfixer l’URL avec /fr/ (logique simplifiée).
 */
final class ReplaceLinkServiceReplaceATest extends TestCase
{
    private function makeService(): ReplaceLinkService
    {
        $rus = $this->createMock(RequestUrlService::class);

        return new class($rus) extends ReplaceLinkService {
            private readonly LanguageEntry $testLang;

            public function __construct(RequestUrlService $rus)
            {
                parent::__construct($rus);
                $this->testLang = new LanguageEntry('fr', 'fr', 'French', 'Français', false);
            }

            // On neutralise replaceUrl pour ne pas dépendre d’autres services
            public function replaceUrl(string $url, LanguageEntry $language, bool $evenExcluded = true): string
            {
                // Exemple simple: insérer /fr après le host si pas déjà présent
                return preg_replace('#^(https?://[^/]+)(/)?#', '$1/fr/', $url);
            }

            // Petit helper pour appeler la méthode protégée avec nos paramètres
            public function callReplaceA(string $html, string $currentUrl, string $quote1, string $quote2, ?string $sometags, ?string $sometags2): string
            {
                // Copie de la logique de replaceA originale, mais on force $language à $this->testLang
                $newUrl = $this->replaceUrl($currentUrl, $this->testLang);
                $sometags ??= '';
                $sometags2 ??= '';
                if ('' === $quote2) {
                    $quote2 = $quote1;
                }

                $regex = '/<a'.preg_quote($sometags, '/').'href='.preg_quote($quote1.$currentUrl.$quote2, '/').preg_quote($sometags2, '/').'>/';
                $replacement = '<a'.$sometags.'href='.$quote1.$newUrl.$quote2.$sometags2.'>';

                return preg_replace($regex, $replacement, $html);
            }
        };
    }

    public function testReplaceAWithAttributesBeforeAndAfter(): void
    {
        $svc = $this->makeService();

        $html = '<a class="x" data-foo="bar" href="https://example.test/blog/slug" rel="nofollow">Lire</a>';
        $currentUrl = 'https://example.test/blog/slug';
        $quote1 = '"';
        $quote2 = '"';
        $sometags = ' class="x" data-foo="bar" ';
        $sometags2 = ' rel="nofollow"';

        // @phpstan-ignore-next-line TODO rewrite the test
        $out = $svc->callReplaceA($html, $currentUrl, $quote1, $quote2, $sometags, $sometags2);

        self::assertStringContainsString('href="https://example.test/fr/blog/slug"', $out);
        self::assertStringContainsString('class="x"', $out);
        self::assertStringContainsString('rel="nofollow"', $out);
    }

    public function testReplaceAWithSingleQuotes(): void
    {
        $svc = $this->makeService();

        $html = "<a data-x='1' href='https://example.test/page' id='y'>Go</a>";
        $currentUrl = 'https://example.test/page';
        $quote1 = "'";
        $quote2 = "'";
        $sometags = " data-x='1' ";
        $sometags2 = " id='y'";

        // @phpstan-ignore-next-line TODO rewrite the test
        $out = $svc->callReplaceA($html, $currentUrl, $quote1, $quote2, $sometags, $sometags2);

        self::assertStringContainsString("href='https://example.test/fr/page'", $out);
        self::assertStringContainsString("data-x='1'", $out);
        self::assertStringContainsString("id='y'", $out);
    }
}
