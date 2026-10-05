<?php

declare(strict_types=1);

namespace Rules\Tests\Rules;

use Rules\Rules\NoMixedParametersRule;
use Rules\Support\SignatureResolver;
use Rules\Support\TypeClassifier;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoMixedParametersRule>
 */
final class NoMixedParametersRuleTest extends RuleTestCase
{
    public function test_rule(): void
    {
        $message = 'Parameter `$%s` leaves input unparsed. Accept a named domain type; run the expected schema or parser (cuyz/valinor, spatie/laravel-data) at the I/O boundary before calling this function.';

        $this->analyse([__DIR__.'/data/no-mixed-parameters.php'], [
            [sprintf($message, 'input'), 11],
            [sprintf($message, 'payload'), 16],
            [sprintf($message, 'input'), 32],
            [sprintf($message, 'value'), 57],
            [sprintf($message, 'value'), 62],
            [sprintf($message, 'thing'), 65],
            [sprintf($message, 'item'), 67],
        ]);
    }

    protected function getRule(): Rule
    {
        return new NoMixedParametersRule(
            new SignatureResolver($this->createReflectionProvider()),
            new TypeClassifier,
        );
    }
}
