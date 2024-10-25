# reCAPTCHA PHP client library

[![Build Status](https://travis-ci.org/google/recaptcha.svg)](https://travis-ci.org/google/recaptcha)
[![Coverage Status](https://coveralls.io/repos/github/google/recaptcha/badge.svg)](https://coveralls.io/github/google/recaptcha)
[![Latest Stable Version](https://poser.pugx.org/google/recaptcha/v/stable.svg)](https://packagist.org/packages/google/recaptcha)
[![Total Downloads](https://poser.pugx.org/google/recaptcha/downloads.svg)](https://packagist.org/packages/google/recaptcha)

reCAPTCHA is a free CAPTCHA service that protects websites from spam and abuse.
This is a PHP library that wraps up the server-side verification step required
to process responses from the reCAPTCHA service. This client supports both v2
and v3.

- reCAPTCHA: https://www.google.com/recaptcha
- This repo: https://github.com/google/recaptcha
- Hosted demo: https://recaptcha-demo.appspot.com/
- Version: 1.2.4
- License: BSD, see [LICENSE](LICENSE)

## Installation

### Composer (recommended)

Use [Composer](https://getcomposer.org) to install this library from Packagist:
[`google/recaptcha`](https://packagist.org/packages/google/recaptcha)

Run the following command from your project directory to add the dependency:

```sh
composer require google/recaptcha "^1.2"
```

Alternatively, add the dependency directly to your `composer.json` file:

```json
"require": {
    "google/recaptcha": "^1.2"
}
```

### Direct download

Download the [ZIP file](https://github.com/google/recaptcha/archive/master.zip)
and extract into your project. An autoloader script is provided in
`src/autoload.php` which you can require into your script. For example:

```php
require_once '/path/to/recaptcha/src/autoload.php';
$recaptcha = new \ReCaptcha\ReCaptcha($secret);
```

The classes in the project are structured according to the
[PSR-4](http://www.php-fig.org/psr/psr-4/) standard, so you can also use your
own autoloader or require the needed files directly in your code.

## Usage

First obtain the appropriate keys for the type of reCAPTCHA you wish to
integrate for v2 at https://www.google.com/recaptcha/admin or v3 at
https://g.co/recaptcha/v3.

Then follow the [integration guide on the developer
site](https://developers.google.com/recaptcha/intro) to add the reCAPTCHA
functionality into your frontend.

This library comes in when you need to verify the user's response. On the PHP
side you need the response from the reCAPTCHA service and secret key from your
credentials. Instantiate the `ReCaptcha` class with your secret key, specify any
additional validation rules, and then call `verify()` with the reCAPTCHA
response and user's IP address. For example:

```php
<?php
$recaptcha = new \ReCaptcha\ReCaptcha($secret);
$resp = $recaptcha->setExpectedHostname('recaptcha-demo.appspot.com')
                  ->verify($gRecaptchaResponse, $remoteIp);
if ($resp->isSuccess()) {
    // Verified!
} else {
    $errors = $resp->getErrorCodes();
}
```

The following methods are available:

- `setExpectedHostname($hostname)`: ensures the hostname matches. You must do
  this if you have disabled "Domain/Package Name Validation" for your
  credentials.
- `setExpectedApkPackageName($apkPackageName)`: if you're verifying a response
  from an Android app. Again, you must do this if you have disabled
  "Domain/Package Name Validation" for your credentials.
- `setExpectedAction($action)`: ensures the action matches for the v3 API.
- `setScoreThreshold($threshold)`: set a score threshold for responses from the
  v3 API
- `setChallengeTimeout($timeoutSeconds)`: set a timeout between the user passing
  the reCAPTCHA and your server processing it.

Each of the `set`\*`()` methods return the `ReCaptcha` instance so you can chain
them together. For example:

```php
<?php
$recaptcha = new \ReCaptcha\ReCaptcha($secret);
$resp = $recaptcha->setExpectedHostname('recaptcha-demo.appspot.com')
                  ->setExpectedAction('homepage')
                  ->setScoreThreshold(0.5)
                  ->verify($gRecaptchaResponse, $remoteIp);

if ($resp->isSuccess()) {
    // Verified!
} else {
    $errors = $resp->getErrorCodes();
}
```

You can find the constants for the libraries error codes in the `ReCaptcha`
class constants, e.g. `ReCaptcha::E_HOSTNAME_MISMATCH`

For more details on usage and structure, see [ARCHITECTURE](ARCHITECTURE.md).

### Examples

You can see examples of each reCAPTCHA type in [examples/](examples/). You can
run the examples locally by using the Composer script:

```sh
composer run-script serve-examples
```

This makes use of the in-built PHP dev server to host the examples at
http://localhost:8080/

These are also hosted on Google AppEngine Flexible environment at
https://recaptcha-demo.appspot.com/. This is configured by
[`app.yaml`](./app.yaml) which you can also use to [deploy to your own AppEngine
project](https://cloud.google.com/appengine/docs/flexible/php/download).

## Contributing

No one ever has enough engineers, so we're very happy to accept contributions
via Pull Requests. For details, see [CONTRIBUTING](CONTRIBUTING.md)
