<?php

namespace App\Model\Typo;

/**
 * From file t3lib/class.t3lib_div.php
 */
class T3libDiv {

	/**
	 * Check for item in list
	 * Check if an item exists in a comma-separated list of items.
	 *
	 * @param string $list comma-separated list of items (string)
	 * @param string $item item to check for
	 * @return boolean TRUE if $item is in $list
	 */
	public static function inList($list, $item) {
		return (strpos(',' . $list . ',', ',' . $item . ',') !== FALSE ? TRUE : FALSE);
	}

	/**
	 * Explodes a string and trims all values for whitespace in the ends.
	 * If $onlyNonEmptyValues is set, then all blank ('') values are removed.
	 *
	 * @param string $delim Delimiter string to explode with
	 * @param string $string The string to explode
	 * @param boolean $removeEmptyValues If set, all empty values will be removed in output
	 * @param integer $limit If positive, the result will contain a maximum of
	 *						 $limit elements, if negative, all components except
	 *						 the last -$limit are returned, if zero (default),
	 *						 the result is not limited at all. Attention though
	 *						 that the use of this parameter can slow down this
	 *						 function.
	 * @return array Exploded values
	 */
	public static function trimExplode($delim, $string, $removeEmptyValues = FALSE, $limit = 0) {
		$explodedValues = explode($delim, $string);

		$result = array_map('trim', $explodedValues);

		if ($removeEmptyValues) {
			$temp = array();
			foreach ($result as $value) {
				if ($value !== '') {
					$temp[] = $value;
				}
			}
			$result = $temp;
		}

		if ($limit != 0) {
			if ($limit < 0) {
				$result = array_slice($result, 0, $limit);
			} elseif (count($result) > $limit) {
				$lastElements = array_slice($result, $limit - 1);
				$result = array_slice($result, 0, $limit - 1);
				$result[] = implode($delim, $lastElements);
			}
		}

		return $result;
	}

	/**
	 * Implodes attributes in the array $arr for an attribute list in eg. and HTML tag (with quotes)
	 *
	 * @param array $arr Array with attribute key/value pairs, eg. "bgcolor"=>"red", "border"=>0
	 * @param boolean $xhtmlSafe If set the resulting attribute list will have a) all attributes in lowercase (and duplicates weeded out, first entry taking precedence) and b) all values htmlspecialchar()'ed. It is recommended to use this switch!
	 * @param boolean $dontOmitBlankAttribs If TRUE, don't check if values are blank. Default is to omit attributes with blank values.
	 * @return string Imploded attributes, eg. 'bgcolor="red" border="0"'
	 */
	public static function implodeAttributes(array $arr, $xhtmlSafe = FALSE, $dontOmitBlankAttribs = FALSE) {
		if ($xhtmlSafe) {
			$newArr = array();
			foreach ($arr as $p => $v) {
				if (!isset($newArr[strtolower($p)])) {
					$newArr[strtolower($p)] = htmlspecialchars($v);
				}
			}
			$arr = $newArr;
		}
		$list = array();
		foreach ($arr as $p => $v) {
			if (strcmp($v, '') || $dontOmitBlankAttribs) {
				$list[] = $p . '="' . $v . '"';
			}
		}
		return implode(' ', $list);
	}

	/**
	 * Explode a string (normally a list of filenames) with whitespaces by considering quotes in that string. This is mostly needed by the imageMagickCommand function above.
	 *
	 * @param string $parameters The whole parameters string
	 * @param boolean $unQuote If set, the elements of the resulting array are unquoted.
	 * @return array Exploded parameters
	 */
	public static function unQuoteFilenames($parameters, $unQuote = FALSE) {
		$paramsArr = explode(' ', trim($parameters));

		$quoteActive = -1; // Whenever a quote character (") is found, $quoteActive is set to the element number inside of $params. A value of -1 means that there are not open quotes at the current position.
		foreach ($paramsArr as $k => $v) {
			if ($quoteActive > -1) {
				$paramsArr[$quoteActive] .= ' ' . $v;
				unset($paramsArr[$k]);
				if (substr($v, -1) === $paramsArr[$quoteActive][0]) {
					$quoteActive = -1;
				}
			} elseif (!trim($v)) {
				unset($paramsArr[$k]); // Remove empty elements

			} elseif (preg_match('/^(["\'])/', $v) && substr($v, -1) !== $v[0]) {
				$quoteActive = $k;
			}
		}

		if ($unQuote) {
			foreach ($paramsArr as $key => &$val) {
				$val = preg_replace('/(^"|"$)/', '', $val);
				$val = preg_replace('/(^\'|\'$)/', '', $val);

			}
			unset($val);
		}
			// return reindexed array
		return array_values($paramsArr);
	}
}