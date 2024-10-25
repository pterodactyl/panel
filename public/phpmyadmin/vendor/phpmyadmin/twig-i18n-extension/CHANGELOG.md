# Change Log

## [Unreleased] -

## [4.0.1] - 2021-06-10

* Fix TransNode constructor optional parameters

## [4.0.0] - 2021-02-25

* Add support for domain translation (#4)
* TransNode constructor signature changed, new $domain parameter `?Node $notes, ?Node $domain = null, int $lineno` (#4)
* TransNode constructor signature changed, new $context parameter `?AbstractExpression $count, ?Node $context = null, ?Node $notes` (#6)
* Add support for contexts in translations (#6)
* Add support for enabling `phpmyadmin/motranslator` or complex non php-gettext supported functions (#6)
* Add support for custom notes labels (#6)
* Make debug info disabled by default, `TransNode::$enableAddDebugInfo = true;` will add it back
* Some slight performance improvements
* Added tests for all the code

## [3.0.0] - 2020-06-14

* Add a .gitattributes file
* Support Twig 3
* Remove extra field from composer.json
* Add support field in composer.json
* Require php >= 7.1
* Setup and apply phpmyadmin/coding-standard
* Apply changes for php 8.0 compatibility (https://github.com/twigphp/Twig/issues/3327)

## [2.0.0] - 2020-01-14

* First release of this library.
