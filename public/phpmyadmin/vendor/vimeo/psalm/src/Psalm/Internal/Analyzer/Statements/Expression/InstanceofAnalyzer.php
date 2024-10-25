<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Type;

use function implode;
use function in_array;
use function strtolower;

class InstanceofAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Instanceof_ $stmt,
        Context $context
    ): bool {
        $was_inside_general_use = $context->inside_general_use;
        $context->inside_general_use = true;

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            $context->inside_general_use = $was_inside_general_use;

            return false;
        }

        $context->inside_general_use = $was_inside_general_use;

        if ($stmt->class instanceof PhpParser\Node\Expr) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->class, $context) === false) {
                return false;
            }
        } elseif (!in_array(strtolower($stmt->class->parts[0]), ['self', 'static', 'parent'], true)) {
            if ($context->check_classes) {
                $codebase = $statements_analyzer->getCodebase();

                $fq_class_name = ClassLikeAnalyzer::getFQCLNFromNameObject(
                    $stmt->class,
                    $statements_analyzer->getAliases()
                );

                if ($codebase->store_node_types
                    && $fq_class_name
                    && !$context->collect_initializations
                    && !$context->collect_mutations
                ) {
                    $codebase->analyzer->addNodeReference(
                        $statements_analyzer->getFilePath(),
                        $stmt->class,
                        $codebase->classlikes->classOrInterfaceOrEnumExists($fq_class_name)
                            ? $fq_class_name
                            : '*'
                                . ($stmt->class instanceof PhpParser\Node\Name\FullyQualified
                                    ? '\\'
                                    : $statements_analyzer->getNamespace() . '-')
                                . implode('\\', $stmt->class->parts)
                    );
                }

                if (!isset($context->phantom_classes[strtolower($fq_class_name)])) {
                    if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                        $statements_analyzer,
                        $fq_class_name,
                        new CodeLocation($statements_analyzer->getSource(), $stmt->class),
                        $context->self,
                        $context->calling_method_id,
                        $statements_analyzer->getSuppressedIssues()
                    ) === false) {
                        return false;
                    }
                }

                if ($codebase->alter_code) {
                    $codebase->classlikes->handleClassLikeReferenceInMigration(
                        $codebase,
                        $statements_analyzer,
                        $stmt->class,
                        $fq_class_name,
                        $context->calling_method_id
                    );
                }
            }
        }

        $statements_analyzer->node_data->setType($stmt, Type::getBool());

        return true;
    }
}
