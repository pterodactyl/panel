<?php

declare(strict_types=1);

namespace Rules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Rules\Rules\NoChainedVarTagsRule;
use Rules\Support\AssertionForms;

/**
 * @extends RuleTestCase<NoChainedVarTagsRule>
 */
final class NoChainedVarTagsRuleTest extends RuleTestCase
{
    public function test_rule(): void
    {
        $this->analyse([__DIR__.'/data/no-chained-var-tags.php'], [
            ['Consecutive `@var` docblocks re-narrow `$decoded` without new evidence. Keep the original precise type, or parse untrusted input once at its boundary.', 14],
        ]);
    }

    protected function getRule(): Rule
    {
        return new NoChainedVarTagsRule(new AssertionForms);
    }
}
