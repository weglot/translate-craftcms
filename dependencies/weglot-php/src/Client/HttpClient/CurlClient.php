<?php

namespace Weglot\Client\HttpClient;

// @codingStandardsIgnoreStart
// PSR2 requires all constants be upper case. Sadly, the CURL_SSLVERSION
// constants do not abide by those rules.
// Note the values 1 and 6 come from their position in the enum that
// defines them in cURL's source code.
if (!\defined('CURL_SSLVERSION_TLSv1')) {
    \define('CURL_SSLVERSION_TLSv1', 1);
}
if (!\defined('CURL_SSLVERSION_TLSv1_2')) {
    \define('CURL_SSLVERSION_TLSv1_2', 6);
}

// @codingStandardsIgnoreEnd

class CurlClient implements ClientInterface
{
    public const DEFAULT_TIMEOUT = 80;
    public const DEFAULT_CONNECT_TIMEOUT = 30;

    public const INITIAL_NETWORK_RETRY_DELAY = 0.5;
    public const MAX_NETWORK_RETRY_DELAY = 2.0;

    public const MAX_NETWORK_RETRIES = 0;

    /**
     * @var int
     */
    protected $timeout = self::DEFAULT_TIMEOUT;

    /**
     * @var int
     */
    protected $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT;

    /**
     * @var int
     */
    protected $maxNetworkRetries = self::MAX_NETWORK_RETRIES;

    /**
     * @var array<string>
     */
    protected $defaultHeaders = [];

    /**
     * @var array<mixed>
     */
    protected $defaultOptions = [];

    /**
     * @var array<string, string>
     */
    protected $userAgentInfo = [];

    /**
     * @param array<mixed>  $defaultOptions
     * @param array<string> $defaultHeaders
     */
    public function __construct(array $defaultOptions = [], array $defaultHeaders = [])
    {
        $this->defaultOptions = $defaultOptions;
        $this->defaultHeaders = $defaultHeaders;

        $this->initUserAgentInfo();
    }

