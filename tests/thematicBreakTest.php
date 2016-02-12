<?php

namespace j11e\markdown\tests;

use j11e\markdown\Parser;

class ThematicBreakTest extends \PHPUnit_Framework_TestCase
{
    public function testCharacters()
    {
        $parser = new Parser();

        $this->assertEquals($parser->parse("***"), "<hr />", "Three stars are a thematic break");
        $this->assertEquals($parser->parse("---"), "<hr />", "Three hypens are a thematic break");
        $this->assertEquals($parser->parse("___"), "<hr />", "Three underscores are a thematic break");

        $this->assertEquals($parser->parse("+++"), "<p>+++</p>", "Three + signs are not a thembreak");
        $this->assertEquals($parser->parse("==="), "<p>===</p>", "Three = signs are not a thembreak");

        $this->assertEquals($parser->parse("*** a"), "<p>*** a</p>", "There cannot be other characters in a thembreak");
        $this->assertEquals($parser->parse("a ***"), "<p>a ***</p>", "There cannot be other characters in a thembreak");
        $this->assertEquals($parser->parse("---a---"), "<p>---a---</p>", "There cannot be other characters in a thembreak");
    }

    public function testSpaces()
    {
        $parser = new Parser();

        // spaces at the beginning of the line
        $this->assertEquals($parser->parse(" ***"), $parser->parse("***"), "There can be up to 3 spaces at the beginning of the line");
        $this->assertEquals($parser->parse("  ***"), $parser->parse("***"), "There can be up to 3 spaces at the beginning of the line");
        $this->assertEquals($parser->parse("   ***"), $parser->parse("***"), "There can be up to 3 spaces at the beginning of the line");
        $this->assertEquals($parser->parse("    ***"), "<pre><code>***\n</code></pre>", "There cannot be 4 spaces at the beginning of the line");

        // spaces at the end of the line
        $this->assertEquals($parser->parse("***    "), "<hr />", "As many spaces as I want at the end of the line");
        $this->assertEquals($parser->parse("*** "), "<hr />", "As many spaces as I want at the end of the line");
        $this->assertEquals($parser->parse("***         "), "<hr />", "As many spaces as I want at the end of the line");
        $this->assertEquals($parser->parse("***          "), "<hr />", "As many spaces as I want at the end of the line");

        // spaces between characters
        $this->assertEquals($parser->parse("* * *"), "<hr />", "As many spaces as I want in between characters");
        $this->assertEquals($parser->parse("*     * *"), "<hr />", "As many spaces as I want in between characters");
        $this->assertEquals($parser->parse("**         *"), "<hr />", "As many spaces as I want in between characters");
        $this->assertEquals($parser->parse("*       * *"), "<hr />", "As many spaces as I want in between characters");
        $this->assertEquals($parser->parse("*       *         *"), "<hr />", "As many spaces as I want in between characters");
        $this->assertEquals($parser->parse("** *"), "<hr />", "As many spaces as I want in between characters");
    }

    public function testMatchingCharacters()
    {
        $parser = new Parser();

        $this->assertEquals($parser->parse("**-"), "<p>**-</p>", "Characters must be the same in a thembreak");
        $this->assertEquals($parser->parse("*-*"), "<p><em>-<em></p>", "Characters must be the same in a thembreak");
    }

    public function testNumberOfCharactersNeeded()
    {
        $parser = new Parser();

        $this->assertEquals($parser->parse("***"), "<hr />", "Three chars is a thembreak");
        $this->assertEquals($parser->parse("*************************"), "<hr />", "More than 3 chars is OK in a thembreak");
        $this->assertEquals($parser->parse("-------------------------"), "<hr />", "More than 3 chars is OK in a thembreak");
        $this->assertEquals($parser->parse("_________________________"), "<hr />", "More than 3 chars is OK in a thembreak");
        $this->assertEquals($parser->parse("**"), "<p>**</p>", "Less than 3 chars is NOT a thembreak");
        $this->assertEquals($parser->parse("--"), "<p>--</p>", "Less than 3 chars is NOT a thembreak");
        $this->assertEquals($parser->parse("__"), "<p>__</p>", "Less than 3 chars is NOT a thembreak");
    }

    public function testDoesNotNeedBlankLine()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("- foo\n***\n- bar"),
            "<ul><li>foo</li></ul>\n<hr />\n<ul><li>bar</li></ul>",
            "Thembreaks do not need blank lines before nor after them"
        );
    }

    public function testPrecedence()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("foo\n***\nbar"),
            "<p>foo</p>\n<hr />\n<p>bar</p>",
            "Thembreaks have precedence over paragraphs"
        );

        $this->assertEquals(
            $parser->parse("Foo\n---\nbar"),
            "<h2>Foo</h2>\n<p>bar</p>",
            "Headlines have precedence over thembreaks"
        );

        $this->assertEquals(
            $parser->parse("- foo\n---\n- bar"),
            "<ul><li>foo</li></ul>\n<hr />\n<ul><li>bar</li></ul>",
            "Thembreaks have precedence over lists"
        );
    }
}
