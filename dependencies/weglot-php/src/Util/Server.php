<?php

namespace Weglot\Util;

use Weglot\Client\Api\Enum\BotType;

class Server
{
    /**
     * @param bool $use_forwarded_host
     *
     * @return string
     */
    public static function fullUrl(array $server, $use_forwarded_host = false)
    {
        return self::urlOrigin($server, $use_forwarded_host).$server['REQUEST_URI'];
    }

    /**
     * @param bool $use_forwarded_host
     *
     * @return string
     */
    public static function urlOrigin(array $server, $use_forwarded_host = false)
    {
        return self::getProtocol($server).'://'.self::getHost($server, $use_forwarded_host);
    }

    /**
     * @return bool
     */
    public static function detectBotVe(array $server)
    {
        $userAgent = self::getUserAgent($server);
        $checkBotVe = Text::contains($userAgent, 'Weglot Visual Editor');
        if (null !== $userAgent && $checkBotVe) {
            return true;
        }

        return false;
    }

    /**
     * @return int
     */
    public static function detectBot(array $server)
    {
        $userAgent = self::getUserAgent($server);
        if (\is_string($userAgent) && !empty($userAgent) && preg_match('/bot|favicon|crawl|facebook|slurp|spider/i', $userAgent)) {
            $checkBotAgent = true;
        } else {
            $checkBotAgent = false;
        }
        $checkBotGoogle = (Text::contains($userAgent, 'Google')
                            || Text::contains($userAgent, 'facebook')
                            || Text::contains($userAgent, 'wprocketbot')
                            || Text::contains($userAgent, 'Ahrefs')
                            || Text::contains($userAgent, 'SemrushBot'));

        if (null !== $userAgent && !$checkBotAgent) {
            return BotType::HUMAN;
        }
        if (null !== $userAgent && $checkBotAgent && $checkBotGoogle) {
            return BotType::GOOGLE;
        }
        foreach (self::otherBotAgents() as $agent => $agentBot) {
            if (null !== $userAgent && $checkBotAgent && !$checkBotGoogle && Text::contains($userAgent, $agent)) {
                return $agentBot;
            }
        }

        return BotType::OTHER;
    }

    /**
     * @return array
     */
    private static function otherBotAgents()
    {
        return [
            'bing' => BotType::BING,
            'yahoo' => BotType::YAHOO,
            'Baidu' => BotType::BAIDU,
            'Yandex' => BotType::YANDEX,
        ];
    }

    /**
     * @return bool
     */
    private static function isSsl(array $server)
    {
        if (isset($server['HTTPS'])) {
            if ('on' == strtolower($server['HTTPS'])) {
                return true;
            }

            if ('1' == $server['HTTPS']) {
                return true;
            } elseif (isset($server['SERVER_PORT']) && ('443' == $server['SERVER_PORT'])) {
                return true;
            }
        }

        if (isset($server['HTTP_X_FORWARDED_PROTO']) && 'https' === $server['HTTP_X_FORWARDED_PROTO']) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public static function getProtocol(array $server)
    {
        $protocol = isset($server['SERVER_PROTOCOL']) ? strtolower($server['SERVER_PROTOCOL']) : 'http';

        return substr($protocol, 0, strpos($protocol, '/')).(self::isSsl($server) ? 's' : '');
    }

    /**
     * @return string
     */
    public static function getPortForUrl(array $server)
    {
        $ssl = self::isSsl($server);

        if ((!$ssl && '80' === self::getPort($server))
            || ($ssl && '443' === self::getPort($server))) {
            return '';
        }

        return ':'.self::getPort($server);
    }

    /**
     * @return string
     */
    public static function getPort(array $server)
    {
        if (!isset($server['SERVER_PORT'])) {
            return '';
        }

        return $server['SERVER_PORT'];
    }

    /**
     * @param bool $use_forwarded_host
     *
     * @return string
     */
    public static function getHost(array $server, $use_forwarded_host = false)
    {
        $host = null;

        if ($use_forwarded_host && isset($server['HTTP_X_FORWARDED_HOST'])) {
            $host = $server['HTTP_X_FORWARDED_HOST'];
        } elseif (isset($server['HTTP_HOST'])) {
            $host = $server['HTTP_HOST'];
        }

        if (null === $host && isset($server['SERVER_NAME'])) {
            $host = $server['SERVER_NAME'].self::getPort($server);
        }

        return $host;
    }

    /**
     * @return string|null
     */
    public static function getUserAgent(array $server)
    {
        return $server['HTTP_USER_AGENT'] ?? null;
    }
}
