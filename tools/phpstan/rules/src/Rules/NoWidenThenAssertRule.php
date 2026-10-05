<?php

declare(strict_types=1);

namespace Rules\Rules;

use Rules\Support\AssertionForms;
use Rules\Support\NodeChildren;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Cast;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Port of anti-slop's no-widen-then-assert: within one function, a binding is
 * widened — `(array)`/`(object)` cast, `json_decode(json_encode(...))`,
 * `get_object_vars`, `iterator_to_array`, `data_get`/`Arr::get`, or a broad
 * inline `@var` — and a later statement narrows the same binding back with an
 * assertion form. Like the TypeScript original, the analysis is a linear scan
 * of each statement list; a plain reassignment clears the binding.
 *
 * @implements Rule<FunctionLike>
 */
final class NoWidenThenAssertRule implements Rule
{
    private const array WIDENING_FUNCTIONS = ['get_object_vars', 'iterator_to_array', 'data_get'];

    private const string BROAD_VAR_TAG = '/@var\s+(?:mixed|object|array(?!\{)|array<[^>]*mixed[^>]*>)\s+\$(\w+)/';

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
        $this->scan($statements, [], $errors, $scope);

        return $errors;
    }

    /**
     * @param  array<Stmt>  $statements
     * @param  array<string, true>  $widened
     * @param  list<\PHPStan\Rules\IdentifierRuleError>  $errors
     */
    private function scan(array $statements, array $widened, array &$errors, Scope $scope): void
    {
        foreach ($statements as $statement) {
            $broadTagVariable = $this->broadVarTagVariable($statement);
            if ($broadTagVariable !== null) {
                $widened[$broadTagVariable] = true;
            }

            if ($statement instanceof Expression && $statement->expr instanceof Assign
                && $statement->expr->var instanceof Variable && is_string($statement->expr->var->name)
            ) {
                $name = $statement->expr->var->name;
                if ($this->isWideningExpression($statement->expr->expr)) {
                    $widened[$name] = true;
                } elseif ($this->narrowedVariable($statement->expr->expr) !== $name) {
                    unset($widened[$name]);
                }
            }

            foreach ($this->narrowingsInStatement($statement, $scope) as [$variable, $line]) {
                if (isset($widened[$variable])) {
                    $errors[] = RuleErrorBuilder::message(sprintf(
                        'Binding `$%s` discards type evidence and later recreates it with an assertion. Keep the precise type from initialization through use; parse boundary input once.',
                        $variable,
                    ))
                        ->identifier('rules.noWidenThenAssert')
                        ->line($line)
                        ->build();
                    unset($widened[$variable]);
                }
            }

            foreach (NodeChildren::statementLists($statement) as $statementList) {
                $this->scan($statementList, $widened, $errors, $scope);
            }
        }
    }

    private function broadVarTagVariable(Stmt $statement): ?string
    {
        $docComment = $statement->getDocComment();
        if ($docComment === null) {
            return null;
        }

        return preg_match(self::BROAD_VAR_TAG, $docComment->getText(), $match) === 1 ? $match[1] : null;
    }

    private function isWideningExpression(Expr $expression): bool
    {
        if ($expression instanceof Cast\Array_ || $expression instanceof Cast\Object_) {
            return true;
        }

        if ($expression instanceof FuncCall && $expression->name instanceof Name) {
            $function = $expression->name->toLowerString();
            if (in_array($function, self::WIDENING_FUNCTIONS, true)) {
                return true;
            }

            if ($function === 'json_decode') {
                $arguments = $expression->getArgs();

                return isset($arguments[0])
                    && $arguments[0]->value instanceof FuncCall
                    && $arguments[0]->value->name instanceof Name
                    && $arguments[0]->value->name->toLowerString() === 'json_encode';
            }
        }

        return $expression instanceof StaticCall
            && $expression->class instanceof Name
            && str_ends_with($expression->class->toString(), 'Arr')
            && $expression->name instanceof Node\Identifier
            && $expression->name->toLowerString() === 'get';
    }

    /**
     * @return list<array{string, int}>
     */
    private function narrowingsInStatement(Stmt $statement, Scope $scope): array
    {
        $narrowings = [];

        $tagVariable = $this->assertionForms->inlineVarTagVariable($statement);
        if ($tagVariable !== null && $this->broadVarTagVariable($statement) === null) {
            $narrowings[] = [$tagVariable, $statement->getStartLine()];
        }

        foreach ($this->assertionForms->assertionsInStatement($statement, $scope) as $assertion) {
            $variable = $this->narrowedVariable($assertion);
            if ($variable !== null) {
                $narrowings[] = [$variable, $assertion->getStartLine()];
            }
        }

        return $narrowings;
    }

    private function narrowedVariable(Expr $expression): ?string
    {
        if ($expression instanceof Cast) {
            return $expression->expr instanceof Variable && is_string($expression->expr->name)
                ? $expression->expr->name
                : null;
        }

        if ($expression instanceof FuncCall || $expression instanceof StaticCall) {
            foreach ($expression->getArgs() as $argument) {
                $inner = $argument->value;
                if ($inner instanceof Variable && is_string($inner->name)) {
                    return $inner->name;
                }

                if ($inner instanceof FuncCall) {
                    foreach ($inner->getArgs() as $nested) {
                        if ($nested->value instanceof Variable && is_string($nested->value->name)) {
                            return $nested->value->name;
                        }
                    }
                }
            }
        }

        return null;
    }
}
