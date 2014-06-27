vendor/autoload.php:
	composer install

test: vendor/autoload.php
	vendor/bin/phpunit
