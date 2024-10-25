<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\AssertionFinder;
use Psalm\Internal\Analyzer\Statements\Expression\Call\FunctionCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\MethodCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\StaticCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\ArrayType;
use Psalm\Node\Expr\VirtualArrayDimFetch;
use Psalm\Node\Expr\VirtualFuncCall;
use Psalm\Node\Expr\VirtualMethodCall;
use Psalm\Node\Expr\VirtualStaticCall;
use Psalm\Node\Expr\VirtualVariable;
use Psalm\Node\Name\VirtualFullyQualified;
use Psalm\Node\VirtualArg;
use Psalm\Node\VirtualIdentifier;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyList;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_map;
use function array_shift;
use function array_slice;
use function count;
use function explode;
use function in_array;
use function mt_rand;
use function reset;
use function strpos;
use function substr;

class ArrayMapReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['array_map'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        $context = $event->getContext();
        if (!$statements_source instanceof StatementsAnalyzer) {
            return Type::getMixed();
        }

        $function_call_arg = $call_args[0] ?? null;

        $function_call_type = $function_call_arg
            ? $statements_source->node_data->getType($function_call_arg->value)
            : null;

        if ($function_call_type && $function_call_type->isNull()) {
            array_shift($call_args);

            $array_arg_types = [];

            foreach ($call_args as $call_arg) {
                $call_arg_type = $statements_source->node_data->getType($call_arg->value);

                if ($call_arg_type) {
                    $array_arg_types[] = clone $call_arg_type;
                } else {
                    $array_arg_types[] = Type::getMixed();
                    break;
                }
            }

            if ($array_arg_types) {
                return new Union([new TKeyedArray($array_arg_types)]);
            }

            return Type::getArray();
        }

        $array_arg = $call_args[1] ?? null;

        if (!$array_arg) {
            return Type::getArray();
        }

        $array_arg_atomic_type = null;
        $array_arg_type = null;

        if ($array_arg_union_type = $statements_source->node_data->getType($array_arg->value)) {
            $arg_types = $array_arg_union_type->getAtomicTypes();

            if (isset($arg_types['array'])) {
                $array_arg_atomic_type = $arg_types['array'];
                $array_arg_type = ArrayType::infer($array_arg_atomic_type);
            }
        }

        $generic_key_type = null;
        $mapping_return_type = null;

        if ($function_call_arg && $function_call_type) {
            if (count($call_args) === 2) {
                $generic_key_type = $array_arg_type->key ?? Type::getArrayKey();
            } else {
                $generic_key_type = Type::getInt();
            }

            if ($function_call_type->hasCallableType()) {
                $closure_types = $function_call_type->getClosureTypes() ?: $function_call_type->getCallableTypes();
                $closure_atomic_type = reset($closure_types);

                $closure_return_type = $closure_atomic_type->return_type ?: Type::getMixed();

                if ($closure_return_type->isVoid()) {
                    $closure_return_type = Type::getNull();
                }

                $mapping_return_type = clone $closure_return_type;
            } elseif ($function_call_arg->value instanceof PhpParser\Node\Scalar\String_
                || $function_call_arg->value instanceof PhpParser\Node\Expr\Array_
                || $function_call_arg->value instanceof PhpParser\Node\Expr\BinaryOp\Concat
            ) {
                $mapping_function_ids = CallAnalyzer::getFunctionIdsFromCallableArg(
                    $statements_source,
                    $function_call_arg->value
                );

                if ($mapping_function_ids) {
                    $mapping_return_type = self::getReturnTypeFromMappingIds(
                        $statements_source,
                        $mapping_function_ids,
                        $context,
                        $function_call_arg,
                        array_slice($call_args, 1)
                    );
                }

                if ($function_call_arg->value instanceof PhpParser\Node\Expr\Array_
                    && isset($function_call_arg->value->items[0])
                    && isset($function_call_arg->value->items[1])
                    && $function_call_arg->value->items[1]->value instanceof PhpParser\Node\Scalar\String_
                    && $function_call_arg->value->items[0]->value instanceof PhpParser\Node\Expr\Variable
                    && ($variable_type
                        = $statements_source->node_data->getType($function_call_arg->value->items[0]->value))
                ) {
                    $fake_method_call = null;

                    foreach ($variable_type->getAtomicTypes() as $variable_atomic_type) {
                        if ($variable_atomic_type instanceof TTemplateParam
                            || $variable_atomic_type instanceof TTemplateParamClass
                        ) {
                            $fake_method_call = new VirtualStaticCall(
                                $function_call_arg->value->items[0]->value,
                                $function_call_arg->value->items[1]->value->value,
                                []
                            );
                        }
                    }

                    if ($fake_method_call) {
                        $fake_method_return_type = self::executeFakeCall(
                            $statements_source,
                            $fake_method_call,
                            $context
                        );

                        if ($fake_method_return_type) {
                            $mapping_return_type = $fake_method_return_type;
                        }
                    }
                }
            }
        }

        if ($mapping_return_type && $generic_key_type) {
            if ($array_arg_atomic_type instanceof TKeyedArray && count($call_args) === 2) {
                $atomic_type = new TKeyedArray(
                    array_map(
                        /**
                        * @return Union
                        */
                        function (Union $_) use ($mapping_return_type): Union {
                            return clone $mapping_return_type;
                        },
                        $array_arg_atomic_type->properties
                    )
                );
                $atomic_type->is_list = $array_arg_atomic_type->is_list;
                $atomic_type->sealed = $array_arg_atomic_type->sealed;
                $atomic_type->previous_key_type = $array_arg_atomic_type->previous_key_type;
                $atomic_type->previous_value_type = $mapping_return_type;

                return new Union([$atomic_type]);
            }

            if ($array_arg_atomic_type instanceof TList
                || count($call_args) !== 2
            ) {
                if ($array_arg_atomic_type instanceof TNonEmptyList) {
                    return new Union([
                        new TNonEmptyList(
                            $mapping_return_type
                        ),
                    ]);
                }

                return new Union([
                    new TList(
                        $mapping_return_type
                    ),
                ]);
            }

            if ($array_arg_atomic_type instanceof TNonEmptyArray) {
                return new Union([
                    new TNonEmptyArray([
                        $generic_key_type,
                        $mapping_return_type,
                    ]),
                ]);
            }

            return new Union([
                new TArray([
                    $generic_key_type,
                    $mapping_return_type,
                ])
            ]);
        }

        return count($call_args) === 2 && !($array_arg_type->is_list ?? false)
            ? new Union([
                new TArray([
                    $array_arg_type->key ?? Type::getArrayKey(),
                    Type::getMixed(),
                ])
            ])
            : Type::getList();
    }

    /**
     * @param-out array<string, array<array<int, string>>>|null $assertions
     */
    private static function executeFakeCall(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $fake_call,
        Context $context,
        ?array &$assertions = null
    ): ?Union {
        $old_data_provider = $statements_analyzer->node_data;

        $statements_analyzer->node_data = clone $statements_analyzer->node_data;

        $suppressed_issues = $statements_analyzer->getSuppressedIssues();

        if (!in_array('PossiblyInvalidMethodCall', $suppressed_issues, true)) {
            $statements_analyzer->addSuppressedIssues(['PossiblyInvalidMethodCall']);
        }

        if (!in_array('MixedArrayOffset', $suppressed_issues, true)) {
            $statements_analyzer->addSuppressedIssues(['MixedArrayOffset']);
        }

        $was_inside_call = $context->inside_call;

        $context->inside_call = true;

        if ($fake_call instanceof PhpParser\Node\Expr\StaticCall) {
            StaticCallAnalyzer::analyze(
                $statements_analyzer,
                $fake_call,
                $context
            );
        } elseif ($fake_call instanceof PhpParser\Node\Expr\MethodCall) {
            MethodCallAnalyzer::analyze(
                $statements_analyzer,
                $fake_call,
                $context
            );
        } elseif ($fake_call instanceof PhpParser\Node\Expr\FuncCall) {
            FunctionCallAnalyzer::analyze(
                $statements_analyzer,
                $fake_call,
                $context
            );
        } else {
            throw new UnexpectedValueException('UnrecognizedCall');
        }

        $codebase = $statements_analyzer->getCodebase();

        if ($assertions !== null) {
            $anded_assertions = AssertionFinder::scrapeAssertions(
                $fake_call,
                null,
                $statements_analyzer,
                $codebase
            );

            $assertions = $anded_assertions[0] ?? [];
        }

        $context->inside_call = $was_inside_call;

        if (!in_array('PossiblyInvalidMethodCall', $suppressed_issues, true)) {
            $statements_analyzer->removeSuppressedIssues(['PossiblyInvalidMethodCall']);
        }

        if (!in_array('MixedArrayOffset', $suppressed_issues, true)) {
            $statements_analyzer->removeSuppressedIssues(['MixedArrayOffset']);
        }

        $return_type = $statements_analyzer->node_data->getType($fake_call) ?? null;

        $statements_analyzer->node_data = $old_data_provider;

        return $return_type;
    }

    /**
     * @param non-empty-array<int, string> $mapping_function_ids
     * @param list<PhpParser\Node\Arg> $array_args
     * @param int|null $fake_var_discriminator Set the fake variable id to a known value with the discriminator
     *                                         as a substring, and don't clear it from the context.
     * @param-out array<string, array<array<int, string>>>|null $assertions
     */
    public static function getReturnTypeFromMappingIds(
        StatementsAnalyzer $statements_source,
        array $mapping_function_ids,
        Context $context,
        PhpParser\Node\Arg $function_call_arg,
        array $array_args,
        ?array &$assertions = null,
        ?int $fake_var_discriminator = null
    ): Union {
        $mapping_return_type = null;

        $codebase = $statements_source->getCodebase();

        $clean_context = false;

        foreach ($mapping_function_ids as $mapping_function_id) {
            $mapping_function_id_parts = explode('&', $mapping_function_id);

            if ($fake_var_discriminator === null) {
                $fake_var_discriminator = mt_rand();
                $clean_context = true;
            }

            foreach ($mapping_function_id_parts as $mapping_function_id_part) {
                $fake_args = [];

                foreach ($array_args as $array_arg) {
                    $fake_args[] = new VirtualArg(
                        new VirtualArrayDimFetch(
                            $array_arg->value,
                            new VirtualVariable(
                                "__fake_{$fake_var_discriminator}_offset_var__",
                                $array_arg->value->getAttributes()
                            ),
                            $array_arg->value->getAttributes()
                        ),
                        false,
                        false,
                        $array_arg->getAttributes()
                    );
                }

                if (strpos($mapping_function_id_part, '::') !== false) {
                    $is_instance = false;

                    if ($mapping_function_id_part[0] === '$') {
                        $mapping_function_id_part = substr($mapping_function_id_part, 1);
                        $is_instance = true;
                    }

                    $method_id_parts = explode('::', $mapping_function_id_part);
                    [$callable_fq_class_name, $callable_method_name] = $method_id_parts;

                    if ($is_instance) {
                        $fake_method_call = new VirtualMethodCall(
                            new VirtualVariable(
                                "__fake_{$fake_var_discriminator}_method_call_var__",
                                $function_call_arg->getAttributes()
                            ),
                            new VirtualIdentifier(
                                $callable_method_name,
                                $function_call_arg->getAttributes()
                            ),
                            $fake_args,
                            $function_call_arg->getAttributes()
                        );

                        $lhs_instance_type = null;

                        $callable_type = $statements_source->node_data->getType($function_call_arg->value);

                        if ($callable_type) {
                            foreach ($callable_type->getAtomicTypes() as $atomic_type) {
                                if ($atomic_type instanceof TKeyedArray
                                    && count($atomic_type->properties) === 2
                                    && isset($atomic_type->properties[0])
                                ) {
                                    $lhs_instance_type = clone $atomic_type->properties[0];
                                }
                            }
                        }

                        $context->vars_in_scope["\$__fake_{$fake_var_discriminator}_offset_var__"] = Type::getMixed();
                        $context->vars_in_scope["\$__fake_{$fake_var_discriminator}_method_call_var__"] =
                            $lhs_instance_type ?: new Union([new TNamedObject($callable_fq_class_name)]);
                    } else {
                        $fake_method_call = new VirtualStaticCall(
                            new VirtualFullyQualified(
                                $callable_fq_class_name,
                                $function_call_arg->getAttributes()
                            ),
                            new VirtualIdentifier(
                                $callable_method_name,
                                $function_call_arg->getAttributes()
                            ),
                            $fake_args,
                            $function_call_arg->getAttributes()
                        );

                        $context->vars_in_scope["\$__fake_{$fake_var_discriminator}_offset_var__"] = Type::getMixed();
                    }

                    $fake_method_return_type = self::executeFakeCall(
                        $statements_source,
                        $fake_method_call,
                        $context,
                        $assertions
                    );

                    $function_id_return_type = $fake_method_return_type ?? Type::getMixed();
                } else {
                    $fake_function_call = new VirtualFuncCall(
                        new VirtualFullyQualified(
                            $mapping_function_id_part,
                            $function_call_arg->getAttributes()
                        ),
                        $fake_args,
                        $function_call_arg->getAttributes()
                    );

                    $context->vars_in_scope["\$__fake_{$fake_var_discriminator}_offset_var__"] = Type::getMixed();

                    $fake_function_return_type = self::executeFakeCall(
                        $statements_source,
                        $fake_function_call,
                        $context,
                        $assertions
                    );

                    $function_id_return_type = $fake_function_return_type ?? Type::getMixed();
                }
            }

            if ($clean_context) {
                self::cleanContext($context, $fake_var_discriminator);
            }

            $fake_var_discriminator = null;

            $mapping_return_type = Type::combineUnionTypes(
                $function_id_return_type,
                $mapping_return_type,
                $codebase
            );
        }

        return $mapping_return_type;
    }

    public static function cleanContext(Context $context, int $fake_var_discriminator): void
    {
        foreach ($context->vars_in_scope as $var_in_scope => $_) {
            if (strpos($var_in_scope, "__fake_{$fake_var_discriminator}_") !== false) {
                unset($context->vars_in_scope[$var_in_scope]);
            }
        }
    }
}
