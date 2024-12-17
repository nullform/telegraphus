<?php

namespace Nullform\Telegraphus\Types;

use Nullform\Telegraphus\AbstractType;

/**
 * This object represents a list of Telegraph articles belonging to an account. Most recently created articles first.
 *
 * @link https://telegra.ph/api#PageList
 */
class PageList extends AbstractType
{
    /**
     * Total number of pages belonging to the target Telegraph account.
     *
     * @var int
     */
    public $total_count;

    /**
     * Requested pages of the target Telegraph account.
     *
     * @var Page[]
     */
    public $pages = [];

    /**
     * @inheritDoc
     */
    public function map($data)
    {
        parent::map($data);

        if ($this->pages) {
            $this->pages = \array_map(function ($page) {
                return new Page($page);
            }, $this->pages);
        }
    }
}
