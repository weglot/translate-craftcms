<?php

declare(strict_types=1);

namespace weglot\craftweglot\helpers;

use craft\helpers\App;
use weglot\craftweglot\Plugin;
use weglot\craftweglot\services\OptionService;
use weglot\craftweglot\services\VersionService;
use yii\helpers\Url;

class DashboardHelper
{
    private const DASHBOARD_URL_PROD = 'https://dashboard.weglot.com';
    private ?string $projectSlug = null;
    private ?string $organizationSlug = null;
    private bool $canGenerate = false;
    private readonly bool $isV2;

    public function __construct(private readonly VersionService $versionService, OptionService $optionService)
    {
        // Gated on the configured key rather than the payload's `api_key`, which only
        // V1 project settings carry — V2 exposes `public_key` instead.
        $apiKey = trim(Plugin::getInstance()->getTypedSettings()->apiKey);

        if ('' !== $apiKey) {
            $this->projectSlug = $optionService->getOption('project_slug');
            $this->organizationSlug = $optionService->getOption('organization_slug');
        }

        $this->isV2 = HelperApi::isV2ApiKey($apiKey);

        $this->canGenerate = null !== $this->projectSlug
            && '' !== $this->projectSlug
            && null !== $this->organizationSlug
            && '' !== $this->organizationSlug;
    }

    /**
     * Fetched lazily: this helper is instantiated on every control panel page, and
     * only the dashboard link needs the extra API round trip.
     */
    private function getWorkspaceSlug(): string
    {
        $apiKey = trim(Plugin::getInstance()->getTypedSettings()->apiKey);

        return Plugin::getInstance()->getUserApi()->getWorkspaceSlug($apiKey);
    }

    private function getBaseUrl(): string
    {
        if ('staging' === HelperApi::getEnvironment()) {
            $stagingUrl = App::env('WEGLOT_DASHBOARD_URL_STAGING');

            return \is_string($stagingUrl) ? $stagingUrl : '';
        }

        return self::DASHBOARD_URL_PROD;
    }

    /**
     * V2 dashboards live on the auth domain, under a flatter path than V1: the
     * `/workspaces/` and `/projects/` segments are dropped, and so is the
     * `/translations/` or `/settings/` section prefix.
     */
    private function buildV2Url(string $path): string
    {
        $workspaceSlug = $this->getWorkspaceSlug();

        if ('' === $workspaceSlug || null === $this->projectSlug || '' === $this->projectSlug) {
            return '#';
        }

        return \sprintf(
            '%s/%s/%s/%s',
            HelperApi::getDashboardUrl(HelperApi::VERSION_V2),
            rawurlencode($workspaceSlug),
            rawurlencode($this->projectSlug),
            $path
        );
    }

    public function getEditTranslationsUrl(): string
    {
        if ($this->isV2) {
            return $this->buildV2Url('languages');
        }

        if (!$this->canGenerate) {
            return '#';
        }

        return \sprintf(
            '%s/workspaces/%s/projects/%s/translations/languages/',
            $this->getBaseUrl(),
            $this->organizationSlug,
            $this->projectSlug
        );
    }

    public function isV2Dashboard(): bool
    {
        return $this->isV2;
    }

    /**
     * V2 merges the block and URL exclusion screens into a single page.
     */
    public function getExclusionsUrl(): string
    {
        return $this->buildV2Url('exclusions');
    }

    public function getVisualEditorUrl(): string
    {
        if (!$this->canGenerate) {
            return '#';
        }

        $launchUrl = Url::home();

        return \sprintf(
            '%s/workspaces/%s/projects/%s/translations/visual-editor/launch?url=%s&mode=translations',
            $this->getBaseUrl(),
            $this->organizationSlug,
            $this->projectSlug,
            urlencode($launchUrl)
        );
    }

    public function getSwitcherEditor(): string
    {
        if ($this->isV2) {
            return $this->buildV2Url('visual-editor?mode=switchers');
        }

        if (!$this->canGenerate) {
            return '#';
        }

        $launchUrl = Url::home();

        return \sprintf(
            '%s/workspaces/%s/projects/%s/settings/language-switcher/editor?url=%s',
            $this->getBaseUrl(),
            $this->organizationSlug,
            $this->projectSlug,
            urlencode($launchUrl)
        );
    }

    public function getLanguageModel(): string
    {
        if ($this->isV2) {
            return $this->buildV2Url('language-model');
        }

        if (!$this->canGenerate) {
            return '#';
        }

        $launchUrl = Url::home();

        return \sprintf(
            '%s/workspaces/%s/projects/%s/settings/language-model',
            $this->getBaseUrl(),
            $this->organizationSlug,
            $this->projectSlug,
            urlencode($launchUrl)
        );
    }

    public function getManageUrlExclusionsUrl(): string
    {
        if (!$this->canGenerate) {
            return '#';
        }

        return \sprintf(
            '%s/workspaces/%s/projects/%s/settings/exclusions#excluded-urls',
            $this->getBaseUrl(),
            $this->organizationSlug,
            $this->projectSlug
        );
    }

    public function getExcludeBlocksUrl(): string
    {
        if (!$this->canGenerate) {
            return '#';
        }

        return \sprintf(
            '%s/workspaces/%s/projects/%s/settings/exclusions#excluded-blocks',
            $this->getBaseUrl(),
            $this->organizationSlug,
            $this->projectSlug
        );
    }

    public function getRegistrationUrl(): string
    {
        return HelperApi::getRegisterUrl(
            $this->versionService->getRandomOnboardingVersion(),
            \Craft::$app->getSites()->getPrimarySite()->getBaseUrl() ?? ''
        );
    }
}
