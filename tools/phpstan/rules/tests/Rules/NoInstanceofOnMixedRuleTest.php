<?php

declare(strict_types=1);

namespace Rules\Tests\Rules;

use Rules\Rules\NoInstanceofOnMixedRule;
use Rules\Support\TypeClassifier;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoInstanceofOnMixedRule>
 */
final class NoInstanceofOnMixedRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoInstanceofOnMixedRule(new TypeClassifier, ['src/Boundary/**']);
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/data/no-instanceof-on-mixed.php'], [
            ['This `instanceof` narrows a `mixed` value without establishing its contract. Parse the value at its I/O boundary (cuyz/valinor, spatie/laravel-data), then branch on the domain type.', 9],
        ]);
    }
}
