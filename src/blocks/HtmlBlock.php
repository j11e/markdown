<?php

namespace j11e\markdown\blocks;

trait HtmlBlock
{
    /**
     * the following are sub-regexp strings that will be composed
     * to try to parse HTML. AttributeName and AttributeValue are in ()s so that
     * their values can be retrieved in the group matches.
     */
    private $htmlAttributeNamePattern = '([a-z_:][a-z0-9_\.:\-]*)';

    private $htmlAttributeValueSpecPattern = ' *= *';

    private $htmlAttributeValuePattern = '([^ "\'=><`]+|\'[^\']*\'|"[^"]*")';

    private function startsWith($needle, $haystack)
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    /**
     * returns an array containing the type (int) and the endPattern (regex str)
     * if the line given as param meets one of the conditions to be the
     * start of an HTML block; else return an empty array
     */
    protected function getEndConditionPattern($line)
    {
        // first, let's remove the initial indentation + opening bracket
        if (!preg_match('/^ {0,3}+(<.*)$/', $line, $groups)) {
            // and also discard all non-html blocks quickly
            return [];
        }

        $content = strtolower($groups[1]);

        $startStringToEndPatternMap = ["<script" => "/<\/script>/",
            "<pre" => "/<\/pre>/",
            "<style" => "/<\/style>/",
            "<!--" => "/-->/",
            "<?" => "/\?>/",
            "<!" => "/>/",
            "<![CDATA[" => "/\]\]>/"
        ];

        foreach ($startStringToEndPatternMap as $startStr => $endPattern) {
            if ($this->startsWith($startStr, $content)) {
                // types 1 to 5 don't really matter, so let's all call them type 1s
                return ["type" => 1,
                        "endPattern" => $endPattern];
            }
        }

        // remove the beginning < or </
        preg_match('/^<\/?+(.*)$/', $content, $content);
        $content = $content[1];

        $typeSixBlockTags = ["address", "article", "aside", "base", "basefont",
                            "blockquote", "body", "caption", "center", "col",
                            "colgroup", "dd", "details", "dialog", "dir",
                            "div", "dl", "dt", "fieldset", "figcaption",
                            "figure", "footer", "form", "frame", "frameset",
                            "h1", "head", "header", "hr", "html", "iframe",
                            "legend", "li", "link",  "main", "menu", "menuitem",
                            "meta", "nav", "noframes", "ol", "optgroup",
                            "option", "p", "param", "section", "source",
                            "summary", "table", "tbody", "td", "tfoot", "th",
                            "thead", "title", "tr", "track", "ul"];
        foreach ($typeSixBlockTags as $tag) {
            if ($this->startsWith($tag, $content)) {
                if (preg_match('/^'.$tag.'( |>|\/>|$)/', $content)) {
                    return ["type" => 6, "endPattern" => "/^\s*$/"];
                }
            }
        }

        $attributePattern = $this->htmlAttributeNamePattern . '(' . $this->htmlAttributeValueSpecPattern . $this->htmlAttributeValuePattern . ')?';
        if (preg_match('/^[a-z][a-z0-9-]* *('.$attributePattern.')* *\/?> *$/', $content)) {
            //              ^tag name    ^ whitespaces ^attr(s)      ^ end of tag, spaces, then end of line
            return ["type" => 7, "endPattern" => "/^\s*$/"];
        }

        return [];
    }

    public function identifyHtmlBlock($lines, $currentIndex)
    {
        $res = $this->getEndConditionPattern($lines[$currentIndex]);

        if (count($res) === 0) {
            return false;
        }

        if ($res['type'] === 7 && count($currentParagraph) !== 0) {
            return false;
        }

        return true;
    }

    public function parseHtmlBlock($lines, $currentIndex)
    {
        $newIndex = $currentIndex;
        $numberOfLines = count($lines);

        $typeAndEndPat = $this->getEndConditionPattern($lines[$currentIndex]);

        do {
            $content[] = $lines[$newIndex];
        } while ($newIndex < $numberOfLines
              && !preg_match($typeAndEndPat['endPattern'], $lines[$newIndex++]));

        end($content);
        if (ltrim(current($content)) === '') {
            // remove the final empty line
            array_pop($content);
        }
        reset($content);

        $content = implode("\n", $content);

        return ['newIndex' => $newIndex,
                'type' => 'HtmlBlock',
                'content' => $content,
                'leaf' => true];
    }

    public function renderHtmlBlock($blockData)
    {
        return $blockData['content'];
    }
}
