<?php

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

            $currentLanguage = $requestUrlService->getCurrentLanguage();
            $originalLanguage = $languageService->getOriginalLanguage();

            $settings = Plugin::getInstance()->getTypedSettings();
            $apiKey = trim((string) $settings->apiKey);

            $currentExternal = ($currentLanguage instanceof LanguageEntry) ? strtolower(trim($currentLanguage->getExternalCode())) : '';
            $isOnTranslatedPage = (
                $currentLanguage instanceof LanguageEntry
                && $originalLanguage instanceof LanguageEntry
                && $currentLanguage->getInternalCode() !== $originalLanguage->getInternalCode()
            );

            $urls = $requestUrlService->getWeglotUrl()->getAllUrls();

            foreach ($urls as $url) {
                if (($url['excluded'] ?? false) === true) {
                    continue;
                }

                $rawHref = \is_string($url['url']) ? $url['url'] : '';
                if ('' === $rawHref) {
                    continue;
                }

                $language = $url['language'] ?? null;
                if (!$language instanceof LanguageEntry) {
                    continue;
                }

                $href = explode('?', $rawHref, 2)[0];
                if ('' === $href) {
                    continue;
                }

                try {
                    if (
                        $isOnTranslatedPage
                        && $originalLanguage instanceof LanguageEntry
                        && $language->getInternalCode() === $originalLanguage->getInternalCode()
                        && '' !== $apiKey
                        && '' !== $currentExternal
                    ) {
                        $parsed = parse_url($href);
                        $path = \is_array($parsed) ? ($parsed['path'] ?? '') : '';
                        if (\is_string($path) && '' !== $path) {
                            $internalPath = ltrim($path, '/'); // ex: blog-fr
                            $rewritten = Plugin::getInstance()->getSlug()->getInternalPathIfTranslatedSlug(
                                $apiKey,
                                [$currentExternal],   // on force la langue source (celle de la page courante)
                                $currentExternal,
                                $internalPath
                            );

                            if (null !== $rewritten && $rewritten !== $internalPath) {
                                $newPath = '/'.ltrim($rewritten, '/');

                                if (\is_array($parsed) && isset($parsed['scheme'], $parsed['host'])) {
                                    $href = $parsed['scheme'].'://'.$parsed['host']
                                            .(isset($parsed['port']) ? ':'.$parsed['port'] : '')
                                            .$newPath
                                            .(isset($parsed['fragment']) && '' !== $parsed['fragment'] ? '#'.$parsed['fragment'] : '');
                                } else {
                                    $href = $newPath.(\is_array($parsed) && isset($parsed['fragment']) && '' !== $parsed['fragment'] ? '#'.$parsed['fragment'] : '');
                                }
                            }
                        }
                    }
                } catch (\Throwable) {
                    // silent
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
