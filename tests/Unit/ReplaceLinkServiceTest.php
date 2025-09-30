<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use weglot\craftweglot\services\ReplaceLinkService;
use weglot\craftweglot\services\RequestUrlService;
use Weglot\Vendor\Weglot\Util\LanguageEntry;

final class ReplaceLinkServiceTest extends TestCase
{
    private function makeRequestUrlServiceStub(string $targetUrlOrEmpty): RequestUrlService
    {
        // Stub minimal qui renvoie un "objet URL" avec getForLanguage()
        return new class($targetUrlOrEmpty) extends RequestUrlService {
            public function __construct(private string $target)
            {
            }

            public function createUrlObject(string $url)
            {
                return new class($this->target) {
                    public function __construct(private string $target)
                    {
                    }

                    public function getForLanguage($language, bool $evenExcluded)
                    {
                        return $this->target; // peut être '' pour simuler “aucun remplacement”
                    }
                };
            }
        };
    }

    public function testReplaceUrlRebuildsWithTrailingSlashAndKeepsQueryAndFragment(): void
    {
        $original = 'https://weglot-craft-project.ddev.site/blog/article-demo-28-education-2';

        // On renvoie une URL sans slash final dans le path pour vérifier l’ajout
        $target = 'https://weglot-craft-project.ddev.site/fr/blog/article-demo-28-education-2?x=1#y';
        $rus = $this->makeRequestUrlServiceStub($target);

        $svc = new ReplaceLinkService($rus);
        $lang = new LanguageEntry('fr', 'fr', 'French', 'Français', false);

        $out = $svc->replaceUrl($original, $lang, true);

        $this->assertSame(
            'https://weglot-craft-project.ddev.site/fr/blog/article-demo-28-education-2/?x=1#y',
            $out,
            'Devrait ajouter un slash final avant la query et conserver query + fragment.'
        );
    }

    public function testReplaceUrlReturnsOriginalWhenNoReplacement(): void
    {
        $original = 'https://weglot-craft-project.ddev.site/blog/abc';
        $rus = $this->makeRequestUrlServiceStub(''); // simulate getForLanguage() => ''

        $svc = new ReplaceLinkService($rus);
        $lang = new LanguageEntry('fr', 'fr', 'French', 'Français', false);

        $out = $svc->replaceUrl($original, $lang, true);
        $this->assertSame($original, $out);
    }
}
