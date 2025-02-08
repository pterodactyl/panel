<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use Throwable;

use function array_pop;
use function array_slice;
use function basename;
use function count;
use function debug_backtrace;
use function explode;
use function function_exists;
use function get_class;
use function gettype;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_object;
use function is_scalar;
use function is_string;
use function mb_substr;
use function md5;
use function realpath;
use function serialize;
use function str_replace;
use function var_export;

use const DIRECTORY_SEPARATOR;
use const E_COMPILE_ERROR;
use const E_COMPILE_WARNING;
use const E_CORE_ERROR;
use const E_CORE_WARNING;
use const E_DEPRECATED;
use const E_ERROR;
use const E_NOTICE;
use const E_PARSE;
use const E_RECOVERABLE_ERROR;
use const E_USER_DEPRECATED;
use const E_USER_ERROR;
use const E_USER_NOTICE;
use const E_USER_WARNING;
use const E_WARNING;
use const PATH_SEPARATOR;

/**
 * a single error
 */
class Error extends Message
{
    /**
     * Error types
     *
     * @var array<int, string>
     */
    public static $errortype = [
        0 => 'Internal error',
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parsing Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        2048 => 'Runtime Notice', // E_STRICT
        E_DEPRECATED => 'Deprecation Notice',
        E_USER_DEPRECATED => 'Deprecation Notice',
        E_RECOVERABLE_ERROR => 'Catchable Fatal Error',
    ];

    /**
     * Error levels
     *
     * @var array<int, string>
     */
    public static $errorlevel = [
        0 => 'error',
        E_ERROR => 'error',
        E_WARNING => 'error',
        E_PARSE => 'error',
        E_NOTICE => 'notice',
        E_CORE_ERROR => 'error',
        E_CORE_WARNING => 'error',
        E_COMPILE_ERROR => 'error',
        E_COMPILE_WARNING => 'error',
        E_USER_ERROR => 'error',
        E_USER_WARNING => 'error',
        E_USER_NOTICE => 'notice',
        2048 => 'notice', // E_STRICT
        E_DEPRECATED => 'notice',
        E_USER_DEPRECATED => 'notice',
        E_RECOVERABLE_ERROR => 'error',
    ];

    /**
     * The file in which the error occurred
     *
     * @var string
     */
    protected $file = '';

    /**
     * The line in which the error occurred
     *
     * @var int
     */
    protected $line = 0;

    /**
     * Holds the backtrace for this error
     *
     * @var array
     */
    protected $backtrace = [];

    /**
     * Hide location of errors
     *
     * @var bool
     */
    protected $hideLocation = false;

    /**
     * @param int    $errno   error number
     * @param string $errstr  error message
     * @param string $errfile file
     * @param int    $errline line
     */
    public function __construct(int $errno, string $errstr, string $errfile, int $errline)
    {
        parent::__construct();
        $this->setNumber($errno);
        $this->setMessage($errstr, false);
        $this->setFile($errfile);
        $this->setLine($errline);

        // This function can be disabled in php.ini
        if (function_exists('debug_backtrace')) {
            $backtrace = @debug_backtrace();
            // remove last three calls:
            // debug_backtrace(), handleError() and addError()
            $backtrace = array_slice($backtrace, 3);
        } else {
            $backtrace = [];
        }

        $this->setBacktrace($backtrace);
    }

    /**
     * Process backtrace to avoid path disclosures, objects and so on
     *
     * @param array $backtrace backtrace
     *
     * @return array
     */
    public static function processBacktrace(array $backtrace): array
    {
        $result = [];

        $members = [
            'line',
            'function',
            'class',
            'type',
        ];

        foreach ($backtrace as $idx => $step) {
            /* Create new backtrace entry */
            $result[$idx] = [];

            /* Make path relative */
            if (isset($step['file'])) {
                $result[$idx]['file'] = self::relPath($step['file']);
            }

            /* Store members we want */
            foreach ($members as $name) {
                if (! isset($step[$name])) {
                    continue;
                }

                $result[$idx][$name] = $step[$name];
            }

            /* Store simplified args */
            if (! isset($step['args'])) {
                continue;
            }

            foreach ($step['args'] as $key => $arg) {
                $result[$idx]['args'][$key] = self::getArg($arg, $step['function']);
            }
        }

        return $result;
    }

