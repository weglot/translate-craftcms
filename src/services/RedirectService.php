<?php

namespace weglot\craftweglot\services;

use craft\base\Component;
use weglot\craftweglot\Plugin;
use Weglot\Vendor\Weglot\Client\Api\LanguageEntry;
use yii\web\Cookie;

class RedirectService extends Component
{
    /**
     * Return an array of navigator languages (lowercased), parsed from Accept-Language
     * with Cloudflare fallback (HTTP_CF_IPCOUNTRY) if missing.
     *
     * @return array<int,string>
     */
    public function getNavigatorLanguages(): array
    {
        $navigatorLanguages = [];
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $navigatorLanguages = explode(',', trim((string) $_SERVER['HTTP_ACCEPT_LANGUAGE']));
            foreach ($navigatorLanguages as &$navigatorLanguage) {
                $pos = strpos($navigatorLanguage, ';');
                if (false !== $pos) {
                    $navigatorLanguage = substr($navigatorLanguage, 0, $pos);
                }
                $navigatorLanguage = strtolower(trim($navigatorLanguage));
            }
        } elseif (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) { // Cloudflare compatibility
            $navigatorLanguages = [strtolower((string) $_SERVER['HTTP_CF_IPCOUNTRY'])];
        }

        return array_values(array_filter($navigatorLanguages));
    }

    /**
     * Normalize certain navigator language codes to match project expectations.
     */
    private function languageException(string $navigatorLanguage): string
    {
        $exceptions = [
            [
                'code' => 'no',
                'detect' => '/^(nn|nb)(-[a-z]+)?$/i',
            ],
            [
                'code' => 'zh',
                'detect' => '/^zh(-hans(-\w{2})?)?(-(cn|sg))?$/i',
            ],
            [
                'code' => 'zh-tw',
                'detect' => '/^zh-(hant)?-?(tw|hk|mo)?$/i',
            ],
        ];

        foreach ($exceptions as $exception) {
            if (preg_match($exception['detect'], $navigatorLanguage)) {
                return $exception['code'];
            }
        }

        return $navigatorLanguage;
    }

    /**
     * Determine the best available language code (external code) from navigator languages
     * and available languages (external codes).
     *
     * @param array<int,string> $navigatorLanguages
     * @param array<int,string> $availableLanguagesExternal
     *
     * @return string|null External code
     */
    public function getBestAvailableLanguage(array $navigatorLanguages, array $availableLanguagesExternal): ?string
    {
        $original = Plugin::getInstance()->getLanguage()->getOriginalLanguage();
        if ($original instanceof LanguageEntry) {
            $availableLanguagesExternal[] = $original->getExternalCode();
        }

        $availableLanguagesExternal = array_values(array_unique(array_map('strtolower', $availableLanguagesExternal)));

        if ([] !== $navigatorLanguages) {
            foreach ($navigatorLanguages as $navigatorLanguage) {
                $nav = strtolower($navigatorLanguage);
                // exact match
                if (\in_array($nav, $availableLanguagesExternal, true)) {
                    return $nav;
                }
                $normalized = strtolower($this->languageException($nav));
                if (\in_array($normalized, $availableLanguagesExternal, true)) {
                    return $normalized;
                }
                $primary = substr($nav, 0, 2);
                if (\in_array($primary, $availableLanguagesExternal, true)) {
                    return $primary;
                }
                foreach ($availableLanguagesExternal as $destination) {
                    if (substr($nav, 0, 2) === substr($destination, 0, 2)) {
                        return $destination;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Perform automatic redirection based on navigator languages and project languages.
     */
    public function autoRedirect(): void
    {
        // Respect user choice not to redirect
        $cookies = \Craft::$app->getRequest()->getCookies();
        $chooseOriginal = $cookies->getValue('WG_CHOOSE_ORIGINAL');
        if (\is_string($chooseOriginal) && 'true' === $chooseOriginal) {
            return;
        }

        // No header available: nothing to do
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && !isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            return;
        }

        $plugin = Plugin::getInstance();
        $languageService = $plugin->getLanguage();
        $requestUrlService = $plugin->getRequestUrlService();
        $optionService = $plugin->getOption();

        $navigatorLanguages = $this->getNavigatorLanguages();

        // Build available external codes from destination languages
        $destinationEntries = $languageService->getDestinationLanguages();
        $destinationExternal = [];
        foreach ($destinationEntries as $entry) {
            $code = $entry->getExternalCode();
            if ('' !== $code) {
                $destinationExternal[] = strtolower($code);
            }
        }

        $bestExternal = $this->getBestAvailableLanguage($navigatorLanguages, $destinationExternal);
        $bestLanguage = null !== $bestExternal ? $languageService->getLanguageFromExternal($bestExternal) : null;

        $originalLanguage = $languageService->getOriginalLanguage();
        $currentLanguage = $requestUrlService->getCurrentLanguage();

        if (null !== $bestLanguage && null !== $originalLanguage && null !== $currentLanguage) {
            if ($bestLanguage->getInternalCode() !== $originalLanguage->getInternalCode()
                && $originalLanguage->getInternalCode() === $currentLanguage->getInternalCode()
            ) {
                // Ensure URL is not excluded and get final URL
                $weglotUrl = $requestUrlService->getWeglotUrl();
                if (!$weglotUrl->getForLanguage($bestLanguage, false)) {
                    return;
                }
                $url = $weglotUrl->getForLanguage($bestLanguage, true);
                if (\is_string($url) && '' !== $url) {
                    \Craft::$app->getResponse()->getHeaders()->set('Vary', 'Accept-Language');
                    \Craft::$app->getResponse()->redirect($url, 302)->send();
                    exit;
                }
            }
        }

        // Fallback if no best language or user navigator doesn't include original
        $fallbackInternal = $optionService->getOption('auto_switch_fallback');
        if (null !== $fallbackInternal && '' !== $fallbackInternal) {
            $fallbackLanguage = $languageService->getLanguageFromInternal((string) $fallbackInternal);
            if ($fallbackLanguage instanceof LanguageEntry) {
                $origExternal = $originalLanguage instanceof LanguageEntry ? $originalLanguage->getExternalCode() : '';
                $origInNavigator = \in_array(strtolower($origExternal), array_map('strtolower', $navigatorLanguages), true);

                if (null === $bestLanguage || !$origInNavigator) {
                    $weglotUrl = $requestUrlService->getWeglotUrl();
                    if (!$weglotUrl->getForLanguage($fallbackLanguage, false)) {
                        return;
                    }
                    $url = $weglotUrl->getForLanguage($fallbackLanguage, true);
                    if (\is_string($url) && '' !== $url) {
                        \Craft::$app->getResponse()->getHeaders()->set('Vary', 'Accept-Language');
                        \Craft::$app->getResponse()->redirect($url, 302)->send();
                        exit;
                    }
                }
            }
        }
    }

    /**
     * Handle user opt-in/opt-out of redirection via wg-choose-original query param.
     */
    public function verifyNoRedirect(): void
    {
        $req = \Craft::$app->getRequest();
        $val = $req->getQueryParam('wg-choose-original');
        if (null === $val) {
            return;
        }

        $resp = \Craft::$app->getResponse();
        if ('true' === $val) {
            $cookie = new Cookie([
                'name' => 'WG_CHOOSE_ORIGINAL',
                'value' => 'true',
                'expire' => time() + 86400 * 2,
                'path' => '/',
            ]);
            $resp->getCookies()->add($cookie);
        } elseif ('false' === $val) {
            $cookie = new Cookie([
                'name' => 'WG_CHOOSE_ORIGINAL',
                'value' => '',
                'expire' => time() - 3600,
                'path' => '/',
            ]);
            $resp->getCookies()->add($cookie);
        } else {
            return;
        }

        $url = $req->getAbsoluteUrl();
        $url = preg_replace('/([&?])wg-choose-original=[^&]*(&|$)/', '$1', (string) $url);
        if (\is_string($url)) {
            $url = rtrim($url, '&?');
            $url = preg_replace('/\?$/', '', $url);
        }

        $resp->redirect((string) $url, 302)->send();
        exit;
    }
}
