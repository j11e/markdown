<?php

namespace j11e\markdown\blocks;

/**
 * this trait has all the reasons in the world to be called "List",
 * but "list" (and all case variations) are reserved by PHP. Sigh.
 */
trait ListBlock
{
    private $listBulletMarkerPattern = "/^([-+*])( {1,4})(.*)$/";
    
    private $listOrderedMarkerPattern = "/^(([0-9]{1,9})[\.)])( {1,4})(.*)$/";

    public function identifyList($lines, $currentIndex)
    {
        return preg_match($this->listBulletMarkerPattern, $lines[$currentIndex])
                || preg_match($this->listOrderedMarkerPattern, $lines[$currentIndex]);
    }

    public function parseList($lines, $currentIndex)
    {
        $curLine = $lines[$currentIndex];
        $listContent = [];
        $curItemContent = [];
        $startNumber = 1;

        if (preg_match($this->listBulletMarkerPattern, $curLine, $groups)) {
            $type = "BulletList";

            $indentLevel = strlen($groups[1]) + strlen($groups[2]);
            $curItemContent[] = $groups[3];
        } else if (preg_match($this->listOrderedMarkerPattern, $curLine, $groups)) {
            $type = "OrderedList";
            
            $indentLevel = strlen($groups[1]) + strlen($groups[3]);
            $startNumber = intval($groups[2]);
            $curItemContent[] = $groups[4];
        }

        $newIndex = $currentIndex+1;
        $lineCheckPattern = '/^ {' . $indentLevel . '}(.*)$/';
        $lastLineWasBlank = false;
        $done = false;

        while (!$done && $newIndex < count($lines)) {
            $curLine = $lines[$newIndex];

            if (ltrim($curLine) === '') {
                /* two consecutive blank lines ends the list...
                 * EXCEPT if it happens within an *fenced* code block 
                 */

                // strict standards cry if I do this on one line
                $currentListItemBlockTree = $this->parseBlockTree($curItemContent);
                $currentListItemLastBlock = end($currentListItemBlockTree);

                if ($currentListItemLastBlock['type'] !== 'CodeBlock'
                        || $currentListItemLastBlock['codeBlockType'] !== 'fenced') {
                    $done = $lastLineWasBlank;
                }

                $lastLineWasBlank = true;
                $curItemContent[] = $curLine;
                $newIndex += 1;
            } else {
                $lastLineWasBlank = false;

                if (preg_match($lineCheckPattern, $curLine, $groups)) {
                    $curItemContent[] = $groups[1];
                    $newIndex += 1;
                } else {
                    $done = true;
                }
            }

        }

        if (count($curItemContent)) {
            $curItemContent = $this->parseBlockTree($curItemContent);
            $content[] = $curItemContent;
        }

        return ['newIndex' => $newIndex,
                'type' => 'List',
                'startNumber' => $startNumber,
                'listType' => $type,
                'content' => $content,
                'leaf' => false,
                ];
    }

    public function renderList($blockData)
    {
        $tag = $blockData["listType"] === "BulletList" ? "ul" : "ol";
        
        $startNumber = "";
        if ($tag == 'ol' && $blockData['startNumber'] != 1) {
            $startNumber = ' start="' . $blockData['startNumber'] . '"';
        }

        $text = "<".$tag.$startNumber.">\n";

        foreach ($blockData['content'] as $listItemBlockTree) {
            if (count($listItemBlockTree) === 1 && $listItemBlockTree[0]["type"] === "paragraph") {
                // the trivial case (only one paragraph in the list item)
                // is rendered without the <p> tag.
                $itemContent = $listItemBlockTree[0]['content'];
            } else {
                $itemContent = "\n" . $this->renderBlockTree($listItemBlockTree) . "\n";
            }

            $text .= "<li>" . $itemContent . "</li>\n";
        }

        $text .= '</' . $tag . '>';

        return $text;
    }
}
