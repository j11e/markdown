<?php

namespace j11e\markdown;

class Parser
{
    use blocks\ThematicBreak;
    use blocks\AtxHeading;
    use blocks\SetextHeading;
    use blocks\CodeBlock;

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

        $output = '';
        for ($i=0; $i<count($this->blocksData); $i++) {
            $renderMethod = 'render'.ucfirst($this->blocksData[$i]['type']);
            $output .= $this->$renderMethod($this->blocksData[$i]);
        }
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

    public function parseParagraph($contentLines)
    {
        $content = implode("\n", $contentLines);
        return ["type" => "paragraph", "content" => $content];
    }

    public function renderParagraph($blockData, $depth = 0)
    {
        return '<p>'.$this->parseInline($blockData['content']).'</p>';
    }

    /**
     * use reflection to retrieve all the block types from the traits used
     */
    public function getBlockTypes()
    {
        $reflectSelf = new \ReflectionClass($this);

        // get all methods, keep only names
        $allMethodsName = array_map(function ($method) {
            return $method->getName();
        }, $reflectSelf->getMethods());

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
     * Parses inline modifiers inside a block. Also handles escaped markdown characters,
     * and use htmlspecialchars on the text
     */
    public function parseInline($inlineContent)
    {
        // TODO, obviously
        return htmlspecialchars($inlineContent);
    }
}
