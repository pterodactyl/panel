<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\Call\Method;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ArgumentsAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ClassTemplateParamCollector;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Node\Expr\VirtualArray;
use Psalm\Node\Expr\VirtualArrayItem;
use Psalm\Node\Scalar\VirtualString;
use Psalm\Node\VirtualArg;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Union;

use function array_map;
use function array_merge;

class MissingMethodCallHandler
{
    public static function handleMagicMethod(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PhpParser\Node\Expr\MethodCall $stmt,
        MethodIdentifier $method_id,
        ClassLikeStorage $class_storage,
        Context $context,
        Config $config,
        ?Union $all_intersection_return_type,
        AtomicMethodCallAnalysisResult $result,
        ?Atomic $lhs_type_part
    ): ?AtomicCallContext {
        $fq_class_name = $method_id->fq_class_name;
        $method_name_lc = $method_id->method_name;

        if ($stmt->isFirstClassCallable()) {
            if (isset($class_storage->pseudo_methods[$method_name_lc])) {
                $result->has_valid_method_call_type = true;
                $result->existent_method_ids[] = $method_id->__toString();
                $result->return_type = self::createFirstClassCallableReturnType(
                    $class_storage->pseudo_methods[$method_name_lc]
                );
            } else {
                $result->non_existent_magic_method_ids[] = $method_id->__toString();
                $result->return_type = self::createFirstClassCallableReturnType();
            }

            return null;
        }

        if ($codebase->methods->return_type_provider->has($fq_class_name)) {
            $return_type_candidate = $codebase->methods->return_type_provider->getReturnType(
                $statements_analyzer,
                $method_id->fq_class_name,
                $method_id->method_name,
                $stmt,
                $context,
                new CodeLocation($statements_analyzer->getSource(), $stmt->name)
            );

            if ($return_type_candidate) {
                if ($all_intersection_return_type) {
                    $return_type_candidate = Type::intersectUnionTypes(
                        $all_intersection_return_type,
                        $return_type_candidate,
                        $codebase
                    ) ?? Type::getMixed();
                }

                $result->return_type = Type::combineUnionTypes(
                    $return_type_candidate,
                    $result->return_type,
                    $codebase
                );

                CallAnalyzer::checkMethodArgs(
                    $method_id,
                    $stmt->getArgs(),
                    null,
                    $context,
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                    $statements_analyzer
                );

                return null;
            }
        }

        $found_method_and_class_storage = self::findPseudoMethodAndClassStorages(
            $codebase,
            $class_storage,
            $method_name_lc
        );

        if ($found_method_and_class_storage) {
            $result->has_valid_method_call_type = true;
            $result->existent_method_ids[] = $method_id->__toString();

            [$pseudo_method_storage, $defining_class_storage] = $found_method_and_class_storage;

            $found_generic_params = ClassTemplateParamCollector::collect(
                $codebase,
                $defining_class_storage,
                $class_storage,
                $method_name_lc,
                $lhs_type_part,
                !$statements_analyzer->isStatic() && $method_id->fq_class_name === $context->self
            );

            ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $stmt->getArgs(),
                $pseudo_method_storage->params,
                (string) $method_id,
                true,
                $context,
                $found_generic_params ? new TemplateResult([], $found_generic_params) : null
            );

            ArgumentsAnalyzer::checkArgumentsMatch(
                $statements_analyzer,
                $stmt->getArgs(),
                null,
                $pseudo_method_storage->params,
                $pseudo_method_storage,
                null,
                $found_generic_params ? new TemplateResult([], $found_generic_params) : null,
                new CodeLocation($statements_analyzer, $stmt),
                $context
            );

            if ($pseudo_method_storage->return_type) {
                $return_type_candidate = clone $pseudo_method_storage->return_type;

                if ($found_generic_params) {
                    TemplateInferredTypeReplacer::replace(
                        $return_type_candidate,
                        new TemplateResult([], $found_generic_params),
                        $codebase
                    );
                }

                $return_type_candidate = TypeExpander::expandUnion(
                    $codebase,
                    $return_type_candidate,
                    $defining_class_storage->name,
                    $lhs_type_part instanceof Atomic\TNamedObject ? $lhs_type_part : $fq_class_name,
                    $defining_class_storage->parent_class
                );

                if ($all_intersection_return_type) {
                    $return_type_candidate = Type::intersectUnionTypes(
                        $all_intersection_return_type,
                        $return_type_candidate,
                        $codebase
                    ) ?? Type::getMixed();
                }

                $result->return_type = Type::combineUnionTypes(
                    $return_type_candidate,
                    $result->return_type,
                    $codebase
                );

                return null;
            }
        } elseif ($all_intersection_return_type === null) {
            ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $stmt->getArgs(),
                null,
                null,
                true,
                $context
            );

