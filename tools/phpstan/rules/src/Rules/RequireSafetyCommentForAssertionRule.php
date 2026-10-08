<?php

declare(strict_types=1);

namespace Rules\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Rules\Support\AssertionForms;

/**
 * Port of anti-slop's require-safety-comment-for-type-assertion: every
 * evidence-fabricating form — casts, assert() narrowing, Webmozart Assert
 * calls, inline `@var` docblocks — must state the invariant the type system
 * cannot express, in a `SAFETY:` comment on the expression or its statement.
 * A cast that does not change the type is dead code, not an assertion, and is
 * not flagged.
 *
 * @implements Rule<Stmt>
 */
final class RequireSafetyCommentForAssertionRule implements Rule
{
    public function __construct(private readonly AssertionForms $assertionForms) {}

    public function getNodeType(): string
    {
        return Stmt::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];

        if ($this->assertionForms->inlineVarTagVariable($node) !== null
            && ! $this->assertionForms->hasSafetyComment($node)
        ) {
            $errors[] = $this->buildError(
                'This inline `@var` overrides inference without a `SAFETY:` justification. State the checked invariant in the docblock or immediately before the statement.',
                $node->getStartLine(),
            );
        }

        foreach ($this->assertionForms->assertionsInStatement($node, $scope) as $assertion) {
            if ($this->assertionForms->hasSafetyComment($assertion, $node)) {
                continue;
            }

            $errors[] = $this->buildError(
                'This type assertion has no `SAFETY:` justification. State the checked invariant immediately before the assertion or its containing statement.',
                $assertion->getStartLine(),
            );
        }

        return $errors;
    }

    private function buildError(string $message, int $line): \PHPStan\Rules\IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)
            ->identifier('rules.requireSafetyCommentForAssertion')
            ->line($line)
            ->build();
    }
}
