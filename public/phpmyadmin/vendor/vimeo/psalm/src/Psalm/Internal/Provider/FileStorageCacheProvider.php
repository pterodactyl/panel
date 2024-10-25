<?php

namespace Psalm\Internal\Provider;

use Psalm\Config;
use Psalm\Internal\Provider\Providers;
use Psalm\Storage\FileStorage;
use RuntimeException;
use UnexpectedValueException;

use function array_merge;
use function dirname;
use function file_exists;
use function file_put_contents;
use function filemtime;
use function get_class;
use function hash;
use function igbinary_serialize;
use function igbinary_unserialize;
use function is_dir;
use function mkdir;
use function serialize;
use function strtolower;
use function unlink;
use function unserialize;

use const DIRECTORY_SEPARATOR;
use const LOCK_EX;
use const PHP_VERSION_ID;

/**
 * @internal
 */
class FileStorageCacheProvider
{
    /**
     * @var string
     */
    private $modified_timestamps = '';

    /**
     * @var Config
     */
    private $config;

    private const FILE_STORAGE_CACHE_DIRECTORY = 'file_cache';

    public function __construct(Config $config)
    {
        $this->config = $config;

        $storage_dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'Storage' . DIRECTORY_SEPARATOR;

        $dependent_files = [
            $storage_dir . 'FileStorage.php',
            $storage_dir . 'FunctionLikeStorage.php',
            $storage_dir . 'ClassLikeStorage.php',
            $storage_dir . 'MethodStorage.php',
            $storage_dir . 'FunctionLikeParameter.php',
        ];

        if ($config->eventDispatcher->hasAfterClassLikeVisitHandlers()) {
            $dependent_files = array_merge($dependent_files, $config->plugin_paths);
        }

        foreach ($dependent_files as $dependent_file_path) {
            if (!file_exists($dependent_file_path)) {
                throw new UnexpectedValueException($dependent_file_path . ' must exist');
            }

            $this->modified_timestamps .= ' ' . filemtime($dependent_file_path);
        }

        $this->modified_timestamps .= $this->config->computeHash();
    }

    public function writeToCache(FileStorage $storage, string $file_contents): void
    {
        $file_path = strtolower($storage->file_path);
        $cache_location = $this->getCacheLocationForPath($file_path, true);
        $storage->hash = $this->getCacheHash($file_path, $file_contents);

        if ($this->config->use_igbinary) {
            file_put_contents($cache_location, igbinary_serialize($storage), LOCK_EX);
        } else {
            file_put_contents($cache_location, serialize($storage), LOCK_EX);
        }
    }

    public function getLatestFromCache(string $file_path, string $file_contents): ?FileStorage
    {
        $file_path = strtolower($file_path);
        $cached_value = $this->loadFromCache($file_path);

        if (!$cached_value) {
            return null;
        }

        $cache_hash = $this->getCacheHash($file_path, $file_contents);

        /** @psalm-suppress TypeDoesNotContainType */
        if (@get_class($cached_value) === '__PHP_Incomplete_Class'
            || $cache_hash !== $cached_value->hash
        ) {
            $this->removeCacheForFile($file_path);

            return null;
        }

        return $cached_value;
    }

    public function removeCacheForFile(string $file_path): void
    {
        $cache_path = $this->getCacheLocationForPath($file_path);

        if (file_exists($cache_path)) {
            unlink($cache_path);
        }
    }

    private function getCacheHash(string $_unused_file_path, string $file_contents): string
    {
        // do not concatenate, as $file_contents can be big and performance will be bad
        // the timestamp is only needed if we don't have file contents
        // as same contents should give same results, independent of when file was modified
        $data = $file_contents ? $file_contents : $this->modified_timestamps;
        return PHP_VERSION_ID >= 80100 ? hash('xxh128', $data) : hash('md4', $data);
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    private function loadFromCache(string $file_path): ?FileStorage
    {
        $cache_location = $this->getCacheLocationForPath($file_path);

        if (file_exists($cache_location)) {
            if ($this->config->use_igbinary) {
                $storage = igbinary_unserialize(Providers::safeFileGetContents($cache_location));

                if ($storage instanceof FileStorage) {
                    return $storage;
                }

                return null;
            }

            $storage = unserialize(Providers::safeFileGetContents($cache_location));

            if ($storage instanceof FileStorage) {
                return $storage;
            }

            return null;
        }

        return null;
    }

    private function getCacheLocationForPath(string $file_path, bool $create_directory = false): string
    {
        $root_cache_directory = $this->config->getCacheDirectory();

        if (!$root_cache_directory) {
            throw new UnexpectedValueException('No cache directory defined');
        }

        $parser_cache_directory = $root_cache_directory . DIRECTORY_SEPARATOR . self::FILE_STORAGE_CACHE_DIRECTORY;

        if ($create_directory && !is_dir($parser_cache_directory)) {
            try {
                if (mkdir($parser_cache_directory, 0777, true) === false) {
                    // any other error than directory already exists/permissions issue
                    throw new RuntimeException(
                        'Failed to create ' . $parser_cache_directory . ' cache directory for unknown reasons'
                    );
                }
            } catch (RuntimeException $e) {
                // Race condition (#4483)
                if (!is_dir($parser_cache_directory)) {
                    // rethrow the error with default message
                    // it contains the reason why creation failed
                    throw $e;
                }
            }
        }

        if (PHP_VERSION_ID >= 80100) {
            $hash = hash('xxh128', $file_path);
        } else {
            $hash = hash('md4', $file_path);
        }

        return $parser_cache_directory
            . DIRECTORY_SEPARATOR
            . $hash
            . ($this->config->use_igbinary ? '-igbinary' : '');
    }
}
