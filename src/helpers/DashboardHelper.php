<?php

namespace weglot\craftweglot\helpers;

use weglot\craftweglot\services\OptionService;
use yii\helpers\Url;

/**
 * Class DashboardHelper
 * @package weglot\craftweglot\helpers
 */
class DashboardHelper
{
    private const DASHBOARD_URL_PROD = 'https://dashboard.weglot.com';
    private const DASHBOARD_URL_STAGING = 'https://dashboard.weglot.dev';

    private ?string $projectSlug = null;
    private ?string $organizationSlug = null;
    private bool $canGenerate = false;

    public function __construct(OptionService $optionService)
    {
        $apiKey = $optionService->getOption('api_key');

        if (!empty($apiKey)) {
            $this->projectSlug = $optionService->getOption('project_slug');
            $this->organizationSlug = $optionService->getOption('organization_slug');
        }

        $this->canGenerate = $this->projectSlug !== null && $this->projectSlug !== '' && $this->projectSlug !== '0' && ($this->organizationSlug !== null && $this->organizationSlug !== '' && $this->organizationSlug !== '0');
    }

    private function getBaseUrl(): string
    {
        return HelperApi::getEnvironment() === 'staging'
            ? self::DASHBOARD_URL_STAGING
            : self::DASHBOARD_URL_PROD;
    }

    public function getEditTranslationsUrl(): string
    {
        if (!$this->canGenerate) {
            return '#';
        }

        return sprintf(
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

        return sprintf(
            '%s/workspaces/%s/projects/%s/translations/visual-editor/launch?url=%s&mode=translations',
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

        return sprintf(
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

        return sprintf(
            '%s/workspaces/%s/projects/%s/settings/exclusions#excluded-blocks',
            $this->getBaseUrl(),
            $this->organizationSlug,
            $this->projectSlug
        );
    }

    /**
  * Returns the registration URL for new users.
  */
    public function getRegistrationUrl(): string
    {
        return $this->getBaseUrl() . '/register?project=craft';
    }
}
