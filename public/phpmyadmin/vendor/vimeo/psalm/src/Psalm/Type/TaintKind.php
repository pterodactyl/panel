<?php

namespace Psalm\Type;

/**
 * An Enum class holding all the taint types that Psalm recognises
 */
class TaintKind
{
    public const INPUT_CALLABLE = 'callable';
    public const INPUT_UNSERIALIZE = 'unserialize';
    public const INPUT_INCLUDE = 'include';
    public const INPUT_EVAL = 'eval';
    public const INPUT_LDAP = 'ldap';
    public const INPUT_SQL = 'sql';
    public const INPUT_HTML = 'html';
    public const INPUT_HAS_QUOTES = 'has_quotes';
    public const INPUT_SHELL = 'shell';
    public const INPUT_SSRF = 'ssrf';
    public const INPUT_FILE = 'file';
    public const INPUT_COOKIE = 'cookie';
    public const INPUT_HEADER = 'header';
    public const USER_SECRET = 'user_secret';
    public const SYSTEM_SECRET = 'system_secret';
}
