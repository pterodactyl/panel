<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Algebra;
use Psalm\Internal\Algebra\FormulaGenerator;
use Psalm\Internal\Analyzer\AlgebraAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\DataFlow\TaintSink;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\Comparator\CallableTypeComparator;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Issue\DeprecatedFunction;
use Psalm\Issue\ImpureFunctionCall;
use Psalm\Issue\InvalidFunctionCall;
use Psalm\Issue\MixedFunctionCall;
use Psalm\Issue\NullFunctionCall;
use Psalm\Issue\PossiblyInvalidFunctionCall;
use Psalm\Issue\PossiblyNullFunctionCall;
use Psalm\Issue\UnusedFunctionCall;
use Psalm\IssueBuffer;
use Psalm\Node\Expr\VirtualFuncCall;
use Psalm\Node\Expr\VirtualMethodCall;
use Psalm\Node\Name\VirtualFullyQualified;
use Psalm\Node\VirtualArg;
use Psalm\Node\VirtualIdentifier;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Plugin\EventHandler\Event\AfterEveryFunctionCallAnalysisEvent;
use Psalm\Storage\Assertion;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\FunctionStorage;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Reconciler;
use Psalm\Type\TaintKind;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_map;
use function array_merge;
use function array_shift;
use function array_slice;
use function count;
use function explode;
use function implode;
use function in_array;
use function preg_replace;
use function reset;
use function spl_object_id;
use function strpos;
use function strtolower;

/**
 * @internal
 */
