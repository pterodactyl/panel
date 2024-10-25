# Changelog
This project versioning adheres to [Semantic Versioning](http://semver.org/).

## Unreleased

## 1.13.1 - 2022-10-11
### Fixed
- Do not fail when using `isDisplayed()` and capabilities are missing in WebDriver instance. (Happens when driver instance was created using `RemoteWebDriver::createBySessionID()`.)

## 1.13.0 - 2022-10-03
### Added
- Support for current Firefox XPI extension format. Extensions could now be loaded into `FirefoxProfile` using `addExtension()` method.
- `setProfile()` method to `FirefoxOptions`, which is now a preferred way to set Firefox Profile.
- Element `isDisplayed()` can now be used even for browsers not supporting native API endpoint (like Safari), thanks to javascript atom workaround.

### Changed
- Handle errors when taking screenshots. `WebDriverException` is thrown if WebDriver returns empty or invalid screenshot data.
- Deprecate `FirefoxDriver::PROFILE` constant. Instead, use `setProfile()` method of `FirefoxOptions` to set Firefox Profile.
- Deprecate `getAllSessions()` method of `RemoteWebDriver` (which is not part of W3C WebDriver).
- Increase default request timeout to 3 minutes (instead of 30 seconds).

### Fixed
- Throw `UnknownErrorException` instead of fatal error if remote end returns invalid response for `findElement()`/`findElements()` commands.

## 1.12.1 - 2022-05-03
### Fixed
- Improper PHP documentation for `getAttribute()` and `getDomProperty()`.
- Unsafe use of `static::` when accessing private property in `DesiredCapabilities`.
- PHP 8.1 deprecations in the `Cookie` class.

### Changed
- Docs: Extend `findElement()`/`findElements()` method documentation to better explain XPath behavior.
- Add `@return` and `@param` type annotations to Cookie class to avoid deprecations in PHP 8.1.

## 1.12.0 - 2021-10-14
### Added
- `RemoteWebElement::getDomProperty()` method to read JavaScript properties of an element (like the value of `innerHTML` etc.) in W3C mode.
- `WebDriverCommand::newSession()` constructor to create new session command without violating typehints.

### Changed
- Allow installation of Symfony 6 components.

### Fixed
- PHP 8.1 compatibility.

## 1.11.1 - 2021-05-21
### Fixed
- `RemoteWebElement::getLocationOnScreenOnceScrolledIntoView()` was missing polyfill implementation for W3C mode and not working in eg. Safari.

## 1.11.0 - 2021-05-03
### Added
- `FirefoxOptions` class to simplify passing Firefox capabilities. Usage is covered [in our wiki](https://github.com/php-webdriver/php-webdriver/wiki/Firefox#firefoxoptions).
- `FirefoxDriver` to easy local start of Firefox instance without a need to start the `geckodriver` process manually. [See wiki](https://github.com/php-webdriver/php-webdriver/wiki/Firefox#start-directly-using-firefoxdriver-class) for usage examples.
- Method `ChromeDriver::startUsingDriverService()` to be used for creating ChromeDriver instance with custom service.

### Fixed
- Driver capabilities received from the browser when creating now session were not set to the instance of ChromeDriver (when ChromeDriver::start() was used).

### Changed
- Deprecate `ChromeDriver::startSession`. However, the method was supposed to be used only internally.
- KeyDown and KeyUp actions will throw an exception when not used with modifier keys.

## 1.10.0 - 2021-02-25
### Added
- Support for sending Chrome DevTools Protocol commands (see details in [wiki](https://github.com/php-webdriver/php-webdriver/wiki/Chrome#chrome-devtools-protocol-cdp)).
- Option to specify type of new window (window or tab) when using `$driver->switchTo()->newWindow()`.

### Fixed
- Actually start ChromeDriver in W3C mode if it is supported by the browser driver. Until now, when it was initialized using `ChromeDriver::start()`, it has always been unintentionally started in OSS mode.
- ChromeOptions were ignored when passed to DesiredCapabilities as `ChromeOptions::CAPABILITY_W3C`.
- Clicking a block element inside `<a>` element in Firefox (workaround for GeckoDriver bug [1374283](https://bugzilla.mozilla.org/show_bug.cgi?id=1374283)).

### Changed
- Throw `DriverServerDiedException` on local driver process terminating unexpectedly and provide full details of original exception to improve debugging.
- Do not require `WEBDRIVER_CHROME_DRIVER` environment variable to be set if `chromedriver` binary is already available via system PATH.
- Mark PhantomJS deprecated, as it is no longer developed and maintained.
- Deprecate `RemoteWebDriver::newWindow()` in favor of `$driver->switchTo()->newWindow()`.
- Don't escape slashes in CURL exception message to improve readability.

## 1.9.0 - 2020-11-19
### Added
- Support of SameSite cookie property.
- Command `RemoteWebDriver::newWindow()` for W3C mode to open new top-level browsing context (aka window).
- PHP 8.0 support.

## 1.8.3 - 2020-10-06
### Fixed
- Make `alertIsPresent()` condition working in W3C mode.
- `RemoteWebDriver::create()` cannot be used without providing the second parameter (which is in fact optional).
- `ChromeDriver::start()` starts in inconsistent state mixing W3C/OSS mode.
- Modifier keys are not released when sending NULL key in GeckoDriver (workaround for GeckoDriver bug [1494661](https://bugzilla.mozilla.org/show_bug.cgi?id=1494661)).
- Do not set unnecessary `binary` value of `goog:chromeOptions` while keep the object in proper data type required by ChromeDriver.

## 1.8.2 - 2020-03-04
### Changed
- Reimplement element `equals()` method to be working in W3C mode.
- New instance of `RemoteWebDriver` created via `createBySessionID()` by default expects W3C mode. This could be disabled using fifth parameter of `createBySessionID()`.
- Disable JSON viewer in Firefox to let JSON response be rendered as-is.

### Fixed
- Properly read fifth parameter whether W3C compliant instance should be created when using `createBySessionID()`.
- Creating of Firefox profile with libzip 1.6+ (eg. on Mac OS Catalina).

## 1.8.1 - 2020-02-17
### Fixed
- Accept array as possible input to `sendKeys()` method. (Unintentional BC break in 1.8.0.)
- Use relative offset when moving mouse pointer in W3C WebDriver mode.

## 1.8.0 - 2020-02-10
### Added
- Experimental W3C WebDriver protocol support. The protocol will be used automatically when remote end (like Geckodriver, newer Chromedriver etc.) supports it.
- `getStatus()` method of `RemoteWebDriver` to get information about remote-end readiness to create new sessions.
- `takeElementScreenshot()` method of `RemoteWebElement` to do the obvious - take screenshot of the particular element.
- Support for sending custom commands via `executeCustomCommand()`. See [wiki](https://github.com/php-webdriver/php-webdriver/wiki/Custom-commands) for more information.

### Changed
- The repository was migrated to [`php-webdriver/php-webdriver`](https://github.com/php-webdriver/php-webdriver/).
- The Packagist package was renamed to [`php-webdriver/webdriver`](https://packagist.org/packages/php-webdriver/webdriver) and the original [`facebook/webdriver`](https://packagist.org/packages/facebook/webdriver) was marked as abandoned.
- Revert no longer needed workaround for Chromedriver bug [2943](https://bugs.chromium.org/p/chromedriver/issues/detail?id=2943).
- Allow installation of Symfony 5 components.
- Rename environment variable used to pass path to ChromeDriver executable from `webdriver.chrome.driver` to `WEBDRIVER_CHROME_DRIVER`. However the old one also still works to keep backward compatibility
- If subdirectories in a path to screenshot destination does not exists (using `takeScreenshot()` or `takeElementScreenshot()` methods), they are automatically created.
- When zip archive cannot be created during file upload, throw an exception instead of silently returning false.
- `WebDriverNavigation` and `EventFiringWebDriverNavigation` now both implement new `WebDriverNavigationInterface`.

### Fixed
- `WebDriverExpectedCondition::presenceOfElementLocated()` works correctly when used within `WebDriverExpectedCondition::not()`.
- Improper behavior of Microsoft Edge when retrieving all cookies via `getCookies()` (it was causing fatal error  when there were no cookies).
- Avoid "path is not canonical" error when uploading file to Chromedriver.

## 1.7.1 - 2019-06-13
### Fixed
- Error `Call to a member function toArray()` if capabilities were already converted to an array.
- Temporarily do not send capabilities to disable W3C WebDriver protocol when BrowserStack hub is used.

## 1.7.0 - 2019-06-10
### Added
- `WebDriverCheckboxes` and `WebDriverRadios` helper classes to simplify interaction with checkboxes and radio buttons.

### Fixed
- Stop sending null values in Cookie object, which is against the protocol and may cause request to remote ends to fail.

### Changed
- Force Chrome to not use W3C WebDriver protocol.
- Add workaround for Chromedriver bug [2943](https://bugs.chromium.org/p/chromedriver/issues/detail?id=2943) which breaks the protocol in Chromedriver 75.

## 1.6.0 - 2018-05-16
### Added
- Connection and request timeouts could be specified also when creating RemoteWebDriver from existing session ID.
- Update PHPDoc for functions that return static instances of a class.

### Changed
- Disable sending 'Expect: 100-Continue' header with POST requests, as they may more easily fail when sending via eg. squid proxy.

## 1.5.0 - 2017-11-15
### Changed
- Drop PHP 5.5 support, the minimal required version of PHP is now PHP 5.6.
- Allow installation of Symfony 4 components.

### Added
- Add a `visibilityOfAnyElementsLocated()` method to `WebDriverExpectedCondition`.

## 1.4.1 - 2017-04-28
### Fixed
- Do not throw notice `Constant CURLOPT_CONNECTTIMEOUT_MS already defined`.

## 1.4.0 - 2017-03-22
### Changed
- Cookies should now be set using `Cookie` value object instead of an array when passed to to `addCookie()` method of `WebDriverOptions`.
- Cookies retrieved using `getCookieNamed()` and `getCookies()` methods of `WebDriverOptions` are now encapsulated in `Cookie` object instead of an plain array. The object implements `ArrayAccess` interface to provide backward compatibility.
- `ext-zip` is now specified as required dependency in composer.json (but the extension was already required by the code, though).
- Deprecate `WebDriverCapabilities::isJavascriptEnabled()` method.
- Deprecate `textToBePresentInElementValue` expected condition in favor of `elementValueContains`.

### Fixed
- Do not throw fatal error when `null` is passed to `sendKeys()`.

## 1.3.0 - 2017-01-13
### Added
- Added `getCapabilities()` method of `RemoteWebDriver`, to retrieve actual capabilities acknowledged by the remote driver on startup.
- Added option to pass required capabilities when creating `RemoteWebDriver`. (So far only desired capabilities were supported.)
- Added new expected conditions:
    - `urlIs` - current URL exactly equals given value
    - `urlContains` - current URL contains given text
    - `urlMatches` - current URL matches regular expression
    - `titleMatches` - current page title matches regular expression
    - `elementTextIs` - text in element exactly equals given text
    - `elementTextContains` (as an alias for `textToBePresentInElement`) - text in element contains given text
    - `elementTextMatches` - text in element matches regular expression
    - `numberOfWindowsToBe` - number of opened windows equals given number
- Possibility to select option of `<select>` by its partial text (using `selectByVisiblePartialText()`).
- `XPathEscaper` helper class to quote XPaths containing both single and double quotes.
- `WebDriverSelectInterface`, to allow implementation of custom select-like components, eg. those not built around and actual select tag.

### Changed
- `Symfony\Process` is used to start local WebDriver processes (when browsers are run directly, without Selenium server) to workaround some PHP bugs and improve portability.
- Clarified meaning of selenium server URL variable in methods of `RemoteWebDriver` class.
- Deprecated `setSessionID()` and `setCommandExecutor()` methods of `RemoteWebDriver` class; these values should be immutable and thus passed only via constructor.
- Deprecated `WebDriverExpectedCondition::textToBePresentInElement()` in favor of `elementTextContains()`.
- Throw an exception when attempting to deselect options of non-multiselect (it already didn't have any effect, but was silently ignored).
- Optimize performance of `(de)selectByIndex()` and `getAllSelectedOptions()` methods of `WebDriverSelect` when used with non-multiple select element.

### Fixed
- XPath escaping in `select*()` and `deselect*()` methods of `WebDriverSelect`.

## 1.2.0 - 2016-10-14
- Added initial support of remote Microsoft Edge browser (but starting local EdgeDriver is still not supported).
- Utilize late static binding to make eg. `WebDriverBy` and `DesiredCapabilities` classes easily extensible.
- PHP version at least 5.5 is required.
- Fixed incompatibility with Appium, caused by redundant params present in requests to Selenium server.

## 1.1.3 - 2016-08-10
- Fixed FirefoxProfile to support installation of extensions with custom namespace prefix in their manifest file.
- Comply codestyle with [PSR-2](http://www.php-fig.org/psr/psr-2/).

## 1.1.2 - 2016-06-04
- Added ext-curl to composer.json.
- Added CHANGELOG.md.
- Added CONTRIBUTING.md with information and rules for contributors.

## 1.1.1 - 2015-12-31
- Fixed strict standards error in `ChromeDriver`.
- Added unit tests for `WebDriverCommand` and `DesiredCapabilities`.
- Fixed retrieving temporary path name in `FirefoxDriver` when `open_basedir` restriction is in effect.

## 1.1.0 - 2015-12-08
- FirefoxProfile improved - added possibility to set RDF file and to add datas for extensions.
- Fixed setting 0 second timeout of `WebDriverWait`.
