<?php

namespace weglot\craftweglot\services;

use Craft;
use craft\base\Component;
use craft\helpers\Json;
use craft\web\View;
use weglot\craftweglot\helpers\HelperApi;
use weglot\craftweglot\helpers\HelperSwitcher;
use weglot\craftweglot\Plugin;

class FrontEndScriptsService extends Component
{
    private bool $switcherAssetsInjected = false;


    public function injectWeglotScripts(): void
    {
        $settings = \weglot\craftweglot\Plugin::getInstance()->getSettings();

        if (empty($settings->apiKey)) {
            return;
        }

        $view = Craft::$app->getView();

        $view->registerJsFile(HelperApi::getCdnUrl() . 'weglot.min.js', [
            'position' => View::POS_HEAD,
        ]);

        $apiKey = Plugin::getInstance()->getOption()->getOption('api_key');
        $whiteList = [];
        $weglotConfig = [
            'api_key' => $apiKey,
            'whitelist' => $whiteList,
            'hide_switcher' => 'false',
            'auto_switch' => 'false',
        ];
        $safeJson = Json::htmlEncode($weglotConfig);

        $js = "Weglot.initialize($safeJson);";
        $view->registerJs($js, View::POS_HEAD);
    }

    public function injectSwitcherAssets(bool $forceDefault = true): void
    {
        if ($this->switcherAssetsInjected) {
            return;
        }

        $options = Plugin::getInstance()->getOption()->getOptions();
        $switchers = $options['custom_settings']['switchers'] ?? [];

        $assets = HelperSwitcher::buildSwitcherAssets(
            is_array($switchers) ? $switchers : [],
            $forceDefault
        );

        $view = Craft::$app->getView();

        foreach ($assets['css'] as $css) {
            $url = (string) ($css['url'] ?? '');
            if ($url === '') {
                continue;
            }
            $version = isset($css['version']) && $css['version'] !== '' ? (string) $css['version'] : null;
            if ($version !== null) {
                $url = $this->appendVersion($url, $version);
            }
            $view->registerCssFile($url, [
                'media' => $css['media'] ?? 'all',
            ]);
        }

        foreach ($assets['js'] as $js) {
            $url = (string) ($js['url'] ?? '');
            if ($url === '') {
                continue;
            }
            $view->registerJsFile($url, [
                'position' => View::POS_END,
            ]);
        }

        $this->switcherAssetsInjected = true;
    }

    private function appendVersion(string $url, string $version): string
    {
        if ($version === '') {
            return $url;
        }
        $sep = (str_contains($url, '?')) ? '&' : '?';

        return $url . $sep . 'v=' . rawurlencode($version);
    }
}
