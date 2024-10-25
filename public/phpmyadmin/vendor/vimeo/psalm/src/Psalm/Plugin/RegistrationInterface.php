<?php

namespace Psalm\Plugin;

use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Scanner\FileScanner;

interface RegistrationInterface
{
    public function addStubFile(string $file_name): void;

    /**
     * @param class-string $handler
     */
    public function registerHooksFromClass(string $handler): void;

    /**
     * @param string $fileExtension e.g. `'html'`
     * @param class-string<FileScanner> $className
     * @deprecated will be removed in v5.0, use \Psalm\Plugin\FileExtensionsInterface instead (#6788)
     */
    public function addFileTypeScanner(string $fileExtension, string $className): void;

    /**
     * @param string $fileExtension e.g. `'html'`
     * @param class-string<FileAnalyzer> $className
     * @deprecated will be removed in v5.0, use \Psalm\Plugin\FileExtensionsInterface instead (#6788)
     */
    public function addFileTypeAnalyzer(string $fileExtension, string $className): void;
}
