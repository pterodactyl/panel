# php-webdriver – Selenium WebDriver bindings for PHP

[![Latest stable version](https://img.shields.io/packagist/v/php-webdriver/webdriver.svg?style=flat-square&label=Packagist)](https://packagist.org/packages/php-webdriver/webdriver)
[![GitHub Actions build status](https://img.shields.io/github/workflow/status/php-webdriver/php-webdriver/Tests?style=flat-square&label=GitHub%20Actions)](https://github.com/php-webdriver/php-webdriver/actions)
[![SauceLabs test status](https://img.shields.io/github/workflow/status/php-webdriver/php-webdriver/Sauce%20Labs?style=flat-square&label=SauceLabs)](https://saucelabs.com/u/php-webdriver)
[![Total downloads](https://img.shields.io/packagist/dd/php-webdriver/webdriver.svg?style=flat-square&label=Downloads)](https://packagist.org/packages/php-webdriver/webdriver)

## Description
Php-webdriver library is PHP language binding for Selenium WebDriver, which allows you to control web browsers from PHP.

This library is compatible with Selenium server version 2.x, 3.x and 4.x.

The library supports modern [W3C WebDriver](https://w3c.github.io/webdriver/) protocol, as well
as legacy [JsonWireProtocol](https://www.selenium.dev/documentation/legacy/json_wire_protocol/).

The concepts of this library are very similar to the "official" Java, JavaScript, .NET, Python and Ruby libraries
which are developed as part of the [Selenium project](https://github.com/SeleniumHQ/selenium/).

## Installation

Installation is possible using [Composer](https://getcomposer.org/).

If you don't already use Composer, you can download the `composer.phar` binary:

    curl -sS https://getcomposer.org/installer | php

Then install the library:

    php composer.phar require php-webdriver/webdriver

## Upgrade from version <1.8.0

Starting from version 1.8.0, the project has been renamed from `facebook/php-webdriver` to `php-webdriver/webdriver`.

In order to receive the new version and future updates, **you need to rename it in your composer.json**:

```diff
"require": {
-    "facebook/webdriver": "(version you use)",
+    "php-webdriver/webdriver": "(version you use)",
}
```

and run `composer update`.

## Getting started

### 1. Start server (aka. remote end)

To control a browser, you need to start a *remote end* (server), which will listen to the commands sent
from this library and will execute them in the respective browser.

This could be Selenium standalone server, but for local development, you can send them directly to so-called "browser driver" like Chromedriver or Geckodriver.

#### a) Chromedriver

📙 Below you will find a simple example. Make sure to read our wiki for [more information on Chrome/Chromedriver](https://github.com/php-webdriver/php-webdriver/wiki/Chrome).

Install the latest Chrome and [Chromedriver](https://sites.google.com/chromium.org/driver/downloads).
Make sure to have a compatible version of Chromedriver and Chrome!

Run `chromedriver` binary, you can pass `port` argument, so that it listens on port 4444:

```sh
chromedriver --port=4444
```

#### b) Geckodriver

📙 Below you will find a simple example. Make sure to read our wiki for [more information on Firefox/Geckodriver](https://github.com/php-webdriver/php-webdriver/wiki/Firefox).

Install the latest Firefox and [Geckodriver](https://github.com/mozilla/geckodriver/releases).
Make sure to have a compatible version of Geckodriver and Firefox!

Run `geckodriver` binary (it start to listen on port 4444 by default):

```sh
geckodriver
```

#### c) Selenium standalone server

Selenium server can be useful when you need to execute multiple tests at once,
when you run tests in several different browsers (like on your CI server), or when you need to distribute tests amongst
several machines in grid mode (where one Selenium server acts as a hub, and others connect to it as nodes).

Selenium server then act like a proxy and takes care of distributing commands to the respective nodes.

The latest version can be found on the [Selenium download page](https://www.selenium.dev/downloads/).

📙 You can find [further Selenium server information](https://github.com/php-webdriver/php-webdriver/wiki/Selenium-server)
in our wiki.

#### d) Docker

Selenium server could also be started inside Docker container - see [docker-selenium project](https://github.com/SeleniumHQ/docker-selenium).

### 2. Create a Browser Session

When creating a browser session, be sure to pass the url of your running server.

For example:

```php
// Chromedriver (if started using --port=4444 as above)
$serverUrl = 'http://localhost:4444';
// Geckodriver
$serverUrl = 'http://localhost:4444';
// selenium-server-standalone-#.jar (version 2.x or 3.x)
$serverUrl = 'http://localhost:4444/wd/hub';
// selenium-server-standalone-#.jar (version 4.x)
$serverUrl = 'http://localhost:4444';
```

Now you can start browser of your choice:

```php
use Facebook\WebDriver\Remote\RemoteWebDriver;

// Chrome
$driver = RemoteWebDriver::create($serverUrl, DesiredCapabilities::chrome());
// Firefox
$driver = RemoteWebDriver::create($serverUrl, DesiredCapabilities::firefox());
// Microsoft Edge
$driver = RemoteWebDriver::create($serverUrl, DesiredCapabilities::microsoftEdge());
```

### 3. Customize Desired Capabilities

Desired capabilities define properties of the browser you are about to start.

They can be customized:

```php
use Facebook\WebDriver\Firefox\FirefoxOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;

$desiredCapabilities = DesiredCapabilities::firefox();

// Disable accepting SSL certificates
$desiredCapabilities->setCapability('acceptSslCerts', false);

// Add arguments via FirefoxOptions to start headless firefox
$firefoxOptions = new FirefoxOptions();
$firefoxOptions->addArguments(['-headless']);
$desiredCapabilities->setCapability(FirefoxOptions::CAPABILITY, $firefoxOptions);

$driver = RemoteWebDriver::create($serverUrl, $desiredCapabilities);
```

Capabilities can also be used to [📙 configure a proxy server](https://github.com/php-webdriver/php-webdriver/wiki/HowTo-Work-with-proxy) which the browser should use.

To configure browser-specific capabilities, you may use [📙 ChromeOptions](https://github.com/php-webdriver/php-webdriver/wiki/Chrome#chromeoptions)
or [📙 FirefoxOptions](https://github.com/php-webdriver/php-webdriver/wiki/Firefox#firefoxoptions).

* See [legacy JsonWire protocol](https://github.com/SeleniumHQ/selenium/wiki/DesiredCapabilities) documentation or [W3C WebDriver specification](https://w3c.github.io/webdriver/#capabilities) for more details.

### 4. Control your browser

```php
// Go to URL
$driver->get('https://en.wikipedia.org/wiki/Selenium_(software)');

// Find search element by its id, write 'PHP' inside and submit
$driver->findElement(WebDriverBy::id('searchInput')) // find search input element
    ->sendKeys('PHP') // fill the search box
    ->submit(); // submit the whole form

// Find element of 'History' item in menu by its css selector
$historyButton = $driver->findElement(
    WebDriverBy::cssSelector('#ca-history a')
);
// Read text of the element and print it to output
echo 'About to click to a button with text: ' . $historyButton->getText();

// Click the element to navigate to revision history page
$historyButton->click();

// Make sure to always call quit() at the end to terminate the browser session
$driver->quit();
```

See [example.php](example.php) for full example scenario.
Visit our GitHub wiki for [📙 php-webdriver command reference](https://github.com/php-webdriver/php-webdriver/wiki/Example-command-reference) and further examples.

**NOTE:** Above snippets are not intended to be a working example by simply copy-pasting. See [example.php](example.php) for a working example.

## Changelog
For latest changes see [CHANGELOG.md](CHANGELOG.md) file.

## More information

Some basic usage example is provided in [example.php](example.php) file.

How-tos are provided right here in [📙 our GitHub wiki](https://github.com/php-webdriver/php-webdriver/wiki).

If you don't use IDE, you may use [API documentation of php-webdriver](https://php-webdriver.github.io/php-webdriver/latest/).

You may also want to check out the Selenium project [docs](https://selenium.dev/documentation/en/) and [wiki](https://github.com/SeleniumHQ/selenium/wiki).

## Testing framework integration

To take advantage of automatized testing you may want to integrate php-webdriver to your testing framework.
There are some projects already providing this:

- [Symfony Panther](https://github.com/symfony/panther) uses php-webdriver and integrates with PHPUnit using `PantherTestCase`
- [Laravel Dusk](https://laravel.com/docs/dusk) is another project using php-webdriver, could be used for testing via `DuskTestCase`
- [Steward](https://github.com/lmc-eu/steward) integrates php-webdriver directly to [PHPUnit](https://phpunit.de/), and provides parallelization
- [Codeception](https://codeception.com/) testing framework provides BDD-layer on top of php-webdriver in its [WebDriver module](https://codeception.com/docs/modules/WebDriver)
- You can also check out this [blogpost](https://codeception.com/11-12-2013/working-with-phpunit-and-selenium-webdriver.html) + [demo project](https://github.com/DavertMik/php-webdriver-demo), describing simple [PHPUnit](https://phpunit.de/) integration

## Support

We have a great community willing to help you!

❓ Do you have a **question, idea or some general feedback**? Visit our [Discussions](https://github.com/php-webdriver/php-webdriver/discussions) page.
(Alternatively, you can [look for many answered questions also on StackOverflow](https://stackoverflow.com/questions/tagged/php+selenium-webdriver)).

🐛 Something isn't working, and you want to **report a bug**? [Submit it here](https://github.com/php-webdriver/php-webdriver/issues/new) as a new issue.

📙 Looking for a **how-to** or **reference documentation**? See [our wiki](https://github.com/php-webdriver/php-webdriver/wiki).

## Contributing ❤️

We love to have your help to make php-webdriver better. See [CONTRIBUTING.md](.github/CONTRIBUTING.md) for more information about contributing and developing php-webdriver.

Php-webdriver is community project - if you want to join the effort with maintaining and developing this library, the best is to look on [issues marked with "help wanted"](https://github.com/php-webdriver/php-webdriver/issues?q=is%3Aissue+is%3Aopen+label%3A%22help+wanted%22)
label. Let us know in the issue comments if you want to contribute and if you want any guidance, and we will be delighted to help you to prepare your pull request.
