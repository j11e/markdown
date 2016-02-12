<?php

namespace j11e\markdown\tests;

use j11e\markdown\Parser;

class HtmlBlockTest extends \PHPUnit_Framework_TestCase
{
    public function testContent()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("<table>\n  <tr>\n    <td>\n           hi\n    </td>\n  </tr>\n</table>\n\nokay.\n"),
            "<table>\n  <tr>\n    <td>\n           hi\n    </td>\n  </tr>\n</table>\n<p>okay.</p>",
            "Type 6 HTML block example"
        );

        $this->assertEquals(
            $parser->parse(" <div>\n  *hello*\n         <foo><a>"),
            " <div>\n  *hello*\n         <foo><a>",
            "Other type 6 HTML block example"
        );

        $this->assertEquals(
            $parser->parse("</div>\n*foo*"),
            "</div>\n*foo*",
            "An HTML block can start with a closing tag"
        );

        $this->assertEquals(
            $parser->parse("<div></div>\n``` c\nint x = 33;\n```"),
            "<div></div>\n``` c\nint x = 33;\n```",
            "HTML blocks continue until a blank line, or the end of the document"
        );

        $this->assertEquals(
            $parser->parse("<style\n  type=\"text/css\">\n\nfoo"),
            "<style\n  type=\"text/css\">\n\nfoo",
            "Even type-1 HTML blocks go 'till the end of the document"
        );

        $this->assertEquals(
            $parser->parse("> <div>\n> foo\n\nbar"),
            "<blockquote>\n<div>\nfoo\n</blockquote>\n<p>bar</p>",
            "When HTML blocks are nested within a block, they go until the end of the parent block"
        );

        $this->assertEquals(
            $parser->parse("- <div>\n- foo"),
            "<ul>\n<li>\n<div>\n</li>\n<li>foo</li>\n</ul>",
            "When HTML blocks are nested within a block, they go until the end of the parent block, ex #2"
        );
    }

    public function testTags()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("<div id=\"foo\"\n  class=\"bar\">\n</div>"),
            "<div id=\"foo\"\n  class=\"bar\">\n</div>",
            "Opening tag can be partial, as long as it is split where there can be whitespace"
        );

        $this->assertEquals(
            $parser->parse("<div id=\"foo\" class=\"bar\n  baz\">\n</div>"),
            "<div id=\"foo\" class=\"bar\n  baz\">\n</div>",
            "Opening tags can be partial, as long as they're split where there can be whitespace"
        );

        $this->assertEquals(
            $parser->parse("<div>\n*foo*\n\n*bar*"),
            "<div>\n*foo*\n<p><em>bar</em></p>",
            "Open tags need not be closed"
        );

        $this->assertEquals(
            $parser->parse("<style>p{color:red;}</style>\n*foo*"),
            "<style>p{color:red;}</style>\n<p><em>foo</em></p>",
            "End tag can occur on the first line"
        );

        $this->assertEquals(
            $parser->parse("<!-- foo -->*bar*\n*baz*"),
            "<!-- foo -->*bar*\n<p><em>baz</em></p>",
            "Anything after the closing tag, on the last line, is still included in the HTML block"
        );

        $this->assertEquals(
            $parser->parse("<del>\n*foo*\n</del>"),
            "<del>\n*foo*\n</del>",
            "Three ways to use HTML: one block with del tags"
        );

        $this->assertEquals(
            $parser->parse("<del>\n\n*foo*\n\n</del>"),
            "<del>\n\n<p><em>foo</em></p>*\n\n</del>",
            "Three ways to use HTML: two HTML blocks - one per del tag - plus a paragraph in between"
        );

        $this->assertEquals(
            $parser->parse("<del>*foo*</del>"),
            "<p><del><em>foo</em></del></p>",
            "Three ways to use HTML: inline HTML"
        );
    }

    public function testHtmlCorrectness()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("<div class\nfoo"),
            "<div class\nfoo",
            "Garbage in, garbage out."
        );

        $this->assertEquals(
            $parser->parse("<div id=\"foo\"\n*hi*"),
            "<div id=\"foo\"\n*hi*",
            "Garbage in, garbage out, 2nd example."
        );

        $this->assertEquals(
            $parser->parse("<div *???-&&-&<----\n*foo*"),
            "<div *???-&&-&<----\n*foo*",
            "Tags don't need to be valid, just to start properly"
        );
    }

    public function testSpecificBlockTypes()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("<pre language=\"haskell\"><code>\nimport Text.HTML.TagSoup\n\nmain :: IO ()\nmain = print $ parseTags tags\n</code></pre>"),
            "<pre language=\"haskell\"><code>\nimport Text.HTML.TagSoup\n\nmain :: IO ()\nmain = print $ parseTags tags\n</code></pre>",
            "Type-1 blocks can contain blank lines"
        );

        $this->assertEquals(
            $parser->parse("<script type=\"text/javascript\">\n// JavaScript example\n\ndocument.getElementById(\"demo\").innerHTML = \"Hello JavaScript!\";\n</script>"),
            "<script type=\"text/javascript\">\n// JavaScript example\n\ndocument.getElementById(\"demo\").innerHTML = \"Hello JavaScript!\";\n</script>",
            "Type-1 blocks can contain blank lines, ex #2"
        );

        $this->assertEquals(
            $parser->parse("<style\n  type=\"text/css\">\nh1 {color:red;}\n\np {color:blue;}\n</style>"),
            "<style\n  type=\"text/css\">\nh1 {color:red;}\n\np {color:blue;}\n</style>",
            "Type-1 blocks can contain blank lines, ex #3"
        );

        $this->assertEquals(
            $parser->parse("<!-- Foo\n\nbar\n   baz -->"),
            "<!-- Foo\n\nbar\n   baz -->",
            "Type-2 block: a comment"
        );

        $this->assertEquals(
            $parser->parse("<?php\n\n  echo '>';\n?>"),
            "<?php\n\n  echo '>';\n?>",
            "Type-3 block: a processing instruction"
        );

        $this->assertEquals(
            $parser->parse("<!DOCTYPE html>"),
            "<!DOCTYPE html>",
            "Type-4 block: a declaration"
        );

        $this->assertEquals(
            $parser->parse("<![CDATA[\nfunction matchwo(a,b)\n{\n  if (a < b && a < 0) then {\n    return 1;\n\n  } else {\n\n    return 0;\n  }\n}\n]]>"),
            "<![CDATA[\nfunction matchwo(a,b)\n{\n  if (a < b && a < 0) then {\n    return 1;\n\n  } else {\n\n    return 0;\n  }\n}\n]]>",
            "Type-5 block: CDATA"
        );

        $this->assertEquals(
            $parser->parse("<div><a href=\"bar\">*foo*</a></div>"),
            "<div><a href=\"bar\">*foo*</a></div>",
            "Type-6 blocks' opening tags need not be alone on their line"
        );

        $this->assertEquals(
            $parser->parse("<table><tr><td>\nfoo\n</td></tr></table>"),
            "<table><tr><td>\nfoo\n</td></tr></table>",
            "Type-6 blocks' opening tags need not be alone on their line, #2"
        );

        $this->assertEquals(
            $parser->parse("<a href=\"foo\">\n*bar*\n</a>"),
            "<a href=\"foo\">\n*bar*\n</a>",
            "Type-7 blocks' opening tags must be alone on its line and be complete"
        );

        $this->assertEquals(
            $parser->parse("<Warning>\n*bar*\n</Warning>"),
            "<Warning>\n*bar*\n</Warning>",
            "Type-7 blocks' opening tags can be anything"
        );

        $this->assertEquals(
            $parser->parse("<i class=\"foo\">\n*bar*\n</i>"),
            "<i class=\"foo\">\n*bar*\n</i>",
            "Type-7 blocks' opening tags can be anything, example #2"
        );

        $this->assertEquals(
            $parser->parse("</ins>\n*bar*"),
            "</ins>\n*bar*",
            "Type-7 blocks' opening tags can be anything, example #3"
        );
    }

    public function testPrecedence()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("Foo\n<div>\nbar\n</div>"),
            "<p>Foo</p>\n<div>\nbar\n</div>",
            "HTLM blocks type 1 to 6 can interrupt a paragraph"
        );

        $this->assertEquals(
            $parser->parse("<DIV CLASS=\"foo\">\n\n*Markdown*\n\n</DIV>"),
            "<DIV CLASS=\"foo\">\n\n<p><em>Markdown</em></p>\n</DIV>",
            "Empty lines break HTML blocks"
        );
    }

    public function testSpaces()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("  <!-- foo -->\n\n    <!-- foo -->"),
            "  <!-- foo -->\n<pre><code>&lt;!-- foo --&gt;\n</code></pre>",
            "Opening tags can be indented up to 3 spaces, no more"
        );
    }
}
