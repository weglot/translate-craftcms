<?php

namespace weglot\craftweglot\services;

use craft\base\Component;
use craft\helpers\Json;
use craft\web\View;
use weglot\craftweglot\helpers\HelperApi;
use weglot\craftweglot\helpers\HelperSwitcher;
use weglot\craftweglot\Plugin;

class FrontEndScriptsService extends Component
{
    private bool $switcherAssetsInjected = false;
    private bool $weglotScriptsInjected = false;
    private bool $algoliaScriptInjected = false;

    /**
     * @param array<string, mixed> $extraConfig
     */
    public function injectWeglotScripts(array $extraConfig = []): void
    {
        if ($this->weglotScriptsInjected) {
            return;
        }

        $settings = Plugin::getInstance()->getTypedSettings();

        if ('' === $settings->apiKey) {
            return;
        }

        $apiKey = Plugin::getInstance()->getOption()->getPublicApiKey();
        if ('' === $apiKey) {
            return;
        }

        $view = \Craft::$app->getView();

        $view->registerJsFile(HelperApi::getCdnUrl().'weglot.min.js', [
            'position' => View::POS_HEAD,
        ]);

        $weglotConfig = [
            'api_key' => $apiKey,
            'whitelist' => [],
            'hide_switcher' => 'true',
            'auto_switch' => 'false',
        ];

        if ([] !== $extraConfig) {
            $weglotConfig = array_merge($weglotConfig, $extraConfig);
        }

        $safeJson = Json::htmlEncode($weglotConfig);
        $js = "Weglot.initialize($safeJson);";
        $view->registerJs($js, View::POS_HEAD);

        $this->weglotScriptsInjected = true;
    }

    /**
     * @param bool $forceDefault whether to enforce default settings for switcher assets
     */
    public function injectSwitcherAssets(bool $forceDefault = true): void
    {
        if ($this->switcherAssetsInjected) {
            return;
        }

        $options = Plugin::getInstance()->getOption()->getOptions();
        $switchers = $options['custom_settings']['switchers'] ?? [];

        $assets = HelperSwitcher::buildSwitcherAssets(
            \is_array($switchers) ? $switchers : [],
            $forceDefault
        );

        $view = \Craft::$app->getView();

        foreach ($assets['css'] as $css) {
            $url = (string) ($css['url'] ?? '');
            if ('' === $url) {
                continue;
            }
            $version = isset($css['version']) && '' !== $css['version'] ? (string) $css['version'] : null;
            if (null !== $version) {
                $url = $this->appendVersion($url, $version);
            }
            $view->registerCssFile($url, [
                'media' => $css['media'] ?? 'all',
            ]);
        }

        foreach ($assets['js'] as $js) {
            $url = (string) ($js['url'] ?? '');
            if ('' === $url) {
                continue;
            }
            $view->registerJsFile($url, [
                'position' => View::POS_END,
            ]);
        }

        $this->switcherAssetsInjected = true;
    }

    /**
     * Injects Algolia integration script if enabled and on a translated language.
     */
    public function injectAlgoliaScript(): void
    {
        if ($this->algoliaScriptInjected) {
            return;
        }

        $settings = Plugin::getInstance()->getTypedSettings();

        // Only inject if Algolia integration is enabled
        if (!$settings->enableAlgolia) {
            return;
        }

        // Only inject on translated languages (not on original language)
        $currentLanguage = Plugin::getInstance()->getRequestUrlService()->getCurrentLanguage();
        $originalLanguage = Plugin::getInstance()->getLanguage()->getOriginalLanguage();

        if (null === $currentLanguage || null === $originalLanguage) {
            return;
        }

        if ($currentLanguage->getInternalCode() === $originalLanguage->getInternalCode()) {
            return;
        }

        $view = \Craft::$app->getView();

        // Inject weglotData object for use in algolia.js
        $apiKeyPublic = Plugin::getInstance()->getOption()->getPublicApiKey();
        $weglotData = [
            'api_key' => $apiKeyPublic,
            'original_language' => $originalLanguage->getInternalCode(),
            'current_language' => $currentLanguage->getInternalCode(),
            'api_url' => HelperApi::getApiUrl(),
        ];

        $weglotDataJson = Json::htmlEncode($weglotData);
        $view->registerJs("window.weglotData = {$weglotDataJson};", View::POS_HEAD);

        // Get the URL to the xhook and algolia.js files
        $assetManager = $view->getAssetManager();
        $bundle = $assetManager->getBundle('weglot\\craftweglot\\resources\\AdminAsset');
        $baseUrl = $bundle->baseUrl ?? '';

        if ('' !== $baseUrl) {
            // Load xhook library (user has explicitly enabled Algolia integration)
            $view->registerJsFile($baseUrl . '/vendor/xhook.min.js', [
                'position' => View::POS_END,
            ]);

            $view->registerJsFile($baseUrl . '/js/algolia.js', [
                'position' => View::POS_END,
            ]);
        }

        $this->algoliaScriptInjected = true;
    }

    private function appendVersion(string $url, string $version): string
    {
        if ('' === $version) {
            return $url;
        }
        $sep = (str_contains($url, '?')) ? '&' : '?';

        return $url.$sep.'v='.rawurlencode($version);
    }
}
