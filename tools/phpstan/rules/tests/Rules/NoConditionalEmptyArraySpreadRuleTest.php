<?php

declare(strict_types=1);

namespace Rules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Rules\Rules\NoConditionalEmptyArraySpreadRule;

/**
 * @extends RuleTestCase<NoConditionalEmptyArraySpreadRule>
 */
final class NoConditionalEmptyArraySpreadRuleTest extends RuleTestCase
{
    public function test_rule(): void
    {
        $message = 'This conditional spread hides key omission behind an empty array. Build the array in separate statements and add the key only when present.';

        $this->analyse([__DIR__.'/data/no-conditional-empty-array-spread.php'], [
            [$message, 11],
            [$message, 14],
            [$message, 16],
            [$message, 18],
        ]);
    }

    protected function getRule(): Rule
    {
        return new NoConditionalEmptyArraySpreadRule;
    }
}
