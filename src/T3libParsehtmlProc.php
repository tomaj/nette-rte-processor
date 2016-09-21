<?php

namespace Tomaj\RTEProcessor;

/**
 * From file t3lib/class.t3lib_parsehtml_proc.php
 *
 * <code>
 * $t3libParsehtmlProc = new \Tomaj\RTEProcessor\T3libParsehtmlProc();
 * $text = $t3libParsehtmlProc->rteTransform($text);
 * </code>
 */
class T3libParsehtmlProc
{
    /**
     * Transformation handler: 'ts_links' / direction: "rte"
     * Converting <link tags> to <A>-tags
     *
     * @param    string        Content input
     * @return    string        Content output
     */
    public function tsLinksRte($value)
    {
        $value = $this->tsAtagToAbs($value);

            // Split content by the TYPO3 pseudo tag "<link>":
        $blockSplit = $this->splitIntoBlock('link', $value, 1);
        $siteUrl = $this->siteUrl();
        foreach ($blockSplit as $k => $v) {
            $error = '';
            if ($k % 2) { // block:
                $tagCode = T3libDiv::unQuoteFilenames(trim(substr($this->getFirstTag($v), 0, -1)), true);
                $link_param = isset($tagCode[1]) ? $tagCode[1] : '';
                $href = '';
                $external = false;
                    // Parsing the typolink data. This parsing is roughly done like in tslib_content->typolink()
                if (strstr($link_param, '@')) { // mailadr
                    $href = 'mailto:' . preg_replace('/^mailto:/i', '', $link_param);
                } elseif (substr($link_param, 0, 1) == '#') { // check if anchor
                    $href = $siteUrl . $link_param;
                } else {
                    $fileChar = intval(strpos($link_param, '/'));
                    $urlChar = intval(strpos($link_param, '.'));
                    $external = false;
                        // Parse URL:
                    $pU = parse_url($link_param);
                        // Detects if a file is found in site-root OR is a simulateStaticDocument.
                    list($rootFileDat) = explode('?', $link_param);
                    $rFD_fI = pathinfo($rootFileDat);
                    $extension = isset($rFD_fI['extension']) ? strtolower($rFD_fI['extension']) : '';
                    if (trim($rootFileDat) && !strstr($link_param, '/') && (@is_file(PATH_site . $rootFileDat) || T3libDiv::inList('php,html,htm', $extension))) {
                        $href = $siteUrl . $link_param;
                    } elseif ((isset($pU['scheme']) && $pU['scheme']) || ($urlChar && (!$fileChar || $urlChar < $fileChar))) {
                            // url (external): if has scheme or if a '.' comes before a '/'.
                        $href = $link_param;
                        $scheme = isset($pU['scheme']) ? trim($pU['scheme']) : '';
                        if (!$scheme) {
                            $href = 'http://' . $href;
                        }
                        $external = true;
                    } elseif ($fileChar) { // file (internal)
                        $href = $siteUrl . $link_param;
                    } else { // integer or alias (alias is without slashes or periods or commas, that is 'nospace,alphanum_x,lower,unique' according to tables.php!!)
                            // Splitting the parameter by ',' and if the array counts more than 1 element it's a id/type/parameters triplet
                        $pairParts = T3libDiv::trimExplode(',', $link_param, true);
                        $idPart = isset($pairParts[0]) ? $pairParts[0] : '';
                        $link_params_parts = explode('#', $idPart);
                        $idPart = trim($link_params_parts[0]);
                        $sectionMark = isset($link_params_parts[1]) ? trim($link_params_parts[1]) : '';
                        $href = '?id=' . $link_param;
                    }
                }

                    // Setting the A-tag:
                $bTag = '<a href="' . htmlspecialchars($href) . '"' .
                        (isset($tagCode[2]) && $tagCode[2] && $tagCode[2] != '-' ? ' target="' . htmlspecialchars($tagCode[2]) . '"' : '') .
                        (isset($tagCode[3]) && $tagCode[3] && $tagCode[3] != '-' ? ' class="' . htmlspecialchars($tagCode[3]) . '"' : '') .
                        (isset($tagCode[4]) && $tagCode[4] ? ' title="' . htmlspecialchars($tagCode[4]) . '"' : '') .
                        ($external ? ' data-htmlarea-external="1"' : '') .
                        ($error ? ' rteerror="' . htmlspecialchars($error) . '" style="background-color: yellow; border:2px red solid; color: black;"' : '') . // Should be OK to add the style; the transformation back to databsae will remove it...
                        '>';
                $eTag = '</a>';

                $blockSplit[$k] = $bTag . $this->tsLinksRte($this->removeFirstAndLastTag($blockSplit[$k])) . $eTag;
            }
        }

            // Return content:
        return implode('', $blockSplit);
    }

