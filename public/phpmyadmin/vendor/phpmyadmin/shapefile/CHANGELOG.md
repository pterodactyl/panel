# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [3.0.1] - 2021-02-05
### Fixed
- Fix method signature of ShapeFile::getDBFHeader()

## [3.0.0] - 2021-02-05
### Added
- Support for PHPUnit 8 and 9
- Support PHP 8

### Changed
- Enable strict mode on PHP files
- Rename ShapeFile::$FileName property to ShapeFile::$fileName
- Rename ShapeRecord::$SHPData property to ShapeRecord::$shpData
- Rename ShapeRecord::$DBFData property to ShapeRecord::$dbfData
- ShapeRecord::getContentLength returns `null` when the shape type is not supported instead of `false`.

### Removed
- Drop support for PHP 5.4, PHP 5.5, PHP 5.6, PHP 7.0 and HHVM

## [2.1] - 2017-05-15
### Changed
- Documentation improvements.

## [2.0] - 2017-01-23
### Changed
- Switched to PhpMyAdmin vendor namespace to follow PSR-4.

## [1.2] - 2017-01-07
### Added
- PHP 7.2 support.

### Changed
- Coding style cleanup.
- Avoid installing tests and test data using composer.

## [1.1] - 2016-11-21
### Fixed
- Fixed adjusting of record bounding box

## [1.0] - 2016-11-21
### Changed
- Documentation improvements
- Code cleanups

## [0.13] - 2016-11-21
### Changed
- Code cleanups
- Improved test coverage

## [0.12] - 2016-11-17
### Changed
- Improved test coverage

### Fixed
- Fixed DBF search

## [0.11] - 2016-11-16
### Changed
- Code cleanups

### Fixed
- Fixed behavior without configured DBF header
- Fixed saving Polygon/Polyline creation with multiple parts
- Fixed saving Multipont records

## [0.10] - 2016-09-05
### Changed
- Improved error handling on loading

## [0.9] - 2016-08-04
### Changed
- Code cleanups

## [0.8] - 2016-06-24
### Changed
- Code cleanups

### Fixed
- Fixed loading of records with optional data

## [0.7] - 2016-06-24
### Fixed
- Properly fail on loading corrupted files

## [0.6] - 2016-06-24
### Fixed
- Fixed detection of end of file when loading

## [0.5] - 2016-06-24
### Added
- Added getShapeName method to ShapeFile

## [0.4] - 2016-06-24
### Changed
- Make API work even without real file open

## [0.3] - 2016-06-24
### Added
- Better support for subclassing

## [0.2] - 2016-06-24
### Changed
- Make the dbase extension optional dependency

## 0.1 - 2016-06-14
### Added
- Inital release based on bfShapeFiles

[Unreleased]: https://github.com/phpmyadmin/shapefile/compare/2.1...HEAD
[2.1]: https://github.com/phpmyadmin/shapefile/compare/2.0...2.1
[2.0]: https://github.com/phpmyadmin/shapefile/compare/1.2...2.0
[1.2]: https://github.com/phpmyadmin/shapefile/compare/1.1...1.2
[1.1]: https://github.com/phpmyadmin/shapefile/compare/1.0...1.1
[1.0]: https://github.com/phpmyadmin/shapefile/compare/0.13...1.0
[0.13]: https://github.com/phpmyadmin/shapefile/compare/0.12...0.13
[0.12]: https://github.com/phpmyadmin/shapefile/compare/0.11...0.12
[0.11]: https://github.com/phpmyadmin/shapefile/compare/0.10...0.11
[0.10]: https://github.com/phpmyadmin/shapefile/compare/0.9...0.10
[0.9]: https://github.com/phpmyadmin/shapefile/compare/0.8...0.9
[0.8]: https://github.com/phpmyadmin/shapefile/compare/0.7...0.8
[0.7]: https://github.com/phpmyadmin/shapefile/compare/0.6...0.7
[0.6]: https://github.com/phpmyadmin/shapefile/compare/0.5...0.6
[0.5]: https://github.com/phpmyadmin/shapefile/compare/0.4...0.5
[0.4]: https://github.com/phpmyadmin/shapefile/compare/0.3...0.4
[0.3]: https://github.com/phpmyadmin/shapefile/compare/0.2...0.3
[0.2]: https://github.com/phpmyadmin/shapefile/compare/0.1...0.2
