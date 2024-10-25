<?php

namespace Psalm\Internal\Provider;

use PhpParser;
use PhpParser\Node\Stmt;
use Psalm\Config;
use Psalm\Internal\Provider\Providers;
use RuntimeException;
use UnexpectedValueException;

use function clearstatcache;
use function file_put_contents;
use function filemtime;
use function gettype;
use function hash;
use function igbinary_serialize;
use function igbinary_unserialize;
use function is_array;
use function is_dir;
use function is_readable;
use function is_writable;
use function json_decode;
use function json_encode;
use function mkdir;
use function scandir;
use function serialize;
use function touch;
use function unlink;
use function unserialize;

use const DIRECTORY_SEPARATOR;
use const LOCK_EX;
use const PHP_VERSION_ID;
use const SCANDIR_SORT_NONE;

/**
 * @internal
 */
class ParserCacheProvider
{
    private const FILE_HASHES = 'file_hashes_json';
    private const PARSER_CACHE_DIRECTORY = 'php-parser';
    private const FILE_CONTENTS_CACHE_DIRECTORY = 'file-caches';

    /**
     * @var Config
     */
    private $config;

    /**
     * A map of filename hashes to contents hashes
     *
     * @var array<string, string>|null
     */
    protected $existing_file_content_hashes;

    /**
     * A map of recently-added filename hashes to contents hashes
     *
     * @var array<string, string>
     */
    protected $new_file_content_hashes = [];

    /**
     * @var bool
     */
    private $use_file_cache;

    public function __construct(Config $config, bool $use_file_cache = true)
    {
        $this->config = $config;
        $this->use_file_cache = $use_file_cache;
    }

    /**
     * @return list<PhpParser\Node\Stmt>|null
     */
    public function loadStatementsFromCache(
        string $file_path,
        int $file_modified_time,
        string $file_content_hash
    ): ?array {
        if (!$this->use_file_cache) {
            return null;
        }

        $cache_location = $this->getCacheLocationForPath($file_path, self::PARSER_CACHE_DIRECTORY);

        $file_cache_key = $this->getParserCacheKey($file_path);

        $file_content_hashes = $this->new_file_content_hashes + $this->getExistingFileContentHashes();

        if (isset($file_content_hashes[$file_cache_key])
            && $file_content_hash === $file_content_hashes[$file_cache_key]
            && is_readable($cache_location)
            && filemtime($cache_location) > $file_modified_time
        ) {
            if ($this->config->use_igbinary) {
                /** @var list<Stmt> */
                $stmts = igbinary_unserialize(Providers::safeFileGetContents($cache_location));
            } else {
                /** @var list<Stmt> */
                $stmts = unserialize(Providers::safeFileGetContents($cache_location));
            }

            return $stmts;
        }

        return null;
    }

    /**
     * @return list<PhpParser\Node\Stmt>|null
     */
    public function loadExistingStatementsFromCache(string $file_path): ?array
    {
        if (!$this->use_file_cache) {
            return null;
        }

        $cache_location = $this->getCacheLocationForPath($file_path, self::PARSER_CACHE_DIRECTORY);

        if (is_readable($cache_location)) {
            if ($this->config->use_igbinary) {
                /** @var list<Stmt> */
                return igbinary_unserialize(Providers::safeFileGetContents($cache_location)) ?: null;
            }

            /** @var list<Stmt> */
            return unserialize(Providers::safeFileGetContents($cache_location)) ?: null;
        }

        return null;
    }

