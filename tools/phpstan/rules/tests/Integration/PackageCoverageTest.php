<?php

declare(strict_types=1);

namespace Rules\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Proves the rule-6 and rule-7 coverage that comes from packages
 * (spaze/phpstan-disallowed-calls, symplify/phpstan-rules) rather than custom
 * rules, by running a real phpstan analysis over a fixture and asserting the
 * reported identifiers. Per anti-slop's working method, custom code is only
 * written where a fixture proves a package gap.
 */
final class PackageCoverageTest extends TestCase
{
    public function testPackagesCoverDynamicAccessAndRuntimeTypeChecks(): void
    {
        $projectRoot = dirname(__DIR__, 5);
        $command = sprintf(
            '%s -d memory_limit=1G %s analyse -c %s --error-format=json --no-progress 2>/dev/null',
            escapeshellarg(PHP_BINARY),
            escapeshellarg($projectRoot.'/vendor/bin/phpstan'),
            escapeshellarg(__DIR__.'/integration.neon'),
        );

        exec($command, $output, $exitCode);
        self::assertSame(1, $exitCode, 'phpstan should report errors for the fixture');

        // Local tooling may prefix phpstan's stdout with guidance text; the
        // JSON report starts at the first brace.
        $raw = implode('', $output);
        $braceOffset = strpos($raw, '{');
        self::assertIsInt($braceOffset, 'no JSON object found in phpstan output');
        $report = json_decode(substr($raw, $braceOffset), true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($report);

        $messages = [];
        foreach ($report['files'] as $file) {
            foreach ($file['messages'] as $message) {
                $messages[] = [$message['identifier'] ?? '?', $message['line']];
            }
        }

        $count = static fn (string $identifier): int => count(
            array_filter($messages, static fn (array $entry): bool => $entry[0] === $identifier),
        );

        // Dynamic property/brace/method names: symplify NoDynamicNameRule.
        self::assertSame(3, $count('symplify.noDynamicName'), var_export($messages, true));
        // The dynamic static-property form comes from phpstan-strict-rules'
        // *.dynamicName family, which also doubles the instance forms.
        $dynamicNameFamily = count(array_filter(
            $messages,
            static fn (array $entry): bool => str_ends_with($entry[0], '.dynamicName'),
        ));
        self::assertSame(4, $dynamicNameFamily, var_export($messages, true));
        // call_user_func / call_user_func_array / ReflectionProperty::getValue: spaze.
        self::assertSame(3, $count('rules.noDynamicAccess'), var_export($messages, true));
        // is_string / is_numeric outside a boundary path: spaze.
        self::assertSame(2, $count('rules.noRuntimeTypeChecks'), var_export($messages, true));
    }
}
