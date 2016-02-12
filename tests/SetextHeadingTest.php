<?php

namespace j11e\markdown\tests;

use j11e\markdown\Parser;

class SetextHeadingTest extends \PHPUnit_Framework_TestCase
{
    public function testCharacters()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("hello\n==="),
            "<h1>hello</h1>",
            "=s produces h1 Setext headings"
        );
        $this->assertEquals(
            $parser->parse("hello\n---"),
            "<h2>hello</h2>",
            "-s produces h2 Setext headings"
        );
        $this->assertEquals(
            $parser->parse("hello\n="),
            "<h1>hello</h1>",
            "One character is enough"
        );
        $this->assertEquals(
            $parser->parse("hello\n-------------------"),
            "<h2>hello</h2>",
            "No limit to the number of characters"
        );
        $this->assertEquals(
            $parser->parse("hello\n==="),
            "<h1>hello</h1>",
            "=s produces h1 Setext headings"
        );
        $this->assertEquals(
            $parser->parse("hello\n==="),
            "<h1>hello</h1>",
            "=s produces h1 Setext headings"
        );
    }

    public function testSpaces()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("hello\n  ==="),
            "<h1>hello</h1>",
            "Up to three spaces at the beginning of the underlining"
        );
        $this->assertEquals(
            $parser->parse("hello\n   ==="),
            "<h1>hello</h1>",
            "Up to three spaces at the beginning of the underlining"
        );
        $this->assertEquals(
            $parser->parse("hello\n===                 "),
            "<h1>hello</h1>",
            "No limit to spaces after the underlining"
        );
        $this->assertEquals(
            $parser->parse("  hello\n==="),
            "<h1>hello</h1>",
            "Up to three spaces at the beginning of the content"
        );
        $this->assertEquals(
            $parser->parse("   hello\n==="),
            "<h1>hello</h1>",
            "Up to three spaces at the beginning of the content"
        );

        $this->assertEquals(
            $parser->parse("   hello\n ==="),
            "<h1>hello</h1>",
            "The heading and the underlining need not be aligned"
        );
        $this->assertEquals(
            $parser->parse("    hello\n    ---\n\n    Foo\n---"),
            "<pre><code>hello\n---\n\nFoo\n</code></pre>\n<hr />",
            "Four spaces is too much for the content"
        );
        $this->assertEquals(
            $parser->parse("hello\n    ---"),
            "<p>hello\n---</p>",
            "Four spaces is too much for the underlining"
        );

        $this->assertEquals(
            $parser->parse("hello\n= = ="),
            "<p>hello\n= = =</p>",
            "No spaces in between characters of the underlining"
        );

        $this->assertEquals(
            $parser->parse("hello\n-------------- -"),
            "<p>hello</p>\n<hr />",
            "No spaces in between characters of the underlining, ex #2"
        );
    }

    public function testPrecedence()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("hello  \n=="),
            "<h1>hello</h1>",
            "Trailing spaces in the content do not cause a line break"
        );

        $this->assertEquals(
            $parser->parse("hello\\\n=="),
            "<h1>hello\\</h1>",
            "A backslash does not cause a line break"
        );

        $this->assertEquals(
            $parser->parse("`Foo\n-----\n`"),
            "<h2>`Foo</h2>\n<p>`</p>",
            "Setext headings have precedence over inline structure"
        );
        $this->assertEquals(
            $parser->parse("<a title=\"a lot\n-----\nof dashes\"/>"),
            "<h2>&lt;a title=&quot;a lot</h2>\n<p>of dashes&quot;/&gt;</p>",
            "Setext headings have precedence over inline structure, ex #2"
        );
    }

    public function testContent()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("foo\nhello\n---"),
            "<h2>foo\nhello</h2>",
            "Setext headings can span multiple lines, if the content above was a paragraph"
        );

        $this->assertEquals(
            $parser->parse("Foo *bar\nbaz*\n==="),
            "<h1>Foo <em>bar\nbaz</em></h1>",
            "Setext headings' content is parsed as inline content"
        );

        $this->assertEquals(
            $parser->parse("Foo\nBar\n==="),
            "<h1>Foo\nBar</h1>",
            "Setext headings need an empty line between the previous paragraph and them"
        );

        $this->assertEquals(
            $parser->parse("---\nFoo\n---\nBar\n---\nBaz"),
            "<hr />\n<h2>Foo</h2>\n<h2>Bar</h2>\n<p>Baz</p>",
            "Setext headings do not need empty lines between other blocks and them"
        );

        $this->assertEquals(
            $parser->parse("\n==="),
            "<p>===</p>",
            "Setext headings' content cannot be empty"
        );

        $this->assertEquals(
            $parser->parse("---\n---"),
            "<hr />\n<hr />",
            "Setext headings' content can only be paragraphs, not other blocks"
        );

        $this->assertEquals(
            $parser->parse("- foo\n----"),
            "<ul><li>foo</li></ul><hr />",
            "Setext headings' content can only be paragraphs, not other blocks, ex #2"
        );

        $this->assertEquals(
            $parser->parse("    foo\n---"),
            "<pre><code>foo\n</code></pre>\n<hr />",
            "Setext headings' content can only be paragraphs, not other blocks, ex #3"
        );

        $this->assertEquals(
            $parser->parse("> Foo\n---"),
            "<blockquote><p>Foo</p></blockquote>\n<hr />",
            "Setext headings' content can only be paragraphs, not other blocks, ex #4"
        );

        $this->assertEquals(
            $parser->parse("\\> foo\n---"),
            "<2>$gt; foo</h2>",
            "Special characters can be escaped to be used in setext headings"
        );
    }

    public function testCannotBeLazyContinuationLine()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("> Foo\n---"),
            "<blockquote><p>Foo</p></blockquote>\n<hr />",
            "Setext headings' underlining cannot be a lazy continuation line in a blockquote"
        );

        $this->assertEquals(
            $parser->parse("> Foo\nbar\n==="),
            "<blockquote><p>Foo\nbar\n===</p></blockquote>",
            "Setext headings' underlining cannot be a lazy continuation line in a blockquote"
        );

        $this->assertEquals(
            $parser->parse("- Foo\n---"),
            "<ul><li>Foo</li></ul>\n<hr />",
            "Setext headings' underlining cannot be a lazy continuation line in a list"
        );
    }
}