            if ($class_storage->sealed_methods || $config->seal_all_methods) {
                $result->non_existent_magic_method_ids[] = $method_id->__toString();

                return null;
            }
        }

        $result->has_valid_method_call_type = true;
        $result->existent_method_ids[] = $method_id->__toString();

        $array_values = array_map(
            /**
             * @return PhpParser\Node\Expr\ArrayItem
             */
            function (PhpParser\Node\Arg $arg): PhpParser\Node\Expr\ArrayItem {
                return new VirtualArrayItem(
                    $arg->value,
                    null,
                    false,
                    $arg->getAttributes()
                );
            },
            $stmt->getArgs()
        );

        $statements_analyzer->node_data = clone $statements_analyzer->node_data;

        return new AtomicCallContext(
            new MethodIdentifier($fq_class_name, '__call'),
            [
                new VirtualArg(
                    new VirtualString($method_name_lc),
                    false,
                    false,
                    $stmt->getAttributes()
                ),
                new VirtualArg(
                    new VirtualArray(
                        $array_values,
                        $stmt->getAttributes()
                    ),
                    false,
                    false,
                    $stmt->getAttributes()
                ),
            ]
        );
    }

    /**
     * @param array<string> $all_intersection_existent_method_ids
     */
    public static function handleMissingOrMagicMethod(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PhpParser\Node\Expr\MethodCall $stmt,
        MethodIdentifier $method_id,
        bool $is_interface,
        Context $context,
        Config $config,
        ?Union $all_intersection_return_type,
        array $all_intersection_existent_method_ids,
        ?string $intersection_method_id,
        string $cased_method_id,
        AtomicMethodCallAnalysisResult $result,
        ?Atomic $lhs_type_part
    ): void {
        $fq_class_name = $method_id->fq_class_name;
        $method_name_lc = $method_id->method_name;

        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

        $found_method_and_class_storage = self::findPseudoMethodAndClassStorages(
            $codebase,
            $class_storage,
            $method_name_lc
        );

        if (($is_interface || $config->use_phpdoc_method_without_magic_or_parent)
            && $found_method_and_class_storage
        ) {
            $result->has_valid_method_call_type = true;
            $result->existent_method_ids[] = $method_id->__toString();

            [$pseudo_method_storage, $defining_class_storage] = $found_method_and_class_storage;

            if ($stmt->isFirstClassCallable()) {
                $result->return_type = self::createFirstClassCallableReturnType($pseudo_method_storage);
                return;
            }

            $found_generic_params = ClassTemplateParamCollector::collect(
                $codebase,
                $defining_class_storage,
                $class_storage,
                $method_name_lc,
                $lhs_type_part,
                !$statements_analyzer->isStatic() && $method_id->fq_class_name === $context->self
            );

            if (ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $stmt->getArgs(),
                $pseudo_method_storage->params,
                (string) $method_id,
                true,
                $context,
                $found_generic_params ? new TemplateResult([], $found_generic_params) : null
            ) === false) {
                return;
            }

            if (ArgumentsAnalyzer::checkArgumentsMatch(
                $statements_analyzer,
                $stmt->getArgs(),
                null,
                $pseudo_method_storage->params,
                $pseudo_method_storage,
                null,
                $found_generic_params ? new TemplateResult([], $found_generic_params) : null,
                new CodeLocation($statements_analyzer, $stmt->name),
                $context
            ) === false) {
                return;
            }

            if ($pseudo_method_storage->return_type) {
                $return_type_candidate = clone $pseudo_method_storage->return_type;

                if ($found_generic_params) {
                    TemplateInferredTypeReplacer::replace(
                        $return_type_candidate,
                        new TemplateResult([], $found_generic_params),
                        $codebase
                    );
                }

                if ($all_intersection_return_type) {
                    $return_type_candidate = Type::intersectUnionTypes(
                        $all_intersection_return_type,
                        $return_type_candidate,
                        $codebase
                    ) ?? Type::getMixed();
                }

                $return_type_candidate = TypeExpander::expandUnion(
                    $codebase,
                    $return_type_candidate,
                    $defining_class_storage->name,
                    $lhs_type_part instanceof Atomic\TNamedObject ? $lhs_type_part : $fq_class_name,
                    $defining_class_storage->parent_class,
                    true,
                    false,
                    $class_storage->final
                );

                $result->return_type = Type::combineUnionTypes($return_type_candidate, $result->return_type);

                return;
            }

            $result->return_type = Type::getMixed();

            return;
        }

        if ($stmt->isFirstClassCallable()) {
            $result->non_existent_class_method_ids[] = $method_id->__toString();
            $result->return_type = self::createFirstClassCallableReturnType();
            return;
        }

        if (ArgumentsAnalyzer::analyze(
            $statements_analyzer,
            $stmt->getArgs(),
            null,
            null,
            true,
            $context
        ) === false) {
            return;
        }

        if ($all_intersection_return_type && $all_intersection_existent_method_ids) {
            $result->existent_method_ids = array_merge(
                $result->existent_method_ids,
                $all_intersection_existent_method_ids
            );

            $result->return_type = Type::combineUnionTypes($all_intersection_return_type, $result->return_type);

            return;
        }

        if ((!$is_interface && !$config->use_phpdoc_method_without_magic_or_parent)
            || !isset($class_storage->pseudo_methods[$method_name_lc])
        ) {
            if ($is_interface) {
                $result->non_existent_interface_method_ids[] = $intersection_method_id ?: $cased_method_id;
            } else {
                $result->non_existent_class_method_ids[] = $intersection_method_id ?: $cased_method_id;
            }
        }
    }

    private static function createFirstClassCallableReturnType(?MethodStorage $method_storage = null): Union
    {
        if ($method_storage) {
            return new Union([new TClosure(
                'Closure',
                $method_storage->params,
                $method_storage->return_type,
                $method_storage->pure
            )]);
        }

        return Type::getClosure();
    }

    /**
     * Try to find matching pseudo method over ancestors (including interfaces).
     *
     * Returns the pseudo method if exists, with its defining class storage.
     * If the method is not declared, null is returned.
     *
     * @param Codebase $codebase
     * @param ClassLikeStorage $static_class_storage The called class
     * @param lowercase-string $method_name_lc
     *
     * @return array{MethodStorage, ClassLikeStorage}
     */
    private static function findPseudoMethodAndClassStorages(
        Codebase $codebase,
        ClassLikeStorage $static_class_storage,
        string $method_name_lc
    ): ?array {
        if (isset($static_class_storage->declaring_pseudo_method_ids[$method_name_lc])) {
            $method_id = $static_class_storage->declaring_pseudo_method_ids[$method_name_lc];
            $class_storage = $codebase->classlikes->getStorageFor($method_id->fq_class_name);

            if ($class_storage && isset($class_storage->pseudo_methods[$method_name_lc])) {
                return [$class_storage->pseudo_methods[$method_name_lc], $class_storage];
            }
        }

        if ($pseudo_method_storage = $static_class_storage->pseudo_methods[$method_name_lc] ?? null) {
            return [$pseudo_method_storage, $static_class_storage];
        }

        $ancestors = $static_class_storage->class_implements;

        foreach ($ancestors as $fq_class_name => $_) {
            $class_storage = $codebase->classlikes->getStorageFor($fq_class_name);

            if ($class_storage && isset($class_storage->pseudo_methods[$method_name_lc])) {
                return [
                    $class_storage->pseudo_methods[$method_name_lc],
                    $class_storage
                ];
            }
        }

        return null;
    }
}
