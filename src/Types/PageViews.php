<?php

namespace Nullform\Telegraphus\Types;

use Nullform\Telegraphus\AbstractType;

/**
 * This object represents the number of page views for a Telegraph article.
 *
 * @link https://telegra.ph/api#PageViews
 */
class PageViews extends AbstractType
{
    /**
     * Number of page views for the target page.
     *
     * @var int
     */
    public $views;
}
