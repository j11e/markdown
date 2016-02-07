<?php

namespace j11e\markdown\blocks;

trait CodeBlock
{
    public function identifyCodeBlock($lines, $currentIndex)
    {
        $indentedBlock = !count($this->currentParagraph) && preg_match('/^ {4,}/', $lines[$currentIndex]);
        $fencedBlock = preg_match('/^ {0,3}(`{3,}|~{3,})((?<=`)\s*+[^`]*|(?<=~)\s*+([^~].*)?)$/', $lines[$currentIndex]);

        return $indentedBlock || $fencedBlock;
    }

    public function parseCodeBlock($lines, $currentIndex)
    {
        $newIndex = $currentIndex;
        $content = [];
        $groups = [];
        $codeBlockType = '';
        $infoStr = '';

        $lineCount = count($lines);

        // test indented code blocks first because they have precedence
        if (preg_match('/^ {4,}/', $lines[$newIndex])) {
            $codeBlockType = 'indented';

            // blank line or 4-space-indented content
            while ($newIndex < $lineCount) {
                $curLine = $lines[$newIndex];

                if (preg_match('/^ {4}(.*)$/', $curLine, $groups)) {
                    $content[] = $groups[1];
                } else if (ltrim($curLine) === '') {
                    $content[] = '';
                } else {
                    break;
                }

                $newIndex++;
            }
        } else {
            preg_match('/^( {0,3})(`{3,}|~{3,})\s*+(.*?)\s*$/', $lines[$newIndex], $groups);
            $codeBlockType = 'fenced';

            $newIndex++;

            $initialIndentLength = strlen($groups[1]);
            $fenceChar = $groups[2][0];
            $fenceLength = strlen($groups[2]);
            $infoStr = $groups[3];

            // left-trim at most as many spaces as the initial indent
            $closingFencePattern = '/^ {0,3}'.$fenceChar.'{'.$fenceLength.',}\s*$/';
            $contentPattern = '/^ {0,'.$initialIndentLength.'}+(.*)$/';

            while ($newIndex < $lineCount) {
                $curLine = $lines[$newIndex];
                $newIndex++;

                if (preg_match($closingFencePattern, $curLine)) {
                    break;
                } else {
                    preg_match($contentPattern, $curLine, $groups);
                    $content[] = $groups[1];
                }
            }
        }

        // remove empty lines preceding or following the code block's content
        while (end($content) === '') {
            array_pop($content);
        }
        while ($content[0] === '') {
            array_shift($content);
        }

        $content = implode("\n", $content);
        
        if ($content) {
            // don't forget the final \n
            $content .= "\n";
        }

        return ['newIndex' => $newIndex,
                'type' => 'CodeBlock',
                'codeBlockType' => $codeBlockType,
                'infoStr' => $infoStr,
                'content' => $content,
                'leaf' => true];
    }

    public function renderCodeBlock($blockData)
    {
        $class = $blockData['infoStr'] ? ' class="language-'.explode(' ', $blockData['infoStr'])[0].'"' : '';
        return '<pre><code'.$class.'>' . htmlspecialchars($blockData['content']) . '</code></pre>';
    }
}
