<?php

namespace j11e\markdown\tests;

use j11e\markdown\Parser;

class BlockQuoteTest extends \PHPUnit_Framework_TestCase
{
    public function testBasicExamples()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("> # Foo\n> bar\n> baz"),
            "<blockquote>\n<h1>Foo</h1>\n<p>bar\nbaz</p>\n</blockquote>",
            "Basic block quote example"
        );

        $this->assertEquals(
            $parser->parse("># Foo\n>bar\n> baz"),
            "<blockquote>\n<h1>Foo</h1>\n<p>bar\nbaz</p>\n</blockquote>",
            "Spaces after the > are unnecessary"
        );

        $this->assertEquals(
            $parser->parse("   > # Foo\n   > bar\n > baz"),
            "<blockquote>\n<h1>Foo</h1>\n<p>bar\nbaz</p>\n</blockquote>",
            "The > can be indented up to three spaces"
        );

        $this->assertEquals(
            $parser->parse(">"),
            "<blockquote>\n</blockquote>",
            "Blocks can be empty"
        );

        $this->assertEquals(
            $parser->parse(">\n>  \n> "),
            "<blockquote>\n</blockquote>",
            "Blocks can be a lot of emptiness"
        );

        $this->assertEquals(
            $parser->parse(">\n> foo\n>  "),
            "<blockquote>\n<p>foo</p>\n</blockquote>",
            "Initial and final blank lines are okay"
        );

        $this->assertEquals(
            $parser->parse("> foo\n\n> bar"),
            "<blockquote>\n<p>foo</p>\n</blockquote>\n<blockquote>\n<p>bar</p>\n</blockquote>",
            "A blank line separates two block quotes"
        );

        $this->assertEquals(
            $parser->parse("> foo\n>\n> bar"),
            "<blockquote>\n<p>foo</p>\n<p>bar</p>\n</blockquote>",
            "Splitting a paragraph within a blockquote works"
        );

        $this->assertEquals(
            $parser->parse(">     code\n\n>    not code"),
            "<blockquote>\n<pre><code>code\n</code></pre>\n</blockquote>\n<blockquote>\n<p>not code</p>\n</blockquote>",
            "Block quote marker matches one space after >, so indented code blocks need 5 spaces!"
        );
    }

    public function testLazyContinuation()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("> # Foo\n> bar\nbaz"),
            "<blockquote>\n<h1>Foo</h1>\n<p>bar\nbaz</p>\n</blockquote>",
            "Lazy continuation example"
        );

        $this->assertEquals(
            $parser->parse("> bar\nbaz\n> foo"),
            "<blockquote>\n<p>bar\nbaz\nfoo</p>\n</blockquote>",
            "Lazy and non-lazy continuations can be mixed"
        );

        $this->assertEquals(
            $parser->parse("> foo\n---"),
            "<blockquote>\n<p>foo</p>\n</blockquote>\n<hr />",
            "Lazy continuation lines must be paragraph continuations, not other blocks"
        );

        $this->assertEquals(
            $parser->parse(">     foo\n    bar"),
            "<blockquote>\n<pre><code>foo\n</code></pre>\n</blockquote>\n<pre><code>bar\n</code></pre>",
            "Indented code blocks cannot be lazy-continuated"
        );

        $this->assertEquals(
            $parser->parse("> ```\nfoo\n```"),
            "<blockquote>\n<pre><code></code></pre>\n</blockquote>\n<p>foo</p>\n<pre><code></code></pre>",
            "The spec is quite thorough regarding what is not an acceptable lazy continuation"
        );

        $this->assertEquals(
            $parser->parse("> foo\n    - bar"),
            "<blockquote>\n<p>foo\n- bar</p>\n</blockquote>",
            "Tricky example of an acceptable lazy continuation"
        );

        $this->assertEquals(
            $parser->parse("> bar\n\nbaz"),
            "<blockquote>\n<p>bar</p>\n</blockquote>\n<p>baz</p>",
            "A blank line after the block quote breaks the lazy continuation"
        );

        $this->assertEquals(
            $parser->parse("> bar\n>\nbaz"),
            "<blockquote>\n<p>bar</p>\n</blockquote>\n<p>baz</p>",
            "A final blank line in the block quote breaks the lazy continuation"
        );

        $this->assertEquals(
            $parser->parse("> > > foo\nbar"),
            "<blockquote>\n<blockquote>\n<blockquote>\n<p>foo\nbar</p>\n</blockquote>\n</blockquote>\n</blockquote>",
            "Lazy continuation works even within nested block quotes"
        );

        $this->assertEquals(
            $parser->parse(">>> foo\n> bar\n>>baz"),
            "<blockquote>\n<blockquote>\n<blockquote>\n<p>foo\nbar\nbaz</p>\n</blockquote>\n</blockquote>\n</blockquote>",
            "Even \"semi-lazy\" continuation works"
        );

        $this->assertEquals(
            $parser->parse("> - foo\n- bar"),
            "<blockquote>\n<ul>\n<li>foo</li>\n</ul>\n</blockquote>\n<ul>\n<li>bar</li>\n</ul>",
            "Lazy continuation lines cannot be non-paragraph blocks, example #2"
        );
    }

    public function testPrecedence()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("    > # Foo\n    > bar\n    > baz"),
            "<pre><code>&gt; # Foo\n&gt; bar\n&gt; baz\n</code></pre>",
            "Indented code blocks have precedence over block quotes"
        );

        $this->assertEquals(
            $parser->parse("foo\n> bar"),
            "<p>foo</p>\n<blockquote>\n<p>bar</p>\n</blockquote>",
            "Block quotes can interrupt paragraphs"
        );

        $this->assertEquals(
            $parser->parse("> aaa\n***\n> bbb"),
            "<blockquote>\n<p>aaa</p>\n</blockquote>\n<hr />\n<blockquote>\n<p>bbb</p>\n</blockquote>",
            "Blank lines are not needed before or after block quotes"
        );
    }
}
