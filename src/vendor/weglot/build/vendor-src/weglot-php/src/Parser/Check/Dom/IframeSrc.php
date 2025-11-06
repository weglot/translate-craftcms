<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
class IframeSrc extends AbstractDomChecker
{
    const DOM = 'iframe';
    const PROPERTY = 'src';
    const WORD_TYPE = WordType::EXTERNAL_LINK;
    protected function check()
    {
        $boolean = \false;
        $current_url = $this->node->src;
        $parsed_url = parse_url($current_url);
        $server_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;
        if (isset($server_host) && isset($parsed_url['host']) && str_replace('www.', '', $parsed_url['host']) !== str_replace('www.', '', $server_host)) {
            return \true;
        } else {
            return \false;
        }
    }
}
