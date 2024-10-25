<?php

namespace Psalm\Internal\PhpVisitor\Reflector;

use PhpParser;
use Psalm\Aliases;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Codebase\Scanner as CodebaseScanner;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FileStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Union;
use UnexpectedValueException;

use function implode;
use function strtolower;

class TypeHintResolver
{
    /**
     * @param PhpParser\Node\Identifier|PhpParser\Node\Name|PhpParser\Node\NullableType|PhpParser\Node\UnionType $hint
     */
    public static function resolve(
        PhpParser\NodeAbstract $hint,
        CodebaseScanner $scanner,
        FileStorage $file_storage,
        ?ClassLikeStorage $classlike_storage,
        Aliases $aliases,
        int $php_major_version,
        int $php_minor_version
    ): Union {
        if ($hint instanceof PhpParser\Node\UnionType) {
            $type = null;

            if (!$hint->types) {
                throw new UnexpectedValueException('bad');
            }

            foreach ($hint->types as $atomic_typehint) {
                $resolved_type = self::resolve(
                    $atomic_typehint,
                    $scanner,
                    $file_storage,
                    $classlike_storage,
                    $aliases,
                    $php_major_version,
                    $php_minor_version
                );

                $type = Type::combineUnionTypes($resolved_type, $type);
            }

            return $type;
        }

        $is_nullable = false;

        if ($hint instanceof PhpParser\Node\NullableType) {
            $is_nullable = true;
            $hint = $hint->type;
        }

        $type_string = null;

        if ($hint instanceof PhpParser\Node\Identifier) {
            $fq_type_string = $hint->name;
        } elseif ($hint instanceof PhpParser\Node\Name\FullyQualified) {
            $fq_type_string = (string)$hint;

            $scanner->queueClassLikeForScanning($fq_type_string);
            $file_storage->referenced_classlikes[strtolower($fq_type_string)] = $fq_type_string;
        } else {
            $lower_hint = strtolower($hint->parts[0]);

            if ($classlike_storage
                && ($lower_hint === 'self' || $lower_hint === 'static')
                && !$classlike_storage->is_trait
            ) {
                $fq_type_string = $classlike_storage->name;

                if ($lower_hint === 'static') {
                    $fq_type_string .= '&static';
                }
            } else {
                $type_string = implode('\\', $hint->parts);
                $fq_type_string = ClassLikeAnalyzer::getFQCLNFromNameObject($hint, $aliases);

                $scanner->queueClassLikeForScanning($fq_type_string);
                $file_storage->referenced_classlikes[strtolower($fq_type_string)] = $fq_type_string;
            }
        }

        $type = Type::parseString(
            $fq_type_string,
            [$php_major_version, $php_minor_version],
            []
        );

        if ($type_string) {
            $atomic_type = $type->getSingleAtomic();
            $atomic_type->text = $type_string;
        }

        if ($is_nullable) {
            $type->addType(new TNull);
        }

        return $type;
    }
}
