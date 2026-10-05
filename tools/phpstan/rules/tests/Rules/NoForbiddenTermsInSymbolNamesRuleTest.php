<?php

declare(strict_types=1);

namespace Rules\Tests\Rules;

use Rules\Rules\NoForbiddenTermsInSymbolNamesRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoForbiddenTermsInSymbolNamesRule>
 */
final class NoForbiddenTermsInSymbolNamesRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoForbiddenTermsInSymbolNamesRule(['shape']);
    }

    public function testRule(): void
    {
        $message = 'Rename symbol `%s` for its domain role; `shape` describes structure rather than ownership.';

        $this->analyse([__DIR__.'/data/no-forbidden-terms-in-symbol-names.php'], [
            [sprintf($message, 'ResponseShape'), 10],
            [sprintf($message, 'UserShape'), 10],
            [sprintf($message, 'SHAPE_VERSION'), 12],
            [sprintf($message, 'payloadShape'), 15],
            [sprintf($message, 'shapeOf'), 17],
            [sprintf($message, 'shaped'), 17],
            [sprintf($message, 'resultShape'), 19],
            [sprintf($message, 'shaped'), 19],
            [sprintf($message, 'resultShape'), 21],
        ]);
    }
}
