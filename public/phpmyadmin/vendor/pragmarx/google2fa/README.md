# Google2FA
## Google Two-Factor Authentication for PHP

Google2FA is a PHP implementation of the Google Two-Factor Authentication Module, supporting the HMAC-Based One-time Password (HOTP) algorithm specified in [RFC 4226](https://tools.ietf.org/html/rfc4226) and the Time-based One-time Password (TOTP) algorithm specified in [RFC 6238](https://tools.ietf.org/html/rfc6238).

---

<p align="center">
    <a href="https://packagist.org/packages/pragmarx/google2fa"><img alt="Latest Stable Version" src="https://img.shields.io/packagist/v/pragmarx/google2fa.svg?style=flat-square"></a>
    <a href="LICENSE.md"><img alt="License" src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square"></a>
    <a href="https://github.com/antonioribeiro/google2fa/actions"><img alt="Build" src="https://img.shields.io/github/actions/workflow/status/antonioribeiro/google2fa/phpunit.yml?style=flat-square"></a>
    <a href="https://github.com/antonioribeiro/google2fa/actions"><img alt="Static Analysis" src="https://img.shields.io/github/actions/workflow/status/antonioribeiro/google2fa/static-analysis.yml?style=flat-square&label=static-analysis"></a>
</p>
<p align="center">
    <a href="https://codecov.io/gh/antonioribeiro/google2fa"><img alt="Coverage" src="https://img.shields.io/codecov/c/github/antonioribeiro/google2fa/9.x?style=flat-square"></a>
    <a href="https://packagist.org/packages/pragmarx/google2fa"><img alt="PHP" src="https://img.shields.io/badge/PHP-7.4%20%7C%208.0%20%7C%208.1%20%7C%208.2%20%7C%208.3%20%7C%208.4%20%7C%208.5-green.svg?style=flat-square"></a>
    <a href="https://packagist.org/packages/pragmarx/google2fa"><img alt="Downloads" src="https://img.shields.io/packagist/dt/pragmarx/google2fa.svg?style=flat-square"></a>
</p>

---

## Menu

  - [Version Compatibility](#version-compatibility)
  - [Google Two-Factor Authentication for PHP](#google-two-factor-authentication-for-php)
  - [Laravel bridge](#laravel-bridge)
  - [Demos, Example & Playground](#demos-example--playground)
  - [Requirements](#requirements)
  - [Installing](#installing)
  - [Usage](#usage)
  - [How To Generate And Use Two Factor Authentication](#how-to-generate-and-use-two-factor-authentication)
  - [Generating QRCodes](#generating-qrcodes)
  - [QR Code Packages](#qr-code-packages)
  - [Examples of Usage](#examples-of-usage)
  - [HMAC Algorithms](#hmac-algorithms)
  - [Server Time](#server-time)
  - [Validation Window](#validation-window)
  - [Using a Bigger and Prefixing the Secret Key](#using-a-bigger-and-prefixing-the-secret-key)
  - [Google Authenticator secret key compatibility](#google-authenticator-secret-key-compatibility)
  - [Google Authenticator Apps](#google-authenticator-apps)
  - [Deprecation Warning](#deprecation-warning)
  - [Testing](#testing)
  - [Authors](#authors)
  - [License](#license)
  - [Contributing](#contributing)

## Version Compatibility

 PHP     | Google2FA
:--------|:----------
 7.4        | 8.x & 9.x
 8.0        | 8.x & 9.x
 8.1        | 8.x & 9.x
 8.2        | 8.x & 9.x
 8.3        | 8.x & 9.x
 8.4        | 8.x & 9.x
 8.5 (beta) | 8.x & 9.x

## ⚠️ Version 9.0.0 Breaking Change

### Default Secret Key Length Increased

**Version 9.0.0** introduces a **breaking change**: The default secret key length has been increased from **16 to 32 characters** for enhanced security.

#### What Changed?
- `generateSecretKey()` now generates 32-character secrets by default (previously 16)
- This increases cryptographic entropy from 80 bits to 160 bits
- Maintains full compatibility with Google Authenticator and other TOTP apps

#### Migration Guide

**If you want to keep the previous behavior (16-character secrets):**
```php
// Old default behavior (v8.x and below)
$secret = $google2fa->generateSecretKey();

// New way to get 16-character secrets (v9.0+)
$secret = $google2fa->generateSecretKey(16);
```

**If you want to use the new default (32-character secrets):**
```php
// This now generates 32-character secrets by default
$secret = $google2fa->generateSecretKey();
```

#### Potential Impact Areas
- **Database schemas**: Check if your `google2fa_secret` columns can handle 32 characters
- **Validation rules**: Update any length validations that expect exactly 16 characters
- **Tests**: Update test assertions expecting 16-character secrets
- **UI components**: Ensure QR code displays and secret key fields accommodate longer secrets

**Important**: Existing 16-character secrets remain fully functional. Database updates are only needed if you want to use the new 32-character default behavior.

#### Why This Change?
While 16-character secrets meet RFC 6238 minimum requirements, 32-character secrets provide significantly better security:
- **16 chars**: 80 bits of entropy (adequate but minimal)
- **32 chars**: 160 bits of entropy (much stronger against brute force)

This change aligns with modern security best practices for cryptographic applications.

## Laravel bridge

This package is agnostic, but there's a [Laravel bridge](https://github.com/antonioribeiro/google2fa-laravel).

## About QRCode generation

This package does not generate QRCodes for 2FA.

If you are looking for Google Two-Factor Authentication, but also need to generate QRCode for it, you can use the [Google2FA QRCode package](https://github.com/antonioribeiro/google2fa-qrcode), which integrates this package and also generates QRCodes using the BaconQRCode library, or check options on how to do it yourself [here in the docs](#qr-code-packages).

## Demos, Example & Playground

Please check the [Google2FA Package Playground](http://pragmarx.com/playground/google2fa).

![playground](docs/playground.jpg)

Here's a demo app showing how to use Google2FA: [google2fa-example](https://github.com/antonioribeiro/google2fa-example).

You can scan the QR code on [this (old) demo page](https://antoniocarlosribeiro.com/technology/google2fa) with a Google Authenticator app and view the code changing (almost) in real time.

## Requirements

- PHP 7.1 or greater

## Installing

Use Composer to install it:

    composer require pragmarx/google2fa

To generate inline QRCodes, you'll need to install a QR code generator, e.g. [BaconQrCode](https://github.com/Bacon/BaconQrCode):

    composer require bacon/bacon-qr-code

## Usage

### Instantiate it directly

```php
use PragmaRX\Google2FA\Google2FA;

$google2fa = new Google2FA();

return $google2fa->generateSecretKey();
```

## How To Generate And Use Two Factor Authentication

Generate a secret key for your user and save it:

```php
// Generates a 32-character secret key (v9.0.0+ default)
$user->google2fa_secret = $google2fa->generateSecretKey();

// Or explicitly specify 16 characters for compatibility
$user->google2fa_secret = $google2fa->generateSecretKey(16);
```

## Generating QRCodes

The more secure way of creating QRCode is to do it yourself or using a library. First you have to install a QR code generator e.g. BaconQrCode, as stated above, then you just have to generate the QR code url using:

```php
$qrCodeUrl = $google2fa->getQRCodeUrl(
    $companyName,
    $companyEmail,
    $secretKey
);
```

Once you have the QR code url, you can feed it to your preferred QR code generator.

```php
// Use your own QR Code generator to generate a data URL:
$google2fa_url = custom_generate_qrcode_url($qrCodeUrl);

/// and in your view:

<img src="{{ $google2fa_url }}" alt="">
```

And to verify, you just have to:

```php
$secret = $request->input('secret');

$valid = $google2fa->verifyKey($user->google2fa_secret, $secret);
```

## QR Code Packages

This package suggests the use of [Bacon/QRCode](https://github.com/Bacon/BaconQrCode) because
it is known as a good QR Code package, but you can use it with any other package, for
instance [Google2FA QRCode](https://github.com/antonioribeiro/google2fa-qrcode),
[Simple QrCode](https://www.simplesoftware.io/docs/simple-qrcode)
or [Endroid QR Code](https://github.com/endroid/qr-code), all of them use
[Bacon/QRCode](https://github.com/Bacon/BaconQrCode) to produce QR Codes.

Usually you'll need a 2FA URL, so you just have to use the URL generator:

```php
$google2fa->getQRCodeUrl($companyName, $companyEmail, $secretKey)
```

## Examples of Usage

### [Google2FA QRCode](https://github.com/antonioribeiro/google2fa-qrcode)

Get a QRCode to be used inline:

```php
$google2fa = (new \PragmaRX\Google2FAQRCode\Google2FA());

$inlineUrl = $google2fa->getQRCodeInline(
    'Company Name',
    'company@email.com',
    $google2fa->generateSecretKey()
);
```

And use in your template:

```php
<img src="{{ $inlineUrl }}">
```

### [Simple QrCode](https://www.simplesoftware.io/docs/simple-qrcode)

```php
<div class="visible-print text-center">
    {!! QrCode::size(100)->generate($google2fa->getQRCodeUrl($companyName, $companyEmail, $secretKey)); !!}
    <p>Scan me to return to the original page.</p>
</div>
```

### [Endroid QR Code Generator](https://github.com/endroid/qr-code)

Generate the data URL

```php

$qrCode = new \Endroid\QrCode\QrCode($value);
$qrCode->setSize(100);
$google2fa_url = $qrCode->writeDataUri();
```

And in your view

```php
<div class="visible-print text-center">
    {!! $google2fa_url !!}
    <p>Scan me to return to the original page.</p>
</div>
```

### [Bacon/QRCode](https://github.com/Bacon/BaconQrCode)

```php
<?php

use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

$google2fa = app(Google2FA::class);

$g2faUrl = $google2fa->getQRCodeUrl(
    'pragmarx',
    'google2fa@pragmarx.com',
    $google2fa->generateSecretKey()
);

$writer = new Writer(
    new ImageRenderer(
        new RendererStyle(400),
        new ImagickImageBackEnd()
    )
);

$qrcode_image = base64_encode($writer->writeString($g2faUrl));
```

And show it as an image:

```php
<img src="data:image/png;base64, <?php echo $qrcode_image; ?> "/>
```

## HMAC Algorithms

To comply with [RFC6238](https://tools.ietf.org/html/rfc6238), this package supports SHA1, SHA256 and SHA512. It defaults to SHA1, so to use a different algorithm you just have to use the method `setAlgorithm()`:

``` php

use PragmaRX\Google2FA\Support\Constants;

$google2fa->setAlgorithm(Constants::SHA512);
```

## Server Time

It's really important that you keep your server time in sync with some NTP server, on Ubuntu you can add this to the crontab:

```bash
sudo service ntp stop
sudo ntpd -gq
sudo service ntp start
```

## Validation Window

To avoid problems with clocks that are slightly out of sync, we do not check against the current key only but also consider `$window` keys each from the past and future. You can pass `$window` as optional third parameter to `verifyKey`, it defaults to `1`. When a new key is generated every 30 seconds, then with the default setting, keys from one previous, the current, and one next 30-seconds intervals will be considered. To the user with properly synchronized clock, it will look like the key is valid for 60 seconds instead of 30, as the system will accept it even when it is already expired for let's say 29 seconds.

```php
$secret = $request->input('secret');

$window = 8; // 8 keys (respectively 4 minutes) past and future

$valid = $google2fa->verifyKey($user->google2fa_secret, $secret, $window);
```

Setting the `$window` parameter to `0` may also mean that the system will not accept a key that was valid when the user has seen it in their generator as it usually takes some time for the user to input the key to the particular form field.

An attacker might be able to watch the user entering his credentials and one time key.
Without further precautions, the key remains valid until it is no longer within the window of the server time. In order to prevent usage of a one time key that has already been used, you can utilize the `verifyKeyNewer` function.

```php
$secret = $request->input('secret');

$timestamp = $google2fa->verifyKeyNewer($user->google2fa_secret, $secret, $user->google2fa_ts);

if ($timestamp !== false) {
    $user->update(['google2fa_ts' => $timestamp]);
    // successful
} else {
    // failed
}
```

Note that `$timestamp` is either `false` (if the key is invalid or has been used before) or the provided key's unix timestamp divided by the key regeneration period of 30 seconds.

## Using a Bigger and Prefixing the Secret Key

Although the probability of collision of a 16 bytes (128 bits) random string is very low, you can harden it by:

#### Use a bigger key

```php
$secretKey = $google2fa->generateSecretKey(32); // now defaults to 32 bytes (v9.0.0+)
$secretKey = $google2fa->generateSecretKey(16); // for 16 byte keys (v8.x behavior)
```

#### You can prefix your secret keys

You may prefix your secret keys, but you have to understand that, as your secret key must have length in power of 2, your prefix will have to have a complementary size. So if your key is 16 bytes long, if you add a prefix it must also be 16 bytes long, but as your prefixes will be converted to base 32, the max length of your prefix is 10 bytes. So, those are the sizes you can use in your prefixes:

```
1, 2, 5, 10, 20, 40, 80...
```

And it can be used like so:

```php
$prefix = strpad($userId, 10, 'X');

$secretKey = $google2fa->generateSecretKey(16, $prefix);
```

#### Window

The Window property defines how long a OTP will work, or how many cycles it will last. A key has a 30 seconds cycle, setting the window to 0 will make the key last for those 30 seconds, setting it to 2 will make it last for 120 seconds. This is how you set the window:

```php
$secretKey = $google2fa->setWindow(4);
```

But you can also set the window while checking the key. If you need to set a window of 4 during key verification, this is how you do:

```php
$isValid = $google2fa->verifyKey($seed, $key, 4);
```

#### Key Regeneration Interval

You can change key regeneration interval, which defaults to 30 seconds, but remember that this is a default value on most authentication apps, like Google Authenticator, which will, basically, make your app out of sync with them.

```php
$google2fa->setKeyRegeneration(40);
```

## Google Authenticator secret key compatibility

To be compatible with Google Authenticator, your (converted to base 32) secret key length must be at least 8 chars and be a power of 2: 8, 16, 32, 64...

So, to prevent errors, you can do something like this while generating it:

```php
$secretKey = '123456789';

$secretKey = str_pad($secretKey, pow(2,ceil(log(strlen($secretKey),2))), 'X');
```

And it will generate

```
123456789XXXXXXX
```

By default, this package will enforce compatibility, but, if Google Authenticator is not a target, you can disable it by doing

```php
$google2fa->setEnforceGoogleAuthenticatorCompatibility(false);
```

## Google Authenticator Apps

To use the two factor authentication, your user will have to install a Google Authenticator compatible app, those are some of the currently available:

* [Authy for iOS, Android, Chrome, OS X](https://www.authy.com/)
* [FreeOTP for iOS, Android and Pebble](https://apps.getpebble.com/en_US/application/52f1a4c3c4117252f9000bb8)
* [Google Authenticator for iOS](https://itunes.apple.com/us/app/google-authenticator/id388497605?mt=8)
* [Google Authenticator for Android](https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2)
* [Google Authenticator (port) on Windows Store](https://www.microsoft.com/en-us/store/p/google-authenticator/9wzdncrdnkrf)
* [Microsoft Authenticator for Windows Phone](https://www.microsoft.com/en-us/store/apps/authenticator/9wzdncrfj3rj)
* [LastPass Authenticator for iOS, Android, OS X, Windows](https://lastpass.com/auth/)
* [1Password for iOS, Android, OS X, Windows](https://1password.com)

## Deprecation Warning

Google API for QR generator is turned off. All versions of that package prior to 5.0.0 are deprecated. Please upgrade and check documentation regarding [QRCode generation](https://github.com/antonioribeiro/google2fa#generating-qrcodes).

## Testing

The package tests were written with [PHPUnit](https://phpunit.de/). There are some Composer scripts to help you run tests and analysis:

PHPUnit:

````
composer test
````

PHPStan analysis:

````
composer analyse
````

## Authors

- [Antonio Carlos Ribeiro](http://twitter.com/iantonioribeiro)
- [Phil (Orginal author of this class)](https://www.idontplaydarts.com/static/ga.php_.txt)
- [All Contributors](https://github.com/antonioribeiro/google2fa/graphs/contributors)

## License

Google2FA is licensed under the MIT License - see the [LICENSE](LICENSE.md) file for details.

## Contributing

Pull requests and issues are more than welcome.

## Sponsorships

### Direct

None.

### Indirect

- JetBrains - [Open Source License](https://www.jetbrains.com/community/opensource/#support) (since 2020)
- Blackfire - [Open Source License](https://www.blackfire.io/open-source/) (since 2022)
