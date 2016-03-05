<?php

namespace j11e\markdown\blocks;

trait LinkRef
{
    // link reference definitions associate a label to an URL
    // links can then use these labels instead of the URL
    private $linkReferencesRegister = [];

    /**
     *  parses a link bit by bit, finding the link label, destination, and title
     */
    private function parseLinkRefDefinition($lines, $currentIndex)
    {
        // sigh PHP, sigh backslashes, sigh regexes, computers are terrible
        $ob = preg_quote('<');
        $cb = preg_quote('>');
        $eob = preg_quote("\<");
        $ecb = preg_quote("\>");

        $ecsb = preg_quote("\]");

        $op = preg_quote("(");
        $cp = preg_quote(")");
        $eop = preg_quote("\(");
        $ecp = preg_quote("\)");
        
        $nl = preg_quote("\n");
        
        $sq = preg_quote("'");
        $dq = preg_quote('"');
        $edq = preg_quote('\"');
        $esq = preg_quote("\'");

        // a link definition can span over multiple lines
        $candidate = array_slice($lines, $currentIndex);
        $candidate = implode("\n", $candidate);
        $linespan = 1;

        /*
        *   step 1: link label
        */
        $lr_label_pattern = "/( {0,3}\[((".$ecsb."|[^\]]){0,999})\])/u";
        $match = preg_match($lr_label_pattern, $candidate, $groups);

        if (!$match) {
            return false;
        }
        $label = $this->normalizeLinkLabel($groups[2]);

        $candidate = ltrim(substr($candidate, strlen($groups[1])));

        /*
        *   step 2: link destination
        */
        // there must be a : between label and dst, also check linespan
        $lr_before_dst_pattern = "/^(:\h*(".$nl.")?\h*)/";
        $match = preg_match($lr_before_dst_pattern, $candidate, $groups);

        if (!$match) {
            return false;
        } else {
            $candidate = substr($candidate, strlen($groups[1]));

            if (count($groups) === 3) {
                $linespan += 1;
            }
        }

        $lr_dst_withbrackets_subpattern = "/^(".$ob."((".$eob."|".$ecb."|[^".$ob.$cb."\h])*+)".$cb.")/u";

        $match = preg_match($lr_dst_withbrackets_subpattern, $candidate, $groups);

        // there are two kinds of link ref destinations
        // if $match: dst between angle brackets; else: without brakets
        if ($match) {
            $destination = $groups[2];
        } else {
            // with angle brackets is easy; without brackets is hard.
            // these are the allowed characters; the range is all ascii printable chars except parentheses
            $chars = "(". $eop . "|" . $ecp . "|\w)";

            // there can be unescaped parentheses... but not nested unescaped parentheses
            // so pattern = "* characters, followed optionally by * characters in parentheses", * times
            $lr_dst_nobrackets_subpattern = '/^(('.$chars.'*('.$op.$chars.'*'.$cp.')?)+)/u';

            $match = preg_match($lr_dst_nobrackets_subpattern, $candidate, $groups);
            
            if (!$match || count($groups) === 1 || strlen($groups[1]) === 0) {
                return false;
            }

            $destination = $groups[1];
        }

        $candidate = substr($candidate, strlen($groups[1]));

        /*
        *   step 3: link title (optional, but if present, must be well formatted)
        */
        // if there is something after the destination URL, on the same line,
        // then it must be a well formatted title, or else the whole def is invalid
        $something_after_destination = false;
        preg_match("/(.*)?(".$nl."|$)/", $candidate, $groups);
        if (count($groups) > 1 && strlen(ltrim($groups[1])) > 0) {
            $something_after_destination = true;
        }

        $lr_before_title_pattern = "/^(\h*(".$nl.")?\h*)/";
        $match = preg_match($lr_before_title_pattern, $candidate, $groups);

        if ($match) {
            $candidate = substr($candidate, strlen($groups[1]));

            $jumped_line_looking_for_title = false;
            if (count($groups) === 3) {
                $jumped_line_looking_for_title = true;
                $linespan += 1;
            }
        }

        $title = "";

        // titles can be in double quotes, single quotes, or parentheses
        // I've fought regexs too long today, f**k it, one regex per case will do
        $lr_title_pattern_double = '/"(('.$edq.'|[^'.$dq.$nl.']|(?<!\s)'.$nl.')*)"\h*('.$nl.'|$)/u';
        $lr_title_pattern_simple = "/'((".$esq."|[^".$sq.$nl."]|(?<!\s)".$nl.")*)'\h*(".$nl."|$)/u";
        $lr_title_pattern_parentheses = "/".$op."((".$eop."|".$ecp."|[^".$op.$cp.$nl."]|(?<!\s)".$nl.")*)".$cp."\h*(".$nl."|$)/u";

        // empty ifs FTW
        if (preg_match($lr_title_pattern_double, $candidate, $groups)) {
        } else if (preg_match($lr_title_pattern_simple, $candidate, $groups)) {
        } else if (preg_match($lr_title_pattern_parentheses, $candidate, $groups)) {
        }

        // count($groups) > 0 => properly formatted title found
        if (count($groups)) {
            $linespan += substr_count($groups[0], "\n") - 1;

            $title = $groups[1];
            $title = htmlspecialchars($title);

            if ($jumped_line_looking_for_title) {
                $linespan += 1;
            }
        } else if ($something_after_destination) {
            // no proper title found; if badly formatted title present, ignore whole link
            return false;
        }

        $ret = ['label'=> $label, 'destination'=> $destination, 'title' => $title, 'linespan' => $linespan];
        return $ret;
    }

