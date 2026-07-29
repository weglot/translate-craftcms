<?php

namespace Weglot\Vendor\Weglot\Parser\Formatter;

class IgnoredNodes
{
    /**
     * @var string
     */
    protected $source;
    /**
     * @var array<int, string>
     */
    protected $ignoredNodes = ['strong', 'b', 'em', 'i', 'small', 'big', 'sub', 'sup', 'abbr', 'acronym', 'bdo', 'cite', 'kbd', 'q'];
    /**
     * @param string $source
     */
    public function __construct($source = '')
    {
        $this->setSource($source);
    }
    /**
     * @param array<int, string> $ignoredNodes
     *
     * @return $this
     */
    public function setIgnoredNodes($ignoredNodes)
    {
        $this->ignoredNodes = $ignoredNodes;
        return $this;
    }
    /**
     * @return array<int, string>
     */
    public function getIgnoredNodes()
    {
        return $this->ignoredNodes;
    }
    /**
     * @param string $source
     *
     * @return $this
     */
    public function setSource($source)
    {
        $this->source = $source;
        return $this;
    }
    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }
    /**
     * @param array<int|string, string> $matches
     */
    protected function replaceContent($matches): void
    {
        $this->setSource(str_replace($matches[0], '&lt;' . $matches['tag'] . str_replace('>', '&gt;', str_replace('<', '&lt;', $matches['more'])) . '&gt;' . $matches['content'] . '&lt;/' . $matches['tag'] . '&gt;', $this->getSource()));
    }
    public function handle(): void
    {
        $pattern = '#<(?<tag>' . implode('|', $this->ignoredNodes) . ')(?<more>\s.*?)?\>(?<content>[^>]*?)\<\/(?<tagclosed>' . implode('|', $this->ignoredNodes) . ')>#i';
        $matches = [];
        while (preg_match($pattern, $this->getSource(), $matches)) {
            $this->replaceContent($matches);
        }
    }
}
