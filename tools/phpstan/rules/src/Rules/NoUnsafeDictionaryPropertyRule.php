<?php

declare(strict_types=1);

namespace Rules\Rules;

use Rules\Support\TypeClassifier;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * The property position of no-unsafe-dictionary-type, plus the "stdClass in
 * contracts" check for stored state.
 *
 * @implements Rule<ClassPropertyNode>
 */
final class NoUnsafeDictionaryPropertyRule implements Rule
{
    public function __construct(private readonly TypeClassifier $typeClassifier) {}

    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $type = $node->getPhpDocType() ?? $node->getNativeType();
        if ($type === null) {
            return [];
        }

        $errors = [];
        $kind = $this->typeClassifier->unsafeDictionaryValueKind($type);
        if ($kind !== null) {
            $errors[] = RuleErrorBuilder::message(sprintf(
                'Property `$%s` is a dictionary of `%s` values, which stores data without a value contract. Declare an `array{...}` shape or a readonly DTO; parse external payloads before insertion.',
                $node->getName(),
                $kind,
            ))
                ->identifier('rules.noUnsafeDictionaryType')
                ->line($node->getStartLine())
                ->build();
        } elseif ($this->typeClassifier->containsStdClass($type)) {
            $errors[] = RuleErrorBuilder::message(sprintf(
                'Property `$%s` is typed `stdClass`, an anonymous bag with no contract. Declare a named readonly DTO and parse external payloads into it.',
                $node->getName(),
            ))
                ->identifier('rules.noUnsafeDictionaryType')
                ->line($node->getStartLine())
                ->build();
        }

        return $errors;
    }
}
