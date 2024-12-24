<?php

namespace Nullform\Telegraphus;

use Nullform\Telegraphus\Exceptions\ParserInvalidTagReplaceRule;
use Nullform\Telegraphus\Exceptions\ParserInvalidHtmlException;
use Nullform\Telegraphus\Exceptions\ParserInvalidTelegraphContentException;
use Nullform\Telegraphus\Types\NodeElement;

/**
 * Parser for Telegraph content.
 *
 * Usage example:
 *
 * ```
 * $parser = new Parser();
 * $parser->addTagReplaceRules([
 *     'h1'     => 'h3',
 *     'h2'     => 'h4',
 *     'div'    => 'p',
 *     'iframe' => false, // Delete this sucker
 * ]);
 * $pageContent = $parser->htmlToTelegraphContent($wysiwygContent);
 * ```
 */
class Parser
{
    /**
     * @var array
     */
    protected $tagReplaceRules = [];

    /**
     * @var string[]|null
     */
    protected $allowedAttributes = null;

    /**
     * @var string[]
     */
    protected $disallowedAttributes = [];

    /**
     * Add rule to replace tag.
     *
     * @param string $tag Tag to be replaced.
     * @param string|false $toTag Tag to replace to. False if the target tag should be filtered (deleted).
     * @return $this
     * @throws ParserInvalidTagReplaceRule
     */
    public function addTagReplaceRule($tag, $toTag)
    {
        if (!\is_string($tag)) {
            throw new ParserInvalidTagReplaceRule('Tag must be a string');
        }
        if (!\is_string($toTag) && $toTag !== false) {
            throw new ParserInvalidTagReplaceRule('ToTag must be a string or false');
        }
        $this->tagReplaceRules[$tag] = $toTag;

        return $this;
    }

    /**
     * Add a list of rules for replacing tags.
     *
     * Array keys - tag to be replaced. Array values - tag to replace to (or false if the target tag should be deleted).
     *
     * Example:
     *
     * ```
     * $parser->addTagReplaceRules([
     *     'b'      => 'strong',
     *     'div'    => 'p',
     *     'iframe' => false, // Delete iframes
     * ]);
     * ```
     *
     * @param array $rules
     * @return $this
     * @throws ParserInvalidTagReplaceRule
     * @uses Parser::addTagReplaceRule()
     */
    public function addTagReplaceRules($rules)
    {
        foreach ($rules as $tag => $toTag) {
            $this->addTagReplaceRule($tag, $toTag);
        }

        return $this;
    }

    /**
     * Is there a rule to replace the tag?
     *
     * @param string $tag
     * @return bool
     */
    public function hasTagReplaceRule($tag)
    {
        return isset($this->tagReplaceRules[(string)$tag]);
    }

    /**
     * Get a rule to replace a tag.
     *
     * - string - replace tag with another tag.
     * - false - delete tag along with its contents.
     * - null - rule not found for this tag.
     *
     * @param string $tag
     * @return string|false|null
     */
    public function getTagReplaceRule($tag)
    {
        return $this->hasTagReplaceRule($tag) ? $this->tagReplaceRules[(string)$tag] : null;
    }

    /**
     * List of allowed tag attributes. Other attributes will be filtered.
     *
     * @param string[]|null $attributes Null - disable the allowed attributes filter.
     * @return $this
     */
    public function setAllowedAttributes($attributes)
    {
        $this->allowedAttributes = \is_null($attributes) ?: $this->prepareListOfAttributes($attributes);

        return $this;
    }

    /**
     * List of attributes to be filtered.
     *
     * @param string[] $attributes
     * @return $this
     */
    public function setDisallowedAttributes($attributes)
    {
        $this->disallowedAttributes = $this->prepareListOfAttributes($attributes);

        return $this;
    }

    /**
     * Convert HTML to array of Node.
     *
     * @param string $html
     * @return NodeElement[]|string[]
     * @throws ParserInvalidHtmlException
     */
    public function htmlToTelegraphContent($html)
    {
        $document = $this->htmlToDomDocument($html);
        /** @var \DOMNode[] $bodyNodeList */
        $bodyNodeList = $document->getElementsByTagName('body');
        $nodes = [];

        /**
         * @param \DOMNode $node
         * @return NodeElement|string|null
         */
        $phpNodeToTelegraphNode = function ($node) use (&$phpNodeToTelegraphNode) {
            $nodeElement = null;

            if ($node->nodeType == \XML_ELEMENT_NODE) {
                $nodeElement = new NodeElement();
                $tag = $node->nodeName;

                if ($this->hasTagReplaceRule($node->nodeName)) {
                    $tag = $this->getTagReplaceRule($node->nodeName);
                }

                if (!$tag) {
                    return null;
                }

                $nodeElement->tag = $tag;

                if ($node->attributes->count()) {
                    $nodeElement->attrs = [];
                    foreach ($node->attributes as $name => $attrNode) {
                        if ($this->isAttributeAllowed($name)) {
                            $nodeElement->attrs[$name] = $attrNode->nodeValue;
                        }
                    }
                    if (!$nodeElement->attrs) {
                        $nodeElement->attrs = null;
                    }
                }

                if ($node->childNodes->count()) {
                    $nodeElement->children = [];
                    foreach ($node->childNodes as $childNode) {
                        $child = $phpNodeToTelegraphNode($childNode);
                        if ($child) {
                            $nodeElement->children[] = $child;
                        }
                    }
                }
            } elseif ($node->nodeType == \XML_TEXT_NODE) {
                $nodeElement = (string)$node->nodeValue;
            }

            return $nodeElement;
        };

        foreach ($bodyNodeList as $node) {
            foreach ($node->childNodes as $child) {
                $element = $phpNodeToTelegraphNode($child);
                if (!\is_null($element)) {
                    $nodes[] = $element;
                }
            }
        }

        return $nodes;
    }

