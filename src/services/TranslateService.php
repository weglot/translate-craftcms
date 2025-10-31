<?php

namespace weglot\craftweglot\services;

use craft\base\Component;
use Weglot\Vendor\Weglot\Client\Api\Exception\ApiError;

class TranslateService extends Component
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly LanguageService $languageService,
        private readonly RequestUrlService $requestUrlService,
        private readonly ParserService $parserService,
        private readonly ReplaceUrlService $replaceUrlService,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    private function isJson(string $string): bool
    {
        if (
            !str_starts_with($string, '[')
            && !str_starts_with($string, '{')
        ) {
            return false;
        }

        json_decode($string);

        return \JSON_ERROR_NONE === json_last_error();
    }

    private function isXml(string $string): bool
    {
        if (!str_contains($string, '<?xml')) {
            return false;
        }

        libxml_use_internal_errors(true);
        simplexml_load_string($string);
        $errors = libxml_get_errors();
        libxml_clear_errors();

        return [] === $errors;
    }

    private function getContentType(string $content): string
    {
        if ($this->isJson($content)) {
            return 'json';
        }

        if ($this->isXml($content)) {
            return 'xml';
        }

        return 'html';
    }

    public function processResponse(string $html): string
    {
        if (\in_array(trim($html), ['', '0'], true)) {
            return $html;
        }

        $originalLanguage = $this->languageService->getOriginalLanguage();
        $currentLanguage = $this->requestUrlService->getCurrentLanguage();
        $type = $this->getContentType($html);

        if (!$this->requestUrlService->getWeglotUrl()->getForLanguage($currentLanguage, false)
             || $originalLanguage->getInternalCode() === $currentLanguage->getInternalCode()) {
            if ('xml' === $type || 'json' === $type) {
                return $html;
            }

            return $this->weglotRenderDom($html);
        }

        $parser = $this->parserService->getParser();

        try {
            switch ($type) {
                case 'json':
                case 'xml':
                    // TODO: Integrate URL replacement logic for XML.
                    return $parser->translate($html, $originalLanguage->getInternalCode(), $currentLanguage->getInternalCode());
                case 'html':
                    // TODO: Manage filters for attribute escaping (HTML, Vue.js).
                    $translatedContent = $parser->translate($html, $originalLanguage->getInternalCode(), $currentLanguage->getInternalCode());

                    // TODO: Integrate URL proxying and other end-processing.
                    return $this->weglotRenderDom($translatedContent);
                default:
                    // TODO: Implement a Craft event for custom content types.
                    return $html;
            }
        } catch (ApiError $e) {
            if ('json' !== $type) {
                $html .= '<!--Weglot error API : '.$this->removeComments($e->getMessage()).'-->';
            }

            return $html;
        } catch (\Exception $e) {
            if ('json' !== $type) {
                $html .= '<!--Weglot error : '.$this->removeComments($e->getMessage()).'-->';
            }

            return $html;
        }
    }

    public function weglotRenderDom(string $html): string
    {
        $originalLanguage = $this->languageService->getOriginalLanguage();
        $currentLanguage = $this->requestUrlService->getCurrentLanguage();

        if ($originalLanguage->getInternalCode() !== $currentLanguage->getInternalCode()) {
            $html = $this->replaceUrlService->replaceLinkInDom($html);
        }

        $html = preg_replace('/<link\b[^>]*\brel=(?:"|\')?canonical(?:"|\')?[^>]*>/i', '', $html);
        $weglotUrl = $this->requestUrlService->getWeglotUrl();

        $canonicalUrl = $weglotUrl->getForLanguage($currentLanguage, false);
        if (!$canonicalUrl) {
            $canonicalUrl = $weglotUrl->getForLanguage($originalLanguage, true);
        }
        if (\is_string($canonicalUrl) && '' !== $canonicalUrl) {
            $canonicalTag = '<link rel="canonical" href="'.htmlspecialchars($canonicalUrl, \ENT_QUOTES, 'UTF-8').'" />';

            $replaced = 0;
            $html = preg_replace('/<\/head>/i', $canonicalTag."\n</head>", (string) $html, 1, $replaced);
            if (0 === $replaced) {
                $html = $canonicalTag."\n".$html;
            }
        }

        return preg_replace_callback(
            '/<html\b([^>]*)>/i',
            static function (array $m): string {
                $attrs = $m[1];
                if (preg_match('/\btranslate\s*=\s*(["\'])(?:no|yes)\1/i', $attrs)) {
                    return '<html'.$attrs.'>';
                }

                return '<html'.$attrs.' translate="no">';
            },
            $html,
            1
        );
    }

    private function removeComments(string $html): string
    {
        $result = preg_replace('/<!--.*?-->/s', '', $html);

        return $result ?? $html;
    }
}
