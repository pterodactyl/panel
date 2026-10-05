<?php

declare(strict_types=1);

namespace Rules\Tests\Rules;

use Rules\Rules\NoUnsafeDictionaryTypeRule;
use Rules\Support\SignatureResolver;
use Rules\Support\TypeClassifier;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoUnsafeDictionaryTypeRule>
 */
final class NoUnsafeDictionaryTypeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoUnsafeDictionaryTypeRule(
            new SignatureResolver($this->createReflectionProvider()),
            new TypeClassifier,
        );
    }

    public function testRule(): void
    {
        $parameter = "Parameter `\$%s` is a dictionary of `%s` values, which gives callers no value contract. Declare an `array{...}` shape or a readonly DTO; parse external payloads (cuyz/valinor, spatie/laravel-data) before insertion.";

        $this->analyse([__DIR__.'/data/no-unsafe-dictionary-type.php'], [
            [sprintf($parameter, 'meta', 'mixed'), 10],
            [sprintf($parameter, 'bag', 'object'), 15],
            [sprintf($parameter, 'nested', 'untyped array'), 20],
            [sprintf($parameter, 'items', 'mixed'), 25],
            [sprintf($parameter, 'stream', 'mixed'), 30],
            [sprintf($parameter, 'unionValues', 'object'), 35],
            [sprintf($parameter, 'rows', 'stdClass'), 40],
            ['This function returns a dictionary of `mixed` values, which gives callers no value contract. Declare an `array{...}` shape or a readonly DTO; parse external payloads before returning them.', 45],
        ]);
    }
}
