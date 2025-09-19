<?php
// php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use weglot\craftweglot\Plugin;
use weglot\craftweglot\services\OptionService;
use weglot\craftweglot\models\Settings;

final class PluginSettingsNormalizationTest extends TestCase
{
	private Plugin $plugin;

	protected function setUp(): void
	{
		parent::setUp();
		// Récupère l’instance du plugin Craft (présente en environnement de test Craft)
		$this->plugin = Plugin::getInstance();

		// Nettoyage du cache entre tests (si dispo)
		try {
			\Craft::$app->getCache()->flush();
		} catch (\Throwable $e) {
		}
	}

	public function testLanguagesAreNormalizedBeforeSave(): void
	{
		// Fake OptionService qui capture les paramètres passés à saveWeglotSettings()
		$fakeOption = new class extends OptionService {
			public ?array $lastArgs = null;

			public function saveWeglotSettings(string $publicApiKey, string $languageFrom, array|string $destinationLanguages): array
			{
				$this->lastArgs = [
					'publicApiKey' => $publicApiKey,
					'languageFrom' => $languageFrom,
					'destination'  => $destinationLanguages,
				];
				// Pas d’appel HTTP réel: on renvoie un succès immédiat
				return ['success' => true, 'result' => ['ok' => 1]];
			}
		};

		// Injecte le fake dans le plugin
		$this->plugin->set('option', $fakeOption);

		// Prépare des réglages contenant une valeur invalide "1" et une chaîne "fr|es"
		$settings = new Settings();
		$settings->apiKey       = 'wg_public_xxx';
		$settings->languageFrom = 'en';
		$settings->languages    = ['1', 'fr|es'];

		// Monte ces réglages dans le plugin
		$this->plugin->setSettings($settings);

		// Déclenche la sauvegarde (qui doit appeler notre fakeOption->saveWeglotSettings)
		$this->plugin->afterSaveSettings();

		// Assertions
		$this->assertIsArray($fakeOption->lastArgs);
		$this->assertSame('wg_public_xxx', $fakeOption->lastArgs['publicApiKey']);
		$this->assertSame('en', $fakeOption->lastArgs['languageFrom']);

		// Les langues doivent être normalisées en ['fr','es'] et ne plus contenir "1"
		$this->assertSame(['fr','es'], $fakeOption->lastArgs['destination']);
	}
}