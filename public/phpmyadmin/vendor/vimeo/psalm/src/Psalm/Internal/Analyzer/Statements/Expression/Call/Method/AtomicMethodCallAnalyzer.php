<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\Call\Method;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeNameOptions;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ArgumentsAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ClassTemplateParamCollector;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Codebase\VariableUseGraph;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Issue\MixedMethodCall;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TEmptyMixed;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyMixed;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function array_keys;
use function array_merge;
use function array_search;
use function array_shift;
use function array_values;
use function count;
use function get_class;
use function reset;
use function strtolower;

/**
 * This is a bunch of complex logic to handle the potential for missing methods,
 * use of intersection types and/or mixins, together with handling for fallback magic
 * methods.
 *
 * The happy path (i.e 99% of method calls) is handled in ExistingAtomicMethodCallAnalyzer
 */
class AtomicMethodCallAnalyzer extends CallAnalyzer
{
    /**
     * @param  TNamedObject|TTemplateParam|null $static_type
     *
     * @psalm-suppress ComplexMethod it's really complex, but unavoidably so
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\MethodCall $stmt,
        Codebase $codebase,
        Context $context,
        Union $lhs_type,
        Atomic $lhs_type_part,
        ?Atomic $static_type,
        bool $is_intersection,
        ?string $lhs_var_id,
        AtomicMethodCallAnalysisResult $result
    ): void {
        if ($lhs_type_part instanceof TTemplateParam
            && !$lhs_type_part->as->isMixed()
        ) {
            $extra_types = $lhs_type_part->extra_types;

            $lhs_type_part = array_values(
                $lhs_type_part->as->getAtomicTypes()
            )[0];

            $lhs_type_part->from_docblock = true;

            if ($lhs_type_part instanceof TNamedObject) {
                $lhs_type_part->extra_types = $extra_types;
            } elseif ($lhs_type_part instanceof TObject && $extra_types) {
                $lhs_type_part = array_shift($extra_types);
                if ($extra_types) {
                    $lhs_type_part->extra_types = $extra_types;
                }
            }

            $result->has_mixed_method_call = true;
        }

        $source = $statements_analyzer->getSource();

        if (!$lhs_type_part instanceof TNamedObject) {
            self::handleInvalidClass(
                $statements_analyzer,
                $codebase,
                $stmt,
                $lhs_type,
                $lhs_type_part,
                $lhs_var_id,
                $context,
                $is_intersection,
                $result
            );

            return;
        }

        if (!$context->collect_initializations
            && !$context->collect_mutations
            && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
            && (!(($parent_source = $statements_analyzer->getSource())
                    instanceof FunctionLikeAnalyzer)
                || !$parent_source->getSource() instanceof TraitAnalyzer)
        ) {
            $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());
        }

        $result->has_valid_method_call_type = true;

        $fq_class_name = $lhs_type_part->value;

        $is_mock = ExpressionAnalyzer::isMock($fq_class_name);

        $result->has_mock = $result->has_mock || $is_mock;

        if ($fq_class_name === 'static') {
            $fq_class_name = (string) $context->self;
        }

        if ($is_mock ||
            $context->isPhantomClass($fq_class_name)
        ) {
            $result->return_type = Type::getMixed();

            ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $stmt->getArgs(),
                null,
                null,
                true,
                $context
            );

            return;
        }

        if ($lhs_var_id === '$this') {
            $does_class_exist = true;
        } else {
            $does_class_exist = ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                $statements_analyzer,
                $fq_class_name,
                new CodeLocation($source, $stmt->var),
                $context->self,
                $context->calling_method_id,
                $statements_analyzer->getSuppressedIssues(),
                new ClassLikeNameOptions(true, false, true, true, $lhs_type_part->from_docblock)
            );
        }

        if (!$does_class_exist) {
            return;
        }

        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

        $result->check_visibility = $result->check_visibility && !$class_storage->override_method_visibility;

        $intersection_types = $lhs_type_part->getIntersectionTypes();

        if (!$stmt->name instanceof PhpParser\Node\Identifier) {
            if (!$context->ignore_variable_method) {
                $codebase->analyzer->addMixedMemberName(
                    strtolower($fq_class_name) . '::',
                    $context->calling_method_id ?: $statements_analyzer->getFileName()
                );
            }

            if ($stmt->isFirstClassCallable()) {
                $return_type_candidate = null;
                $method_name_type = $statements_analyzer->node_data->getType($stmt->name);
                if ($method_name_type && $method_name_type->isSingleStringLiteral()) {
                    $method_identifier = new MethodIdentifier(
                        $fq_class_name,
                        strtolower($method_name_type->getSingleStringLiteral()->value)
                    );
                    //the call to methodExists will register that the method was called from somewhere
                    if ($codebase->methods->methodExists(
                        $method_identifier,
                        $context->calling_method_id,
                        null,
                        $statements_analyzer,
                        $statements_analyzer->getFilePath(),
                        true,
                        $context->insideUse()
                    )) {
                        $method_storage = $codebase->methods->getStorage($method_identifier);

                        $return_type_candidate = new Union([new TClosure(
                            'Closure',
                            $method_storage->params,
                            $method_storage->return_type,
                            $method_storage->pure
                        )]);
                    }
                }

                $statements_analyzer->node_data->setType($stmt, $return_type_candidate ?? Type::getClosure());


                return;
            }

            ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $stmt->getArgs(),
                null,
                null,
                true,
                $context
            );

            $result->return_type = Type::getMixed();
            return;
        }

        $method_name_lc = strtolower($stmt->name->name);

        $method_id = new MethodIdentifier($fq_class_name, $method_name_lc);

        $args = $stmt->isFirstClassCallable() ? [] : $stmt->getArgs();

        $naive_method_id = $method_id;

        // this tells us whether or not we can stay on the happy path
        $naive_method_exists = $codebase->methods->methodExists(
            $method_id,
            $context->calling_method_id,
            $codebase->collect_locations
                ? new CodeLocation($source, $stmt->name)
                : null,
            !$context->collect_initializations
                && !$context->collect_mutations
                ? $statements_analyzer
                : null,
            $statements_analyzer->getFilePath(),
            false,
            $context->insideUse()
        );

        $fake_method_exists = false;

        if (!$naive_method_exists) {
            // if the method doesn't exist we check for any method existence providers
            if ($codebase->methods->existence_provider->has($fq_class_name)) {
                $method_exists = $codebase->methods->existence_provider->doesMethodExist(
                    $fq_class_name,
                    $method_id->method_name,
                    $source,
                    null
                );

                if ($method_exists) {
                    $fake_method_exists = true;
                }
            }

            $naive_method_exists = false;

            // @mixin attributes are an absolute pain! Lots of complexity here,
            // as they can redefine the called class, method id etc.
            if ($class_storage->templatedMixins
                && $lhs_type_part instanceof TGenericObject
                && $class_storage->template_types
            ) {
                [$lhs_type_part, $class_storage, $naive_method_exists, $method_id, $fq_class_name]
                    = self::handleTemplatedMixins(
                        $class_storage,
                        $lhs_type_part,
                        $method_name_lc,
                        $codebase,
                        $context,
                        $method_id,
                        $source,
                        $stmt,
                        $statements_analyzer,
                        $fq_class_name
                    );
            } elseif ($class_storage->mixin_declaring_fqcln
                && $class_storage->namedMixins
            ) {
                [$lhs_type_part, $class_storage, $naive_method_exists, $method_id, $fq_class_name]
                    = self::handleRegularMixins(
                        $class_storage,
                        $lhs_type_part,
                        $method_name_lc,
                        $codebase,
                        $context,
                        $method_id,
                        $source,
                        $stmt,
                        $statements_analyzer,
                        $fq_class_name,
                        $lhs_var_id
                    );
            }
        }

        $all_intersection_return_type = null;
        $all_intersection_existent_method_ids = [];

        // insersection types are also fun, they also complicate matters
        if ($intersection_types) {
            [$all_intersection_return_type, $all_intersection_existent_method_ids]
                = self::getIntersectionReturnType(
                    $statements_analyzer,
                    $stmt,
                    $codebase,
                    $context,
                    $lhs_type,
                    $lhs_type_part,
                    $lhs_var_id,
                    $result,
                    $intersection_types
                );
        }

        if (($fake_method_exists
                && $codebase->methods->methodExists(new MethodIdentifier($fq_class_name, '__call')))
            || !$naive_method_exists
            || !MethodAnalyzer::isMethodVisible(
                $method_id,
                $context,
                $statements_analyzer->getSource()
            )
        ) {
            $interface_has_method = false;

            if ($class_storage->abstract && $class_storage->class_implements) {
                foreach ($class_storage->class_implements as $interface_fqcln_lc => $_) {
                    $interface_storage = $codebase->classlike_storage_provider->get($interface_fqcln_lc);

                    if (isset($interface_storage->methods[$method_name_lc])) {
                        $interface_has_method = true;
                        $fq_class_name = $interface_storage->name;
                        $method_id = new MethodIdentifier(
                            $fq_class_name,
                            $method_name_lc
                        );
                        break;
                    }
                }
            }

            if (!$interface_has_method
                && $codebase->methods->methodExists(
                    new MethodIdentifier($fq_class_name, '__call'),
                    $context->calling_method_id,
                    $codebase->collect_locations
                        ? new CodeLocation($source, $stmt->name)
                        : null,
                    !$context->collect_initializations
                        && !$context->collect_mutations
                        ? $statements_analyzer
                        : null,
                    $statements_analyzer->getFilePath(),
                    true,
                    $context->insideUse()
                )
            ) {
                $new_call_context = MissingMethodCallHandler::handleMagicMethod(
                    $statements_analyzer,
                    $codebase,
                    $stmt,
                    $method_id,
                    $class_storage,
                    $context,
                    $codebase->config,
                    $all_intersection_return_type,
                    $result,
                    $lhs_type_part
                );

                if ($new_call_context) {
                    if ($method_id === $new_call_context->method_id) {
                        return;
                    }

                    $method_id = $new_call_context->method_id;
                    $args = $new_call_context->args;
                } else {
                    return;
                }
            }
        }

        $intersection_method_id = $intersection_types
            ? '(' . $lhs_type_part . ')'  . '::' . $stmt->name->name
            : null;
        $cased_method_id = $fq_class_name . '::' . $stmt->name->name;

        if ($lhs_var_id === '$this'
            && $context->self
            && $fq_class_name !== $context->self
            && $codebase->methods->methodExists(
                new MethodIdentifier($context->self, $method_name_lc)
            )
        ) {
            $method_id = new MethodIdentifier($context->self, $method_name_lc);
            $cased_method_id = $context->self . '::' . $stmt->name->name;
            $fq_class_name = $context->self;
        }

        $source_method_id = $source instanceof FunctionLikeAnalyzer
            ? $source->getId()
            : null;

        $corrected_method_exists = ($naive_method_exists && $method_id === $naive_method_id)
            || ($method_id !== $naive_method_id
                && $codebase->methods->methodExists(
                    $method_id,
                    $context->calling_method_id,
                    $codebase->collect_locations && $method_id !== $source_method_id
                        ? new CodeLocation($source, $stmt->name)
                        : null
                ));

        if (!$corrected_method_exists
            || ($codebase->config->use_phpdoc_method_without_magic_or_parent
                && isset($class_storage->pseudo_methods[$method_name_lc]))
        ) {
            MissingMethodCallHandler::handleMissingOrMagicMethod(
                $statements_analyzer,
                $codebase,
                $stmt,
                $method_id,
                $codebase->interfaceExists($fq_class_name),
                $context,
                $codebase->config,
                $all_intersection_return_type,
                $all_intersection_existent_method_ids,
                $intersection_method_id,
                $cased_method_id,
                $result,
                $lhs_type_part
            );

            return;
        }

        $old_node_data = $statements_analyzer->node_data;

        $return_type_candidate = ExistingAtomicMethodCallAnalyzer::analyze(
            $statements_analyzer,
            $stmt,
            $stmt->name,
            $args,
            $codebase,
            $context,
            $lhs_type_part,
            $static_type,
            $lhs_var_id,
            $method_id,
            $result
        );

        $statements_analyzer->node_data = $old_node_data;

        $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

        $in_call_map = InternalCallMapHandler::inCallMap((string) ($declaring_method_id ?? $method_id));

        if (!$in_call_map) {
            if ($result->check_visibility) {
                $name_code_location = new CodeLocation($statements_analyzer, $stmt->name);

                MethodVisibilityAnalyzer::analyze(
                    $method_id,
                    $context,
                    $statements_analyzer->getSource(),
                    $name_code_location,
                    $statements_analyzer->getSuppressedIssues()
                );
            }
        }

        self::updateResultReturnType(
            $result,
            $return_type_candidate,
            $all_intersection_return_type,
            $codebase
        );
    }

    /**
     * @param  TNamedObject|TTemplateParam $lhs_type_part
     * @param   array<string, Atomic> $intersection_types
     *
     * @return  array{?Union, array<string>}
     */
    private static function getIntersectionReturnType(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\MethodCall $stmt,
        Codebase $codebase,
        Context $context,
        Union $lhs_type,
        Atomic $lhs_type_part,
        ?string $lhs_var_id,
        AtomicMethodCallAnalysisResult $result,
        array $intersection_types
    ): array {
        $all_intersection_return_type = null;
        $all_intersection_existent_method_ids = [];

        foreach ($intersection_types as $intersection_type) {
            $intersection_result = clone $result;

            /** @var ?Union $intersection_result->return_type */
            $intersection_result->return_type = null;

            self::analyze(
                $statements_analyzer,
                $stmt,
                $codebase,
                $context,
                $lhs_type,
                $intersection_type,
                $lhs_type_part,
                true,
                $lhs_var_id,
                $intersection_result
            );

            $result->returns_by_ref = $intersection_result->returns_by_ref;
            $result->has_mock = $intersection_result->has_mock;
            $result->has_valid_method_call_type = $intersection_result->has_valid_method_call_type;
            $result->has_mixed_method_call = $intersection_result->has_mixed_method_call;
            $result->invalid_method_call_types = $intersection_result->invalid_method_call_types;
            $result->check_visibility = $intersection_result->check_visibility;
            $result->too_many_arguments = $intersection_result->too_many_arguments;

            $all_intersection_existent_method_ids = array_merge(
                $all_intersection_existent_method_ids,
                $intersection_result->existent_method_ids
            );

            if ($intersection_result->return_type) {
                if (!$all_intersection_return_type || $all_intersection_return_type->isMixed()) {
                    $all_intersection_return_type = $intersection_result->return_type;
                } else {
                    $all_intersection_return_type = Type::intersectUnionTypes(
                        $all_intersection_return_type,
                        $intersection_result->return_type,
                        $codebase
                    ) ?? Type::getMixed();
                }
            }
        }

        return [$all_intersection_return_type, $all_intersection_existent_method_ids];
    }

