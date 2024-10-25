<?php

namespace Psalm\Internal\Provider;

use Psalm\Config;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function hash;
use function mkdir;
use function touch;

use const DIRECTORY_SEPARATOR;
use const PHP_VERSION_ID;

/**
 * Used to determine which files reference other files, necessary for using the --diff
 * option from the command line.
 */
class ProjectCacheProvider
{
    private const GOOD_RUN_NAME = 'good_run';
    private const COMPOSER_LOCK_HASH = 'composer_lock_hash';

    /**
     * @var int|null
     */
    private $last_run;

    /**
     * @var string|null
     */
    private $composer_lock_hash;

    private $composer_lock_location;

    public function __construct(string $composer_lock_location)
    {
        $this->composer_lock_location = $composer_lock_location;
    }

    public function canDiffFiles(): bool
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        return $cache_directory && file_exists($cache_directory . DIRECTORY_SEPARATOR . self::GOOD_RUN_NAME);
    }

    public function processSuccessfulRun(float $start_time, string $psalm_version): void
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $run_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::GOOD_RUN_NAME;

        file_put_contents($run_cache_location, $psalm_version);

        touch($run_cache_location, (int)$start_time);
    }

    public function getLastRun(string $psalm_version): int
    {
        if ($this->last_run === null) {
            $cache_directory = Config::getInstance()->getCacheDirectory();

            $run_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::GOOD_RUN_NAME;

            if (file_exists($run_cache_location) && file_get_contents($run_cache_location) === $psalm_version) {
                $this->last_run = filemtime($run_cache_location);
            } else {
                $this->last_run = 0;
            }
        }

        return $this->last_run;
    }

    public function hasLockfileChanged(): bool
    {
        if (!file_exists($this->composer_lock_location)) {
            return true;
        }

        $lockfile_contents = file_get_contents($this->composer_lock_location);

        if (!$lockfile_contents) {
            return true;
        }

        if (PHP_VERSION_ID >= 80100) {
            $hash = hash('xxh128', $lockfile_contents);
        } else {
            $hash = hash('md4', $lockfile_contents);
        }

        $changed = $hash !== $this->getComposerLockHash();

        $this->composer_lock_hash = $hash;

        return $changed;
    }

    public function updateComposerLockHash(): void
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if (!$cache_directory || !$this->composer_lock_hash) {
            return;
        }

        if (!file_exists($cache_directory)) {
            mkdir($cache_directory, 0777, true);
        }

        $lock_hash_location = $cache_directory . DIRECTORY_SEPARATOR . self::COMPOSER_LOCK_HASH;

        file_put_contents($lock_hash_location, $this->composer_lock_hash);
    }

    protected function getComposerLockHash(): string
    {
        if ($this->composer_lock_hash === null) {
            $cache_directory = Config::getInstance()->getCacheDirectory();

            $lock_hash_location = $cache_directory . DIRECTORY_SEPARATOR . self::COMPOSER_LOCK_HASH;

            if (file_exists($lock_hash_location)) {
                $this->composer_lock_hash = file_get_contents($lock_hash_location) ?: '';
            } else {
                $this->composer_lock_hash = '';
            }
        }

        return $this->composer_lock_hash;
    }
}
