<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\Call\Method;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Assignment\InstancePropertyAssignmentAnalyzer as AssignmentAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Issue\ImpureMethodCall;
use Psalm\Issue\UnusedMethodCall;
use Psalm\IssueBuffer;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Type;

class MethodCallPurityAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PhpParser\Node\Expr\MethodCall $stmt,
        ?string $lhs_var_id,
        string $cased_method_id,
        MethodIdentifier $method_id,
        MethodStorage $method_storage,
        ClassLikeStorage $class_storage,
        Context $context,
        Config $config,
        AtomicMethodCallAnalysisResult $result
    ): void {
        $method_pure_compatible = $method_storage->external_mutation_free
            && $statements_analyzer->node_data->isPureCompatible($stmt->var);

        if ($context->pure
            && !$method_storage->mutation_free
            && !$method_pure_compatible
        ) {
            IssueBuffer::maybeAdd(
                new ImpureMethodCall(
                    'Cannot call a non-mutation-free method '
                        . $cased_method_id . ' from a pure context',
                    new CodeLocation($statements_analyzer, $stmt->name)
                ),
                $statements_analyzer->getSuppressedIssues()
            );
        } elseif ($context->mutation_free
            && !$method_storage->mutation_free
            && !$method_pure_compatible
        ) {
            IssueBuffer::maybeAdd(
                new ImpureMethodCall(
                    'Cannot call a possibly-mutating method '
                        . $cased_method_id . ' from a mutation-free context',
                    new CodeLocation($statements_analyzer, $stmt->name)
                ),
                $statements_analyzer->getSuppressedIssues()
            );
        } elseif ($context->external_mutation_free
            && !$method_storage->mutation_free
            && $method_id->fq_class_name !== $context->self
            && !$method_pure_compatible
        ) {
            IssueBuffer::maybeAdd(
                new ImpureMethodCall(
                    'Cannot call a possibly-mutating method '
                        . $cased_method_id . ' from a mutation-free context',
                    new CodeLocation($statements_analyzer, $stmt->name)
                ),
                $statements_analyzer->getSuppressedIssues()
            );
        } elseif (($method_storage->mutation_free
                || ($method_storage->external_mutation_free
                    && ($stmt->var->getAttribute('external_mutation_free', false)
                        || $stmt->var->getAttribute('pure', false))
                ))
            && !$context->inside_unset
        ) {
            if ($method_storage->mutation_free
                && (!$method_storage->mutation_free_inferred
                    || $method_storage->final
                    || $method_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE)
                && ($method_storage->immutable || $config->remember_property_assignments_after_call)
            ) {
                if ($context->inside_conditional
                    && !$method_storage->assertions
                    && !$method_storage->if_true_assertions
                ) {
                    $stmt->setAttribute('memoizable', true);

                    if ($method_storage->immutable) {
                        $stmt->setAttribute('pure', true);
                    }
                }

                $result->can_memoize = true;
            }

            if ($codebase->find_unused_variables
                && !$context->inside_conditional
                && !$context->inside_general_use
                && !$context->inside_throw
            ) {
                if (!$context->inside_assignment
                    && !$context->inside_call
                    && !$context->inside_return
                    && !$method_storage->assertions
                    && !$method_storage->if_true_assertions
                    && !$method_storage->if_false_assertions
                    && !$method_storage->throws
                ) {
                    IssueBuffer::maybeAdd(
                        new UnusedMethodCall(
                            'The call to ' . $cased_method_id . ' is not used',
                            new CodeLocation($statements_analyzer, $stmt->name),
                            (string) $method_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );
                } elseif (!$method_storage->mutation_free_inferred) {
                    $stmt->setAttribute('pure', true);
                }
            }
        }

        if ($statements_analyzer->getSource() instanceof FunctionLikeAnalyzer
            && $statements_analyzer->getSource()->track_mutations
            && !$method_storage->mutation_free
            && !$method_pure_compatible
        ) {
            $statements_analyzer->getSource()->inferred_has_mutation = true;
            $statements_analyzer->getSource()->inferred_impure = true;
        }

        if (!$config->remember_property_assignments_after_call
            && !$method_storage->mutation_free
            && !$method_pure_compatible
        ) {
            $context->removeMutableObjectVars();
        } elseif ($method_storage->this_property_mutations) {
            if (!$method_pure_compatible) {
                $context->removeMutableObjectVars(true);
            }

            foreach ($method_storage->this_property_mutations as $name => $_) {
                $mutation_var_id = $lhs_var_id . '->' . $name;

                $this_property_didnt_exist = $lhs_var_id === '$this'
                    && isset($context->vars_in_scope[$mutation_var_id])
                    && !isset($class_storage->declaring_property_ids[$name]);

                if ($this_property_didnt_exist) {
                    $context->vars_in_scope[$mutation_var_id] = Type::getMixed();
                } else {
                    $new_type = AssignmentAnalyzer::getExpandedPropertyType(
                        $codebase,
                        $class_storage->name,
                        $name,
                        $class_storage
                    ) ?? Type::getMixed();

                    $context->vars_in_scope[$mutation_var_id] = $new_type;
                    $context->possibly_assigned_var_ids[$mutation_var_id] = true;
                }
            }
        }
    }
}
