<?php

declare(strict_types=1);

namespace Rules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Cast;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Port of anti-slop's no-chained-type-assertions, cast-chain half:
 * `(object) (array) $x` erases evidence and fabricates it back in one
 * expression. The consecutive inline `@var` half lives in
 * NoChainedVarTagsRule because it needs the statement sequence.
 *
 * @implements Rule<Cast>
 */
final class NoChainedAssertionsRule implements Rule
{
    public function getNodeType(): string
    {
        return Cast::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->expr instanceof Cast) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'This assertion chain discards type evidence and fabricates a new type. Keep the original precise type, or parse untrusted input once at its boundary.',
            )
                ->identifier('rules.noChainedAssertions')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}
