# Change Log

## [5.3.1] - 2023-08-23

* Add function guards to the global functions (#44)

## [5.3.0] - 2022-04-26

* Add support for Symfony 6
* Split out `.mo` parsing to separate `MoParser` class
* Added `CacheInterface` so alternate cache implementations are pluggable
* Added `ApcuCache` implementation to leverage shared in-memory translation cache

## [5.2.0] - 2021-02-05

* Fix "Translator::selectString() must be of the type integer, boolean returned" (#37)
* Fix "TypeError: StringReader::readintarray() ($count) must be of type int, float given" failing tests on ARM (#36)
* Add support for getting and setting all translations (#30)

## [5.1.0] - 2020-11-15

* Allow PHPUnit 9 (#35)
* Fix some typos
* Sync config files
* Allow PHP 8.0

## [5.0.0] - 2020-02-28

* Drop support for PHP 5.3, PHP 5.4, PHP 5.5, PHP 5.6, PHP 7.0 and HHVM
* Enabled strict mode on PHP files
* Add support for Symfony 5 (#34)
* Add support for phpunit 8
* Rename CHANGES.md to CHANGELOG.md and follow a standard format

## [4.0] - 2018-02-12

* The library no longer changes system locales.

## [3.4] -  2017-12-15

* Added Translator::setTranslation method.

## [3.3] -  2017-06-01

* Add support for switching locales for Loader instance.

## [3.2] -  2017-05-23

* Various fixes when handling corrupted mo files.

## [3.1] -  2017-05-15

* Documentation improvements.

## [3.0] -  2017-01-23

* All classes moved to the PhpMyAdmin namespace.

## [2.2] -  2017-01-07

* Coding style cleanup.
* Avoid installing tests using composer.

## [2.1] -  2016-12-21

* Various code cleanups.
* Added support for PHP 5.3.

## [2.0] -  2016-10-13

* Consistently use camelCase in API.
* No more relies on using eval().
* Depends on symfony/expression-language for calculations.

## [1.2] -  2016-09-22

* Stricter validation of plural expression.

## [1.1] -  2016-08-29

* Improved handling of corrupted mo files.
* Minor performance improvements.
* Stricter validation of plural expression.

## [1.0] -  2016-04-27

* Documentation improvements.
* Testsuite improvements.

## [0.4] -  2016-03-02

* Fixed test failures with hhvm due to broken putenv.

## [0.3] -  2016-03-01

* Added Loader::detectlocale method.

## [0.2] -  2016-02-24

* Marked PHP 5.4 and 5.5 as supported.

## [0.1] -  2016-02-23

* Initial release.
