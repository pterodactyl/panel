<?php

declare(strict_types=1);

namespace Rules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Port of anti-slop's no-module-mocking: mocking a concrete class rewires
 * class loading instead of a real dependency seam. Mocks of interfaces and
 * abstract classes are fine. Mockery's `overload:`/`alias:` prefixes replace
 * the autoloaded class outright and are always rejected.
 *
 * @implements Rule<CallLike>
 */
final class NoClassLoadingMocksRule implements Rule
{
    private const array TEST_CASE_METHODS = ['createmock', 'createstub', 'getmockbuilder', 'createconfiguredmock', 'createpartialmock'];

    private const array LARAVEL_TEST_METHODS = ['mock', 'partialmock', 'spy'];

    private const array MOCKERY_METHODS = ['mock', 'spy', 'namedmock'];

    public function __construct(private readonly ReflectionProvider $reflectionProvider) {}

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $target = $this->mockTargetName($node, $scope);
        if ($target === null) {
            return [];
        }

        if (str_starts_with($target, 'overload:') || str_starts_with($target, 'alias:')) {
            return [$this->buildError($node, sprintf(
                'Mocking `%s` replaces the autoloaded class for the whole process. Extract a real interface and inject a faithful in-memory fake instead.',
                $target,
            ))];
        }

        if (! $this->reflectionProvider->hasClass($target)) {
            return [];
        }

        $classReflection = $this->reflectionProvider->getClass($target);
        if ($classReflection->isInterface() || $classReflection->isAbstract()) {
            return [];
        }

        return [$this->buildError($node, sprintf(
            'Mocking the concrete class `%s` fakes an implementation instead of a contract. Extract a real interface, or inject a faithful in-memory fake.',
            $target,
        ))];
    }

    private function mockTargetName(CallLike $node, Scope $scope): ?string
    {
        if ($node instanceof StaticCall && $node->class instanceof Name && $node->name instanceof Identifier) {
            if ($node->class->toString() !== 'Mockery' && ! str_ends_with($node->class->toString(), '\\Mockery')) {
                return null;
            }

            if (! in_array($node->name->toLowerString(), self::MOCKERY_METHODS, true)) {
                return null;
            }

            return $this->firstArgumentClassString($node, $scope);
        }

        if ($node instanceof MethodCall && $node->name instanceof Identifier) {
            $method = $node->name->toLowerString();
            $isTestCaseMethod = in_array($method, self::TEST_CASE_METHODS, true);
            $isLaravelMethod = in_array($method, self::LARAVEL_TEST_METHODS, true);
            if (! $isTestCaseMethod && ! $isLaravelMethod) {
                return null;
            }

            $callerType = $scope->getType($node->var);
            $isTestCase = false;
            foreach ($callerType->getObjectClassReflections() as $callerClass) {
                if ($callerClass->is('PHPUnit\Framework\TestCase')) {
                    $isTestCase = true;

                    break;
                }
            }

            if (! $isTestCase) {
                return null;
            }

            return $this->firstArgumentClassString($node, $scope);
        }

        return null;
    }

    private function firstArgumentClassString(CallLike $node, Scope $scope): ?string
    {
        $arguments = $node->getArgs();
        if ($arguments === []) {
            return null;
        }

        $type = $scope->getType($arguments[0]->value);
        $strings = $type->getConstantStrings();

        return $strings === [] ? null : $strings[0]->getValue();
    }

    private function buildError(CallLike $node, string $message): \PHPStan\Rules\IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)
            ->identifier('rules.noClassLoadingMocks')
            ->line($node->getStartLine())
            ->build();
    }
}
