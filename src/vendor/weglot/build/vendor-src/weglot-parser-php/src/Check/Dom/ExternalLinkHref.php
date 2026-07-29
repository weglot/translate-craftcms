<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Parser\Definitions\Enum\WordType;
class ExternalLinkHref extends AbstractDomChecker
{
    public const DOM = 'a';
    public const PROPERTY = 'href';
    public const WORD_TYPE = WordType::EXTERNAL_LINK;
    protected function check()
    {
        $currentUrl = $this->node->href;
        $parsedUrl = parse_url($currentUrl);
        $serverHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;
        if (preg_match('/^tel:/', $currentUrl) || preg_match('/^mailto:/', $currentUrl)) {
            return \true;
        }
        if (isset($serverHost) && isset($parsedUrl['host']) && str_replace('www.', '', $parsedUrl['host']) !== str_replace('www.', '', $serverHost)) {
            return \true;
        }
        return \false;
    }
}
