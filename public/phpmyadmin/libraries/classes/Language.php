<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use function _bindtextdomain;
use function _setlocale;
use function _textdomain;
use function addcslashes;
use function function_exists;
use function in_array;
use function preg_match;
use function setlocale;
use function str_contains;
use function str_replace;
use function strcmp;

/**
 * Language object
 */
class Language
{
    /** @var string */
    protected $code;

    /** @var string */
    protected $name;

    /** @var string */
    protected $native;

    /** @var string */
    protected $regex;

    /** @var string */
    protected $mysql;

    /**
     * Constructs the Language object
     *
     * @param string $code   Language code
     * @param string $name   English name
     * @param string $native Native name
     * @param string $regex  Match regular expression
     * @param string $mysql  MySQL locale code
     */
    public function __construct($code, $name, $native, $regex, $mysql)
    {
        $this->code = $code;
        $this->name = $name;
        $this->native = $native;
        if (! str_contains($regex, '[-_]')) {
            $regex = str_replace('|', '([-_][[:alpha:]]{2,3})?|', $regex);
        }

        $this->regex = $regex;
        $this->mysql = $mysql;
    }

    /**
     * Returns native name for language
     *
     * @return string
     */
    public function getNativeName()
    {
        return $this->native;
    }

    /**
     * Returns English name for language
     *
     * @return string
     */
    public function getEnglishName()
    {
        return $this->name;
    }

    /**
     * Returns verbose name for language
     *
     * @return string
     */
    public function getName()
    {
        if (! empty($this->native)) {
            return $this->native . ' - ' . $this->name;
        }

        return $this->name;
    }

    /**
     * Returns language code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Returns MySQL locale code, can be empty
     *
     * @return string
     */
    public function getMySQLLocale()
    {
        return $this->mysql;
    }

    /**
     * Compare function used for sorting
     *
     * @param Language $other Other object to compare
     *
     * @return int same as strcmp
     */
    public function cmp(Language $other): int
    {
        return strcmp($this->name, $other->name);
    }

    /**
     * Checks whether language is currently active.
     */
    public function isActive(): bool
    {
        return $GLOBALS['lang'] == $this->code;
    }

    /**
     * Checks whether language matches HTTP header Accept-Language.
     *
     * @param string $header Header content
     */
    public function matchesAcceptLanguage($header): bool
    {
        $pattern = '/^('
            . addcslashes($this->regex, '/')
            . ')(;q=[0-9]\\.[0-9])?$/i';

        return (bool) preg_match($pattern, $header);
    }

    /**
     * Checks whether language matches HTTP header User-Agent
     *
     * @param string $header Header content
     */
    public function matchesUserAgent($header): bool
    {
        $pattern = '/(\(|\[|;[[:space:]])('
            . addcslashes($this->regex, '/')
            . ')(;|\]|\))/i';

        return (bool) preg_match($pattern, $header);
    }

    /**
     * Checks whether language is RTL
     */
    public function isRTL(): bool
    {
        return in_array($this->code, ['ar', 'fa', 'he', 'ur']);
    }

    /**
     * Activates given translation
     */
    public function activate(): void
    {
        $GLOBALS['lang'] = $this->code;

        // Set locale
        _setlocale(0, $this->code);
        _bindtextdomain('phpmyadmin', LOCALE_PATH);
        _textdomain('phpmyadmin');
        // Set PHP locale as well
        if (function_exists('setlocale')) {
            setlocale(0, $this->code);
        }

        /* Text direction for language */
        if ($this->isRTL()) {
            $GLOBALS['text_dir'] = 'rtl';
        } else {
            $GLOBALS['text_dir'] = 'ltr';
        }

        /* Show possible warnings from langauge selection */
        LanguageManager::getInstance()->showWarnings();
    }
}
