<?php

namespace Nullform\Telegraphus\Types;

use Nullform\Telegraphus\AbstractType;
use Nullform\Telegraphus\Parser;

/**
 * This object represents a page on Telegraph.
 *
 * @link https://telegra.ph/api#Page
 */
class Page extends AbstractType
{
    /**
     * Path to the page.
     *
     * @var string
     */
    public $path;

    /**
     * URL of the page.
     *
     * @var string
     */
    public $url;

    /**
     * Title of the page.
     *
     * @var string
     */
    public $title;

    /**
     * Description of the page.
     *
     * @var string
     */
    public $description;

    /**
     * Optional. Name of the author, displayed below the title.
     *
     * @var null|string
     */
    public $author_name = null;

    /**
     * Optional. Profile link, opened when users click on the author's name below the title.
     * Can be any link, not necessarily to a Telegram profile or channel.
     *
     * @var null|string
     */
    public $author_url = null;

    /**
     * Optional. Image URL of the page.
     *
     * @var null|string
     */
    public $image_url = null;

    /**
     * Optional. Content of the page.
     *
     * @var null|NodeElement[]
     */
    public $content = null;

    /**
     * Number of page views for the page.
     *
     * @var int
     */
    public $views = 0;

    /**
     * Optional. Only returned if access_token passed.
     * True, if the target Telegraph account can edit the page.
     *
     * Currently returned only for getPageList method.
     *
     * @var null|bool
     */
    public $can_edit = null;

    /**
     * @inheritDoc
     */
    public function map($data)
    {
        parent::map($data);

        $parser = new Parser();

        if (\is_string($this->content)) { // JSON.
            try {
                $this->content = $parser->decodeTelegraphContent($this->content);
            } catch (\Exception $e) {
            }
        } elseif (\is_array($this->content)) { // Check for array[].
            for ($i = 0; $i < \count($this->content); $i++) {
                if (\is_array($this->content[$i])) { // Associative array.
                    $this->content[$i] = new NodeElement($this->content[$i]);
                }
            }
        }
    }
}
