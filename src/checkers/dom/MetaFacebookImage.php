<?php

namespace weglot\craftweglot\checkers\dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
use Weglot\Vendor\Weglot\Parser\Check\Dom\AbstractDomChecker;

class MetaFacebookImage extends AbstractDomChecker
{
    public const DOM = "meta[property='og:image'], meta[property='og:image:secure_url']";
    public const PROPERTY = 'content';
    public const WORD_TYPE = WordType::IMG_SRC;
}
