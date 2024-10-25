# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [3.0.3] - 2020-11-30

### Changed

* Changed PHP version constraint in `composer.json` from `^7.1` to `>=7.1`

## [3.0.2] - 2019-02-04

### Changed

* `Chunk::setLines()` now ensures that the `$lines` array only contains `Line` objects

## [3.0.1] - 2018-06-10

### Fixed

* Removed `"minimum-stability": "dev",` from `composer.json`

## [3.0.0] - 2018-02-01

* The `StrictUnifiedDiffOutputBuilder` implementation of the `DiffOutputBuilderInterface` was added

### Changed

* The default `DiffOutputBuilderInterface` implementation now generates context lines (unchanged lines)

### Removed

* Removed support for PHP 7.0

### Fixed

* Fixed [#70](https://github.com/sebastianbergmann/diff/issues/70): Diffing of arrays no longer works

## [2.0.1] - 2017-08-03

### Fixed

* Fixed [#66](https://github.com/sebastianbergmann/diff/pull/66): Restored backwards compatibility for PHPUnit 6.1.4, 6.2.0, 6.2.1, 6.2.2, and 6.2.3

## [2.0.0] - 2017-07-11 [YANKED]

### Added

* Implemented [#64](https://github.com/sebastianbergmann/diff/pull/64): Show line numbers for chunks of a diff

### Removed

* This component is no longer supported on PHP 5.6

[3.0.3]: https://github.com/sebastianbergmann/diff/compare/3.0.2...3.0.3
[3.0.2]: https://github.com/sebastianbergmann/diff/compare/3.0.1...3.0.2
[3.0.1]: https://github.com/sebastianbergmann/diff/compare/3.0.0...3.0.1
[3.0.0]: https://github.com/sebastianbergmann/diff/compare/2.0...3.0.0
[2.0.1]: https://github.com/sebastianbergmann/diff/compare/c341c98ce083db77f896a0aa64f5ee7652915970...2.0.1
[2.0.0]: https://github.com/sebastianbergmann/diff/compare/1.4...c341c98ce083db77f896a0aa64f5ee7652915970