    /**
     * Toggles location hiding
     *
     * @param bool $hide Whether to hide
     */
    public function setHideLocation(bool $hide): void
    {
        $this->hideLocation = $hide;
    }

    /**
     * sets PhpMyAdmin\Error::$_backtrace
     *
     * We don't store full arguments to avoid wakeup or memory problems.
     *
     * @param array $backtrace backtrace
     */
    public function setBacktrace(array $backtrace): void
    {
        $this->backtrace = self::processBacktrace($backtrace);
    }

    /**
     * sets PhpMyAdmin\Error::$_line
     *
     * @param int $line the line
     */
    public function setLine(int $line): void
    {
        $this->line = $line;
    }

    /**
     * sets PhpMyAdmin\Error::$_file
     *
     * @param string $file the file
     */
    public function setFile(string $file): void
    {
        $this->file = self::relPath($file);
    }

    /**
     * returns unique PhpMyAdmin\Error::$hash, if not exists it will be created
     *
     * @return string PhpMyAdmin\Error::$hash
     */
    public function getHash(): string
    {
        try {
            $backtrace = serialize($this->getBacktrace());
        } catch (Throwable $e) {
            $backtrace = '';
        }

        if ($this->hash === null) {
            $this->hash = md5(
                $this->getNumber() .
                $this->getMessage() .
                $this->getFile() .
                $this->getLine() .
                $backtrace
            );
        }

        return $this->hash;
    }

    /**
     * returns PhpMyAdmin\Error::$_backtrace for first $count frames
     * pass $count = -1 to get full backtrace.
     * The same can be done by not passing $count at all.
     *
     * @param int $count Number of stack frames.
     *
     * @return array PhpMyAdmin\Error::$_backtrace
     */
    public function getBacktrace(int $count = -1): array
    {
        if ($count != -1) {
            return array_slice($this->backtrace, 0, $count);
        }

        return $this->backtrace;
    }

    /**
     * returns PhpMyAdmin\Error::$file
     *
     * @return string PhpMyAdmin\Error::$file
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * returns PhpMyAdmin\Error::$line
     *
     * @return int PhpMyAdmin\Error::$line
     */
    public function getLine(): int
    {
        return $this->line;
    }

    /**
     * returns type of error
     *
     * @return string type of error
     */
    public function getType(): string
    {
        return self::$errortype[$this->getNumber()] ?? 'Internal error';
    }

    /**
     * returns level of error
     *
     * @return string level of error
     */
    public function getLevel(): string
    {
        return self::$errorlevel[$this->getNumber()] ?? 'error';
    }

    /**
     * returns title prepared for HTML Title-Tag
     *
     * @return string HTML escaped and truncated title
     */
    public function getHtmlTitle(): string
    {
        return htmlspecialchars(
            mb_substr($this->getTitle(), 0, 100)
        );
    }

    /**
     * returns title for error
     */
    public function getTitle(): string
    {
        return $this->getType() . ': ' . $this->getMessage();
    }

    /**
     * Get HTML backtrace
     */
    public function getBacktraceDisplay(): string
    {
        return self::formatBacktrace(
            $this->getBacktrace(),
            "<br>\n",
            "<br>\n"
        );
    }

    /**
     * return formatted backtrace field
     *
     * @param array  $backtrace Backtrace data
     * @param string $separator Arguments separator to use
     * @param string $lines     Lines separator to use
     *
     * @return string formatted backtrace
     */
    public static function formatBacktrace(
        array $backtrace,
        string $separator,
        string $lines
    ): string {
        $retval = '';

        foreach ($backtrace as $step) {
            if (isset($step['file'], $step['line'])) {
                $retval .= self::relPath($step['file'])
                    . '#' . $step['line'] . ': ';
            }

            if (isset($step['class'])) {
                $retval .= $step['class'] . $step['type'];
            }

            $retval .= self::getFunctionCall($step, $separator);
            $retval .= $lines;
        }

        return $retval;
    }

