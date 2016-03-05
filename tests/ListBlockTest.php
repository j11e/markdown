<?php

namespace j11e\markdown\tests;

use j11e\markdown\Parser;

class ListBlockTest extends \PHPUnit_Framework_TestCase
{
    public function testBasicCase()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("A paragraph\nwith two lines.\n\n    indented code\n\n> A block quote."),
            "<p>A paragraph\nwith two lines.</p>\n<pre><code>indented code\n</code></pre>\n<blockquote>\n<p>A block quote.</p>\n</blockquote>",
            "Basic case example #1: unordered list"
        );

        $this->assertEquals(
            $parser->parse("1.  A paragraph\n    with two lines.\n\n        indented code\n\n    > A block quote."),
            "<ol>\n<li>\n<p>A paragraph\nwith two lines.</p>\n<pre><code>indented code\n</code></pre>\n<blockquote>\n<p>A block quote.</p>\n</blockquote>\n</li>\n</ol>",
            "Basic case example #2: ordered list"
        );

        $this->assertEquals(
            $parser->parse("-one\n\n2.two"),
            "<p>-one</p>\n<p>2.two</p>",
            "There must be at least one space between the list marker and the content"
        );

        $this->assertEquals(
            $parser->parse("1.  foo\n\n    ```\n    bar\n    ```\n\n    baz\n\n    > bam"),
            "<ol>\n<li>\n<p>foo</p>\n<pre><code>bar\n</code></pre>\n<p>baz</p>\n<blockquote>\n<p>bam</p>\n</blockquote>\n</li>\n</ol>",
            "A list can contain any type of block"
        );
    }

    public function testOrderedListNumbers()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("0. ok"),
            "<ol start=\"0\">\n<li>ok</li>\n</ol>",
            "Ordered list markers can have the number 0"
        );

        $this->assertEquals(
            $parser->parse("003. ok"),
            "<ol start=\"3\">\n<li>ok</li>\n</ol>",
            "Ordered lists markers can have a number start with a 0"
        );

        $this->assertEquals(
            $parser->parse("123456789. ok"),
            "<ol start=\"123456789\">\n<li>ok</li>\n</ol>",
            "Ordered list markers can be nine digits or less"
        );

        $this->assertEquals(
            $parser->parse("1234567890. not ok"),
            "<p>1234567890. not ok</p>",
            "Ordered list markers cannot be more than nine digits"
        );

        $this->assertEquals(
            $parser->parse("-1. not ok"),
            "<p>-1. not ok</p>",
            "Ordered list markers cannot have a negative number"
        );
    }

    public function testIndentation()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("- one\n\n two"),
            "<ul>\n<li>one</li>\n</ul>\n<p>two</p>",
            "Not enough spaces to be included in the list"
        );

        $this->assertEquals(
            $parser->parse("- one\n\n  two"),
            "<ul>\n<li>\n<p>one</p>\n<p>two</p>\n</li>\n</ul>",
            "Just enough spaces to be included in the list"
        );

        $this->assertEquals(
            $parser->parse(" -    one\n\n     two"),
            "<ul>\n<li>one</li>\n</ul>\n<pre><code> two\n</code></pre>",
            "Not enough spaces to be included in the list, ex #2"
        );

        $this->assertEquals(
            $parser->parse(" -    one\n\n      two"),
            "<ul>\n<li>\n<p>one</p>\n<p>two</p>\n</li>\n</ul>",
            "Just enough spaces to be included in the list, ex #2"
        );

        $this->assertEquals(
            $parser->parse("   > > 1.  one\n>>\n>>     two"),
            "<blockquote>\n<blockquote>\n<ol>\n<li>\n<p>one</p>\n<p>two</p>\n</li>\n</ol>\n</blockquote>\n</blockquote>",
            "It's about indentation, not columns"
        );

        $this->assertEquals(
            $parser->parse(">>- one\n>>\n  >  > two"),
            "<blockquote>\n<blockquote>\n<ul>\n<li>one</li>\n</ul>\n<p>two</p>\n</blockquote>\n</blockquote>",
            "It's about indentation, not columns; example #2"
        );
    }

    public function testConsecutiveBlankLines()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("- Foo\n\n      bar\n\n      baz"),
            "<ul>\n<li>\n<p>Foo</p>\n<pre><code>bar\n\nbaz\n</code></pre>\n</li>\n</ul>",
            "One blank line is fine..."
        );

        $this->assertEquals(
            $parser->parse("- Foo\n\n      bar\n\n\n      baz"),
            "<ul>\n<li>\n<p>Foo</p>\n<pre><code>bar\n</code></pre>\n</li>\n</ul>\n<pre><code>  baz\n</code></pre>",
            "Two blank lines interrupt lists"
        );

        $this->assertEquals(
            $parser->parse("- foo\n\n  bar\n\n- foo\n\n\n  bar\n\n- ```\n  foo\n\n\n  bar\n  ```\n\n- baz\n\n  + ```\n    foo\n\n\n    bar\n    ```"),
            "<ul>\n<li>\n<p>foo</p>\n<p>bar</p>\n</li>\n<li>\n<p>foo</p>\n</li>\n</ul>\n<p>bar</p>\n<ul>\n<li>\n<pre><code>foo\n\n\nbar\n</code></pre>\n</li>\n<li>\n<p>baz</p>\n<ul>\n<li>\n<pre><code>foo\n\n\nbar\n</code></pre>\n</li>\n</ul>\n</li>\n</ul>",
            "Two consecutive blank lines interrupt lists... except if they're contained in a *fenced* code block"
        );

    }
}
