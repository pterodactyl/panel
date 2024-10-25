<?php

namespace Psalm\Internal\Provider;

use FilesystemIterator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIterator;
use RecursiveIteratorIterator;
use UnexpectedValueException;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function in_array;
use function is_dir;

use const DIRECTORY_SEPARATOR;

class FileProvider
{
    /**
     * @var array<string, string>
     */
    protected $temp_files = [];

    /**
     * @var array<string, string>
     */
    protected static $open_files = [];

    public function getContents(string $file_path, bool $go_to_source = false): string
    {
        if (!$go_to_source && isset($this->temp_files[$file_path])) {
            return $this->temp_files[$file_path];
        }

        if (isset(self::$open_files[$file_path])) {
            return self::$open_files[$file_path];
        }

        if (!file_exists($file_path)) {
            throw new UnexpectedValueException('File ' . $file_path . ' should exist to get contents');
        }

        if (is_dir($file_path)) {
            throw new UnexpectedValueException('File ' . $file_path . ' is a directory');
        }

        $file_contents = (string) file_get_contents($file_path);

        self::$open_files[$file_path] = $file_contents;

        return $file_contents;
    }

    public function setContents(string $file_path, string $file_contents): void
    {
        if (isset(self::$open_files[$file_path])) {
            self::$open_files[$file_path] = $file_contents;
        }

        if (isset($this->temp_files[$file_path])) {
            $this->temp_files[$file_path] = $file_contents;
        }

        file_put_contents($file_path, $file_contents);
    }

    public function setOpenContents(string $file_path, string $file_contents): void
    {
        if (isset(self::$open_files[$file_path])) {
            self::$open_files[$file_path] = $file_contents;
        }
    }

    public function getModifiedTime(string $file_path): int
    {
        if (!file_exists($file_path)) {
            throw new UnexpectedValueException('File should exist to get modified time');
        }

        return (int) filemtime($file_path);
    }

    public function addTemporaryFileChanges(string $file_path, string $new_content): void
    {
        $this->temp_files[$file_path] = $new_content;
    }

    public function removeTemporaryFileChanges(string $file_path): void
    {
        unset($this->temp_files[$file_path]);
    }

    public function openFile(string $file_path): void
    {
        self::$open_files[$file_path] = $this->getContents($file_path, true);
    }

    public function isOpen(string $file_path): bool
    {
        return isset($this->temp_files[$file_path]) || isset(self::$open_files[$file_path]);
    }

    public function closeFile(string $file_path): void
    {
        unset($this->temp_files[$file_path], self::$open_files[$file_path]);
    }

    public function fileExists(string $file_path): bool
    {
        return file_exists($file_path);
    }

    /**
     * @param array<string> $file_extensions
     * @param null|callable(string):bool $filter
     *
     * @return list<string>
     */
    public function getFilesInDir(string $dir_path, array $file_extensions, callable $filter = null): array
    {
        $file_paths = [];

        $iterator = new RecursiveDirectoryIterator(
            $dir_path,
            FilesystemIterator::CURRENT_AS_PATHNAME | FilesystemIterator::SKIP_DOTS
        );

        if ($filter !== null) {
            $iterator = new RecursiveCallbackFilterIterator(
                $iterator,
                /** @param mixed $_ */
                function (string $current, $_, RecursiveIterator $iterator) use ($filter): bool {
                    if ($iterator->hasChildren()) {
                        $path = $current . DIRECTORY_SEPARATOR;
                    } else {
                        $path = $current;
                    }

                    return $filter($path);
                }
            );
        }

        /** @var RecursiveDirectoryIterator */
        $iterator = new RecursiveIteratorIterator($iterator);
        $iterator->rewind();

        while ($iterator->valid()) {
            $extension = $iterator->getExtension();
            if (in_array($extension, $file_extensions, true)) {
                $file_paths[] = (string)$iterator->getRealPath();
            }

            $iterator->next();
        }

        return $file_paths;
    }
}
