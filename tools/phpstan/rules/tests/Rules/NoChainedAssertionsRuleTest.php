<?php

declare(strict_types=1);

namespace Rules\Tests\Rules;

use Rules\Rules\NoChainedAssertionsRule;
use Rules\Support\AssertionForms;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoChainedAssertionsRule>
 */
final class NoChainedAssertionsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoChainedAssertionsRule(new AssertionForms);
    }

    public function testRule(): void
    {
        $message = 'This assertion chain discards type evidence and fabricates a new type. Keep the original precise type, or parse untrusted input once at its boundary.';

        $this->analyse([__DIR__.'/data/no-chained-assertions.php'], [
            [$message, 9],
            [$message, 11],
            [$message, 11],
        ]);
    }
}
