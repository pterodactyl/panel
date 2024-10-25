<?php

namespace Psalm\Internal\Provider;

use RuntimeException;

use function fclose;
use function filesize;
use function flock;
use function fopen;
use function fread;
use function usleep;

use const LOCK_SH;

/**
 * @internal
 */
class Providers
{
    /**
     * @var FileProvider
     */
    public $file_provider;

    /**
     * @var ?ParserCacheProvider
     */
    public $parser_cache_provider;

    /**
     * @var FileStorageProvider
     */
    public $file_storage_provider;

    /**
     * @var ClassLikeStorageProvider
     */
    public $classlike_storage_provider;

    /**
     * @var StatementsProvider
     */
    public $statements_provider;

    /**
     * @var FileReferenceProvider
     */
    public $file_reference_provider;

    /**
     * @var ?ProjectCacheProvider
     */
    public $project_cache_provider;

    public function __construct(
        FileProvider $file_provider,
        ?ParserCacheProvider $parser_cache_provider = null,
        ?FileStorageCacheProvider $file_storage_cache_provider = null,
        ?ClassLikeStorageCacheProvider $classlike_storage_cache_provider = null,
        ?FileReferenceCacheProvider $file_reference_cache_provider = null,
        ?ProjectCacheProvider $project_cache_provider = null
    ) {
        $this->file_provider = $file_provider;
        $this->parser_cache_provider = $parser_cache_provider;
        $this->project_cache_provider = $project_cache_provider;

        $this->file_storage_provider = new FileStorageProvider($file_storage_cache_provider);
        $this->classlike_storage_provider = new ClassLikeStorageProvider($classlike_storage_cache_provider);
        $this->statements_provider = new StatementsProvider(
            $file_provider,
            $parser_cache_provider,
            $file_storage_cache_provider
        );
        $this->file_reference_provider = new FileReferenceProvider($file_reference_cache_provider);
    }

    public static function safeFileGetContents(string $path): string
    {
        // no readable validation as that must be done in the caller
        $fp = fopen($path, 'r');
        if ($fp === false) {
            return '';
        }
        $max_wait_cycles = 5;
        $has_lock = false;
        while ($max_wait_cycles > 0) {
            if (flock($fp, LOCK_SH)) {
                $has_lock = true;
                break;
            }
            $max_wait_cycles--;
            usleep(50000);
        }

        if (!$has_lock) {
            fclose($fp);
            throw new RuntimeException('Could not acquire lock for ' . $path);
        }

        $file_size = filesize($path);
        $content = '';
        if ($file_size > 0) {
            $content = (string) fread($fp, $file_size);
        }

        fclose($fp);

        return $content;
    }
}
