<?php

declare(strict_types=1);

namespace weglot\craftweglot\checkers\dom;

use Weglot\Vendor\Weglot\Parser\Check\Dom\AbstractDomChecker;
use Weglot\Vendor\Weglot\Parser\Definitions\Enum\WordType;

/**
 * Collects the `srcset` attribute of <img> for translation.
 *
 * weglot-parser-php 0.1.1 dropped the upstream ImageSourceSet checker that
 * weglot-php used to ship, which stopped `srcset` URLs from being translated
 * (translated `src` would then coexist with original-language `srcset`). This
 * plugin-side checker restores that behaviour; ParserService gates it behind
 * the `media_enabled` option, like ImageSource / ImageDataSource.
 */
class ImageSourceSet extends AbstractDomChecker
{
    public const DOM = 'img';
    public const PROPERTY = 'srcset';
    public const WORD_TYPE = WordType::IMG_SRC;
}
