<?php

namespace Weglot\Parser\Check\Dom;

use Weglot\Client\Api\Enum\WordType;

class ExternalLinkHref extends AbstractDomChecker
{
    public const DOM = 'a';

    public const PROPERTY = 'href';

    public const WORD_TYPE = WordType::EXTERNAL_LINK;

    protected function check()
    {
        $current_url = $this->node->href;
        $parsed_url = parse_url($current_url);
        $server_host = $_SERVER['HTTP_HOST'] ?? null;

        if (preg_match('/^tel:/', $current_url) || preg_match('/^mailto:/', $current_url)) {
            return true;
        }

        if (isset($server_host) && isset($parsed_url['host']) && str_replace('www.', '', $parsed_url['host']) !== str_replace('www.', '', $server_host)) {
            return true;
        } else {
            return false;
        }
    }
}
