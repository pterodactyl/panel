# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [7.0.15] - 2021-07-26

### Changed

* Bumped required version of php-token-stream

## [7.0.14] - 2020-12-02

### Changed

* [#837](https://github.com/sebastianbergmann/php-code-coverage/issues/837): Allow version 4 of php-token-stream

## [7.0.13] - 2020-11-30

### Changed

* Changed PHP version constraint in `composer.json` from `^7.2` to `>=7.2` to allow installation of this version of this library on PHP 8. However, this version of this library does not work on PHP 8. PHPUnit 8.5, which uses this version of this library, does not call into this library and instead shows a message that code coverage functionality is not available for PHPUnit 8.5 on PHP 8.

## [7.0.12] - 2020-11-27

### Added

* [#834](https://github.com/sebastianbergmann/php-code-coverage/issues/834): Support `XDEBUG_MODE` environment variable

## [7.0.11] - 2020-11-27

### Added

* Support for Xdebug 3

## [7.0.10] - 2019-11-20

### Fixed

* [#710](https://github.com/sebastianbergmann/php-code-coverage/pull/710): Code Coverage does not work in PhpStorm

## [7.0.9] - 2019-11-20

### Changed

* [#709](https://github.com/sebastianbergmann/php-code-coverage/pull/709): Prioritize PCOV over Xdebug

## [7.0.8] - 2019-09-17

### Changed

* Update HTML report Bootstrap 4.3.1, jQuery 3.4.1, and popper.js 1.15.0

## [7.0.7] - 2019-07-25

### Changed

* Bumped required version of php-token-stream

## [7.0.6] - 2019-07-08

### Changed

* Bumped required version of php-token-stream

## [7.0.5] - 2019-06-06

### Fixed

* [#681](https://github.com/sebastianbergmann/php-code-coverage/pull/681): `use function` statements are not ignored

## [7.0.4] - 2019-05-29

### Fixed

* [#682](https://github.com/sebastianbergmann/php-code-coverage/pull/682): Code that is not executed is reported as being executed when using PCOV

## [7.0.3] - 2019-02-26

### Fixed

* [#671](https://github.com/sebastianbergmann/php-code-coverage/issues/671): `TypeError` when directory name is a number

## [7.0.2] - 2019-02-15

### Changed

* Updated HTML report to Bootstrap 4.3.0

### Fixed

* [#667](https://github.com/sebastianbergmann/php-code-coverage/pull/667): `TypeError` in PHP reporter

## [7.0.1] - 2019-02-01

### Fixed

* [#664](https://github.com/sebastianbergmann/php-code-coverage/issues/664): `TypeError` when whitelisted file does not exist

## [7.0.0] - 2019-02-01

### Added

* Implemented [#663](https://github.com/sebastianbergmann/php-code-coverage/pull/663): Support for PCOV

### Fixed

* [#654](https://github.com/sebastianbergmann/php-code-coverage/issues/654): HTML report fails to load assets
* [#655](https://github.com/sebastianbergmann/php-code-coverage/issues/655): Popin pops in outside of screen

### Removed

* This component is no longer supported on PHP 7.1

## [6.1.4] - 2018-10-31

### Fixed

* [#650](https://github.com/sebastianbergmann/php-code-coverage/issues/650): Wasted screen space in HTML code coverage report

## [6.1.3] - 2018-10-23

### Changed

* Use `^3.1` of `sebastian/environment` again due to [regression](https://github.com/sebastianbergmann/environment/issues/31)

## [6.1.2] - 2018-10-23

### Fixed

* [#645](https://github.com/sebastianbergmann/php-code-coverage/pull/645): Crash that can occur when php-token-stream parses invalid files

## [6.1.1] - 2018-10-18

### Changed

* This component now allows `^4` of `sebastian/environment`

## [6.1.0] - 2018-10-16

### Changed

* Class names are now abbreviated (unqualified name shown, fully qualified name shown on hover) in the file view of the HTML report
* Update HTML report to Bootstrap 4

[7.0.15]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.14...7.0.15
[7.0.14]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.13...7.0.14
[7.0.13]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.12...7.0.13
[7.0.12]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.11...7.0.12
[7.0.11]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.10...7.0.11
[7.0.10]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.9...7.0.10
[7.0.9]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.8...7.0.9
[7.0.8]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.7...7.0.8
[7.0.7]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.6...7.0.7
[7.0.6]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.5...7.0.6
[7.0.5]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.4...7.0.5
[7.0.4]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.3...7.0.4
[7.0.3]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.2...7.0.3
[7.0.2]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.1...7.0.2
[7.0.1]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.0...7.0.1
[7.0.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/6.1.4...7.0.0
[6.1.4]: https://github.com/sebastianbergmann/php-code-coverage/compare/6.1.3...6.1.4
[6.1.3]: https://github.com/sebastianbergmann/php-code-coverage/compare/6.1.2...6.1.3
[6.1.2]: https://github.com/sebastianbergmann/php-code-coverage/compare/6.1.1...6.1.2
[6.1.1]: https://github.com/sebastianbergmann/php-code-coverage/compare/6.1.0...6.1.1
[6.1.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/6.0...6.1.0

