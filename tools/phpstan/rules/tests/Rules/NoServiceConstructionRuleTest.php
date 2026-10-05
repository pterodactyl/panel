<?php

declare(strict_types=1);

namespace Rules\Tests\Rules;

use Rules\Laravel\Rules\NoServiceConstructionRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoServiceConstructionRule>
 */
final class NoServiceConstructionRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoServiceConstructionRule(['App\\Services\\'], ['app/Providers/**']);
    }

    public function testRule(): void
    {
        $message = 'Runtime code constructs the service `App\Services\ReportBuilder` instead of receiving it. Inject it through the constructor and let the container own the wiring.';

        $this->analyse([__DIR__.'/data/no-service-construction.php'], [
            [$message, 24],
            [$message, 29],
            [$message, 34],
        ]);
    }
}
