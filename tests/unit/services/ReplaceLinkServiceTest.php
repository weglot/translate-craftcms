<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\services;

use PHPUnit\Framework\TestCase;
use Weglot\Client\Api\LanguageEntry;
use weglot\craftweglot\services\ReplaceLinkService;
use weglot\craftweglot\services\RequestUrlService;
use Weglot\Util\Url;

final class ReplaceLinkServiceTest extends TestCase
{
    private function makeRequestUrlServiceStub(string $targetUrlOrEmpty): RequestUrlService
    {
        // Stub minimal qui renvoie un "objet URL" avec getForLanguage()
        return new class($targetUrlOrEmpty) extends RequestUrlService {
            public function __construct(private readonly string $target)
            {
                parent::__construct();
            }

            public function createUrlObject(string $url): Url
            {
                $originalLang = new LanguageEntry('en', 'en', 'English', 'English', false);

                $entry = new LanguageEntry('fr', 'fr', 'French', 'Français', false);

                return new Url($this->target, $originalLang, [$entry], null, [], []);
            }
        };
    }

    public function testReplaceUrlReturnsOriginalWhenNoReplacement(): void
    {
        $original = 'https://weglot-craft-project.ddev.site/blog/abc';
        $rus = $this->makeRequestUrlServiceStub(''); // simulate getForLanguage() => ''

        $svc = new ReplaceLinkService($rus);
        $lang = new LanguageEntry('fr', 'fr', 'French', 'Français', false);

        $out = $svc->replaceUrl($original, $lang, true);
        self::assertSame($original, $out);
    }
}
