Nette RTE processor
====================

Requirements
------------

nette-rte-processor requires PHP 5.3.0 or higher.

**WARNING:** Most of code is from TYPO3 t3lib library! - *So it isnt very nice ;-)*

Installation
------------

The best way to install nette-rte-processor is using [Composer](http://getcomposer.org/):

```sh
$ composer require tomaj/nette-rte-processor
```

Background
----------

This library is usefull for processing RTE fields from TYPO3 when you need render it to frontend with Nette. Library use code from TYPO3 to convert special marks from RTE to output HTML.

Usage
-----

You can use simple static function

```
\App\Model\Typo\TextFormatter::rteTransform($bodytext)
```

or create helper for using in templates:

```
$template->addFilter('rtetransform', function($text) {
	return \App\Model\Typo\TextFormatter::rteTransform($text);
});
```

-----

Repository [http://github.com/tomaj/nette-rte-processor](http://github.com/tomaj/nette-rte-processor).
