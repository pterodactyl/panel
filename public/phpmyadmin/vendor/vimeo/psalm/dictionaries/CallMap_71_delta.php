<?php // phpcs:ignoreFile

/**
 * This contains the information needed to convert the function signatures for php 7.1 to php 7.0 (and vice versa)
 *
 * This file has three sections.
 * The 'added' section contains function/method names from FunctionSignatureMap (And alternates, if applicable) that did not exist prior to PHP 7.1
 * The 'removed' section contains the signatures that were removed in php 7.1.
 * The 'changed' section contains functions for which the signature has changed for php 7.1.
 *     Each function in the 'changed' section has an 'old' and a 'new' section,
 *     representing the function as it was in PHP before 7.1 and in PHP 7.1, respectively
 *
 * @see CallMap.php
 *
 * @phan-file-suppress PhanPluginMixedKeyNoKey (read by Phan when analyzing this file)
 */
return [
  'added' => [
    'Closure::fromCallable' => ['Closure', 'callable'=>'callable'],
    'curl_multi_errno' => ['int|false', 'mh'=>'resource'],
    'curl_share_errno' => ['int|false', 'sh'=>'resource'],
    'curl_share_strerror' => ['?string', 'error_code'=>'int'],
    'getenv\'1' => ['array<string,string>'],
    'hash_hkdf' => ['non-empty-string|false', 'algo'=>'string', 'key'=>'string', 'length='=>'int', 'info='=>'string', 'salt='=>'string'],
    'is_iterable' => ['bool', 'value'=>'mixed'],
    'openssl_get_curve_names' => ['list<string>'],
    'pcntl_async_signals' => ['bool', 'enable='=>'bool'],
    'pcntl_signal_get_handler' => ['int|string', 'signal'=>'int'],
    'sapi_windows_cp_conv' => ['?string', 'in_codepage'=>'int|string', 'out_codepage'=>'int|string', 'subject'=>'string'],
    'sapi_windows_cp_get' => ['int'],
    'sapi_windows_cp_is_utf8' => ['bool'],
    'sapi_windows_cp_set' => ['bool', 'codepage'=>'int'],
    'session_create_id' => ['string', 'prefix='=>'string'],
    'session_gc' => ['int|false'],
  ],
  'changed' => [
    'DateTimeZone::listIdentifiers' => [
      'old' => ['list<string>|false', 'timezoneGroup='=>'int', 'countryCode='=>'string'],
      'new' => ['list<string>|false', 'timezoneGroup='=>'int', 'countryCode='=>'string|null'],
    ],
    'SQLite3::createFunction' => [
      'old' => ['bool', 'name'=>'string', 'callback'=>'callable', 'argCount='=>'int'],
      'new' => ['bool', 'name'=>'string', 'callback'=>'callable', 'argCount='=>'int', 'flags='=>'int'],
    ],
    'get_headers' => [
      'old' => ['array|false', 'url'=>'string', 'associative='=>'int'],
      'new' => ['array|false', 'url'=>'string', 'associative='=>'int', 'context='=>'resource'],
    ],
    'getopt' => [
      'old' => ['array<string,string>|array<string,false>|array<string,list<mixed>>|false', 'short_options'=>'string', 'long_options='=>'array'],
      'new' => ['array<string,string>|array<string,false>|array<string,list<mixed>>|false', 'short_options'=>'string', 'long_options='=>'array', '&w_rest_index='=>'int'],
    ],
    'pg_fetch_all' => [
      'old' => ['array<array>', 'result'=>'resource'],
      'new' => ['array<array>', 'result'=>'resource', 'result_type='=>'int'],
    ],
    'pg_last_error' => [
      'old' => ['string', 'connection='=>'resource'],
      'new' => ['string', 'connection='=>'resource', 'operation='=>'int'],
    ],
    'pg_select' => [
      'old' => ['string|array|false', 'connection'=>'resource', 'table_name'=>'string', 'assoc_array'=>'array', 'options='=>'int'],
      'new' => ['string|array|false', 'connection'=>'resource', 'table_name'=>'string', 'assoc_array'=>'array', 'options='=>'int', 'result_type='=>'int'],
    ],
    'timezone_identifiers_list' => [
      'old' => ['list<string>|false', 'timezoneGroup='=>'int', 'countryCode='=>'string'],
      'new' => ['list<string>|false', 'timezoneGroup='=>'int', 'countryCode='=>'?string'],
    ],
    'unpack' => [
      'old' => ['array', 'format'=>'string', 'string'=>'string'],
      'new' => ['array|false', 'format'=>'string', 'string'=>'string', 'offset='=>'int'],
    ],
  ],
  'removed' => [
  ],
];
