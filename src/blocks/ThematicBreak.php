<?php

namespace j11e\markdown\blocks;

trait ThematicBreak
{
    /**
     * A line consisting of 0-3 spaces of indentation, followed by a sequence
     * of three or more matching -, _, or * characters, each followed optionally
     * by any number of spaces, forms a thematic break.
     */
    public function identifyThematicBreak($lines, $currentIndex)
    {
        $curLine = $lines[$currentIndex];
        return preg_match('/^ {0,3}([\*\-\_])( *\1){2,} *$/', $curLine);
    }

    public function parseThematicBreak($lines, $currentIndex)
    {
        return ['newIndex' => $currentIndex+1,
                'type' => 'thematicBreak',
                ];
    }

    public function renderThematicBreak($blockData)
    {
        return '<hr />';
    }
}
