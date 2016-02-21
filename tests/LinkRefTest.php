<?php

namespace j11e\markdown\tests;

use j11e\markdown\Parser;

class LinkRefTest extends \PHPUnit_Framework_TestCase
{
    public function testSpaces()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("## hello"),
            "<h2>hello</h2>",
            "Up to three spaces at the beginning of the line"
        );
    }

    public function testContent()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("[foo]: /url \"title\"\n\n[foo]"),
            "<p><a href=\"/url\" title=\"title\">foo</a></p>",
            "Basic link example, on one line"
        );

        $this->assertEquals(
            $parser->parse("   [foo2]: \n      /url  \n           'the title'  \n\n[foo2]"),
            "<p><a href=\"/url\" title=\"the title\">foo2</a></p>",
            "Basic link example, on multiple lines"
        );

        $this->assertEquals(
            $parser->parse("[Foo*bar\]]:my_(url) 'title (with parens)'\n\n[Foo*bar\]]"),
            "<p><a href=\"my_(url)\" title=\"title (with parens)\">Foo*bar]</a></p>",
            "Title with parentheses"
        );

        $this->assertEquals(
            $parser->parse("[Foo bar]:\n<my%20url>\n'title'\n\n[Foo bar]"),
            "<p><a href=\"my%20url\" title=\"title\">Foo bar</a></p>",
            "Basic link with angle brackets, multiline"
        );

        $this->assertEquals(
            $parser->parse("[foo3]:\n\n[foo3]"),
            "<p>[foo3]:</p>\n<p>[foo3]</p>",
            "Link definitions must have a destination"
        );

        $this->assertEquals(
            $parser->parse("[foo4]: /url\bar\*baz \"foo\\\"bar\baz\"\n\n[foo4]"),
            "<p><a href=\"/url\\bar*baz\" title=\"foo&quot;bar\baz\">foo4</a></p>",
            "Both title and destination can contain backslash escapes and litteral backslashes"
        );
    }

    public function testTitle()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("[foo]: /url '\ntitle\nline1\nline2\n'\n\n[foo]"),
            "<p><a href=\"/url\" title=\"\ntitle\nline1\nline2\n\">foo</a></p>",
            "Link def. titles can extend over multiple lines"
        );

        $this->assertEquals(
            $parser->parse("[foo2]: /url 'title\n\nwith blank line'\n\n[foo2]"),
            "<p>[foo2]: /url 'title</p>\n<p>with blank line'</p>\n<p>[foo2]</p>",
            "Link def. titles cannot contain blank lines"
        );

        $this->assertEquals(
            $parser->parse("[foo3]:\n/url\n\n[foo3]"),
            "<p><a href=\"/url\">foo3</a></p>",
            "Link def. title can be omitted"
        );

        $this->assertEquals(
            $parser->parse("[foo4]: /url \"title\" ok"),
            "<p>[foo4]: /url &quot;title&quot; ok</p>",
            "Only whitespace is accepted after a link def.'s title"
        );

        $this->assertEquals(
            $parser->parse("[foo5]: /url\n\"title\" ok"),
            "<p>&quot;title&quot; ok</p>",
            "If the title is invalid but on another line, then it's just considered a paragraph"
        );
    }

    public function testLinkRegistry()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("[foo]\n\n[foo]: url"),
            "<p><a href=\"url\">foo</a></p>",
            "A link can come before its definition"
        );

        $this->assertEquals(
            $parser->parse("[foo2]\n\n[foo2]: first\n[foo2]: second"),
            "<p><a href=\"first\">foo2</a></p>",
            "If a link is defined more than once, the first definition is kept"
        );

        $this->assertEquals(
            $parser->parse("[FOO3]: /url\n\n[Foo3]"),
            "<p><a href=\"/url\">Foo3</a></p>",
            "Link registry's labels are case insensitive"
        );

        $this->assertEquals(
            $parser->parse("[ΑΓΩ]: /φου\n\n[αγω]"),
            "<p><a href=\"/%CF%86%CE%BF%CF%85\">αγω</a></p>",
            "Link registry's labels are case insensitive, even in unicode"
        );

        $this->assertEquals(
            $parser->parse("[foo4]\n\n> [foo4]: /url"),
            "<p><a href=\"/url\">foo4</a></p>\n<blockquote>\n</blockquote>",
            "Link definitions can occur inside block containers, and affect the whole document"
        );
    }

    public function testPrecedence()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("    [foo]: /url \"title\"\n\n[foo]"),
            "<pre><code>[foo]: /url &quot;title&quot;\n</code></pre>\n<p>[foo]</p>",
            "Indented code blocks have precedence over link definitions"
        );

        $this->assertEquals(
            $parser->parse("```\n[foo2]: /url\n```\n\n[foo2]"),
            "<pre><code>[foo2]: /url\n</code></pre>\n<p>[foo2]</p>",
            "Fenced code blocks have precedence over link definitions"
        );

        $this->assertEquals(
            $parser->parse("Foo\n[bar]: /baz\n\n[bar]"),
            "<p>Foo\n[bar]: /baz</p>\n<p>[bar]</p>",
            "Link definitions cannot interrupt a paragraph"
        );

        $this->assertEquals(
            $parser->parse("[foo4]: /foo-url \"foo\"\n[bar2]: /bar-url\n  \"bar\"\n[baz]: /baz-url\n\n[foo4],\n[bar2],\n[baz]"),
            "<p><a href=\"/foo-url\" title=\"foo\">foo4</a>,\n<a href=\"/bar-url\" title=\"bar\">bar2</a>,\n<a href=\"/baz-url\">baz</a></p>",
            "Multiple link definitions can occur without blank lines in between"
        );

        $this->assertEquals(
            $parser->parse("# [Foo3]\n[foo3]: /url\n> bar"),
            "<h1><a href=\"/url\">Foo3</a></h1>\n<blockquote>\n<p>bar</p>\n</blockquote>",
            "Link definitions have precedence over other blocks and don't need blank lines after"
        );
    }
}
