<?php

declare(strict_types=1);

namespace Rules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Plus;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Port of anti-slop's no-conditional-empty-object-spread: `...($c ? [...] : [])`
 * hides key omission behind an empty array, and the same dodge through
 * array_merge/array_replace or `+`.
 *
 * @implements Rule<Expr>
 */
final class NoConditionalEmptyArraySpreadRule implements Rule
{
    private const array MERGE_FUNCTIONS = ['array_merge', 'array_replace'];

    public function getNodeType(): string
    {
        return Expr::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($node instanceof Array_) {
            foreach ($node->items as $item) {
                if ($item->unpack && $this->isConditionalEmptyArray($item->value)) {
                    return [$this->buildError($item->value)];
                }
            }

            return [];
        }

        if ($node instanceof FuncCall && $node->name instanceof Name
            && in_array($node->name->toLowerString(), self::MERGE_FUNCTIONS, true)
        ) {
            foreach ($node->getArgs() as $argument) {
                if ($this->isConditionalEmptyArray($argument->value)) {
                    return [$this->buildError($argument->value)];
                }
            }

            return [];
        }

        if ($node instanceof Plus
            && ($this->isConditionalEmptyArray($node->left) || $this->isConditionalEmptyArray($node->right))
        ) {
            return [$this->buildError($node)];
        }

        return [];
    }

    private function isConditionalEmptyArray(Expr $expression): bool
    {
        if (! $expression instanceof Ternary || $expression->if === null) {
            return false;
        }

        return $this->isEmptyArray($expression->if) || $this->isEmptyArray($expression->else);
    }

    private function isEmptyArray(Expr $expression): bool
    {
        return $expression instanceof Array_ && $expression->items === [];
    }

    private function buildError(Expr $node): \PHPStan\Rules\IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            'This conditional spread hides key omission behind an empty array. Build the array in separate statements and add the key only when present.',
        )
            ->identifier('rules.noConditionalEmptyArraySpread')
            ->line($node->getStartLine())
            ->build();
    }
}