    /**
     * Converting <A>-tags to absolute URLs (+ setting rtekeep attribute)
     *
     * @param    string        Content input
     * @param    boolean        If true, then the "rtekeep" attribute will not be set.
     * @return    string        Content output
     */
    private function tsAtagToAbs($value, $dontSetRTEKEEP = false)
    {
        $blockSplit = $this->splitIntoBlock('A', $value);
        foreach ($blockSplit as $k => $v) {
            if ($k % 2) { // block:
                $attribArray = $this->getTagAttributesClassic($this->getFirstTag($v), 1);

                    // Checking if there is a scheme, and if not, prepend the current url.
                if (isset($attribArray['href']) && strlen($attribArray['href'])) { // ONLY do this if href has content - the <a> tag COULD be an anchor and if so, it should be preserved...

                    $uP = parse_url(strtolower($attribArray['href']));
                    $scheme = isset($uP['scheme']) ? $uP['scheme'] : '';
                    if (!$scheme) {
                        $attribArray['href'] = $this->siteUrl() . $attribArray['href'];
                    } elseif ($scheme != 'mailto') {
                        $attribArray['data-htmlarea-external'] = 1;
                    }
                } else {
                    $attribArray['rtekeep'] = 1;
                }
                if (!$dontSetRTEKEEP) {
                    $attribArray['rtekeep'] = 1;
                }

                $bTag = '<a ' . T3libDiv::implodeAttributes($attribArray, 1) . '>';
                $eTag = '</a>';
                $blockSplit[$k] = $bTag . $this->tsAtagToAbs($this->removeFirstAndLastTag($blockSplit[$k])) . $eTag;
            }
        }
        return implode('', $blockSplit);
    }

    /**
     * Returns an array with the $content divided by tag-blocks specified with the list of tags, $tag
     * Even numbers in the array are outside the blocks, Odd numbers are block-content.
     * Use ->getAllParts() and ->removeFirstAndLastTag() to process the content if needed.
     *
     * @param    string        List of tags, comma separated.
     * @param    string        HTML-content
     * @param    boolean        If set, excessive end tags are ignored - you should probably set this in most cases.
     * @return    array        Even numbers in the array are outside the blocks, Odd numbers are block-content.
     * @see splitTags(), getAllParts(), removeFirstAndLastTag()
     */
    protected function splitIntoBlock($tag, $content, $eliminateExtraEndTags = 0)
    {
        $tags = array_unique(T3libDiv::trimExplode(',', $tag, 1));
        $regexStr = '/\<\/?(' . implode('|', $tags) . ')(\s*\>|\s[^\>]*\>)/si';

        $parts = preg_split($regexStr, $content);

        $newParts = [];
        $pointer = strlen($parts[0]);
        $buffer = $parts[0];
        $nested = 0;
        reset($parts);
        next($parts);
        while (list($k, $v) = each($parts)) {
            $isEndTag = substr($content, $pointer, 2) == '</' ? 1 : 0;
            $tagLen = strcspn(substr($content, $pointer), '>') + 1;

            if (!$isEndTag) { // We meet a start-tag:
                if (!$nested) { // Ground level:
                    $newParts[] = $buffer; // previous buffer stored
                    $buffer = '';
                }
                $nested++; // We are inside now!
                $mbuffer = substr($content, $pointer, strlen($v) + $tagLen); // New buffer set and pointer increased
                $pointer += strlen($mbuffer);
                $buffer .= $mbuffer;
            } else { // If we meet an endtag:
                $nested--; // decrease nested-level
                $eliminated = 0;
                if ($eliminateExtraEndTags && $nested < 0) {
                    $nested = 0;
                    $eliminated = 1;
                } else {
                    $buffer .= substr($content, $pointer, $tagLen); // In any case, add the endtag to current buffer and increase pointer
                }
                $pointer += $tagLen;
                if (!$nested && !$eliminated) { // if we're back on ground level, (and not by eliminating tags...
                    $newParts[] = $buffer;
                    $buffer = '';
                }
                $mbuffer = substr($content, $pointer, strlen($v)); // New buffer set and pointer increased
                $pointer += strlen($mbuffer);
                $buffer .= $mbuffer;
            }
        }
        $newParts[] = $buffer;
        return $newParts;
    }

    /**
     * Returns SiteURL based on thisScript.
     *
     * @return    string        Value of t3lib_div::getIndpEnv('TYPO3_SITE_URL');
     */
    protected function siteUrl()
    {
        return '/';
    }

