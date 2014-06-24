<?php

namespace App\Model\Typo;

/**
 * Text formater pre TYPO3
 *
 * Pomocou tejto triedy je mozne vypisat na stranku text vlozeny pomocou RTE
 * <code>
 * \App\Model\Typo\TextFormatter::rteTransform($article->bodytext)
 * </code>
 *
 * Pre pouzitie v presenteri:
 * <code>
 * {!$bodytext|rtetransform}
 * </code>
 *
 * je vsak treba zaregistrovat ten helper:
 * <code> 
 * $template->addFilter('rtetransform', function($text) {
 *	return \App\Model\Typo\TextFormatter::rteTransform($text);
 * });
 * </code>
 */
class TextFormatter {
	/**
	 * Transform RTE text
	 *
	 * @param string $text
	 * @return string
	 */
	public static function rteTransform($text) {
		// Add every line to p tag
		$text = preg_replace('#\r?\n#', PHP_EOL, $text);
		$text = '<p>' . implode('</p><p>', explode(PHP_EOL, $text)) . '</p>';
		$text = str_replace('<p></p>', '<p>&nbsp;</p>', $text);

		// Transform links
		$t3libParsehtmlProc = new T3libParsehtmlProc();
		$text = $t3libParsehtmlProc->TS_links_rte($text);

		$dom = new \DOMDocument('1.0', 'utf-8');
		@$dom->loadHTML('<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 ' .
			'Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">' . 
			'<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"></head>' .
			'<body><!-- START PARTIAL HTML -->' . $text . '<!-- END PARTIAL HTML --></body></html>');

		
		// Transform image paths
		$images = $dom->getElementsByTagName('img');
		foreach ($images as $imageItem) {
			/* @var $imageItem \DOMElement */
			$imageSrc = (string) $imageItem->getAttribute('src');
			if (! preg_match('#^http[s]?://#i', $imageSrc)) {
				$imageSrc = '/' . $imageSrc;
				$imageItem->setAttribute('src', $imageSrc);
			}
		}

		// Export HTML
		$rawHtml = $dom->saveHTML();
		$startPartialHtml = mb_strpos($rawHtml, '<!-- START PARTIAL HTML -->') + mb_strlen('<!-- START PARTIAL HTML -->');
		$endPartialHtml = mb_strpos($rawHtml, '<!-- END PARTIAL HTML -->');
		$text = mb_substr($rawHtml, $startPartialHtml, $endPartialHtml - $startPartialHtml);
		return $text;
	}
}