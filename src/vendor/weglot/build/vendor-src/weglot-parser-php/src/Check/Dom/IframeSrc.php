<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Parser\Definitions\Enum\WordType;
class IframeSrc extends AbstractDomChecker
{
    public const DOM = 'iframe';
    public const PROPERTY = 'src';
    public const WORD_TYPE = WordType::EXTERNAL_LINK;
    protected function check()
    {
        $currentUrl = $this->node->src;
        $parsedUrl = parse_url($currentUrl);
        $serverHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;
        if (isset($serverHost) && isset($parsedUrl['host']) && str_replace('www.', '', $parsedUrl['host']) !== str_replace('www.', '', $serverHost)) {
            return \true;
        }
        return \false;
    }
}
