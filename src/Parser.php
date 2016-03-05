<?php

namespace j11e\markdown;

class Parser
{
    use blocks\Paragraph;
    use blocks\ThematicBreak;
    use blocks\AtxHeading;
    use blocks\SetextHeading;
    use blocks\CodeBlock;
    use blocks\HtmlBlock;
    use blocks\LinkRef;
    use blocks\BlockQuote;
    use blocks\ListBlock;

    /**
     * the current "paragraph" block object, to which lines are appended by default
     * if no other blocktype matches.
     * this is an instance property and not a local variable because traits
     * can alter its value for convenience
     */
    protected $currentParagraph;

    /**
     * array of strings representing all the block types supported by the parser.
     * this is an instance property and not a local variable because it won't change,
     * so let's not use reflection every time we parse a line, or move a variable around
     * from method to method...
     */
    protected $blockTypes;

    /**
     * parses the text provided as parameter into HTML output.
     *
     * @param $markdownStr string|array the markdown text to parse, as text or as an array of lines
     *
     * @return string the parsed text, as HTML
     */
    public function parse($markdown)
    {
        if (!is_array($markdown)) {
            $markdown = $this->preprocess($markdown);
            $lines = explode("\n", $markdown);
        } else {
            $lines = $markdown;
        }
        
        $this->currentParagraph = array();
        $this->blockTypes = $this->getBlockTypes();

        $blockTree = $this->parseBlockTree($lines);

        $output = $this->renderBlockTree($blockTree);

        return $output;
    }

    /**
     * given the array of lines and the index of the current line in the array,
     * returns the block type matching this line. This function has an "hidden input":
     * $this->currentParagraph!
     *
     * @param lines array[string] the entier text to parse, split as lines
     * @param index integer the index of the current line
     *
     * @return string the blocktype
     */
    protected function getMatchingBlockType($lines, $index)
    {
        $type = 'paragraph';
        foreach ($this->blockTypes as $blockType) {
            $identifyMethod = 'identify' . ucfirst($blockType);
            if ($this->$identifyMethod($lines, $index)) {
                $type = $blockType;
                break;
            }
        }

        return $type;
    }

    /**
     * pre-parsing processing. For now, simply standardize line endings.
     */
    protected function preprocess($markdownStr)
    {
        $markdownStr = str_replace(["\r\n", "\n\r", "\r"], "\n", $markdownStr);
        return $markdownStr;
    }

    /**
     * handles the parsing of the block tree, the first step of parse().
     *
     * @param array[string] the entire text to parse, split as lines
     *
     * @return array[array] the block structure of the text, as a tree of associative arrays
     */
    protected function parseBlockTree($lines)
    {
        $blockTree = array();

        $currentIndex = 0;
        while ($currentIndex < count($lines)) {
            // empty lines just interrupt the current paragraph
            if ($lines[$currentIndex] === '') {
                if ($this->currentParagraph) {
                    $blockTree[] = $this->parseParagraph($this->currentParagraph);
                    $this->currentParagraph = array();
                }
                $currentIndex++;
            } else {
                $currentType = $this->getMatchingBlockType($lines, $currentIndex);

                if ($currentType === 'paragraph') {
                    $this->currentParagraph[] = ltrim(rtrim($lines[$currentIndex]));
                    $currentIndex++;
                } else {
                    $parseMethod = 'parse' . ucfirst($currentType);

                    $block = $this->$parseMethod($lines, $currentIndex);
                    $currentIndex = $block['newIndex'];

                    if (count($this->currentParagraph)) {
                        $blockTree[] = $this->parseParagraph($this->currentParagraph);
                        $this->currentParagraph = array();
                    }

                    $blockTree[] = $block;
                }
            }
        }

        // when we're done inspecting all the lines, if we had a paragraph going,
        // don't forget it
        if ($this->currentParagraph) {
            $blockTree[] = $this->parseParagraph($this->currentParagraph);
            $this->currentParagraph = array();
        }

        return $blockTree;
    }

    /**
     * Handles the parsing of inline elements, the 2nd step of parse().
     *
     * @param array[array] the block tree which is going to be walked, parsing inline elements within the text leaf nodes.
     *
     * @return array[array] the resulting block tree with inline elements parsed.
     *
     * @todo the description above has not yet been implemented :)
     */
    public function parseInline($inlineContent)
    {
        foreach ($this->getInlineParsers() as $inlineParsingMethod) {
            $inlineContent = $this->$inlineParsingMethod($inlineContent);
        }

        $eob = preg_quote('\<');
        $ecb = preg_quote('\>');

        $inlineContent = preg_replace_callback("/^((".$eob."|[^<])*)/", function ($match) {
            return htmlentities($match[0]);
        }, $inlineContent);

        $inlineContent = preg_replace_callback("/(?<=([^\\\]>))((".$eob."|[^<])*)(?=([^\\\]<))/", function ($match) {
            return htmlentities($match[0]);
        }, $inlineContent);

        $inlineContent = preg_replace_callback("/(?<=[^\\\]>)((".$ecb."|[^>])*)$/", function ($match) {
            return htmlentities($match[0]);
        }, $inlineContent);

        // $inlineContent = preg_replace('/\\\([^a-zA-Z0-9])/', "$1", $inlineContent);
        $inlineContent = preg_replace('/( {2,}|\\\)$/', '<br />', $inlineContent);

        return $inlineContent;
    }

    /**
     * handles the rendering of the block tree, the third step of parse(). The
     * block tree parameter can be a single block.
     *
     * @param array[array] the block tree to render, as an array of associative arrays (blocks)
     *
     * @return string the rendered tree as HTML
     */
    public function renderBlockTree($blockTree)
    {
        $output = [];

        if (array_key_exists('type', $blockTree)) {
            $renderMethod = 'render'.ucfirst($blockTree['type']);
            $rendered = $this->$renderMethod($blockTree);

            if ($rendered) {
                // blocks can render to an empty string; these don't count
                $output[] = $rendered;
            }
        } else {
            for ($i=0; $i<count($blockTree); $i++) {
                $output[] = $this->renderBlockTree($blockTree[$i]);
            }
        }

        // for legibility, each rendered block is separated from the previous one by a \n
        $output = implode("\n", $output);

        return $output;
    }

    /**
     * utility function for getBlockTypes and getInlineParsers
     */
    private function getAllMethodNames()
    {
        $reflectSelf = new \ReflectionClass($this);

        // get all methods, keep only names
        return array_map(function ($method) {
            return $method->getName();
        }, $reflectSelf->getMethods());
    }

    /**
     * use reflection to retrieve all the block types from the traits used
     */
    public function getBlockTypes()
    {
        $allMethodsName = $this->getAllMethodNames();

        // filter on names that start with identify
        $blockTypes = array_filter($allMethodsName, function ($name) {
            return strncmp($name, 'identify', 8) === 0;
        });

        // only keep the types, remove "identify"
        $blockTypes = array_map(function ($name) {
            return substr($name, 8);
        }, $blockTypes);

        return $blockTypes;
    }

    /**
     * use reflection to retrieve all the inline handling functions from the traits used
     */
    public function getInlineParsers()
    {
        $allMethodsName = $this->getAllMethodNames();

        return array_filter($allMethodsName, function ($name) {
            return strncmp($name, "inlineParse", 11) === 0;
        });
    }
}
