<?php

namespace Psalm\Internal;

use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Codebase\Functions;
use Psalm\Internal\Codebase\Reflection;
use Psalm\Internal\FileManipulation\ClassDocblockManipulator;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\FileManipulation\FunctionDocblockManipulator;
use Psalm\Internal\FileManipulation\PropertyDocblockManipulator;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileReferenceProvider;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\Internal\Provider\StatementsProvider;
use Psalm\Internal\Provider\StatementsVolatileCache;
use Psalm\Internal\Scanner\ParsedDocblock;
use Psalm\Internal\Type\TypeTokenizer;
use Psalm\IssueBuffer;

abstract class RuntimeCaches
{
    public static function clearAll(): void
    {
        IssueBuffer::clearCache();
        Reflection::clearCache();
        Functions::clearCache();
        TypeTokenizer::clearCache();
        FileReferenceProvider::clearCache();
        FileManipulationBuffer::clearCache();
        ClassDocblockManipulator::clearCache();
        FunctionDocblockManipulator::clearCache();
        PropertyDocblockManipulator::clearCache();
        FileAnalyzer::clearCache();
        FunctionLikeAnalyzer::clearCache();
        ClassLikeStorageProvider::deleteAll();
        FileStorageProvider::deleteAll();
        StatementsProvider::clearLexer();
        StatementsProvider::clearParser();
        ParsedDocblock::resetNewlineBetweenAnnotations();
        StatementsVolatileCache::getInstance()->clearCache();
    }
}
