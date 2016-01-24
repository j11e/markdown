<?php

namespace j11e\markdown\blocks;

trait AtxHeading
{
    public function identifyAtxHeading($lines, $currentIndex)
    {
        return preg_match('/^ {0,3}#{1,6}( |$)/', $lines[$currentIndex]);
    }

    public function parseAtxHeading($lines, $currentIndex)
    {
        $curLine = $lines[$currentIndex];
        preg_match('/^ {0,3}(#{1,6})( .*?)( #* *)?$/', $curLine, $rawContent);

        $level = strlen($rawContent[1]);

        $content = '';
        if ($rawContent[2]) {
            $content = ltrim(rtrim($rawContent[2]));
        }

        $newIndex = $currentIndex + 1;
        return ['newIndex' => $newIndex,
                'type' => 'AtxHeading',
                'level' => $level,
                'content' => $content,
                'leaf' => true,
                ];
    }

    public function renderAtxHeading($blockData)
    {
        return '<h'.$blockData['level'].'>'.$blockData['content'].'</h'.$blockData['level'].'>';
    }
}
