<?php

namespace Nullform\Telegraphus\Types;

use Nullform\Telegraphus\AbstractType;

/**
 * @link https://telegra.ph/api#getViews
 */
class GetViewsParams extends AbstractType
{
    /**
     * Required if month is passed. If passed, the number of page views for the requested year will be returned.
     *
     * 2000-2100.
     *
     * @var null|int
     */
    public $year = null;

    /**
     * Required if day is passed. If passed, the number of page views for the requested month will be returned.
     *
     * 1-12
     *
     * @var null|int
     */
    public $month = null;

    /**
     * Required if hour is passed. If passed, the number of page views for the requested day will be returned.
     *
     * 1-31
     *
     * @var null|int
     */
    public $day = null;

    /**
     * If passed, the number of page views for the requested hour will be returned.
     *
     * 0-24
     *
     * @var null|int
     */
    public $hour = null;
}
