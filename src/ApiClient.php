<?php

namespace Nullform\Telegraphus;

use Nullform\Telegraphus\Exceptions\HttpException;
use Nullform\Telegraphus\Exceptions\ParserInvalidHtmlException;
use Nullform\Telegraphus\Exceptions\TelegraphApiException;
use Nullform\Telegraphus\Exceptions\TokenNotProvidedException;
use Nullform\Telegraphus\Types\Account;
use Nullform\Telegraphus\Types\GetViewsParams;
use Nullform\Telegraphus\Types\NodeElement;
use Nullform\Telegraphus\Types\Page;
use Nullform\Telegraphus\Types\PageList;
use Nullform\Telegraphus\Types\PageViews;

/**
 * Client for Telegraph API.
 *
 * @link https://telegra.ph/api
 */
class ApiClient
{
    /**
     * @var string
     */
    protected $baseUrl = 'https://api.telegra.ph/';

    /**
     * @var string|null
     */
    protected $token = null;

    /**
     * @var array
     */
    protected $curlOptions = [];

    /**
     * @var array
     */
    protected $curlInfo = [];

    /**
     * @param string|null $token Access token of the Telegraph account.
     */
    public function __construct($token = null)
    {
        if ($token) {
            $this->setToken($token);
        }
    }

    /**
     * Set an access token to use in all future API requests.
     *
     * @param string|null $token
     * @return $this
     */
    public function setToken($token = null)
    {
        if (!\is_null($token)) {
            $token = (string)$token;
        }

        $this->token = $token;

        return $this;
    }

    /**
     * Get the current access token.
     *
     * @return string|null
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Get current options for a cURL transfer.
     *
     * @return array
     */
    public function getCurlOptions()
    {
        return $this->curlOptions;
    }

    /**
     * Set multiple (rewrite) options for a cURL transfer.
     *
     * @param array $curlOptions An array specifying which options to set and their values.
     * @return $this
     * @see https://www.php.net/manual/en/function.curl-setopt-array.php
     */
    public function setCurlOptions($curlOptions)
    {
        $this->curlOptions = (array)$curlOptions;

        return $this;
    }

    /**
     * Set an option for a cURL transfer.
     *
     * @param int $option
     * @param mixed $value
     * @return $this
     * @see https://www.php.net/manual/en/function.curl-setopt.php
     */
    public function setCurlOption($option, $value)
    {
        $this->curlOptions[(int)$option] = $value;

        return $this;
    }

    /**
     * Information about the last request via cURL.
     *
     * @return array
     * @see \curl_getinfo()
     */
    public function getCurlInfo()
    {
        return $this->curlInfo;
    }

    /**
     * Use this method to create a new Telegraph account.
     *
     * Most users only need one account, but this can be useful for channel administrators who would like to keep
     * individual author names and profile links for each of their channels.
     *
     * On success, returns an Account object with the regular fields and an additional access_token field.
     *
     * @param Account $account
     * @return Account
     * @throws HttpException
     * @throws TelegraphApiException
     * @link https://telegra.ph/api#createAccount
     */
    public function createAccount($account)
    {
        return $account->map($this->callApi('createAccount', null, $account->toArray()));
    }

    /**
     * Use this method to update information about a Telegraph account. Pass only the parameters that you want to edit.
     *
     * On success, returns an Account object with the default fields.
     *
     * @param Account $account
     * @return Account
     * @throws HttpException
     * @throws TelegraphApiException
     * @throws TokenNotProvidedException
     * @link https://telegra.ph/api#editAccountInfo
     */
    public function editAccountInfo($account)
    {
        $this->checkRequiredToken();

        return $account->map($this->callApi('editAccountInfo', null, $account->toArray()));
    }

    /**
     * Use this method to get information about a Telegraph account.
     *
     * Returns an Account object on success.
     *
     * @return Account
     * @throws HttpException
     * @throws TelegraphApiException
     * @throws TokenNotProvidedException
     * @link https://telegra.ph/api#getAccountInfo
     */
    public function getAccountInfo()
    {
        $this->checkRequiredToken();

        return new Account(
            $this->callApi(
                'getAccountInfo',
                null,
                [
                    'fields' => ['short_name', 'author_name', 'author_url', 'auth_url', 'page_count'],
                ]
            )
        );
    }

    /**
     * Use this method to revoke access_token and generate a new one, for example,
     * if the user would like to reset all connected sessions, or you have reasons to believe the token was compromised.
     *
     * On success, returns an Account object with new access_token and auth_url fields.
     *
     * @return Account
     * @throws HttpException
     * @throws TelegraphApiException
     * @throws TokenNotProvidedException
     * @link https://telegra.ph/api#revokeAccessToken
     */
    public function revokeAccessToken()
    {
        $this->checkRequiredToken();

        return new Account($this->callApi('revokeAccessToken'));
    }

    /**
     * Use this method to create a new Telegraph page.
     *
     * On success, returns a Page object.
     *
     * Immediately after the page is created, its description may not be filled in.
     * To get an actual description, request the page using the getPage method.
     *
     * @param string $title
     * @param NodeElement[]|string $content Array of NodeElement or HTML (will be converted automatically).
     * @param null|string $authorName
     * @param null|string $authorUrl
     * @return Page
     * @throws ParserInvalidHtmlException
     * @throws HttpException
     * @throws TelegraphApiException
     * @throws TokenNotProvidedException
     * @link https://telegra.ph/api#createPage
     */
    public function createPage($title, $content, $authorName = null, $authorUrl = null)
    {
        $this->checkRequiredToken();

        $data = $this->prepareDataForPage($title, $content, $authorName, $authorUrl);

        return new Page($this->callApi('createPage', null, $data));
    }

