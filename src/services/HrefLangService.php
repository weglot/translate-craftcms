<?php

declare(strict_types=1);

namespace weglot\craftweglot\services;

use craft\base\Component;
use craft\web\View;
use weglot\craftweglot\Plugin;
use Weglot\Vendor\Weglot\Client\Api\LanguageEntry;

class HrefLangService extends Component
{
    public function generateHrefLangTags(): string
    {
        $render = "\n";

        try {
            $requestUrlService = Plugin::getInstance()->getRequestUrlService();
            $languageService = Plugin::getInstance()->getLanguage();

            $eligible = $requestUrlService->isEligibleUrl($requestUrlService->getFullUrl());
            if ([] === $eligible) {
                return $render;
            }

            $originalLanguage = $languageService->getOriginalLanguage();

            // Rows start from the source-language URL so custom URLs and slugs are applied per
            // language; on a translated page the current URL carries that page's own slugs. The
            // exclusion flags stay those of the requested URL.
            $excluded = [];
            foreach ($requestUrlService->getWeglotUrl()->getAllUrls() as $currentRow) {
                if ($currentRow['language'] instanceof LanguageEntry) {
                    $excluded[$currentRow['language']->getInternalCode()] = true === $currentRow['excluded'];
                }
            }

            $sourceUrl = $requestUrlService->createUrlObject($requestUrlService->getSourceUrl());
            $host = (string) $sourceUrl->getHost();

            foreach ($sourceUrl->getAllUrls() as $url) {
                $language = $url['language'] ?? null;
                if (!$language instanceof LanguageEntry) {
                    continue;
                }

                if ($excluded[$language->getInternalCode()] ?? false) {
                    continue;
                }

                $rawHref = \is_string($url['url']) ? $url['url'] : '';
                if ('' === $rawHref) {
                    continue;
                }

                $href = explode('?', $rawHref, 2)[0];
                if ('' === $href) {
                    continue;
                }

                $isOriginal = $originalLanguage instanceof LanguageEntry
                    && $language->getInternalCode() === $originalLanguage->getInternalCode();
                $prefix = $host.'/'.$language->getExternalCode();
                if (!$isOriginal && str_starts_with($href, $prefix.'/')) {
                    $href = $prefix.$requestUrlService->getPathForLanguage(substr($href, \strlen($prefix)), $language);
                }

                $hreflang = $language->getExternalCode();

                $render .= '<link rel="alternate" href="'.
                           htmlspecialchars($href, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8').
                           '" hreflang="'.
                           htmlspecialchars($hreflang, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8').
                           "\"/>\n";
            }
        } catch (\Throwable $e) {
            \Craft::debug('HrefLangService: '.$e->getMessage(), __METHOD__);
        }

        return $render;
    }

    public function injectHrefLangTags(): void
    {
        $pluginSettings = Plugin::getInstance()->getTypedSettings();
        if ('' === $pluginSettings->apiKey) {
            return;
        }

        $html = $this->generateHrefLangTags();
        if ('' !== trim($html)) {
            \Craft::$app->getView()->registerHtml($html, View::POS_HEAD);
        }
    }
}
