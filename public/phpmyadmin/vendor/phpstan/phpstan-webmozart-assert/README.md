# PHPStan webmozart/assert extension

[![Build](https://github.com/phpstan/phpstan-webmozart-assert/workflows/Build/badge.svg)](https://github.com/phpstan/phpstan-webmozart-assert/actions)
[![Latest Stable Version](https://poser.pugx.org/phpstan/phpstan-webmozart-assert/v/stable)](https://packagist.org/packages/phpstan/phpstan-webmozart-assert)
[![License](https://poser.pugx.org/phpstan/phpstan-webmozart-assert/license)](https://packagist.org/packages/phpstan/phpstan-webmozart-assert)

* [PHPStan](https://phpstan.org/)
* [webmozart/assert](https://github.com/webmozart/assert)

## Description

The main scope of this extension is to help phpstan to detect the type of object after the `Webmozart\Assert` validation.

```php
<?php declare(strict_types = 1);

use Webmozart\Assert;

function demo(?int $a) {
	// ...

	Assert::integer($a);
	// phpstan is now aware that $a can no longer be `null` at this point

	return ($a === 10);
}
```

This extension specifies types of values passed to:

* `Assert::integer`
* `Assert::positiveInteger`
* `Assert::string`
* `Assert::stringNotEmpty`
* `Assert::float`
* `Assert::numeric`
* `Assert::natural`
* `Assert::integerish`
* `Assert::boolean`
* `Assert::scalar`
* `Assert::object`
* `Assert::resource`
* `Assert::isCallable`
* `Assert::isArray`
* `Assert::isTraversable` (deprecated, use `isIterable` or `isInstanceOf` instead)
* `Assert::isIterable`
* `Assert::isCountable`
* `Assert::isInstanceOf`
* `Assert::isInstanceOfAny`
* `Assert::notInstanceOf`
* `Assert::isAOf`
* `Assert::isAnyOf`
* `Assert::isNotA`
* `Assert::subclassOf`
* `Assert::true`
* `Assert::false`
* `Assert::notFalse`
* `Assert::null`
* `Assert::notNull`
* `Assert::isEmpty`
* `Assert::notEmpty`
* `Assert::eq`
* `Assert::notEq`
* `Assert::same`
* `Assert::notSame`
* `Assert::greaterThan`
* `Assert::greaterThanEq`
* `Assert::lessThan`
* `Assert::lessThanEq`
* `Assert::range`
* `Assert::implementsInterface`
* `Assert::classExists`
* `Assert::interfaceExists`
* `Assert::keyExists`
* `Assert::keyNotExists`
* `Assert::validArrayKey`
* `Assert::count`
* `Assert::minCount`
* `Assert::maxCount`
* `Assert::countBetween`
* `Assert::isList`
* `Assert::isNonEmptyList`
* `Assert::isMap`
* `Assert::isNonEmptyMap`
* `Assert::inArray`
* `Assert::oneOf`
* `Assert::methodExists`
* `Assert::propertyExists`
* `Assert::isArrayAccessible`
* `Assert::contains`
* `Assert::startsWith`
* `Assert::startsWithLetter`
* `Assert::endsWith`
* `Assert::unicodeLetters`
* `Assert::alpha`
* `Assert::digits`
* `Assert::alnum`
* `Assert::lower`
* `Assert::upper`
* `Assert::length`
* `Assert::minLength`
* `Assert::maxLength`
* `Assert::lengthBetween`
* `Assert::uuid`
* `Assert::ip`
* `Assert::ipv4`
* `Assert::ipv6`
* `Assert::email`
* `Assert::notWhitespaceOnly`
* `nullOr*`, `all*` and `allNullOr*` variants of the above methods


## Installation

To use this extension, require it in [Composer](https://getcomposer.org/):

```
composer require --dev phpstan/phpstan-webmozart-assert
```

If you also install [phpstan/extension-installer](https://github.com/phpstan/extension-installer) then you're all set!

<details>
  <summary>Manual installation</summary>

If you don't want to use `phpstan/extension-installer`, include extension.neon in your project's PHPStan config:

```
includes:
    - vendor/phpstan/phpstan-webmozart-assert/extension.neon
```
</details>