    private static function updateResultReturnType(
        AtomicMethodCallAnalysisResult $result,
        Union $return_type_candidate,
        ?Union $all_intersection_return_type,
        Codebase $codebase
    ): void {
        if ($all_intersection_return_type) {
            $return_type_candidate = Type::intersectUnionTypes(
                $all_intersection_return_type,
                $return_type_candidate,
                $codebase
            ) ?? Type::getMixed();
        }

        $result->return_type = Type::combineUnionTypes($return_type_candidate, $result->return_type);
    }

    private static function handleInvalidClass(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PhpParser\Node\Expr\MethodCall $stmt,
        Union $lhs_type,
        Atomic $lhs_type_part,
        ?string $lhs_var_id,
        Context $context,
        bool $is_intersection,
        AtomicMethodCallAnalysisResult $result
    ): void {
        switch (get_class($lhs_type_part)) {
            case TNull::class:
            case TFalse::class:
                // handled above
                return;

            case TTemplateParam::class:
            case TEmptyMixed::class:
            case TEmpty::class:
            case TMixed::class:
            case TNonEmptyMixed::class:
            case TObject::class:
            case TObjectWithProperties::class:
                if (!$context->collect_initializations
                    && !$context->collect_mutations
                    && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                    && (!(($parent_source = $statements_analyzer->getSource())
                            instanceof FunctionLikeAnalyzer)
                        || !$parent_source->getSource() instanceof TraitAnalyzer)
                ) {
                    $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
                }

                $result->has_mixed_method_call = true;

                if ($lhs_type_part instanceof TObjectWithProperties
                    && $stmt->name instanceof PhpParser\Node\Identifier
                    && isset($lhs_type_part->methods[$stmt->name->name])
                ) {
                    $result->existent_method_ids[] = $lhs_type_part->methods[$stmt->name->name];
                } elseif (!$is_intersection) {
                    if ($stmt->name instanceof PhpParser\Node\Identifier) {
                        $codebase->analyzer->addMixedMemberName(
                            strtolower($stmt->name->name),
                            $context->calling_method_id ?: $statements_analyzer->getFileName()
                        );
                    }

                    if ($context->check_methods) {
                        $message = 'Cannot determine the type of the object'
                            . ' on the left hand side of this expression';

                        if ($lhs_var_id) {
                            $message = 'Cannot determine the type of ' . $lhs_var_id;

                            if ($stmt->name instanceof PhpParser\Node\Identifier) {
                                $message .= ' when calling method ' . $stmt->name->name;
                            }
                        }

                        $origin_locations = [];

                        if ($statements_analyzer->data_flow_graph instanceof VariableUseGraph) {
                            foreach ($lhs_type->parent_nodes as $parent_node) {
                                $origin_locations = array_merge(
                                    $origin_locations,
                                    $statements_analyzer->data_flow_graph->getOriginLocations($parent_node)
                                );
                            }
                        }

                        $origin_location = count($origin_locations) === 1 ? reset($origin_locations) : null;

                        $name_code_location = new CodeLocation($statements_analyzer, $stmt->name);

                        if ($origin_location && $origin_location->getHash() === $name_code_location->getHash()) {
                            $origin_location = null;
                        }

                        IssueBuffer::maybeAdd(
                            new MixedMethodCall(
                                $message,
                                $name_code_location,
                                $origin_location
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        );
                    }
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

                $result->return_type = Type::getMixed();
                return;

            default:
                $result->invalid_method_call_types[] = (string)$lhs_type_part;
                return;
        }
    }

    /**
     * @param lowercase-string $method_name_lc
     * @return array{TNamedObject, ClassLikeStorage, bool, MethodIdentifier, string}
     */
    private static function handleTemplatedMixins(
        ClassLikeStorage $class_storage,
        TNamedObject $lhs_type_part,
        string $method_name_lc,
        Codebase $codebase,
        Context $context,
        MethodIdentifier $method_id,
        StatementsSource $source,
        PhpParser\Node\Expr\MethodCall $stmt,
        StatementsAnalyzer $statements_analyzer,
        string $fq_class_name
    ): array {
        $naive_method_exists = false;

        if ($class_storage->templatedMixins
            && $lhs_type_part instanceof TGenericObject
            && $class_storage->template_types
        ) {
            $template_type_keys = array_keys($class_storage->template_types);

            foreach ($class_storage->templatedMixins as $mixin) {
                $param_position = array_search(
                    $mixin->param_name,
                    $template_type_keys
                );

                if ($param_position !== false
                    && isset($lhs_type_part->type_params[$param_position])
                ) {
                    $current_type_param = $lhs_type_part->type_params[$param_position];
                    if ($current_type_param->isSingle()) {
                        $lhs_type_part_new = array_values(
                            $current_type_param->getAtomicTypes()
                        )[0];

                        if ($lhs_type_part_new instanceof TNamedObject) {
                            $new_method_id = new MethodIdentifier(
                                $lhs_type_part_new->value,
                                $method_name_lc
                            );

                            $mixin_class_storage = $codebase->classlike_storage_provider->get(
                                $lhs_type_part_new->value
                            );

                            if ($codebase->methods->methodExists(
                                $new_method_id,
                                $context->calling_method_id,
                                $codebase->collect_locations
                                    ? new CodeLocation($source, $stmt->name)
                                    : null,
                                !$context->collect_initializations
                                && !$context->collect_mutations
                                    ? $statements_analyzer
                                    : null,
                                $statements_analyzer->getFilePath(),
                                true,
                                $context->insideUse()
                            )) {
                                $lhs_type_part = clone $lhs_type_part_new;
                                $class_storage = $mixin_class_storage;

                                $naive_method_exists = true;
                                $method_id = $new_method_id;
                            } elseif (isset($mixin_class_storage->pseudo_methods[$method_name_lc])) {
                                $lhs_type_part = clone $lhs_type_part_new;
                                $class_storage = $mixin_class_storage;
                                $method_id = $new_method_id;
                            }
                        }
                    }
                }
            }
        }

        return [
            $lhs_type_part,
            $class_storage,
            $naive_method_exists,
            $method_id,
            $fq_class_name
        ];
    }

    /**
     * @param lowercase-string $method_name_lc
     * @return array{TNamedObject, ClassLikeStorage, bool, MethodIdentifier, string}
     */
    private static function handleRegularMixins(
        ClassLikeStorage $class_storage,
        TNamedObject $lhs_type_part,
        string $method_name_lc,
        Codebase $codebase,
        Context $context,
        MethodIdentifier $method_id,
        StatementsSource $source,
        PhpParser\Node\Expr\MethodCall $stmt,
        StatementsAnalyzer $statements_analyzer,
        string $fq_class_name,
        ?string $lhs_var_id
    ): array {
        $naive_method_exists = false;

        foreach ($class_storage->namedMixins as $mixin) {
            if (!$class_storage->mixin_declaring_fqcln) {
                continue;
            }

            $new_method_id = new MethodIdentifier(
                $mixin->value,
                $method_name_lc
            );

            if ($codebase->methods->methodExists(
                $new_method_id,
                $context->calling_method_id,
                $codebase->collect_locations
                    ? new CodeLocation($source, $stmt->name)
                    : null,
                !$context->collect_initializations
                && !$context->collect_mutations
                    ? $statements_analyzer
                    : null,
                $statements_analyzer->getFilePath(),
                true,
                $context->insideUse()
            )) {
                $mixin_declaring_class_storage = $codebase->classlike_storage_provider->get(
                    $class_storage->mixin_declaring_fqcln
                );

                $mixin_class_template_params = ClassTemplateParamCollector::collect(
                    $codebase,
                    $mixin_declaring_class_storage,
                    $codebase->classlike_storage_provider->get($fq_class_name),
                    null,
                    $lhs_type_part,
                    $lhs_var_id === '$this'
                );

                $lhs_type_part = clone $mixin;

                $lhs_type_part->replaceTemplateTypesWithArgTypes(
                    new TemplateResult([], $mixin_class_template_params ?: []),
                    $codebase
                );

                $lhs_type_expanded = TypeExpander::expandUnion(
                    $codebase,
                    new Union([$lhs_type_part]),
                    $mixin_declaring_class_storage->name,
                    $fq_class_name,
                    $class_storage->parent_class,
                    true,
                    false,
                    $class_storage->final
                );

                $new_lhs_type_part = $lhs_type_expanded->getSingleAtomic();

                if ($new_lhs_type_part instanceof TNamedObject) {
                    $lhs_type_part = $new_lhs_type_part;
                }

                $mixin_class_storage = $codebase->classlike_storage_provider->get($mixin->value);

                $fq_class_name = $mixin_class_storage->name;
                $mixin_class_storage->mixin_declaring_fqcln = $class_storage->mixin_declaring_fqcln;
                $class_storage = $mixin_class_storage;
                $naive_method_exists = true;
                $method_id = $new_method_id;
            }
        }

        return [
            $lhs_type_part,
            $class_storage,
            $naive_method_exists,
            $method_id,
            $fq_class_name
        ];
    }
}
