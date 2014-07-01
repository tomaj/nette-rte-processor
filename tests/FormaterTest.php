<?php

require dirname(__FILE__). '/../vendor/autoload.php';

class FormaterTest extends PHPUnit_Framework_TestCase
{
	public function testConvertParagraphs()
	{
		$input = "line1\nline2\nline3";
		$expected = "<p>line1</p><p>line2</p><p>line3</p>";
		$output = \Tomaj\RTEProcessor\TextFormatter::rteTransform($input);
		$this->assertEquals($output, $expected);
	}

	public function testConvertInternalLinks()
	{
		$input = "<link 123>asdsad</link>";
		$expected = "<p><a href=\"?id=123\">asdsad</a></p>";
		$output = \Tomaj\RTEProcessor\TextFormatter::rteTransform($input);
		$this->assertEquals($output, $expected);
	}

	public function testConvertMailToLinks()
	{
		$input = "<link mailto:jozko@pucik.sk>asdsad</link>";
		$expected = "<p><a href=\"mailto:jozko@pucik.sk\">asdsad</a></p>";
		$output = \Tomaj\RTEProcessor\TextFormatter::rteTransform($input);
		$this->assertEquals($output, $expected);
	}

	public function testConvertExternalLinks()
	{
		$input = "<link http://www.sme.sk/>asdsad</link>";
		$expected = "<p><a href=\"http://www.sme.sk/\" data-htmlarea-external=\"1\">asdsad</a></p>";
		$output = \Tomaj\RTEProcessor\TextFormatter::rteTransform($input);
		$this->assertEquals($output, $expected);
	}
}