<?php

namespace j11e\markdown;

class Parser
{
    use blocks\ThematicBreak;
    use blocks\AtxHeading;
    use blocks\SetextHeading;

    protected $blocksData;

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

        $blockTypes = $this->getBlockTypes();
        $this->blocksData = array();
        $currentParagraph = ['type' => 'paragraph', 'content' => ''];

        $currentIndex = 0;
        while ($currentIndex < count($lines)) {
            $currentType = 'paragraph';
            foreach ($blockTypes as $blockType) {
                $identifyMethod = 'identify' . ucfirst($blockType);
                if ($this->$identifyMethod($lines, $currentIndex)) {
                    $currentType = $blockType;
                    break;
                }
            }

            if ($currentType === 'paragraph') {
                $currentParagraph['content'] .= $lines[$currentIndex];
                $currentIndex++;
            } else {
                if ($currentParagraph['content']) {
                    $this->blocksData[] = $currentParagraph;
                }

                $currentParagraph = ['type' => 'paragraph', 'content' => ''];

                $parserMethod = 'parse' . ucfirst($currentType);
                $block = $this->$parserMethod($lines, $currentIndex);
                $currentIndex = $block['newIndex'];
                $this->blocksData[] = $block;
            }
        }

        if ($currentParagraph['content']) {
            $this->blocksData[] = $currentParagraph;
        }

        $output = '';
        for ($i=0; $i<count($this->blocksData); $i++) {
            $renderMethod = 'render'.ucfirst($this->blocksData[$i]['type']);
            $output .= $this->$renderMethod($this->blocksData[$i]);
        }
        return $output;
    }

    /**
     * pre-parsing processing. For now, simply standardize line endings.
     */
    protected function preprocess($markdownStr)
    {
        $markdownStr = str_replace(['\r\n', '\n\r', '\r'], '\n', $markdownStr);
        return $markdownStr;
    }

    /**
     * parses a single paragraph's content, recursively parsing inline modifiers
     */
    public function renderParagraph($blockData, $depth = 0)
    {
        return '<p>'.$blockData['content'].'</p>';
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
}
