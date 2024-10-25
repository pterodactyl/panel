# Sodium Compat

[![Build Status](https://github.com/paragonie/sodium_compat/actions/workflows/ci.yml/badge.svg)](https://github.com/paragonie/sodium_compat/actions)
[![Psalm Status](https://github.com/paragonie/sodium_compat/actions/workflows/psalm.yml/badge.svg)](https://github.com/paragonie/sodium_compat/actions)
[![Windows Build Status](https://ci.appveyor.com/api/projects/status/itcx1vgmfqiawgbe?svg=true)](https://ci.appveyor.com/project/paragonie-scott/sodium-compat)
[![Latest Stable Version](https://poser.pugx.org/paragonie/sodium_compat/v/stable)](https://packagist.org/packages/paragonie/sodium_compat)
[![Latest Unstable Version](https://poser.pugx.org/paragonie/sodium_compat/v/unstable)](https://packagist.org/packages/paragonie/sodium_compat)
[![License](https://poser.pugx.org/paragonie/sodium_compat/license)](https://packagist.org/packages/paragonie/sodium_compat)
[![Downloads](https://img.shields.io/packagist/dt/paragonie/sodium_compat.svg)](https://packagist.org/packages/paragonie/sodium_compat)

Sodium Compat is a pure PHP polyfill for the Sodium cryptography library 
(libsodium), a core extension in PHP 7.2.0+ and otherwise [available in PECL](https://pecl.php.net/package/libsodium).

This library tentatively supports PHP 5.2.4 - 8.x (latest), but officially
only supports [non-EOL'd versions of PHP](https://secure.php.net/supported-versions.php).

If you have the PHP extension installed, Sodium Compat will opportunistically
and transparently use the PHP extension instead of our implementation.

## IMPORTANT!

This cryptography library has not been formally audited by an independent third 
party that specializes in cryptography or cryptanalysis.

If you require such an audit before you can use sodium_compat in your projects
and have the funds for such an audit, please open an issue or contact 
`security at paragonie dot com` so we can help get the ball rolling.

However, sodium_compat has been adopted by high profile open source projects,
such as [Joomla!](https://github.com/joomla/joomla-cms/blob/459d74686d2a638ec51149d7c44ddab8075852be/composer.json#L40)
and [Magento](https://github.com/magento/magento2/blob/8fd89cfdf52c561ac0ca7bc20fd38ef688e201b0/composer.json#L44).
Furthermore, sodium_compat was developed by Paragon Initiative Enterprises, a
company that *specializes* in secure PHP development and PHP cryptography, and
has been informally reviewed by many other security experts who also specialize
in PHP.

If you'd like to learn more about the defensive security measures we've taken
to prevent sodium_compat from being a source of vulnerability in your systems,
please read [*Cryptographically Secure PHP Development*](https://paragonie.com/blog/2017/02/cryptographically-secure-php-development).

# Installing Sodium Compat

If you're using Composer:

```bash
composer require paragonie/sodium_compat
```

### Install From Source

If you're not using Composer, download a [release tarball](https://github.com/paragonie/sodium_compat/releases)
(which should be signed with [our GnuPG public key](https://paragonie.com/static/gpg-public-key.txt)), extract
its contents, then include our `autoload.php` script in your project.

```php
<?php
require_once "/path/to/sodium_compat/autoload.php";
```

### PHP Archives (Phar) Releases

Since version 1.3.0, [sodium_compat releases](https://github.com/paragonie/sodium_compat/releases) include a
PHP Archive (.phar file) and associated GPG signature. First, download both files and verify them with our
GPG public key, like so:

```bash
# Getting our public key from the keyserver:
gpg --fingerprint 7F52D5C61D1255C731362E826B97A1C2826404DA
if [ $? -ne 0 ]; then
    echo -e "\033[33mDownloading PGP Public Key...\033[0m"
    gpg  --keyserver pgp.mit.edu --recv-keys 7F52D5C61D1255C731362E826B97A1C2826404DA
    # Security <security@paragonie.com>
    gpg --fingerprint 7F52D5C61D1255C731362E826B97A1C2826404DA
    if [ $? -ne 0 ]; then
        echo -e "\033[31mCould not download PGP public key for verification\033[0m"
        exit 1
    fi
fi

# Verifying the PHP Archive
gpg --verify sodium-compat.phar.sig sodium-compat.phar
```

Now, simply include this .phar file in your application.

```php
<?php
require_once "/path/to/sodium-compat.phar";
```

# Support

[Commercial support for libsodium](https://download.libsodium.org/doc/commercial_support/) is available
from multiple vendors. If you need help using sodium_compat in one of your projects, [contact Paragon Initiative Enterprises](https://paragonie.com/contact). 

Non-commercial report will be facilitated through [Github issues](https://github.com/paragonie/sodium_compat/issues).
We offer no guarantees of our availability to resolve questions about integrating sodium_compat into third-party
software for free, but will strive to fix any bugs (security-related or otherwise) in our library.

## Support Contracts

If your company uses this library in their products or services, you may be
interested in [purchasing a support contract from Paragon Initiative Enterprises](https://paragonie.com/enterprise).

# Using Sodium Compat

## True Polyfill

As per the [second vote on the libsodium RFC](https://wiki.php.net/rfc/libsodium#proposed_voting_choices),
PHP 7.2 uses `sodium_*` instead of `\Sodium\*`.

```php
<?php
require_once "/path/to/sodium_compat/autoload.php";

$alice_kp = sodium_crypto_sign_keypair();
$alice_sk = sodium_crypto_sign_secretkey($alice_kp);
$alice_pk = sodium_crypto_sign_publickey($alice_kp);

$message = 'This is a test message.';
$signature = sodium_crypto_sign_detached($message, $alice_sk);
if (sodium_crypto_sign_verify_detached($signature, $message, $alice_pk)) {
    echo 'OK', PHP_EOL;
} else {
    throw new Exception('Invalid signature');
}
```

## Polyfill For the Old PECL Extension API

If you're using PHP 5.3.0 or newer and do not have the PECL extension installed,
you can just use the [standard ext/sodium API features as-is](https://paragonie.com/book/pecl-libsodium)
and the polyfill will work its magic.

```php
<?php
require_once "/path/to/sodium_compat/autoload.php";

$alice_kp = \Sodium\crypto_sign_keypair();
$alice_sk = \Sodium\crypto_sign_secretkey($alice_kp);
$alice_pk = \Sodium\crypto_sign_publickey($alice_kp);

$message = 'This is a test message.';
$signature = \Sodium\crypto_sign_detached($message, $alice_sk);
if (\Sodium\crypto_sign_verify_detached($signature, $message, $alice_pk)) {
    echo 'OK', PHP_EOL;
} else {
    throw new Exception('Invalid signature');
}
```

The polyfill does not expose this API on PHP < 5.3, or if you have the PHP
extension installed already.

## General-Use Polyfill

If your users are on PHP < 5.3, or you want to write code that will work
whether or not the PECL extension is available, you'll want to use the
**`ParagonIE_Sodium_Compat`** class for most of your libsodium needs.

The above example, written for general use:

```php
<?php
require_once "/path/to/sodium_compat/autoload.php";

$alice_kp = ParagonIE_Sodium_Compat::crypto_sign_keypair();
$alice_sk = ParagonIE_Sodium_Compat::crypto_sign_secretkey($alice_kp);
$alice_pk = ParagonIE_Sodium_Compat::crypto_sign_publickey($alice_kp);

$message = 'This is a test message.';
$signature = ParagonIE_Sodium_Compat::crypto_sign_detached($message, $alice_sk);
if (ParagonIE_Sodium_Compat::crypto_sign_verify_detached($signature, $message, $alice_pk)) {
    echo 'OK', PHP_EOL;
} else {
    throw new Exception('Invalid signature');
}
```

Generally: If you replace `\Sodium\ ` with `ParagonIE_Sodium_Compat::`, any
code already written for the libsodium PHP extension should work with our
polyfill without additional code changes.

Since this doesn't require a namespace, this API *is* exposed on PHP 5.2.

Since version 0.7.0, we have our own namespaced API (`ParagonIE\Sodium\*`) to allow brevity
in software that uses PHP 5.3+. This is useful if you want to use our file cryptography
features without writing `ParagonIE_Sodium_File` every time. This is not exposed on PHP < 5.3,
so if your project supports PHP < 5.3, use the underscore method instead.

To learn how to use Libsodium, read [*Using Libsodium in PHP Projects*](https://paragonie.com/book/pecl-libsodium).

## Help, Sodium_Compat is Slow! How can I make it fast?

There are three ways to make it fast:

1. Use a newer version of PHP (at least 7.2).
2. [Install the libsodium PHP extension from PECL](https://paragonie.com/book/pecl-libsodium/read/00-intro.md#installing-libsodium).
3. Only if the previous two options are not available for you:
   1. Verify that [the processor you're using actually implements constant-time multiplication](https://bearssl.org/ctmul.html).
      Sodium_compat does, but it must trade some speed in order to attain cross-platform security.
   2. Only if you are 100% certain that your processor is safe, you can set `ParagonIE_Sodium_Compat::$fastMult = true;`
      without harming the security of your cryptography keys. If your processor *isn't* safe, then decide whether you
      want speed or security because you can't have both.

### How can I tell if sodium_compat will be slow, at runtime?

Since version 1.8, you can use the `polyfill_is_fast()` static method to
determine if sodium_compat will be slow at runtime.

```php
<?php
if (ParagonIE_Sodium_Compat::polyfill_is_fast()) {
    // Use libsodium now
    $process->execute();
} else {
    // Defer to a cron job or other sort of asynchronous process
    $process->enqueue();
}
```

### Help, my PHP only has 32-Bit Integers! It's super slow!

If the `PHP_INT_SIZE` constant equals `4` instead of `8` (PHP 5 on Windows,
Linux on i386, etc.), you will run into **significant performance issues**.

In particular: public-key cryptography (encryption and signatures)
is affected. There is nothing we can do about that.

The root cause of these performance issues has to do with implementing cryptography
algorithms in constant-time using 16-bit limbs (to avoid overflow) in pure PHP.

To mitigate these performance issues, simply install PHP 7.2 or newer and enable
the `sodium` extension.

Affected users are encouraged to install the sodium extension (or libsodium from
older version of PHP).

Windows users on PHP 5 may be able to simply upgrade to PHP 7 and the slowdown
will be greatly reduced.

## Documentation

First, you'll want to read the [Libsodium Quick Reference](https://paragonie.com/blog/2017/06/libsodium-quick-reference-quick-comparison-similar-functions-and-which-one-use).
It aims to answer, "Which function should I use for [common problem]?".

If you don't find the answers in the Quick Reference page, check out
[*Using Libsodium in PHP Projects*](https://paragonie.com/book/pecl-libsodium).

Finally, the [official libsodium documentation](https://download.libsodium.org/doc/) 
(which was written for the C library, not the PHP library) also contains a lot of
insightful technical information you may find helpful.

## API Coverage

**Recommended reading:** [Libsodium Quick Reference](https://paragonie.com/blog/2017/06/libsodium-quick-reference-quick-comparison-similar-functions-and-which-one-use)

* Mainline NaCl Features
    * `crypto_auth()`
    * `crypto_auth_verify()`
    * `crypto_box()`
    * `crypto_box_open()`
    * `crypto_scalarmult()`
    * `crypto_secretbox()`
    * `crypto_secretbox_open()`
    * `crypto_sign()`
    * `crypto_sign_open()`
* PECL Libsodium Features
    * `crypto_aead_aes256gcm_encrypt()`
    * `crypto_aead_aes256gcm_decrypt()`
    * `crypto_aead_chacha20poly1305_encrypt()`
    * `crypto_aead_chacha20poly1305_decrypt()`
    * `crypto_aead_chacha20poly1305_ietf_encrypt()`
    * `crypto_aead_chacha20poly1305_ietf_decrypt()`
    * `crypto_aead_xchacha20poly1305_ietf_encrypt()`
    * `crypto_aead_xchacha20poly1305_ietf_decrypt()`
    * `crypto_box_xchacha20poly1305()`
    * `crypto_box_xchacha20poly1305_open()`
    * `crypto_box_seal()`
    * `crypto_box_seal_open()`
    * `crypto_generichash()`
    * `crypto_generichash_init()`
    * `crypto_generichash_update()`
    * `crypto_generichash_final()`
    * `crypto_kx()`
    * `crypto_secretbox_xchacha20poly1305()`
    * `crypto_secretbox_xchacha20poly1305_open()`
    * `crypto_shorthash()`
    * `crypto_sign_detached()`
    * `crypto_sign_ed25519_pk_to_curve25519()`
    * `crypto_sign_ed25519_sk_to_curve25519()`
    * `crypto_sign_verify_detached()`
    * For advanced users only:
        * `crypto_core_ristretto255_add()`
        * `crypto_core_ristretto255_from_hash()`
        * `crypto_core_ristretto255_is_valid_point()`
        * `crypto_core_ristretto255_random()`
        * `crypto_core_ristretto255_scalar_add()`
        * `crypto_core_ristretto255_scalar_complement()`
        * `crypto_core_ristretto255_scalar_invert()`
        * `crypto_core_ristretto255_scalar_mul()`
        * `crypto_core_ristretto255_scalar_negate()`
        * `crypto_core_ristretto255_scalar_random()`
        * `crypto_core_ristretto255_scalar_reduce()`
        * `crypto_core_ristretto255_scalar_sub()`
        * `crypto_core_ristretto255_sub()`
        * `crypto_scalarmult_ristretto255_base()`
        * `crypto_scalarmult_ristretto255()`
        * `crypto_stream()`
        * `crypto_stream_keygen()`
        * `crypto_stream_xor()`
        * `crypto_stream_xchacha20()`
        * `crypto_stream_xchacha20_keygen()`
        * `crypto_stream_xchacha20_xor()`
        * `crypto_stream_xchacha20_xor_ic()`
    * Other utilities (e.g. `crypto_*_keypair()`)
        * `add()`
        * `base642bin()`
        * `bin2base64()`
        * `bin2hex()`
        * `hex2bin()`
        * `crypto_kdf_derive_from_key()`
        * `crypto_kx_client_session_keys()`
        * `crypto_kx_server_session_keys()`
        * `crypto_secretstream_xchacha20poly1305_init_push()`
        * `crypto_secretstream_xchacha20poly1305_push()`
        * `crypto_secretstream_xchacha20poly1305_init_pull()`
        * `crypto_secretstream_xchacha20poly1305_pull()`
        * `crypto_secretstream_xchacha20poly1305_rekey()`
        * `pad()`
        * `unpad()`

### Cryptography Primitives Provided

* **X25519** - Elliptic Curve Diffie Hellman over Curve25519
* **Ed25519** - Edwards curve Digital Signature Algorithm over Curve25519
* **Xsalsa20** - Extended-nonce Salsa20 stream cipher
* **ChaCha20** - Stream cipher
* **Xchacha20** - Extended-nonce ChaCha20 stream cipher
* **Poly1305** - Polynomial Evaluation Message Authentication Code modulo 2^130 - 5
* **BLAKE2b** - Cryptographic Hash Function
* **SipHash-2-4** - Fast hash, but not collision-resistant; ideal for hash tables.

### Features Excluded from this Polyfill

* `\Sodium\memzero()` - Although we expose this API endpoint, we can't reliably
  zero buffers from PHP.
  
  If you have the PHP extension installed, sodium_compat
  will use the native implementation to zero out the string provided. Otherwise
  it will throw a `SodiumException`.
* `\Sodium\crypto_pwhash()` - It's not feasible to polyfill scrypt or Argon2
  into PHP and get reasonable performance. Users would feel motivated to select
  parameters that downgrade security to avoid denial of service (DoS) attacks.
  
  The only winning move is not to play.
  
  If ext/sodium or ext/libsodium is installed, these API methods will fallthrough
  to the extension. Otherwise, our polyfill library will throw a `SodiumException`.
  
  To detect support for Argon2i at runtime, use
  `ParagonIE_Sodium_Compat::crypto_pwhash_is_available()`, which returns a
   boolean value (`TRUE` or `FALSE`).

### PHPCompatibility Ruleset

For sodium_compat users and that utilize [`PHPCompatibility`](https://github.com/PHPCompatibility/PHPCompatibility)
in their CI process, there is now a custom ruleset available which can be used
to prevent false positives being thrown by `PHPCompatibility` for the native
PHP functionality being polyfilled by this repo.

You can find the repo for the `PHPCompatibilityParagonieSodiumCompat` ruleset
here [on Github](https://github.com/PHPCompatibility/PHPCompatibilityParagonie) 
and [on Packagist](https://packagist.org/packages/phpcompatibility/phpcompatibility-paragonie).
