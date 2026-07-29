<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Parser\Definitions\Enum\WordType;
use Weglot\Vendor\Weglot\Parser\Parser;
use Weglot\Vendor\Weglot\Parser\Util\Text;
use Weglot\Vendor\WGSimpleHtmlDom\simple_html_dom_node;
abstract class AbstractDomChecker
{
    public const DOM = '';
    public const PROPERTY = '';
    public const WORD_TYPE = WordType::TEXT;
    public const ESCAPE_SPECIAL_CHAR = \false;
    /**
     * @var simple_html_dom_node
     */
    protected $node;
    /**
     * @var string
     */
    protected $property;
    /**
     * @param string $property
     */
    public function __construct(simple_html_dom_node $node, $property)
    {
        $this->setNode($node)->setProperty($property);
    }
    /**
     * @return $this
     */
    public function setNode(simple_html_dom_node $node)
    {
        $this->node = $node;
        return $this;
    }
    /**
     * @return simple_html_dom_node
     */
    public function getNode()
    {
        return $this->node;
    }
    /**
     * @param string $property
     *
     * @return $this
     */
    public function setProperty($property)
    {
        $this->property = $property;
        return $this;
    }
    /**
     * @return string
     */
    public function getProperty()
    {
        return $this->property;
    }
    /**
     * @return bool
     */
    public function handle()
    {
        return $this->defaultCheck() && $this->check();
    }
    /**
     * @return bool
     */
    protected function defaultCheck()
    {
        $property = $this->property;
        if ($this->node->hasAncestorAttribute('wg-mode-whitelist')) {
            return '' != Text::fullTrim($this->node->{$property}) && $this->node->hasAncestorAttribute(Parser::ATTRIBUTE_TRANSLATE);
        }
        return '' != Text::fullTrim($this->node->{$property}) && (!$this->node->hasAncestorAttribute(Parser::ATTRIBUTE_NO_TRANSLATE) || $this->node->hasAncestorAttribute(Parser::ATTRIBUTE_TRANSLATE_INSIDE_BLOCKS));
    }
    /**
     * @return bool
     */
    protected function check()
    {
        return \true;
    }
    /**
     * @return list<int|string>
     */
    public static function toArray()
    {
        $class = static::class;
        return [$class::DOM, $class::PROPERTY, $class::WORD_TYPE];
    }
}
