<?php

declare(strict_types=1);

namespace Rules\Tests\Rules;

use Rules\Rules\NoWidenThenAssertRule;
use Rules\Support\AssertionForms;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoWidenThenAssertRule>
 */
final class NoWidenThenAssertRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoWidenThenAssertRule(new AssertionForms);
    }

    public function testRule(): void
    {
        $message = 'Binding `$%s` discards type evidence and later recreates it with an assertion. Keep the precise type from initialization through use; parse boundary input once.';

        $this->analyse([__DIR__.'/data/no-widen-then-assert.php'], [
            [sprintf($message, 'widened'), 17],
            [sprintf($message, 'data'), 26],
            [sprintf($message, 'vars'), 35],
        ]);
    }
}
