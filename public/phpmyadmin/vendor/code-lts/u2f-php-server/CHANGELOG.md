# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased] - yyyy-mm-dd

## [1.2.2] - 2024-07-24

- Update and fix doc blocks
- Support PHPUnit 10
- Improve tests for PHPUnit 10 & 11
- Exclude tests from the vendor tarball

## [1.2.1] - 2022-12-03

- Don't replace `samyoul/u2f-php-server` anymore in `composer.json`
- Exclude tests and phpunit config from vendor archives
- Apply `wdes/coding-standard` and make constants officially public

## [1.2.0] - 2021-12-12

- Added `ReturnTypeWillChange` for PHP 8.1 on SignRequest and RegistrationRequest
- Wrote tests for most of the code of this library
- Run tests on GitHub actions
- Changed the namespace from `Samyoul` to `CodeLts`
- Cleaned up the code
- Added a `.gitattributes` file
- Improved phpdoc blocks
- Wrote betters comments
- Added a CHANGELOG file
- Improved the README file

## [1.1.4] - 2018-10-26

- Fix issue when there is more than one U2F key registered

## [1.1.3] - 2016-12-14

- Fix public property access bug in `U2FServer` class
- Made `SignRequest` class json `JsonSerializable`

## [1.1.2] - 2016-12-13

- Replaced `toString()` with `jsonSerialize()` on `RegistrationRequest` class

## [1.1.1] - 2016-12-13

- Added `toString()` on `RegistrationRequest` class

## [1.1.0] - 2016-12-13

- Re-Namespaced the classes

## [1.0.0] - 2016-12-12

- First version
