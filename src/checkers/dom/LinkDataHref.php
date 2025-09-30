<?php

namespace weglot\craftweglot\checkers\dom;

use Weglot\Vendor\Weglot\Parser\Check\Dom\LinkHref;

class LinkDataHref extends LinkHref
{
    /**
     * {@inheritdoc}
     */
    public const PROPERTY = 'data-href';
}
