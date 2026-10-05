<?php

declare(strict_types=1);

namespace Rules\Tests\Rules;

use Rules\Rules\NoObjectParametersRule;
use Rules\Support\SignatureResolver;
use Rules\Support\TypeClassifier;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoObjectParametersRule>
 */
final class NoObjectParametersRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoObjectParametersRule(
            new SignatureResolver($this->createReflectionProvider()),
            new TypeClassifier,
        );
    }

    public function testRule(): void
    {
        $message = 'Parameter `$%s` uses the broad `object` type. Accept a named owner type; parse external input (cuyz/valinor, spatie/laravel-data) at its boundary before calling this function.';

        $this->analyse([__DIR__.'/data/no-object-parameters.php'], [
            [sprintf($message, 'input'), 7],
            [sprintf($message, 'payload'), 12],
            [sprintf($message, 'maybe'), 14],
            [sprintf($message, 'target'), 26],
            [sprintf($message, 'thing'), 29],
        ]);
    }
}
