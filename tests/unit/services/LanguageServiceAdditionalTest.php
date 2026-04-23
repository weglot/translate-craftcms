<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\services;

use PHPUnit\Framework\TestCase;
use weglot\craftweglot\Plugin;
use weglot\craftweglot\services\LanguageService;
use weglot\craftweglot\services\OptionService;
use Weglot\Vendor\Weglot\Client\Api\LanguageEntry;

final class LanguageServiceAdditionalTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Fixtures & factories
    // -------------------------------------------------------------------------

    /**
     * Inject an OptionService stub that returns $langFrom for 'language_from'
     * and null for all other options.
     */
    private function injectOptionStub(string $langFrom = ''): void
    {
        $stub = new class($langFrom) extends OptionService {
            public function __construct(private readonly string $langFrom)
            {
                parent::__construct();
            }

            public function getOption(string $key): ?string
            {
                return 'language_from' === $key ? $this->langFrom : null;
            }
        };
        Plugin::getInstance()->set('option', $stub);
    }

    private function entry(string $internal, string $external): LanguageEntry
    {
        return new LanguageEntry($internal, $external, $internal, $internal, false);
    }

    // -------------------------------------------------------------------------
    // codesFromDestinationEntries
    // -------------------------------------------------------------------------

    public function testCodesFromDestinationEntriesReturnsEmptyArrayForNoEntries(): void
    {
        $svc = new LanguageService();
        self::assertSame([], $svc->codesFromDestinationEntries([], false));
    }

    public function testCodesFromDestinationEntriesNormalizesUnderscoreToHyphen(): void
    {
        $svc = new LanguageService();
        // zh_TW → zh-TW (underscore replaced, region uppercased)
        $codes = $svc->codesFromDestinationEntries([$this->entry('zh', 'zh_TW')], false);

        self::assertSame(['zh-TW'], $codes);
    }

    public function testCodesFromDestinationEntriesDeduplicatesIdenticalCodes(): void
    {
        $svc = new LanguageService();
        $codes = $svc->codesFromDestinationEntries([
            $this->entry('fr', 'fr'),
            $this->entry('fr', 'fr'),
        ], false);

        self::assertSame(['fr'], $codes);
    }

    public function testCodesFromDestinationEntriesExcludesSourceLanguage(): void
    {
        // Inject an OptionService that returns 'fr' as the source language.
        $this->injectOptionStub('fr');

        $svc = new LanguageService();
        $codes = $svc->codesFromDestinationEntries([
            $this->entry('fr', 'fr'),
            $this->entry('de', 'de'),
        ], true);

        self::assertNotContains('fr', $codes);
        self::assertContains('de', $codes);
    }

    public function testCodesFromDestinationEntriesDoesNotExcludeSourceWhenFlagIsFalse(): void
    {
        $this->injectOptionStub('fr');

        $svc = new LanguageService();
        $codes = $svc->codesFromDestinationEntries([
            $this->entry('fr', 'fr'),
            $this->entry('de', 'de'),
        ], false);

        self::assertContains('fr', $codes);
        self::assertContains('de', $codes);
    }

    // -------------------------------------------------------------------------
    // getOriginalLanguageNameCustom
    // -------------------------------------------------------------------------

    public function testGetOriginalLanguageNameCustomReturnsNullWhenNotConfigured(): void
    {
        $this->injectOptionStub();

        $svc = new LanguageService();
        self::assertNull($svc->getOriginalLanguageNameCustom());
    }
}
