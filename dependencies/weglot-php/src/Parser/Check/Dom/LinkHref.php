<?php

namespace Weglot\Parser\Check\Dom;

use Weglot\Client\Api\Enum\WordType;
use Weglot\Util\Text as TextUtil;

class LinkHref extends AbstractDomChecker
{
    public const DOM = 'a';

    public const PROPERTY = 'href';

    public const WORD_TYPE = WordType::PDF_HREF;

    /**
     * @var array
     */
    protected $extensions = [
        'pdf',
        'rar',
        'docx',
    ];

    protected function check()
    {
        $boolean = false;

        foreach ($this->extensions as $extension) {
            $start = (\strlen($extension) + 1) * -1;
            $boolean = $boolean || (strtolower(substr(TextUtil::fullTrim($this->node->href), $start)) === ('.'.$extension));
        }

        return $boolean;
    }
}
