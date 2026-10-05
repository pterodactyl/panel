<?php

declare(strict_types=1);

namespace Rules\Rules;

use Rules\Support\TypeAliasDocblock;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Port of anti-slop's no-unknown-type-aliases: a named alias whose body is
 * `mixed` (or a union containing it, directly or through another local alias)
 * hides the top type behind a domain-sounding name.
 *
 * @implements Rule<InClassNode>
 */
final class NoMixedTypeAliasesRule implements Rule
{
    public function __construct(private readonly TypeAliasDocblock $typeAliasDocblock) {}

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $docComment = $node->getOriginalNode()->getDocComment();
        if ($docComment === null) {
            return [];
        }

        $aliases = $this->typeAliasDocblock->aliases($docComment->getText());

        $errors = [];
        foreach ($aliases as $name => $body) {
            if (! $this->typeAliasDocblock->resolvesToMixed($body, $aliases, [$name => true])) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Type alias `%s` hides `mixed`. Keep `mixed` explicit at the parsing boundary; otherwise alias the parsed owner type.',
                $name,
            ))
                ->identifier('rules.noMixedTypeAliases')
                ->line($node->getOriginalNode()->getStartLine())
                ->build();
        }

        return $errors;
    }
}
