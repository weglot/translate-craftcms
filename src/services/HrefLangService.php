<?php

namespace weglot\craftweglot\services;

use Craft;
use craft\base\Component;
use craft\web\View;
use weglot\craftweglot\Plugin;

class HrefLangService extends Component
{
    public function generateHrefLangTags(): string
    {
        $render = "\n";

        try {
            $requestUrlService = Plugin::getInstance()->getRequestUrlService();

            $eligible = $requestUrlService->isEligibleUrl($requestUrlService->getFullUrl());
            if (empty($eligible)) {
                return $render;
            }

            $urls = $requestUrlService->getWeglotUrl()->getAllUrls();

            foreach ($urls as $url) {
                if (!empty($url['excluded'])) {
                    continue;
                }

                $rawHref = is_string($url['url']) ? $url['url'] : '';
                if ($rawHref === '') {
                    continue;
                }
                $href = explode('?', $rawHref, 2)[0];
                if ($href === '') {
                    continue;
                }


                $language = $url['language'] ?? null;
                if (!$language instanceof \Weglot\Client\Api\LanguageEntry) {
                    continue;
                }

                $hreflang = $url['language']?->getExternalCode();

                $render .= '<link rel="alternate" href="' .
                           htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                           '" hreflang="' .
                           htmlspecialchars((string) $hreflang, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                           "\"/>\n";
            }
        } catch (\Throwable $e) {
            Craft::debug('HrefLangService: ' . $e->getMessage(), __METHOD__);
        }

        return $render;
    }

    public function injectHrefLangTags(): void
    {
        $html = $this->generateHrefLangTags();
        if (trim($html) !== '') {
            Craft::$app->getView()->registerHtml($html, View::POS_HEAD);
        }
    }
}
