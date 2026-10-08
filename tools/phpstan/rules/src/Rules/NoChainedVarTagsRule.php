<?php

declare(strict_types=1);

namespace Rules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Rules\Support\AssertionForms;
use Rules\Support\NodeChildren;

/**
 * The statement-sequence half of no-chained-type-assertions: two consecutive
 * inline `@var` docblocks re-narrowing the same variable with no intervening
 * statement fabricate evidence in two steps.
 *
 * @implements Rule<FunctionLike>
 */
final class NoChainedVarTagsRule implements Rule
{
    public function __construct(private readonly AssertionForms $assertionForms) {}

    public function getNodeType(): string
    {
        return FunctionLike::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node instanceof ClassMethod && ! $node instanceof Function_ && ! $node instanceof Closure) {
            return [];
        }

        $statements = $node->getStmts();
        if ($statements === null) {
            return [];
        }

        $errors = [];
        $this->scan($statements, $errors);

        return $errors;
    }

    /**
     * @param  array<Stmt>  $statements
     * @param  list<\PHPStan\Rules\IdentifierRuleError>  $errors
     */
    private function scan(array $statements, array &$errors): void
    {
        $previousVariable = null;
        foreach ($statements as $statement) {
            $variable = $this->assertionForms->inlineVarTagVariable($statement);
            if ($variable !== null && $variable === $previousVariable) {
                $errors[] = RuleErrorBuilder::message(sprintf(
                    'Consecutive `@var` docblocks re-narrow `$%s` without new evidence. Keep the original precise type, or parse untrusted input once at its boundary.',
                    $variable,
                ))
                    ->identifier('rules.noChainedAssertions')
                    ->line($statement->getStartLine())
                    ->build();
            }

            $previousVariable = $variable;

            foreach (NodeChildren::statementLists($statement) as $statementList) {
                $this->scan($statementList, $errors);
            }
        }
    }
}
