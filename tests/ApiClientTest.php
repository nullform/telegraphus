<?php

namespace Nullform\Telegraphus\Tests;

use Nullform\Telegraphus\ApiClient;
use Nullform\Telegraphus\Parser;
use Nullform\Telegraphus\Types\Account;
use Nullform\Telegraphus\Types\GetViewsParams;
use Nullform\Telegraphus\Types\Page;
use Nullform\Telegraphus\Types\PageList;
use Nullform\Telegraphus\Types\PageViews;
use PHPUnit\Framework\TestCase;

class ApiClientTest extends TestCase
{
    /**
     * @var null|Account
     */
    private static $account = null;

    public function testSetGetToken()
    {
        $apiClient = $this->getClient();
        $apiClient->setToken('test-token');

        $this->assertEquals('test-token', $apiClient->getToken());
    }

    public function testSetCurlOption()
    {
        $apiClient = $this->getClient();
        $apiClient->setCurlOption(CURLOPT_USERAGENT, 'test-user-agent');

        $this->assertTrue($apiClient->getCurlOptions()[CURLOPT_USERAGENT] === 'test-user-agent');
    }

    public function testSetCurlOptions()
    {
        $apiClient = $this->getClient();
        $apiClient->setCurlOptions([
            CURLOPT_USERAGENT => 'test-user-agent',
            CURLOPT_POST      => true,
        ]);

        $this->assertTrue($apiClient->getCurlOptions()[CURLOPT_USERAGENT] === 'test-user-agent');
        $this->assertTrue($apiClient->getCurlOptions()[CURLOPT_POST] === true);
    }

    public function testGetCurlOptions()
    {
        $apiClient = $this->getClient();
        $apiClient->setCurlOptions([
            CURLOPT_USERAGENT => 'test-user-agent',
        ]);

        $this->assertTrue(\is_array($apiClient->getCurlOptions()));
        $this->assertCount(1, $apiClient->getCurlOptions());
    }

    /**
     * @return void
     * @throws \Nullform\Telegraphus\Exceptions\HttpException
     * @throws \Nullform\Telegraphus\Exceptions\TelegraphApiException
     */
    public function testCreateAccount()
    {
        $account = new Account();
        $account->short_name = 'test-account';
        $account->author_url = 'https://www.google.com/';
        $account->author_name = 'Test account';

        $apiClient = $this->getClient();
        self::$account = $apiClient->createAccount($account);

        $this->assertInstanceOf(Account::class, self::$account);
        $this->assertEquals($account->short_name, self::$account->short_name);
        $this->assertEquals($account->author_url, self::$account->author_url);
        $this->assertEquals($account->author_name, self::$account->author_name);
        $this->assertTrue(\is_string($account->auth_url));
    }

    /**
     * @return void
     * @throws \Nullform\Telegraphus\Exceptions\HttpException
     * @throws \Nullform\Telegraphus\Exceptions\TelegraphApiException
     * @throws \Nullform\Telegraphus\Exceptions\TokenNotProvidedException
     * @depends testCreateAccount
     */
    public function testGetAccountInfo()
    {
        $account = $this->getClient()->getAccountInfo();

        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals(self::$account->author_name, $account->author_name);
        $this->assertEquals(self::$account->author_url, $account->author_url);
        $this->assertEquals(self::$account->short_name, $account->short_name);
        $this->assertTrue(\is_int($account->page_count));
    }

    /**
     * @return void
     * @throws \Nullform\Telegraphus\Exceptions\HttpException
     * @throws \Nullform\Telegraphus\Exceptions\TelegraphApiException
     * @throws \Nullform\Telegraphus\Exceptions\TokenNotProvidedException
     * @depends testCreateAccount
     */
    public function testEditAccountInfo()
    {
        self::$account->short_name = 'test-account-edited';
        self::$account->author_url = 'https://www.microsoft.com/';
        self::$account->author_name = 'Test account edited';

        $this->getClient()->editAccountInfo(self::$account);

        $this->assertEquals('test-account-edited', self::$account->short_name);
        $this->assertEquals('https://www.microsoft.com/', self::$account->author_url);
        $this->assertEquals('Test account edited', self::$account->author_name);
    }

    /**
     * @return Page
     * @throws \Nullform\Telegraphus\Exceptions\HttpException
     * @throws \Nullform\Telegraphus\Exceptions\ParserInvalidHtmlException
     * @throws \Nullform\Telegraphus\Exceptions\TelegraphApiException
     * @throws \Nullform\Telegraphus\Exceptions\TokenNotProvidedException
     * @depends testCreateAccount
     */
    public function testCreatePage()
    {
        $sourceHtml = '<h3>Title</h3>';
        $sourceHtml .= '<p>First paragraph</p>';
        $sourceHtml .= '<p>Second <b>paragraph</b></p>';
        $title = 'Test page';
        $authorName = self::$account->author_name;
        $authorUrl = self::$account->author_url;

        $client = $this->getClient();
        $page = $client->createPage($title, $sourceHtml, $authorName, $authorUrl);

        $this->assertInstanceOf(Page::class, $page);
        $this->assertEquals($title, $page->title);
        $this->assertEquals($authorName, $page->author_name);
        $this->assertEquals($authorUrl, $page->author_url);
        $this->assertCount(3, $page->content);

        return $page;
    }

