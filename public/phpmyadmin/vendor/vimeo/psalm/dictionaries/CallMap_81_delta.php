<?php // phpcs:ignoreFile

/**
 * This contains the information needed to convert the function signatures for php 8.1 to php 8.0 (and vice versa)
 *
 * This file has three sections.
 * The 'added' section contains function/method names from FunctionSignatureMap (And alternates, if applicable) that do not exist in php 8.0
 * The 'removed' section contains the signatures that were removed in php 8.1
 * The 'changed' section contains functions for which the signature has changed for php 8.1.
 *     Each function in the 'changed' section has an 'old' and a 'new' section,
 *     representing the function as it was in PHP 8.0 and in PHP 8.1, respectively
 *
 * @see CallMap.php
 *
 * @phan-file-suppress PhanPluginMixedKeyNoKey (read by Phan when analyzing this file)
 */
return [
  'added' => [
    'array_is_list' => ['bool', 'array' => 'array'],
    'enum_exists' => ['bool', 'enum' => 'class-string', 'autoload=' => 'bool'],
    'fsync' => ['bool', 'stream' => 'resource'],
    'fdatasync' => ['bool', 'stream' => 'resource'],
    'imageavif' => ['bool', 'image'=>'GdImage', 'file='=>'resource|string|null', 'quality='=>'int', 'speed='=>'int'],
    'imagecreatefromavif' => ['false|GdImage', 'filename'=>'string'],
    'mysqli_fetch_column' => ['null|int|float|string|false', 'result'=>'mysqli_result', 'column='=>'int'],
    'mysqli_result::fetch_column' => ['null|int|float|string|false', 'column='=>'int'],
    'CURLStringFile::__construct' => ['void', 'data'=>'string', 'postname'=>'string', 'mime='=>'string'],
    'Fiber::__construct' => ['void', 'callback'=>'callable'],
    'Fiber::start' => ['mixed', '...args'=>'mixed'],
    'Fiber::resume' => ['mixed', 'value='=>'null|mixed'],
    'Fiber::throw' => ['mixed', 'exception'=>'Throwable'],
    'Fiber::isStarted' => ['bool'],
    'Fiber::isSuspended' => ['bool'],
    'Fiber::isRunning' => ['bool'],
    'Fiber::isTerminated' => ['bool'],
    'Fiber::getReturn' => ['mixed'],
    'Fiber::getCurrent' => ['?self'],
    'Fiber::suspend' => ['mixed', 'value='=>'null|mixed'],
    'FiberError::__construct' => ['void'],
    'ReflectionClass::isEnum' => ['bool'],
    'ReflectionEnum::getBackingType' => ['?ReflectionType'],
    'ReflectionEnum::getCase' => ['ReflectionEnumUnitCase', 'name' => 'string'],
    'ReflectionEnum::getCases' => ['list<ReflectionEnumUnitCase>'],
    'ReflectionEnum::hasCase' => ['bool', 'name' => 'string'],
    'ReflectionEnum::isBacked' => ['bool'],
    'ReflectionEnumUnitCase::getEnum' => ['ReflectionEnum'],
    'ReflectionEnumUnitCase::getValue' => ['UnitEnum'],
    'ReflectionEnumBackedCase::getBackingValue' => ['string|int'],
    'ReflectionFunctionAbstract::getTentativeReturnType' => ['?ReflectionType'],
    'ReflectionFunctionAbstract::hasTentativeReturnType' => ['bool'],
    'ReflectionFunctionAbstract::isStatic' => ['bool'],
    'ReflectionObject::isEnum' => ['bool'],
  ],

  'changed' => [
    'finfo_buffer' => [
       'old' => ['string|false', 'finfo'=>'resource', 'string'=>'string', 'flags='=>'int', 'context='=>'resource'],
       'new' => ['string|false', 'finfo'=>'finfo', 'string'=>'string', 'flags='=>'int', 'context='=>'resource'],
    ],
    'finfo_close' => [
        'old' => ['bool', 'finfo'=>'resource'],
        'new' => ['bool', 'finfo'=>'finfo'],
    ],
    'finfo_file' => [
        'old' => ['string|false', 'finfo'=>'resource', 'filename'=>'string', 'flags='=>'int', 'context='=>'resource'],
        'new' => ['string|false', 'finfo'=>'finfo', 'filename'=>'string', 'flags='=>'int', 'context='=>'resource'],
    ],
    'finfo_open' => [
        'old' => ['resource|false', 'flags='=>'int', 'magic_database='=>'string'],
        'new' => ['finfo|false', 'flags='=>'int', 'magic_database='=>'string'],
    ],
    'finfo_set_flags' => [
        'old' => ['bool', 'finfo'=>'resource', 'flags'=>'int'],
        'new' => ['bool', 'finfo'=>'finfo', 'flags'=>'int'],
    ],
    'fputcsv' => [
        'old' => ['int|false', 'stream'=>'resource', 'fields'=>'array<array-key, null|scalar|Stringable>', 'separator='=>'string', 'enclosure='=>'string', 'escape='=>'string'],
        'new' => ['int|false', 'stream'=>'resource', 'fields'=>'array<array-key, null|scalar|Stringable>', 'separator='=>'string', 'enclosure='=>'string', 'escape='=>'string', 'eol='=>'string'],
    ],
    'ftp_connect' => [
      'old' => ['resource|false', 'hostname' => 'string', 'port=' => 'int', 'timeout=' => 'int'],
      'new' => ['FTP\Connection|false', 'hostname' => 'string', 'port=' => 'int', 'timeout=' => 'int'],
    ],
    'ftp_ssl_connect' => [
      'old' => ['resource|false', 'hostname' => 'string', 'port=' => 'int', 'timeout=' => 'int'],
      'new' => ['FTP\Connection|false', 'hostname' => 'string', 'port=' => 'int', 'timeout=' => 'int'],
    ],
    'ftp_login' => [
      'old' => ['bool', 'ftp' => 'resource', 'username' => 'string', 'password' => 'string'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'username' => 'string', 'password' => 'string'],
    ],
    'ftp_pwd' => [
      'old' => ['string|false', 'ftp' => 'resource'],
      'new' => ['string|false', 'ftp' => 'FTP\Connection'],
    ],
    'ftp_cdup' => [
      'old' => ['bool', 'ftp' => 'resource'],
      'new' => ['bool', 'ftp' => 'FTP\Connection'],
    ],
    'ftp_chdir' => [
      'old' => ['bool', 'ftp' => 'resource', 'directory' => 'string'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'directory' => 'string'],
    ],
    'ftp_exec' => [
      'old' => ['bool', 'ftp' => 'resource', 'command' => 'string'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'command' => 'string'],
    ],
    'ftp_raw' => [
      'old' => ['?array', 'ftp' => 'resource', 'command' => 'string'],
      'new' => ['?array', 'ftp' => 'FTP\Connection', 'command' => 'string'],
    ],
    'ftp_mkdir' => [
      'old' => ['string|false', 'ftp' => 'resource', 'directory' => 'string'],
      'new' => ['string|false', 'ftp' => 'FTP\Connection', 'directory' => 'string'],
    ],
    'ftp_rmdir' => [
      'old' => ['bool', 'ftp' => 'resource', 'directory' => 'string'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'directory' => 'string'],
    ],
    'ftp_chmod' => [
      'old' => ['int|false', 'ftp' => 'resource', 'permissions' => 'int', 'filename' => 'string'],
      'new' => ['int|false', 'ftp' => 'FTP\Connection', 'permissions' => 'int', 'filename' => 'string'],
    ],
    'ftp_alloc' => [
      'old' => ['bool', 'ftp' => 'resource', 'size' => 'int', '&w_response=' => 'string'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'size' => 'int', '&w_response=' => 'string'],
    ],
    'ftp_nlist' => [
      'old' => ['array|false', 'ftp' => 'resource', 'directory' => 'string'],
      'new' => ['array|false', 'ftp' => 'FTP\Connection', 'directory' => 'string'],
    ],
    'ftp_rawlist' => [
      'old' => ['array|false', 'ftp' => 'resource', 'directory' => 'string', 'recursive=' => 'bool'],
      'new' => ['array|false', 'ftp' => 'FTP\Connection', 'directory' => 'string', 'recursive=' => 'bool'],
    ],
    'ftp_mlsd' => [
      'old' => ['array|false', 'ftp' => 'resource', 'directory' => 'string'],
      'new' => ['array|false', 'ftp' => 'FTP\Connection', 'directory' => 'string'],
    ],
    'ftp_systype' => [
      'old' => ['string|false', 'ftp' => 'resource'],
      'new' => ['string|false', 'ftp' => 'FTP\Connection'],
    ],
    'ftp_fget' => [
      'old' => ['bool', 'ftp' => 'resource', 'stream' => 'resource', 'remote_filename' => 'string', 'mode=' => 'int', 'offset=' => 'int'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'stream' => 'resource', 'remote_filename' => 'string', 'mode=' => 'int', 'offset=' => 'int'],
    ],
    'ftp_nb_fget' => [
      'old' => ['int', 'ftp' => 'resource', 'stream' => 'resource', 'remote_filename' => 'string', 'mode=' => 'int', 'offset=' => 'int'],
      'new' => ['int', 'ftp' => 'FTP\Connection', 'stream' => 'resource', 'remote_filename' => 'string', 'mode=' => 'int', 'offset=' => 'int'],
    ],
    'ftp_pasv' => [
      'old' => ['bool', 'ftp' => 'resource', 'enable' => 'bool'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'enable' => 'bool'],
    ],
    'ftp_get' => [
      'old' => ['bool', 'ftp' => 'resource', 'local_filename' => 'string', 'remote_filename' => 'string', 'mode=' => 'int', 'offset=' => 'int'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'local_filename' => 'string', 'remote_filename' => 'string', 'mode=' => 'int', 'offset=' => 'int'],
    ],
    'ftp_nb_get' => [
      'old' => ['int', 'ftp' => 'resource', 'local_filename' => 'string', 'remote_filename' => 'string', 'mode=' => 'int', 'offset=' => 'int'],
      'new' => ['int', 'ftp' => 'FTP\Connection', 'local_filename' => 'string', 'remote_filename' => 'string', 'mode=' => 'int', 'offset=' => 'int'],
    ],
    'ftp_nb_continue' => [
      'old' => ['int', 'ftp' => 'resource'],
      'new' => ['int', 'ftp' => 'FTP\Connection'],
    ],
    'ftp_fput' => [
      'old' => ['bool', 'ftp' => 'resource', 'remote_filename' => 'string', 'stream' => 'resource', 'mode=' => 'int', 'offset=' => 'int'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'remote_filename' => 'string', 'stream' => 'resource', 'mode=' => 'int', 'offset=' => 'int'],
    ],
    'ftp_nb_fput' => [
      'old' => ['int', 'ftp' => 'resource', 'remote_filename' => 'string', 'stream' => 'resource', 'mode=' => 'int', 'offset=' => 'int'],
      'new' => ['int', 'ftp' => 'FTP\Connection', 'remote_filename' => 'string', 'stream' => 'resource', 'mode=' => 'int', 'offset=' => 'int'],
    ],
    'ftp_put' => [
      'old' => ['bool', 'ftp' => 'resource', 'remote_filename' => 'string', 'local_filename' => 'string', 'mode=' => 'int', 'offset=' => 'int'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'remote_filename' => 'string', 'local_filename' => 'string', 'mode=' => 'int', 'offset=' => 'int'],
    ],
    'ftp_append' => [
      'old' => ['bool', 'ftp' => 'resource', 'remote_filename' => 'string', 'local_filename' => 'string', 'mode=' => 'int'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'remote_filename' => 'string', 'local_filename' => 'string', 'mode=' => 'int'],
    ],
    'ftp_nb_put' => [
      'old' => ['int', 'ftp' => 'resource', 'remote_filename' => 'string', 'local_filename' => 'string', 'mode=' => 'int', 'offset=' => 'int'],
      'new' => ['int', 'ftp' => 'FTP\Connection', 'remote_filename' => 'string', 'local_filename' => 'string', 'mode=' => 'int', 'offset=' => 'int'],
    ],
    'ftp_size' => [
      'old' => ['int', 'ftp' => 'resource', 'filename' => 'string'],
      'new' => ['int', 'ftp' => 'FTP\Connection', 'filename' => 'string'],
    ],
    'ftp_mdtm' => [
      'old' => ['int', 'ftp' => 'resource', 'filename' => 'string'],
      'new' => ['int', 'ftp' => 'FTP\Connection', 'filename' => 'string'],
    ],
    'ftp_rename' => [
      'old' => ['bool', 'ftp' => 'resource', 'from' => 'string', 'to' => 'string'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'from' => 'string', 'to' => 'string'],
    ],
    'ftp_delete' => [
      'old' => ['bool', 'ftp' => 'resource', 'filename' => 'string'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'filename' => 'string'],
    ],
    'ftp_site' => [
      'old' => ['bool', 'ftp' => 'resource', 'command' => 'string'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'command' => 'string'],
    ],
    'ftp_close' => [
      'old' => ['bool', 'ftp' => 'resource'],
      'new' => ['bool', 'ftp' => 'FTP\Connection'],
    ],
    'ftp_quit' => [
      'old' => ['bool', 'ftp' => 'resource'],
      'new' => ['bool', 'ftp' => 'FTP\Connection'],
    ],
    'ftp_set_option' => [
      'old' => ['bool', 'ftp' => 'resource', 'option' => 'int', 'value' => 'mixed'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'option' => 'int', 'value' => 'mixed'],
    ],
    'ftp_get_option' => [
      'old' => ['mixed|false', 'ftp' => 'resource', 'option' => 'int'],
      'new' => ['mixed|false', 'ftp' => 'FTP\Connection', 'option' => 'int'],
    ],
    'hash' => [
      'old' => ['non-empty-string', 'algo'=>'string', 'data'=>'string', 'binary='=>'bool'],
      'new' => ['non-empty-string', 'algo'=>'string', 'data'=>'string', 'binary='=>'bool', 'options='=>'array{seed:scalar}'],
    ],
    'hash_file' => [
      'old' => ['non-empty-string|false', 'algo'=>'string', 'filename'=>'string', 'binary='=>'bool'],
      'new' => ['non-empty-string|false', 'algo'=>'string', 'filename'=>'string', 'binary='=>'bool', 'options='=>'array{seed:scalar}'],
    ],
    'hash_init' => [
      'old' => ['HashContext', 'algo'=>'string', 'flags='=>'int', 'key='=>'string'],
      'new' => ['HashContext', 'algo'=>'string', 'flags='=>'int', 'key='=>'string', 'options='=>'array{seed:scalar}'],
    ],
    'imageinterlace' => [
      'old' => ['int|bool', 'image'=>'GdImage', 'enable='=>'bool|null'],
      'new' => ['bool', 'image'=>'GdImage', 'enable='=>'bool|null'],
    ],
    'imap_append' => [
        'old' => ['bool', 'imap'=>'resource', 'folder'=>'string', 'message'=>'string', 'options='=>'string', 'internal_date='=>'string'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'folder'=>'string', 'message'=>'string', 'options='=>'string', 'internal_date='=>'string'],
    ],
    'imap_body' => [
        'old' => ['string|false', 'imap'=>'resource', 'message_num'=>'int', 'flags='=>'int'],
        'new' => ['string|false', 'imap'=>'IMAP\Connection', 'message_num'=>'int', 'flags='=>'int'],
    ],
    'imap_bodystruct' => [
        'old' => ['stdClass|false', 'imap'=>'resource', 'message_num'=>'int', 'section'=>'string'],
        'new' => ['stdClass|false', 'imap'=>'IMAP\Connection', 'message_num'=>'int', 'section'=>'string'],
    ],
    'imap_check' => [
        'old' => ['stdClass|false', 'imap'=>'resource'],
        'new' => ['stdClass|false', 'imap'=>'IMAP\Connection'],
    ],
    'imap_clearflag_full' => [
        'old' => ['bool', 'imap'=>'resource', 'sequence'=>'string', 'flag'=>'string', 'options='=>'int'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'sequence'=>'string', 'flag'=>'string', 'options='=>'int'],
    ],
    'imap_close' => [
        'old' => ['bool', 'imap'=>'resource', 'flags='=>'int'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'flags='=>'int'],
    ],
    'imap_create' => [
        'old' => ['bool', 'imap'=>'resource', 'mailbox'=>'string'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'mailbox'=>'string'],
    ],
    'imap_createmailbox' => [
        'old' => ['bool', 'imap'=>'resource', 'mailbox'=>'string'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'mailbox'=>'string'],
    ],
    'imap_delete' => [
        'old' => ['bool', 'imap'=>'resource', 'message_nums'=>'string', 'flags='=>'int'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'message_nums'=>'string', 'flags='=>'int'],
    ],
    'imap_deletemailbox' => [
        'old' => ['bool', 'imap'=>'resource', 'mailbox'=>'string'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'mailbox'=>'string'],
    ],
    'imap_expunge' => [
        'old' => ['bool', 'imap'=>'resource'],
        'new' => ['bool', 'imap'=>'IMAP\Connection'],
    ],
    'imap_fetch_overview' => [
        'old' => ['array|false', 'imap'=>'resource', 'sequence'=>'string', 'flags='=>'int'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'sequence'=>'string', 'flags='=>'int'],
    ],
    'imap_fetchbody' => [
        'old' => ['string|false', 'imap'=>'resource', 'message_num'=>'int', 'section'=>'string', 'flags='=>'int'],
        'new' => ['string|false', 'imap'=>'IMAP\Connection', 'message_num'=>'int', 'section'=>'string', 'flags='=>'int'],
    ],
    'imap_fetchheader' => [
        'old' => ['string|false', 'imap'=>'resource', 'message_num'=>'int', 'flags='=>'int'],
        'new' => ['string|false', 'imap'=>'IMAP\Connection', 'message_num'=>'int', 'flags='=>'int'],
    ],
    'imap_fetchmime' => [
        'old' => ['string|false', 'imap'=>'resource', 'message_num'=>'int', 'section'=>'string', 'flags='=>'int'],
        'new' => ['string|false', 'imap'=>'IMAP\Connection', 'message_num'=>'int', 'section'=>'string', 'flags='=>'int'],
    ],
    'imap_fetchstructure' => [
        'old' => ['stdClass|false', 'imap'=>'resource', 'message_num'=>'int', 'flags='=>'int'],
        'new' => ['stdClass|false', 'imap'=>'IMAP\Connection', 'message_num'=>'int', 'flags='=>'int'],
    ],
    'imap_fetchtext' => [
        'old' => ['string|false', 'imap'=>'resource', 'message_num'=>'int', 'flags='=>'int'],
        'new' => ['string|false', 'imap'=>'IMAP\Connection', 'message_num'=>'int', 'flags='=>'int'],
    ],
    'imap_gc' => [
        'old' => ['bool', 'imap'=>'resource', 'flags'=>'int'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'flags'=>'int'],
    ],
    'imap_get_quota' => [
        'old' => ['array|false', 'imap'=>'resource', 'quota_root'=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'quota_root'=>'string'],
    ],
    'imap_get_quotaroot' => [
        'old' => ['array|false', 'imap'=>'resource', 'mailbox'=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'mailbox'=>'string'],
    ],
    'imap_getacl' => [
        'old' => ['array|false', 'imap'=>'resource', 'mailbox'=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'mailbox'=>'string'],
    ],
    'imap_getmailboxes' => [
        'old' => ['array|false', 'imap'=>'resource', 'reference'=>'string', 'pattern'=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'reference'=>'string', 'pattern'=>'string'],
    ],
    'imap_getsubscribed' => [
        'old' => ['array|false', 'imap'=>'resource', 'reference'=>'string', 'pattern'=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'reference'=>'string', 'pattern'=>'string'],
    ],
    'imap_headerinfo' => [
        'old' => ['stdClass|false', 'imap'=>'resource', 'message_num'=>'int', 'from_length='=>'int', 'subject_length='=>'int', 'default_host='=>'string|null'],
        'new' => ['stdClass|false', 'imap'=>'IMAP\Connection', 'message_num'=>'int', 'from_length='=>'int', 'subject_length='=>'int', 'default_host='=>'string|null'],
    ],
    'imap_headers' => [
        'old' => ['array|false', 'imap'=>'resource'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection'],
    ],
    'imap_list' => [
        'old' => ['array|false', 'imap'=>'resource', 'reference'=>'string', 'pattern'=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'reference'=>'string', 'pattern'=>'string'],
    ],
    'imap_listmailbox' => [
        'old' => ['array|false', 'imap'=>'resource', 'reference'=>'string', 'pattern'=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'reference'=>'string', 'pattern'=>'string'],
    ],
    'imap_listscan' => [
        'old' => ['array|false', 'imap'=>'resource', 'reference'=>'string', 'pattern'=>'string', 'content'=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'reference'=>'string', 'pattern'=>'string', 'content'=>'string'],
    ],
    'imap_listsubscribed' => [
        'old' => ['array|false', 'imap'=>'resource', 'reference'=>'string', 'pattern'=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'reference'=>'string', 'pattern'=>'string'],
    ],
    'imap_lsub' => [
        'old' => ['array|false', 'imap'=>'resource', 'reference'=>'string', 'pattern'=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'reference'=>'string', 'pattern'=>'string'],
    ],
    'imap_mail_copy' => [
        'old' => ['bool', 'imap'=>'resource', 'message_nums'=>'string', 'mailbox'=>'string', 'flags='=>'int'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'message_nums'=>'string', 'mailbox'=>'string', 'flags='=>'int'],
    ],
    'imap_mail_move' => [
        'old' => ['bool', 'imap'=>'resource', 'message_nums'=>'string', 'mailbox'=>'string', 'flags='=>'int'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'message_nums'=>'string', 'mailbox'=>'string', 'flags='=>'int'],
    ],
    'imap_mailboxmsginfo' => [
        'old' => ['stdClass|false', 'imap'=>'resource'],
        'new' => ['stdClass|false', 'imap'=>'IMAP\Connection'],
    ],
    'imap_msgno' => [
        'old' => ['int|false', 'imap'=>'resource', 'message_uid'=>'int'],
        'new' => ['int|false', 'imap'=>'IMAP\Connection', 'message_uid'=>'int'],
    ],
    'imap_num_msg' => [
        'old' => ['int|false', 'imap'=>'resource'],
        'new' => ['int|false', 'imap'=>'IMAP\Connection'],
    ],
    'imap_num_recent' => [
        'old' => ['int|false', 'imap'=>'resource'],
        'new' => ['int|false', 'imap'=>'IMAP\Connection'],
    ],
    'imap_open' => [
        'old' => ['resource|false', 'mailbox'=>'string', 'user'=>'string', 'password'=>'string', 'flags='=>'int', 'retries='=>'int', 'options='=>'?array'],
        'new' => ['IMAP\Connection|false', 'mailbox'=>'string', 'user'=>'string', 'password'=>'string', 'flags='=>'int', 'retries='=>'int', 'options='=>'?array'],
    ],
    'imap_ping' => [
        'old' => ['bool', 'imap'=>'resource'],
        'new' => ['bool', 'imap'=>'IMAP\Connection'],
    ],
    'imap_rename' => [
        'old' => ['bool', 'imap'=>'resource', 'from'=>'string', 'to'=>'string'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'from'=>'string', 'to'=>'string'],
    ],
    'imap_renamemailbox' => [
        'old' => ['bool', 'imap'=>'resource', 'from'=>'string', 'to'=>'string'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'from'=>'string', 'to'=>'string'],
    ],
    'imap_reopen' => [
        'old' => ['bool', 'imap'=>'resource', 'mailbox'=>'string', 'flags='=>'int', 'retries='=>'int'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'mailbox'=>'string', 'flags='=>'int', 'retries='=>'int'],
    ],
    'imap_savebody' => [
        'old' => ['bool', 'imap'=>'resource', 'file'=>'string|resource', 'message_num'=>'int', 'section='=>'string', 'flags='=>'int'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'file'=>'string|resource', 'message_num'=>'int', 'section='=>'string', 'flags='=>'int'],
    ],
    'imap_scan' => [
        'old' => ['array|false', 'imap'=>'resource', 'reference'=>'string', 'pattern'=>'string', 'content'=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'reference'=>'string', 'pattern'=>'string', 'content'=>'string'],
    ],
    'imap_scanmailbox' => [
        'old' => ['array|false', 'imap'=>'resource', 'reference'=>'string', 'pattern'=>'string', 'content'=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'reference'=>'string', 'pattern'=>'string', 'content'=>'string'],
    ],
    'imap_search' => [
        'old' => ['array|false', 'imap'=>'resource', 'criteria'=>'string', 'flags='=>'int', 'charset='=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'criteria'=>'string', 'flags='=>'int', 'charset='=>'string'],
    ],
    'imap_set_quota' => [
        'old' => ['bool', 'imap'=>'resource', 'quota_root'=>'string', 'mailbox_size'=>'int'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'quota_root'=>'string', 'mailbox_size'=>'int'],
    ],
    'imap_setacl' => [
        'old' => ['bool', 'imap'=>'resource', 'mailbox'=>'string', 'user_id'=>'string', 'rights'=>'string'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'mailbox'=>'string', 'user_id'=>'string', 'rights'=>'string'],
    ],
    'imap_setflag_full' => [
        'old' => ['bool', 'imap'=>'resource', 'sequence'=>'string', 'flag'=>'string', 'options='=>'int'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'sequence'=>'string', 'flag'=>'string', 'options='=>'int'],
    ],
    'imap_sort' => [
        'old' => ['array|false', 'imap'=>'resource', 'criteria'=>'int', 'reverse'=>'int', 'flags='=>'int', 'search_criteria='=>'string', 'charset='=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'criteria'=>'int', 'reverse'=>'int', 'flags='=>'int', 'search_criteria='=>'string', 'charset='=>'string'],
    ],
    'imap_status' => [
        'old' => ['stdClass|false', 'imap'=>'resource', 'mailbox'=>'string', 'flags'=>'int'],
        'new' => ['stdClass|false', 'imap'=>'IMAP\Connection', 'mailbox'=>'string', 'flags'=>'int'],
    ],
    'imap_subscribe' => [
        'old' => ['bool', 'imap'=>'resource', 'mailbox'=>'string'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'mailbox'=>'string'],
    ],
    'imap_thread' => [
        'old' => ['array|false', 'imap'=>'resource', 'flags='=>'int'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'flags='=>'int'],
    ],
    'imap_uid' => [
        'old' => ['int|false', 'imap'=>'resource', 'message_num'=>'int'],
        'new' => ['int|false', 'imap'=>'IMAP\Connection', 'message_num'=>'int'],
    ],
    'imap_undelete' => [
        'old' => ['bool', 'imap'=>'resource', 'message_nums'=>'string', 'flags='=>'int'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'message_nums'=>'string', 'flags='=>'int'],
    ],
    'imap_unsubscribe' => [
        'old' => ['bool', 'imap'=>'resource', 'mailbox'=>'string'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'mailbox'=>'string'],
    ],
    'ini_set' => [
        'old' => ['string|false', 'option'=>'string', 'value'=>'string'],
        'new' => ['string|false', 'option'=>'string', 'value'=>'string|int|float|bool|null'],
    ],
    'ldap_add' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'entry'=>'array', 'controls='=>'?array'],
    ],
    'ldap_add_ext' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
      'new' => ['LDAP\Result|false', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'entry'=>'array', 'controls='=>'?array'],
    ],
    'ldap_bind' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn='=>'string|null', 'password='=>'string|null'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'dn='=>'string|null', 'password='=>'string|null'],
    ],
    'ldap_bind_ext' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'dn='=>'string|null', 'password='=>'string|null', 'controls='=>'array'],
      'new' => ['LDAP\Result|false', 'ldap'=>'LDAP\Connection', 'dn='=>'string|null', 'password='=>'string|null', 'controls='=>'?array'],
    ],
    'ldap_close' => [
      'old' => ['bool', 'ldap'=>'resource'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection'],
    ],
    'ldap_compare' => [
      'old' => ['bool|int', 'ldap'=>'resource', 'dn'=>'string', 'attribute'=>'string', 'value'=>'string'],
      'new' => ['bool|int', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'attribute'=>'string', 'value'=>'string', 'controls='=>'?array'],
    ],
    'ldap_connect' => [
      'old' => ['resource|false', 'uri='=>'string', 'port='=>'int', 'wallet='=>'string', 'password='=>'string', 'auth_mode='=>'int'],
      'new' => ['LDAP\Connection|false', 'uri='=>'string', 'port='=>'int', 'wallet='=>'string', 'password='=>'string', 'auth_mode='=>'int'],
    ],
    'ldap_count_entries' => [
      'old' => ['int|false', 'ldap'=>'resource', 'result'=>'resource'],
      'new' => ['int|false', 'ldap'=>'LDAP\Connection', 'result'=>'LDAP\Result'],
    ],
    'ldap_delete' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'controls='=>'?array'],
    ],
    'ldap_delete_ext' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'dn'=>'string', 'controls='=>'array'],
      'new' => ['LDAP\Result|false', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'controls='=>'?array'],
    ],
    'ldap_errno' => [
      'old' => ['int', 'ldap'=>'resource'],
      'new' => ['int', 'ldap'=>'LDAP\Connection'],
    ],
    'ldap_error' => [
      'old' => ['string', 'ldap'=>'resource'],
      'new' => ['string', 'ldap'=>'LDAP\Connection'],
    ],
    'ldap_exop' => [
      'old' => ['mixed', 'ldap'=>'resource', 'reqoid'=>'string', 'reqdata='=>'string', 'serverctrls='=>'array|null', '&w_response_data='=>'string', '&w_response_oid='=>'string'],
      'new' => ['mixed', 'ldap'=>'LDAP\Connection', 'request_oid'=>'string', 'request_data='=>'string', 'controls='=>'?array', '&w_response_data='=>'string', '&w_response_oid='=>'string'],
    ],
    'ldap_exop_passwd' => [
      'old' => ['bool|string', 'ldap'=>'resource', 'user='=>'string', 'old_password='=>'string', 'new_password='=>'string', '&w_controls='=>'array|null'],
      'new' => ['bool|string', 'ldap'=>'LDAP\Connection', 'user='=>'string', 'old_password='=>'string', 'new_password='=>'string', '&w_controls='=>'array|null'],
    ],
    'ldap_exop_refresh' => [
      'old' => ['int|false', 'ldap'=>'resource', 'dn'=>'string', 'ttl'=>'int'],
      'new' => ['int|false', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'ttl'=>'int'],
    ],
    'ldap_exop_whoami' => [
      'old' => ['string|false', 'ldap'=>'resource'],
      'new' => ['string|false', 'ldap'=>'LDAP\Connection'],
    ],
    'ldap_first_attribute' => [
      'old' => ['string|false', 'ldap'=>'resource', 'entry'=>'resource'],
      'new' => ['string|false', 'ldap'=>'LDAP\Connection', 'entry'=>'LDAP\ResultEntry'],
    ],
    'ldap_first_entry' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'result'=>'resource'],
      'new' => ['LDAP\ResultEntry|false', 'ldap'=>'LDAP\Connection', 'result'=>'LDAP\Result'],
    ],
    'ldap_first_reference' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'result'=>'resource'],
      'new' => ['LDAP\ResultEntry|false', 'ldap'=>'LDAP\Connection', 'result'=>'LDAP\Result'],
    ],
    'ldap_free_result' => [
      'old' => ['bool', 'ldap'=>'resource'],
      'new' => ['bool', 'result'=>'LDAP\Result'],
    ],
    'ldap_get_attributes' => [
      'old' => ['array|false', 'ldap'=>'resource', 'entry'=>'resource'],
      'new' => ['array|false', 'ldap'=>'LDAP\Connection', 'entry'=>'LDAP\ResultEntry'],
    ],
    'ldap_get_dn' => [
      'old' => ['string|false', 'ldap'=>'resource', 'entry'=>'resource'],
      'new' => ['string|false', 'ldap'=>'LDAP\Connection', 'entry'=>'LDAP\ResultEntry'],
    ],
    'ldap_get_entries' => [
      'old' => ['array|false', 'ldap'=>'resource', 'result'=>'resource'],
      'new' => ['array|false', 'ldap'=>'LDAP\Connection', 'result'=>'LDAP\Result'],
    ],
    'ldap_get_option' => [
      'old' => ['bool', 'ldap'=>'resource', 'option'=>'int', '&w_value'=>'mixed'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'option'=>'int', '&w_value='=>'array|string|int|null'],
    ],
    'ldap_get_values' => [
      'old' => ['array|false', 'ldap'=>'resource', 'entry'=>'resource', 'attribute'=>'string'],
      'new' => ['array|false', 'ldap'=>'LDAP\Connection', 'entry'=>'LDAP\ResultEntry', 'attribute'=>'string'],
    ],
    'ldap_get_values_len' => [
      'old' => ['array|false', 'ldap'=>'resource', 'entry'=>'resource', 'attribute'=>'string'],
      'new' => ['array|false', 'ldap'=>'LDAP\Connection', 'entry'=>'LDAP\ResultEntry', 'attribute'=>'string'],
    ],
    'ldap_list' => [
      'old' => ['resource|false', 'ldap'=>'resource|array', 'base'=>'string', 'filter'=>'string', 'attributes='=>'array', 'attributes_only='=>'int', 'sizelimit='=>'int', 'timelimit='=>'int', 'deref='=>'int'],
      'new' => ['LDAP\Result|LDAP\Result[]|false', 'ldap'=>'LDAP\Connection|LDAP\Connection[]', 'base'=>'string', 'filter'=>'string', 'attributes='=>'array', 'attributes_only='=>'int', 'sizelimit='=>'int', 'timelimit='=>'int', 'deref='=>'int', 'controls='=>'?array'],
    ],
    'ldap_mod_add' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'entry'=>'array', 'controls='=>'?array'],
    ],
    'ldap_mod_add_ext' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
      'new' => ['LDAP\Result|false', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'entry'=>'array', 'controls='=>'?array'],
    ],
    'ldap_mod_del' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'entry'=>'array', 'controls='=>'?array'],
    ],
    'ldap_mod_del_ext' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
      'new' => ['LDAP\Result|false', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'entry'=>'array', 'controls='=>'?array'],
    ],
    'ldap_mod_replace' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'entry'=>'array', 'controls='=>'?array'],
    ],
    'ldap_mod_replace_ext' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
      'new' => ['LDAP\Result|false', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'entry'=>'array', 'controls='=>'?array'],
    ],
    'ldap_modify' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'entry'=>'array', 'controls='=>'?array'],
    ],
    'ldap_modify_batch' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'modifications_info'=>'array'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'modifications_info'=>'array', 'controls='=>'?array'],
    ],
    'ldap_next_attribute' => [
      'old' => ['string|false', 'ldap'=>'resource', 'entry'=>'resource'],
      'new' => ['string|false', 'ldap'=>'LDAP\Connection', 'entry'=>'LDAP\ResultEntry'],
    ],
    'ldap_next_entry' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'result'=>'resource'],
      'new' => ['LDAP\ResultEntry|false', 'ldap'=>'LDAP\Connection', 'entry'=>'LDAP\ResultEntry'],
    ],
    'ldap_next_reference' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'entry'=>'resource'],
      'new' => ['LDAP\ResultEntry|false', 'ldap'=>'LDAP\Connection', 'entry'=>'LDAP\ResultEntry'],
    ],
    'ldap_parse_exop' => [
      'old' => ['bool', 'ldap'=>'resource', 'result'=>'resource', '&w_response_data='=>'string', '&w_response_oid='=>'string'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'result'=>'LDAP\Result', '&w_response_data='=>'string', '&w_response_oid='=>'string'],
    ],
    'ldap_parse_reference' => [
      'old' => ['bool', 'ldap'=>'resource', 'entry'=>'resource', 'referrals'=>'array'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'entry'=>'LDAP\ResultEntry', '&w_referrals'=>'array'],
    ],
    'ldap_parse_result' => [
      'old' => ['bool', 'ldap'=>'resource', 'result'=>'resource', '&w_error_code'=>'int', '&w_matched_dn='=>'string', '&w_error_message='=>'string', '&w_referrals='=>'array', '&w_controls='=>'array'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'result'=>'LDAP\Result', '&w_error_code'=>'int', '&w_matched_dn='=>'string', '&w_error_message='=>'string', '&w_referrals='=>'array', '&w_controls='=>'array'],
    ],
    'ldap_read' => [
      'old' => ['resource|false', 'ldap'=>'resource|array', 'base'=>'string', 'filter'=>'string', 'attributes='=>'array', 'attributes_only='=>'int', 'sizelimit='=>'int', 'timelimit='=>'int', 'deref='=>'int'],
      'new' => ['LDAP\Result|LDAP\Result[]|false', 'ldap'=>'LDAP\Connection|LDAP\Connection[]', 'base'=>'string', 'filter'=>'string', 'attributes='=>'array', 'attributes_only='=>'int', 'sizelimit='=>'int', 'timelimit='=>'int', 'deref='=>'int', 'controls='=>'?array'],
    ],
    'ldap_rename' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'new_rdn'=>'string', 'new_parent'=>'string', 'delete_old_rdn'=>'bool'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'new_rdn'=>'string', 'new_parent'=>'string', 'delete_old_rdn'=>'bool', 'controls='=>'?array'],
    ],
    'ldap_rename_ext' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'dn'=>'string', 'new_rdn'=>'string', 'new_parent'=>'string', 'delete_old_rdn'=>'bool', 'controls='=>'array'],
      'new' => ['LDAP\Result|false', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'new_rdn'=>'string', 'new_parent'=>'string', 'delete_old_rdn'=>'bool', 'controls='=>'?array'],
    ],
    'ldap_sasl_bind' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn='=>'string', 'password='=>'string', 'mech='=>'string', 'realm='=>'string', 'authc_id='=>'string', 'authz_id='=>'string', 'props='=>'string'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'dn='=>'string', 'password='=>'string', 'mech='=>'string', 'realm='=>'string', 'authc_id='=>'string', 'authz_id='=>'string', 'props='=>'string'],
    ],
    'ldap_search' => [
      'old' => ['resource|false', 'ldap'=>'resource|resource[]', 'base'=>'string', 'filter'=>'string', 'attributes='=>'array', 'attributes_only='=>'int', 'sizelimit='=>'int', 'timelimit='=>'int', 'deref='=>'int'],
      'new' => ['LDAP\Result|LDAP\Result[]|false', 'ldap'=>'LDAP\Connection|LDAP\Connection[]', 'base'=>'string', 'filter'=>'string', 'attributes='=>'array', 'attributes_only='=>'int', 'sizelimit='=>'int', 'timelimit='=>'int', 'deref='=>'int', 'controls='=>'?array'],
    ],
    'ldap_set_option' => [
      'old' => ['bool', 'ldap'=>'resource|null', 'option'=>'int', 'value'=>'mixed'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection|null', 'option'=>'int', 'value'=>'mixed'],
    ],
    'ldap_set_rebind_proc' => [
      'old' => ['bool', 'ldap'=>'resource', 'callback'=>'?callable'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'callback'=>'?callable'],
    ],
    'ldap_start_tls' => [
        'old' => ['bool', 'ldap'=>'resource'],
        'new' => ['bool', 'ldap'=>'LDAP\Connection'],
    ],
    'ldap_unbind' => [
      'old' => ['bool', 'ldap'=>'resource'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection'],
    ],
    'mysqli::connect' => [
      'old' => ['null|false', 'hostname='=>'string|null', 'username='=>'string|null', 'password='=>'string|null', 'database='=>'string|null', 'port='=>'int|null', 'socket='=>'string|null'],
      'new' => ['bool', 'hostname='=>'string|null', 'username='=>'string|null', 'password='=>'string|null', 'database='=>'string|null', 'port='=>'int|null', 'socket='=>'string|null'],
    ],
    'mysqli_execute' => [
      'old' => ['bool', 'statement' => 'mysqli_stmt'],
      'new' => ['bool', 'statement' => 'mysqli_stmt', 'params=' => 'list<mixed>|null'],
    ],
    'mysqli_stmt_execute' => [
      'old' => ['bool', 'statement' => 'mysqli_stmt'],
      'new' => ['bool', 'statement' => 'mysqli_stmt', 'params=' => 'list<mixed>|null'],
    ],
    'mysqli_stmt::execute' => [
      'old' => ['bool'],
      'new' => ['bool', 'params=' => 'list<mixed>|null'],
    ],
    'pg_affected_rows' => [
      'old' => ['int', 'result' => 'resource'],
      'new' => ['int', 'result' => '\PgSql\Result'],
    ],
    'pg_cancel_query' => [
      'old' => ['bool', 'connection' => 'resource'],
      'new' => ['bool', 'connection' => '\PgSql\Connection'],
    ],
    'pg_client_encoding' => [
      'old' => ['string', 'connection=' => 'resource'],
      'new' => ['string', 'connection=' => '\PgSql\Connection'],
    ],
    'pg_close' => [
      'old' => ['bool', 'connection=' => 'resource'],
      'new' => ['bool', 'connection=' => '\PgSql\Connection'],
    ],
    'pg_connect' => [
      'old' => ['resource|false', 'connection_string' => 'string', 'flags=' => 'int'],
      'new' => ['\PgSql\Connection|false', 'connection_string' => 'string', 'flags=' => 'int'],
    ],
    'pg_connect_poll' => [
      'old' => ['int', 'connection' => 'resource'],
      'new' => ['int', 'connection' => '\PgSql\Connection'],
    ],
    'pg_connection_busy' => [
      'old' => ['bool', 'connection' => 'resource'],
      'new' => ['bool', 'connection' => '\PgSql\Connection'],
    ],
    'pg_connection_reset' => [
      'old' => ['bool', 'connection' => 'resource'],
      'new' => ['bool', 'connection' => '\PgSql\Connection'],
    ],
    'pg_connection_status' => [
      'old' => ['int', 'connection' => 'resource'],
      'new' => ['int', 'connection' => '\PgSql\Connection'],
    ],
    'pg_consume_input' => [
      'old' => ['bool', 'connection' => 'resource'],
      'new' => ['bool', 'connection' => '\PgSql\Connection'],
    ],
    'pg_convert' => [
      'old' => ['array|false', 'connection' => 'resource', 'table_name' => 'string', 'values' => 'array', 'flags=' => 'int'],
      'new' => ['array|false', 'connection' => '\PgSql\Connection', 'table_name' => 'string', 'values' => 'array', 'flags=' => 'int'],
    ],
    'pg_copy_from' => [
      'old' => ['bool', 'connection' => 'resource', 'table_name' => 'string', 'rows' => 'array', 'separator=' => 'string', 'null_as=' => 'string'],
      'new' => ['bool', 'connection' => '\PgSql\Connection', 'table_name' => 'string', 'rows' => 'array', 'separator=' => 'string', 'null_as=' => 'string'],
    ],
    'pg_copy_to' => [
      'old' => ['array|false', 'connection' => 'resource', 'table_name' => 'string', 'separator=' => 'string', 'null_as=' => 'string'],
      'new' => ['array|false', 'connection' => '\PgSql\Connection', 'table_name' => 'string', 'separator=' => 'string', 'null_as=' => 'string'],
    ],
    'pg_dbname' => [
      'old' => ['string', 'connection=' => 'resource'],
      'new' => ['string', 'connection=' => '\PgSql\Connection'],
    ],
    'pg_delete' => [
      'old' => ['string|bool', 'connection' => 'resource', 'table_name' => 'string', 'conditions' => 'array', 'flags=' => 'int'],
      'new' => ['string|bool', 'connection' => '\PgSql\Connection', 'table_name' => 'string', 'conditions' => 'array', 'flags=' => 'int'],
    ],
    'pg_end_copy' => [
      'old' => ['bool', 'connection=' => 'resource'],
      'new' => ['bool', 'connection=' => '\PgSql\Connection'],
    ],
    'pg_escape_bytea' => [
      'old' => ['string', 'connection' => 'resource', 'string' => 'string'],
      'new' => ['string', 'connection' => '\PgSql\Connection', 'string' => 'string'],
    ],
    'pg_escape_identifier' => [
      'old' => ['string|false', 'connection' => 'resource', 'string' => 'string'],
      'new' => ['string|false', 'connection' => '\PgSql\Connection', 'string' => 'string'],
    ],
    'pg_escape_literal' => [
      'old' => ['string|false', 'connection' => 'resource', 'string' => 'string'],
      'new' => ['string|false', 'connection' => '\PgSql\Connection', 'string' => 'string'],
    ],
    'pg_escape_string' => [
      'old' => ['string', 'connection' => 'resource', 'string' => 'string'],
      'new' => ['string', 'connection' => '\PgSql\Connection', 'string' => 'string'],
    ],
    'pg_exec' => [
      'old' => ['resource|false', 'connection' => 'resource', 'query' => 'string'],
      'new' => ['\PgSql\Result|false', 'connection' => '\PgSql\Connection', 'query' => 'string'],
    ],
    'pg_execute' => [
      'old' => ['resource|false', 'connection' => 'resource', 'statement_name' => 'string', 'params' => 'array'],
      'new' => ['\PgSql\Result|false', 'connection' => '\PgSql\Connection', 'statement_name' => 'string', 'params' => 'array'],
    ],
    'pg_execute\'1' => [
      'old' => ['resource|false', 'connection' => 'string', 'statement_name' => 'array'],
      'new' => ['\PgSql\Result|false', 'connection' => 'string', 'statement_name' => 'array'],
    ],
    'pg_fetch_all' => [
      'old' => ['array<array>', 'result' => 'resource', 'result_type=' => 'int'],
      'new' => ['array<array>', 'result' => '\PgSql\Result', 'result_type=' => 'int'],
    ],
    'pg_fetch_all_columns' => [
      'old' => ['array', 'result' => 'resource', 'field=' => 'int'],
      'new' => ['array', 'result' => '\PgSql\Result', 'field=' => 'int'],
    ],
    'pg_fetch_array' => [
      'old' => ['array<string|null>|false', 'result' => 'resource', 'row=' => '?int', 'mode=' => 'int'],
      'new' => ['array<string|null>|false', 'result' => '\PgSql\Result', 'row=' => '?int', 'mode=' => 'int'],
    ],
    'pg_fetch_assoc' => [
      'old' => ['array<string, mixed>|false', 'result' => 'resource', 'row=' => '?int'],
      'new' => ['array<string, mixed>|false', 'result' => '\PgSql\Result', 'row=' => '?int'],
    ],
    'pg_fetch_object' => [
      'old' => ['object|false', 'result' => 'resource', 'row=' => '?int', 'class=' => 'string', 'constructor_args=' => 'array'],
      'new' => ['object|false', 'result' => '\PgSql\Result', 'row=' => '?int', 'class=' => 'string', 'constructor_args=' => 'array'],
    ],
    'pg_fetch_result' => [
      'old' => ['string|false|null', 'result' => 'resource', 'row' => 'string|int'],
      'new' => ['string|false|null', 'result' => '\PgSql\Result', 'row' => 'string|int'],
    ],
    'pg_fetch_result\'1' => [
      'old' => ['string|false|null', 'result' => 'resource', 'row' => '?int', 'field' => 'string|int'],
      'new' => ['string|false|null', 'result' => '\PgSql\Result', 'row' => '?int', 'field' => 'string|int'],
    ],
    'pg_fetch_row' => [
      'old' => ['array|false', 'result' => 'resource', 'row=' => '?int', 'mode=' => 'int'],
      'new' => ['array|false', 'result' => '\PgSql\Result', 'row=' => '?int', 'mode=' => 'int'],
    ],
    'pg_field_is_null' => [
      'old' => ['int|false', 'result' => 'resource', 'row'=>'string|int'],
      'new' => ['int|false', 'result' => '\PgSql\Result', 'row'=>'string|int'],
    ],
    'pg_field_is_null\'1' => [
      'old' => ['int|false', 'result' => 'resource', 'row' => 'int', 'field' => 'string|int'],
      'new' => ['int|false', 'result' => '\PgSql\Result', 'row' => 'int', 'field' => 'string|int'],
    ],
    'pg_field_name' => [
      'old' => ['string', 'result' => 'resource', 'field' => 'int'],
      'new' => ['string', 'result' => '\PgSql\Result', 'field' => 'int'],
    ],
    'pg_field_num' => [
      'old' => ['int', 'result' => 'resource', 'field' => 'string'],
      'new' => ['int', 'result' => '\PgSql\Result', 'field' => 'string'],
    ],
    'pg_field_prtlen' => [
      'old' => ['int|false', 'result' => 'resource', 'row' => 'string|int'],
      'new' => ['int|false', 'result' => '\PgSql\Result', 'row' => 'string|int'],
    ],
    'pg_field_prtlen\'1' => [
      'old' => ['int|false', 'result' => 'resource', 'row' => 'int', 'field' => 'string|int'],
      'new' => ['int|false', 'result' => '\PgSql\Result', 'row' => 'int', 'field' => 'string|int'],
    ],
    'pg_field_size' => [
      'old' => ['int', 'result' => 'resource', 'field' => 'int'],
      'new' => ['int', 'result' => '\PgSql\Result', 'field' => 'int'],
    ],
    'pg_field_table' => [
      'old' => ['string|int|false', 'result' => 'resource', 'field' => 'int', 'oid_only=' => 'bool'],
      'new' => ['string|int|false', 'result' => '\PgSql\Result', 'field' => 'int', 'oid_only=' => 'bool'],
    ],
    'pg_field_type' => [
      'old' => ['string', 'result' => 'resource', 'field' => 'int'],
      'new' => ['string', 'result' => '\PgSql\Result', 'field' => 'int'],
    ],
    'pg_field_type_oid' => [
      'old' => ['int|string', 'result' => 'resource', 'field' => 'int'],
      'new' => ['int|string', 'result' => '\PgSql\Result', 'field' => 'int'],
    ],
    'pg_flush' => [
      'old' => ['int|bool', 'connection' => 'resource'],
      'new' => ['int|bool', 'connection' => '\PgSql\Connection'],
    ],
    'pg_free_result' => [
      'old' => ['bool', 'result' => 'resource'],
      'new' => ['bool', 'result' => '\PgSql\Result'],
    ],
    'pg_get_notify' => [
      'old' => ['array|false', 'result' => 'resource', 'mode=' => 'int'],
      'new' => ['array|false', 'result' => '\PgSql\Result', 'mode=' => 'int'],
    ],
    'pg_get_pid' => [
      'old' => ['int', 'connection' => 'resource'],
      'new' => ['int', 'connection' => '\PgSql\Connection'],
    ],
    'pg_get_result' => [
      'old' => ['resource|false', 'connection=' => 'resource'],
      'new' => ['\PgSql\Result|false', 'connection=' => '\PgSql\Connection'],
    ],
    'pg_host' => [
      'old' => ['string', 'connection=' => 'resource'],
      'new' => ['string', 'connection=' => '\PgSql\Connection'],
    ],
    'pg_insert' => [
      'old' => ['resource|string|false', 'connection' => 'resource', 'table_name' => 'string', 'values' => 'array', 'flags=' => 'int'],
      'new' => ['\PgSql\Result|string|false', 'connection' => '\PgSql\Connection', 'table_name' => 'string', 'values' => 'array', 'flags=' => 'int'],
    ],
    'pg_last_error' => [
      'old' => ['string', 'connection=' => 'resource', 'operation=' => 'int'],
      'new' => ['string', 'connection=' => '\PgSql\Connection', 'operation=' => 'int'],
    ],
    'pg_last_notice' => [
      'old' => ['string|array|bool', 'connection' => 'resource', 'mode=' => 'int'],
      'new' => ['string|array|bool', 'connection' => '\PgSql\Connection', 'mode=' => 'int'],
    ],
    'pg_last_oid' => [
      'old' => ['string|int|false', 'result' => 'resource'],
      'new' => ['string|int|false', 'result' => '\PgSql\Result'],
    ],
    'pg_lo_close' => [
      'old' => ['bool', 'lob' => 'resource'],
      'new' => ['bool', 'lob' => '\PgSql\Lob'],
    ],
    'pg_lo_create' => [
      'old' => ['int|string|false', 'connection=' => 'resource', 'oid=' => 'int|string'],
      'new' => ['int|string|false', 'connection=' => '\PgSql\Connection', 'oid=' => 'int|string'],
    ],
    'pg_lo_export' => [
      'old' => ['bool', 'connection' => 'resource', 'oid' => 'int|string', 'filename' => 'string'],
      'new' => ['bool', 'connection' => '\PgSql\Connection', 'oid' => 'int|string', 'filename' => 'string'],
    ],
    'pg_lo_import' => [
      'old' => ['int|string|false', 'connection' => 'resource', 'filename' => 'string', 'oid' => 'string|int'],
      'new' => ['int|string|false', 'connection' => '\PgSql\Connection', 'filename' => 'string', 'oid' => 'string|int'],
    ],
    'pg_lo_open' => [
      'old' => ['resource|false', 'connection' => 'resource', 'oid' => 'int|string', 'mode' => 'string'],
      'new' => ['\PgSql\Lob|false', 'connection' => '\PgSql\Connection', 'oid' => 'int|string', 'mode' => 'string'],
    ],
    'pg_lo_open\'1' => [
      'old' => ['resource|false', 'connection' => 'int|string', 'oid' => 'string'],
      'new' => ['\PgSql\Lob|false', 'connection' => 'int|string', 'oid' => 'string'],
    ],
    'pg_lo_read' => [
      'old' => ['string|false', 'lob' => 'resource', 'length=' => 'int'],
      'new' => ['string|false', 'lob' => '\PgSql\Lob', 'length=' => 'int'],
    ],
    'pg_lo_read_all' => [
      'old' => ['int', 'lob' => 'resource'],
      'new' => ['int', 'lob' => '\PgSql\Lob'],
    ],
    'pg_lo_seek' => [
      'old' => ['bool', 'lob' => 'resource', 'offset' => 'int', 'whence=' => 'int'],
      'new' => ['bool', 'lob' => '\PgSql\Lob', 'offset' => 'int', 'whence=' => 'int'],
    ],
    'pg_lo_tell' => [
      'old' => ['int', 'lob' => 'resource'],
      'new' => ['int', 'lob' => '\PgSql\Lob'],
    ],
    'pg_lo_truncate' => [
      'old' => ['bool', 'lob' => 'resource', 'size' => 'int'],
      'new' => ['bool', 'lob' => '\PgSql\Lob', 'size' => 'int'],
    ],
    'pg_lo_unlink' => [
      'old' => ['bool', 'connection' => 'resource', 'oid' => 'int|string'],
      'new' => ['bool', 'connection' => '\PgSql\Connection', 'oid' => 'int|string'],
    ],
    'pg_lo_write' => [
      'old' => ['int|false', 'lob' => 'resource', 'data' => 'string', 'length=' => 'int'],
      'new' => ['int|false', 'lob' => '\PgSql\Lob', 'data' => 'string', 'length=' => 'int'],
    ],
    'pg_meta_data' => [
      'old' => ['array|false', 'connection' => 'resource', 'table_name' => 'string', 'extended=' => 'bool'],
      'new' => ['array|false', 'connection' => '\PgSql\Connection', 'table_name' => 'string', 'extended=' => 'bool'],
    ],
    'pg_num_fields' => [
      'old' => ['int', 'result' => 'resource'],
      'new' => ['int', 'result' => '\PgSql\Result'],
    ],
    'pg_num_rows' => [
      'old' => ['int', 'result' => 'resource'],
      'new' => ['int', 'result' => '\PgSql\Result'],
    ],
    'pg_options' => [
      'old' => ['string', 'connection=' => 'resource'],
      'new' => ['string', 'connection=' => '\PgSql\Connection'],
    ],
    'pg_parameter_status' => [
      'old' => ['string|false', 'connection' => 'resource', 'name' => 'string'],
      'new' => ['string|false', 'connection' => '\PgSql\Connection', 'name' => 'string'],
    ],
    'pg_pconnect' => [
      'old' => ['resource|false', 'connection_string' => 'string', 'flags=' => 'string', 'port=' => 'string|int', 'options=' => 'string', 'tty=' => 'string', 'database=' => 'string'],
      'new' => ['\PgSql\Connection|false', 'connection_string' => 'string', 'flags=' => 'string', 'port=' => 'string|int', 'options=' => 'string', 'tty=' => 'string', 'database=' => 'string'],
    ],
    'pg_ping' => [
      'old' => ['bool', 'connection=' => 'resource'],
      'new' => ['bool', 'connection=' => '\PgSql\Connection'],
    ],
    'pg_port' => [
      'old' => ['int', 'connection=' => 'resource'],
      'new' => ['int', 'connection=' => '\PgSql\Connection'],
    ],
    'pg_prepare' => [
      'old' => ['resource|false', 'connection' => 'resource', 'statement_name' => 'string', 'query' => 'string'],
      'new' => ['\PgSql\Result|false', 'connection' => '\PgSql\Connection', 'statement_name' => 'string', 'query' => 'string'],
    ],
    'pg_prepare\'1' => [
      'old' => ['resource|false', 'connection' => 'string', 'statement_name' => 'string'],
      'new' => ['\PgSql\Result|false', 'connection' => 'string', 'statement_name' => 'string'],
    ],
    'pg_put_line' => [
      'old' => ['bool', 'connection' => 'resource', 'data' => 'string'],
      'new' => ['bool', 'connection' => '\PgSql\Connection', 'data' => 'string'],
    ],
    'pg_query' => [
      'old' => ['resource|false', 'connection' => 'resource', 'query' => 'string'],
      'new' => ['\PgSql\Result|false', 'connection' => '\PgSql\Connection', 'query' => 'string'],
    ],
    'pg_query\'1' => [
      'old' => ['resource|false', 'connection' => 'string'],
      'new' => ['\PgSql\Result|false', 'connection' => 'string'],
    ],
    'pg_query_params' => [
      'old' => ['resource|false', 'connection' => 'resource', 'query' => 'string', 'params' => 'array'],
      'new' => ['\PgSql\Result|false', 'connection' => '\PgSql\Connection', 'query' => 'string', 'params' => 'array'],
    ],
    'pg_query_params\'1' => [
      'old' => ['resource|false', 'connection' => 'string', 'query' => 'array'],
      'new' => ['\PgSql\Result|false', 'connection' => 'string', 'query' => 'array'],
    ],
    'pg_result_error' => [
      'old' => ['string|false', 'result' => 'resource'],
      'new' => ['string|false', 'result' => '\PgSql\Result'],
    ],
    'pg_result_error_field' => [
      'old' => ['string|false|null', 'result' => 'resource', 'field_code' => 'int'],
      'new' => ['string|false|null', 'result' => '\PgSql\Result', 'field_code' => 'int'],
    ],
    'pg_result_seek' => [
      'old' => ['bool', 'result' => 'resource', 'row' => 'int'],
      'new' => ['bool', 'result' => '\PgSql\Result', 'row' => 'int'],
    ],
    'pg_result_status' => [
      'old' => ['string|int', 'result' => 'resource', 'mode=' => 'int'],
      'new' => ['string|int', 'result' => '\PgSql\Result', 'mode=' => 'int'],
    ],
    'pg_select' => [
      'old' => ['string|array|false', 'connection' => 'resource', 'table_name' => 'string', 'assoc_array' => 'array', 'options=' => 'int', 'result_type=' => 'int'],
      'new' => ['string|array|false', 'connection' => '\PgSql\Connection', 'table_name' => 'string', 'assoc_array' => 'array', 'options=' => 'int', 'result_type=' => 'int'],
    ],
    'pg_send_execute' => [
      'old' => ['bool|int', 'connection' => 'resource', 'query' => 'string', 'params' => 'array'],
      'new' => ['bool|int', 'connection' => '\PgSql\Connection', 'query' => 'string', 'params' => 'array'],
    ],
    'pg_send_prepare' => [
      'old' => ['bool|int', 'connection' => 'resource', 'statement_name' => 'string', 'query' => 'string'],
      'new' => ['bool|int', 'connection' => '\PgSql\Connection', 'statement_name' => 'string', 'query' => 'string'],
    ],
    'pg_send_query' => [
      'old' => ['bool|int', 'connection' => 'resource', 'query' => 'string'],
      'new' => ['bool|int', 'connection' => '\PgSql\Connection', 'query' => 'string'],
    ],
    'pg_send_query_params' => [
      'old' => ['bool|int', 'connection' => 'resource', 'query' => 'string', 'params' => 'array'],
      'new' => ['bool|int', 'connection' => '\PgSql\Connection', 'query' => 'string', 'params' => 'array'],
    ],
    'pg_set_client_encoding' => [
      'old' => ['int', 'connection' => 'resource', 'encoding' => 'string'],
      'new' => ['int', 'connection' => '\PgSql\Connection', 'encoding' => 'string'],
    ],
    'pg_set_error_verbosity' => [
      'old' => ['int|false', 'connection' => 'resource', 'verbosity' => 'int'],
      'new' => ['int|false', 'connection' => '\PgSql\Connection', 'verbosity' => 'int'],
    ],
    'pg_socket' => [
      'old' => ['resource|false', 'connection' => 'resource'],
      'new' => ['resource|false', 'connection' => '\PgSql\Connection'],
    ],
    'pg_trace' => [
      'old' => ['bool', 'filename' => 'string', 'mode=' => 'string', 'connection=' => 'resource'],
      'new' => ['bool', 'filename' => 'string', 'mode=' => 'string', 'connection=' => '\PgSql\Connection'],
    ],
    'pg_transaction_status' => [
      'old' => ['int', 'connection' => 'resource'],
      'new' => ['int', 'connection' => '\PgSql\Connection'],
    ],
    'pg_tty' => [
      'old' => ['string', 'connection=' => 'resource'],
      'new' => ['string', 'connection=' => '\PgSql\Connection'],
    ],
    'pg_untrace' => [
      'old' => ['bool', 'connection=' => 'resource'],
      'new' => ['bool', 'connection=' => '\PgSql\Connection'],
    ],
    'pg_update' => [
      'old' => ['string|bool', 'connection' => 'resource', 'table_name' => 'string', 'values' => 'array', 'conditions' => 'array', 'flags=' => 'int'],
      'new' => ['string|bool', 'connection' => '\PgSql\Connection', 'table_name' => 'string', 'values' => 'array', 'conditions' => 'array', 'flags=' => 'int'],
    ],
    'pg_version' => [
      'old' => ['array', 'connection=' => 'resource'],
      'new' => ['array', 'connection=' => '\PgSql\Connection'],
    ],
    'pspell_add_to_personal' => [
      'old' => ['bool', 'dictionary'=>'int', 'word'=>'string'],
      'new' => ['bool', 'dictionary'=>'PSpell\Dictionary', 'word'=>'string'],
    ],
    'pspell_add_to_session' => [
      'old' => ['bool', 'dictionary'=>'int', 'word'=>'string'],
      'new' => ['bool', 'dictionary'=>'PSpell\Dictionary', 'word'=>'string'],
    ],
    'pspell_check' => [
      'old' => ['bool', 'dictionary'=>'int', 'word'=>'string'],
      'new' => ['bool', 'dictionary'=>'PSpell\Dictionary', 'word'=>'string'],
    ],
    'pspell_clear_session' => [
      'old' => ['bool', 'dictionary'=>'int'],
      'new' => ['bool', 'dictionary'=>'PSpell\Dictionary'],
    ],
    'pspell_config_data_dir' => [
      'old' => ['bool', 'config'=>'int', 'directory'=>'string'],
      'new' => ['bool', 'config'=>'PSpell\Config', 'directory'=>'string'],
    ],
    'pspell_config_dict_dir' => [
      'old' => ['bool', 'config'=>'int', 'directory'=>'string'],
      'new' => ['bool', 'config'=>'PSpell\Config', 'directory'=>'string'],
    ],
    'pspell_config_ignore' => [
      'old' => ['bool', 'config'=>'int', 'min_length'=>'int'],
      'new' => ['bool', 'config'=>'PSpell\Config', 'min_length'=>'int'],
    ],
    'pspell_config_mode' => [
      'old' => ['bool', 'config'=>'int', 'mode'=>'int'],
      'new' => ['bool', 'config'=>'PSpell\Config', 'mode'=>'int'],
    ],
    'pspell_config_personal' => [
      'old' => ['bool', 'config'=>'int', 'filename'=>'string'],
      'new' => ['bool', 'config'=>'PSpell\Config', 'filename'=>'string'],
    ],
    'pspell_config_repl' => [
      'old' => ['bool', 'config'=>'int', 'filename'=>'string'],
      'new' => ['bool', 'config'=>'PSpell\Config', 'filename'=>'string'],
    ],
    'pspell_config_runtogether' => [
      'old' => ['bool', 'config'=>'int', 'allow'=>'bool'],
      'new' => ['bool', 'config'=>'PSpell\Config', 'allow'=>'bool'],
    ],
    'pspell_config_save_repl' => [
      'old' => ['bool', 'config'=>'int', 'save'=>'bool'],
      'new' => ['bool', 'config'=>'PSpell\Config', 'save'=>'bool'],
    ],
    'pspell_new_config' => [
      'old' => ['int|false', 'config'=>'int'],
      'new' => ['int|false', 'config'=>'PSpell\Config'],
    ],
    'pspell_save_wordlist' => [
      'old' => ['bool', 'dictionary'=>'int'],
      'new' => ['bool', 'dictionary'=>'PSpell\Dictionary'],
    ],
    'pspell_store_replacement' => [
      'old' => ['bool', 'dictionary'=>'int', 'misspelled'=>'string', 'correct'=>'string'],
      'new' => ['bool', 'dictionary'=>'PSpell\Dictionary', 'misspelled'=>'string', 'correct'=>'string'],
    ],
    'pspell_suggest' => [
      'old' => ['array', 'dictionary'=>'int', 'word'=>'string'],
      'new' => ['array', 'dictionary'=>'PSpell\Dictionary', 'word'=>'string'],
    ],
  ],

  'removed' => [
    'ReflectionMethod::isStatic' => ['bool'],
  ],
];
