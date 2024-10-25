<?php // phpcs:ignoreFile

/**
 * This contains the information needed to convert the function signatures for php 7.4 to php 7.3 (and vice versa)
 *
 * This file has three sections.
 * The 'added' section contains function/method names from FunctionSignatureMap (And alternates, if applicable) that do not exist in php 7.3
 * The 'removed' section contains the signatures that were removed in php 7.4.
 * The 'changed' section contains functions for which the signature has changed for php 7.4.
 *     Each function in the 'changed' section has an 'old' and a 'new' section,
 *     representing the function as it was in PHP 7.3 and in PHP 7.4, respectively
 *
 * @see CallMap.php
 *
 * @phan-file-suppress PhanPluginMixedKeyNoKey (read by Phan when analyzing this file)
 */
return [
  'added' => [
    'ReflectionProperty::getType' => ['?ReflectionType'],
    'mb_str_split' => ['list<string>|false', 'string'=>'string', 'length='=>'positive-int', 'encoding='=>'string'],
    'openssl_x509_verify' => ['int', 'certificate'=>'string|resource', 'public_key'=>'string|array|resource'],
  ],
  'changed' => [
    'array_merge' => [
        'old' => ['array', '...arrays'=>'array'],
        'new' => ['array', '...arrays='=>'array'],
    ],
    'array_merge_recursive' => [
        'old' => ['array', '...arrays'=>'array'],
        'new' => ['array', '...arrays='=>'array'],
    ],
    'gzread' => [
      'old' => ['string|0', 'stream'=>'resource', 'length'=>'int'],
      'new' => ['string|false', 'stream'=>'resource', 'length'=>'int'],
    ],
    'password_hash' => [
      'old' => ['string|false', 'password'=>'string', 'algo'=>'int', 'options='=>'array'],
      'new' => ['string|false', 'password'=>'string', 'algo'=>'int|string|null', 'options='=>'array'],
    ],
    'password_needs_rehash' => [
      'old' => ['bool', 'hash'=>'string', 'algo'=>'int', 'options='=>'array'],
      'new' => ['bool', 'hash'=>'string', 'algo'=>'int|string|null', 'options='=>'array'],
    ],
    'proc_open' => [
      'old' => ['resource|false', 'command'=>'string', 'descriptor_spec'=>'array', '&pipes'=>'resource[]', 'cwd='=>'?string', 'env_vars='=>'?array', 'options='=>'?array'],
      'new' => ['resource|false', 'command'=>'string|array', 'descriptor_spec'=>'array', '&pipes'=>'resource[]', 'cwd='=>'?string', 'env_vars='=>'?array', 'options='=>'?array'],
    ],
  ],
  'removed' => [
  ],
];