    /**
     * Formats function call in a backtrace
     *
     * @param array  $step      backtrace step
     * @param string $separator Arguments separator to use
     */
    public static function getFunctionCall(array $step, string $separator): string
    {
        $retval = $step['function'] . '(';
        if (isset($step['args'])) {
            if (count($step['args']) > 1) {
                $retval .= $separator;
                foreach ($step['args'] as $arg) {
                    $retval .= "\t";
                    $retval .= $arg;
                    $retval .= ',' . $separator;
                }
            } elseif (count($step['args']) > 0) {
                foreach ($step['args'] as $arg) {
                    $retval .= $arg;
                }
            }
        }

        return $retval . ')';
    }

    /**
     * Get a single function argument
     *
     * if $function is one of include/require
     * the $arg is converted to a relative path
     *
     * @param mixed  $arg      argument to process
     * @param string $function function name
     */
    public static function getArg($arg, string $function): string
    {
        $retval = '';
        $includeFunctions = [
            'include',
            'include_once',
            'require',
            'require_once',
        ];
        $connectFunctions = [
            'mysql_connect',
            'mysql_pconnect',
            'mysqli_connect',
            'mysqli_real_connect',
            'connect',
            '_realConnect',
        ];

        if (in_array($function, $includeFunctions) && is_string($arg)) {
            $retval .= self::relPath($arg);
        } elseif (in_array($function, $connectFunctions) && is_string($arg)) {
            $retval .= gettype($arg) . ' ********';
        } elseif (is_scalar($arg)) {
            $retval .= gettype($arg) . ' '
                . htmlspecialchars(var_export($arg, true));
        } elseif (is_object($arg)) {
            $retval .= '<Class:' . get_class($arg) . '>';
        } else {
            $retval .= gettype($arg);
        }

        return $retval;
    }

    /**
     * Gets the error as string of HTML
     */
    public function getDisplay(): string
    {
        $this->isDisplayed(true);

        $context = 'primary';
        $level = $this->getLevel();
        if ($level === 'error') {
            $context = 'danger';
        }

        $retval = '<div class="alert alert-' . $context . '" role="alert">';
        if (! $this->isUserError()) {
            $retval .= '<strong>' . $this->getType() . '</strong>';
            $retval .= ' in ' . $this->getFile() . '#' . $this->getLine();
            $retval .= "<br>\n";
        }

        $retval .= $this->getMessage();
        if (! $this->isUserError()) {
            $retval .= "<br>\n";
            $retval .= "<br>\n";
            $retval .= "<strong>Backtrace</strong><br>\n";
            $retval .= "<br>\n";
            $retval .= $this->getBacktraceDisplay();
        }

        $retval .= '</div>';

        return $retval;
    }

    /**
     * whether this error is a user error
     */
    public function isUserError(): bool
    {
        return $this->hideLocation ||
            ($this->getNumber() & (E_USER_WARNING | E_USER_ERROR | E_USER_NOTICE | E_USER_DEPRECATED));
    }

    /**
     * return short relative path to phpMyAdmin basedir
     *
     * prevent path disclosure in error message,
     * and make users feel safe to submit error reports
     *
     * @param string $path path to be shorten
     *
     * @return string shortened path
     */
    public static function relPath(string $path): string
    {
        $dest = @realpath($path);

        /* Probably affected by open_basedir */
        if ($dest === false) {
            return basename($path);
        }

        $hereParts = explode(
            DIRECTORY_SEPARATOR,
            (string) realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..')
        );
        $destParts = explode(DIRECTORY_SEPARATOR, $dest);

        $result = '.';
        while (implode(DIRECTORY_SEPARATOR, $destParts) != implode(DIRECTORY_SEPARATOR, $hereParts)) {
            if (count($hereParts) > count($destParts)) {
                array_pop($hereParts);
                $result .= DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..';
            } else {
                array_pop($destParts);
            }
        }

        $path = $result . str_replace(implode(DIRECTORY_SEPARATOR, $destParts), '', $dest);

        return str_replace(DIRECTORY_SEPARATOR . PATH_SEPARATOR, DIRECTORY_SEPARATOR, $path);
    }
}