    /**
     * Initializing default user-agent.
     *
     * @return void
     */
    public function initUserAgentInfo()
    {
        $curlVersion = curl_version();
        $this->userAgentInfo = [
            'curl' => 'cURL\\'.$curlVersion['version'],
            'ssl' => $curlVersion['ssl_version'],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function getDefaultOptions()
    {
        return $this->defaultOptions;
    }

    /**
     * @param string $header
     *
     * @return void
     */
    public function addHeader($header)
    {
        $this->defaultHeaders[] = $header;
    }

    /**
     * @return array<string>
     */
    public function getDefaultHeaders()
    {
        return $this->defaultHeaders;
    }

    /**
     * @param string $service
     * @param string $value
     *
     * @return void
     */
    public function addUserAgentInfo($service, $value)
    {
        $this->userAgentInfo[$service] = $value;
    }

    /**
     * @return array<string, string>
     */
    public function getUserAgentInfo()
    {
        return $this->userAgentInfo;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param int $seconds
     *
     * @return $this
     */
    public function setTimeout($seconds)
    {
        $this->timeout = $seconds;

        return $this;
    }

    /**
     * @return int
     */
    public function getConnectTimeout()
    {
        return $this->connectTimeout;
    }

    /**
     * @param int $seconds
     *
     * @return $this
     */
    public function setConnectTimeout($seconds)
    {
        $this->connectTimeout = $seconds;

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxNetworkRetries()
    {
        return $this->maxNetworkRetries;
    }

    /**
     * @param int $retries
     *
     * @return $this
     */
    public function setMaxNetworkRetries($retries)
    {
        $this->maxNetworkRetries = $retries;

        return $this;
    }

    public function request($method, $absUrl, $params = [], $body = [])
    {
        // init
        $method = strtolower($method);
        $headers = $this->getDefaultHeaders();
        $options = $this->getDefaultOptions();

        // parameters
        if (\count($params) > 0) {
            $encoded = http_build_query($params);
            $absUrl = $absUrl.'?'.$encoded;
        }

        // generic processing
        [$options, $headers] = $this->processMethod($method, $options, $headers, $body);
        $options = $this->processHeadersAndOptions($headers, $options, $absUrl);

        // Create a callback to capture HTTP headers for the response
        $rheaders = [];
        $headerCallback = static function ($curl, $header_line) use (&$rheaders) {
            // Ignore the HTTP request line (HTTP/1.1 200 OK)
            if (!str_contains($header_line, ':')) {
                return \strlen($header_line);
            }
            [$key, $value] = explode(':', trim($header_line), 2);
            $rheaders[trim($key)] = trim($value);

            return \strlen($header_line);
        };
        $options[\CURLOPT_HEADERFUNCTION] = $headerCallback;

        [$rbody, $rcode] = $this->executeRequestWithRetries($options, $absUrl);

        return [$rbody, $rcode, $rheaders];
    }

    /**
     * Setup behavior for each methods.
     *
     * @param string        $method
     * @param array<mixed>  $options
     * @param array<string> $headers
     * @param array<mixed>  $body
     *
     * @return array{array<mixed>, array<string>}
     *
     * @throws \Exception
     */
    private function processMethod($method, array $options, array $headers, array $body = [])
    {
        if ('get' === $method) {
            if ([] !== $body) {
                throw new \Exception('Issuing a GET request with a body');
            }
            $options[\CURLOPT_HTTPGET] = 1;
        } elseif ('post' === $method) {
            $dataString = json_encode($body);

            $options[\CURLOPT_POST] = 1;
            $options[\CURLOPT_POSTFIELDS] = $dataString;

            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: '.\strlen($dataString);
        } else {
            throw new \Exception('Unrecognized method '.strtoupper($method));
        }

        return [$options, $headers];
    }

    /**
     * @param array<string> $headers
     * @param array<mixed>  $options
     * @param string        $absUrl
     *
     * @return array<mixed>
     */
    private function processHeadersAndOptions(array $headers, array $options, $absUrl)
    {
        // By default for large request body sizes (> 1024 bytes), cURL will
        // send a request without a body and with a `Expect: 100-continue`
        // header, which gives the server a chance to respond with an error
        // status code in cases where one can be determined right away (say
        // on an authentication problem for example), and saves the "large"
        // request body from being ever sent.
        //
        // Unfortunately, the bindings don't currently correctly handle the
        // success case (in which the server sends back a 100 CONTINUE), so
        // we'll error under that condition. To compensate for that problem
        // for the time being, override cURL's behavior by simply always
        // sending an empty `Expect:` header.
        $headers[] = 'Expect: ';

        // injecting user-agent in headers
        $headers[] = 'User-Agent: '.implode(' | ', $this->getUserAgentInfo());

        // options
        $options[\CURLOPT_URL] = $absUrl;
        $options[\CURLOPT_RETURNTRANSFER] = true;
        $options[\CURLOPT_CONNECTTIMEOUT] = $this->getConnectTimeout();
        $options[\CURLOPT_TIMEOUT] = $this->getTimeout();
        $options[\CURLOPT_HTTPHEADER] = $headers;
        $options[\CURLOPT_SSL_VERIFYPEER] = true;
        $options[\CURLOPT_CAPATH] = __DIR__.'/../../../data/';
        $options[\CURLOPT_CAINFO] = __DIR__.'/../../../data/ca-certificates.crt';

        return $options;
    }

    /**
     * @param array<string, mixed> $options cURL options
     * @param string               $absUrl  The URL being requested, including domain and protocol
     *
     * @return array{string, int}
     *
     * @throws \Exception
     */
    protected function executeRequestWithRetries(array $options, $absUrl)
    {
        $numRetries = 0;
        $message = '';

        // prepare bypass list once
        $bypass = \defined('WP_PROXY_BYPASS_HOSTS')
            ? array_map('trim', explode(',', WP_PROXY_BYPASS_HOSTS))
            : [];

        // extract the hostname for bypass checking
        $urlHost = parse_url($absUrl, \PHP_URL_HOST);

        while (true) {
            $rcode = $errno = 0;

            $curl = curl_init();
            // only apply proxy settings if this host is NOT in the bypass list
            if (!\in_array($urlHost, $bypass, true)) {
                if (\defined('WP_PROXY_HOST')) {
                    curl_setopt($curl, \CURLOPT_PROXY, WP_PROXY_HOST);
                }
                if (\defined('WP_PROXY_PORT')) {
                    curl_setopt($curl, \CURLOPT_PROXYPORT, WP_PROXY_PORT);
                }
                // if we have a username (password may be omitted), send credentials
                if (\defined('WP_PROXY_USERNAME')) {
                    $user = WP_PROXY_USERNAME;
                    $pass = \defined('WP_PROXY_PASSWORD') ? WP_PROXY_PASSWORD : '';
                    curl_setopt($curl, \CURLOPT_PROXYUSERPWD, $user.':'.$pass);
                }
            }
            curl_setopt_array($curl, $options);

            $rbody = curl_exec($curl);
            if (false === $rbody) {
                $errno = curl_errno($curl);
                $message = curl_error($curl);
            } else {
                $rcode = curl_getinfo($curl, \CURLINFO_HTTP_CODE);
            }
            curl_close($curl);

            if ($this->shouldRetry($errno, $rcode, $numRetries)) {
                ++$numRetries;
                $sleepSeconds = $this->sleepTime($numRetries);
                usleep((int) ($sleepSeconds * 1000000));
                continue;
            }

            break;
        }

        if (false === $rbody) {
            $this->handleCurlError($absUrl, $errno, $message, $numRetries);
        }

        return [$rbody, $rcode];
    }

    /**
     * @param string $url
     * @param int    $errno
     * @param string $message
     * @param int    $numRetries
     *
     * @return never
     *
     * @throws \Exception
     */
    private function handleCurlError($url, $errno, $message, $numRetries)
    {
        switch ($errno) {
            case \CURLE_COULDNT_CONNECT:
            case \CURLE_COULDNT_RESOLVE_HOST:
            case \CURLE_OPERATION_TIMEOUTED:
                $msg = "Could not connect to Weglot ($url).  Please check your "
                       .'internet connection and try again.  If this problem persists, '
                       ."you should check Weglot's status at "
                       .'https://twitter.com/weglot, or';
                break;
            case \CURLE_SSL_CACERT:
            case \CURLE_SSL_PEER_CERTIFICATE:
                $msg = "Could not verify Weglot's SSL certificate.  Please make sure "
                       .'that your network is not intercepting certificates.  '
                       ."(Try going to $url in your browser.)  "
                       .'If this problem persists,';
                break;
            default:
                $msg = 'Unexpected error communicating with Weglot.  '
                       .'If this problem persists,';
        }
        $msg .= " let us know at support@weglot.com.\n\n(Network error [errno $errno]: $message)";
        if ($numRetries > 0) {
            $msg .= "\n\nRequest was retried $numRetries times.";
        }
        throw new \Exception($msg);
    }

    /**
     * Checks if an error is a problem that we should retry on. This includes both
     * socket errors that may represent an intermittent problem and some special
     * HTTP statuses.
     *
     * @param int $errno
     * @param int $rcode
     * @param int $numRetries
     *
     * @return bool
     */
    private function shouldRetry($errno, $rcode, $numRetries)
    {
        // Don't make too much retries
        if ($numRetries >= $this->getMaxNetworkRetries()) {
            return false;
        }

        // Retry on timeout-related problems (either on open or read).
        $timeoutRelated = (\CURLE_OPERATION_TIMEOUTED === $errno);

        // Destination refused the connection, the connection was reset, or a
        // variety of other connection failures. This could occur from a single
        // saturated server, so retry in case it's intermittent.
        $refusedConnection = (\CURLE_COULDNT_CONNECT === $errno);

        // 409 conflict
        $conflict = (409 === $rcode);

        if ($timeoutRelated || $refusedConnection || $conflict) {
            return true;
        }

        return false;
    }

    /**
     * @param int $numRetries
     *
     * @return float
     */
    private function sleepTime($numRetries)
    {
        // Apply exponential backoff with $initialNetworkRetryDelay on the
        // number of $numRetries so far as inputs. Do not allow the number to exceed
        // $maxNetworkRetryDelay.
        $sleepSeconds = min(
            self::INITIAL_NETWORK_RETRY_DELAY * 1.0 * 2 ** ($numRetries - 1),
            self::MAX_NETWORK_RETRY_DELAY
        );
        // Apply some jitter by randomizing the value in the range of
        // ($sleepSeconds / 2) to ($sleepSeconds).
        $sleepSeconds *= 0.5 * (1 + (mt_rand() / mt_getrandmax() * 1.0));
        // But never sleep less than the base sleep seconds.
        $sleepSeconds = max(self::INITIAL_NETWORK_RETRY_DELAY, $sleepSeconds);

        return $sleepSeconds;
    }
}
