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
        private readonly OptionService $optionService,
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

                    $html = $this->injectAiDisclaimer($html);

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

    /**
     * @param string $html the HTML content to process
     *
     * @return string the HTML with AI disclaimer injected if applicable
     */
    private function injectAiDisclaimer(string $html): string
    {
        $customSettings = $this->optionService->getOption('custom_settings');

        $aiDisclaimerSelector = null;

        if (\is_array($customSettings) && isset($customSettings['ai_disclaimer_selector'])) {
            $aiDisclaimerSelector = $customSettings['ai_disclaimer_selector'];
        }

        if (!\is_string($aiDisclaimerSelector) || '' === trim($aiDisclaimerSelector)) {
            return $html;
        }

        $selector = trim($aiDisclaimerSelector);
        $disclaimerText = 'Translated content on this website may be generated using artificial intelligence. Learn more about AI-generated translations';

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);

        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        $xpathQuery = $this->cssToXPath($selector);

        try {
            $elements = $xpath->query($xpathQuery);

            if ($elements && $elements->length > 0) {
                $targetElement = $elements->item(0);

                $disclaimerNode = $dom->createTextNode($disclaimerText);

                $targetElement->appendChild($disclaimerNode);

                $html = $dom->saveHTML();

                $html = preg_replace('/<\?xml encoding="utf-8" \?>\s*/', '', $html);
            }
        } catch (\Exception) {
            // Silently fail - don't block the site if disclaimer injection fails
        }

        return $html;
    }

    /**
     * Converts a basic CSS selector to XPath.
     *
     * @param string $selector the CSS selector to convert
     *
     * @return string the XPath query
     */
    private function cssToXPath(string $selector): string
    {
        // Handle ID selector
        if (str_starts_with($selector, '#')) {
            $id = substr($selector, 1);

            return "//*[@id='$id']";
        }

        // Handle class selector
        if (str_starts_with($selector, '.')) {
            $class = substr($selector, 1);

            return "//*[contains(concat(' ', normalize-space(@class), ' '), ' $class ')]";
        }

        // Handle attribute selector
        if (preg_match('/\[([^\]=]+)(?:=["\']?([^"\'\]]+)["\']?)?\]/', $selector, $matches)) {
            $attr = $matches[1];
            if (isset($matches[2])) {
                $value = $matches[2];

                return "//*[@$attr='$value']";
            }

            return "//*[@$attr]";
        }

        // Default: element selector
        return "//$selector";
    }
}
