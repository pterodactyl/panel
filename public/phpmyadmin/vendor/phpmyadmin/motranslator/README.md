# motranslator

Translation API for PHP using Gettext MO files.

![Test-suite](https://github.com/phpmyadmin/motranslator/workflows/Run%20tests/badge.svg?branch=master)
[![codecov.io](https://codecov.io/github/phpmyadmin/motranslator/coverage.svg?branch=master)](https://codecov.io/github/phpmyadmin/motranslator?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/phpmyadmin/motranslator/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/phpmyadmin/motranslator/?branch=master)
[![Packagist](https://img.shields.io/packagist/dt/phpmyadmin/motranslator.svg)](https://packagist.org/packages/phpmyadmin/motranslator)

## Features

* All strings are stored in memory for fast lookup
* Fast loading of MO files
* Low level API for reading MO files
* Emulation of Gettext API
* No use of `eval()` for plural equation

## Limitations

* Default `InMemoryCache` not suitable for huge MO files which you don't want to store in memory
* Input and output encoding has to match (preferably UTF-8)

## Installation

Please use [Composer][1] to install:

```sh
composer require phpmyadmin/motranslator
```

## Documentation

The API documentation is available at <https://develdocs.phpmyadmin.net/motranslator/>.

## Object API usage

```php
// Create loader object
$loader = new PhpMyAdmin\MoTranslator\Loader();

// Set locale
$loader->setlocale('cs');

// Set default text domain
$loader->textdomain('domain');

// Set path where to look for a domain
$loader->bindtextdomain('domain', __DIR__ . '/data/locale/');

// Get translator
$translator = $loader->getTranslator();

// Now you can use Translator API (see below)
```

## Low level API usage

```php
// Directly load the mo file
// You can use null to not load a file and the use a setter to set the translations
$cache = new PhpMyAdmin\MoTranslator\Cache\InMemoryCache(new PhpMyAdmin\MoTranslator\MoParser('./path/to/file.mo'));
$translator = new PhpMyAdmin\MoTranslator\Translator($cache);

// Now you can use Translator API (see below)
```

## Translator API usage

```php
// Translate string
echo $translator->gettext('String');

// Translate plural string
echo $translator->ngettext('String', 'Plural string', $count);

// Translate string with context
echo $translator->pgettext('Context', 'String');

// Translate plural string with context
echo $translator->npgettext('Context', 'String', 'Plural string', $count);

// Get the translations
echo $translator->getTranslations();

// All getters and setters below are more to be used if you are using a manual loading mode
// Example: $translator = new PhpMyAdmin\MoTranslator\Translator(null);

// Set a translation
echo $translator->setTranslation('Test', 'Translation for "Test" key');

// Set translations
echo $translator->setTranslations([
  'Test' => 'Translation for "Test" key',
  'Test 2' => 'Translation for "Test 2" key',
]);

// Use the translation
echo $translator->gettext('Test 2'); // -> Translation for "Test 2" key
```

## Gettext compatibility usage

```php
// Load compatibility layer
PhpMyAdmin\MoTranslator\Loader::loadFunctions();

// Configure
_setlocale(LC_MESSAGES, 'cs');
_textdomain('phpmyadmin');
_bindtextdomain('phpmyadmin', __DIR__ . '/data/locale/');
_bind_textdomain_codeset('phpmyadmin', 'UTF-8');

// Use functions
echo _gettext('Type');
echo __('Type');

// It also support other Gettext functions
_dnpgettext($domain, $msgctxt, $msgid, $msgidPlural, $number);
_dngettext($domain, $msgid, $msgidPlural, $number);
_npgettext($msgctxt, $msgid, $msgidPlural, $number);
_ngettext($msgid, $msgidPlural, $number);
_dpgettext($domain, $msgctxt, $msgid);
_dgettext($domain, $msgid);
_pgettext($msgctxt, $msgid);
```

## Using APCu-backed cache

If you have the [APCu][5] extension installed you can use it for storing the translation cache. The `.mo` file
will then only be loaded once and all processes will share the same cache, reducing memory usage and resulting in
performance comparable to the native `gettext` extension.

If you are using `Loader`, pass it an `ApcuCacheFactory` _before_ getting the translator instance:

```php
PhpMyAdmin\MoTranslator\Loader::setCacheFactory(
    new PhpMyAdmin\MoTranslator\Cache\AcpuCacheFactory()
);
$loader = new PhpMyAdmin\MoTranslator\Loader();

// Proceed as before 
```

If you are using the low level API, instantiate the `ApcuCache` directly:

```php
$cache = new PhpMyAdmin\MoTranslator\Cache\ApcuCache(
    new PhpMyAdmin\MoTranslator\MoParser('./path/to/file.mo'),
    'de_DE',     // the locale
    'phpmyadmin' // the domain
);
$translator = new PhpMyAdmin\MoTranslator\Translator($cache);

// Proceed as before
```

By default, APCu will cache the translations until next server restart and prefix the cache entries with `mo_` to
avoid clashes with other cache entries. You can control this behaviour by passing `$ttl` and `$prefix` arguments, either
to the `ApcuCacheFactory` or when instantiating `ApcuCache`:

```php
PhpMyAdmin\MoTranslator\Loader::setCacheFactory(
    new PhpMyAdmin\MoTranslator\Cache\AcpuCacheFactory(
        3600,     // cache for 1 hour
        true,     // reload on cache miss
        'custom_' // custom prefix for cache entries
    )
);
$loader = new PhpMyAdmin\MoTranslator\Loader();

// or...

$cache = new PhpMyAdmin\MoTranslator\Cache\ApcuCache(
    new PhpMyAdmin\MoTranslator\MoParser('./path/to/file.mo'),
    'de_DE',
    'phpmyadmin',
    3600,     // cache for 1 hour
    true,     // reload on cache miss
    'custom_' // custom prefix for cache entries
);
$translator = new PhpMyAdmin\MoTranslator\Translator($cache);
```

If you receive updated translation files you can load them without restarting the server using the low-level API:

```php
$parser = new PhpMyAdmin\MoTranslator\MoParser('./path/to/file.mo');
$cache = new PhpMyAdmin\MoTranslator\Cache\ApcuCache($parser, 'de_DE', 'phpmyadmin');
$parser->parseIntoCache($cache);
```

You should ensure APCu has enough memory to store all your translations, along with any other entries you use it 
for. If an entry is evicted from cache, the `.mo` file will be re-parsed, impacting performance. See the 
`apc.shm_size` and `apc.shm_segments` [documentation][6] and monitor cache usage when first rolling out.

If your `.mo` files are missing lots of translations, the first time a missing entry is requested the `.mo` file 
will be re-parsed. Again, this will impact performance until all the missing entries are hit once. You can turn off this
behaviour by setting the `$reloadOnMiss` argument to `false`. If you do this it is _critical_ that APCu has enough 
memory, or users will see untranslated text when entries are evicted.

## History

This library is based on [php-gettext][2]. It adds some performance
improvements and ability to install using [Composer][1].

## Motivation

Motivation for this library includes:

* The [php-gettext][2] library is not maintained anymore
* It doesn't work with recent PHP version (phpMyAdmin has patched version)
* It relies on `eval()` function for plural equations what can have severe security implications, see [CVE-2016-6175][4]
* It's not possible to install it using [Composer][1]
* There was place for performance improvements in the library

### Why not to use native gettext in PHP?

We've tried that, but it's not a viable solution:

* You can not use locales not known to system, what is something you can not
  control from web application. This gets even more tricky with minimalist
  virtualisation containers.
* Changing the MO file usually leads to PHP segmentation fault. It (or rather
  Gettext library) caches headers of MO file and if it's content is changed
  (for example new version is uploaded to server) it tries to access new data
  with old references. This is bug known for ages:
  https://bugs.php.net/bug.php?id=45943

### Why use Gettext and not JSON, YAML or whatever?

We want translators to be able to use their favorite tools and we want us to be
able to use wide range of tools available with Gettext as well such as 
[web based translation using Weblate][3]. Using custom format usually adds
another barrier for translators and we want to make it easy for them to
contribute.

[1]:https://getcomposer.org/
[2]:https://launchpad.net/php-gettext
[3]:https://weblate.org/
[4]: https://www.cve.org/CVERecord?id=CVE-2016-6175
[5]:https://www.php.net/manual/en/book.apcu.php
[6]:https://www.php.net/manual/en/apcu.configuration.php
