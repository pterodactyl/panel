<?php

declare(strict_types=1);

namespace Rules\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Rules\Rules\NoUnsafeDictionaryPropertyRule;
use Rules\Support\TypeClassifier;

/**
 * @extends RuleTestCase<NoUnsafeDictionaryPropertyRule>
 */
final class NoUnsafeDictionaryPropertyRuleTest extends RuleTestCase
{
    public function test_rule(): void
    {
        $this->analyse([__DIR__.'/data/no-unsafe-dictionary-property.php'], [
            ['Property `$meta` is a dictionary of `mixed` values, which stores data without a value contract. Declare an `array{...}` shape or a readonly DTO; parse external payloads before insertion.', 10],
            ['Property `$bag` is a dictionary of `object` values, which stores data without a value contract. Declare an `array{...}` shape or a readonly DTO; parse external payloads before insertion.', 13],
            ['Property `$blob` is typed `stdClass`, an anonymous bag with no contract. Declare a named readonly DTO and parse external payloads into it.', 15],
        ]);
    }

    protected function getRule(): Rule
    {
        return new NoUnsafeDictionaryPropertyRule(new TypeClassifier);
    }
}
