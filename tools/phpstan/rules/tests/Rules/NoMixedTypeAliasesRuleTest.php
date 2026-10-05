<?php

declare(strict_types=1);

namespace Rules\Tests\Rules;

use Rules\Rules\NoMixedTypeAliasesRule;
use Rules\Support\TypeAliasDocblock;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoMixedTypeAliasesRule>
 */
final class NoMixedTypeAliasesRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoMixedTypeAliasesRule(new TypeAliasDocblock);
    }

    public function testRule(): void
    {
        $message = 'Type alias `%s` hides `mixed`. Keep `mixed` explicit at the parsing boundary; otherwise alias the parsed owner type.';

        $this->analyse([__DIR__.'/data/no-mixed-type-aliases.php'], [
            [sprintf($message, 'ExternalValue'), 15],
            [sprintf($message, 'MaybeMixed'), 15],
            [sprintf($message, 'LoosePsalm'), 15],
            [sprintf($message, 'Hidden'), 15],
        ]);
    }
}
