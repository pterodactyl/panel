<?php

declare(strict_types=1);

namespace Rules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Rules\Rules\NoKnownValueWideningReturnRule;
use Rules\Support\TypeClassifier;

/**
 * @extends RuleTestCase<NoKnownValueWideningReturnRule>
 */
final class NoKnownValueWideningReturnRuleTest extends RuleTestCase
{
    public function test_rule(): void
    {
        $this->analyse([__DIR__.'/data/no-known-value-widening.php'], [
            ['The broad declared return type discards the known evidence of every returned value. Delete the annotation and let inference stand, or introduce a named shape.', 28],
        ]);
    }

    protected function getRule(): Rule
    {
        return new NoKnownValueWideningReturnRule(new TypeClassifier);
    }
}
