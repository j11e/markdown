<?php

namespace j11e\markdown\blocks;

trait SetextHeading
{
    public function identifySetextHeading($lines, $currentIndex)
    {
        if ($currentIndex === count($lines)-1) {
            return false;
        }

        if (preg_match('/^ {0,3}([-=])\1* *$/', $lines[$currentIndex+1])) {
            // this could be a setext heading, but ONLY IF the current line is
            // not a block; so make next line (the underlining) an empty line,
            // parse the current line, and see if it comes back as a paragraph
            // or something else
            $oldLine = $lines[$currentIndex+1];
            $lines[$currentIndex+1] = '';

            $curLineType = $this->getMatchingBlockType($lines, $currentIndex);

            $lines[$currentIndex+1] = $oldLine;

            if ($curLineType === 'paragraph') {
                return true;
            }
        }
        return false;
    }

    public function parseSetextHeading($lines, $currentIndex)
    {
        $level = ltrim($lines[$currentIndex+1])[0] === '=' ? 1 : 2;

        $content = ltrim(rtrim($lines[$currentIndex]));

        // a setext heading can be a whole paragraph
        // so I only detect it on the paragraph's last line,
        // then consume the whole paragraph
        $lastBlock = end($this->blocksData);
        reset($this->blocksData);
        if ($lastBlock['type'] === 'paragraph') {
            $content = $lastBlock['content'] . "\n" . $content;
            array_pop($this->blocksData);
        }

        return ['newIndex' => $currentIndex+2,
                'type' => 'SetextHeading',
                'level' => $level,
                'content' => $content,
                'leaf' => true,
                ];
    }

    public function renderSetextHeading($blockData)
    {
        return '<h'.$blockData['level'].'>'.$this->parseInline($blockData['content']).'</h'.$blockData['level'].'>';
    }
}
