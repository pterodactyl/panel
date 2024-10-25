<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\Fetch;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\MethodCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Issue\ImpurePropertyFetch;
use Psalm\Issue\InvalidPropertyFetch;
use Psalm\Issue\MixedPropertyFetch;
use Psalm\Issue\NullPropertyFetch;
use Psalm\Issue\PossiblyInvalidPropertyFetch;
use Psalm\Issue\PossiblyNullPropertyFetch;
use Psalm\Issue\UninitializedProperty;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TTemplateParam;

use function array_merge;
use function array_shift;
use function rtrim;
use function strtolower;

/**
 * @internal
 */
class InstancePropertyFetchAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\PropertyFetch $stmt,
        Context $context,
        bool $in_assignment = false,
        bool $is_static_access = false
    ): bool {
        $was_inside_general_use = $context->inside_general_use;
        $context->inside_general_use = true;

        if (!$stmt->name instanceof PhpParser\Node\Identifier) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->name, $context) === false) {
                return false;
            }
        }

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->var, $context) === false) {
            $context->inside_general_use = $was_inside_general_use;

            return false;
        }

        $context->inside_general_use = $was_inside_general_use;

        if ($stmt->name instanceof PhpParser\Node\Identifier) {
            $prop_name = $stmt->name->name;
        } elseif (($stmt_name_type = $statements_analyzer->node_data->getType($stmt->name))
            && $stmt_name_type->isSingleStringLiteral()
        ) {
            $prop_name = $stmt_name_type->getSingleStringLiteral()->value;
        } else {
            $prop_name = null;
        }

        $codebase = $statements_analyzer->getCodebase();

        $stmt_var_id = ExpressionIdentifier::getArrayVarId(
            $stmt->var,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        $var_id = ExpressionIdentifier::getArrayVarId(
            $stmt,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        if ($var_id && $context->hasVariable($var_id)) {
            self::handleScopedProperty(
                $context,
                $var_id,
                $statements_analyzer,
                $stmt,
                $codebase,
                $stmt_var_id,
                $in_assignment
            );

            return true;
        }

        if ($stmt_var_id && $context->hasVariable($stmt_var_id)) {
            $stmt_var_type = $context->vars_in_scope[$stmt_var_id];
        } else {
            $stmt_var_type = $statements_analyzer->node_data->getType($stmt->var);
        }

        if (!$stmt_var_type) {
            return true;
        }

        if ($stmt_var_type->isNull()) {
            if (IssueBuffer::accepts(
                new NullPropertyFetch(
                    'Cannot get property on null variable ' . $stmt_var_id,
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                return false;
            }

            return true;
        }

        if ($stmt_var_type->isEmpty()) {
            if (IssueBuffer::accepts(
                new MixedPropertyFetch(
                    'Cannot fetch property on empty var ' . $stmt_var_id,
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                return false;
            }

            return true;
        }

        if ($stmt_var_type->hasMixed()) {
            if (!$context->collect_initializations
                && !$context->collect_mutations
                && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                && (!(($parent_source = $statements_analyzer->getSource())
                        instanceof FunctionLikeAnalyzer)
                    || !$parent_source->getSource() instanceof TraitAnalyzer)
            ) {
                $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
            }

            if ($stmt->name instanceof PhpParser\Node\Identifier) {
                $codebase->analyzer->addMixedMemberName(
                    '$' . $stmt->name->name,
                    $context->calling_method_id ?: $statements_analyzer->getFileName()
                );
            }

            IssueBuffer::maybeAdd(
                new MixedPropertyFetch(
                    'Cannot fetch property on mixed var ' . $stmt_var_id,
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            );

            $statements_analyzer->node_data->setType($stmt, Type::getMixed());

            if ($codebase->store_node_types
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                $codebase->analyzer->addNodeType(
                    $statements_analyzer->getFilePath(),
                    $stmt->name,
                    $stmt_var_type->getId()
                );
            }
        }

        if (!$context->collect_initializations
            && !$context->collect_mutations
            && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
            && (!(($parent_source = $statements_analyzer->getSource())
                    instanceof FunctionLikeAnalyzer)
                || !$parent_source->getSource() instanceof TraitAnalyzer)
        ) {
            $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getRootFilePath());
        }

        if ($stmt_var_type->isNullable() && !$stmt_var_type->ignore_nullable_issues) {
            // we can only be sure that the variable is possibly null if we know the var_id
            if (!$context->inside_isset
                && $stmt->name instanceof PhpParser\Node\Identifier
                && !MethodCallAnalyzer::hasNullsafe($stmt->var)
            ) {
                IssueBuffer::maybeAdd(
                    new PossiblyNullPropertyFetch(
                        rtrim('Cannot get property on possibly null variable ' . $stmt_var_id)
                        . ' of type ' . $stmt_var_type,
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            } else {
                $statements_analyzer->node_data->setType($stmt, Type::getNull());
            }
        }

        if (!$prop_name) {
            if ($stmt_var_type->hasObjectType() && !$context->ignore_variable_property) {
                foreach ($stmt_var_type->getAtomicTypes() as $type) {
                    if ($type instanceof TNamedObject) {
                        $codebase->analyzer->addMixedMemberName(
                            strtolower($type->value) . '::$',
                            $context->calling_method_id ?: $statements_analyzer->getFileName()
                        );
                    }
                }
            }

            $statements_analyzer->node_data->setType($stmt, Type::getMixed());

            if ($codebase->store_node_types
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                $codebase->analyzer->addNodeType(
                    $statements_analyzer->getFilePath(),
                    $stmt->name,
                    $stmt_var_type->getId()
                );
            }

            return true;
        }

        $invalid_fetch_types = [];
        $has_valid_fetch_type = false;

        $var_atomic_types = $stmt_var_type->getAtomicTypes();

        while ($lhs_type_part = array_shift($var_atomic_types)) {
            if ($lhs_type_part instanceof TTemplateParam) {
                $var_atomic_types = array_merge($var_atomic_types, $lhs_type_part->as->getAtomicTypes());
                continue;
            }

            AtomicPropertyFetchAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context,
                $in_assignment,
                $var_id,
                $stmt_var_id,
                $stmt_var_type,
                $lhs_type_part,
                $prop_name,
                $has_valid_fetch_type,
                $invalid_fetch_types,
                $is_static_access
            );
        }

        $stmt_type = $statements_analyzer->node_data->getType($stmt);

        if ($stmt_var_type->isNullable() && !$context->inside_isset && $stmt_type) {
            $stmt_type->addType(new TNull);

            if ($stmt_var_type->ignore_nullable_issues) {
                $stmt_type->ignore_nullable_issues = true;
            }
        }

        if ($codebase->store_node_types
            && !$context->collect_initializations
            && !$context->collect_mutations
            && ($stmt_type = $statements_analyzer->node_data->getType($stmt))
        ) {
            $codebase->analyzer->addNodeType(
                $statements_analyzer->getFilePath(),
                $stmt->name,
                $stmt_type->getId()
            );
        }

        if ($invalid_fetch_types) {
            $lhs_type_part = $invalid_fetch_types[0];

            if ($has_valid_fetch_type) {
                IssueBuffer::maybeAdd(
                    new PossiblyInvalidPropertyFetch(
                        'Cannot fetch property on possible non-object ' . $stmt_var_id . ' of type ' . $lhs_type_part,
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            } else {
                IssueBuffer::maybeAdd(
                    new InvalidPropertyFetch(
                        'Cannot fetch property on non-object ' . $stmt_var_id . ' of type ' . $lhs_type_part,
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            }
        }

        if ($var_id) {
            $context->vars_in_scope[$var_id] = $statements_analyzer->node_data->getType($stmt) ?? Type::getMixed();
        }

        return true;
    }

    private static function handleScopedProperty(
        Context $context,
        string $var_id,
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\PropertyFetch $stmt,
        Codebase $codebase,
        ?string $stmt_var_id,
        bool $in_assignment
    ): void {
        $stmt_type = $context->vars_in_scope[$var_id];

        // we don't need to check anything
        $statements_analyzer->node_data->setType($stmt, $stmt_type);

        if (!$context->collect_initializations
            && !$context->collect_mutations
            && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
            && (!(($parent_source = $statements_analyzer->getSource())
                    instanceof FunctionLikeAnalyzer)
                || !$parent_source->getSource() instanceof TraitAnalyzer)
        ) {
            $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());
        }

        if ($codebase->store_node_types
            && !$context->collect_initializations
            && !$context->collect_mutations
        ) {
            $codebase->analyzer->addNodeType(
                $statements_analyzer->getFilePath(),
                $stmt->name,
                $stmt_type->getId()
            );
        }

        if ($stmt_var_id === '$this'
            && !$stmt_type->initialized
            && $context->collect_initializations
            && ($stmt_var_type = $statements_analyzer->node_data->getType($stmt->var))
            && $stmt_var_type->hasObjectType()
            && $stmt->name instanceof PhpParser\Node\Identifier
        ) {
            $source = $statements_analyzer->getSource();

            $property_id = null;

            foreach ($stmt_var_type->getAtomicTypes() as $lhs_type_part) {
                if ($lhs_type_part instanceof TNamedObject) {
                    if (!$codebase->classExists($lhs_type_part->value)) {
                        continue;
                    }

                    $property_id = $lhs_type_part->value . '::$' . $stmt->name->name;
                }
            }

            if ($property_id
                && $source instanceof FunctionLikeAnalyzer
                && $source->getMethodName() === '__construct'
                && !$context->inside_unset
            ) {
                if ($context->inside_isset
                    || ($context->inside_assignment
                        && isset($context->vars_in_scope[$var_id])
                        && $context->vars_in_scope[$var_id]->isNullable()
                    )
                ) {
                    $stmt_type->initialized = true;
                } else {
                    IssueBuffer::maybeAdd(
                        new UninitializedProperty(
                            'Cannot use uninitialized property ' . $var_id,
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $var_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );

                    $stmt_type->addType(new TNull);
                }
            }
        }


        if (($stmt_var_type = $statements_analyzer->node_data->getType($stmt->var))
            && $stmt_var_type->hasObjectType()
            && $stmt->name instanceof PhpParser\Node\Identifier
        ) {
            // log the appearance
            foreach ($stmt_var_type->getAtomicTypes() as $lhs_type_part) {
                if ($lhs_type_part instanceof TNamedObject) {
                    if (!$codebase->classExists($lhs_type_part->value)) {
                        continue;
                    }

                    $property_id = $lhs_type_part->value . '::$' . $stmt->name->name;


                    $class_storage = $codebase->classlike_storage_provider->get($lhs_type_part->value);

                    AtomicPropertyFetchAnalyzer::processTaints(
                        $statements_analyzer,
                        $stmt,
                        $stmt_type,
                        $property_id,
                        $class_storage,
                        $in_assignment
                    );

                    $declaring_property_class = $codebase->properties->getDeclaringClassForProperty(
                        $property_id,
                        true,
                        $statements_analyzer
                    );

                    if ($declaring_property_class) {
                        AtomicPropertyFetchAnalyzer::checkPropertyDeprecation(
                            $stmt->name->name,
                            $declaring_property_class,
                            $stmt,
                            $statements_analyzer
                        );
                    }

                    $codebase->properties->propertyExists(
                        $property_id,
                        true,
                        $statements_analyzer,
                        $context,
                        $codebase->collect_locations
                            ? new CodeLocation($statements_analyzer->getSource(), $stmt)
                            : null
                    );

                    if ($codebase->store_node_types
                        && !$context->collect_initializations
                        && !$context->collect_mutations
                    ) {
                        $codebase->analyzer->addNodeReference(
                            $statements_analyzer->getFilePath(),
                            $stmt->name,
                            $property_id
                        );
                    }

                    if (!$context->collect_mutations
                        && !$context->collect_initializations
                        && !($class_storage->external_mutation_free
                            && $stmt_type->allow_mutations)
                    ) {
                        if ($context->pure) {
                            IssueBuffer::maybeAdd(
                                new ImpurePropertyFetch(
                                    'Cannot access a property on a mutable object from a pure context',
                                    new CodeLocation($statements_analyzer, $stmt)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            );
                        } elseif ($statements_analyzer->getSource()
                            instanceof FunctionLikeAnalyzer
                            && $statements_analyzer->getSource()->track_mutations
                        ) {
                            $statements_analyzer->getSource()->inferred_impure = true;
                        }
                    }
                }
            }
        }
    }
}
