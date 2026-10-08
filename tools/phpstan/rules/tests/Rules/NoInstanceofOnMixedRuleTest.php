<?php

declare(strict_types=1);

namespace Rules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Rules\Rules\NoInstanceofOnMixedRule;
use Rules\Support\TypeClassifier;

/**
 * @extends RuleTestCase<NoInstanceofOnMixedRule>
 */
final class NoInstanceofOnMixedRuleTest extends RuleTestCase
{
    public function test_rule(): void
    {
        $this->analyse([__DIR__.'/data/no-instanceof-on-mixed.php'], [
            ['This `instanceof` narrows a `mixed` value without establishing its contract. Parse the value at its I/O boundary (cuyz/valinor, spatie/laravel-data), then branch on the domain type.', 9],
        ]);
    }

    protected function getRule(): Rule
    {
        return new NoInstanceofOnMixedRule(new TypeClassifier, ['src/Boundary/**']);
    }
}
