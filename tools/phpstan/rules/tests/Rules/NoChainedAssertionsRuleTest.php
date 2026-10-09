<?php

declare(strict_types=1);

namespace Rules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Rules\Rules\NoChainedAssertionsRule;
use Rules\Support\AssertionForms;

/**
 * @extends RuleTestCase<NoChainedAssertionsRule>
 */
final class NoChainedAssertionsRuleTest extends RuleTestCase
{
    public function test_rule(): void
    {
        $message = 'This assertion chain discards type evidence and fabricates a new type. Keep the original precise type, or parse untrusted input once at its boundary.';

        $this->analyse([__DIR__.'/data/no-chained-assertions.php'], [
            [$message, 9],
            [$message, 11],
            [$message, 11],
        ]);
    }

    protected function getRule(): Rule
    {
        return new NoChainedAssertionsRule(new AssertionForms);
    }
}