    /**
     * Convert array of Node to HTML.
     *
     * @param NodeElement[] $content
     * @return string
     * @throws ParserInvalidTelegraphContentException
     */
    public function telegraphContentToHtml($content)
    {
        $document = $this->telegraphContentToDomDocument($content);

        return \html_entity_decode($document->saveHTML());
    }

    /**
     * Decode Telegraph content from a JSON string.
     *
     * @param string $json
     * @return NodeElement[]
     * @throws ParserInvalidTelegraphContentException
     */
    public function decodeTelegraphContent($json)
    {
        $decoded = @\json_decode($json, true);

        if (!\is_array($decoded)) {
            throw new ParserInvalidTelegraphContentException('Invalid content');
        }

        $nodes = [];
        $getNodeFromSource = function ($source) use (&$getNodeFromSource) {
            $node = \is_string($source) ? $source : new NodeElement($source);

            if ($node instanceof NodeElement && !empty($source['children'])) {
                $node->children = [];
                foreach ($source['children'] as $child) {
                    $node->children[] = $getNodeFromSource($child);
                }
            }

            return $node;
        };

        foreach ($decoded as $node) {
            $nodes[] = $getNodeFromSource($node);
        }

        return $nodes;
    }

    /**
     * @param string $html
     * @return \DOMDocument
     * @throws ParserInvalidHtmlException
     */
    protected function htmlToDomDocument($html)
    {
        $document = $this->createDefaultDomDocument();

        $source = '<html lang="en">';
        $source .= '<head><title>Document</title>';
        $source .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        $source .= '</head>';
        $source .= '<body>' . $html . '</body>';
        $source .= '</html>';

        if (!$document->loadHTML($source, \LIBXML_NOERROR)) {
            throw new ParserInvalidHtmlException('Invalid HTML');
        }

        return $document;
    }

    /**
     * @param NodeElement[]|string[] $content
     * @return \DOMDocument
     * @throws ParserInvalidTelegraphContentException
     */
    protected function telegraphContentToDomDocument($content)
    {
        $document = $this->createDefaultDomDocument();
        /**
         * @param NodeElement|string $source
         * @param \DOMNode|null $parentNode
         * @return void
         * @throws ParserInvalidTelegraphContentException
         */
        $parseSourceElement = function ($source, $parentNode = null) use (&$parseSourceElement, $document) {
            if (\is_string($source)) {
                $node = $document->createTextNode($source);
            } elseif ($source instanceof NodeElement) {
                $tag = $source->tag;
                if ($this->hasTagReplaceRule($tag)) {
                    $tag = $this->getTagReplaceRule($tag);
                }
                if (!$tag) {
                    return;
                }
                try {
                    $node = $document->createElement($tag);
                } catch (\Exception $e) {
                    throw new ParserInvalidTelegraphContentException('Invalid tag: ' . $e->getMessage());
                }
            } else {
                throw new ParserInvalidTelegraphContentException('Invalid content source: ' . \gettype($source));
            }
            if ($source instanceof NodeElement && !empty($source->children)) {
                foreach ($source->children as $child) {
                    $parseSourceElement($child, $node);
                }
            }
            if ($source instanceof NodeElement && !empty($source->attrs)) {
                foreach ($source->attrs as $key => $value) {
                    if ($this->isAttributeAllowed($key)) {
                        $node->setAttribute($key, $value);
                    }
                }
            }
            if ($parentNode) {
                $parentNode->appendChild($node);
            } else {
                $document->appendChild($node);
            }
        };

        foreach ($content as $element) {
            $parseSourceElement($element);
        }

        return $document;
    }

    /**
     * @return \DOMDocument
     */
    protected function createDefaultDomDocument()
    {
        return new \DOMDocument('1.0', 'utf-8');
    }

    /**
     * @param string[] $attributes
     * @return string[]
     */
    protected function prepareListOfAttributes($attributes)
    {
        $attributes = (array)$attributes;

        $attributes = \array_map(function ($attribute) {
            return \trim(\strtolower((string)$attribute));
        }, $attributes);

        $attributes = \array_filter($attributes);

        return \array_unique($attributes);
    }

    /**
     * @param string $attribute
     * @return bool
     */
    protected function isAttributeAllowed($attribute)
    {
        $attribute = \strtolower((string)$attribute);

        return (\is_null($this->allowedAttributes) || \in_array($attribute, $this->allowedAttributes))
            && !\in_array($attribute, $this->disallowedAttributes);
    }
}
