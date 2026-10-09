<?php

declare(strict_types=1);

namespace Rules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Rules\Rules\NoKnownValueWideningPropertyRule;
use Rules\Support\TypeClassifier;

/**
 * @extends RuleTestCase<NoKnownValueWideningPropertyRule>
 */
final class NoKnownValueWideningRuleTest extends RuleTestCase
{
    public function test_rule(): void
    {
        $message = 'The broad declared type on property `$%s` discards its default value\'s evidence. Delete the annotation and let inference stand, or introduce a named shape.';

        $this->analyse([__DIR__.'/data/no-known-value-widening.php'], [
            [sprintf($message, 'handlers'), 10],
            [sprintf($message, 'marker'), 20],
        ]);
    }

    protected function getRule(): Rule
    {
        return new NoKnownValueWideningPropertyRule(new TypeClassifier);
    }
}
