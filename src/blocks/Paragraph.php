<?php

namespace j11e\markdown\blocks;

trait Paragraph
{
    public function parseParagraph($contentLines)
    {
        // every line's leading spaces are stripped
        $contentLines = array_map(function ($el) {
            return ltrim($el);
        }, $contentLines);

        // remove final spaces
        $le = count($contentLines);
        $contentLines[$le-1] = rtrim($contentLines[$le-1]);

        $content = implode("\n", $contentLines);
        return ["type" => "paragraph", "content" => $content];
    }

    public function renderParagraph($blockData, $depth = 0)
    {
        return '<p>'.$this->parseInline($blockData['content']).'</p>';
    }
}
