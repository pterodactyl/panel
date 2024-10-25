<?php

namespace Psalm\Plugin\Hook;

use PhpParser\Node\Stmt\ClassLike;
use Psalm\Codebase;
use Psalm\FileManipulation;
use Psalm\FileSource;
use Psalm\Storage\ClassLikeStorage;

/** @deprecated going to be removed in Psalm 5 */
interface AfterClassLikeVisitInterface
{
    /**
     * @param  FileManipulation[] $file_replacements
     *
     * @return void
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint
     */
    public static function afterClassLikeVisit(
        ClassLike $stmt,
        ClassLikeStorage $storage,
        FileSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    );
}