    /**
     * Use this method to edit an existing Telegraph page.
     *
     * On success, returns a Page object.
     *
     * @param string $path
     * @param string $title
     * @param NodeElement[]|string $content Array of NodeElement or HTML (will be converted automatically).
     * @param null|string $authorName
     * @param null|string $authorUrl
     * @return Page
     * @throws HttpException
     * @throws ParserInvalidHtmlException
     * @throws TelegraphApiException
     * @throws TokenNotProvidedException
     * @link https://telegra.ph/api#editPage
     */
    public function editPage($path, $title, $content, $authorName = null, $authorUrl = null)
    {
        $this->checkRequiredToken();

        $data = $this->prepareDataForPage($title, $content, $authorName, $authorUrl);

        return new Page($this->callApi('editPage', $path, $data));
    }

    /**
     * Use this method to get a Telegraph page.
     *
     * Returns a Page object on success.
     *
     * @param string $path
     * @return Page
     * @throws HttpException
     * @throws TelegraphApiException
     * @link https://telegra.ph/api#getPage
     */
    public function getPage($path)
    {
        return new Page($this->callApi('getPage', $path, ['return_content' => true]));
    }

    /**
     * Use this method to get a list of pages belonging to a Telegraph account.
     *
     * Returns a PageList object, sorted by most recently created pages first.
     *
     * @param int $offset
     * @param int $limit
     * @return PageList
     * @throws HttpException
     * @throws TelegraphApiException
     * @throws TokenNotProvidedException
     */
    public function getPageList($offset = 0, $limit = 50)
    {
        $this->checkRequiredToken();

        return new PageList($this->callApi('getPageList', null, ['offset' => $offset, 'limit' => $limit]));
    }

    /**
     * Use this method to get the number of views for a Telegraph article.
     *
     * Returns a PageViews object on success.
     *
     * By default, the total number of page views will be returned.
     *
     * @param string $path
     * @param GetViewsParams|null $params
     * @return PageViews
     * @throws HttpException
     * @throws TelegraphApiException
     */
    public function getViews($path, $params = null)
    {
        return new PageViews($this->callApi('getViews', $path, $params));
    }

    /**
     * @param string $title
     * @param NodeElement[]|string $content Array of NodeElement or HTML (will be converted automatically).
     * @param null|string $authorName
     * @param null|string $authorUrl
     * @return array
     * @throws ParserInvalidHtmlException
     */
    protected function prepareDataForPage($title, $content, $authorName = null, $authorUrl = null)
    {
        $data = [
            'title'          => $title,
            'return_content' => true,
        ];

        if (!\is_array($content)) {
            $parser = new Parser();
            $content = $parser->htmlToTelegraphContent($content);
        }

        $data['content'] = \json_encode($content, JSON_UNESCAPED_UNICODE);

        if (!empty($authorName)) {
            $data['author_name'] = (string)$authorName;
        }
        if (!empty($authorUrl)) {
            $data['author_url'] = (string)$authorUrl;
        }

        return $data;
    }

    /**
     * @return void
     * @throws TokenNotProvidedException
     */
    protected function checkRequiredToken()
    {
        if (!$this->token) {
            throw new TokenNotProvidedException('Token not provided');
        }
    }

    /**
     * Call Telegraph API.
     *
     * @param string $method
     * @param string|null $path
     * @param array|AbstractType|null $data Data that will be encoded in JSON and sent along with the request.
     * @return string Result (JSON string).
     * @throws HttpException
     * @throws TelegraphApiException
     * @see https://telegra.ph/api
     */
    protected function callApi($method, $path = null, $data = null)
    {
        $method = (string)$method;
        $url = $this->baseUrl . $method;
        $body = '';

        if ($data instanceof AbstractType) {
            $data = $data->toArray();
        }

        if ($path) {
            $url .= '/' . $path;
        }

        if (isset($data['path'])) {
            unset($data['path']);
        }
        if (isset($data['access_token'])) {
            unset($data['access_token']);
        }
        if ($this->getToken()) {
            $data['access_token'] = $this->getToken();
        }

        if (!empty($data)) {
            $body = \json_encode($data);
        }

        $curl = \curl_init($url);
        $curlOptions = $this->curlOptions;

        $curlOptions[\CURLOPT_POST] = true;
        $curlOptions[\CURLOPT_RETURNTRANSFER] = true;
        $curlOptions[\CURLOPT_FOLLOWLOCATION] = true;

        if (!isset($curlOptions[\CURLOPT_HTTPHEADER])) {
            $curlOptions[\CURLOPT_HTTPHEADER] = [];
        }

        if (!empty($body)) {
            $curlOptions[\CURLOPT_POSTFIELDS] = $body;
            $curlOptions[\CURLOPT_HTTPHEADER] = \array_merge($curlOptions[\CURLOPT_HTTPHEADER], [
                'Content-Type: application/json',
                'Content-Length: ' . \strlen($body),
            ]);
        }

        \curl_setopt_array($curl, $curlOptions);

        $response = \curl_exec($curl);
        $parsedResponse = \json_decode((string)$response, true);
        $curlError = \curl_error($curl);

        $this->curlInfo = \curl_getinfo($curl);

        \curl_close($curl);

        if (empty($parsedResponse['ok'])) { // Fail
            if (!empty($this->curlInfo['http_code'])) {
                throw new TelegraphApiException(
                    !empty($parsedResponse['error']) ? (string)$parsedResponse['error'] : 'Unknown Telegraph API error',
                    (int)$this->curlInfo['http_code']
                );
            } else {
                throw new HttpException($curlError ?: 'Unknown cURL error');
            }
        }

        return $parsedResponse['result'] ?: '';
    }
}
