<?php

namespace Nullform\Telegraphus\Tests;

use Nullform\Telegraphus\Exceptions\ParserInvalidTagReplaceRule;
use Nullform\Telegraphus\Exceptions\ParserInvalidHtmlException;
use Nullform\Telegraphus\Exceptions\ParserInvalidTelegraphContentException;
use Nullform\Telegraphus\Parser;
use Nullform\Telegraphus\Types\NodeElement;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    /**
     * @throws ParserInvalidHtmlException
     */
    public function testHtmlToTelegraphContent()
    {
        $html = '<h1>Title</h1>';
        $html .= '<p>First paragraph</p>';
        $html .= '<p>Second paragraph</p>';
        $html .= '<p>Third <b>paragraph</b> with <a href="https://www.google.com/">link</a>.</p>';
        $html .= '<footer>Copyright</footer>';

        $parser = $this->getParser();
        $content = $parser->htmlToTelegraphContent($html);

        $this->assertTrue(\is_array($content));
        $this->assertCount(5, $content);
        $this->assertTrue($content[0]->tag == 'h1');
        $this->assertTrue($content[1]->tag == 'p');
        $this->assertTrue($content[2]->tag == 'p');
        $this->assertTrue($content[3]->tag == 'p');
        $this->assertTrue($content[4]->tag == 'footer');
        $this->assertTrue(\is_array($content[3]->children));
        $this->assertCount(5, $content[3]->children);
        $this->assertTrue(\is_string($content[3]->children[0]));
        $this->assertInstanceOf(NodeElement::class, $content[3]->children[1]);
        $this->assertTrue(\is_string($content[3]->children[2]));
        $this->assertInstanceOf(NodeElement::class, $content[3]->children[3]);
        $this->assertTrue(\is_string($content[3]->children[4]));
    }

    /**
     * @throws ParserInvalidTelegraphContentException
     */
    public function testDecodeTelegraphContent()
    {
        $json = '[{"tag":"p","children":["Hello, world!"]}]';
        $parser = $this->getParser();
        $content = $parser->decodeTelegraphContent($json);

        $this->assertTrue(\is_array($content));
        $this->assertCount(1, $content);
        $this->assertInstanceOf(NodeElement::class, $content[0]);
        $this->assertTrue($content[0]->tag == 'p');
        $this->assertTrue(\is_array($content[0]->children));
        $this->assertTrue(\is_string($content[0]->children[0]));

        $this->expectException(ParserInvalidTelegraphContentException::class);
        $parser = $this->getParser();
        $parser->decodeTelegraphContent('invalid content');
    }

    /**
     * @throws ParserInvalidHtmlException
     * @throws ParserInvalidTelegraphContentException
     * @throws ParserInvalidTagReplaceRule
     */
    public function testTagReplaceRules()
    {
        $sourceHtml = '<h1>Title</h1>';
        $sourceHtml .= '<div>First paragraph</div>';
        $sourceHtml .= '<div>Second <span>paragraph</span></div>';
        $sourceHtml .= '<footer>Copyright</footer>';

        $targetHtml = '<h3>Title</h3>';
        $targetHtml .= '<p>First paragraph</p>';
        $targetHtml .= '<p>Second <b>paragraph</b></p>';

        $parser = $this->getParser();
        $parser->addTagReplaceRules([
            'h1'     => 'h3',
            'div'    => 'p',
            'span'   => 'b',
            'footer' => false,
        ]);
        $content = $parser->htmlToTelegraphContent($sourceHtml);
        $html = $parser->telegraphContentToHtml($content);

        $this->assertEquals(\trim($targetHtml), \trim($html));
        $this->assertTrue($parser->hasTagReplaceRule('h1'));
        $this->assertFalse($parser->hasTagReplaceRule('h2'));
        $this->assertEquals('h3', $parser->getTagReplaceRule('h1'));
        $this->assertFalse($parser->getTagReplaceRule('footer'));
    }

    /**
     * @throws ParserInvalidTelegraphContentException
     */
    public function testTelegraphContentToHtml()
    {
        $targetHtml = '<h3>Title</h3>';
        $targetHtml .= '<p>First paragraph</p>';
        $targetHtml .= '<p>Link: <a href="https://www.google.com/">Google</a></p>';
        $parser = $this->getParser();
        $content = [
            new NodeElement(['tag' => 'h3', 'children' => ['Title']]),
            new NodeElement(['tag' => 'p', 'children' => ['First paragraph']]),
            new NodeElement([
                'tag'      => 'p',
                'children' => [
                    'Link: ',
                    new NodeElement([
                        'tag'      => 'a',
                        'attrs'    => ['href' => 'https://www.google.com/'],
                        'children' => ['Google']
                    ])
                ]
            ]),
        ];
        $html = $parser->telegraphContentToHtml($content);

        $this->assertEquals(\trim($targetHtml), \trim($html));
    }

    protected function getParser()
    {
        return new Parser();
    }
}
