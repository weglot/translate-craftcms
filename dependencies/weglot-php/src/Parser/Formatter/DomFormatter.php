<?php

namespace Weglot\Parser\Formatter;

if (!\function_exists('array_column')) {
    /**
     * @param int|string|null $columnKey
     * @param int|string|null $indexKey
     *
     * @return array|false
     */
    function array_column(array $input, $columnKey, $indexKey = null)
    {
        $array = [];
        foreach ($input as $value) {
            if (!\array_key_exists($columnKey, $value)) {
                trigger_error("Key \"$columnKey\" does not exist in array");

                return false;
            }
            if (null === $indexKey) {
                $array[] = $value[$columnKey];
            } else {
                if (!\array_key_exists($indexKey, $value)) {
                    trigger_error("Key \"$indexKey\" does not exist in array");

                    return false;
                }
                if (!\is_scalar($value[$indexKey])) {
                    trigger_error("Key \"$indexKey\" does not contain scalar value");

                    return false;
                }
                $array[$value[$indexKey]] = $value[$columnKey];
            }
        }

        return $array;
    }
}

class DomFormatter extends AbstractFormatter
{
    public function handle(array $nodes, &$index)
    {
        $translatable_attributes = $this->getTranslatableAttributes();

        $original_words = array_column($this->getTranslated()->getInputWords()->jsonSerialize(), 'w');
        $translated_words = array_column($this->getTranslated()->getOutputWords()->jsonSerialize(), 'w');

        for ($i = 0; $i < \count($nodes); ++$i) {
            $currentNode = $nodes[$i];

            if (null !== $translated_words[$i + $index]) {
                $currentTranslated = $translated_words[$i + $index];

                $this->metaContent($currentNode, $currentTranslated, $translatable_attributes, $original_words, $translated_words);
                $this->imageSource($currentNode, $currentTranslated, $i);
            }
        }
        $index += \count($nodes);
    }

    /**
     * @param string $translated
     * @param array  $translatable_attributes
     * @param array  $original_words
     * @param array  $translated_words
     *
     * @return void
     */
    protected function metaContent(array $details, $translated, $translatable_attributes, $original_words, $translated_words)
    {
        $property = $details['property'];

        if ($details['class']::ESCAPE_SPECIAL_CHAR) {
            $details['node']->$property = htmlspecialchars($translated);
        } else {
            $details['node']->$property = $translated;
        }

        if (\array_key_exists('attributes', $details)) {
            foreach ($details['attributes'] as $wg => $attributes) {
                $attributeString = '';
                foreach ($attributes as $key => $attribute) {
                    if (\in_array($key, $translatable_attributes)) {
                        $pos = array_search($attribute, $original_words);
                        if (false !== $pos) {
                            $attribute = $translated_words[$pos];
                        }
                    }
                    $attributeString .= $key.'="'.$attribute.'" ';
                }
                $attributeString = \strlen($attributeString) > 0 ? ' '.$attributeString : $attributeString;
                $details['node']->$property = str_replace(' '.$wg.'=""', rtrim($attributeString), $details['node']->$property);
                $details['node']->$property = str_replace(' '.$wg.'=\'\'', rtrim($attributeString), $details['node']->$property);
            }
        }
    }

    /**
     * @param string $translated
     * @param int    $index
     *
     * @return void
     */
    protected function imageSource(array $details, $translated, $index)
    {
        $words = $this->getTranslated()->getInputWords();

        if ('\Weglot\Parser\Check\Dom\ImageSource' === $details['class']) {
            if ($details['node']->hasAttribute('srcset')
                && '' != $details['node']->srcset
                && $translated != $words[$index]->getWord()) {
                $details['node']->srcset = '';
            }
        }

        if ('\Weglot\Parser\Check\Dom\ImageDataSource' === $details['class']) {
            $dataSrcSet = 'data-srcset';
            if ($details['node']->hasAttribute('data-srcset')
                && $details['node']->$dataSrcSet != ''
                && $translated != $words[$index]->getWord()) {
                $details['node']->$dataSrcSet = '';
            }
        }
    }

    /**
     * @return array
     */
    protected function getTranslatableAttributes()
    {
        $checkers = $this->getParser()->getDomCheckerProvider()->getCheckers();

        $attributes = [];
        foreach ($checkers as $class) {
            $attributes[] = $class::toArray()[1];
        }

        return $attributes;
    }
}
