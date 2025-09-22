<?php

namespace weglot\craftweglot\services;

use craft\base\Component;
use weglot\craftweglot\helpers\HelperReplaceUrl;
use weglot\craftweglot\Plugin;

class ReplaceUrlService extends Component
{
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
                    '<html $1lang=$2' . $currentLanguage->getExternalCode() . '$4 weglot-lang=$2' . $currentLanguage->getInternalCode() . '$4',
                    $dom
                );
            } else {
                $dom = preg_replace(
                    '/<html (.*?)?lang=(\"|\')(\S*)(\"|\')/',
                    '<html $1lang=$2' . $currentLanguage->getExternalCode() . '$4',
                    $dom
                );
            }

            $dom = preg_replace(
                '/property="og:locale" content=(\"|\')(\S*)(\"|\')/',
                'property="og:locale" content=$1' . $currentLanguage->getExternalCode() . '$3',
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

    public function modifyLink(string $pattern, string $translatedPage, string $type): string
    {
        preg_match_all($pattern, $translatedPage, $out, PREG_PATTERN_ORDER);

        if (empty($out[0])) {
            return $translatedPage;
        }

        $countOut0 = count($out[0]);
        for ($i = 0; $i < $countOut0; $i++) {
            $sometags = $out[1][$i] ?? '';
            $quote1 = $out[2][$i] ?? '"';
            $currentUrl = $out[3][$i] ?? '';
            $quote2 = $quote1; // la quote fermante est égale à la quote ouvrante (backref \2 dans le regex)
            $sometags2 = $out[4][$i] ?? '';

            if (strlen($currentUrl) >= 1500) { // Limit from WP plugin
                continue;
            }

            $functionName = 'replace' . ucfirst($type);

            if (method_exists(Plugin::getInstance()->getReplaceLinkService(), $functionName)) {
                $translatedPage = Plugin::getInstance()->getReplaceLinkService()->{$functionName}($translatedPage, $currentUrl, $quote1, $quote2, $sometags, $sometags2);
            }
        }
        return $translatedPage;
    }
}
