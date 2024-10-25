# Change Log

All notable changes to `sebastianbergmann/php-token-stream` are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [3.1.3] - 2021-07-26

### Changed

* The declarations of methods in `PHP_Token_Stream` that implement the `ArrayAccess`, `Countable`, and `SeekableIterator` interfaces are now compatible with PHP 8.1

## [3.1.2] - 2020-11-30

### Changed

* Changed PHP version constraint in `composer.json` from `^7.1` to `>=7.1` to allow installation of this version of this library on PHP 8. However, this version of this library does not work on PHP 8. PHPUnit 8.5, which uses this version of this library (through phpunit/php-code-coverage), does not call into this library and instead shows a message that code coverage functionality is not available for PHPUnit 8.5 on PHP 8.

## [3.1.1] - 2019-09-17

### Fixed

* Fixed [#84](https://github.com/sebastianbergmann/php-token-stream/issues/84): Methods named `class` are not handled correctly

## [3.1.0] - 2019-07-25

### Added

* Added support for `FN` and `COALESCE_EQUAL` tokens introduced in PHP 7.4

## [3.0.2] - 2019-07-08

### Changed

* Implemented [#82](https://github.com/sebastianbergmann/php-token-stream/issues/82): Make sure this component works when its classes are prefixed using php-scoper

## [3.0.1] - 2018-10-30

### Fixed

* Fixed [#78](https://github.com/sebastianbergmann/php-token-stream/pull/78): `getEndTokenId()` does not handle string-dollar (`"${var}"`) interpolation

## [3.0.0] - 2018-02-01

### Removed

* Implemented [#71](https://github.com/sebastianbergmann/php-token-stream/issues/71): Remove code specific to Hack language constructs
* Implemented [#72](https://github.com/sebastianbergmann/php-token-stream/issues/72): Drop support for PHP 7.0

## [2.0.2] - 2017-11-27

### Fixed

* Fixed [#69](https://github.com/sebastianbergmann/php-token-stream/issues/69): `PHP_Token_USE_FUNCTION` does not serialize correctly

## [2.0.1] - 2017-08-20

### Fixed

* Fixed [#68](https://github.com/sebastianbergmann/php-token-stream/issues/68): Method with name `empty` wrongly recognized as anonymous function

## [2.0.0] - 2017-08-03

[3.1.3]: https://github.com/sebastianbergmann/php-token-stream/compare/3.1.2...3.1.3
[3.1.2]: https://github.com/sebastianbergmann/php-token-stream/compare/3.1.1...3.1.2
[3.1.1]: https://github.com/sebastianbergmann/php-token-stream/compare/3.1.0...3.1.1
[3.1.0]: https://github.com/sebastianbergmann/php-token-stream/compare/3.0.2...3.1.0
[3.0.2]: https://github.com/sebastianbergmann/php-token-stream/compare/3.0.1...3.0.2
[3.0.1]: https://github.com/sebastianbergmann/php-token-stream/compare/3.0.0...3.0.1
[3.0.0]: https://github.com/sebastianbergmann/php-token-stream/compare/2.0...3.0.0
[2.0.2]: https://github.com/sebastianbergmann/php-token-stream/compare/2.0.1...2.0.2
[2.0.1]: https://github.com/sebastianbergmann/php-token-stream/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/sebastianbergmann/php-token-stream/compare/1.4.11...2.0.0
