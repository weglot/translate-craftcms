<?php

namespace weglot\craftweglot\helpers;

use craft\helpers\App;
use weglot\craftweglot\services\OptionService;
use yii\helpers\Url;

class DashboardHelper
{
    private const DASHBOARD_URL_PROD = 'https://dashboard.weglot.com';
    private ?string $projectSlug = null;
    private ?string $organizationSlug = null;
    private bool $canGenerate = false;

    public function __construct(OptionService $optionService)
    {
        $apiKey = $optionService->getOption('api_key');

        if ('' !== $apiKey) {
            $this->projectSlug = $optionService->getOption('project_slug');
            $this->organizationSlug = $optionService->getOption('organization_slug');
        }

        $this->canGenerate = null !== $this->projectSlug
            && '' !== $this->projectSlug
            && null !== $this->organizationSlug
            && '' !== $this->organizationSlug;
    }

    private function getBaseUrl(): string
    {
        if ('staging' === HelperApi::getEnvironment()) {
            $stagingUrl = App::env('WEGLOT_DASHBOARD_URL_STAGING');

            return \is_string($stagingUrl) ? $stagingUrl : '';
        }

        return self::DASHBOARD_URL_PROD;
    }

    public function getEditTranslationsUrl(): string
    {
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

    public function getSwiterEditor(): string
    {
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
        return $this->getBaseUrl().'/register?project=craft';
    }
}
