<?php

namespace j11e\markdown\tests;

use j11e\markdown\Parser;

class ParagraphTest extends \PHPUnit_Framework_TestCase
{
    public function testContent()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("aaa\n\nbbb"),
            "<p>aaa</p>\n<p>bbb</p>",
            "An empty line breaks a paragraph"
        );

        $this->assertEquals(
            $parser->parse("aaa\nbbb\n\nccc\nddd"),
            "<p>aaa\nbbb</p>\n<p>ccc\nddd</p>",
            "An empty line breaks a paragraph, ex #2"
        );

        $this->assertEquals(
            $parser->parse("aaa\n\n\n\nbbb"),
            "<p>aaa</p>\n<p>bbb</p>",
            "Multiple empty lines are useless"
        );

        $this->assertEquals(
            $parser->parse("  aaa\n bbb"),
            "<p>aaa\nbbb</p>",
            "Leading spaces are removed"
        );

        $this->assertEquals(
            $parser->parse("aaa     \nbbb     "),
            "<p>aaa<br />\nbbb</p>",
            "Final spaces are stripped before inline parsing"
        );
    }

    public function testPrecedence()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("aaa\n             bbb\n                             ccc"),
            "<p>aaa\nbbb\nccc</p>",
            "Code blocks cannot interrupt paragraphs"
        );

        $this->assertEquals(
            $parser->parse("    aaa\nbbb"),
            "<pre><code>aaa\n</code></pre>\n<p>bbb</p>",
            "Code blocks still exist though."
        );
    }
}
