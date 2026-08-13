<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\helpers;

use PHPUnit\Framework\TestCase;
use weglot\craftweglot\helpers\DashboardHelper;
use weglot\craftweglot\Plugin;
use weglot\craftweglot\services\OptionService;
use weglot\craftweglot\services\UserApiService;
use weglot\craftweglot\services\VersionService;

final class DashboardHelperTest extends TestCase
{
    private string $savedApiKey = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->savedApiKey = Plugin::getInstance()->getTypedSettings()->apiKey;
        \Craft::$app->getCache()->delete(UserApiService::workspaceCacheKey('sk_abc123'));
        // The service memoizes the slug for its lifetime, and the component is shared
        // across the whole suite — hand each test a fresh one.
        Plugin::getInstance()->set('userApi', new UserApiService());
    }

    protected function tearDown(): void
    {
        Plugin::getInstance()->getTypedSettings()->apiKey = $this->savedApiKey;
        \Craft::$app->getCache()->delete(UserApiService::workspaceCacheKey('sk_abc123'));
        parent::tearDown();
    }

    /**
     * @param array<string,mixed> $options
     */
    private function makeHelper(string $apiKey, array $options): DashboardHelper
    {
        Plugin::getInstance()->getTypedSettings()->apiKey = $apiKey;

        $optionService = new class($options) extends OptionService {
            /** @param array<string,mixed> $options */
            public function __construct(private readonly array $options)
            {
                parent::__construct();
            }

            public function getOption(string $key): string|array|int|bool|null
            {
                return $this->options[$key] ?? null;
            }
        };

        return new DashboardHelper(new VersionService(), $optionService);
    }

    public function testV2BuildsTheFlatDashboardUrlFromTheWorkspaceSlug(): void
    {
        \Craft::$app->getCache()->set(UserApiService::workspaceCacheKey('sk_abc123'), 'my-workspace');

        $helper = $this->makeHelper('sk_abc123', ['project_slug' => 'my-project']);

        self::assertSame(
            'https://auth.weglot.com/my-workspace/my-project/languages',
            $helper->getEditTranslationsUrl()
        );
    }

    /**
     * V2 project settings expose `public_key`, never `api_key`; reading the project
     * slug must not depend on that absent field.
     */
    public function testV2UrlIsBuiltEvenWhenTheOptionsCarryNoApiKey(): void
    {
        \Craft::$app->getCache()->set(UserApiService::workspaceCacheKey('sk_abc123'), 'my-workspace');

        $helper = $this->makeHelper('sk_abc123', [
            'api_key' => '',
            'public_key' => 'wg_public',
            'project_slug' => 'my-project',
        ]);

        self::assertStringEndsWith('/my-workspace/my-project/languages', $helper->getEditTranslationsUrl());
    }

    public function testV2FallsBackToPlaceholderWithoutAWorkspaceSlug(): void
    {
        \Craft::$app->getCache()->set(UserApiService::workspaceCacheKey('sk_abc123'), '');

        $helper = $this->makeHelper('sk_abc123', ['project_slug' => 'my-project']);

        self::assertSame('#', $helper->getEditTranslationsUrl());
    }

    public function testV1KeepsTheNestedDashboardUrl(): void
    {
        $helper = $this->makeHelper('wg_abc123', [
            'project_slug' => 'my-project',
            'organization_slug' => 'my-org',
        ]);

        self::assertSame(
            'https://dashboard.weglot.com/workspaces/my-org/projects/my-project/translations/languages/',
            $helper->getEditTranslationsUrl()
        );
    }

    public function testV2DashboardIsOnlyReportedForV2Keys(): void
    {
        self::assertFalse($this->makeHelper('wg_abc123', [])->isV2Dashboard());
        self::assertTrue($this->makeHelper('sk_abc123', [])->isV2Dashboard());
    }

    /**
     * Suffixes mirror the WordPress V2 quick links (templates/admin/v2/home.php).
     */
    public function testV2QuickLinkSuffixes(): void
    {
        \Craft::$app->getCache()->set(UserApiService::workspaceCacheKey('sk_abc123'), 'my-workspace');

        $helper = $this->makeHelper('sk_abc123', ['project_slug' => 'my-project']);
        $base = 'https://auth.weglot.com/my-workspace/my-project/';

        self::assertSame($base.'languages', $helper->getEditTranslationsUrl());
        self::assertSame($base.'visual-editor?mode=switchers', $helper->getSwitcherEditor());
        self::assertSame($base.'language-model', $helper->getLanguageModel());
        self::assertSame($base.'exclusions', $helper->getExclusionsUrl());
    }
}
