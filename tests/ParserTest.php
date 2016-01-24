<?php

namespace j11e\markdown\tests;

use j11e\markdown\Parser;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * more complex scenarios are tested by writing the markdown and expected
     * html output in a file, in the ./data folder.
     *
     * @dataProvider getDataFiles
     */
    public function testFromDataFiles($markdown, $html, $description)
    {
        $parser = new Parser();

        $this->assertEquals($parser->parse($markdown), $html, $description);
    }

    public function getDataFiles()
    {
        $ds = DIRECTORY_SEPARATOR;
        
        // ls ./data/
        $files = glob(__DIR__ . "${ds}data${ds}*md");

        // construct array of [markdown, html] arrays
        $data = array();
        for ($i = 0; $i < count($files); $i++) {
            $handle = fopen($files[$i], 'r');
            if (!$handle) {
                // there's no reason, but...
                continue;
            }
            $md = fread($handle, filesize($files[$i]));
            fclose($handle);

            $htmlFilename = substr($files[$i], 0, strlen($files[$i])-3) . ".html";
            $handle = fopen($htmlFilename, 'r');
            if (!$handle) {
                // there's still no reason, but...
                continue;
            }
            $html = fread($handle, filesize($htmlFilename));
            fclose($handle);

            $data[] = array($md, $html, $files[$i]);
        }
        
        return $data;
    }
}
