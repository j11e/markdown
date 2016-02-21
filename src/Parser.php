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

    protected $blockTypes;

    protected $blocksData;

    protected $currentParagraph;

    /**
     * parses the text provided as parameter into HTML output.
     *
     * @param $markdownStr string the markdown text to parse
     *
     * @return string the parsed text, as HTML
     */
    public function parse($markdownStr)
    {
        $markdownStr = $this->preprocess($markdownStr);
        $lines = explode("\n", $markdownStr);
        $this->blockTypes = $this->getBlockTypes();
        $this->blocksData = array();

        $this->currentParagraph = array();

        $currentIndex = 0;
        while ($currentIndex < count($lines)) {
            // empty lines just interrupt the current paragraph
            if ($lines[$currentIndex] === '') {
                if ($this->currentParagraph) {
                    $this->blocksData[] = $this->parseParagraph($this->currentParagraph);
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
                        $this->blocksData[] = $this->parseParagraph($this->currentParagraph);
                        $this->currentParagraph = array();
                    }

                    $this->blocksData[] = $block;
                }
            }
        }

        // when we're done inspecting all the lines, if we had a paragraph going,
        // don't forget it
        if ($this->currentParagraph) {
            $this->blocksData[] = $this->parseParagraph($this->currentParagraph);
        }

        $output = [];
        for ($i=0; $i<count($this->blocksData); $i++) {
            $renderMethod = 'render'.ucfirst($this->blocksData[$i]['type']);
            $rendered = $this->$renderMethod($this->blocksData[$i]);

            if ($rendered) {
                // blocks can render to an empty string; these don't count
                $output[] = $rendered;
            }
        }
        // for legibility, each block is separated from the previous one by a \n
        $output = implode("\n", $output);

        return $output;
    }

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

    /**
     * Parses inline modifiers inside a block
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

        return $inlineContent;
    }
}
