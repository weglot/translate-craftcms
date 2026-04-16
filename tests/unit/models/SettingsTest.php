<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\models;

use PHPUnit\Framework\TestCase;
use weglot\craftweglot\models\Settings;
use weglot\craftweglot\Plugin;
use weglot\craftweglot\services\UserApiService;

final class SettingsTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Default values
    // -------------------------------------------------------------------------

    public function testDefaultApiKeyIsEmptyString(): void
    {
        self::assertSame('', (new Settings())->apiKey);
    }

    public function testDefaultLanguageFromIsEn(): void
    {
        self::assertSame('en', (new Settings())->languageFrom);
    }

    public function testDefaultEnableDynamicsIsFalse(): void
    {
        self::assertFalse((new Settings())->enableDynamics);
    }

    public function testDefaultLanguagesIsEmptyArray(): void
    {
        self::assertSame([], (new Settings())->languages);
    }

    // -------------------------------------------------------------------------
    // validateApiKey
    // -------------------------------------------------------------------------

    public function testValidateApiKeyDoesNothingWhenKeyIsEmpty(): void
    {
        $settings = new Settings();
        $settings->apiKey = '';

        $settings->validateApiKey('apiKey');

        // No errors added — blank key is allowed before validation
        self::assertFalse($settings->hasErrors('apiKey'));
    }

    public function testValidateApiKeyDoesNothingWhenKeyIsWhitespaceOnly(): void
    {
        $settings = new Settings();
        $settings->apiKey = '   ';

        $settings->validateApiKey('apiKey');

        self::assertFalse($settings->hasErrors('apiKey'));
    }

    public function testValidateApiKeyAddsErrorWhenApiResponseIndicatesFailure(): void
    {
        $stub = new class extends UserApiService {
            /** @return array<string, mixed> */
            public function getUserInfo(string $apiKey): array
            {
                return ['succeeded' => 0, 'error' => 'Invalid API Key.'];
            }
        };
        Plugin::getInstance()->set('userApi', $stub);

        $settings = new Settings();
        $settings->apiKey = 'wg_bad_key';

        $settings->validateApiKey('apiKey');

        self::assertTrue($settings->hasErrors('apiKey'));
    }

    public function testValidateApiKeyAddsErrorWhenApiThrowsException(): void
    {
        $stub = new class extends UserApiService {
            /** @return array<string, mixed> */
            public function getUserInfo(string $apiKey): array
            {
                throw new \RuntimeException('Network failure');
            }
        };
        Plugin::getInstance()->set('userApi', $stub);

        $settings = new Settings();
        $settings->apiKey = 'wg_bad_key';

        $settings->validateApiKey('apiKey');

        self::assertTrue($settings->hasErrors('apiKey'));
    }
}
