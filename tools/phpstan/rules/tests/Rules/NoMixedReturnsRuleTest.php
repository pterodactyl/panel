<?php

declare(strict_types=1);

namespace Rules\Tests\Rules;

use Rules\Rules\NoMixedReturnsRule;
use Rules\Support\SignatureResolver;
use Rules\Support\TypeClassifier;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoMixedReturnsRule>
 */
final class NoMixedReturnsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoMixedReturnsRule(
            new SignatureResolver($this->createReflectionProvider()),
            new TypeClassifier,
        );
    }

    public function testRule(): void
    {
        $returns = 'This function exposes `mixed` to its caller. Parse the value at its boundary (cuyz/valinor, spatie/laravel-data) and return a named domain type.';
        $yields = 'This function yields `mixed` values to its caller. Parse each value at its boundary and yield a named domain type.';

        $this->analyse([__DIR__.'/data/no-mixed-returns.php'], [
            [$returns, 7],
            [$returns, 15],
            [$yields, 23],
            [$yields, 31],
            [$returns, 64],
            [$returns, 70],
        ]);
    }
}
