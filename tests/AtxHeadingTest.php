<?php

namespace j11e\markdown\tests;

use j11e\markdown\Parser;

class AtxHeadingTest extends \PHPUnit_Framework_TestCase
{
    public function testSpaces()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("## hello"),
            "<h2>hello</h2>",
            "Up to three spaces at the beginning of the line"
        );
        $this->assertEquals(
            $parser->parse(" ## hello"),
            "<h2>hello</h2>",
            "Up to three spaces at the beginning of the line"
        );
        $this->assertEquals(
            $parser->parse("   ## hello"),
            "<h2>hello</h2>",
            "Up to three spaces at the beginning of the line"
        );
        $this->assertEquals(
            $parser->parse("    ## hello"),
            "<pre><code>## hello\n</code></pre>",
            "Up to three spaces at the beginning of the line"
        );

        $this->assertEquals(
            $parser->parse("##hello"),
            "<p>##hello</p>",
            "Need a least a space after #s"
        );

        $this->assertEquals(
            $parser->parse("###"),
            "<h3></h3>",
            "No need for a space for empty headings"
        );

        $this->assertEquals(
            $parser->parse("##\thello"),
            "<p>##\thello</p>",
            "A tab after #s will not do"
        );

        $this->assertEquals(
            $parser->parse("#       hello      "),
            "<h1>hello</h1>",
            "Content is trimmed for spaces both left and right"
        );
    }

    public function testCharacters()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("# hello"),
            "<h1>hello</h1>",
            "Up to six #s is an ATX heading"
        );
        $this->assertEquals(
            $parser->parse("##### hello"),
            "<h5>hello</h5>",
            "Up to six #s is an ATX heading"
        );
        $this->assertEquals(
            $parser->parse("###### hello"),
            "<h6>hello</h6>",
            "Up to six #s is an ATX heading"
        );
        $this->assertEquals(
            $parser->parse("####### hello"),
            "<p>####### hello</p>",
            "Up to six #s is an ATX heading"
        );
        $this->assertEquals(
            $parser->parse("\## hello"),
            "<p>## hello</p>",
            "Escaped #s are no headings"
        );
    }

    public function testClosingCharacters()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("#### hello ##############"),
            "<h4>hello</h4>",
            "Closing #s are ok and unlimited"
        );
        $this->assertEquals(
            $parser->parse("#### hello ##############              "),
            "<h4>hello</h4>",
            "Closing #s can have unlimited spaces after them"
        );
        $this->assertEquals(
            $parser->parse("#### hello ############## a"),
            "<h4>hello ############## a</h4>",
            "Closing #s cannot have other characters"
        );
        $this->assertEquals(
            $parser->parse("#### hello##############"),
            "<h4>hello##############</h4>",
            "Closing #s must be preceded by a space"
        );
        $this->assertEquals(
            $parser->parse("#### hello \####"),
            "<h4>hello ####</h4>",
            "Escaped #s do not count as closing #s"
        );
        $this->assertEquals(
            $parser->parse("#### hello ##\##"),
            "<h4>hello ####</h4>",
            "Escaped #s do not count as closing #s"
        );
    }

    public function testContentIsParsedAsInline()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("# hello *there* \*you\*"),
            "<h1>hello <em>there</em> *you*</h1>",
            "ATX headings' content are parsed as inline content"
        );
    }

    public function testPrecedence()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("Foo\n# bar\nbaz"),
            "<p>Foo</p>\n<h1>bar</h1>\n<p>baz</p>",
            "ATX headings can break paragraphs"
        );

        $this->assertEquals(
            $parser->parse("***\n## lol\n*****"),
            "<hr />\n<h2>lol</h2>\n<hr />",
            "ATX headings do not need blank lines before nor after them"
        );
    }
}
