<?php

declare(strict_types=1);

namespace Rules\Rules;

use Rules\Support\TypeClassifier;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Scalar;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * The property half of anti-slop's no-known-value-widening: a property whose
 * declared type is a broad escape hatch but whose default is a known literal
 * discards the literal's evidence. The `@var` statement half is covered by
 * PHPStan's own reportAnyTypeWideningInVarTag, which the extension enables.
 *
 * @implements Rule<ClassPropertyNode>
 */
final class NoKnownValueWideningPropertyRule implements Rule
{
    public function __construct(private readonly TypeClassifier $typeClassifier) {}

    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $default = $node->getDefault();
        if ($default === null || ! $this->isKnownEvidence($default) || $this->isEmptyArray($default)) {
            return [];
        }

        $type = $node->getPhpDocType() ?? $node->getNativeType();
        if ($type === null || ! $this->isBroadTarget($type)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'The broad declared type on property `$%s` discards its default value\'s evidence. Delete the annotation and let inference stand, or introduce a named shape.',
                $node->getName(),
            ))
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
