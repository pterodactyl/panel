<?php

declare(strict_types=1);

namespace Rules\Tests\Rules;

use Rules\Rules\NoClassLoadingMocksRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoClassLoadingMocksRule>
 */
final class NoClassLoadingMocksRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoClassLoadingMocksRule($this->createReflectionProvider());
    }

    public function testRule(): void
    {
        $concrete = 'Mocking the concrete class `%s` fakes an implementation instead of a contract. Extract a real interface, or inject a faithful in-memory fake.';
        $loading = 'Mocking `%s` replaces the autoloaded class for the whole process. Extract a real interface and inject a faithful in-memory fake instead.';
        $class = 'Rules\Tests\Data\NoClassLoadingMocks\SystemClock';

        $this->analyse([__DIR__.'/data/no-class-loading-mocks.php'], [
            [sprintf($concrete, $class), 32],
            [sprintf($concrete, $class), 33],
            [sprintf($concrete, $class), 34],
            [sprintf($concrete, $class), 37],
            [sprintf($loading, 'overload:'.$class), 38],
            [sprintf($loading, 'alias:'.$class), 39],
        ]);
    }
}
