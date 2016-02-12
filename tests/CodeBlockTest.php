<?php

namespace j11e\markdown\tests;

use j11e\markdown\Parser;

class CodeBlockTest extends \PHPUnit_Framework_TestCase
{

    public function testIndentedBlockSpaces()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("    a simple\n      indented code block"),
            "<pre><code>a simple\n  indented code block\n</code></pre>",
            "Four spaces is an indented code block"
        );

        $this->assertEquals(
            $parser->parse("    chunk1\n       \n       chunk2"),
            "<pre><code>chunk1\n   \n   chunk2\n</code></pre>",
            "Spaces after the fourth are not removed, even on otherwise empty lines"
        );

        $this->assertEquals(
            $parser->parse("       foo\n    bar"),
            "<pre><code>   foo\nbar\n</code></pre>",
            "The first line can be indented more than four spaces"
        );
    }

    public function testIndentedBlockContent()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("    <a/>\n    *foo*\n\n    - one"),
            "<pre><code>&lt;a/&gt;\n*foo*\n\n- one\n</code></pre>",
            "Code block content is litteral, unparsed text"
        );

        $this->assertEquals(
            $parser->parse("    chunk1\n\n    chunk2\n  \n \n \n    chunk3"),
            "<pre><code>chunk1\n\nchunk2\n\n\n\nchunk3\n</code></pre>",
            "Non-empty lines continue the code block"
        );

        $this->assertEquals(
            $parser->parse("    \n    foo\n    "),
            "<pre><code>foo\n</code></pre>",
            "Blank lines preceding or following the indented code blocks are not included in it"
        );

        $this->assertEquals(
            $parser->parse("    foo  "),
            "<pre><code>foo  \n</code></pre>",
            "Trailing spaces kept in the content"
        );
    }

    public function testIndentedBlockPrecedence()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("salut\n    mec"),
            "<p>salut\nmec</p>",
            "Code blocks cannot break paragraphs"
        );

        $this->assertEquals(
            $parser->parse("    salut\nmec"),
            "<pre><code>salut\n</code></pre>\n<p>mec</p>",
            "Paragraph can, however, interrupt code blocks"
        );

        $this->assertEquals(
            $parser->parse("  - foo\n\n    bar"),
            "<ul><li><p>foo</p><p>bar</p></li></ul>",
            "Lists have precedence over code blocks"
        );

        $this->assertEquals(
            $parser->parse("1.  foo\n\n    - bar"),
            "<ol><li><p>foo</p><ul><li>bar</li></ul></li></ol>",
            "Even nested lists have precedence over code blocks"
        );

        $this->assertEquals(
            $parser->parse("# Heading\n    foo\nHeading\n-------\n    foo\n----"),
            "<h1>Heading</h1>\n<pre><code>foo\n</code></pre>\n<h2>Heading</h2>\n<pre><code>foo\n</code></pre>\n<hr />",
            "Code blocks can occur immediately before and after other blocks"
        );
    }

    public function testFencedBlockCharacters()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("```\n<\n >\n```"),
            "<pre><code>&lt;\n &gt;\n</code></pre>",
            "Basic fenced code block with backticks"
        );

        $this->assertEquals(
            $parser->parse("~~~\n<\n >\n~~~"),
            "<pre><code>&lt;\n &gt;\n</code></pre>",
            "Basic fenced code block with tildes"
        );

        $this->assertEquals(
            $parser->parse("```\naaa\n~~~\n```"),
            "<pre><code>aaa\n~~~\n</code></pre>",
            "The closing fence must use the same characters as the opening"
        );

        $this->assertEquals(
            $parser->parse("~~~\naaa\n```\n~~~"),
            "<pre><code>aaa\n```\n</code></pre>",
            "The closing fence must use the same characters as the opening 2"
        );

        $this->assertEquals(
            $parser->parse("````\naaa\n```\n````"),
            "<pre><code>aaa\n```\n</code></pre>",
            "The closing fence must be at least as long as the opening"
        );

        $this->assertEquals(
            $parser->parse("~~~~\naaa\n~~~\n~~~~"),
            "<pre><code>aaa\n~~~\n</code></pre>",
            "The closing fence must be at least as long as the opening 2"
        );
    }

    public function testFencedBlockSpaces()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse(" ```\n aaa\nbbb\n```"),
            "<pre><code>aaa\nbbb\n</code></pre>",
            "In the fence is indented, content's indentation is removed if present"
        );

        $this->assertEquals(
            $parser->parse("  ```\n aaa\n  bbb\n   ccc\n```"),
            "<pre><code>aaa\nbbb\n ccc\n</code></pre>",
            "In the fence is indented, content's indentation is removed if present, but not more than the fence's indentation"
        );

        $this->assertEquals(
            $parser->parse(" ```\n aaa\nbbb\n   ```"),
            "<pre><code>aaa\nbbb\n</code></pre>",
            "The closing fence can be indented up to 3 spaces, no matter the opening fence's indentation"
        );

        $this->assertEquals(
            $parser->parse("```\naaa\nbbb\n    ```"),
            "<pre><code>aaa\nbbb\n    ```\n</code></pre>",
            "The closing fence cannot be indented by more than 3 spaces"
        );

        $this->assertEquals(
            $parser->parse("``` ```\n aaa"),
            "<p><code></code>\naaa</p>",
            "Opening fences cannot contain spaces"
        );

        $this->assertEquals(
            $parser->parse("~~~\naaa\n~~~ ~~~"),
            "<pre><code>aaa\n~~~ ~~~\n</code></pre>",
            "Closing fences cannot contain spaces"
        );


    }

    public function testFencedBlockContent()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("```\naaa\nbbb"),
            "<pre><code>aaa\nbbb\n</code></pre>",
            "Unclosed code blocks are closed at the end of the document"
        );

        $this->assertEquals(
            $parser->parse("> ```\n> aaa\n\nbbb"),
            "<blockquote><pre><code>aaa\n</code></pre></blockquote>\n<p>bbb</p>",
            "Unclosed code blocks within a block quote end at the end of the quote"
        );

        $this->assertEquals(
            $parser->parse("```\n\n\n```"),
            "<pre><code>\n\n</code></pre>",
            "Code blocks can have all empty lines as content"
        );

        $this->assertEquals(
            $parser->parse("```\n```"),
            "<pre><code></code></pre>",
            "Code blocks can be empty"
        );
    }

    public function testFencedBlockPrecedence()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("    ```\n    aaa\n    ```"),
            "<pre><code>```\naaa\n```\n</code></pre>",
            "Indented code blocks have precedence over fenced ones"
        );

        $this->assertEquals(
            $parser->parse("foo\n```\nbar\n```\nbaz"),
            "<p>foo</p>\n<pre><code>bar\n</code></pre>\n<p>baz</p>",
            "No need for blank lines before or after a paragraph"
        );

        $this->assertEquals(
            $parser->parse("foo\n---\n~~~\nbar\n~~~\n# baz"),
            "<h2>foo</h2>\n<pre><code>bar\n</code></pre>\n<h1>baz</h1>",
            "Other blocks do not need a blank line after or before"
        );
    }

    public function testInfoString()
    {
        $parser = new Parser();

        $this->assertEquals(
            $parser->parse("```python\ndef foo(x):\n  return 4\n```"),
            "<pre><code class=\"language-python\">def foo(x):\n  return 4\n</code></pre>",
            "The first word of the info string is used as class attribute for code element"
        );

        $this->assertEquals(
            $parser->parse("~~~ python startline=4 etc   \ndef foo(x):\n  return 4\n~~~"),
            "<pre><code class=\"language-python\">def foo(x):\n  return 4\n</code></pre>",
            "Only the first word of the info string is used, stripped from spaces"
        );

        $this->assertEquals(
            $parser->parse("````;\n````"),
            "<pre><code class=\"language-;\"></code></pre>",
            "Info strings are not smart"
        );

        $this->assertEquals(
            $parser->parse("``` aa ```\nfoo"),
            "<p><code>aa</code>\nfoo</p>",
            "Info strings cannot contain backticks if the fence is composed of backticks"
        );

        $this->assertEquals(
            $parser->parse("```\n``` aaa\n```"),
            "<pre><code>``` aaa\n</code></pre>",
            "Closing fences cannot have info strings"
        );
    }
}
