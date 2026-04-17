<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\services;

use PHPUnit\Framework\TestCase;
use weglot\craftweglot\Plugin;
use weglot\craftweglot\services\LanguageService;
use weglot\craftweglot\services\OptionService;
use Weglot\Vendor\Weglot\Client\Api\LanguageCollection;
use Weglot\Vendor\Weglot\Client\Api\LanguageEntry;

final class LanguageServiceLegacyFallbackTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Fixtures & factories
    // -------------------------------------------------------------------------

    /**
     * Inject an OptionService stub returning pipe-separated legacy languages
     * and no destination_language (modern format).
     */
    private function injectLegacyOptionStub(string $languages, string $languageFrom = 'en'): void
    {
        $stub = new class($languages, $languageFrom) extends OptionService {
            public function __construct(
                private readonly string $languages,
                private readonly string $languageFrom,
            ) {
                parent::__construct();
            }

            public function getOption(string $key): ?string
            {
                return match ($key) {
                    'destination_language' => null,
                    'languages' => $this->languages,
                    'language_from' => $this->languageFrom,
                    default => null,
                };
            }
        };
        Plugin::getInstance()->set('option', $stub);
    }

    /**
     * Inject a LanguageService stub with a fixed in-memory catalog, bypassing
     * the Weglot API entirely.
     *
     * @param string[] $codes
     */
    private function injectLangCatalog(array $codes): void
    {
        $stub = new class($codes) extends LanguageService {
            /** @param string[] $codes */
            public function __construct(private readonly array $codes)
            {
                parent::__construct();
            }

            /** @throws \Exception */
            public function getAllLanguages(): LanguageCollection
            {
                $collection = new LanguageCollection();
                foreach ($this->codes as $code) {
                    $collection->addOne(new LanguageEntry($code, $code, $code, $code, false));
                }

                return $collection;
            }
        };
        Plugin::getInstance()->set('language', $stub);
    }

    // -------------------------------------------------------------------------
    // getDestinationLanguages — legacy pipe-separated format
    // -------------------------------------------------------------------------

    public function testLegacyPipeSeparatedLanguagesAreResolvedCorrectly(): void
    {
        $this->injectLegacyOptionStub('fr|es|it');
        $this->injectLangCatalog(['en', 'fr', 'es', 'it']);

        $entries = Plugin::getInstance()->getLanguage()->getDestinationLanguages();

        self::assertCount(3, $entries);
    }

    public function testLegacyPipeSeparatedCodesExcludeSourceAfterNormalisation(): void
    {
        $this->injectLegacyOptionStub('fr|es|it', 'en');
        $this->injectLangCatalog(['en', 'fr', 'es', 'it']);

        $entries = Plugin::getInstance()->getLanguage()->getDestinationLanguages();
        $codes = Plugin::getInstance()->getLanguage()->codesFromDestinationEntries($entries, true);
        sort($codes);

        self::assertSame(['es', 'fr', 'it'], $codes);
    }

    public function testLegacyCommaSeparatedLanguagesAreResolvedCorrectly(): void
    {
        $this->injectLegacyOptionStub('fr,de');
        $this->injectLangCatalog(['en', 'fr', 'de']);

        $entries = Plugin::getInstance()->getLanguage()->getDestinationLanguages();

        self::assertCount(2, $entries);
    }
}
