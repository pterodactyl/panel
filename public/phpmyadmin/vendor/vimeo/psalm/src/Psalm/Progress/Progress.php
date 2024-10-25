<?php

namespace Psalm\Progress;

use function error_reporting;
use function function_exists;
use function fwrite;
use function sapi_windows_cp_is_utf8;
use function stripos;

use const E_ERROR;
use const PHP_OS;
use const STDERR;

abstract class Progress
{
    public function setErrorReporting(): void
    {
        error_reporting(E_ERROR);
    }

    public function debug(string $message): void
    {
    }

    public function startScanningFiles(): void
    {
    }

    public function startAnalyzingFiles(): void
    {
    }

    public function startAlteringFiles(): void
    {
    }

    public function alterFileDone(string $file_name): void
    {
    }

    public function start(int $number_of_tasks): void
    {
    }

    public function taskDone(int $level): void
    {
    }

    public function finish(): void
    {
    }

    public function write(string $message): void
    {
        fwrite(STDERR, $message);
    }

    protected static function doesTerminalSupportUtf8(): bool
    {
        if (stripos(PHP_OS, 'WIN') === 0) {
            if (!function_exists('sapi_windows_cp_is_utf8') || !sapi_windows_cp_is_utf8()) {
                return false;
            }
        }

        return true;
    }
}
