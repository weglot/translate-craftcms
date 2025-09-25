<?php

// php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Weglot\Client\Api\LanguageCollection;
use Weglot\Client\Api\LanguageEntry;
use weglot\craftweglot\services\LanguageService;

final class CodesFromDestinationEntriesTest extends TestCase
{
    public function testNormalizeCodes(): void
    {
        // Service minimal (évite tout appel externe)
        $svc = new class extends LanguageService {
            protected function fetchLanguagesFromApi(): LanguageCollection
            {
                return new LanguageCollection();
            }
        };

        $entries = [
            new LanguageEntry('fr', 'fr-fr', 'French', 'Français', false),
            new LanguageEntry('es', 'es', 'Spanish', 'Español', false),
        ];

        $codes = $svc->codesFromDestinationEntries($entries, false);
        sort($codes);
        // Attendu: fr-FR et es (normalisation)
        $this->assertSame(['es', 'fr-FR'], $codes);
    }
}
