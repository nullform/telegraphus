<?php

namespace Nullform\Telegraphus\Types;

/**
 * This object represents a DOM element node.
 *
 * @link https://telegra.ph/api#NodeElement
 */
class NodeElement extends Node
{
    /**
     * Name of the DOM element.
     *
     * Available tags: a, aside, b, blockquote, br, code, em, figcaption, figure, h3, h4, hr, i, iframe,
     * img, li, ol, p, pre, s, strong, u, ul, video.
     *
     * @var string
     */
    public $tag;

    /**
     * Optional. Attributes of the DOM element.
     * Key represents name of attribute, value represents value of attribute.
     *
     * Available attributes: href, src.
     *
     * @var null|array{href: string, src: string}
     */
    public $attrs = null;

    /**
     * Optional. List of child nodes for the DOM element.
     *
     * @var null|NodeElement[]|string[]
     */
    public $children = null;

    /**
     * @inheritDoc
     */
    public function map($data)
    {
        parent::map($data);

        if ($this->children) {
            $this->children = \array_map(function ($child) {
                if (\is_array($child)) {
                    return new NodeElement($child);
                } else {
                    return $child;
                }
            }, $this->children);
        }
    }
}
