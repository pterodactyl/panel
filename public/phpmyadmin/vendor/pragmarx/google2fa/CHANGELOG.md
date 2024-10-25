## Change Log

## [8.0.0] - 2020-05-05
### Added
- PHP 8 Support
- Tests
- Extract som test helpers
- PHPStan checks
### Changed
- PHP required version bumped to >= 7.1
- Exception interfaces extending Throwable  

## [7.0.0] - 2019-09-21
### Added
- PHPStan checks
### Removed
- Constants::ARGUMENT_NOT_SET - This is a BC break

## [6.1.3] - 2019-09-21
### Drafted
- To fix inserted BC break
 
## [6.1.2] - 2019-09-21 
### DELETED
- To fix inserted BC break

## [6.1.1] - 2019-09-21
### DELETED
- To fix inserted BC break

## [6.0.0] - 2019-09-11
### Added
- Base exception class and interfaces 
### Removed
- Support for PHP 5.4 to 7.0, will keep supporting PHP 7.1, 7.2, 7.3 & 7.4 

## [5.0.0] - 2019-05-19
### Changed
- Remove dead Google Charts API 

## [4.0.0] - 2018-10-06
### Changed
- Bacon QRCode package removed

## [3.0.1] - 2018-03-15
### Changed
- Relicensed to MIT

## [3.0.0] - 2018-03-07
### Changed
- It's now mandatory to enable Google Api secret key access by executing `setAllowInsecureCallToGoogleApis(true);`

## [2.0.4] - 2017-06-22
### Fixed
- Fix Base32 to keep supporting PHP 5.4 && 5.5.

## [2.0.3] - 2017-06-22
## [2.0.2] - 2017-06-21
## [2.0.1] - 2017-06-20
### Fixed
- Minor bugs

## [2.0.0] - 2017-06-20
### Changed
- Drop the Laravel support in favor of a bridge package (https://github.com/antonioribeiro/google2fa-laravel).
- Using a more secure Base 32 algorithm, to prevent cache-timing attacks.  
- Added verifyKeyNewer() method to prevent reuse of keys.
- Refactored to remove complexity, by extracting support methods.
- Created a package playground page (https://pragmarx.com/google2fa)

## [2.0.0] - 2017-06-20
### Changed
- Drop the Laravel support in favor of a bridge package (https://github.com/antonioribeiro/google2fa-laravel).
- Using a more secure Base 32 algorithm, to prevent cache-timing attacks.  
- Added verifyKeyNewer() method to prevent reuse of keys.
- Refactored to remove complexity, by extracting support methods.
- Created a package playground page (https://pragmarx.com/google2fa)

## [1.0.1] - 2016-07-18
### Changed
- Drop support for PHP 5.3.7, require PHP 5.4+. 
- Coding style is now PSR-2 automatically enforced by StyleCI.

## [1.0.0] - 2016-07-17
### Changed
- Package bacon/bacon-qr-code was moved to "suggest". 

## [0.8.1] - 2016-07-17
### Fixed
- Allow paragonie/random_compat ~1.4|~2.0.

## [0.8.0] - 2016-07-17
### Changed
- Bumped christian-riesen/base32 to ~1.3
- Use paragonie/random_compat to generate cryptographically secure random secret keys
- Readme improvements
- Drop simple-qrcode in favor of bacon/bacon-qr-code 
- Fix tavis setup for phpspec, PHP 7, hhvm and improve cache

## [0.7.0] - 2015-11-07
### Changed
- Fixed URL generation for QRCodes
- Avoid time attacks

## [0.2.0] - 2015-02-19
### Changed
- Laravel 5 compatibility.

## [0.1.0] - 2014-07-06
### Added
- First version.
