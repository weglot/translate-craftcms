<?php

namespace Weglot\Vendor\Weglot\Parser\Check\Dom;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
use Weglot\Vendor\Weglot\Parser\Parser;
use Weglot\Vendor\Weglot\Util\Text;
use Weglot\Vendor\WGSimpleHtmlDom\simple_html_dom_node;
abstract class AbstractDomChecker
{
    /**
     * DOM node to match.
     *
     * @var string
     */
    const DOM = '';
    /**
     * DOM property to get.
     *
     * @var string
     */
    const PROPERTY = '';
    /**
     * Type of content returned by DOM property.
     *
     * @var int
     */
    const WORD_TYPE = WordType::TEXT;
    /**
     * Need to escape DOM attribute.
     *
     * @var bool
     */
    const ESCAPE_SPECIAL_CHAR = \false;
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
        // we check if we're on wg-mode-whitelist
        if ($this->node->hasAncestorAttribute('wg-mode-whitelist')) {
            return '' != Text::fullTrim($this->node->{$property}) && $this->node->hasAncestorAttribute(Parser::ATTRIBUTE_TRANSLATE);
        } else {
            return '' != Text::fullTrim($this->node->{$property}) && (!$this->node->hasAncestorAttribute(Parser::ATTRIBUTE_NO_TRANSLATE) || $this->node->hasAncestorAttribute(Parser::ATTRIBUTE_TRANSLATE_INSIDE_BLOCKS));
        }
    }
    /**
     * @return bool
     */
    protected function check()
    {
        return \true;
    }
    /**
     * @return array
     */
    public static function toArray()
    {
        $class = static::class;
        return [$class::DOM, $class::PROPERTY, $class::WORD_TYPE];
    }
}
