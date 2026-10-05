<?php

declare(strict_types=1);

namespace Rules\Tests\Rules;

use Rules\Rules\RequireSafetyCommentForAssertionRule;
use Rules\Support\AssertionForms;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RequireSafetyCommentForAssertionRule>
 */
final class RequireSafetyCommentForAssertionRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new RequireSafetyCommentForAssertionRule(new AssertionForms);
    }

    public function testRule(): void
    {
        $assertion = 'This type assertion has no `SAFETY:` justification. State the checked invariant immediately before the assertion or its containing statement.';
        $varTag = 'This inline `@var` overrides inference without a `SAFETY:` justification. State the checked invariant in the docblock or immediately before the statement.';

        $this->analyse([__DIR__.'/data/require-safety-comment.php'], [
            [$assertion, 14],
            [$varTag, 20],
            [$assertion, 25],
            [$assertion, 30],
        ]);
    }
}
