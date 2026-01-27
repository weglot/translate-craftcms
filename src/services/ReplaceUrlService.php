<?php

namespace weglot\craftweglot\services;

use craft\base\Component;
use weglot\craftweglot\helpers\HelperReplaceUrl;
use weglot\craftweglot\Plugin;

class ReplaceUrlService extends Component
{
    /**
     * @param string $dom the DOM content as a string to be modified
     *
     * @return string the modified DOM string with replaced links and updated attributes
     */
    public function replaceLinkInDom(string $dom): string
    {
        $data = HelperReplaceUrl::getReplaceModifyLink();

        foreach ($data as $key => $value) {
            $dom = $this->modifyLink($value, $dom, $key);
        }

        $currentLanguage = Plugin::getInstance()->getRequestUrlService()->getCurrentLanguage();
        $currentUrl = Plugin::getInstance()->getRequestUrlService()->getWeglotUrl();

        if ($currentUrl->getForLanguage($currentLanguage, false)) {
            if ($currentLanguage->getExternalCode() !== $currentLanguage->getInternalCode()) {
                $dom = preg_replace(
                    '/<html (.*?)?lang=(\"|\')(\S*)(\"|\')/',
                    '<html $1lang=$2'.$currentLanguage->getExternalCode().'$4 weglot-lang=$2'.$currentLanguage->getInternalCode().'$4',
                    $dom
                );
            } else {
                $dom = preg_replace(
                    '/<html (.*?)?lang=(\"|\')(\S*)(\"|\')/',
                    '<html $1lang=$2'.$currentLanguage->getExternalCode().'$4',
                    $dom
                );
            }

            $dom = preg_replace(
                '/property="og:locale" content=(\"|\')(\S*)(\"|\')/',
                'property="og:locale" content=$1'.$currentLanguage->getExternalCode().'$3',
                (string) $dom
            );
        } else {
            $dom = preg_replace(
                '/<html (.*?)?lang=(\"|\')(\S*)(\"|\')/',
                '<html $1lang=$2$3$4 data-excluded-page="true"',
                $dom
            );
        }

        return $dom;
    }

    /**
     * @param string $pattern        the regular expression pattern to find links in the translated page
     * @param string $translatedPage the content of the page where links will be modified
     * @param string $type           The type of link modification to be performed (e.g., 'a', 'datalink', 'form', etc.).
     *
     * @return string the modified translated page with links processed based on the specified type
     */
    public function modifyLink(string $pattern, string $translatedPage, string $type): string
    {
        preg_match_all($pattern, $translatedPage, $out, \PREG_PATTERN_ORDER);

        if ([] === $out[0]) {
            return $translatedPage;
        }

        $countOut0 = \count($out[0]);
        $replaceLinkService = Plugin::getInstance()->getReplaceLinkService();

        for ($i = 0; $i < $countOut0; ++$i) {
            $sometags = $out[1][$i] ?? '';
            $quote1 = $out[2][$i] ?? '"';
            $currentUrl = $out[3][$i] ?? '';
            $quote2 = $quote1; // la quote fermante est égale à la quote ouvrante (backref \2 dans le regex)
            $sometags2 = $out[4][$i] ?? '';

            if (\strlen($currentUrl) >= 1500) { // Limit from WP plugin
                continue;
            }

            if (preg_match('#^/?(?:index\.php/)?actions/#i', $currentUrl)) {
                continue;
            }

            switch ($type) {
                case 'a':
                    $translatedPage = $replaceLinkService->replaceA($translatedPage, $currentUrl, $quote1, $quote2, $sometags, $sometags2);
                    break;
                case 'datalink':
                    $translatedPage = $replaceLinkService->replaceDatalink($translatedPage, $currentUrl, $quote1, $quote2, $sometags);
                    break;
                case 'dataurl':
                    $translatedPage = $replaceLinkService->replaceDataurl($translatedPage, $currentUrl, $quote1, $quote2, $sometags);
                    break;
                case 'datacart':
                    $translatedPage = $replaceLinkService->replaceDatacart($translatedPage, $currentUrl, $quote1, $quote2, $sometags);
                    break;
                case 'form':
                    $translatedPage = $replaceLinkService->replaceForm($translatedPage, $currentUrl, $quote1, $quote2, $sometags);
                    break;
                case 'canonical':
                    $translatedPage = $replaceLinkService->replaceCanonical($translatedPage, $currentUrl, $quote1, $quote2, $sometags);
                    break;
                case 'amp':
                    $translatedPage = $replaceLinkService->replaceAmp($translatedPage, $currentUrl, $quote1, $quote2, $sometags);
                    break;
                case 'meta':
                    $translatedPage = $replaceLinkService->replaceMeta($translatedPage, $currentUrl, $quote1, $quote2, $sometags);
                    break;
                case 'next':
                    $translatedPage = $replaceLinkService->replaceNext($translatedPage, $currentUrl, $quote1, $quote2, $sometags);
                    break;
                case 'prev':
                    $translatedPage = $replaceLinkService->replacePrev($translatedPage, $currentUrl, $quote1, $quote2, $sometags);
                    break;
            }
        }

        return $translatedPage;
    }
}
