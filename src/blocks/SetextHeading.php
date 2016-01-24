<?php

namespace j11e\markdown\blocks;

class SetextHeading
{
    public function identifySetextHeading($lines, $currentIndex)
    {
        return false;
    }

    public function parseSetextHeading($lines, $currentIndex)
    {
        $newIndex = $currentIndex + 1;
        return ['newIndex' => $newIndex,
                'type' => 'SetextHeading',
                'content' => $content,
                'leaf' => true];
    }

    public function renderSetextHeading($blockData)
    {
        return '';
    }
}
