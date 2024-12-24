# Telegraphus

The PHP package to interact with [Telegra.ph](https://telegra.ph/).

> Telegra.ph is a minimalist publishing tool that allows you to create richly formatted posts
> and push them to the Web in just a click.

## Requirements

- PHP >= 5.6

## Installation

```shell
composer require nullform/telegraphus
```

## Usage examples

First you need to create an account and get an access token:

```php
use Nullform\Telegraphus\ApiClient;
use Nullform\Telegraphus\Types\Account;

$client = new ApiClient();

$account = new Account();
$account->author_name = 'John Doe';
$account->author_url = 'https://www.google.com/';
$account->short_name = 'john';

try {

    $client->createAccount($account);
    $token = $account->access_token;

    $client->setToken($token);

    // ...

} catch (\Exception $exception) {
    // ...
}
```

Next, you can create pages using the parser to convert your HTML code into *Telegra.ph* format.

```php
use Nullform\Telegraphus\ApiClient;
use Nullform\Telegraphus\Parser;

$token = 'token';
$client = new ApiClient($token);
$html = '<h1>Title</h1>'
    . '<div>First paragraph</div>'
    . '<div>Second <span>paragraph</span></div>'
    . '<footer>Copyright</footer>';

// Note that not all HTML tags are available in Telegra.ph.
// Available tags: a, aside, b, blockquote, br, code, em, figcaption, figure, h3, h4,
// hr, i, iframe, img, li, ol, p, pre, s, strong, u, ul, video.

$parser = new Parser();

// You can replace or delete tags
$parser->addTagReplaceRules([
    'h1'     => 'h3',  // Convert <h1> to <h3>
    'div'    => 'p',   // Convert <div> to <p>
    'span'   => 'b',   // Convert <span> to <b>
    'footer' => false, // Remove <footer>
]);

try {

    $page = $client->createPage(
        'Test page',
        $parser->htmlToTelegraphContent($html)
    );

    // ...

} catch (\Exception $exception) {
    // ...
}
```

After a successful request, *Telegra.ph* will create a page with the title "Test Page" and the following content:

```html
<h3>Title</h3>
<p>First paragraph</p>
<p>Second <b>paragraph</b></p>
```

You can pass HTML code as content to the createPage() method.
But then the HTML code will be converted to *Telegra.ph* format "as is", without replacing tags.

Don't forget that *Telegra.ph* doesn't support all tags, only the following:
a, aside, b, blockquote, br, code, em, figcaption, figure, h3, h4, hr, i, iframe, img, li, ol, p, pre, s, strong, u, ul, video.

And now you can get a list of all your pages:

```php
use Nullform\Telegraphus\ApiClient;

$token = 'token';
$client = new ApiClient($token);

try {

    $pageList = $client->getPageList();

    foreach ($pageList->pages as $page) {
        // ...
    }

} catch (\Exception $exception) {
    // ...
}
```

If you want to edit one:

```php
use Nullform\Telegraphus\ApiClient;

$token = 'token';
$client = new ApiClient($token);
$html = '<p>New page content</p>';
$path = 'page path';

try {

    $page = $client->editPage($path, 'Test page', $html);

    // ...

} catch (\Exception $exception) {
    // ...
}
```

## Methods

### ApiClient

- ApiClient::**__construct**(*?string* $token = null)
- ApiClient::**setToken**(*?string* $token): *ApiClient*
- ApiClient::**getToken**(): *?string*
- ApiClient::**getCurlOptions**(): *array*
- ApiClient::**setCurlOptions**(*array* $curlOptions): *ApiClient*
- ApiClient::**setCurlOption**(*int* $option, *mixed* \$value): *ApiClient*
- ApiClient::**getCurlInfo**(): *array*
- ApiClient::**createAccount**(*Account* $account): *Account*
- ApiClient::**editAccountInfo**(*Account* $account): *Account*
- ApiClient::**getAccountInfo**(): *Account*
- ApiClient::**revokeAccessToken**(): *Account*
- ApiClient::**createPage**(*string* \$title, *NodeElement[]|string* \$content, *?string* \$authorName = null, *?string* \$authorUrl = null): *Page*
- ApiClient::**editPage**(*string* \$path, *string* \$title, *NodeElement[]|string* \$content, *?string* \$authorName = null, *?string* \$authorUrl = null): *Page*
- ApiClient::**getPage**(*string* $path): *Page*
- ApiClient::**getPageList**(*int* \$offset = 0, *int* \$limit = 50): *PageList*
- ApiClient::**getViews**(*string* $path, *?GetViewsParams* \$params): *PageViews*

### Parser

- Parser::**addTagReplaceRule**(*string* $tag, *string* \$toTag): *Parser*
- Parser::**addTagReplaceRules**(*array* $rules): *Parser*
- Parser::**hasTagReplaceRule**(*string* $tag): *bool*
- Parser::**getTagReplaceRule**(*string* $tag): *string|false|null*
- Parser::**setAllowedAttributes**(*?string[]* $attributes): *Parser*
- Parser::**setDisallowedAttributes**(*string[]* $attributes): *Parser*
- Parser::**htmlToTelegraphContent**(*string* $html): *NodeElement[]|string[]*
- Parser::**telegraphContentToHtml**(*NodeElement[]* $content): *string*
- Parser::**decodeTelegraphContent**(*string* $json): *NodeElement[]*

## Tests

Tested on PHP 5.6, 7.2 and 8.0.

```shell
php composer.phar test tests
```