    /**
     * @param Page $page
     * @return Page
     * @throws \Nullform\Telegraphus\Exceptions\HttpException
     * @throws \Nullform\Telegraphus\Exceptions\TelegraphApiException
     * @depends testCreatePage
     */
    public function testGetPage($page)
    {
        $newPage = $this->getClient()->getPage($page->path);

        $this->assertInstanceOf(Page::class, $newPage);
        $this->assertEquals($page->title, $newPage->title);
        $this->assertEquals($page->author_name, $newPage->author_name);
        $this->assertEquals($page->author_url, $newPage->author_url);
        $this->assertEquals($page->content, $newPage->content);
        $this->assertTrue(\is_int($newPage->views));
//        $this->assertTrue($newPage->can_edit); // Currently returned only for getPageList method.
        $this->assertNull($newPage->image_url);

        return $page;
    }

    /**
     * @param Page $page
     * @return void
     * @throws \Nullform\Telegraphus\Exceptions\HttpException
     * @throws \Nullform\Telegraphus\Exceptions\ParserInvalidHtmlException
     * @throws \Nullform\Telegraphus\Exceptions\TelegraphApiException
     * @throws \Nullform\Telegraphus\Exceptions\TokenNotProvidedException
     * @depends testGetPage
     */
    public function testEditPage($page)
    {
        $parser = new Parser();
        $data = new Page();
        $data->title = 'Edited test page';
        $data->author_name = 'Tester';
        $data->author_url = 'https://www.google.com/';
        $data->content = $parser->htmlToTelegraphContent('<p>Hello world</p>');

        $newPage = $this->getClient()->editPage(
            $page->path,
            $data->title,
            $data->content,
            $data->author_name,
            $data->author_url
        );

        $this->assertInstanceOf(Page::class, $newPage);
        $this->assertEquals($data->title, $newPage->title);
        $this->assertEquals($data->author_name, $newPage->author_name);
        $this->assertEquals($data->author_url, $newPage->author_url);
        $this->assertEquals($data->content, $newPage->content);
    }

    /**
     * @param Page $page
     * @return void
     * @throws \Nullform\Telegraphus\Exceptions\HttpException
     * @throws \Nullform\Telegraphus\Exceptions\TelegraphApiException
     * @depends testGetPage
     */
    public function testGetViews($page)
    {
        $params = new GetViewsParams();
        $params->year = (int)date('Y');
        $params->month = (int)date('n');
        $params->day = (int)date('j');
        $params->hour = (int)date('G');

        $views = $this->getClient()->getViews($page->path, $params);

        $this->assertInstanceOf(PageViews::class, $views);
        $this->assertTrue(\is_int($views->views));
    }

    /**
     * @return void
     * @throws \Nullform\Telegraphus\Exceptions\HttpException
     * @throws \Nullform\Telegraphus\Exceptions\TelegraphApiException
     * @throws \Nullform\Telegraphus\Exceptions\TokenNotProvidedException
     * @depends testGetPage
     */
    public function testGetPageList()
    {
        $pageList = $this->getClient()->getPageList(0, 5);

        $this->assertInstanceOf(PageList::class, $pageList);
        $this->assertTrue(\is_int($pageList->total_count));
        $this->assertEquals(1, $pageList->total_count);
        $this->assertInstanceOf(Page::class, $pageList->pages[0]);
        $this->assertTrue($pageList->pages[0]->can_edit);
    }

    /**
     * @return void
     * @throws \Nullform\Telegraphus\Exceptions\HttpException
     * @throws \Nullform\Telegraphus\Exceptions\TelegraphApiException
     * @throws \Nullform\Telegraphus\Exceptions\TokenNotProvidedException
     * @depends testCreateAccount
     */
    public function testGetCurlInfo()
    {
        $client = $this->getClient();

        $client->getAccountInfo();
        $info = $client->getCurlInfo();

        $this->assertEquals(200, $info['http_code']);
    }

    /**
     * @return void
     * @throws \Nullform\Telegraphus\Exceptions\HttpException
     * @throws \Nullform\Telegraphus\Exceptions\TelegraphApiException
     * @throws \Nullform\Telegraphus\Exceptions\TokenNotProvidedException
     * @depends testEditAccountInfo
     */
    public function testRevokeAccessToken()
    {
        $prevAccount = self::$account;
        self::$account = $this->getClient()->revokeAccessToken();

        $this->assertInstanceOf(Account::class, self::$account);
        $this->assertNotEquals($prevAccount->access_token, self::$account->access_token);
    }

    /**
     * @return ApiClient
     */
    protected function getClient()
    {
        return new ApiClient(self::$account ? self::$account->access_token : null);
    }
}
