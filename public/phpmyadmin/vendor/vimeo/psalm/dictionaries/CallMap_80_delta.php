<?php // phpcs:ignoreFile

/**
 * This contains the information needed to convert the function signatures for php 8.0 to php 7.4 (and vice versa)
 *
 * This file has three sections.
 * The 'added' section contains function/method names from FunctionSignatureMap (And alternates, if applicable) that do not exist in php 7.4
 * The 'removed' section contains the signatures that were removed in php 8.0
 * The 'changed' section contains functions for which the signature has changed for php 8.0.
 *     Each function in the 'changed' section has an 'old' and a 'new' section,
 *     representing the function as it was in PHP 7.4 and in PHP 8.0, respectively
 *
 * @see CallMap.php
 *
 * @phan-file-suppress PhanPluginMixedKeyNoKey (read by Phan when analyzing this file)
 */
return [
  'added' => [
    'DateTime::createFromInterface' => ['self', 'object'=>'DateTimeInterface'],
    'DateTimeImmutable::createFromInterface' => ['self', 'object'=>'DateTimeInterface'],
    'PhpToken::getTokenName' => ['string'],
    'PhpToken::is' => ['bool', 'kind'=>'string|int|string[]|int[]'],
    'PhpToken::isIgnorable' => ['bool'],
    'PhpToken::tokenize' => ['list<PhpToken>', 'code'=>'string', 'flags='=>'int'],
    'ReflectionClass::getAttributes' => ['list<ReflectionAttribute>', 'name='=>'?string', 'flags='=>'int'],
    'ReflectionClassConstant::getAttributes' => ['list<ReflectionAttribute>', 'name='=>'?string', 'flags='=>'int'],
    'ReflectionFunctionAbstract::getAttributes' => ['list<ReflectionAttribute>', 'name='=>'?string', 'flags='=>'int'],
    'ReflectionParameter::getAttributes' => ['list<ReflectionAttribute>', 'name='=>'?string', 'flags='=>'int'],
    'ReflectionProperty::getAttributes' => ['list<ReflectionAttribute>', 'name='=>'?string', 'flags='=>'int'],
    'ReflectionUnionType::getTypes' => ['list<ReflectionNamedType>'],
    'fdiv' => ['float', 'num1'=>'float', 'num2'=>'float'],
    'get_debug_type' => ['string', 'value'=>'mixed'],
    'get_resource_id' => ['int', 'resource'=>'resource'],
    'imagegetinterpolation' => ['int', 'image'=>'GdImage'],
    'str_contains' => ['bool', 'haystack'=>'string', 'needle'=>'string'],
    'str_ends_with' => ['bool', 'haystack'=>'string', 'needle'=>'string'],
    'str_starts_with' => ['bool', 'haystack'=>'string', 'needle'=>'string'],
  ],
  'changed' => [
    'DateTime::format' => [
      'old' => ['string|false', 'format'=>'string'],
      'new' => ['string', 'format'=>'string'],
    ],
    'DateTimeZone::listIdentifiers' => [
      'old' => ['list<string>|false', 'timezoneGroup='=>'int', 'countryCode='=>'string|null'],
      'new' => ['list<string>', 'timezoneGroup='=>'int', 'countryCode='=>'string|null'],
    ],
    'PDOStatement::bindColumn' => [
      'old' => ['bool', 'column'=>'mixed', '&rw_param'=>'mixed', 'type='=>'int', 'maxlen='=>'int', 'driverdata='=>'mixed'],
      'new' => ['bool', 'column'=>'mixed', '&rw_var'=>'mixed', 'type='=>'int', 'maxLength='=>'int', 'driverOptions='=>'mixed'],
    ],
    'PDOStatement::bindParam' => [
      'old' => ['bool', 'paramno'=>'mixed', '&rw_param'=>'mixed', 'type='=>'int', 'maxlen='=>'int', 'driverdata='=>'mixed'],
      'new' => ['bool', 'param,'=>'string|int', '&rw_var'=>'mixed', 'type='=>'int', 'maxLength='=>'int', 'driverOptions='=>'mixed'],
    ],
    'PDOStatement::bindValue' => [
      'old' => ['bool', 'paramno'=>'mixed', 'param'=>'mixed', 'type='=>'int'],
      'new' => ['bool', 'param'=>'string|int', 'value'=>'mixed', 'type='=>'int'],
    ],
    'PDOStatement::debugDumpParams' => [
      'old' => ['void'],
      'new' => ['bool|null'],
    ],
    'PDOStatement::errorCode' => [
      'old' => ['string'],
      'new' => ['string|null'],
    ],
    'PDOStatement::execute' => [
      'old' => ['bool', 'bound_input_params='=>'?array'],
      'new' => ['bool', 'params='=>'?array'],
    ],
    'PDOStatement::fetch' => [
      'old' => ['mixed', 'how='=>'int', 'orientation='=>'int', 'offset='=>'int'],
      'new' => ['mixed', 'mode='=>'int', 'cursorOrientation='=>'int', 'cursorOffset='=>'int'],
    ],
    'PDOStatement::fetchAll' => [
      'old' => ['array|false', 'how='=>'int', 'fetch_argument='=>'int|string|callable', 'ctor_args='=>'?array'],
      'new' => ['array', 'mode='=>'int', '...args='=>'mixed'],
    ],
    'PDOStatement::fetchColumn' => [
      'old' => ['string|int|float|bool|null', 'column_number='=>'int'],
      'new' => ['mixed', 'column='=>'int'],
    ],
    'PDOStatement::fetchObject' => [
      'old' => ['object|false', 'class_name='=>'string', 'ctor_args='=>'array'],
      'new' => ['object|false', 'class='=>'?string', 'ctorArgs='=>'?array'],
    ],
    'PDOStatement::setFetchMode' => [
      'old' => ['bool', 'mode'=>'int'],
      'new' => ['bool', 'mode'=>'int', '...args='=>'mixed'],
    ],
    'Phar::getMetadata' => [
      'old' => ['mixed'],
      'new' => ['mixed', 'unserializeOptions='=>'array'],
    ],
    'PharFileInfo::getMetadata' => [
      'old' => ['mixed'],
      'new' => ['mixed', 'unserializeOptions='=>'array'],
    ],
    'ReflectionClass::getConstants' => [
      'old' => ['array<string,mixed>'],
      'new' => ['array<string,mixed>', 'filter='=>'?int'],
    ],
    'ReflectionClass::getReflectionConstants' => [
        'old' => ['list<ReflectionClassConstant>'],
        'new' => ['list<ReflectionClassConstant>', 'filter='=>'?int'],
    ],
    'ReflectionClass::newInstanceArgs' => [
        'old' => ['object', 'args='=>'list<mixed>'],
        'new' => ['object', 'args='=>'array<array-key, mixed>'],
    ],
    'ReflectionProperty::getValue' => [
        'old' => ['mixed', 'object='=>'object'],
        'new' => ['mixed', 'object='=>'null|object'],
    ],
    'XMLWriter::flush' => [
      'old' => ['string|int|false', 'empty='=>'bool'],
      'new' => ['string|int', 'empty='=>'bool'],
    ],
    'SoapClient::__doRequest' => [
      'old' => ['?string', 'request'=>'string', 'location'=>'string', 'action'=>'string', 'version'=>'int', 'one_way='=>'int'],
      'new' => ['?string', 'request'=>'string', 'location'=>'string', 'action'=>'string', 'version'=>'int', 'one_way='=>'bool'],
    ],
    'XMLWriter::startAttributeNs' => [
      'old' => ['bool', 'prefix'=>'string', 'name'=>'string', 'namespace'=>'?string'],
      'new' => ['bool', 'prefix'=>'?string', 'name'=>'string', 'namespace'=>'?string'],
    ],
    'XMLWriter::writeAttributeNs' => [
      'old' => ['bool', 'prefix'=>'string', 'name'=>'string', 'namespace'=>'?string', 'value'=>'string'],
      'new' => ['bool', 'prefix'=>'?string', 'name'=>'string', 'namespace'=>'?string', 'value'=>'string'],
    ],
    'XMLWriter::writeDtdEntity' => [
      'old' => ['bool', 'name'=>'string', 'content'=>'string', 'isParam'=>'bool', 'publicId'=>'string', 'systemId'=>'string', 'notationData'=>'string'],
      'new' => ['bool', 'name'=>'string', 'content'=>'string', 'isParam='=>'bool', 'publicId='=>'?string', 'systemId='=>'?string', 'notationData='=>'?string'],
    ],
    'array_column' => [
        'old' => ['array', 'array'=>'array', 'column_key'=>'mixed', 'index_key='=>'mixed'],
        'new' => ['array', 'array'=>'array', 'column_key'=>'int|string|null', 'index_key='=>'int|string|null'],
    ],
    'array_combine' => [
      'old' => ['associative-array|false', 'keys'=>'string[]|int[]', 'values'=>'array'],
      'new' => ['associative-array', 'keys'=>'string[]|int[]', 'values'=>'array'],
    ],
    'array_diff' => [
        'old' => ['associative-array', 'array'=>'array', '...arrays'=>'array'],
        'new' => ['associative-array', 'array'=>'array', '...arrays='=>'array'],
    ],
    'array_diff_assoc' => [
        'old' => ['associative-array', 'array'=>'array', '...arrays'=>'array'],
        'new' => ['associative-array', 'array'=>'array', '...arrays='=>'array'],
    ],
    'array_diff_key' => [
        'old' => ['associative-array', 'array'=>'array', '...arrays'=>'array'],
        'new' => ['associative-array', 'array'=>'array', '...arrays='=>'array'],
    ],
    'array_key_exists' => [
        'old' => ['bool', 'key'=>'string|int', 'array'=>'array|object'],
        'new' => ['bool', 'key'=>'string|int', 'array'=>'array'],
    ],
    'array_intersect' => [
        'old' => ['associative-array', 'array'=>'array', '...arrays'=>'array'],
        'new' => ['associative-array', 'array'=>'array', '...arrays='=>'array'],
    ],
    'array_intersect_assoc' => [
        'old' => ['associative-array', 'array'=>'array', '...arrays'=>'array'],
        'new' => ['associative-array', 'array'=>'array', '...arrays='=>'array'],
    ],
    'array_intersect_key' => [
        'old' => ['associative-array', 'array'=>'array', '...arrays'=>'array'],
        'new' => ['associative-array', 'array'=>'array', '...arrays='=>'array'],
    ],
    'bcadd' => [
      'old' => ['numeric-string', 'num1'=>'numeric-string', 'num2'=>'numeric-string', 'scale='=>'int'],
      'new' => ['numeric-string', 'num1'=>'numeric-string', 'num2'=>'numeric-string', 'scale='=>'int|null'],
    ],
    'bccomp' => [
      'old' => ['int', 'num1'=>'numeric-string', 'num2'=>'numeric-string', 'scale='=>'int'],
      'new' => ['int', 'num1'=>'numeric-string', 'num2'=>'numeric-string', 'scale='=>'int|null'],
    ],
    'bcdiv' => [
      'old' => ['numeric-string|null', 'dividend'=>'numeric-string', 'divisor'=>'numeric-string', 'scale='=>'int'],
      'new' => ['numeric-string|null', 'dividend'=>'numeric-string', 'divisor'=>'numeric-string', 'scale='=>'int|null'],
    ],
    'bcmod' => [
      'old' => ['numeric-string|null', 'dividend'=>'numeric-string', 'divisor'=>'numeric-string', 'scale='=>'int'],
      'new' => ['numeric-string|null', 'dividend'=>'numeric-string', 'divisor'=>'numeric-string', 'scale='=>'int|null'],
    ],
    'bcmul' => [
      'old' => ['numeric-string', 'num1'=>'numeric-string', 'num2'=>'numeric-string', 'scale='=>'int'],
      'new' => ['numeric-string', 'num1'=>'numeric-string', 'num2'=>'numeric-string', 'scale='=>'int|null'],
    ],
    'bcpow' => [
      'old' => ['numeric-string', 'num'=>'numeric-string', 'exponent'=>'numeric-string', 'scale='=>'int'],
      'new' => ['numeric-string', 'num'=>'numeric-string', 'exponent'=>'numeric-string', 'scale='=>'int|null'],
    ],
    'bcpowmod' => [
      'old' => ['numeric-string|false', 'base'=>'numeric-string', 'exponent'=>'numeric-string', 'modulus'=>'numeric-string', 'scale='=>'int'],
      'new' => ['numeric-string|false', 'base'=>'numeric-string', 'exponent'=>'numeric-string', 'modulus'=>'numeric-string', 'scale='=>'int|null'],
    ],
    'bcscale' => [
      'old' => ['int', 'scale='=>'int'],
      'new' => ['int', 'scale='=>'int|null'],
    ],
    'bcsqrt' => [
      'old' => ['numeric-string', 'num'=>'numeric-string', 'scale='=>'int'],
      'new' => ['numeric-string|null', 'num'=>'numeric-string', 'scale='=>'int|null'],
    ],
    'bcsub' => [
      'old' => ['numeric-string', 'num1'=>'numeric-string', 'num2'=>'numeric-string', 'scale='=>'int'],
      'new' => ['numeric-string', 'num1'=>'numeric-string', 'num2'=>'numeric-string', 'scale='=>'int|null'],
    ],
    'com_load_typelib' => [
      'old' => ['bool', 'typelib_name'=>'string', 'case_insensitive='=>'bool'],
      'new' => ['bool', 'typelib_name'=>'string', 'case_insensitive='=>'true'],
    ],
    'count' => [
        'old' => ['int', 'value'=>'Countable|array|SimpleXMLElement', 'mode='=>'int'],
        'new' => ['int', 'value'=>'Countable|array', 'mode='=>'int'],
    ],
    'count_chars' => [
      'old' => ['array<int,int>|false', 'input'=>'string', 'mode='=>'0|1|2'],
      'new' => ['array<int,int>', 'input'=>'string', 'mode='=>'0|1|2'],
    ],
    'count_chars\'1' => [
      'old' => ['string|false', 'input'=>'string', 'mode='=>'3|4'],
      'new' => ['string', 'input'=>'string', 'mode='=>'3|4'],
    ],
    'curl_close' => [
      'old' => ['void', 'ch'=>'resource'],
      'new' => ['void', 'handle'=>'CurlHandle'],
    ],
    'curl_copy_handle' => [
      'old' => ['resource', 'ch'=>'resource'],
      'new' => ['CurlHandle', 'handle'=>'CurlHandle'],
    ],
    'curl_errno' => [
      'old' => ['int', 'ch'=>'resource'],
      'new' => ['int', 'handle'=>'CurlHandle'],
    ],
    'curl_error' => [
      'old' => ['string', 'ch'=>'resource'],
      'new' => ['string', 'handle'=>'CurlHandle'],
    ],
    'curl_escape' => [
      'old' => ['string|false', 'ch'=>'resource', 'string'=>'string'],
      'new' => ['string|false', 'handle'=>'CurlHandle', 'string'=>'string'],
    ],
    'curl_exec' => [
      'old' => ['bool|string', 'ch'=>'resource'],
      'new' => ['bool|string', 'handle'=>'CurlHandle'],
    ],
    'curl_file_create' => [
      'old' => ['CURLFile', 'filename'=>'string', 'mimetype='=>'string', 'postfilename='=>'string'],
      'new' => ['CURLFile', 'filename'=>'string', 'mime_type='=>'string|null', 'posted_filename='=>'string|null'],
    ],
    'curl_getinfo' => [
      'old' => ['mixed', 'ch'=>'resource', 'option='=>'int'],
      'new' => ['mixed', 'handle'=>'CurlHandle', 'option='=>'int'],
    ],
    'curl_init' => [
      'old' => ['resource|false', 'url='=>'string'],
      'new' => ['CurlHandle|false', 'url='=>'string'],
    ],
    'curl_multi_add_handle' => [
      'old' => ['int', 'mh'=>'resource', 'ch'=>'resource'],
      'new' => ['int', 'multi_handle'=>'CurlMultiHandle', 'handle'=>'CurlHandle'],
    ],
    'curl_multi_close' => [
      'old' => ['void', 'mh'=>'resource'],
      'new' => ['void', 'multi_handle'=>'CurlMultiHandle'],
    ],
    'curl_multi_errno' => [
      'old' => ['int|false', 'mh'=>'resource'],
      'new' => ['int', 'multi_handle'=>'CurlMultiHandle'],
    ],
    'curl_multi_exec' => [
      'old' => ['int', 'mh'=>'resource', '&w_still_running'=>'int'],
      'new' => ['int', 'multi_handle'=>'CurlMultiHandle', '&w_still_running'=>'int'],
    ],
    'curl_multi_getcontent' => [
      'old' => ['string', 'ch'=>'resource'],
      'new' => ['string', 'handle'=>'CurlHandle'],
    ],
    'curl_multi_info_read' => [
      'old' => ['array|false', 'mh'=>'resource', '&w_msgs_in_queue='=>'int'],
      'new' => ['array|false', 'multi_handle'=>'CurlMultiHandle', '&w_queued_messages='=>'int'],
    ],
    'curl_multi_init' => [
      'old' => ['resource|false'],
      'new' => ['CurlMultiHandle|false'],
    ],
    'curl_multi_remove_handle' => [
      'old' => ['int', 'mh'=>'resource', 'ch'=>'resource'],
      'new' => ['int', 'multi_handle'=>'CurlMultiHandle', 'handle'=>'CurlHandle'],
    ],
    'curl_multi_select' => [
      'old' => ['int', 'mh'=>'resource', 'timeout='=>'float'],
      'new' => ['int', 'multi_handle'=>'CurlMultiHandle', 'timeout='=>'float'],
    ],
    'curl_multi_setopt' => [
      'old' => ['bool', 'mh'=>'resource', 'option'=>'int', 'value'=>'mixed'],
      'new' => ['bool', 'multi_handle'=>'CurlMultiHandle', 'option'=>'int', 'value'=>'mixed'],
    ],
    'curl_pause' => [
      'old' => ['int', 'ch'=>'resource', 'bitmask'=>'int'],
      'new' => ['int', 'handle'=>'CurlHandle', 'flags'=>'int'],
    ],
    'curl_reset' => [
      'old' => ['void', 'ch'=>'resource'],
      'new' => ['void', 'handle'=>'CurlHandle'],
    ],
    'curl_setopt' => [
      'old' => ['bool', 'ch'=>'resource', 'option'=>'int', 'value'=>'callable|mixed'],
      'new' => ['bool', 'handle'=>'CurlHandle', 'option'=>'int', 'value'=>'callable|mixed'],
    ],
    'curl_setopt_array' => [
      'old' => ['bool', 'ch'=>'resource', 'options'=>'array'],
      'new' => ['bool', 'handle'=>'CurlHandle', 'options'=>'array'],
    ],
    'curl_share_close' => [
      'old' => ['void', 'sh'=>'resource'],
      'new' => ['void', 'share_handle'=>'CurlShareHandle'],
    ],
    'curl_share_errno' => [
      'old' => ['int|false', 'sh'=>'resource'],
      'new' => ['int', 'share_handle'=>'CurlShareHandle'],
    ],
    'curl_share_init' => [
      'old' => ['resource'],
      'new' => ['CurlShareHandle'],
    ],
    'curl_share_setopt' => [
      'old' => ['bool', 'sh'=>'resource', 'option'=>'int', 'value'=>'mixed'],
      'new' => ['bool', 'share_handle'=>'CurlShareHandle', 'option'=>'int', 'value'=>'mixed'],
    ],
    'curl_unescape' => [
      'old' => ['string|false', 'ch'=>'resource', 'string'=>'string'],
      'new' => ['string|false', 'handle'=>'CurlShareHandle', 'string'=>'string'],
    ],
    'date' => [
      'old' => ['string', 'format'=>'string', 'timestamp='=>'int'],
      'new' => ['string', 'format'=>'string', 'timestamp='=>'?int'],
    ],
    'date_format' => [
      'old' => ['string|false', 'object'=>'DateTimeInterface', 'format'=>'string'],
      'new' => ['string', 'object'=>'DateTimeInterface', 'format'=>'string'],
    ],
    'datefmt_create' => [
        'old' => ['?IntlDateFormatter', 'locale'=>'?string', 'dateType'=>'int', 'timeType'=>'int', 'timezone='=>'DateTimeZone|IntlTimeZone|string|null', 'calendar='=>'IntlCalendar|int|null', 'pattern='=>'string'],
        'new' => ['?IntlDateFormatter', 'locale'=>'?string', 'dateType='=>'int', 'timeType='=>'int', 'timezone='=>'DateTimeZone|IntlTimeZone|string|null', 'calendar='=>'IntlCalendar|int|null', 'pattern='=>'?string'],
    ],
    'dom_import_simplexml' => [
        'old' => ['DOMElement|null', 'node'=>'SimpleXMLElement'],
        'new' => ['DOMElement', 'node'=>'SimpleXMLElement'],
    ],
    'explode' => [
      'old' => ['list<string>|false', 'separator'=>'string', 'string'=>'string', 'limit='=>'int'],
      'new' => ['list<string>', 'separator'=>'string', 'string'=>'string', 'limit='=>'int'],
    ],
    'get_class_methods' => [
        'old' => ['list<string>|null', 'object_or_class'=>'mixed'],
        'new' => ['list<string>', 'object_or_class'=>'object|class-string'],
    ],
    'get_parent_class' => [
        'old' => ['class-string|false', 'object_or_class='=>'mixed'],
        'new' => ['class-string|false', 'object_or_class='=>'object|class-string'],
    ],
    'gmdate' => [
      'old' => ['string', 'format'=>'string', 'timestamp='=>'int'],
      'new' => ['string', 'format'=>'string', 'timestamp='=>'int|null'],
    ],
    'gmmktime' => [
      'old' => ['int|false', 'hour='=>'int', 'minute='=>'int', 'second='=>'int', 'month='=>'int', 'day='=>'int', 'year='=>'int'],
      'new' => ['int|false', 'hour'=>'int', 'minute='=>'int|null', 'second='=>'int|null', 'month='=>'int|null', 'day='=>'int|null', 'year='=>'int|null'],
    ],
    'gmp_binomial' => [
      'old' => ['GMP|false', 'n'=>'GMP|string|int', 'k'=>'int'],
      'new' => ['GMP', 'n'=>'GMP|string|int', 'k'=>'int'],
    ],
    'gmstrftime' => [
      'old' => ['string|false', 'format'=>'string', 'timestamp='=>'int'],
      'new' => ['string|false', 'format'=>'string', 'timestamp='=>'?int'],
    ],
    'hash' => [
      'old' => ['string|false', 'algo'=>'string', 'data'=>'string', 'binary='=>'bool'],
      'new' => ['non-empty-string', 'algo'=>'string', 'data'=>'string', 'binary='=>'bool'],
    ],
    'hash_hmac' => [
      'old' => ['non-empty-string|false', 'algo'=>'string', 'data'=>'string', 'key'=>'string', 'binary='=>'bool'],
      'new' => ['non-empty-string', 'algo'=>'string', 'data'=>'string', 'key'=>'string', 'binary='=>'bool'],
    ],
    'hash_init' => [
      'old' => ['HashContext|false', 'algo'=>'string', 'flags='=>'int', 'key='=>'string'],
      'new' => ['HashContext', 'algo'=>'string', 'flags='=>'int', 'key='=>'string'],
    ],
    'hash_hkdf' => [
      'old' => ['non-empty-string|false', 'algo'=>'string', 'key'=>'string', 'length='=>'int', 'info='=>'string', 'salt='=>'string'],
      'new' => ['non-empty-string', 'algo'=>'string', 'key'=>'string', 'length='=>'int', 'info='=>'string', 'salt='=>'string'],
    ],
    'hash_update_file' => [
      'old' => ['bool', 'context'=>'HashContext', 'filename'=>'string', 'stream_context='=>'resource'],
      'new' => ['bool', 'context'=>'HashContext', 'filename'=>'string', 'stream_context='=>'?resource'],
    ],
    'imageaffine' => [
      'old' => ['resource|false', 'src'=>'resource', 'affine'=>'array', 'clip='=>'array'],
      'new' => ['false|GdImage', 'image'=>'GdImage', 'affine'=>'array', 'clip='=>'?array'],
    ],
    'imagealphablending' => [
      'old' => ['bool', 'image'=>'resource', 'enable'=>'bool'],
      'new' => ['bool', 'image'=>'GdImage', 'enable'=>'bool'],
    ],
    'imageantialias' => [
      'old' => ['bool', 'image'=>'resource', 'enable'=>'bool'],
      'new' => ['bool', 'image'=>'GdImage', 'enable'=>'bool'],
    ],
    'imagearc' => [
      'old' => ['bool', 'image'=>'resource', 'center_x'=>'int', 'center_y'=>'int', 'width'=>'int', 'height'=>'int', 'start_angle'=>'int', 'end_angle'=>'int', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'center_x'=>'int', 'center_y'=>'int', 'width'=>'int', 'height'=>'int', 'start_angle'=>'int', 'end_angle'=>'int', 'color'=>'int'],
    ],
    'imagebmp' => [
      'old' => ['bool', 'image'=>'resource', 'file='=>'resource|string|null', 'compressed='=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'file='=>'resource|string|null', 'compressed='=>'bool'],
    ],
    'imagechar' => [
      'old' => ['bool', 'image'=>'resource', 'font'=>'int', 'x'=>'int', 'y'=>'int', 'char'=>'string', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'font'=>'int', 'x'=>'int', 'y'=>'int', 'char'=>'string', 'color'=>'int'],
    ],
    'imagecharup' => [
      'old' => ['bool', 'image'=>'resource', 'font'=>'int', 'x'=>'int', 'y'=>'int', 'char'=>'string', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'font'=>'int', 'x'=>'int', 'y'=>'int', 'char'=>'string', 'color'=>'int'],
    ],
    'imagecolorallocate' => [
      'old' => ['int|false', 'image'=>'resource', 'red'=>'int', 'green'=>'int', 'blue'=>'int'],
      'new' => ['int|false', 'image'=>'GdImage', 'red'=>'int', 'green'=>'int', 'blue'=>'int'],
    ],
    'imagecolorallocatealpha' => [
      'old' => ['int|false', 'image'=>'resource', 'red'=>'int', 'green'=>'int', 'blue'=>'int', 'alpha'=>'int'],
      'new' => ['int|false', 'image'=>'GdImage', 'red'=>'int', 'green'=>'int', 'blue'=>'int', 'alpha'=>'int'],
    ],
    'imagecolorat' => [
      'old' => ['int|false', 'image'=>'resource', 'x'=>'int', 'y'=>'int'],
      'new' => ['int|false', 'image'=>'GdImage', 'x'=>'int', 'y'=>'int'],
    ],
    'imagecolorclosest' => [
      'old' => ['int|false', 'image'=>'resource', 'red'=>'int', 'green'=>'int', 'blue'=>'int'],
      'new' => ['int|false', 'image'=>'GdImage', 'red'=>'int', 'green'=>'int', 'blue'=>'int'],
    ],
    'imagecolorclosestalpha' => [
      'old' => ['int|false', 'image'=>'resource', 'red'=>'int', 'green'=>'int', 'blue'=>'int', 'alpha'=>'int'],
      'new' => ['int|false', 'image'=>'GdImage', 'red'=>'int', 'green'=>'int', 'blue'=>'int', 'alpha'=>'int'],
    ],
    'imagecolorclosesthwb' => [
      'old' => ['int|false', 'image'=>'resource', 'red'=>'int', 'green'=>'int', 'blue'=>'int'],
      'new' => ['int|false', 'image'=>'GdImage', 'red'=>'int', 'green'=>'int', 'blue'=>'int'],
    ],
    'imagecolordeallocate' => [
      'old' => ['bool', 'image'=>'resource', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'color'=>'int'],
    ],
    'imagecolorexact' => [
      'old' => ['int|false', 'image'=>'resource', 'red'=>'int', 'green'=>'int', 'blue'=>'int'],
      'new' => ['int|false', 'image'=>'GdImage', 'red'=>'int', 'green'=>'int', 'blue'=>'int'],
    ],
    'imagecolorexactalpha' => [
      'old' => ['int|false', 'image'=>'resource', 'red'=>'int', 'green'=>'int', 'blue'=>'int', 'alpha'=>'int'],
      'new' => ['int|false', 'image'=>'GdImage', 'red'=>'int', 'green'=>'int', 'blue'=>'int', 'alpha'=>'int'],
    ],
    'imagecolormatch' => [
      'old' => ['bool', 'image1'=>'resource', 'image2'=>'resource'],
      'new' => ['bool', 'image1'=>'GdImage', 'image2'=>'GdImage'],
    ],
    'imagecolorresolve' => [
      'old' => ['int|false', 'image'=>'resource', 'red'=>'int', 'green'=>'int', 'blue'=>'int'],
      'new' => ['int|false', 'image'=>'GdImage', 'red'=>'int', 'green'=>'int', 'blue'=>'int'],
    ],
    'imagecolorresolvealpha' => [
      'old' => ['int|false', 'image'=>'resource', 'red'=>'int', 'green'=>'int', 'blue'=>'int', 'alpha'=>'int'],
      'new' => ['int|false', 'image'=>'GdImage', 'red'=>'int', 'green'=>'int', 'blue'=>'int', 'alpha'=>'int'],
    ],
    'imagecolorset' => [
      'old' => ['void', 'image'=>'resource', 'color'=>'int', 'red'=>'int', 'green'=>'int', 'blue'=>'int', 'alpha='=>'int'],
      'new' => ['void', 'image'=>'GdImage', 'color'=>'int', 'red'=>'int', 'green'=>'int', 'blue'=>'int', 'alpha='=>'int'],
    ],
    'imagecolorsforindex' => [
      'old' => ['array|false', 'image'=>'resource', 'color'=>'int'],
      'new' => ['array|false', 'image'=>'GdImage', 'color'=>'int'],
    ],
    'imagecolorstotal' => [
      'old' => ['int|false', 'image'=>'resource'],
      'new' => ['int|false', 'image'=>'GdImage'],
    ],
    'imagecolortransparent' => [
      'old' => ['int|false', 'image'=>'resource', 'color='=>'int'],
      'new' => ['int|false', 'image'=>'GdImage', 'color='=>'int'],
    ],
    'imageconvolution' => [
      'old' => ['bool', 'image'=>'resource', 'matrix'=>'array', 'divisor'=>'float', 'offset'=>'float'],
      'new' => ['bool', 'image'=>'GdImage', 'matrix'=>'array', 'divisor'=>'float', 'offset'=>'float'],
    ],
    'imagecopy' => [
      'old' => ['bool', 'dst_image'=>'resource', 'src_image'=>'resource', 'dst_x'=>'int', 'dst_y'=>'int', 'src_x'=>'int', 'src_y'=>'int', 'src_width'=>'int', 'src_height'=>'int'],
      'new' => ['bool', 'dst_image'=>'GdImage', 'src_image'=>'GdImage', 'dst_x'=>'int', 'dst_y'=>'int', 'src_x'=>'int', 'src_y'=>'int', 'src_width'=>'int', 'src_height'=>'int'],
    ],
    'imagecopymerge' => [
      'old' => ['bool', 'dst_image'=>'resource', 'src_image'=>'resource', 'dst_x'=>'int', 'dst_y'=>'int', 'src_x'=>'int', 'src_y'=>'int', 'src_width'=>'int', 'src_height'=>'int', 'pct'=>'int'],
      'new' => ['bool', 'dst_image'=>'GdImage', 'src_image'=>'GdImage', 'dst_x'=>'int', 'dst_y'=>'int', 'src_x'=>'int', 'src_y'=>'int', 'src_width'=>'int', 'src_height'=>'int', 'pct'=>'int'],
    ],
    'imagecopymergegray' => [
      'old' => ['bool', 'dst_image'=>'resource', 'src_image'=>'resource', 'dst_x'=>'int', 'dst_y'=>'int', 'src_x'=>'int', 'src_y'=>'int', 'src_width'=>'int', 'src_height'=>'int', 'pct'=>'int'],
      'new' => ['bool', 'dst_image'=>'GdImage', 'src_image'=>'GdImage', 'dst_x'=>'int', 'dst_y'=>'int', 'src_x'=>'int', 'src_y'=>'int', 'src_width'=>'int', 'src_height'=>'int', 'pct'=>'int'],
    ],
    'imagecopyresampled' => [
      'old' => ['bool', 'dst_image'=>'resource', 'src_image'=>'resource', 'dst_x'=>'int', 'dst_y'=>'int', 'src_x'=>'int', 'src_y'=>'int', 'dst_width'=>'int', 'dst_height'=>'int', 'src_width'=>'int', 'src_height'=>'int'],
      'new' => ['bool', 'dst_image'=>'GdImage', 'src_image'=>'GdImage', 'dst_x'=>'int', 'dst_y'=>'int', 'src_x'=>'int', 'src_y'=>'int', 'dst_width'=>'int', 'dst_height'=>'int', 'src_width'=>'int', 'src_height'=>'int'],
    ],
    'imagecopyresized' => [
      'old' => ['bool', 'dst_image'=>'resource', 'src_image'=>'resource', 'dst_x'=>'int', 'dst_y'=>'int', 'src_x'=>'int', 'src_y'=>'int', 'dst_width'=>'int', 'dst_height'=>'int', 'src_width'=>'int', 'src_height'=>'int'],
      'new' => ['bool', 'dst_image'=>'GdImage', 'src_image'=>'GdImage', 'dst_x'=>'int', 'dst_y'=>'int', 'src_x'=>'int', 'src_y'=>'int', 'dst_width'=>'int', 'dst_height'=>'int', 'src_width'=>'int', 'src_height'=>'int'],
    ],
    'imagecreate' => [
      'old' => ['resource|false', 'x_size'=>'int', 'y_size'=>'int'],
      'new' => ['false|GdImage', 'width'=>'int', 'height'=>'int'],
    ],
    'imagecreatefrombmp' => [
      'old' => ['resource|false', 'filename'=>'string'],
      'new' => ['false|GdImage', 'filename'=>'string'],
    ],
    'imagecreatefromgd' => [
      'old' => ['resource|false', 'filename'=>'string'],
      'new' => ['false|GdImage', 'filename'=>'string'],
    ],
    'imagecreatefromgd2' => [
      'old' => ['resource|false', 'filename'=>'string'],
      'new' => ['false|GdImage', 'filename'=>'string'],
    ],
    'imagecreatefromgd2part' => [
      'old' => ['resource|false', 'filename'=>'string', 'srcx'=>'int', 'srcy'=>'int', 'width'=>'int', 'height'=>'int'],
      'new' => ['false|GdImage', 'filename'=>'string', 'x'=>'int', 'y'=>'int', 'width'=>'int', 'height'=>'int'],
    ],
    'imagecreatefromgif' => [
      'old' => ['resource|false', 'filename'=>'string'],
      'new' => ['false|GdImage', 'filename'=>'string'],
    ],
    'imagecreatefromjpeg' => [
      'old' => ['resource|false', 'filename'=>'string'],
      'new' => ['false|GdImage', 'filename'=>'string'],
    ],
    'imagecreatefrompng' => [
      'old' => ['resource|false', 'filename'=>'string'],
      'new' => ['false|GdImage', 'filename'=>'string'],
    ],
    'imagecreatefromstring' => [
      'old' => ['resource|false', 'image'=>'string'],
      'new' => ['false|GdImage', 'data'=>'string'],
    ],
    'imagecreatefromwbmp' => [
      'old' => ['resource|false', 'filename'=>'string'],
      'new' => ['false|GdImage', 'filename'=>'string'],
    ],
    'imagecreatefromwebp' => [
      'old' => ['resource|false', 'filename'=>'string'],
      'new' => ['false|GdImage', 'filename'=>'string'],
    ],
    'imagecreatefromxbm' => [
      'old' => ['resource|false', 'filename'=>'string'],
      'new' => ['false|GdImage', 'filename'=>'string'],
    ],
    'imagecreatefromxpm' => [
      'old' => ['resource|false', 'filename'=>'string'],
      'new' => ['false|GdImage', 'filename'=>'string'],
    ],
    'imagecreatetruecolor' => [
      'old' => ['resource|false', 'x_size'=>'int', 'y_size'=>'int'],
      'new' => ['false|GdImage', 'width'=>'int', 'height'=>'int'],
    ],
    'imagecrop' => [
      'old' => ['resource|false', 'im'=>'resource', 'rect'=>'array'],
      'new' => ['false|GdImage', 'image'=>'GdImage', 'rectangle'=>'array'],
    ],
    'imagecropauto' => [
      'old' => ['resource|false', 'im'=>'resource', 'mode='=>'int', 'threshold='=>'float', 'color='=>'int'],
      'new' => ['false|GdImage', 'image'=>'GdImage', 'mode='=>'int', 'threshold='=>'float', 'color='=>'int'],
    ],
    'imagedashedline' => [
      'old' => ['bool', 'image'=>'resource', 'x1'=>'int', 'y1'=>'int', 'x2'=>'int', 'y2'=>'int', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'x1'=>'int', 'y1'=>'int', 'x2'=>'int', 'y2'=>'int', 'color'=>'int'],
    ],
    'imagedestroy' => [
      'old' => ['bool', 'image'=>'resource'],
      'new' => ['bool', 'image'=>'GdImage'],
    ],
    'imageellipse' => [
      'old' => ['bool', 'image'=>'resource', 'center_x'=>'int', 'center_y'=>'int', 'width'=>'int', 'height'=>'int', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'center_x'=>'int', 'center_y'=>'int', 'width'=>'int', 'height'=>'int', 'color'=>'int'],
    ],
    'imagefill' => [
      'old' => ['bool', 'image'=>'resource', 'x'=>'int', 'y'=>'int', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'x'=>'int', 'y'=>'int', 'color'=>'int'],
    ],
    'imagefilledarc' => [
      'old' => ['bool', 'image'=>'resource', 'center_x'=>'int', 'center_y'=>'int', 'width'=>'int', 'height'=>'int', 'start_angle'=>'int', 'end_angle'=>'int', 'color'=>'int', 'style'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'center_x'=>'int', 'center_y'=>'int', 'width'=>'int', 'height'=>'int', 'start_angle'=>'int', 'end_angle'=>'int', 'color'=>'int', 'style'=>'int'],
    ],
    'imagefilledellipse' => [
      'old' => ['bool', 'image'=>'resource', 'center_x'=>'int', 'center_y'=>'int', 'width'=>'int', 'height'=>'int', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'center_x'=>'int', 'center_y'=>'int', 'width'=>'int', 'height'=>'int', 'color'=>'int'],
    ],
    'imagefilledpolygon' => [
      'old' => ['bool', 'image'=>'resource', 'points'=>'array', 'num_points_or_color'=>'int', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'points'=>'array', 'num_points_or_color'=>'int', 'color'=>'int'],
    ],
    'imagefilledrectangle' => [
      'old' => ['bool', 'image'=>'resource', 'x1'=>'int', 'y1'=>'int', 'x2'=>'int', 'y2'=>'int', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'x1'=>'int', 'y1'=>'int', 'x2'=>'int', 'y2'=>'int', 'color'=>'int'],
    ],
    'imagefilltoborder' => [
      'old' => ['bool', 'image'=>'resource', 'x'=>'int', 'y'=>'int', 'border_color'=>'int', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'x'=>'int', 'y'=>'int', 'border_color'=>'int', 'color'=>'int'],
    ],
    'imagefilter' => [
      'old' => ['bool', 'image'=>'resource', 'filter'=>'int', 'args='=>'int', 'arg2='=>'int', 'arg3='=>'int', 'arg4='=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'filter'=>'int', 'args='=>'int', 'arg2='=>'int', 'arg3='=>'int', 'arg4='=>'int'],
    ],
    'imageflip' => [
      'old' => ['bool', 'image'=>'resource', 'mode'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'mode'=>'int'],
    ],
    'imagefttext' => [
      'old' => ['array|false', 'image'=>'resource', 'size'=>'float', 'angle'=>'float', 'x'=>'int', 'y'=>'int', 'color'=>'int', 'font_filename'=>'string', 'text'=>'string', 'options='=>'array'],
      'new' => ['array|false', 'image'=>'GdImage', 'size'=>'float', 'angle'=>'float', 'x'=>'int', 'y'=>'int', 'color'=>'int', 'font_filename'=>'string', 'text'=>'string', 'options='=>'array'],
    ],
    'imagegammacorrect' => [
      'old' => ['bool', 'image'=>'resource', 'input_gamma'=>'float', 'output_gamma'=>'float'],
      'new' => ['bool', 'image'=>'GdImage', 'input_gamma'=>'float', 'output_gamma'=>'float'],
    ],
    'imagegd' => [
      'old' => ['bool', 'image'=>'resource', 'file='=>'string|resource|null'],
      'new' => ['bool', 'image'=>'GdImage', 'file='=>'string|resource|null'],
    ],
    'imagegd2' => [
      'old' => ['bool', 'image'=>'resource', 'file='=>'string|resource|null', 'chunk_size='=>'int', 'mode='=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'file='=>'string|resource|null', 'chunk_size='=>'int', 'mode='=>'int'],
    ],
    'imagegetclip' => [
      'old' => ['array<int,int>|false', 'im'=>'resource'],
      'new' => ['array<int,int>', 'image'=>'GdImage'],
    ],
    'imagegif' => [
      'old' => ['bool', 'image'=>'resource', 'file='=>'string|resource|null'],
      'new' => ['bool', 'image'=>'GdImage', 'file='=>'string|resource|null'],
    ],
    'imagegrabscreen' => [
      'old' => ['false|resource'],
      'new' => ['false|GdImage'],
    ],
    'imagegrabwindow' => [
      'old' => ['false|resource', 'window_handle'=>'int', 'client_area='=>'int'],
      'new' => ['false|GdImage', 'handle'=>'int', 'client_area='=>'int'],
    ],
    'imageinterlace' => [
      'old' => ['int|false', 'image'=>'resource', 'enable='=>'int'],
      'new' => ['int|bool', 'image'=>'GdImage', 'enable='=>'bool|null'],
    ],
    'imageistruecolor' => [
      'old' => ['bool', 'image'=>'resource'],
      'new' => ['bool', 'image'=>'GdImage'],
    ],
    'imagejpeg' => [
      'old' => ['bool', 'image'=>'resource', 'file='=>'string|resource|null', 'quality='=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'file='=>'string|resource|null', 'quality='=>'int'],
    ],
    'imagelayereffect' => [
      'old' => ['bool', 'image'=>'resource', 'effect'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'effect'=>'int'],
    ],
    'imageline' => [
      'old' => ['bool', 'image'=>'resource', 'x1'=>'int', 'y1'=>'int', 'x2'=>'int', 'y2'=>'int', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'x1'=>'int', 'y1'=>'int', 'x2'=>'int', 'y2'=>'int', 'color'=>'int'],
    ],
    'imageopenpolygon' => [
      'old' => ['bool', 'image'=>'resource', 'points'=>'array', 'num_points'=>'int', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'points'=>'array', 'num_points'=>'int', 'color'=>'int'],
    ],
    'imagepalettecopy' => [
      'old' => ['void', 'dst'=>'resource', 'src'=>'resource'],
      'new' => ['void', 'dst'=>'GdImage', 'src'=>'GdImage'],
    ],
    'imagepalettetotruecolor' => [
      'old' => ['bool', 'image'=>'resource'],
      'new' => ['bool', 'image'=>'GdImage'],
    ],
    'imagepng' => [
      'old' => ['bool', 'image'=>'resource', 'file='=>'string|resource|null', 'quality='=>'int', 'filters='=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'file='=>'string|resource|null', 'quality='=>'int', 'filters='=>'int'],
    ],
    'imagepolygon' => [
      'old' => ['bool', 'image'=>'resource', 'points'=>'array', 'num_points_or_color'=>'int', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'points'=>'array', 'num_points_or_color'=>'int', 'color'=>'int'],
    ],
    'imagerectangle' => [
      'old' => ['bool', 'image'=>'resource', 'x1'=>'int', 'y1'=>'int', 'x2'=>'int', 'y2'=>'int', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'x1'=>'int', 'y1'=>'int', 'x2'=>'int', 'y2'=>'int', 'color'=>'int'],
    ],
    'imageresolution' => [
      'old' => ['array|bool', 'image'=>'resource', 'resolution_x='=>'int', 'resolution_y='=>'int'],
      'new' => ['array|bool', 'image'=>'GdImage', 'resolution_x='=>'int', 'resolution_y='=>'int'],
    ],
    'imagerotate' => [
      'old' => ['resource|false', 'src_im'=>'resource', 'angle'=>'float', 'bgdcolor'=>'int', 'ignoretransparent='=>'int'],
      'new' => ['false|GdImage', 'image'=>'GdImage', 'angle'=>'float', 'background_color'=>'int', 'ignore_transparent='=>'int'],
    ],
    'imagesavealpha' => [
      'old' => ['bool', 'image'=>'resource', 'enable'=>'bool'],
      'new' => ['bool', 'image'=>'GdImage', 'enable'=>'bool'],
    ],
    'imagescale' => [
      'old' => ['resource|false', 'im'=>'resource', 'new_width'=>'int', 'new_height='=>'int', 'method='=>'int'],
      'new' => ['false|GdImage', 'image'=>'GdImage', 'width'=>'int', 'height='=>'int', 'mode='=>'int'],
    ],
    'imagesetbrush' => [
      'old' => ['bool', 'image'=>'resource', 'brush'=>'resource'],
      'new' => ['bool', 'image'=>'GdImage', 'brush'=>'GdImage'],
    ],
    'imagesetclip' => [
      'old' => ['bool', 'image'=>'resource', 'x1'=>'int', 'x2'=>'int', 'y1'=>'int', 'y2'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'x1'=>'int', 'x2'=>'int', 'y1'=>'int', 'y2'=>'int'],
    ],
    'imagesetinterpolation' => [
      'old' => ['bool', 'image'=>'resource', 'method'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'method'=>'int'],
    ],
    'imagesetpixel' => [
      'old' => ['bool', 'image'=>'resource', 'x'=>'int', 'y'=>'int', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'x'=>'int', 'y'=>'int', 'color'=>'int'],
    ],
    'imagesetstyle' => [
      'old' => ['bool', 'image'=>'resource', 'style'=>'non-empty-array'],
      'new' => ['bool', 'image'=>'GdImage', 'style'=>'non-empty-array'],
    ],
    'imagesetthickness' => [
      'old' => ['bool', 'image'=>'resource', 'thickness'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'thickness'=>'int'],
    ],
    'imagesettile' => [
      'old' => ['bool', 'image'=>'resource', 'tile'=>'resource'],
      'new' => ['bool', 'image'=>'GdImage', 'tile'=>'GdImage'],
    ],
    'imagestring' => [
      'old' => ['bool', 'image'=>'resource', 'font'=>'int', 'x'=>'int', 'y'=>'int', 'string'=>'string', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'font'=>'int', 'x'=>'int', 'y'=>'int', 'string'=>'string', 'color'=>'int'],
    ],
    'imagestringup' => [
      'old' => ['bool', 'image'=>'resource', 'font'=>'int', 'x'=>'int', 'y'=>'int', 'string'=>'string', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'font'=>'int', 'x'=>'int', 'y'=>'int', 'string'=>'string', 'color'=>'int'],
    ],
    'imagesx' => [
      'old' => ['int|false', 'image'=>'resource'],
      'new' => ['int|false', 'image'=>'GdImage'],
    ],
    'imagesy' => [
      'old' => ['int|false', 'image'=>'resource'],
      'new' => ['int|false', 'image'=>'GdImage'],
    ],
    'imagetruecolortopalette' => [
      'old' => ['bool', 'image'=>'resource', 'dither'=>'bool', 'num_colors'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'dither'=>'bool', 'num_colors'=>'int'],
    ],
    'imagettftext' => [
      'old' => ['false|array', 'image'=>'resource', 'size'=>'float', 'angle'=>'float', 'x'=>'int', 'y'=>'int', 'color'=>'int', 'font_filename'=>'string', 'text'=>'string'],
      'new' => ['false|array', 'image'=>'GdImage', 'size'=>'float', 'angle'=>'float', 'x'=>'int', 'y'=>'int', 'color'=>'int', 'font_filename'=>'string', 'text'=>'string'],
    ],
    'imagewbmp' => [
      'old' => ['bool', 'image'=>'resource', 'file='=>'string|resource|null', 'foreground_color='=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'file='=>'string|resource|null', 'foreground_color='=>'int'],
    ],
    'imagewebp' => [
      'old' => ['bool', 'image'=>'resource', 'file='=>'string|resource|null', 'quality='=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'file='=>'string|resource|null', 'quality='=>'int'],
    ],
    'imagexbm' => [
      'old' => ['bool', 'image'=>'resource', 'filename='=>'?string', 'foreground_color='=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'filename='=>'?string', 'foreground_color='=>'int'],
    ],
    'ldap_exop_passwd' => [
      'old' => ['bool|string', 'ldap'=>'resource', 'user='=>'string', 'old_password='=>'string', 'new_password='=>'string', '&w_controls='=>'array'],
      'new' => ['bool|string', 'ldap'=>'resource', 'user='=>'string', 'old_password='=>'string', 'new_password='=>'string', '&w_controls='=>'array|null'],
    ],
    'ldap_set_rebind_proc' => [
      'old' => ['bool', 'ldap'=>'resource', 'callback'=>'callable'],
      'new' => ['bool', 'ldap'=>'resource', 'callback'=>'?callable'],
    ],
    'mb_check_encoding' => [
      'old' => ['bool', 'value='=>'array|string', 'encoding='=>'string'],
      'new' => ['bool', 'value='=>'array|string|null', 'encoding='=>'string|null'],
    ],
    'mb_chr' => [
      'old' => ['string|false', 'codepoint'=>'int', 'encoding='=>'string'],
      'new' => ['string|false', 'codepoint'=>'int', 'encoding='=>'string|null'],
    ],
    'mb_convert_case' => [
      'old' => ['string', 'string'=>'string', 'mode'=>'int', 'encoding='=>'string'],
      'new' => ['string', 'string'=>'string', 'mode'=>'int', 'encoding='=>'string|null'],
    ],
    'mb_convert_encoding' => [
      'old' => ['string|false', 'string'=>'string', 'to_encoding'=>'string', 'from_encoding='=>'mixed'],
      'new' => ['string|false', 'string'=>'string', 'to_encoding'=>'string', 'from_encoding='=>'array|string|null'],
    ],
    'mb_convert_encoding\'1' => [
      'old' => ['array', 'string'=>'array', 'to_encoding'=>'string', 'from_encoding='=>'mixed'],
      'new' => ['array', 'string'=>'array', 'to_encoding'=>'string', 'from_encoding='=>'array|string|null'],
    ],
    'mb_convert_kana' => [
      'old' => ['string', 'string'=>'string', 'mode='=>'string', 'encoding='=>'string'],
      'new' => ['string', 'string'=>'string', 'mode='=>'string', 'encoding='=>'string|null'],
    ],
    'mb_decode_numericentity' => [
      'old' => ['string', 'string'=>'string', 'map'=>'array', 'encoding='=>'string'],
      'new' => ['string', 'string'=>'string', 'map'=>'array', 'encoding='=>'string|null'],
    ],
    'mb_detect_encoding' => [
      'old' => ['string|false', 'string'=>'string', 'encodings='=>'mixed', 'strict='=>'bool'],
      'new' => ['string|false', 'string'=>'string', 'encodings='=>'array|string|null', 'strict='=>'bool'],
    ],
    'mb_detect_order' => [
      'old' => ['bool|list<string>', 'encoding='=>'mixed'],
      'new' => ['bool|list<string>', 'encoding='=>'array|string|null'],
    ],
    'mb_encode_mimeheader' => [
      'old' => ['string', 'string'=>'string', 'charset='=>'string', 'transfer_encoding='=>'string', 'newline='=>'string', 'indent='=>'int'],
      'new' => ['string', 'string'=>'string', 'charset='=>'string|null', 'transfer_encoding='=>'string|null', 'newline='=>'string', 'indent='=>'int'],
    ],
    'mb_encode_numericentity' => [
      'old' => ['string', 'string'=>'string', 'map'=>'array', 'encoding='=>'string', 'hex='=>'bool'],
      'new' => ['string', 'string'=>'string', 'map'=>'array', 'encoding='=>'string|null', 'hex='=>'bool'],
    ],
    'mb_ereg' => [
      'old' => ['int|false', 'pattern'=>'string', 'string'=>'string', '&w_matches='=>'array|null'],
      'new' => ['bool', 'pattern'=>'string', 'string'=>'string', '&w_matches='=>'array|null'],
    ],
    'mb_ereg_match' => [
      'old' => ['bool', 'pattern'=>'string', 'string'=>'string', 'options='=>'string'],
      'new' => ['bool', 'pattern'=>'string', 'string'=>'string', 'options='=>'string|null'],
    ],
    'mb_ereg_replace' => [
      'old' => ['string|false', 'pattern'=>'string', 'replacement'=>'string', 'string'=>'string', 'options='=>'string'],
      'new' => ['string|false|null', 'pattern'=>'string', 'replacement'=>'string', 'string'=>'string', 'options='=>'string|null'],
    ],
    'mb_ereg_replace_callback' => [
      'old' => ['string|false|null', 'pattern'=>'string', 'callback'=>'callable', 'string'=>'string', 'options='=>'string'],
      'new' => ['string|false|null', 'pattern'=>'string', 'callback'=>'callable', 'string'=>'string', 'options='=>'string|null'],
    ],
    'mb_ereg_search' => [
      'old' => ['bool', 'pattern='=>'string', 'options='=>'string'],
      'new' => ['bool', 'pattern='=>'string|null', 'options='=>'string|null'],
    ],
    'mb_ereg_search_init' => [
      'old' => ['bool', 'string'=>'string', 'pattern='=>'string', 'options='=>'string'],
      'new' => ['bool', 'string'=>'string', 'pattern='=>'string|null', 'options='=>'string|null'],
    ],
    'mb_ereg_search_pos' => [
      'old' => ['int[]|false', 'pattern='=>'string', 'options='=>'string'],
      'new' => ['int[]|false', 'pattern='=>'string|null', 'options='=>'string|null'],
    ],
    'mb_ereg_search_regs' => [
      'old' => ['string[]|false', 'pattern='=>'string', 'options='=>'string'],
      'new' => ['string[]|false', 'pattern='=>'string|null', 'options='=>'string|null'],
    ],
    'mb_eregi' => [
      'old' => ['int|false', 'pattern'=>'string', 'string'=>'string', '&w_matches='=>'array'],
      'new' => ['bool', 'pattern'=>'string', 'string'=>'string', '&w_matches='=>'array|null'],
    ],
    'mb_eregi_replace' => [
      'old' => ['string|false', 'pattern'=>'string', 'replacement'=>'string', 'string'=>'string', 'options='=>'string'],
      'new' => ['string|false|null', 'pattern'=>'string', 'replacement'=>'string', 'string'=>'string', 'options='=>'string|null'],
    ],
    'mb_http_input' => [
      'old' => ['string|false', 'type='=>'string'],
      'new' => ['array|string|false', 'type='=>'string|null'],
    ],
    'mb_http_output' => [
      'old' => ['string|bool', 'encoding='=>'string'],
      'new' => ['string|bool', 'encoding='=>'string|null'],
    ],
    'mb_internal_encoding' => [
      'old' => ['string|bool', 'encoding='=>'string'],
      'new' => ['string|bool', 'encoding='=>'string|null'],
    ],
    'mb_language' => [
      'old' => ['string|bool', 'language='=>'string'],
      'new' => ['string|bool', 'language='=>'string|null'],
    ],
    'mb_ord' => [
      'old' => ['int|false', 'string'=>'string', 'encoding='=>'string'],
      'new' => ['int|false', 'string'=>'string', 'encoding='=>'string|null'],
    ],
    'mb_parse_str' => [
      'old' => ['bool', 'string'=>'string', '&w_result='=>'array'],
      'new' => ['bool', 'string'=>'string', '&w_result'=>'array'],
    ],
    'mb_regex_encoding' => [
      'old' => ['string|bool', 'encoding='=>'string'],
      'new' => ['string|bool', 'encoding='=>'string|null'],
    ],
    'mb_regex_set_options' => [
      'old' => ['string', 'options='=>'string'],
      'new' => ['string', 'options='=>'string|null'],
    ],
    'mb_scrub' => [
      'old' => ['string', 'string'=>'string', 'encoding='=>'string'],
      'new' => ['string', 'string'=>'string', 'encoding='=>'string|null'],
    ],
    'mb_send_mail' => [
      'old' => ['bool', 'to'=>'string', 'subject'=>'string', 'message'=>'string', 'additional_headers='=>'string|array', 'additional_params='=>'string'],
      'new' => ['bool', 'to'=>'string', 'subject'=>'string', 'message'=>'string', 'additional_headers='=>'string|array', 'additional_params='=>'string|null'],
    ],
    'mb_str_split' => [
      'old' => ['list<string>|false', 'string'=>'string', 'length='=>'positive-int', 'encoding='=>'string'],
      'new' => ['list<string>', 'string'=>'string', 'length='=>'positive-int', 'encoding='=>'string|null'],
    ],
    'mb_strcut' => [
      'old' => ['string', 'string'=>'string', 'start'=>'int', 'length='=>'?int', 'encoding='=>'string'],
      'new' => ['string', 'string'=>'string', 'start'=>'int', 'length='=>'?int', 'encoding='=>'string|null'],
    ],
    'mb_strimwidth' => [
      'old' => ['string', 'string'=>'string', 'start'=>'int', 'width'=>'int', 'trim_marker='=>'string', 'encoding='=>'string'],
      'new' => ['string', 'string'=>'string', 'start'=>'int', 'width'=>'int', 'trim_marker='=>'string', 'encoding='=>'string|null'],
    ],
    'mb_stripos' => [
      'old' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int', 'encoding='=>'string'],
      'new' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int', 'encoding='=>'string|null'],
    ],
    'mb_stristr' => [
      'old' => ['string|false', 'haystack'=>'string', 'needle'=>'string', 'before_needle='=>'bool', 'encoding='=>'string'],
      'new' => ['string|false', 'haystack'=>'string', 'needle'=>'string', 'before_needle='=>'bool', 'encoding='=>'string|null'],
    ],
    'mb_strlen' => [
      'old' => ['0|positive-int', 'string'=>'string', 'encoding='=>'string'],
      'new' => ['0|positive-int', 'string'=>'string', 'encoding='=>'string|null'],
    ],
    'mb_strpos' => [
      'old' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int', 'encoding='=>'string'],
      'new' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int', 'encoding='=>'string|null'],
    ],
    'mb_strrchr' => [
      'old' => ['string|false', 'haystack'=>'string', 'needle'=>'string', 'before_needle='=>'bool', 'encoding='=>'string'],
      'new' => ['string|false', 'haystack'=>'string', 'needle'=>'string', 'before_needle='=>'bool', 'encoding='=>'string|null'],
    ],
    'mb_strrichr' => [
      'old' => ['string|false', 'haystack'=>'string', 'needle'=>'string', 'before_needle='=>'bool', 'encoding='=>'string'],
      'new' => ['string|false', 'haystack'=>'string', 'needle'=>'string', 'before_needle='=>'bool', 'encoding='=>'string|null'],
    ],
    'mb_strripos' => [
      'old' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int', 'encoding='=>'string'],
      'new' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int', 'encoding='=>'string|null'],
    ],
    'mb_strrpos' => [
      'old' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int', 'encoding='=>'string'],
      'new' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int', 'encoding='=>'string|null'],
    ],
    'mb_strstr' => [
      'old' => ['string|false', 'haystack'=>'string', 'needle'=>'string', 'before_needle='=>'bool', 'encoding='=>'string'],
      'new' => ['string|false', 'haystack'=>'string', 'needle'=>'string', 'before_needle='=>'bool', 'encoding='=>'string|null'],
    ],
    'mb_strtolower' => [
      'old' => ['lowercase-string', 'string'=>'string', 'encoding='=>'string'],
      'new' => ['lowercase-string', 'string'=>'string', 'encoding='=>'string|null'],
    ],
    'mb_strtoupper' => [
      'old' => ['string', 'string'=>'string', 'encoding='=>'string'],
      'new' => ['string', 'string'=>'string', 'encoding='=>'string|null'],
    ],
    'mb_strwidth' => [
      'old' => ['int', 'string'=>'string', 'encoding='=>'string'],
      'new' => ['int', 'string'=>'string', 'encoding='=>'string|null'],
    ],
    'mb_substitute_character' => [
      'old' => ['bool|int|string', 'substitute_character='=>'mixed'],
      'new' => ['bool|int|string', 'substitute_character='=>'int|string|null'],
    ],
    'mb_substr' => [
      'old' => ['string', 'string'=>'string', 'start'=>'int', 'length='=>'?int', 'encoding='=>'string'],
      'new' => ['string', 'string'=>'string', 'start'=>'int', 'length='=>'?int', 'encoding='=>'string|null'],
    ],
    'mb_substr_count' => [
      'old' => ['int', 'haystack'=>'string', 'needle'=>'string', 'encoding='=>'string'],
      'new' => ['int', 'haystack'=>'string', 'needle'=>'string', 'encoding='=>'string|null'],
    ],
    'mktime' => [
      'old' => ['int|false', 'hour='=>'int', 'minute='=>'int', 'second='=>'int', 'month='=>'int', 'day='=>'int', 'year='=>'int'],
      'new' => ['int|false', 'hour'=>'int', 'minute='=>'int|null', 'second='=>'int|null', 'month='=>'int|null', 'day='=>'int|null', 'year='=>'int|null'],
    ],
    'mysqli::__construct' => [
      'old' => ['void', 'hostname='=>'string', 'username='=>'string', 'password='=>'string', 'database='=>'string', 'port='=>'int', 'socket='=>'string'],
      'new' => ['void', 'hostname='=>'string|null', 'username='=>'string|null', 'password='=>'string|null', 'database='=>'string|null', 'port='=>'int|null', 'socket='=>'string|null'],
    ],
    'mysqli::begin_transaction' => [
      'old' => ['bool', 'flags='=>'int', 'name='=>'string'],
      'new' => ['bool', 'flags='=>'int', 'name='=>'?string'],
    ],
    'mysqli::commit' => [
      'old' => ['bool', 'flags='=>'int', 'name='=>'string'],
      'new' => ['bool', 'flags='=>'int', 'name='=>'?string'],
    ],
    'mysqli::connect' => [
      'old' => ['null|false', 'hostname='=>'string', 'username='=>'string', 'password='=>'string', 'database='=>'string', 'port='=>'int', 'socket='=>'string'],
      'new' => ['null|false', 'hostname='=>'string|null', 'username='=>'string|null', 'password='=>'string|null', 'database='=>'string|null', 'port='=>'int|null', 'socket='=>'string|null'],
    ],
    'mysqli::rollback' => [
      'old' => ['bool', 'flags='=>'int', 'name='=>'string'],
      'new' => ['bool', 'flags='=>'int', 'name='=>'?string'],
    ],
    'mysqli_begin_transaction' => [
      'old' => ['bool', 'mysql'=>'mysqli', 'flags='=>'int', 'name='=>'string'],
      'new' => ['bool', 'mysql'=>'mysqli', 'flags='=>'int', 'name='=>'?string'],
    ],
    'mysqli_commit' => [
      'old' => ['bool', 'mysql'=>'mysqli', 'flags='=>'int', 'name='=>'string'],
      'new' => ['bool', 'mysql'=>'mysqli', 'flags='=>'int', 'name='=>'?string'],
    ],
    'mysqli_connect' => [
      'old' => ['mysqli|false', 'hostname='=>'string', 'username='=>'string', 'password='=>'string', 'database='=>'string', 'port='=>'int', 'socket='=>'string'],
      'new' => ['mysqli|false', 'hostname='=>'string|null', 'username='=>'string|null', 'password='=>'string|null', 'database='=>'string|null', 'port='=>'int|null', 'socket='=>'string|null'],
    ],
    'mysqli_rollback' => [
      'old' => ['bool', 'mysql'=>'mysqli', 'flags='=>'int', 'name='=>'string'],
      'new' => ['bool', 'mysql'=>'mysqli', 'flags='=>'int', 'name='=>'?string'],
    ],
    'number_format' => [
      'old' => ['string', 'num'=>'float|int', 'decimals='=>'int'],
      'new' => ['string', 'num'=>'float|int', 'decimals='=>'int', 'decimal_separator='=>'?string', 'thousands_separator='=>'?string'],
    ],
    'ob_implicit_flush' => [
      'old' => ['void', 'enable='=>'int'],
      'new' => ['void', 'enable='=>'bool'],
    ],
    'openssl_csr_export' => [
      'old' => ['bool', 'csr'=>'string|resource', '&w_output'=>'string', 'no_text='=>'bool'],
      'new' => ['bool', 'csr'=>'OpenSSLCertificateSigningRequest|string', '&w_output'=>'string', 'no_text='=>'bool'],
    ],
    'openssl_csr_export_to_file' => [
      'old' => ['bool', 'csr'=>'string|resource', 'output_filename'=>'string', 'no_text='=>'bool'],
      'new' => ['bool', 'csr'=>'OpenSSLCertificateSigningRequest|string', 'output_filename'=>'string', 'no_text='=>'bool'],
    ],
    'openssl_csr_get_public_key' => [
      'old' => ['resource|false', 'csr'=>'string|resource', 'short_names='=>'bool'],
      'new' => ['OpenSSLAsymmetricKey|false', 'csr'=>'OpenSSLCertificateSigningRequest|string', 'short_names='=>'bool'],
    ],
    'openssl_csr_get_subject' => [
      'old' => ['array|false', 'csr'=>'string|resource', 'short_names='=>'bool'],
      'new' => ['array|false', 'csr'=>'OpenSSLCertificateSigningRequest|string', 'short_names='=>'bool'],
    ],
    'openssl_csr_new' => [
      'old' => ['resource|false', 'distinguished_names'=>'array', '&w_private_key'=>'resource', 'options='=>'array', 'extra_attributes='=>'array'],
      'new' => ['OpenSSLCertificateSigningRequest|false', 'distinguished_names'=>'array', '&w_private_key'=>'OpenSSLAsymmetricKey', 'options='=>'array|null', 'extra_attributes='=>'array|null'],
    ],
    'openssl_csr_sign' => [
        'old' =>  ['resource|false', 'csr'=>'string|resource', 'ca_certificate'=>'string|resource|null', 'private_key'=>'string|resource|array', 'days'=>'int', 'options='=>'array', 'serial='=>'int'],
        'new' => ['OpenSSLCertificate|false', 'csr'=>'OpenSSLCertificateSigningRequest|string', 'ca_certificate'=>'OpenSSLCertificate|string|null', 'private_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'days'=>'int', 'options='=>'array|null', 'serial='=>'int'],
    ],
    'openssl_dh_compute_key' => [
      'old' => ['string|false', 'public_key'=>'string', 'private_key'=>'resource'],
      'new' => ['string|false', 'public_key'=>'string', 'private_key'=>'OpenSSLAsymmetricKey'],
    ],
    'openssl_free_key' => [
      'old' => ['void', 'key'=>'resource'],
      'new' => ['void', 'key'=>'OpenSSLAsymmetricKey'],
    ],
    'openssl_get_privatekey' => [
      'old' => ['resource|false', 'private_key'=>'string', 'passphrase='=>'string'],
      'new' => ['OpenSSLAsymmetricKey|false', 'private_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'passphrase='=>'?string'],
    ],
    'openssl_get_publickey' => [
      'old' => ['resource|false', 'public_key'=>'resource|string'],
      'new' => ['OpenSSLAsymmetricKey|false', 'public_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string'],
    ],
    'openssl_open' => [
      'old' => ['bool', 'data'=>'string', '&w_output'=>'string', 'encrypted_key'=>'string', 'private_key'=>'string|array|resource', 'cipher_algo='=>'string', 'iv='=>'string'],
      'new' => ['bool', 'data'=>'string', '&w_output'=>'string', 'encrypted_key'=>'string', 'private_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'cipher_algo'=>'string', 'iv='=>'string|null'],
    ],
    'openssl_pkcs12_export' => [
      'old' => ['bool', 'certificate'=>'string|resource', '&w_output'=>'string', 'private_key'=>'string|array|resource', 'passphrase'=>'string', 'options='=>'array'],
      'new' => ['bool', 'certificate'=>'OpenSSLCertificate|string', '&w_output'=>'string', 'private_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'passphrase'=>'string', 'options='=>'array'],
    ],
    'openssl_pkcs12_export_to_file' => [
      'old' => ['bool', 'certificate'=>'string|resource', 'output_filename'=>'string', 'private_key'=>'string|array|resource', 'passphrase'=>'string', 'options='=>'array'],
      'new' => ['bool', 'certificate'=>'OpenSSLCertificate|string', 'output_filename'=>'string', 'private_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'passphrase'=>'string', 'options='=>'array'],
    ],
    'openssl_pkcs7_decrypt' => [
      'old' => ['bool', 'input_filename'=>'string', 'output_filename'=>'string', 'certificate'=>'string|resource', 'private_key='=>'string|resource|array'],
      'new' => ['bool', 'input_filename'=>'string', 'output_filename'=>'string', 'certificate'=>'OpenSSLCertificate|string', 'private_key='=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string|null'],
    ],
    'openssl_pkcs7_encrypt' => [
      'old' => ['bool', 'input_filename'=>'string', 'output_filename'=>'string', 'certificate'=>'string|resource|array', 'headers'=>'array', 'flags='=>'int', 'cipher_algo='=>'int'],
      'new' => ['bool', 'input_filename'=>'string', 'output_filename'=>'string', 'certificate'=>'OpenSSLCertificate|list<OpenSSLCertificate|string>|string', 'headers'=>'array|null', 'flags='=>'int', 'cipher_algo='=>'int'],
    ],
    'openssl_pkcs7_sign' => [
      'old' => ['bool', 'input_filename'=>'string', 'output_filename'=>'string', 'certificate'=>'string|resource', 'private_key'=>'string|resource|array', 'headers'=>'array', 'flags='=>'int', 'untrusted_certificates_filename='=>'string'],
      'new' => ['bool', 'input_filename'=>'string', 'output_filename'=>'string', 'certificate'=>'OpenSSLCertificate|string', 'private_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'headers'=>'array|null', 'flags='=>'int', 'untrusted_certificates_filename='=>'string|null'],
    ],
    'openssl_pkey_derive' => [
      'old' => ['string|false', 'public_key'=>'mixed', 'private_key'=>'mixed', 'key_length='=>'?int'],
      'new' => ['string|false', 'public_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'private_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'key_length='=>'int'],
    ],
    'openssl_pkey_export' => [
      'old' => ['bool', 'key'=>'resource', '&w_output'=>'string', 'passphrase='=>'string|null', 'options='=>'array'],
      'new' => ['bool', 'key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', '&w_output'=>'string', 'passphrase='=>'string|null', 'options='=>'array|null'],
    ],
    'openssl_pkey_export_to_file' => [
      'old' => ['bool', 'key'=>'resource|string|array', 'output_filename'=>'string', 'passphrase='=>'string|null', 'options='=>'array'],
      'new' => ['bool', 'key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'output_filename'=>'string', 'passphrase='=>'string|null', 'options='=>'array|null'],
    ],
    'openssl_pkey_free' => [
      'old' => ['void', 'key'=>'resource'],
      'new' => ['void', 'key'=>'OpenSSLAsymmetricKey'],
    ],
    'openssl_pkey_get_details' => [
      'old' => ['array|false', 'key'=>'resource'],
      'new' => ['array|false', 'key'=>'OpenSSLAsymmetricKey'],
    ],
    'openssl_pkey_get_private' => [
      'old' => ['resource|false', 'private_key'=>'string', 'passphrase='=>'string'],
      'new' => ['OpenSSLAsymmetricKey|false', 'private_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array|string', 'passphrase='=>'?string'],
    ],
    'openssl_pkey_get_public' => [
      'old' => ['resource|false', 'public_key'=>'resource|string'],
      'new' => ['OpenSSLAsymmetricKey|false', 'public_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string'],
    ],
    'openssl_pkey_new' => [
      'old' => ['resource|false', 'options='=>'array'],
      'new' => ['OpenSSLAsymmetricKey|false', 'options='=>'array|null'],
    ],
    'openssl_private_decrypt' => [
      'old' => ['bool', 'data'=>'string', '&w_decrypted_data'=>'string', 'private_key'=>'string|resource|array', 'padding='=>'int'],
      'new' => ['bool', 'data'=>'string', '&w_decrypted_data'=>'string', 'private_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'padding='=>'int'],
    ],
    'openssl_private_encrypt' => [
      'old' => ['bool', 'data'=>'string', '&w_encrypted_data'=>'string', 'private_key'=>'string|resource|array', 'padding='=>'int'],
      'new' => ['bool', 'data'=>'string', '&w_encrypted_data'=>'string', 'private_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'padding='=>'int'],
    ],
    'openssl_public_decrypt' => [
      'old' => ['bool', 'data'=>'string', '&w_decrypted_data'=>'string', 'public_key'=>'string|resource', 'padding='=>'int'],
      'new' => ['bool', 'data'=>'string', '&w_decrypted_data'=>'string', 'public_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'padding='=>'int'],
    ],
    'openssl_public_encrypt' => [
      'old' => ['bool', 'data'=>'string', '&w_encrypted_data'=>'string', 'public_key'=>'string|resource', 'padding='=>'int'],
      'new' => ['bool', 'data'=>'string', '&w_encrypted_data'=>'string', 'public_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'padding='=>'int'],
    ],
    'openssl_seal' => [
      'old' => ['int|false', 'data'=>'string', '&w_sealed_data'=>'string', '&w_encrypted_keys'=>'array', 'public_key'=>'array', 'cipher_algo='=>'string', '&rw_iv='=>'string'],
      'new' => ['int|false', 'data'=>'string', '&w_sealed_data'=>'string', '&w_encrypted_keys'=>'array', 'public_key'=>'list<OpenSSLAsymmetricKey>', 'cipher_algo'=>'string', '&rw_iv='=>'string'],
    ],
    'openssl_sign' => [
      'old' => ['bool', 'data'=>'string', '&w_signature'=>'string', 'private_key'=>'resource|string', 'algorithm='=>'int|string'],
      'new' => ['bool', 'data'=>'string', '&w_signature'=>'string', 'private_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'algorithm='=>'int|string'],
    ],
    'openssl_spki_new' => [
      'old' => ['?string', 'private_key'=>'resource', 'challenge'=>'string', 'digest_algo='=>'int'],
      'new' => ['string|false', 'private_key'=>'OpenSSLAsymmetricKey', 'challenge'=>'string', 'digest_algo='=>'int'],
    ],
    'openssl_verify' => [
      'old' => ['-1|0|1', 'data'=>'string', 'signature'=>'string', 'public_key'=>'resource|string', 'algorithm='=>'int|string'],
      'new' => ['-1|0|1|false', 'data'=>'string', 'signature'=>'string', 'public_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'algorithm='=>'int|string'],
    ],
    'openssl_x509_check_private_key' => [
      'old' => ['bool', 'certificate'=>'string|resource', 'private_key'=>'string|resource|array'],
      'new' => ['bool', 'certificate'=>'OpenSSLCertificate|string', 'private_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string'],
    ],
    'openssl_x509_checkpurpose' => [
      'old' => ['bool|int', 'certificate'=>'string|resource', 'purpose'=>'int', 'ca_info='=>'array', 'untrusted_certificates_file='=>'string'],
      'new' => ['bool|int', 'certificate'=>'OpenSSLCertificate|string', 'purpose'=>'int', 'ca_info='=>'array', 'untrusted_certificates_file='=>'string|null'],
    ],
    'openssl_x509_export' => [
      'old' => ['bool', 'certificate'=>'string|resource', '&w_output'=>'string', 'no_text='=>'bool'],
      'new' => ['bool', 'certificate'=>'OpenSSLCertificate|string', '&w_output'=>'string', 'no_text='=>'bool'],
    ],
    'openssl_x509_export_to_file' => [
      'old' =>  ['bool', 'certificate'=>'string|resource', 'output_filename'=>'string', 'no_text='=>'bool'],
      'new' => ['bool', 'certificate'=>'OpenSSLCertificate|string', 'output_filename'=>'string', 'no_text='=>'bool'],
    ],
    'openssl_x509_fingerprint' => [
      'old' => ['string|false', 'certificate'=>'string|resource', 'digest_algo='=>'string', 'binary='=>'bool'],
      'new' => ['string|false', 'certificate'=>'OpenSSLCertificate|string', 'digest_algo='=>'string', 'binary='=>'bool'],
    ],
    'openssl_x509_free' => [
      'old' => ['void', 'certificate'=>'resource'],
      'new' => ['void', 'certificate'=>'OpenSSLCertificate'],
    ],
    'openssl_x509_parse' => [
      'old' => ['array|false', 'certificate'=>'string|resource', 'short_names='=>'bool'],
      'new' => ['array|false', 'certificate'=>'OpenSSLCertificate|string', 'short_names='=>'bool'],
    ],
    'openssl_x509_read' => [
      'old' => ['resource|false', 'certificate'=>'string|resource'],
      'new' => ['OpenSSLCertificate|false', 'certificate'=>'OpenSSLCertificate|string'],
    ],
    'openssl_x509_verify' => [
      'old' => ['int', 'certificate'=>'string|resource', 'public_key'=>'string|array|resource'],
      'new' => ['int', 'certificate'=>'string|OpenSSLCertificate', 'public_key'=>'string|OpenSSLCertificate|OpenSSLAsymmetricKey|array'],
    ],
    'parse_str' => [
      'old' => ['void', 'string'=>'string', '&w_result='=>'array'],
      'new' => ['void', 'string'=>'string', '&w_result'=>'array'],
    ],
    'password_hash' => [
      'old' => ['string|false', 'password'=>'string', 'algo'=>'int|string|null', 'options='=>'array'],
      'new' => ['string', 'password'=>'string', 'algo'=>'int|string|null', 'options='=>'array'],
    ],
    'proc_get_status' => [
      'old' => ['array{command: string, pid: int, running: bool, signaled: bool, stopped: bool, exitcode: int, termsig: int, stopsig: int}|false', 'process'=>'resource'],
      'new' => ['array{command: string, pid: int, running: bool, signaled: bool, stopped: bool, exitcode: int, termsig: int, stopsig: int}', 'process'=>'resource'],
    ],
    'session_set_cookie_params' => [
      'old' => ['bool', 'lifetime'=>'int', 'path='=>'string', 'domain='=>'string', 'secure='=>'bool', 'httponly='=>'bool'],
      'new' => ['bool', 'lifetime'=>'int', 'path='=>'?string', 'domain='=>'?string', 'secure='=>'?bool', 'httponly='=>'?bool'],
    ],
    'socket_accept' => [
      'old' => ['resource|false', 'socket'=>'resource'],
      'new' => ['Socket|false', 'socket'=>'Socket'],
    ],
    'socket_addrinfo_bind' => [
      'old' => ['?resource', 'addrinfo'=>'resource'],
      'new' => ['Socket|false', 'address'=>'AddressInfo'],
    ],
    'socket_addrinfo_connect' => [
      'old' => ['resource', 'addrinfo'=>'resource'],
      'new' => ['Socket|false', 'address'=>'AddressInfo'],
    ],
    'socket_addrinfo_explain' => [
      'old' => ['array', 'addrinfo'=>'resource'],
      'new' => ['array', 'address'=>'AddressInfo'],
    ],
    'socket_addrinfo_lookup' => [
      'old' => ['resource[]', 'node'=>'string', 'service='=>'mixed', 'hints='=>'array'],
      'new' => ['false|AddressInfo[]', 'host='=>'string|null', 'service='=>'mixed', 'hints='=>'array'],
    ],
    'socket_bind' => [
      'old' => ['bool', 'socket'=>'resource', 'addr'=>'string', 'port='=>'int'],
      'new' => ['bool', 'socket'=>'Socket', 'addr'=>'string', 'port='=>'int'],
    ],
    'socket_clear_error' => [
      'old' => ['void', 'socket='=>'resource'],
      'new' => ['void', 'socket='=>'Socket'],
    ],
    'socket_close' => [
      'old' => ['void', 'socket'=>'resource'],
      'new' => ['void', 'socket'=>'Socket'],
    ],
    'socket_connect' => [
      'old' => ['bool', 'socket'=>'resource', 'addr'=>'string', 'port='=>'int'],
      'new' => ['bool', 'socket'=>'Socket', 'addr'=>'string', 'port='=>'int'],
    ],
    'socket_create' => [
      'old' => ['resource|false', 'domain'=>'int', 'type'=>'int', 'protocol'=>'int'],
      'new' => ['Socket|false', 'domain'=>'int', 'type'=>'int', 'protocol'=>'int'],
    ],
    'socket_create_listen' => [
      'old' => ['resource|false', 'port'=>'int', 'backlog='=>'int'],
      'new' => ['Socket|false', 'port'=>'int', 'backlog='=>'int'],
    ],
    'socket_create_pair' => [
      'old' => ['bool', 'domain'=>'int', 'type'=>'int', 'protocol'=>'int', '&w_fd'=>'resource[]'],
      'new' => ['bool', 'domain'=>'int', 'type'=>'int', 'protocol'=>'int', '&w_fd'=>'Socket[]'],
    ],
    'socket_export_stream' => [
      'old' => ['resource|false', 'socket'=>'resource'],
      'new' => ['resource|false', 'socket'=>'Socket'],
    ],
    'socket_get_option' => [
      'old' => ['mixed|false', 'socket'=>'resource', 'level'=>'int', 'optname'=>'int'],
      'new' => ['mixed|false', 'socket'=>'Socket', 'level'=>'int', 'optname'=>'int'],
    ],
    'socket_get_status' => [
      'old' => ['array', 'stream'=>'resource'],
      'new' => ['array', 'stream'=>'Socket'],
    ],
    'socket_getopt' => [
      'old' => ['mixed', 'socket'=>'resource', 'level'=>'int', 'optname'=>'int'],
      'new' => ['mixed', 'socket'=>'Socket', 'level'=>'int', 'optname'=>'int'],
    ],
    'socket_getpeername' => [
      'old' => ['bool', 'socket'=>'resource', '&w_addr'=>'string', '&w_port='=>'int'],
      'new' => ['bool', 'socket'=>'Socket', '&w_addr'=>'string', '&w_port='=>'int'],
    ],
    'socket_getsockname' => [
      'old' => ['bool', 'socket'=>'resource', '&w_addr'=>'string', '&w_port='=>'int'],
      'new' => ['bool', 'socket'=>'Socket', '&w_addr'=>'string', '&w_port='=>'int'],
    ],
    'socket_import_stream' => [
      'old' => ['resource|false|null', 'stream'=>'resource'],
      'new' => ['Socket|false|null', 'stream'=>'resource'],
    ],
    'socket_last_error' => [
      'old' => ['int', 'socket='=>'resource'],
      'new' => ['int', 'socket='=>'Socket'],
    ],
    'socket_listen' => [
      'old' => ['bool', 'socket'=>'resource', 'backlog='=>'int'],
      'new' => ['bool', 'socket'=>'Socket', 'backlog='=>'int'],
    ],
    'socket_read' => [
      'old' => ['string|false', 'socket'=>'resource', 'length'=>'int', 'type='=>'int'],
      'new' => ['string|false', 'socket'=>'Socket', 'length'=>'int', 'type='=>'int'],
    ],
    'socket_recv' => [
      'old' => ['int|false', 'socket'=>'resource', '&w_buf'=>'string', 'length'=>'int', 'flags'=>'int'],
      'new' => ['int|false', 'socket'=>'Socket', '&w_buf'=>'string', 'length'=>'int', 'flags'=>'int'],
    ],
    'socket_recvfrom' => [
      'old' => ['int|false', 'socket'=>'resource', '&w_buf'=>'string', 'length'=>'int', 'flags'=>'int', '&w_name'=>'string', '&w_port='=>'int'],
      'new' => ['int|false', 'socket'=>'Socket', '&w_buf'=>'string', 'length'=>'int', 'flags'=>'int', '&w_name'=>'string', '&w_port='=>'int'],
    ],
    'socket_recvmsg' => [
      'old' => ['int|false', 'socket'=>'resource', '&w_message'=>'string', 'flags='=>'int'],
      'new' => ['int|false', 'socket'=>'Socket', '&w_message'=>'string', 'flags='=>'int'],
    ],
    'socket_select' => [
      'old' => ['int|false', '&rw_read_fds'=>'resource[]|null', '&rw_write_fds'=>'resource[]|null', '&rw_except_fds'=>'resource[]|null', 'tv_sec'=>'int|null', 'tv_usec='=>'int'],
      'new' => ['int|false', '&rw_read_fds'=>'Socket[]|null', '&rw_write_fds'=>'Socket[]|null', '&rw_except_fds'=>'Socket[]|null', 'tv_sec'=>'int|null', 'tv_usec='=>'int'],
    ],
    'socket_send' => [
      'old' => ['int|false', 'socket'=>'resource', 'buf'=>'string', 'length'=>'int', 'flags'=>'int'],
      'new' => ['int|false', 'socket'=>'Socket', 'buf'=>'string', 'length'=>'int', 'flags'=>'int'],
    ],
    'socket_sendmsg' => [
      'old' => ['int|false', 'socket'=>'resource', 'message'=>'array', 'flags'=>'int'],
      'new' => ['int|false', 'socket'=>'Socket', 'message'=>'array', 'flags'=>'int'],
    ],
    'socket_sendto' => [
      'old' => ['int|false', 'socket'=>'resource', 'buf'=>'string', 'length'=>'int', 'flags'=>'int', 'addr'=>'string', 'port='=>'int'],
      'new' => ['int|false', 'socket'=>'Socket', 'buf'=>'string', 'length'=>'int', 'flags'=>'int', 'addr'=>'string', 'port='=>'int'],
    ],
    'socket_set_block' => [
      'old' => ['bool', 'socket'=>'resource'],
      'new' => ['bool', 'socket'=>'Socket'],
    ],
    'socket_set_blocking' => [
      'old' => ['bool', 'socket'=>'resource', 'mode'=>'int'],
      'new' => ['bool', 'socket'=>'Socket', 'mode'=>'int'],
    ],
    'socket_set_nonblock' => [
      'old' => ['bool', 'socket'=>'resource'],
      'new' => ['bool', 'socket'=>'Socket'],
    ],
    'socket_set_option' => [
      'old' => ['bool', 'socket'=>'resource', 'level'=>'int', 'optname'=>'int', 'optval'=>'int|string|array'],
      'new' => ['bool', 'socket'=>'Socket', 'level'=>'int', 'optname'=>'int', 'optval'=>'int|string|array'],
    ],
    'socket_set_timeout' => [
      'old' => ['bool', 'stream'=>'resource', 'seconds'=>'int', 'microseconds='=>'int'],
      'new' => ['bool', 'stream'=>'resource', 'seconds'=>'int', 'microseconds='=>'int'],
    ],
    'socket_setopt' => [
      'old' => ['void', 'socket'=>'resource', 'level'=>'int', 'optname'=>'int', 'optval'=>'int|string|array'],
      'new' => ['void', 'socket'=>'Socket', 'level'=>'int', 'optname'=>'int', 'optval'=>'int|string|array'],
    ],
    'socket_shutdown' => [
      'old' => ['bool', 'socket'=>'resource', 'how='=>'int'],
      'new' => ['bool', 'socket'=>'Socket', 'how='=>'int'],
    ],
    'socket_strerror' => [
      'old' => ['string', 'errno'=>'int'],
      'new' => ['string', 'errno'=>'int'],
    ],
    'socket_write' => [
      'old' => ['int|false', 'socket'=>'resource', 'data'=>'string', 'length='=>'int'],
      'new' => ['int|false', 'socket'=>'Socket', 'data'=>'string', 'length='=>'int|null'],
    ],
    'socket_wsaprotocol_info_export' => [
      'old' => ['string|false', 'socket'=>'resource', 'process_id'=>'int'],
      'new' => ['string|false', 'socket'=>'Socket', 'process_id'=>'int'],
    ],
    'socket_wsaprotocol_info_import' => [
      'old' => ['resource|false', 'info_id'=>'string'],
      'new' => ['Socket|false', 'info_id'=>'string'],
    ],
    'strchr' => [
      'old' => ['string|false', 'haystack'=>'string', 'needle'=>'string|int', 'before_needle='=>'bool'],
      'new' => ['string|false', 'haystack'=>'string', 'needle'=>'string', 'before_needle='=>'bool'],
    ],
    'strftime' => [
      'old' => ['string|false', 'format'=>'string', 'timestamp='=>'int'],
      'new' => ['string|false', 'format'=>'string', 'timestamp='=>'?int'],
    ],
    'stripos' => [
      'old' => ['int|false', 'haystack'=>'string', 'needle'=>'string|int', 'offset='=>'int'],
      'new' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int'],
    ],
    'stristr' => [
      'old' => ['string|false', 'haystack'=>'string', 'needle'=>'string|int', 'before_needle='=>'bool'],
      'new' => ['string|false', 'haystack'=>'string', 'needle'=>'string', 'before_needle='=>'bool'],
    ],
    'strpos' => [
      'old' => ['int|false', 'haystack'=>'string', 'needle'=>'string|int', 'offset='=>'int'],
      'new' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int'],
    ],
    'strrchr' => [
      'old' => ['string|false', 'haystack'=>'string', 'needle'=>'string|int'],
      'new' => ['string|false', 'haystack'=>'string', 'needle'=>'string'],
    ],
    'strripos' => [
      'old' => ['int|false', 'haystack'=>'string', 'needle'=>'string|int', 'offset='=>'int'],
      'new' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int'],
    ],
    'strrpos' => [
      'old' => ['int|false', 'haystack'=>'string', 'needle'=>'string|int', 'offset='=>'int'],
      'new' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int'],
    ],
    'strstr' => [
      'old' => ['string|false', 'haystack'=>'string', 'needle'=>'string|int', 'before_needle='=>'bool'],
      'new' => ['string|false', 'haystack'=>'string', 'needle'=>'string', 'before_needle='=>'bool'],
    ],
    'timezone_identifiers_list' => [
      'old' => ['list<string>|false', 'timezoneGroup='=>'int', 'countryCode='=>'?string'],
      'new' => ['list<string>', 'timezoneGroup='=>'int', 'countryCode='=>'?string'],
    ],
    'xml_get_current_byte_index' => [
      'old' => ['int|false', 'parser'=>'resource'],
      'new' => ['int|false', 'parser'=>'XMLParser'],
    ],
    'xml_get_current_column_number' => [
      'old' => ['int|false', 'parser'=>'resource'],
      'new' => ['int|false', 'parser'=>'XMLParser'],
    ],
    'xml_get_current_line_number' => [
      'old' => ['int|false', 'parser'=>'resource'],
      'new' => ['int|false', 'parser'=>'XMLParser'],
    ],
    'xml_get_error_code' => [
      'old' => ['int|false', 'parser'=>'resource'],
      'new' => ['int|false', 'parser'=>'XMLParser'],
    ],
    'xml_parse' => [
      'old' => ['int', 'parser'=>'resource', 'data'=>'string', 'is_final='=>'bool'],
      'new' => ['int', 'parser'=>'XMLParser', 'data'=>'string', 'is_final='=>'bool'],
    ],
    'xml_parse_into_struct' => [
      'old' => ['int', 'parser'=>'resource', 'data'=>'string', '&w_values'=>'array', '&w_index='=>'array'],
      'new' => ['int', 'parser'=>'XMLParser', 'data'=>'string', '&w_values'=>'array', '&w_index='=>'array'],
    ],
    'xml_parser_create' => [
      'old' => ['resource', 'encoding='=>'string'],
      'new' => ['XMLParser', 'encoding='=>'string'],
    ],
    'xml_parser_create_ns' => [
      'old' => ['resource', 'encoding='=>'string', 'separator='=>'string'],
      'new' => ['XMLParser', 'encoding='=>'string', 'separator='=>'string'],
    ],
    'xml_parser_free' => [
      'old' => ['bool', 'parser'=>'resource'],
      'new' => ['bool', 'parser'=>'XMLParser'],
    ],
    'xml_parser_get_option' => [
      'old' => ['mixed|false', 'parser'=>'resource', 'option'=>'int'],
      'new' => ['mixed|false', 'parser'=>'XMLParser', 'option'=>'int'],
    ],
    'xml_parser_set_option' => [
      'old' => ['bool', 'parser'=>'resource', 'option'=>'int', 'value'=>'mixed'],
      'new' => ['bool', 'parser'=>'XMLParser', 'option'=>'int', 'value'=>'mixed'],
    ],
    'xml_set_character_data_handler' => [
      'old' => ['bool', 'parser'=>'resource', 'handler'=>'callable'],
      'new' => ['bool', 'parser'=>'XMLParser', 'handler'=>'callable'],
    ],
    'xml_set_default_handler' => [
      'old' => ['bool', 'parser'=>'resource', 'handler'=>'callable'],
      'new' => ['bool', 'parser'=>'XMLParser', 'handler'=>'callable'],
    ],
    'xml_set_element_handler' => [
      'old' => ['bool', 'parser'=>'resource', 'start_handler'=>'callable', 'end_handler'=>'callable'],
      'new' => ['bool', 'parser'=>'XMLParser', 'start_handler'=>'callable', 'end_handler'=>'callable'],
    ],
    'xml_set_end_namespace_decl_handler' => [
      'old' => ['bool', 'parser'=>'resource', 'handler'=>'callable'],
      'new' => ['bool', 'parser'=>'XMLParser', 'handler'=>'callable'],
    ],
    'xml_set_external_entity_ref_handler' => [
      'old' => ['bool', 'parser'=>'resource', 'handler'=>'callable'],
      'new' => ['bool', 'parser'=>'XMLParser', 'handler'=>'callable'],
    ],
    'xml_set_notation_decl_handler' => [
      'old' => ['bool', 'parser'=>'resource', 'handler'=>'callable'],
      'new' => ['bool', 'parser'=>'XMLParser', 'handler'=>'callable'],
    ],
    'xml_set_object' => [
      'old' => ['bool', 'parser'=>'resource', 'object'=>'object'],
      'new' => ['bool', 'parser'=>'XMLParser', 'object'=>'object'],
    ],
    'xml_set_processing_instruction_handler' => [
      'old' => ['bool', 'parser'=>'resource', 'handler'=>'callable'],
      'new' => ['bool', 'parser'=>'XMLParser', 'handler'=>'callable'],
    ],
    'xml_set_start_namespace_decl_handler' => [
      'old' => ['bool', 'parser'=>'resource', 'handler'=>'callable'],
      'new' => ['bool', 'parser'=>'XMLParser', 'handler'=>'callable'],
    ],
    'xml_set_unparsed_entity_decl_handler' => [
      'old' => ['bool', 'parser'=>'resource', 'handler'=>'callable'],
      'new' => ['bool', 'parser'=>'XMLParser', 'handler'=>'callable'],
    ],
    'xmlwriter_end_attribute' => [
      'old' => ['bool', 'writer'=>'resource'],
      'new' => ['bool', 'writer'=>'XMLWriter'],
    ],
    'xmlwriter_end_cdata' => [
      'old' => ['bool', 'writer'=>'resource'],
      'new' => ['bool', 'writer'=>'XMLWriter'],
    ],
    'xmlwriter_end_comment' => [
      'old' => ['bool', 'writer'=>'resource'],
      'new' => ['bool', 'writer'=>'XMLWriter'],
    ],
    'xmlwriter_end_document' => [
      'old' => ['bool', 'writer'=>'resource'],
      'new' => ['bool', 'writer'=>'XMLWriter'],
    ],
    'xmlwriter_end_dtd' => [
      'old' => ['bool', 'writer'=>'resource'],
      'new' => ['bool', 'writer'=>'XMLWriter'],
    ],
    'xmlwriter_end_dtd_attlist' => [
      'old' => ['bool', 'writer'=>'resource'],
      'new' => ['bool', 'writer'=>'XMLWriter'],
    ],
    'xmlwriter_end_dtd_element' => [
      'old' => ['bool', 'writer'=>'resource'],
      'new' => ['bool', 'writer'=>'XMLWriter'],
    ],
    'xmlwriter_end_dtd_entity' => [
      'old' => ['bool', 'writer'=>'resource'],
      'new' => ['bool', 'writer'=>'XMLWriter'],
    ],
    'xmlwriter_end_element' => [
      'old' => ['bool', 'writer'=>'resource'],
      'new' => ['bool', 'writer'=>'XMLWriter'],
    ],
    'xmlwriter_end_pi' => [
      'old' => ['bool', 'writer'=>'resource'],
      'new' => ['bool', 'writer'=>'XMLWriter'],
    ],
    'xmlwriter_flush' => [
      'old' => ['string|int|false', 'writer'=>'resource', 'empty='=>'bool'],
      'new' => ['string|int', 'writer'=>'XMLWriter', 'empty='=>'bool'],
    ],
    'xmlwriter_full_end_element' => [
      'old' => ['bool', 'writer'=>'resource'],
      'new' => ['bool', 'writer'=>'XMLWriter'],
    ],
    'xmlwriter_open_memory' => [
      'old' => ['resource|false'],
      'new' => ['XMLWriter|false'],
    ],
    'xmlwriter_open_uri' => [
      'old' => ['resource|false', 'uri'=>'string'],
      'new' => ['XMLWriter|false', 'uri'=>'string'],
    ],
    'xmlwriter_output_memory' => [
      'old' => ['string', 'writer'=>'resource', 'flush='=>'bool'],
      'new' => ['string', 'writer'=>'XMLWriter', 'flush='=>'bool'],
    ],
    'xmlwriter_set_indent' => [
      'old' => ['bool', 'writer'=>'resource', 'enable'=>'bool'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'enable'=>'bool'],
    ],
    'xmlwriter_set_indent_string' => [
      'old' => ['bool', 'writer'=>'resource', 'indentation'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'indentation'=>'string'],
    ],
    'xmlwriter_start_attribute' => [
      'old' => ['bool', 'writer'=>'resource', 'name'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'name'=>'string'],
    ],
    'xmlwriter_start_attribute_ns' => [
      'old' => ['bool', 'writer'=>'resource', 'prefix'=>'string', 'name'=>'string', 'namespace'=>'?string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'prefix'=>'?string', 'name'=>'string', 'namespace'=>'?string'],
    ],
    'xmlwriter_start_cdata' => [
      'old' => ['bool', 'writer'=>'resource'],
      'new' => ['bool', 'writer'=>'XMLWriter'],
    ],
    'xmlwriter_start_comment' => [
      'old' => ['bool', 'writer'=>'resource'],
      'new' => ['bool', 'writer'=>'XMLWriter'],
    ],
    'xmlwriter_start_document' => [
      'old' => ['bool', 'writer'=>'resource', 'version='=>'?string', 'encoding='=>'?string', 'standalone='=>'?string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'version='=>'?string', 'encoding='=>'?string', 'standalone='=>'?string'],
    ],
    'xmlwriter_start_dtd' => [
      'old' => ['bool', 'writer'=>'resource', 'qualifiedName'=>'string', 'publicId='=>'?string', 'systemId='=>'?string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'qualifiedName'=>'string', 'publicId='=>'?string', 'systemId='=>'?string'],
    ],
    'xmlwriter_start_dtd_attlist' => [
      'old' => ['bool', 'writer'=>'resource', 'name'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'name'=>'string'],
    ],
    'xmlwriter_start_dtd_element' => [
      'old' => ['bool', 'writer'=>'resource', 'qualifiedName'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'qualifiedName'=>'string'],
    ],
    'xmlwriter_start_dtd_entity' => [
      'old' => ['bool', 'writer'=>'resource', 'name'=>'string', 'isParam'=>'bool'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'name'=>'string', 'isParam'=>'bool'],
    ],
    'xmlwriter_start_element' => [
      'old' => ['bool', 'writer'=>'resource', 'name'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'name'=>'string'],
    ],
    'xmlwriter_start_element_ns' => [
      'old' => ['bool', 'writer'=>'resource', 'prefix'=>'?string', 'name'=>'string', 'namespace'=>'?string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'prefix'=>'?string', 'name'=>'string', 'namespace'=>'?string'],
    ],
    'xmlwriter_start_pi' => [
      'old' => ['bool', 'writer'=>'resource', 'target'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'target'=>'string'],
    ],
    'xmlwriter_text' => [
      'old' => ['bool', 'writer'=>'resource', 'content'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'content'=>'string'],
    ],
    'xmlwriter_write_attribute' => [
      'old' => ['bool', 'writer'=>'resource', 'name'=>'string', 'value'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'name'=>'string', 'value'=>'string'],
    ],
    'xmlwriter_write_attribute_ns' => [
      'old' => ['bool', 'writer'=>'resource', 'prefix'=>'string', 'name'=>'string', 'namespace'=>'?string', 'value'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'prefix'=>'?string', 'name'=>'string', 'namespace'=>'?string', 'value'=>'string'],
    ],
    'xmlwriter_write_cdata' => [
      'old' => ['bool', 'writer'=>'resource', 'content'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'content'=>'string'],
    ],
    'xmlwriter_write_comment' => [
      'old' => ['bool', 'writer'=>'resource', 'content'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'content'=>'string'],
    ],
    'xmlwriter_write_dtd' => [
      'old' => ['bool', 'writer'=>'resource', 'name'=>'string', 'publicId='=>'?string', 'systemId='=>'?string', 'content='=>'?string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'name'=>'string', 'publicId='=>'?string', 'systemId='=>'?string', 'content='=>'?string'],
    ],
    'xmlwriter_write_dtd_attlist' => [
      'old' => ['bool', 'writer'=>'resource', 'name'=>'string', 'content'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'name'=>'string', 'content'=>'string'],
    ],
    'xmlwriter_write_dtd_element' => [
      'old' => ['bool', 'writer'=>'resource', 'name'=>'string', 'content'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'name'=>'string', 'content'=>'string'],
    ],
    'xmlwriter_write_dtd_entity' => [
      'old' => ['bool', 'writer'=>'resource', 'name'=>'string', 'content'=>'string', 'isParam'=>'bool', 'publicId'=>'string', 'systemId'=>'string', 'notationData'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'name'=>'string', 'content'=>'string', 'isParam='=>'bool', 'publicId='=>'?string', 'systemId='=>'?string', 'notationData='=>'?string'],
    ],
    'xmlwriter_write_element' => [
      'old' => ['bool', 'writer'=>'resource', 'name'=>'string', 'content'=>'?string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'name'=>'string', 'content='=>'?string'],
    ],
    'xmlwriter_write_element_ns' => [
      'old' => ['bool', 'writer'=>'resource', 'prefix'=>'?string', 'name'=>'string', 'namespace'=>'string', 'content'=>'?string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'prefix'=>'?string', 'name'=>'string', 'namespace'=>'?string', 'content='=>'?string'],
    ],
    'xmlwriter_write_pi' => [
      'old' => ['bool', 'writer'=>'resource', 'target'=>'string', 'content'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'target'=>'string', 'content'=>'string'],
    ],
    'xmlwriter_write_raw' => [
      'old' => ['bool', 'writer'=>'resource', 'content'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'content'=>'string'],
    ],
  ],
  'removed' => [
    'PDOStatement::setFetchMode\'1' => ['bool', 'fetch_column'=>'int', 'colno'=>'int'],
    'PDOStatement::setFetchMode\'2' => ['bool', 'fetch_class'=>'int', 'classname'=>'string', 'ctorargs'=>'array'],
    'PDOStatement::setFetchMode\'3' => ['bool', 'fetch_into'=>'int', 'object'=>'object'],
    'ReflectionType::isBuiltin' => ['bool'],
    'SplFileObject::fgetss' => ['string|false', 'allowable_tags='=>'string'],
    'create_function' => ['string', 'args'=>'string', 'code'=>'string'],
    'each' => ['array{0:int|string,key:int|string,1:mixed,value:mixed}', '&r_arr'=>'array'],
    'gmp_random' => ['GMP', 'limiter='=>'int'],
    'gzgetss' => ['string|false', 'zp'=>'resource', 'length'=>'int', 'allowable_tags='=>'string'],
    'image2wbmp' => ['bool', 'im'=>'resource', 'filename='=>'?string', 'threshold='=>'int'],
    'jpeg2wbmp' => ['bool', 'jpegname'=>'string', 'wbmpname'=>'string', 'dest_height'=>'int', 'dest_width'=>'int', 'threshold'=>'int'],
    'ldap_control_paged_result' => ['bool', 'link_identifier'=>'resource', 'pagesize'=>'int', 'iscritical='=>'bool', 'cookie='=>'string'],
    'ldap_control_paged_result_response' => ['bool', 'link_identifier'=>'resource', 'result_identifier'=>'resource', '&w_cookie'=>'string', '&w_estimated'=>'int'],
    'ldap_sort' => ['bool', 'link_identifier'=>'resource', 'result_identifier'=>'resource', 'sortfilter'=>'string'],
    'number_format\'1' => ['string', 'num'=>'float|int', 'decimals'=>'int', 'decimal_separator'=>'?string', 'thousands_separator'=>'?string'],
    'png2wbmp' => ['bool', 'pngname'=>'string', 'wbmpname'=>'string', 'dest_height'=>'int', 'dest_width'=>'int', 'threshold'=>'int'],
    'read_exif_data' => ['array', 'filename'=>'string', 'sections_needed='=>'string', 'sub_arrays='=>'bool', 'read_thumbnail='=>'bool'],
    'SimpleXMLIterator::rewind' => ['void'],
    'SimpleXMLIterator::valid' => ['bool'],
    'SimpleXMLIterator::current' => ['?SimpleXMLIterator'],
    'SimpleXMLIterator::key' => ['string|false'],
    'SimpleXMLIterator::next' => ['void'],
    'SimpleXMLIterator::hasChildren' => ['bool'],
    'SimpleXMLIterator::getChildren' => ['SimpleXMLIterator'],
  ],
];
