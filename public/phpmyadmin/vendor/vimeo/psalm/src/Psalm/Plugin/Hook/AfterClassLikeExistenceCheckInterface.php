<?php

namespace Psalm\Plugin\Hook;

use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\FileManipulation;
use Psalm\StatementsSource;

/** @deprecated going to be removed in Psalm 5 */
interface AfterClassLikeExistenceCheckInterface
{
    /**
     * @param  FileManipulation[] $file_replacements
     */
    public static function afterClassLikeExistenceCheck(
        string $fq_class_name,
        CodeLocation $code_location,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    ): void;
}
