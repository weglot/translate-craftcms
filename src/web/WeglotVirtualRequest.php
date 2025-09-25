<?php

declare(strict_types=1);

namespace weglot\craftweglot\web;

use craft\web\Request;

class WeglotVirtualRequest extends Request
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly string $forcedPath,
        private readonly Request $original,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    /**
     * Splits the forced path into segments by removing leading and trailing slashes,
     * trimming additional whitespace, and filtering empty segments.
     *
     * @return array<int, string> An array of non-empty segments derived from the forced path.
     */
    private function forcedSegments(): array
    {
        return array_values(array_filter(explode('/', trim($this->forcedPath, '/'))));
    }

    /**
     * Retrieves the path information, either the real path info from the original source
     * or the forced path set within the instance.
     *
     * @param bool $returnRealPathInfo Determines whether to return the real path info
     *                                 from the original source or the forced path.
     *
     * @return string The requested path information, either the real path info or the forced path.
     */
    public function getPathInfo(bool $returnRealPathInfo = false): string
    {
        if ($returnRealPathInfo) {
            return $this->original->getPathInfo(true);
        }
        return $this->forcedPath;
    }

    /**
     * Retrieves the full path stored in the forcedPath property.
     *
     * @return string The full path as a string.
     */
    public function getFullPath(): string
    {
        return $this->forcedPath;
    }

    /**
     * Retrieves the segments by utilizing the forcedSegments method.
     *
     * @return array<int, string> The array of segments.
     */
    public function getSegments(): array
    {
        return $this->forcedSegments();
    }

    /**
     * Retrieves a specific segment based on the given number.
     *
     * @param int|string $num The segment number to retrieve (1-based index).
     *
     * @return string|null The segment corresponding to the given number, or null if it does not exist.
     */
    public function getSegment($num): ?string
    {
        $i = (int)$num - 1;
        $segments = $this->forcedSegments();
        return $segments[$i] ?? null;
    }

    /**
     * Constructs and retrieves the URL based on the forced path and query string.
     *
     * @return string The constructed URL.
     */
    public function getUrl(): string
    {
        $qs = $this->original->getQueryString();
        return '/' . ltrim($this->forcedPath, '/') . ($qs !== '' ? ('?' . $qs) : '');
    }
}