    /**
     * "To normalize a label, perform the Unicode case fold and collapse
     * consecutive internal whitespace to a single space"
     * mb_strtolower is a pretty weak unicode case folding, but since I only intend
     * to use this parser for french and english text, it'll do
     */
    private function normalizeLinkLabel($label)
    {
        $label = mb_strtolower($label);
        $label = preg_replace('/\h+/', ' ', $label);
        
        return $label;
    }

    public function identifyLinkRef($lines, $currentIndex)
    {
        return count($this->currentParagraph) === 0 &&
                $this->parseLinkRefDefinition($lines, $currentIndex);
    }

    public function parseLinkRef($lines, $currentIndex)
    {
        $linkData = $this->parseLinkRefDefinition($lines, $currentIndex);

        // register the link if it hasn't been defined already
        if (!array_key_exists($linkData['label'], $this->linkReferencesRegister)) {
            $this->linkReferencesRegister[$linkData['label']] = [
                "destination" => $linkData["destination"],
                "title" => $linkData["title"]
            ];
        }

        $newIndex = $currentIndex + $linkData['linespan'];
        return ['newIndex' => $newIndex,
                'type' => 'linkRef',
                'leaf' => true];
    }

    public function renderLinkRef($blockData)
    {
        return '';
    }

    public function inlineParseLinkRef($inlineContent)
    {
        $ecsb = preg_quote("\]");
        $lr_label_pattern = "/\[((".$ecsb."|[^\]]){0,999})\]/";
        
        $linkLabels = preg_match_all($lr_label_pattern, $inlineContent, $matches);

        for ($i=0; $i<$linkLabels; $i++) {
            $label = $matches[1][$i];
            $normalizedLabel = $this->normalizeLinkLabel($label);

            if (array_key_exists($normalizedLabel, $this->linkReferencesRegister)) {
                $linkData = $this->linkReferencesRegister[$normalizedLabel];
                
                $url = $linkData['destination'];
                $titleAttr = "";
                if (strlen($linkData['title']) > 0) {
                    $titleAttr = " title=\"${linkData['title']}\"";
                }
                $linkHtml = "<a href=\"${url}\"${titleAttr}>${label}</a>";

                $inlineContent = str_replace("[${label}]", $linkHtml, $inlineContent);
            }
        }

        return $inlineContent;
    }
}
