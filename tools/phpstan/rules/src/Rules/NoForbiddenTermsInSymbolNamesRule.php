<?php

declare(strict_types=1);

namespace Rules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Const_;
use PhpParser\Node\Stmt\EnumCase;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Port of anti-slop's no-shape-in-symbol-names: a symbol named for its
 * structure ("shape") instead of its domain role names the evidence it should
 * carry. The term list is configurable; the default is only "shape".
 *
 * @implements Rule<Node>
 */
final class NoForbiddenTermsInSymbolNamesRule implements Rule
{
    /**
     * @param  list<string>  $forbiddenTerms
     */
    public function __construct(private readonly array $forbiddenTerms = ['shape']) {}

    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];
        foreach ($this->symbolNames($node) as [$name, $line]) {
            $term = $this->matchedTerm($name);
            if ($term === null) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Rename symbol `%s` for its domain role; `%s` describes structure rather than ownership.',
                $name,
                $term,
            ))
                ->identifier('rules.noForbiddenTermsInSymbolNames')
                ->line($line)
                ->build();
        }

        return $errors;
    }

    /**
     * @return list<array{string, int}>
     */
    private function symbolNames(Node $node): array
    {
        if ($node instanceof ClassLike && $node->name !== null) {
            $names = [[$node->name->toString(), $node->name->getStartLine()]];
            $docComment = $node->getDocComment();
            if ($docComment !== null) {
                preg_match_all('/@(?:phpstan|psalm)-type\s+(\w+)/', $docComment->getText(), $matches);
                foreach ($matches[1] as $aliasName) {
                    $names[] = [$aliasName, $node->getStartLine()];
                }
            }

            return $names;
        }

        if ($node instanceof ClassMethod || $node instanceof Function_) {
            return [[$node->name->toString(), $node->name->getStartLine()]];
        }

        if ($node instanceof Property) {
            $names = [];
            foreach ($node->props as $property) {
                $names[] = [$property->name->toString(), $property->getStartLine()];
            }

            return $names;
        }

        if ($node instanceof Param && $node->var instanceof Variable && is_string($node->var->name)) {
            return [[$node->var->name, $node->getStartLine()]];
        }

        if ($node instanceof Const_ || $node instanceof Node\Stmt\ClassConst) {
            $names = [];
            foreach ($node->consts as $const) {
                $names[] = [$const->name->toString(), $const->getStartLine()];
            }

            return $names;
        }

        if ($node instanceof EnumCase) {
            return [[$node->name->toString(), $node->getStartLine()]];
        }

        if ($node instanceof Variable && is_string($node->name)) {
            return [[$node->name, $node->getStartLine()]];
        }

        return [];
    }

    private function matchedTerm(string $name): ?string
    {
        foreach ($this->forbiddenTerms as $term) {
            if (stripos($name, $term) !== false) {
                return $term;
            }
        }

        return null;
    }
}
