<?php

namespace Weglot\Client\Api\Enum;

/**
 * Enum BotType
 * Used to define which bot is parsing the page.
 * Basically, most of time we recommend to use as "human".
 */
abstract class BotType
{
    public const HUMAN = 0;
    public const OTHER = 1;
    public const GOOGLE = 2;
    public const BING = 3;
    public const YAHOO = 4;
    public const BAIDU = 5;
    public const YANDEX = 6;
    public const WGVE = 7;
}
