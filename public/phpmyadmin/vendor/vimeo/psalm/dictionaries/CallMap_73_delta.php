<?php // phpcs:ignoreFile

/**
 * This contains the information needed to convert the function signatures for php 7.3 to php 7.2 (and vice versa)
 *
 * This file has three sections.
 * The 'added' section contains function/method names from FunctionSignatureMap (And alternates, if applicable) that do not exist in php 7.2
 * The 'removed' section contains the signatures that were removed in php 7.3.
 * The 'changed' section contains functions for which the signature has changed for php 7.3.
 *     Each function in the 'changed' section has an 'old' and a 'new' section,
 *     representing the function as it was in PHP 7.2 and in PHP 7.3, respectively
 *
 * @see CallMap.php
 *
 * @phan-file-suppress PhanPluginMixedKeyNoKey (read by Phan when analyzing this file)
 */
return [
  'added' => [
    'DateTime::createFromImmutable' => ['static', 'object'=>'DateTimeImmutable'],
    'JsonException::__clone' => ['void'],
    'JsonException::__construct' => ['void'],
    'JsonException::__toString' => ['string'],
    'JsonException::__wakeup' => ['void'],
    'JsonException::getCode' => ['int'],
    'JsonException::getFile' => ['string'],
    'JsonException::getLine' => ['int'],
    'JsonException::getMessage' => ['string'],
    'JsonException::getPrevious' => ['?Throwable'],
    'JsonException::getTrace' => ['list<array{file?:string,line?:int,function:string,class?:class-string,type?:\'::\'|\'->\',args?:array<mixed>}>'],
    'JsonException::getTraceAsString' => ['string'],
    'SplPriorityQueue::isCorrupted' => ['bool'],
    'array_key_first' => ['int|string|null', 'array'=>'array'],
    'array_key_last' => ['int|string|null', 'array'=>'array'],
    'fpm_get_status' => ['array|false'],
    'gc_status' => ['array{runs:int,collected:int,threshold:int,roots:int}'],
    'gmp_binomial' => ['GMP|false', 'n'=>'GMP|string|int', 'k'=>'int'],
    'gmp_kronecker' => ['int', 'num1'=>'GMP|string|int', 'num2'=>'GMP|string|int'],
    'gmp_lcm' => ['GMP', 'num1'=>'GMP|string|int', 'num2'=>'GMP|string|int'],
    'gmp_perfect_power' => ['bool', 'num'=>'GMP|string|int'],
    'hrtime' => ['array{0:int,1:int}|false', 'as_number='=>'false'],
    'hrtime\'1' => ['int|float|false', 'as_number='=>'true'],
    'is_countable' => ['bool', 'value'=>'mixed'],
    'net_get_interfaces' => ['array<string,array<string,mixed>>|false'],
    'openssl_pkey_derive' => ['string|false', 'public_key'=>'mixed', 'private_key'=>'mixed', 'key_length='=>'?int'],
    'session_set_cookie_params\'1' => ['bool', 'options'=>'array{lifetime?:?int,path?:?string,domain?:?string,secure?:?bool,httponly?:?bool,samesite?:?string}'],
    'setcookie\'1' => ['bool', 'name'=>'string', 'value='=>'string', 'options='=>'array'],
    'setrawcookie\'1' => ['bool', 'name'=>'string', 'value='=>'string', 'options='=>'array'],
    'socket_wsaprotocol_info_export' => ['string|false', 'socket'=>'resource', 'process_id'=>'int'],
    'socket_wsaprotocol_info_import' => ['resource|false', 'info_id'=>'string'],
    'socket_wsaprotocol_info_release' => ['bool', 'info_id'=>'string'],
  ],
  'changed' => [
    'array_push' => [
        'old' => ['int', '&rw_array'=>'array', '...values'=>'mixed'],
        'new' => ['int', '&rw_array'=>'array', '...values='=>'mixed'],
    ],
    'array_unshift' => [
        'old' => ['int', '&rw_array'=>'array', '...values'=>'mixed'],
        'new' => ['int', '&rw_array'=>'array', '...values='=>'mixed'],
    ],
    'bcscale' => [
      'old' => ['int', 'scale'=>'int'],
      'new' => ['int', 'scale='=>'int'],
    ],
    'ldap_exop_passwd' => [
      'old' => ['bool|string', 'ldap'=>'resource', 'user='=>'string', 'old_password='=>'string', 'new_password='=>'string'],
      'new' => ['bool|string', 'ldap'=>'resource', 'user='=>'string', 'old_password='=>'string', 'new_password='=>'string', '&w_controls='=>'array'],
    ],
  ],
  'removed' => [
  ],
];