    public function loadExistingFileContentsFromCache(string $file_path): ?string
    {
        if (!$this->use_file_cache) {
            return null;
        }

        $cache_location = $this->getCacheLocationForPath($file_path, self::FILE_CONTENTS_CACHE_DIRECTORY);

        if (is_readable($cache_location)) {
            return Providers::safeFileGetContents($cache_location);
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function getExistingFileContentHashes(): array
    {
        if (!$this->use_file_cache) {
            return [];
        }

        if ($this->existing_file_content_hashes === null) {
            $root_cache_directory = $this->config->getCacheDirectory();
            $file_hashes_path = $root_cache_directory . DIRECTORY_SEPARATOR . self::FILE_HASHES;

            if (!$root_cache_directory) {
                throw new UnexpectedValueException('No cache directory defined');
            }

            if (!is_readable($file_hashes_path)) {
                // might not exist yet
                $this->existing_file_content_hashes = [];
                return $this->existing_file_content_hashes;
            }

            $hashes_encoded = Providers::safeFileGetContents($file_hashes_path);
            if (!$hashes_encoded) {
                throw new UnexpectedValueException('File content hashes should be in cache');
            }

            $hashes_decoded = json_decode($hashes_encoded, true);

            if (!is_array($hashes_decoded)) {
                throw new UnexpectedValueException(
                    'File content hashes are of invalid type ' . gettype($hashes_decoded)
                );
            }

            /** @var array<string, string> $hashes_decoded */
            $this->existing_file_content_hashes = $hashes_decoded;
        }

        return $this->existing_file_content_hashes;
    }

    /**
     * @param  list<PhpParser\Node\Stmt>        $stmts
     */
    public function saveStatementsToCache(
        string $file_path,
        string $file_content_hash,
        array $stmts,
        bool $touch_only
    ): void {
        $cache_location = $this->getCacheLocationForPath($file_path, self::PARSER_CACHE_DIRECTORY, !$touch_only);

        if ($touch_only) {
            touch($cache_location);
        } else {
            if ($this->config->use_igbinary) {
                file_put_contents($cache_location, igbinary_serialize($stmts), LOCK_EX);
            } else {
                file_put_contents($cache_location, serialize($stmts), LOCK_EX);
            }

            $file_cache_key = $this->getParserCacheKey($file_path);
            $this->new_file_content_hashes[$file_cache_key] = $file_content_hash;
        }
    }

    /**
     * @return array<string, string>
     */
    public function getNewFileContentHashes(): array
    {
        return $this->new_file_content_hashes;
    }

    /**
     * @param array<string, string> $file_content_hashes
     *
     */
    public function addNewFileContentHashes(array $file_content_hashes): void
    {
        $this->new_file_content_hashes = $file_content_hashes + $this->new_file_content_hashes;
    }

    public function saveFileContentHashes(): void
    {
        if (!$this->use_file_cache) {
            return;
        }

        $root_cache_directory = $this->config->getCacheDirectory();

        if (!$root_cache_directory) {
            return;
        }

        // directory was removed most likely due to a race condition
        // with other psalm instances that were manually started at
        // the same time
        clearstatcache(true, $root_cache_directory);
        if (!is_dir($root_cache_directory)) {
            return;
        }

        $file_content_hashes = $this->new_file_content_hashes + $this->getExistingFileContentHashes();

        $file_hashes_path = $root_cache_directory . DIRECTORY_SEPARATOR . self::FILE_HASHES;

        file_put_contents(
            $file_hashes_path,
            json_encode($file_content_hashes),
            LOCK_EX
        );
    }

    public function cacheFileContents(string $file_path, string $file_contents): void
    {
        if (!$this->use_file_cache) {
            return;
        }

        $cache_location = $this->getCacheLocationForPath($file_path, self::FILE_CONTENTS_CACHE_DIRECTORY, true);

        file_put_contents($cache_location, $file_contents, LOCK_EX);
    }

    public function deleteOldParserCaches(float $time_before): int
    {
        $cache_directory = $this->config->getCacheDirectory();

        $this->existing_file_content_hashes = null;
        $this->new_file_content_hashes = [];

        if (!$cache_directory) {
            return 0;
        }

        $removed_count = 0;

        $cache_directory .= DIRECTORY_SEPARATOR . self::PARSER_CACHE_DIRECTORY;

        if (is_dir($cache_directory)) {
            $directory_files = scandir($cache_directory, SCANDIR_SORT_NONE);

            foreach ($directory_files as $directory_file) {
                $full_path = $cache_directory . DIRECTORY_SEPARATOR . $directory_file;

                if ($directory_file[0] === '.') {
                    continue;
                }

                if (filemtime($full_path) < $time_before && is_writable($full_path)) {
                    unlink($full_path);
                    ++$removed_count;
                }
            }
        }

        return $removed_count;
    }

    private function getParserCacheKey(string $file_path): string
    {
        if (PHP_VERSION_ID >= 80100) {
            $hash = hash('xxh128', $file_path);
        } else {
            $hash = hash('md4', $file_path);
        }

        return $hash . ($this->config->use_igbinary ? '-igbinary' : '') . '-r';
    }


    private function getCacheLocationForPath(
        string $file_path,
        string $subdirectory,
        bool $create_directory = false
    ): string {
        $root_cache_directory = $this->config->getCacheDirectory();

        if (!$root_cache_directory) {
            throw new UnexpectedValueException('No cache directory defined');
        }

        $parser_cache_directory = $root_cache_directory . DIRECTORY_SEPARATOR . $subdirectory;

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

        return $parser_cache_directory
               . DIRECTORY_SEPARATOR
               . $this->getParserCacheKey($file_path);
    }
}
