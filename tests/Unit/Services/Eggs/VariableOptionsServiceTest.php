<?php

declare(strict_types=1);

use Pterodactyl\Services\Eggs\VariableOptionsService;

test('paper version arrays are flattened and prereleases can be filtered out', function (): void {
    $options = app(VariableOptionsService::class)->optionsFromPayload([
        'versions' => [
            '1.21' => ['1.21.11', '1.21.11-rc1'],
            '1.20' => ['1.20.6'],
        ],
    ], [
        'data_path' => 'versions.*',
        'value_path' => '$value',
        'filters' => [['path' => '$value', 'operator' => 'not_contains', 'value' => '-rc']],
    ]);

    expect($options)->toBe([
        ['label' => '1.21.11', 'value' => '1.21.11'],
        ['label' => '1.20.6', 'value' => '1.20.6'],
    ]);
});

test('mojang object arrays use paths and can exclude snapshots', function (): void {
    $options = app(VariableOptionsService::class)->optionsFromPayload([
        'versions' => [
            ['id' => '26.4-snapshot-4', 'type' => 'snapshot'],
            ['id' => '26.3', 'type' => 'release'],
        ],
    ], [
        'data_path' => 'versions',
        'value_path' => 'id',
        'label_path' => 'id',
        'filters' => [['path' => 'type', 'operator' => 'not_equals', 'value' => 'snapshot']],
    ]);

    expect($options)->toBe([['label' => '26.3', 'value' => '26.3']]);
});

test('forge maps can select and filter their keys', function (): void {
    $options = app(VariableOptionsService::class)->optionsFromPayload([
        'promos' => [
            '1.21.11-latest' => '61.2.4',
            '1.21.11-recommended' => '61.2.0',
        ],
    ], [
        'data_path' => 'promos',
        'value_path' => '$key',
        'label_path' => '$key',
        'strip_suffix' => '-recommended',
        'filters' => [['path' => '$key', 'operator' => 'ends_with', 'value' => '-recommended']],
    ]);

    expect($options)->toBe([['label' => '1.21.11', 'value' => '1.21.11']]);
});
