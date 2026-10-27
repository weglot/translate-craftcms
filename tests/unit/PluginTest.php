<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit;

use PHPUnit\Framework\TestCase;
use weglot\craftweglot\models\Settings;
use weglot\craftweglot\Plugin;
use weglot\craftweglot\services\LanguageService;
use weglot\craftweglot\services\OptionService;

final class PluginTest extends TestCase
{
    private Plugin $plugin;

    private ?LanguageService $originalLanguage = null;

    private int $languageCalls = 0;

    protected function setUp(): void
    {
        parent::setUp();
        // Récupère l’instance du plugin Craft (présente en environnement de test Craft)
        $this->plugin = Plugin::getInstance();
        $this->originalLanguage = $this->plugin->getLanguage();

        // Nettoyage du cache entre tests (si dispo)
        try {
            \Craft::$app->getCache()->flush();
        } catch (\Throwable) {
        }
    }

    protected function tearDown(): void
    {
        // Guarded: setUp() may have thrown before the component was captured.
        if ($this->originalLanguage instanceof LanguageService) {
            $this->plugin->set('language', $this->originalLanguage);
        }
        parent::tearDown();
    }

    public function testLanguagesAreNormalizedBeforeSave(): void
    {
        // Fake OptionService qui capture les paramètres passés à saveWeglotSettings()
        $fakeOption = new class extends OptionService {
            /** @var array<string, mixed>|null */
            public ?array $lastArgs = null;

            public function saveWeglotSettings(string $publicApiKey, string $languageFrom, array|string $destinationLanguages): array
            {
                $this->lastArgs = [
                    'publicApiKey' => $publicApiKey,
                    'languageFrom' => $languageFrom,
                    'destination' => $destinationLanguages,
                ];

                // Pas d’appel HTTP réel: on renvoie un succès immédiat
                return ['success' => true, 'result' => ['ok' => 1]];
            }
        };

        // Injecte le fake dans le plugin
        $this->plugin->set('option', $fakeOption);

        // Prépare des réglages contenant une valeur invalide "1" et une chaîne "fr|es"
        $settings = new Settings();
        $settings->apiKey = 'wg_public_xxx';
        $settings->languageFrom = 'en';
        $settings->languages = ['1', 'fr|es'];

        // Monte ces réglages dans le plugin
        $this->plugin->setSettings($settings->toArray());

        // Déclenche la sauvegarde (qui doit appeler notre fakeOption->saveWeglotSettings)
        $this->plugin->afterSaveSettings();

        // Assertions
        self::assertIsArray($fakeOption->lastArgs);
        self::assertSame('wg_public_xxx', $fakeOption->lastArgs['publicApiKey']);
        self::assertSame('en', $fakeOption->lastArgs['languageFrom']);

        // Les langues doivent être normalisées en ['fr','es'] et ne plus contenir "1"
        self::assertSame(['fr', 'es'], $fakeOption->lastArgs['destination']);
    }

    public function testFrontPageUrlOpensTheFirstDestinationLanguageHome(): void
    {
        self::assertSame('https://x.test/fr/', Plugin::buildFrontPageUrl('https://x.test', ['fr', 'de']));
        self::assertSame('https://x.test/fr/', Plugin::buildFrontPageUrl('https://x.test/', ['fr']));
    }

    public function testFrontPageUrlKeepsTheSubdirectory(): void
    {
        self::assertSame('https://x.test/shop/br/', Plugin::buildFrontPageUrl('https://x.test/shop/', ['br']));
    }

    public function testFrontPageUrlFallsBackToTheSiteHomeWithoutDestinationLanguage(): void
    {
        self::assertSame('https://x.test/', Plugin::buildFrontPageUrl('https://x.test/', []));
        self::assertSame('https://x.test/', Plugin::buildFrontPageUrl('https://x.test/', ['']));
    }

    public function testOnboardingLinkReadsNoLanguageWhenTheOptionsFailedToLoad(): void
    {
        $this->stubLanguage(['fr']);

        self::assertSame('https://x.test/', $this->onboardingFrontPageUrl('https://x.test/', false));
        self::assertSame(0, $this->languageCalls);
        self::assertSame('https://x.test/fr/', $this->onboardingFrontPageUrl('https://x.test/', true));
    }

    public function testOnboardingLinkFallsBackToTheSiteHomeWhenTheLanguagesCannotBeRead(): void
    {
        $this->stubLanguage(null);

        self::assertSame('https://x.test/', $this->onboardingFrontPageUrl('https://x.test/', true));
    }

    private function onboardingFrontPageUrl(string $siteBaseUrl, bool $optionsLoaded): string
    {
        $method = new \ReflectionMethod($this->plugin, 'onboardingFrontPageUrl');

        return (string) $method->invoke($this->plugin, $siteBaseUrl, $optionsLoaded);
    }

    /**
     * @param list<string>|null $codes null makes getDestinationLanguages() throw
     */
    private function stubLanguage(?array $codes): void
    {
        $calls = &$this->languageCalls;
        $language = new class($codes, $calls) extends LanguageService {
            /** @param list<string>|null $codes */
            public function __construct(private readonly ?array $codes, private int &$calls)
            {
                parent::__construct();
            }

            public function getDestinationLanguages(): array
            {
                ++$this->calls;
                if (null === $this->codes) {
                    throw new \RuntimeException('Languages API unreachable');
                }

                return [];
            }

            public function codesFromDestinationEntries(array $entries, bool $excludeSource = true): array
            {
                return $this->codes ?? [];
            }
        };
        $this->plugin->set('language', $language);
    }
}