class FunctionCallAnalyzer extends CallAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\FuncCall $stmt,
        Context $context
    ): bool {
        $function_name = $stmt->name;

        $codebase = $statements_analyzer->getCodebase();

        $code_location = new CodeLocation($statements_analyzer->getSource(), $stmt);
        $config = $codebase->config;

        $is_first_class_callable = $stmt->isFirstClassCallable();

        $real_stmt = $stmt;

        if ($function_name instanceof PhpParser\Node\Name
            && !$is_first_class_callable
            && isset($stmt->getArgs()[0])
            && !$stmt->getArgs()[0]->unpack
        ) {
            $original_function_id = implode('\\', $function_name->parts);

            if ($original_function_id === 'call_user_func') {
                $other_args = array_slice($stmt->getArgs(), 1);

                $function_name = $stmt->getArgs()[0]->value;

                $stmt = new VirtualFuncCall(
                    $function_name,
                    $other_args,
                    $stmt->getAttributes()
                );
            }

            if ($original_function_id === 'call_user_func_array' && isset($stmt->getArgs()[1])) {
                $function_name = $stmt->getArgs()[0]->value;

                $stmt = new VirtualFuncCall(
                    $function_name,
                    [new VirtualArg($stmt->getArgs()[1]->value, false, true)],
                    $stmt->getAttributes()
                );
            }
        }

        if ($function_name instanceof PhpParser\Node\Expr) {
            $function_call_info = self::getAnalyzeNamedExpression(
                $statements_analyzer,
                $stmt,
                $real_stmt,
                $function_name,
                $context
            );

            if ($function_call_info->function_exists === false) {
                return true;
            }

            if ($function_call_info->new_function_name) {
                $function_name = $function_call_info->new_function_name;
            }
        } else {
            $function_call_info = self::handleNamedFunction(
                $statements_analyzer,
                $stmt,
                $function_name,
                $context,
                $code_location
            );

            if (!$function_call_info->function_exists) {
                return true;
            }
        }

        $set_inside_conditional = false;

        if ($function_name instanceof PhpParser\Node\Name
            && $function_name->parts === ['assert']
            && !$context->inside_conditional
        ) {
            $context->inside_conditional = true;
            $set_inside_conditional = true;
        }

        if (!$is_first_class_callable) {
            $template_result = null;

            if (isset($function_call_info->function_storage->template_types)) {
                $template_result = new TemplateResult($function_call_info->function_storage->template_types ?: [], []);
            }

            ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $stmt->getArgs(),
                $function_call_info->function_params,
                $function_call_info->function_id,
                $function_call_info->allow_named_args,
                $context,
                $template_result
            );
        }

        if ($set_inside_conditional) {
            $context->inside_conditional = false;
        }

        $function_callable = null;

        if (!$is_first_class_callable
            && $function_name instanceof PhpParser\Node\Name
            && $function_call_info->function_id
        ) {
            if (!$function_call_info->is_stubbed && $function_call_info->in_call_map) {
                $function_callable = InternalCallMapHandler::getCallableFromCallMapById(
                    $codebase,
                    $function_call_info->function_id,
                    $stmt->getArgs(),
                    $statements_analyzer->node_data
                );

                $function_call_info->function_params = $function_callable->params;
            }
        }

        $template_result = new TemplateResult([], []);

        // do this here to allow closure param checks
        if (!$is_first_class_callable && $function_call_info->function_params !== null) {
            ArgumentsAnalyzer::checkArgumentsMatch(
                $statements_analyzer,
                $stmt->getArgs(),
                $function_call_info->function_id,
                $function_call_info->function_params,
                $function_call_info->function_storage,
                null,
                $template_result,
                $code_location,
                $context
            );
        }

        CallAnalyzer::checkTemplateResult(
            $statements_analyzer,
            $template_result,
            $code_location,
            $function_call_info->function_id
        );

        if ($function_name instanceof PhpParser\Node\Name && $function_call_info->function_id) {
            $stmt_type = FunctionCallReturnTypeFetcher::fetch(
                $statements_analyzer,
                $codebase,
                $stmt,
                $function_name,
                $function_call_info->function_id,
                $function_call_info->in_call_map,
                $function_call_info->is_stubbed,
                $function_call_info->function_storage,
                $function_callable,
                $template_result,
                $context
            );

            $statements_analyzer->node_data->setType($real_stmt, $stmt_type);

            if ($stmt_type->isNever()) {
                $context->has_returned = true;
            }

            $event = new AfterEveryFunctionCallAnalysisEvent(
                $stmt,
                $function_call_info->function_id,
                $context,
                $statements_analyzer->getSource(),
                $codebase
            );

            $config->eventDispatcher->dispatchAfterEveryFunctionCallAnalysis($event);

            if ($is_first_class_callable) {
                return true;
            }
        }

        if ($is_first_class_callable) {
            $type_provider = $statements_analyzer->getNodeTypeProvider();
            $closure_types = [];

            if ($input_type = $type_provider->getType($function_name)) {
                foreach ($input_type->getAtomicTypes() as $atomic_type) {
                    $candidate_callable = CallableTypeComparator::getCallableFromAtomic(
                        $codebase,
                        $atomic_type,
                        null,
                        $statements_analyzer
                    );

                    if ($candidate_callable) {
                        $closure_types[] = new TClosure(
                            'Closure',
                            $candidate_callable->params,
                            $candidate_callable->return_type,
                            $candidate_callable->is_pure
                        );
                    }
                }
            }

            if ($closure_types) {
                $stmt_type = TypeCombiner::combine($closure_types, $codebase);
            } else {
                $stmt_type = Type::getClosure();
            }

            $statements_analyzer->node_data->setType($real_stmt, $stmt_type);

            return true;
        }

        foreach ($function_call_info->defined_constants as $const_name => $const_type) {
            $context->constants[$const_name] = clone $const_type;
            $context->vars_in_scope[$const_name] = clone $const_type;
        }

        foreach ($function_call_info->global_variables as $var_id => $_) {
            $context->vars_in_scope[$var_id] = Type::getMixed();
            $context->vars_possibly_in_scope[$var_id] = true;
        }

        if ($function_name instanceof PhpParser\Node\Name
            && $function_name->parts === ['assert']
            && isset($stmt->getArgs()[0])
        ) {
            self::processAssertFunctionEffects(
                $statements_analyzer,
                $codebase,
                $stmt,
                $stmt->getArgs()[0],
                $context
            );
        }

        if ($codebase->store_node_types
            && !$context->collect_initializations
            && !$context->collect_mutations
            && ($stmt_type = $statements_analyzer->node_data->getType($real_stmt))
        ) {
            $codebase->analyzer->addNodeType(
                $statements_analyzer->getFilePath(),
                $stmt,
                $stmt_type->getId()
            );
        }

        self::checkFunctionCallPurity(
            $statements_analyzer,
            $codebase,
            $stmt,
            $function_name,
            $function_call_info,
            $context
        );

        if ($function_call_info->function_storage) {
            $inferred_lower_bounds = $template_result->lower_bounds;

            if ($function_call_info->function_storage->assertions && $function_name instanceof PhpParser\Node\Name) {
                self::applyAssertionsToContext(
                    $function_name,
                    null,
                    $function_call_info->function_storage->assertions,
                    $stmt->getArgs(),
                    $inferred_lower_bounds,
                    $context,
                    $statements_analyzer
                );
            }

            if ($function_call_info->function_storage->if_true_assertions) {
                $statements_analyzer->node_data->setIfTrueAssertions(
                    $stmt,
                    array_map(
                        function (Assertion $assertion) use ($inferred_lower_bounds, $codebase): Assertion {
                            return $assertion->getUntemplatedCopy($inferred_lower_bounds ?: [], null, $codebase);
                        },
                        $function_call_info->function_storage->if_true_assertions
                    )
                );
            }

            if ($function_call_info->function_storage->if_false_assertions) {
                $statements_analyzer->node_data->setIfFalseAssertions(
                    $stmt,
                    array_map(
                        function (Assertion $assertion) use ($inferred_lower_bounds, $codebase): Assertion {
                            return $assertion->getUntemplatedCopy($inferred_lower_bounds ?: [], null, $codebase);
                        },
                        $function_call_info->function_storage->if_false_assertions
                    )
                );
            }

            if ($function_call_info->function_storage->deprecated && $function_call_info->function_id) {
                IssueBuffer::maybeAdd(
                    new DeprecatedFunction(
                        'The function ' . $function_call_info->function_id . ' has been marked as deprecated',
                        $code_location,
                        $function_call_info->function_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            }
        }

        if ($function_call_info->byref_uses) {
            foreach ($function_call_info->byref_uses as $byref_use_var => $_) {
                $context->vars_in_scope['$' . $byref_use_var] = Type::getMixed();
                $context->vars_possibly_in_scope['$' . $byref_use_var] = true;
            }
        }

        if ($function_name instanceof PhpParser\Node\Name && $function_call_info->function_id) {
            NamedFunctionCallHandler::handle(
                $statements_analyzer,
                $codebase,
                $stmt,
                $real_stmt,
                $function_name,
                strtolower($function_call_info->function_id),
                $context
            );
        }

        if (!$statements_analyzer->node_data->getType($real_stmt)) {
            $statements_analyzer->node_data->setType($real_stmt, Type::getMixed());
        }

        return true;
    }

    private static function handleNamedFunction(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\FuncCall $stmt,
        PhpParser\Node\Name $function_name,
        Context $context,
        CodeLocation $code_location
    ): FunctionCallInfo {
        $function_call_info = new FunctionCallInfo();

        $codebase = $statements_analyzer->getCodebase();
        $codebase_functions = $codebase->functions;

        $original_function_id = implode('\\', $function_name->parts);

        if (!$function_name instanceof PhpParser\Node\Name\FullyQualified) {
            $function_call_info->function_id = $codebase_functions->getFullyQualifiedFunctionNameFromString(
                $original_function_id,
                $statements_analyzer
            );
        } else {
            $function_call_info->function_id = $original_function_id;
        }

        $namespaced_function_exists = $codebase_functions->functionExists(
            $statements_analyzer,
            strtolower($function_call_info->function_id)
        );

        if (!$namespaced_function_exists
            && !$function_name instanceof PhpParser\Node\Name\FullyQualified
        ) {
            $function_call_info->in_call_map = InternalCallMapHandler::inCallMap($original_function_id);
            $function_call_info->is_stubbed = $codebase_functions->hasStubbedFunction($original_function_id);

            if ($function_call_info->is_stubbed || $function_call_info->in_call_map) {
                $function_call_info->function_id = $original_function_id;
            }
        } else {
            $function_call_info->in_call_map = InternalCallMapHandler::inCallMap($function_call_info->function_id);
            $function_call_info->is_stubbed = $codebase_functions->hasStubbedFunction($function_call_info->function_id);
        }

        $function_call_info->function_exists
            = $function_call_info->is_stubbed || $function_call_info->in_call_map || $namespaced_function_exists;

        if ($function_call_info->function_exists
            && $codebase->store_node_types
            && !$context->collect_initializations
            && !$context->collect_mutations
        ) {
            ArgumentMapPopulator::recordArgumentPositions(
                $statements_analyzer,
                $stmt,
                $codebase,
                $function_call_info->function_id
            );
        }

        $is_predefined = true;

        $is_maybe_root_function = !$function_name instanceof PhpParser\Node\Name\FullyQualified
            && count($function_name->parts) === 1;

        $args = $stmt->isFirstClassCallable() ? [] : $stmt->getArgs();

        if (!$function_call_info->in_call_map) {
            $predefined_functions = $codebase->config->getPredefinedFunctions();
            $is_predefined = isset($predefined_functions[strtolower($original_function_id)])
                || isset($predefined_functions[strtolower($function_call_info->function_id)]);

            if ($context->check_functions) {
                if (self::checkFunctionExists(
                    $statements_analyzer,
                    $function_call_info->function_id,
                    $code_location,
                    $is_maybe_root_function
                ) === false) {
                    if ($args && ArgumentsAnalyzer::analyze(
                        $statements_analyzer,
                        $args,
                        null,
                        null,
                        true,
                        $context
                    ) === false) {
                        // fall through
                    }

                    return $function_call_info;
                }

                $function_call_info->function_exists = true;
            }
        } else {
            $function_call_info->function_exists = true;
        }

        $function_call_info->function_params = null;
        $function_call_info->defined_constants = [];
        $function_call_info->global_variables = [];
        $args = $stmt->isFirstClassCallable() ? [] : $stmt->getArgs();

        if ($function_call_info->function_exists) {
            if ($codebase->functions->params_provider->has($function_call_info->function_id)) {
                $function_call_info->function_params = $codebase->functions->params_provider->getFunctionParams(
                    $statements_analyzer,
                    $function_call_info->function_id,
                    $args,
                    null,
                    $code_location
                );
            }

            if ($function_call_info->function_params === null) {
                if (!$function_call_info->in_call_map || $function_call_info->is_stubbed) {
                    try {
                        $function_call_info->function_storage = $function_storage = $codebase_functions->getStorage(
                            $statements_analyzer,
                            strtolower($function_call_info->function_id)
                        );

                        $function_call_info->function_params = $function_call_info->function_storage->params;

                        if (!$function_storage->allow_named_arg_calls) {
                            $function_call_info->allow_named_args = false;
                        }

                        if (!$is_predefined) {
                            $function_call_info->defined_constants = $function_storage->defined_constants;
                            $function_call_info->global_variables = $function_storage->global_variables;
                        }
                    } catch (UnexpectedValueException $e) {
                        $function_call_info->function_params = [
                            new FunctionLikeParameter('args', false, null, null, null, false, false, true)
                        ];
                    }
                } else {
                    $function_callable = InternalCallMapHandler::getCallableFromCallMapById(
                        $codebase,
                        $function_call_info->function_id,
                        $args,
                        $statements_analyzer->node_data
                    );

                    $function_call_info->function_params = $function_callable->params;
                }
            }

            if ($codebase->store_node_types
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                $codebase->analyzer->addNodeReference(
                    $statements_analyzer->getFilePath(),
                    $function_name,
                    $function_call_info->function_id . '()'
                );
            }
        }

        return $function_call_info;
    }

    private static function getAnalyzeNamedExpression(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\FuncCall $stmt,
        PhpParser\Node\Expr\FuncCall $real_stmt,
        PhpParser\Node\Expr $function_name,
        Context $context
    ): FunctionCallInfo {
        $function_call_info = new FunctionCallInfo();

        $codebase = $statements_analyzer->getCodebase();

        $was_in_call = $context->inside_call;
        $context->inside_call = true;

        if (ExpressionAnalyzer::analyze($statements_analyzer, $function_name, $context) === false) {
            $context->inside_call = $was_in_call;

            return $function_call_info;
        }

        $context->inside_call = $was_in_call;

        $function_call_info->byref_uses = [];

        if ($stmt_name_type = $statements_analyzer->node_data->getType($function_name)) {
            if ($stmt_name_type->isNull()) {
                IssueBuffer::maybeAdd(
                    new NullFunctionCall(
                        'Cannot call function on null value',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );

                return $function_call_info;
            }

            if ($stmt_name_type->isNullable()) {
                IssueBuffer::maybeAdd(
                    new PossiblyNullFunctionCall(
                        'Cannot call function on possibly null value',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            }

            $invalid_function_call_types = [];
            $has_valid_function_call_type = false;

            $var_atomic_types = $stmt_name_type->getAtomicTypes();

            while ($var_atomic_types) {
                $var_type_part = array_shift($var_atomic_types);

                if ($var_type_part instanceof TTemplateParam) {
                    $var_atomic_types = array_merge($var_atomic_types, $var_type_part->as->getAtomicTypes());
                    continue;
                }

                if ($var_type_part instanceof TClosure || $var_type_part instanceof TCallable) {
                    if (!$var_type_part->is_pure) {
                        if ($context->pure || $context->mutation_free) {
                            IssueBuffer::maybeAdd(
                                new ImpureFunctionCall(
                                    'Cannot call an impure function from a mutation-free context',
                                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            );
                        }

                        if (!$function_call_info->function_storage) {
                            $function_call_info->function_storage = new FunctionStorage();
                        }

                        $function_call_info->function_storage->pure = false;
                        $function_call_info->function_storage->mutation_free = false;
                    }

                    $function_call_info->function_params = $var_type_part->params;

                    if (($stmt_type = $statements_analyzer->node_data->getType($real_stmt))
                        && $var_type_part->return_type
                    ) {
                        $statements_analyzer->node_data->setType(
                            $real_stmt,
                            Type::combineUnionTypes(
                                $stmt_type,
                                $var_type_part->return_type
                            )
                        );
                    } else {
                        $statements_analyzer->node_data->setType(
                            $real_stmt,
                            $var_type_part->return_type ?? Type::getMixed()
                        );
                    }

                    if ($var_type_part instanceof TClosure) {
                        $function_call_info->byref_uses += $var_type_part->byref_uses;
                    }

                    $function_call_info->function_exists = true;
                    $has_valid_function_call_type = true;
                } elseif ($var_type_part instanceof TMixed) {
                    $has_valid_function_call_type = true;

                    IssueBuffer::maybeAdd(
                        new MixedFunctionCall(
                            'Cannot call function on ' . $var_type_part->getId(),
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );
                } elseif ($var_type_part instanceof TCallableObject
                    || $var_type_part instanceof TCallableString
                    || ($var_type_part instanceof TNamedObject && $var_type_part->value === 'Closure')
                    || ($var_type_part instanceof TObjectWithProperties && isset($var_type_part->methods['__invoke']))
                ) {
                    // this is fine
                    $has_valid_function_call_type = true;
                } elseif ($var_type_part instanceof TString
                    || $var_type_part instanceof TArray
                    || $var_type_part instanceof TList
                    || ($var_type_part instanceof TKeyedArray
                        && count($var_type_part->properties) === 2)
                ) {
                    $potential_method_id = null;

                    if ($var_type_part instanceof TKeyedArray) {
                        $potential_method_id = CallableTypeComparator::getCallableMethodIdFromTKeyedArray(
                            $var_type_part,
                            $codebase,
                            $context->calling_method_id,
                            $statements_analyzer->getFilePath()
                        );

                        if ($potential_method_id === 'not-callable') {
                            $potential_method_id = null;
                        }
                    } elseif ($var_type_part instanceof TLiteralString) {
                        if (!$var_type_part->value) {
                            $invalid_function_call_types[] = '\'\'';
                            continue;
                        }

                        if (strpos($var_type_part->value, '::')) {
                            $parts = explode('::', strtolower($var_type_part->value));
                            $fq_class_name = $parts[0];
                            $fq_class_name = preg_replace('/^\\\/', '', $fq_class_name, 1);
                            $potential_method_id = new MethodIdentifier($fq_class_name, $parts[1]);
                        } else {
                            $function_call_info->new_function_name = new VirtualFullyQualified(
                                $var_type_part->value,
                                $function_name->getAttributes()
                            );
                        }
                    }

                    if ($potential_method_id) {
                        $codebase->methods->methodExists(
                            $potential_method_id,
                            $context->calling_method_id,
                            null,
                            $statements_analyzer,
                            $statements_analyzer->getFilePath()
                        );
                    }

                    // this is also kind of fine
                    $has_valid_function_call_type = true;
                } elseif ($var_type_part instanceof TNull) {
                    // handled above
                } elseif (!$var_type_part instanceof TNamedObject
                    || !$codebase->classlikes->classOrInterfaceExists($var_type_part->value)
                    || !$codebase->methods->methodExists(
                        new MethodIdentifier(
                            $var_type_part->value,
                            '__invoke'
                        )
                    )
                ) {
                    $invalid_function_call_types[] = (string)$var_type_part;
                } else {
                    self::analyzeInvokeCall(
                        $statements_analyzer,
                        $stmt,
                        $real_stmt,
                        $function_name,
                        $context,
                        $var_type_part
                    );
                }
            }

            if ($invalid_function_call_types) {
                $var_type_part = reset($invalid_function_call_types);

                if ($has_valid_function_call_type) {
                    IssueBuffer::maybeAdd(
                        new PossiblyInvalidFunctionCall(
                            'Cannot treat type ' . $var_type_part . ' as callable',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );
                } else {
                    IssueBuffer::maybeAdd(
                        new InvalidFunctionCall(
                            'Cannot treat type ' . $var_type_part . ' as callable',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );
                }

                return $function_call_info;
            }

            if ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph
                && $stmt_name_type->parent_nodes
                && $stmt_name_type->hasString()
                && !in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
            ) {
                $arg_location = new CodeLocation($statements_analyzer->getSource(), $function_name);

                $custom_call_sink = TaintSink::getForMethodArgument(
                    'variable-call',
                    'variable-call',
                    0,
                    $arg_location,
                    $arg_location
                );

                $custom_call_sink->taints = [TaintKind::INPUT_CALLABLE];

                $statements_analyzer->data_flow_graph->addSink($custom_call_sink);

                $event = new AddRemoveTaintsEvent($stmt, $context, $statements_analyzer, $codebase);

                $added_taints = $codebase->config->eventDispatcher->dispatchAddTaints($event);
                $removed_taints = $codebase->config->eventDispatcher->dispatchRemoveTaints($event);

                foreach ($stmt_name_type->parent_nodes as $parent_node) {
                    $statements_analyzer->data_flow_graph->addPath(
                        $parent_node,
                        $custom_call_sink,
                        'call',
                        $added_taints,
                        $removed_taints
                    );
                }
            }
        }

        if (!$statements_analyzer->node_data->getType($real_stmt)) {
            $statements_analyzer->node_data->setType($real_stmt, Type::getMixed());
        }

        return $function_call_info;
    }

    private static function analyzeInvokeCall(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\FuncCall $stmt,
        PhpParser\Node\Expr\FuncCall $real_stmt,
        PhpParser\Node\Expr $function_name,
        Context $context,
        Atomic $atomic_type
    ): void {
        $old_data_provider = $statements_analyzer->node_data;

        $statements_analyzer->node_data = clone $statements_analyzer->node_data;

        $fake_method_call = new VirtualMethodCall(
            $function_name,
            new VirtualIdentifier('__invoke', $function_name->getAttributes()),
            $stmt->args
        );

        $suppressed_issues = $statements_analyzer->getSuppressedIssues();

        if (!in_array('InternalMethod', $suppressed_issues, true)) {
            $statements_analyzer->addSuppressedIssues(['InternalMethod']);
        }

        $statements_analyzer->node_data->setType($function_name, new Union([$atomic_type]));

        MethodCallAnalyzer::analyze(
            $statements_analyzer,
            $fake_method_call,
            $context,
            false
        );

        if (!in_array('InternalMethod', $suppressed_issues, true)) {
            $statements_analyzer->removeSuppressedIssues(['InternalMethod']);
        }

        $fake_method_call_type = $statements_analyzer->node_data->getType($fake_method_call);

        $statements_analyzer->node_data = $old_data_provider;

        if ($stmt_type = $statements_analyzer->node_data->getType($real_stmt)) {
            $statements_analyzer->node_data->setType(
                $real_stmt,
                Type::combineUnionTypes(
                    $fake_method_call_type ?? Type::getMixed(),
                    $stmt_type
                )
            );
        } else {
            $statements_analyzer->node_data->setType(
                $real_stmt,
                $fake_method_call_type ?? Type::getMixed()
            );
        }
    }

    private static function processAssertFunctionEffects(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PhpParser\Node\Expr\FuncCall $stmt,
        PhpParser\Node\Arg $first_arg,
        Context $context
    ): void {
        $first_arg_value_id = spl_object_id($first_arg->value);

        $assert_clauses = FormulaGenerator::getFormula(
            $first_arg_value_id,
            $first_arg_value_id,
            $first_arg->value,
            $context->self,
            $statements_analyzer,
            $codebase
        );

        AlgebraAnalyzer::checkForParadox(
            $context->clauses,
            $assert_clauses,
            $statements_analyzer,
            $stmt,
            []
        );

        $simplified_clauses = Algebra::simplifyCNF(array_merge($context->clauses, $assert_clauses));

        $assert_type_assertions = Algebra::getTruthsFromFormula($simplified_clauses);

        $changed_var_ids = [];

        if ($assert_type_assertions) {
            // while in an and, we allow scope to boil over to support
            // statements of the form if ($x && $x->foo())
            $op_vars_in_scope = Reconciler::reconcileKeyedTypes(
                $assert_type_assertions,
                $assert_type_assertions,
                $context->vars_in_scope,
                $changed_var_ids,
                array_map(
                    function ($_): bool {
                        return true;
                    },
                    $assert_type_assertions
                ),
                $statements_analyzer,
                $statements_analyzer->getTemplateTypeMap() ?: [],
                $context->inside_loop,
                new CodeLocation($statements_analyzer->getSource(), $stmt)
            );

            foreach ($changed_var_ids as $var_id => $_) {
                $first_appearance = $statements_analyzer->getFirstAppearance($var_id);

                if ($first_appearance
                    && isset($context->vars_in_scope[$var_id])
                    && $context->vars_in_scope[$var_id]->hasMixed()
                ) {
                    if (!$context->collect_initializations
                        && !$context->collect_mutations
                        && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                        && (!(($parent_source = $statements_analyzer->getSource())
                                    instanceof FunctionLikeAnalyzer)
                                || !$parent_source->getSource() instanceof TraitAnalyzer)
                    ) {
                        $codebase->analyzer->decrementMixedCount($statements_analyzer->getFilePath());
                    }

                    IssueBuffer::remove(
                        $statements_analyzer->getFilePath(),
                        'MixedAssignment',
                        $first_appearance->raw_file_start
                    );
                }

                if (isset($op_vars_in_scope[$var_id])) {
                    $op_vars_in_scope[$var_id]->from_docblock = true;
                }
            }

            $context->vars_in_scope = $op_vars_in_scope;
        }

        if ($changed_var_ids) {
            $simplified_clauses = Context::removeReconciledClauses($simplified_clauses, $changed_var_ids)[0];
        }

        $context->clauses = $simplified_clauses;
    }

    private static function checkFunctionCallPurity(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PhpParser\Node\Expr\FuncCall $stmt,
        PhpParser\Node $function_name,
        FunctionCallInfo $function_call_info,
        Context $context
    ): void {
        $config = $codebase->config;

        if (!$context->collect_initializations
            && !$context->collect_mutations
            && ($context->mutation_free
                || $context->external_mutation_free
                || $codebase->find_unused_variables
                || !$config->remember_property_assignments_after_call
                || ($statements_analyzer->getSource() instanceof FunctionLikeAnalyzer
                    && $statements_analyzer->getSource()->track_mutations))
        ) {
            $must_use = true;

            $callmap_function_pure = $function_call_info->function_id && $function_call_info->in_call_map
                ? $codebase->functions->isCallMapFunctionPure(
                    $codebase,
                    $statements_analyzer->node_data,
                    $function_call_info->function_id,
                    $stmt->isFirstClassCallable() ? [] : $stmt->getArgs(),
                    $must_use
                )
                : null;

            if ((!$function_call_info->in_call_map
                    && $function_call_info->function_storage
                    && !$function_call_info->function_storage->pure
                    && !$function_call_info->function_storage->mutation_free)
                || ($callmap_function_pure === false)
            ) {
                if ($context->mutation_free || $context->external_mutation_free) {
                    IssueBuffer::maybeAdd(
                        new ImpureFunctionCall(
                            'Cannot call an impure function from a mutation-free context',
                            new CodeLocation($statements_analyzer, $function_name)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );
                } elseif ($statements_analyzer->getSource() instanceof FunctionLikeAnalyzer
                    && $statements_analyzer->getSource()->track_mutations
                ) {
                    $statements_analyzer->getSource()->inferred_has_mutation = true;
                    $statements_analyzer->getSource()->inferred_impure = true;
                }

                if (!$config->remember_property_assignments_after_call) {
                    $context->removeMutableObjectVars();
                }
            } elseif ($function_call_info->function_id
                && (($function_call_info->function_storage
                        && $function_call_info->function_storage->pure
                        && !$function_call_info->function_storage->assertions
                        && $must_use)
                    || ($callmap_function_pure === true && $must_use))
                && $codebase->find_unused_variables
                && !$context->inside_conditional
                && !$context->inside_unset
            ) {
                /**
                 * If a function is pure, and has the return type of 'no-return',
                 * it's okay to dismiss it's return value.
                 */
                if (!$context->insideUse()
                    && !self::callUsesByReferenceArguments($function_call_info, $stmt)
                    && !(
                        $function_call_info->function_storage &&
                        $function_call_info->function_storage->return_type &&
                        $function_call_info->function_storage->return_type->isNever()
                    )
                ) {
                    IssueBuffer::maybeAdd(
                        new UnusedFunctionCall(
                            'The call to ' . $function_call_info->function_id . ' is not used',
                            new CodeLocation($statements_analyzer, $function_name),
                            $function_call_info->function_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );
                } else {
                    $stmt->setAttribute('pure', true);
                }
            }
        }
    }

    private static function callUsesByReferenceArguments(
        FunctionCallInfo $function_call_info,
        PhpParser\Node\Expr\FuncCall $stmt
    ): bool {
        // If the function doesn't have any by-reference parameters
        // we shouldn't look any further.
        if (!$function_call_info->hasByReferenceParameters() || null === $function_call_info->function_params) {
            return false;
        }

        $parameters = $function_call_info->function_params;

        // If no arguments were passed
        if (0 === count($stmt->getArgs())) {
            return false;
        }

        foreach ($stmt->getArgs() as $index => $argument) {
            $parameter = null;
            if (null !== $argument->name) {
                $argument_name = $argument->name->toString();
                foreach ($parameters as $param) {
                    if ($param->name === $argument_name) {
                        $parameter = $param;
                        break;
                    }
                }
            } else {
                $parameter = $parameters[$index] ?? null;
            }

            if ($parameter && $parameter->by_ref) {
                return true;
            }
        }

        return false;
    }
}
