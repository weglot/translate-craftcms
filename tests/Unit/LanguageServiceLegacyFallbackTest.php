<?php

// php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Weglot\Client\Api\LanguageCollection;
use Weglot\Client\Api\LanguageEntry;
use weglot\craftweglot\Plugin;
use weglot\craftweglot\services\LanguageService;
use weglot\craftweglot\services\OptionService;

final class LanguageServiceLegacyFallbackTest extends TestCase
{
    private Plugin $plugin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->plugin = Plugin::getInstance();

        try {
            Craft::$app->getCache()->flush();
        } catch (Throwable $e) {
        }
    }

    public function testLegacyLanguagesPipeSeparated(): void
    {
        // Fake options: destination_language vide, legacy "fr|es|it", et language_from "en"
        $fakeOption = new class extends OptionService {
            public function getOption(string $key)
            {
                $data = [
                    'destination_language' => null,
                    'languages' => 'fr|es|it',
                    'language_from' => 'en',
                ];

                return $data[$key] ?? null;
            }
        };
        $this->plugin->set('option', $fakeOption);

        // LanguageService fake: évite l’appel API et expose un catalogue réduit
        $fakeLanguage = new class extends LanguageService {
            protected function fetchLanguagesFromApi(): LanguageCollection
            {
                $c = new LanguageCollection();
                $c->addOne(new LanguageEntry('en', 'en', 'English', 'English', false));
                $c->addOne(new LanguageEntry('fr', 'fr', 'French', 'Français', false));
                $c->addOne(new LanguageEntry('es', 'es', 'Spanish', 'Español', false));
                $c->addOne(new LanguageEntry('it', 'it', 'Italian', 'Italiano', false));

                return $c;
            }
        };
        $this->plugin->set('language', $fakeLanguage);

        $entries = $this->plugin->getLanguage()->getDestinationLanguages();
        $this->assertCount(3, $entries);

        // Codes normalisés pour le routage (en excluant la source "en")
        $codes = $this->plugin->getLanguage()->codesFromDestinationEntries($entries, true);
        sort($codes);
        $this->assertSame(['es', 'fr', 'it'], $codes);
    }
}
