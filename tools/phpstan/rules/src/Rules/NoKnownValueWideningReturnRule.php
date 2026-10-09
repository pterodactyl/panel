<?php

declare(strict_types=1);

namespace Rules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Scalar;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Rules\Support\TypeClassifier;

/**
 * The return half of anti-slop's no-known-value-widening: a function whose
 * declared return type is a broad escape hatch but whose every return
 * statement is a literal, `new`, or constant discards that evidence at the
 * contract. Applies to named functions; MethodReturnStatementsNode extends
 * this node so methods are covered by the same rule.
 *
 * @implements Rule<FunctionReturnStatementsNode>
 */
final class NoKnownValueWideningReturnRule implements Rule
{
    public function __construct(private readonly TypeClassifier $typeClassifier) {}

    public function getNodeType(): string
    {
        return FunctionReturnStatementsNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $returnType = $node->getFunctionReflection()->getOnlyVariant()->getReturnType();
        if (! $this->isBroadTarget($returnType)) {
            return [];
        }

        $returns = $node->getReturnStatements();
        if ($returns === []) {
            return [];
        }

        foreach ($returns as $returnStatement) {
            $expression = $returnStatement->getReturnNode()->expr;
            if ($expression === null || ! $this->isKnownEvidence($expression) || $this->isEmptyArray($expression)) {
                return [];
            }
        }

        return [
            RuleErrorBuilder::message(
                'The broad declared return type discards the known evidence of every returned value. Delete the annotation and let inference stand, or introduce a named shape.',
            )
                ->identifier('rules.noKnownValueWidening')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    private function isKnownEvidence(Expr $expression): bool
    {
        return $expression instanceof Array_
            || $expression instanceof New_
            || $expression instanceof Scalar
            || $expression instanceof ClassConstFetch
            || $expression instanceof ConstFetch;
    }

    private function isEmptyArray(Expr $expression): bool
    {
        return $expression instanceof Array_ && $expression->items === [];
    }

    private function isBroadTarget(\PHPStan\Type\Type $type): bool
    {
        return $this->typeClassifier->isExplicitMixed($type)
            || $this->typeClassifier->containsBroadObject($type)
            || $this->typeClassifier->unsafeDictionaryValueKind($type) !== null;
    }
}
