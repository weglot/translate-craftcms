<?php

namespace Weglot\Vendor\Weglot\Parser\Check;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
use Weglot\Vendor\Weglot\Client\Api\Exception\InvalidWordTypeException;
use Weglot\Vendor\Weglot\Client\Api\WordEntry;
use Weglot\Vendor\Weglot\Parser\Check\Dom\AbstractDomChecker;
use Weglot\Vendor\Weglot\Parser\Parser;
use Weglot\Vendor\Weglot\Util\Text;
use Weglot\Vendor\WGSimpleHtmlDom\simple_html_dom;
use Weglot\Vendor\WGSimpleHtmlDom\simple_html_dom_node;
class DomCheckerProvider
{
    /**
     * @var array
     */
    protected $inlineNodes = ['a', 'span', 'strong', 'b', 'em', 'i', 'small', 'big', 'sub', 'sup', 'abbr', 'acronym', 'bdo', 'cite', 'kbd', 'q', 'u', 'mark'];
    const DEFAULT_CHECKERS_NAMESPACE = '\Weglot\Vendor\Weglot\Parser\Check\Dom\\';
    /**
     * @var Parser
     */
    protected $parser;
    /**
     * @var array
     */
    protected $checkers = [];
    /**
     * @var array
     */
    protected $discoverCaching = [];
    /**
     * @var int
     */
    protected $translationEngine;
    /**
     * @param int $translationEngine
     */
    public function __construct(Parser $parser, $translationEngine)
    {
        $this->setParser($parser);
        $this->setTranslationEngine($translationEngine);
        $this->loadDefaultCheckers();
    }
    /**
     * @return $this
     */
    public function setParser(Parser $parser)
    {
        $this->parser = $parser;
        return $this;
    }
    /**
     * @return Parser
     */
    public function getParser()
    {
        return $this->parser;
    }
    /**
     * @param array $inlineNodes
     *
     * @return $this
     */
    public function setInlineNodes($inlineNodes)
    {
        $this->inlineNodes = $inlineNodes;
        return $this;
    }
    /**
     * @return array
     */
    public function getInlineNodes()
    {
        return $this->inlineNodes;
    }
    /**
     * @param string $node
     *
     * @return $this
     */
    public function addInlineNode($node)
    {
        $this->inlineNodes[] = $node;
        return $this;
    }
    /**
     * @param int $translationEngine
     *
     * @return $this
     */
    public function setTranslationEngine($translationEngine)
    {
        $this->translationEngine = $translationEngine;
        return $this;
    }
    /**
     * @return int
     */
    public function getTranslationEngine()
    {
        return $this->translationEngine;
    }
    /**
     * @param AbstractDomChecker $checker
     *
     * @return $this
     */
    public function addChecker($checker)
    {
        $this->checkers[] = $checker;
        return $this;
    }
    /**
     * @return $this
     */
    public function addCheckers(array $checkers)
    {
        $this->checkers = array_merge($this->checkers, $checkers);
        return $this;
    }
    /**
     * @return $this
     */
    public function removeCheckers(array $removeCheckers)
    {
        $this->checkers = array_diff($this->checkers, $removeCheckers);
        return $this;
    }
    /**
     * @return array
     */
    public function getCheckers()
    {
        $this->resetDiscoverCaching();
        return $this->checkers;
    }
    /**
     * @return $this
     */
    public function resetDiscoverCaching()
    {
        $this->discoverCaching = [];
        return $this;
    }
    /**
     * @param string $domToSearch
     *
     * @return array<simple_html_dom_node>
     */
    public function discoverCachingGet($domToSearch, simple_html_dom $dom)
    {
        if (!isset($this->discoverCaching[$domToSearch])) {
            $this->discoverCaching[$domToSearch] = $dom->find($domToSearch);
        }
        return $this->discoverCaching[$domToSearch];
    }
    /**
     * Load default checkers.
     *
     * @return void
     */
    protected function loadDefaultCheckers()
    {
        $files = array_diff(scandir(__DIR__ . '/Dom'), ['AbstractDomChecker.php', '..', '.']);
        $checkers = array_map(function ($filename) {
            return self::DEFAULT_CHECKERS_NAMESPACE . Text::removeFileExtension($filename);
        }, $files);
        $this->addCheckers($checkers);
    }
    /**
     * @param mixed $checker Class of the Checker to add
     *
     * @return bool
     */
    public function register($checker)
    {
        if ($checker instanceof AbstractDomChecker) {
            $this->addChecker($checker);
            return \true;
        }
        return \false;
    }
    /**
     * @param string $class
     *
     * @return array
     */
    protected function getClassDetails($class)
    {
        $class = self::DEFAULT_CHECKERS_NAMESPACE . $class;
        return [$class, $class::DOM, $class::PROPERTY, $class::WORD_TYPE];
    }
    /**
     * @return array
     *
     * @throws InvalidWordTypeException
     */
    public function handle(simple_html_dom $dom)
    {
        $nodes = [];
        $checkers = $this->getCheckers();
        foreach ($checkers as $class) {
            list($selector, $property, $defaultWordType) = $class::toArray();
            $discoveringNodes = $this->discoverCachingGet($selector, $dom);
            if ($this->getTranslationEngine() <= 2) {
                // Old model
                $this->handleOldEngine($discoveringNodes, $nodes, $class, $property, $defaultWordType);
            }
            if (3 == $this->getTranslationEngine()) {
                // New model
                for ($i = 0; $i < \count($discoveringNodes); ++$i) {
                    $node = $discoveringNodes[$i];
                    $instance = new $class($node, $property);
                    if ($instance->handle()) {
                        $wordType = $defaultWordType;
                        $attributes = [];
                        // Will contain attributes of merged node so that we can put them back after the API call.
                        if ('text' === $selector) {
                            if ('title' === $node->parent->tag) {
                                $wordType = WordType::TITLE;
                            }
                            $shift = 0;
                            // If the parent node is eligible, we take it instead and we continue until it's not eligible.
                            while ($number = $this->numberOfTextNodeInParentAfterChild($node->parentNode(), $node)) {
                                $node = $node->parentNode();
                                $shift = $number - 1;
                                if ('root' === $node->tag) {
                                    break;
                                }
                            }
                            // We descend the node to see if we can take a child instead, in the case there are wrapping node or empty nodes. For instance, In that case <p><b>Hello</b></p>, it's better to chose node "b" than "p"
                            $node = $this->getMinimalNode($node);
                            // We remove attributes from all child nodes and replace by wg-1, wg-2, etc... Real attributes are saved into $attributes.
                            $node = $this->removeAttributesFromChild($node, $attributes);
                            $i += $shift;
                        }
                        $this->getParser()->getWords()->addOne(new WordEntry($node->{$property}, $wordType));
                        $nodes[] = ['node' => $node, 'class' => $class, 'property' => $property, 'attributes' => $attributes];
                    }
                }
            }
        }
        return $nodes;
    }
    /**
     * @param array        $discoveringNodes
     * @param array        $nodes
     * @param class-string $class
     * @param string       $property
     * @param int          $wordType
     *
     * @return void
     *
     * @throws InvalidWordTypeException
     */
    public function handleOldEngine($discoveringNodes, &$nodes, $class, $property, $wordType)
    {
        foreach ($discoveringNodes as $node) {
            $instance = new $class($node, $property);
            if ($instance->handle()) {
                $this->getParser()->getWords()->addOne(new WordEntry($node->{$property}, $wordType));
                $nodes[] = ['node' => $node, 'class' => $class, 'property' => $property];
            } else if (str_contains($node->{$property}, '&gt;') || str_contains($node->{$property}, '&lt;')) {
                $node->{$property} = str_replace(['&lt;', '&gt;'], ['<', '>'], $node->{$property});
            }
        }
    }
    /**
     * This function is important : It return the number of text node inside a given node, but it count only text node that are inside or after a given child (if no child is given it count everything)
     * If at some point it find a block or a excluded block, it returns false.
     *
     * @param simple_html_dom_node|null $node
     * @param simple_html_dom_node|null $child
     * @param bool                      $countEmptyText
     *
     * @return int|false
     */
    public function numberOfTextNodeInParentAfterChild($node, $child = null, &$countEmptyText = \false)
    {
        $count = 0;
        if ($this->isText($node)) {
            if (!$countEmptyText && '' != Text::fullTrim($node->innertext()) && !is_numeric(Text::fullTrim($node->innertext())) && !preg_match('/^\d+%$/', Text::fullTrim($node->innertext()))) {
                $countEmptyText = \true;
            }
            if ($countEmptyText) {
                ++$count;
            }
        }
        if (\is_object($node)) {
            foreach ($node->nodes as $k => $n) {
                if ('comment' === $n->tag) {
                    unset($node->nodes[$k]);
                    continue;
                }
                if ($this->containsBlock($n) || $n->hasAttribute(Parser::ATTRIBUTE_NO_TRANSLATE)) {
                    return \false;
                }
                if (null != $child && $n->outertext() == $child->outertext()) {
                    $child = null;
                }
                if (null == $child) {
                    $number = $this->numberOfTextNodeInParentAfterChild($n, null, $countEmptyText);
                    if (\false === $number) {
                        return \false;
                    } else {
                        $count += $number;
                    }
                }
            }
            $node->nodes = array_values($node->nodes);
        }
        return $count;
    }
    /**
     * @param simple_html_dom_node $node
     *
     * @return simple_html_dom_node
     */
    public function getMinimalNode($node)
    {
        if ($this->isText($node)) {
            return $node;
        }
        // We remove unnecessary wrapping nodes
        while (1 == \count($node->nodes)) {
            $node = $node->nodes[0];
        }
        $notEmptyChild = [];
        foreach ($node->nodes as $n) {
            if (!$this->hasOnlyEmptyChild($n)) {
                $notEmptyChild[] = $n;
            }
        }
        if (1 == \count($notEmptyChild)) {
            return $this->getMinimalNode($notEmptyChild[0]);
        }
        return $node;
    }
    /**
     * @param simple_html_dom_node $node
     * @param array                $attributes
     *
     * @return simple_html_dom_node
     */
    public function removeAttributesFromChild($node, &$attributes)
    {
        foreach ($node->children() as $child) {
            if ('comment' === $child->tag) {
                continue;
            }
            $k = \count($attributes) + 1;
            $attributes['wg-' . $k] = $child->getAllAttributes();
            $child->attr = [];
            $child->setAttribute('wg-' . $k, '');
            $this->removeAttributesFromChild($child, $attributes);
        }
        return $node;
    }
    /**
     * @param simple_html_dom_node $node
     *
     * @return bool
     */
    public function hasOnlyEmptyChild($node)
    {
        if ($this->isText($node)) {
            if ('' != Text::fullTrim($node->innertext())) {
                return \false;
            } else {
                return \true;
            }
        }
        foreach ($node->nodes as $child) {
            if (!$this->hasOnlyEmptyChild($child)) {
                return \false;
            }
        }
        return \true;
    }
    /**
     * @param simple_html_dom_node $node
     *
     * @return bool
     */
    public function isInline($node)
    {
        return \in_array($node->tag, $this->getInlineNodes());
    }
    /**
     * @param simple_html_dom_node $node
     *
     * @return bool
     */
    public function isText($node)
    {
        return 'text' === $node->tag;
    }
    /**
     * @param simple_html_dom_node $node
     *
     * @return bool
     */
    public function isBlock($node)
    {
        return !$this->isInline($node) && !$this->isText($node) && !('br' === $node->tag);
    }
    /**
     * @param simple_html_dom_node $node
     *
     * @return bool
     */
    public function containsBlock($node)
    {
        if ($this->isBlock($node)) {
            return \true;
        } else {
            foreach ($node->nodes as $n) {
                if ($this->containsBlock($n)) {
                    return \true;
                }
            }
            return \false;
        }
    }
    /**
     * @param simple_html_dom_node $node
     *
     * @return bool
     */
    public function isInlineOrText($node)
    {
        return $this->isInline($node) || $this->isText($node);
    }
    /**
     * @param mixed $value
     * @param bool  $strict
     *
     * @return array
     */
    public function unsetValue(array $array, $value, $strict = \true)
    {
        if (($key = array_search($value, $array, $strict)) !== \false) {
            unset($array[$key]);
        }
        return $array;
    }
}
