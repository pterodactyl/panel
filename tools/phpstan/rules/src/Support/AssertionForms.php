<?php

declare(strict_types=1);

namespace Rules\Support;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Cast;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\Scope;

/**
 * Recognizes the type-assertion forms shared by requireSafetyCommentForAssertion,
 * noChainedAssertions, and noWidenThenAssert: casts, assert() narrowing,
 * Webmozart Assert calls, and inline `@var` docblocks.
 */
final class AssertionForms
{
    public const string SAFETY_PATTERN = '/\bSAFETY\s*:/';

    /**
     * Collects assertion expressions inside one statement, without descending
     * into nested statements or closure bodies (those are visited on their own).
     *
     * @return list<Expr>
     */
    public function assertionsInStatement(Stmt $statement, Scope $scope): array
    {
        $found = [];
        foreach (NodeChildren::nodes($statement) as $child) {
            if ($child instanceof Expr) {
                $this->collect($child, $scope, $found);
            }
        }

        return $found;
    }

    public function isDeadCast(Cast $cast, Scope $scope): bool
    {
        $type = $scope->getType($cast->expr);

        return match (true) {
            $cast instanceof Cast\Int_ => $type->isInteger()->yes(),
            $cast instanceof Cast\String_ => $type->isString()->yes(),
            $cast instanceof Cast\Bool_ => $type->isBoolean()->yes(),
            $cast instanceof Cast\Double => $type->isFloat()->yes(),
            $cast instanceof Cast\Array_ => $type->isArray()->yes(),
            $cast instanceof Cast\Object_ => $type->isObject()->yes(),
            default => false,
        };
    }

    public function isNarrowingAssertCall(Expr $expression): bool
    {
        if (! $expression instanceof FuncCall || ! $expression->name instanceof Name
            || $expression->name->toLowerString() !== 'assert'
        ) {
            return false;
        }

        $arguments = $expression->getArgs();
        if ($arguments === []) {
            return false;
        }

        return $this->containsNarrowingCheck($arguments[0]->value);
    }

    public function isWebmozartAssertCall(Expr $expression): bool
    {
        return $expression instanceof StaticCall
            && $expression->class instanceof Name
            && str_ends_with($expression->class->toString(), 'Assert\\Assert');
    }

    public function hasSafetyComment(Node ...$nodes): bool
    {
        foreach ($nodes as $node) {
            foreach ($node->getComments() as $comment) {
                if (preg_match(self::SAFETY_PATTERN, $comment->getText()) === 1) {
                    return true;
                }
            }
        }

        return false;
    }

    public function inlineVarTagVariable(Stmt $statement): ?string
    {
        $docComment = $statement->getDocComment();
        if ($docComment === null) {
            return null;
        }

        return preg_match('/@var\s+.+?\s+\$(\w+)/', $docComment->getText(), $match) === 1
            ? $match[1]
            : null;
    }

    /**
     * @param  list<Expr>  $found
     */
    private function collect(Expr $expression, Scope $scope, array &$found): void
    {
        if ($expression instanceof Closure) {
            return;
        }

        if (($expression instanceof Cast && ! $this->isDeadCast($expression, $scope))
            || $this->isNarrowingAssertCall($expression)
            || $this->isWebmozartAssertCall($expression)
        ) {
            $found[] = $expression;
        }

        foreach (NodeChildren::nodes($expression) as $child) {
            if ($child instanceof Expr) {
                $this->collect($child, $scope, $found);
            } elseif ($child instanceof Node\Arg) {
                $this->collect($child->value, $scope, $found);
            }
        }
    }

    private function containsNarrowingCheck(Expr $expression): bool
    {
        if ($expression instanceof Instanceof_) {
            return true;
        }

        if ($expression instanceof FuncCall && $expression->name instanceof Name
            && str_starts_with($expression->name->toLowerString(), 'is_')
        ) {
            return true;
        }

        foreach (NodeChildren::nodes($expression) as $child) {
            if ($child instanceof Expr && $this->containsNarrowingCheck($child)) {
                return true;
            }

            if ($child instanceof Node\Arg && $this->containsNarrowingCheck($child->value)) {
                return true;
            }
        }

        return false;
    }
}
