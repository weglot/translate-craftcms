<?php

namespace weglot\craftweglot\services;

use craft\base\Component;
use craft\helpers\Json;
use craft\web\View;
use weglot\craftweglot\helpers\HelperApi;
use weglot\craftweglot\Plugin;

class PageViewsService extends Component
{
    private bool $injected = false;

    public function injectPageViewsScript(): void
    {
        if ($this->injected) {
            return;
        }

        $app = \Craft::$app;
        $request = $app->getRequest();

        if (!$request->getIsSiteRequest() || $request->getIsAjax()) {
            return;
        }

        $options = Plugin::getInstance()->getOption()->getOptions();
        if (!isset($options['page_views_enabled']) || true !== $options['page_views_enabled']) {
            return;
        }

        $apiKey = (string) Plugin::getInstance()->getOption()->getOption('api_key');
        if ('' === trim($apiKey)) {
            return;
        }

        $view = $app->getView();

        $endpoint = HelperApi::getApiUrl().'/pageviews?api_key='.rawurlencode($apiKey);
        $safeEndpoint = Json::htmlEncode($endpoint);

        $js = <<<JS
                    (function(){
                        try {
                            var url = {$safeEndpoint};
                            var payload = {
                                url: (location.protocol + '//' + location.host + location.pathname),
                                language: document.documentElement.getAttribute('lang'),
                                browser_language: (navigator.language || navigator.userLanguage)
                            };
                            var body = JSON.stringify(payload);

                            if (navigator.sendBeacon) {
                                try {
                                    var queued = navigator.sendBeacon(url, body);
                                    if (queued) {
                                        return;
                                    }
                                } catch (e) {
                                    // silent (fallbacks ci-dessous)
                                }
                            }

                            if (window.fetch) {
                                fetch(url, {
                                    method: 'POST',
                                    headers: {},
                                    body: body,
                                    keepalive: true
                                }).catch(function(){/* silent */});
                            } else {
                                var xhr = new XMLHttpRequest();
                                xhr.open('POST', url, true);
                                try { xhr.setRequestHeader('Content-Type', 'application/json'); } catch(e) {}
                                try { xhr.send(body); } catch(e) {}
                            }
                        } catch (e) {
                            // silent
                        }
                    })();
                JS;

        $view->registerJs($js, View::POS_END);

        $this->injected = true;
    }
}
