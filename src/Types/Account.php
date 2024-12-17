<?php

namespace Nullform\Telegraphus\Types;

use Nullform\Telegraphus\AbstractType;

/**
 * This object represents a Telegraph account.
 *
 * @link https://telegra.ph/api#Account
 */
class Account extends AbstractType
{
    /**
     * Account name, helps users with several accounts remember which they are currently using.
     * Displayed to the user above the "Edit/Publish" button on Telegra.ph, other users don't see this name.
     *
     * @var string
     */
    public $short_name;

    /**
     * Default author name used when creating new articles.
     *
     * @var string
     */
    public $author_name;

    /**
     * Profile link, opened when users click on the author's name below the title.
     * Can be any link, not necessarily to a Telegram profile or channel.
     *
     * @var string
     */
    public $author_url;

    /**
     * Optional. Only returned by the createAccount and revokeAccessToken method. Access token of the Telegraph account.
     *
     * @var string|null
     */
    public $access_token = null;

    /**
     * Optional. URL to authorize a browser on telegra.ph and connect it to a Telegraph account.
     * This URL is valid for only one use and for 5 minutes only.
     *
     * @var string|null
     */
    public $auth_url = null;

    /**
     * Optional. Number of pages belonging to the Telegraph account.
     *
     * @var int|null
     */
    public $page_count = null;
}
