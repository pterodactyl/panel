<?php

declare(strict_types=1);

namespace Psalm\PhpUnitPlugin\Hooks;

use PhpParser\Comment\Doc;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use Psalm\Codebase;
use Psalm\DocComment;
use Psalm\Exception\DocblockParseException;
use Psalm\IssueBuffer;
use Psalm\Issue;
use Psalm\Plugin\EventHandler\AfterClassLikeAnalysisInterface;
use Psalm\Plugin\EventHandler\AfterClassLikeVisitInterface;
use Psalm\Plugin\EventHandler\AfterCodebasePopulatedInterface;
use Psalm\Plugin\EventHandler\Event\AfterClassLikeAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\AfterClassLikeVisitEvent;
use Psalm\Plugin\EventHandler\Event\AfterCodebasePopulatedEvent;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TNull;
use RuntimeException;

class TestCaseHandler implements
    AfterClassLikeVisitInterface,
    AfterClassLikeAnalysisInterface,
    AfterCodebasePopulatedInterface
{
    /**
     * {@inheritDoc}
     */
    public static function afterCodebasePopulated(AfterCodebasePopulatedEvent $event)
    {
        $codebase = $event->getCodebase();

        foreach ($codebase->classlike_storage_provider->getAll() as $name => $storage) {
            $meta = (array) ($storage->custom_metadata[__NAMESPACE__] ?? []);
            if ($codebase->classExtends($name, 'PHPUnit\Framework\TestCase') && ($meta['hasInitializers'] ?? false)) {
                $storage->suppressed_issues[] = 'MissingConstructor';

                foreach (self::getDescendants($codebase, $name) as $dependent_name) {
                    $dependent_storage = $codebase->classlike_storage_provider->get($dependent_name);
                    $dependent_storage->suppressed_issues[] = 'MissingConstructor';
                }
            }
        }
    }

    /** @return string[] */
    private static function getDescendants(Codebase $codebase, string $name): array
    {
        if (!$codebase->classlike_storage_provider->has($name)) {
            return [];
        }

        $storage = $codebase->classlike_storage_provider->get($name);
        $ret = [];

        foreach ($storage->dependent_classlikes as $dependent => $_) {
            if ($codebase->classExtends($dependent, $name)) {
                $ret[] = $dependent;
                $ret = array_merge($ret, self::getDescendants($codebase, $dependent));
            }
        }
        return $ret;
    }

    /**
     * {@inheritDoc}
     */
    public static function afterClassLikeVisit(AfterClassLikeVisitEvent $event)
    {
        $class_node = $event->getStmt();
        $class_storage = $event->getStorage();
        $statements_source = $event->getStatementsSource();
        $codebase = $event->getCodebase();

        if (self::hasInitializers($class_storage, $class_node)) {
            $class_storage->custom_metadata[__NAMESPACE__] = ['hasInitializers' => true];
        }

        $file_path = $statements_source->getFilePath();
        $file_storage = $codebase->file_storage_provider->get($file_path);

        foreach ($class_node->getMethods() as $method) {
            $specials = self::getSpecials($method);
            if (!isset($specials['dataProvider'])) {
                continue;
            }
            foreach ($specials['dataProvider'] as $provider) {
                if (false !== strpos($provider, '::')) {
                    [$class_name] = explode('::', $provider);
                    $fq_class_name = Type::getFQCLNFromString($class_name, $statements_source->getAliases());
                    self::queueClassLikeForScanning($codebase, $fq_class_name, $file_path);
                    $file_storage->referenced_classlikes[strtolower($fq_class_name)] = $fq_class_name;
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public static function afterStatementAnalysis(AfterClassLikeAnalysisEvent $event)
    {
        $class_node = $event->getStmt();
        $class_storage = $event->getClasslikeStorage();
        $codebase = $event->getCodebase();
        $statements_source = $event->getStatementsSource();

        if (!$codebase->classExtends($class_storage->name, 'PHPUnit\Framework\TestCase')) {
            return null;
        }

        // This should always pass, we're calling it for the side-effect of adding self-reference
        // in order to
        // 1. Inform Psalm that class is used
        // 2. Make Psalm analyze unused methods
        //
        // Marking class as used is required to get more detailed dead-code analysis (like unused
        // methods). If we instead just suppress UnusedClass, unused methods are not analyzed.
        if (!$codebase->classOrInterfaceExists($class_storage->name, $class_storage->location)) {
            return null;
        }

        foreach ($class_storage->declaring_method_ids as $method_name_lc => $declaring_method_id) {
            $method_name = $codebase->getCasedMethodId($class_storage->name . '::' . $method_name_lc);
            $method_storage = $codebase->methods->getStorage($declaring_method_id);
            [$declaring_method_class, $declaring_method_name] = explode('::', (string) $declaring_method_id);
            $declaring_class_storage = $codebase->classlike_storage_provider->get($declaring_method_class);

            $declaring_class_node = $class_node;
            if ($declaring_class_storage->is_trait) {
                $declaring_class_node = $codebase->classlikes->getTraitNode($declaring_class_storage->name);
            }

            if (!$method_storage->location) {
                continue;
            }

            $stmt_method = $declaring_class_node->getMethod($declaring_method_name);

            if (!$stmt_method) {
                continue;
            }

            $specials = self::getSpecials($stmt_method);

            $is_test = 0 === strpos($method_name_lc, 'test') || isset($specials['test']);
            if (!$is_test) {
                continue; // skip non-test methods
            }

            $codebase->methodExists(
                (string) $declaring_method_id,
                null,
                'PHPUnit\Framework\TestSuite::run'
            );

            if (!isset($specials['dataProvider'])) {
                continue;
            }

            foreach ($specials['dataProvider'] as $line => $provider) {
                $provider_docblock_location = clone $method_storage->location;
                $provider_docblock_location->setCommentLine($line);

                if (false !== strpos($provider, '::')) {
                    [$class_name, $method_id] = explode('::', $provider);
                    $fq_class_name = Type::getFQCLNFromString($class_name, $statements_source->getAliases());

                    if (!$codebase->classOrInterfaceExists($fq_class_name, $provider_docblock_location)) {
                        IssueBuffer::accepts(new Issue\UndefinedClass(
                            'Class ' . $fq_class_name . ' does not exist',
                            $provider_docblock_location,
                            $fq_class_name
                        ));
                        continue;
                    }
                    $apparent_provider_method_name = $fq_class_name . '::' . $method_id;
                } else {
                    $apparent_provider_method_name = $class_storage->name . '::' . $provider;
                }

                $apparent_provider_method_name = preg_replace('/\(\s*\)$/', '', $apparent_provider_method_name);

                $provider_method_id = $codebase->getDeclaringMethodId($apparent_provider_method_name);

                if (null === $provider_method_id) {
                    IssueBuffer::accepts(new Issue\UndefinedMethod(
                        'Provider method ' . $apparent_provider_method_name . ' is not defined',
                        $provider_docblock_location,
                        $apparent_provider_method_name
                    ));
                    continue;
                }

                // methodExists also can mark methods as used (weird, but handy)
                $provider_method_exists = $codebase->methodExists(
                    $provider_method_id,
                    $provider_docblock_location,
                    (string) $declaring_method_id
                );

                if (!$provider_method_exists) {
                    IssueBuffer::accepts(new Issue\UndefinedMethod(
                        'Provider method ' . $apparent_provider_method_name . ' is not defined',
                        $provider_docblock_location,
                        $apparent_provider_method_name
                    ));
                    continue;
                }

                $provider_return_type = $codebase->getMethodReturnType($provider_method_id, $_);

                if (!$provider_return_type) {
                    continue;
                }

                $provider_return_type_string = $provider_return_type->getId();

                $provider_return_type_location = $codebase->getMethodReturnTypeLocation($provider_method_id);
                assert(null !== $provider_return_type_location);

                $expected_provider_return_type = new Type\Atomic\TIterable([
                    Type::getArrayKey(),
                    Type::getArray(),
                ]);

                $non_null_provider_return_types = [];
                foreach (self::getAtomics($provider_return_type) as $type) {
                    // PHPUnit allows returning null from providers and treats it as an empty set
                    // resulting in the test being skipped
                    if ($type instanceof TNull) {
                        continue;
                    }

                    if (!$type->isIterable($codebase)) {
                        IssueBuffer::accepts(new Issue\InvalidReturnType(
                            'Providers must return ' . $expected_provider_return_type->getId()
                            . ', ' . $provider_return_type_string . ' provided',
                            $provider_return_type_location
                        ));
                        continue 2;
                    }
                    $non_null_provider_return_types[] = $type;
                }

                if ([] === $non_null_provider_return_types) {
                    IssueBuffer::accepts(new Issue\InvalidReturnType(
                        'Providers must return ' . $expected_provider_return_type->getId()
                        . ', ' . $provider_return_type_string . ' provided',
                        $provider_return_type_location
                    ));
                    continue;
                }

                // unionize iterable so that instead of array<int,string>|Traversable<object|int>
                // we get iterable<int|object,string|int>
                //
                // TODO: this may get implemented in a future Psalm version, remove it then
                $provider_return_type = self::unionizeIterables(
                    $codebase,
                    new Type\Union($non_null_provider_return_types)
                );

                $provider_key_type_is_compatible = $codebase->isTypeContainedByType(
                    $provider_return_type->type_params[0],
                    $expected_provider_return_type->type_params[0]
                );

                if (!$provider_key_type_is_compatible) {
                    // XXX: isn't array key allowed by the $expected_provider_return_type ?
                    $provider_key_type_is_uncertain =
                        $provider_return_type->type_params[0]->hasMixed()
                        || $provider_return_type->type_params[0]->hasArrayKey();

                    if ($provider_key_type_is_uncertain) {
                        IssueBuffer::accepts(new Issue\MixedInferredReturnType(
                            'Providers must return ' . $expected_provider_return_type->getId()
                            . ', possibly different ' . $provider_return_type_string . ' provided',
                            $provider_return_type_location
                        ));
                    } else {
                        IssueBuffer::accepts(new Issue\InvalidReturnType(
                            'Providers must return ' . $expected_provider_return_type->getId()
                            . ', ' . $provider_return_type_string . ' provided',
                            $provider_return_type_location
                        ));
                    }
                    continue;
                }

                $provider_value_type_is_compatible = $codebase->isTypeContainedByType(
                    $provider_return_type->type_params[1],
                    $expected_provider_return_type->type_params[1]
                );

                if (!$provider_value_type_is_compatible) {
                    if ($provider_return_type->type_params[1]->hasMixed()) {
                        IssueBuffer::accepts(new Issue\MixedInferredReturnType(
                            'Providers must return ' . $expected_provider_return_type->getId()
                            . ', possibly different ' . $provider_return_type_string . ' provided',
                            $provider_return_type_location
                        ));
                    } else {
                        IssueBuffer::accepts(new Issue\InvalidReturnType(
                            'Providers must return ' . $expected_provider_return_type->getId()
                            . ', ' . $provider_return_type_string . ' provided',
                            $provider_return_type_location
                        ));
                    }
                    continue;
                }

                $checkParam =
                static function (
                    Type\Union $potential_argument_type,
                    Type\Union $param_type,
                    bool $is_optional,
                    int $param_offset
                ) use (
                    $codebase,
                    $method_name,
                    $provider_method_id,
                    $provider_return_type_string,
                    $provider_docblock_location
                ): void {
                    $param_type = clone $param_type;
                    if ($is_optional) {
                        $param_type->possibly_undefined = true;
                    }
                    if ($codebase->isTypeContainedByType($potential_argument_type, $param_type)) {
                        // ok
                    } elseif ($codebase->canTypeBeContainedByType($potential_argument_type, $param_type)) {
                        IssueBuffer::accepts(new Issue\PossiblyInvalidArgument(
                            'Argument ' . ($param_offset + 1) . ' of ' . $method_name
                            . ' expects ' . $param_type->getId() . ', '
                            . $potential_argument_type->getId() . ' provided'
                            . ' by ' . $provider_method_id . '():(' . $provider_return_type_string . ')',
                            $provider_docblock_location
                        ));
                    } elseif ($potential_argument_type->possibly_undefined && !$is_optional) {
                        IssueBuffer::accepts(new Issue\InvalidArgument(
                            'Argument ' . ($param_offset + 1) . ' of ' . $method_name
                            . ' has no default value, but possibly undefined '
                            . $potential_argument_type->getId() . ' provided'
                            . ' by ' . $provider_method_id . '():(' . $provider_return_type_string . ')',
                            $provider_docblock_location
                        ));
                    } else {
                        IssueBuffer::accepts(new Issue\InvalidArgument(
                            'Argument ' . ($param_offset + 1) . ' of ' . $method_name
                            . ' expects ' . $param_type->getId() . ', '
                            . $potential_argument_type->getId() . ' provided'
                            . ' by ' . $provider_method_id . '():(' . $provider_return_type_string . ')',
                            $provider_docblock_location
                        ));
                    }
                };

                /** @var Type\Atomic\TArray|Type\Atomic\TKeyedArray|Type\Atomic\TList $dataset_type */
                $dataset_type = self::getAtomics($provider_return_type->type_params[1])['array'];

                if ($dataset_type instanceof Type\Atomic\TArray) {
                    // check that all of the required (?) params accept value type
                    $potential_argument_type = $dataset_type->type_params[1];
                    foreach ($method_storage->params as $param_offset => $param) {
                        if (!$param->type) {
                            continue;
                        }
                        $checkParam($potential_argument_type, $param->type, $param->is_optional, $param_offset);
                    }
                } elseif ($dataset_type instanceof Type\Atomic\TList) {
                    $potential_argument_type = $dataset_type->type_param;
                    foreach ($method_storage->params as $param_offset => $param) {
                        if (!$param->type) {
                            continue;
                        }
                        $checkParam($potential_argument_type, $param->type, $param->is_optional, $param_offset);
                    }
                } else { // TKeyedArray
                    // iterate over all params checking if corresponding value type is acceptable
                    // let's hope properties are sorted in array order
                    $potential_argument_types = array_values($dataset_type->properties);

                    foreach ($method_storage->params as $param_offset => $param) {
                        if (!isset($potential_argument_types[$param_offset])) {
                            // variadics are never required
                            // and they always come last
                            if ($param->is_variadic) {
                                break;
                            }
                            // reached default params, so it's fine, but let's continue
                            // because MisplacedRequiredParam could be suppressed
                            if ($param->is_optional) {
                                continue;
                            }

                            IssueBuffer::accepts(new Issue\TooFewArguments(
                                'Too few arguments for ' . $method_name
                                . ' - expecting at least ' . ($param_offset + 1)
                                . ', but saw ' . count($potential_argument_types)
                                . ' provided by ' . $provider_method_id . '()'
                                .  ':(' . $provider_return_type_string . ')',
                                $provider_docblock_location,
                                $method_name
                            ));
                            break;
                        }
                        $potential_argument_type = $potential_argument_types[$param_offset];

                        $param_type = $param->type === null ? Type::getMixed() : $param->type;
                        if ($param->is_variadic) {
                            $param_types = self::getAtomics($param_type);
                            $variadic_param_type = new Type\Union(array_values($param_types));

                            // check remaining argument types
                            for (; $param_offset < count($potential_argument_types); $param_offset++) {
                                $potential_argument_type = $potential_argument_types[$param_offset];
                                $checkParam(
                                    $potential_argument_type,
                                    $variadic_param_type,
                                    true,
                                    $param_offset
                                );
                            }
                            break;
                        }

                        $checkParam($potential_argument_type, $param_type, $param->is_optional, $param_offset);
                    }
                }
            }
        }
    }

    /** @return non-empty-array<string,Type\Atomic> */
    private static function getAtomics(Type\Union $union): array
    {
        return $union->getAtomicTypes();
    }

    private static function unionizeIterables(Codebase $codebase, Type\Union $iterables): Type\Atomic\TIterable
    {
        /** @var Type\Union[] $key_types */
        $key_types = [];

        /** @var Type\Union[] $value_types */
        $value_types = [];

        foreach (self::getAtomics($iterables) as $type) {
            if (!$type->isIterable($codebase)) {
                throw new RuntimeException('should be iterable');
            }

            if ($type instanceof Type\Atomic\TArray) {
                $key_types[] = $type->type_params[0] ?? Type::getMixed();
                $value_types[] = $type->type_params[1] ?? Type::getMixed();
            } elseif ($type instanceof Type\Atomic\TKeyedArray) {
                $key_types[] = $type->getGenericKeyType();
                $value_types[] = $type->getGenericValueType();
            } elseif ($type instanceof Type\Atomic\TNamedObject || $type instanceof Type\Atomic\TIterable) {
                [$key_types[], $value_types[]] = $codebase->getKeyValueParamsForTraversableObject($type);
            } elseif ($type instanceof Type\Atomic\TList) {
                $key_types[] = Type::getInt();
                $value_types[] = $type->type_param;
            } else {
                throw new RuntimeException('unexpected type');
            }
        }

        $combine =
            /** @param null|Type\Union $a */
            static function ($a, Type\Union $b) use ($codebase): Type\Union {
                return $a ? Type::combineUnionTypes($a, $b, $codebase) : $b;
            };

        return new Type\Atomic\TIterable([
            array_reduce($key_types, $combine),
            array_reduce($value_types, $combine),
        ]);
    }


    private static function hasInitializers(ClassLikeStorage $storage, ClassLike $stmt): bool
    {
        if (isset($storage->methods['setup'])) {
            return true;
        }

        foreach ($storage->methods as $method => $_) {
            $stmt_method = $stmt->getMethod($method);
            if (!$stmt_method) {
                continue;
            }
            if (self::isBeforeInitializer($stmt_method)) {
                return true;
            }
        }
        return false;
    }

    private static function isBeforeInitializer(ClassMethod $method): bool
    {
        $specials = self::getSpecials($method);
        return isset($specials['before']);
    }

    /** @return array<string, array<int,string>> */
    private static function getSpecials(ClassMethod $method): array
    {
        $docblock = $method->getDocComment();
        if (!$docblock) {
            return [];
        }

        try {
            $parsed_comment = self::getParsedComment($docblock);
        } catch (DocblockParseException $e) {
            return [];
        }

        if (!isset($parsed_comment['specials'])) {
            return [];
        }

        return array_map(
            function (array $lines) {
                return array_map('trim', $lines);
            },
            $parsed_comment['specials']
        );
    }

    /** @return array{description:string, specials:array<string,array<int,string>>} */
    private static function getParsedComment(Doc $comment): array
    {
        $parsed_docblock = DocComment::parsePreservingLength($comment);

        $description = $parsed_docblock->description;
        $specials = $parsed_docblock->tags;

        return [
            'description' => $description,
            'specials' => $specials,
        ];
    }

    private static function queueClassLikeForScanning(
        Codebase $codebase,
        string $fq_class_name,
        string $file_path
    ): void {
        if (method_exists($codebase, 'queueClassLikeForScanning')) {
            $codebase->queueClassLikeForScanning($fq_class_name);
        } else {
            /**
             * @psalm-suppress InvalidScalarArgument
             * @psalm-suppress InvalidArgument
             */
            $codebase->scanner->queueClassLikeForScanning($fq_class_name, $file_path);
        }
    }
}
