<?php

namespace Psalm\Internal;

use RuntimeException;
use Throwable;

use function defined;
use function error_reporting;
use function fwrite;
use function ini_set;
use function set_error_handler;
use function set_exception_handler;

use const E_ALL;
use const E_STRICT;
use const STDERR;

final class ErrorHandler
{
    /** @var bool */
    private static $exceptions_enabled = true;

    public static function install(): void
    {
        self::setErrorReporting();
        self::installErrorHandler();
        self::installExceptionHandler();
    }

    /**
     * @template T
     * @param callable():T $f
     * @return T
     */
    public static function runWithExceptionsSuppressed(callable $f)
    {
        try {
            self::$exceptions_enabled = false;
            return $f();
        } finally {
            self::$exceptions_enabled = true;
        }
    }

    /** @psalm-suppress UnusedConstructor added to prevent instantiations */
    private function __construct()
    {
    }

    private static function setErrorReporting(): void
    {
        error_reporting(E_ALL | E_STRICT);
        ini_set('display_errors', '1');
    }

    private static function installErrorHandler(): void
    {
        set_error_handler(static function (
            int $error_code,
            string $error_message,
            string $error_filename = 'unknown',
            int $error_line = -1
        ): bool {
            if (ErrorHandler::$exceptions_enabled && ($error_code & error_reporting())) {
                throw new RuntimeException(
                    'PHP Error: ' . $error_message . ' in ' . $error_filename . ':' . $error_line,
                    $error_code
                );
            }
            // let PHP handle suppressed errors how it sees fit
            return false;
        });
    }

    private static function installExceptionHandler(): void
    {
        /**
         * If there is an uncaught exception,
         * then print more of the backtrace than is done by default to stderr,
         * then exit with a non-zero exit code to indicate failure.
         */
        set_exception_handler(static function (Throwable $throwable): void {
            fwrite(STDERR, "Uncaught $throwable\n");
            $version = defined('PSALM_VERSION') ? PSALM_VERSION : '(unknown version)';
            fwrite(STDERR, "(Psalm $version crashed due to an uncaught Throwable)\n");
            exit(1);
        });
    }
}