    /**
     * Returns the first tag in $str
     * Actually everything from the begining of the $str is returned, so you better make sure the tag is the first thing...
     *
     * @param    string        HTML string with tags
     * @return    string
     */
    protected function getFirstTag($str)
    {
        // First:
        $endLen = strpos($str, '>') + 1;
        return substr($str, 0, $endLen);
    }

    /**
     * Removes the first and last tag in the string
     * Anything before the first and after the last tags respectively is also removed
     *
     * @param    string        String to process
     * @return    string
     */
    protected function removeFirstAndLastTag($str)
    {
        // End of first tag:
        $start = strpos($str, '>');
        // Begin of last tag:
        $end = strrpos($str, '<');
        // return
        return substr($str, $start + 1, $end - $start - 1);
    }

    /**
     * Get tag attributes, the classic version (which had some limitations?)
     *
     * @param    string        The tag
     * @param    boolean        De-htmlspecialchar flag.
     * @return    array
     * @access private
     */
    protected function getTagAttributesClassic($tag, $deHSC = 0)
    {
        $attr = $this->getTagAttributes($tag, $deHSC);
        return is_array($attr[0]) ? $attr[0] : [];
    }

    /**
     * Returns an array with all attributes as keys. Attributes are only lowercase a-z
     * If a attribute is empty (shorthand), then the value for the key is empty. You can check if it existed with isset()
     *
     * @param    string        Tag: $tag is either a whole tag (eg '<TAG OPTION ATTRIB=VALUE>') or the parameterlist (ex ' OPTION ATTRIB=VALUE>')
     * @param    boolean        If set, the attribute values are de-htmlspecialchar'ed. Should actually always be set!
     * @return    array        array(Tag attributes,Attribute meta-data)
     */
    protected function getTagAttributes($tag, $deHSC = 0)
    {
        list($components, $metaC) = $this->splitTagAttributes($tag);
        $name = ''; // attribute name is stored here
        $valuemode = false;
        $attributes = [];
        $attributesMeta = [];
        if (is_array($components)) {
            foreach ($components as $key => $val) {
                if ($val != '=') { // Only if $name is set (if there is an attribute, that waits for a value), that valuemode is enabled. This ensures that the attribute is assigned it's value
                    if ($valuemode) {
                        if ($name) {
                            $attributes[$name] = $deHSC ? self::htmlspecialcharsDecode($val) : $val;
                            $attributesMeta[$name]['dashType'] = $metaC[$key];
                            $name = '';
                        }
                    } else {
                        if ($namekey = preg_replace('/[^[:alnum:]_\:\-]/', '', $val)) {
                            $name = strtolower($namekey);
                            $attributesMeta[$name] = [];
                            $attributesMeta[$name]['origTag'] = $namekey;
                            $attributes[$name] = '';
                        }
                    }
                    $valuemode = false;
                } else {
                    $valuemode = true;
                }
            }
            return array($attributes, $attributesMeta);
        }
    }

    /**
     * Returns an array with the 'components' from an attribute list. The result is normally analyzed by getTagAttributes
     * Removes tag-name if found
     *
     * @param    string        The tag or attributes
     * @return    array
     * @access private
     */
    protected function splitTagAttributes($tag)
    {
        $matches = [];
        if (preg_match('/(\<[^\s]+\s+)?(.*?)\s*(\>)?$/s', $tag, $matches) !== 1) {
            return array([], []);
        }
        $tag_tmp = $matches[2];

        $metaValue = [];
        $value = [];
        $matches = [];
        if (preg_match_all('/("[^"]*"|\'[^\']*\'|[^\s"\'\=]+|\=)/s', $tag_tmp, $matches) > 0) {
            foreach ($matches[1] as $part) {
                $firstChar = substr($part, 0, 1);
                if ($firstChar == '"' || $firstChar == "'") {
                    $metaValue[] = $firstChar;
                    $value[] = substr($part, 1, -1);
                } else {
                    $metaValue[] = '';
                    $value[] = $part;
                }
            }
        }
        return array($value, $metaValue);
    }

    /**
     * Inverse version of htmlspecialchars()
     *
     * @param string $value Value where &gt;, &lt;, &quot; and &amp; should be converted to regular chars.
     * @return string Converted result.
     */
    public static function htmlspecialcharsDecode($value)
    {
        $value = str_replace('&gt;', '>', $value);
        $value = str_replace('&lt;', '<', $value);
        $value = str_replace('&quot;', '"', $value);
        $value = str_replace('&amp;', '&', $value);
        return $value;
    }
}
