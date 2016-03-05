<?php

namespace j11e\markdown\blocks;

trait BlockQuote
{
    public function identifyBlockQuote($lines, $currentIndex)
    {
        return preg_match('/^ {0,3}> ?/', $lines[$currentIndex]);
    }

    public function parseBlockQuote($lines, $currentIndex)
    {
        $done = false;
        $newIndex = $currentIndex;
        $le = count($lines);
        $content = [];

        while (!$done && $newIndex < $le) {
            $curLine = $lines[$newIndex];

            if (preg_match('/^ {0,3}> ?(.*)$/', $curLine, $groups)) {
                $content[] = $groups[1];
                $newIndex += 1;
            } else if (ltrim($lines[$newIndex]) !== ''
                && ltrim($content[count($content) - 1]) !== ''
                && $this->checkLazyContinuationLine($lines, $newIndex, $content)) {

                $content[] = $lines[$newIndex];
                $newIndex += 1;
            } else {
                //echo "else...";
                $done = true;
            }
        }

        // remove initial and final blank lines
        while (count($content) > 0 && ltrim($content[0]) === '') {
            array_shift($content);
        }
        while (count($content) > 0 && ltrim($content[count($content) -1]) === '') {
            array_pop($content);
        }

        //echo "finished parsing BlockQuote, content = " . print_r($content, true) . "<br/>";
        return ['newIndex' => $newIndex,
                'type' => 'BlockQuote',
                'content' => $content,
                'leaf' => false,
                ];
    }

    /**
     * checks if $lines[$currentIndex] can be consumed by the current block quote
     * as a lazy continuation line. Recursively, in the case of nested blocks.
     */
    protected function checkLazyContinuationLine($lines, $currentIndex, $currentContent)
    {
        // lazy continuation? if the last block within the block quote
        // is a paragraph, and not a blank line, trick the parser into
        // thinking the current block is a paragraph to check if the
        // current line qualifies as a paragraph continuation
        $canConsume = false;

        $curLine = $lines[$currentIndex];
        $lastLine = $lines[$currentIndex -1];

        $oldParagraph = $this->currentParagraph;

        $currentBlockStruct = $this->parseBlockTree($currentContent);

        $this->currentParagraph[] = "dummy";
        $lastBlock = end($currentBlockStruct);

        if ($lastBlock['type'] === "paragraph"
            && $this->getMatchingBlockType($lines, $currentIndex) === 'paragraph') {
            $canConsume = true;
        } else if ($lastBlock['type'] === 'BlockQuote') {
            // recursively check
            $canConsume = $this->checkLazyContinuationLine($lines, $currentIndex, $lastBlock['content']);
        }

        $this->currentParagraph = $oldParagraph;

        return $canConsume;
    }

    public function renderBlockQuote($blockData)
    {
        $textContent = $this->parse(implode("\n", $blockData['content']));

        if (strlen($textContent) > 0) {
            $textContent = $textContent . "\n";
        }

        return "<blockquote>\n" . $textContent . "</blockquote>";
    }
}
