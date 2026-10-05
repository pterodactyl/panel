<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\ActivityLogTranslationTest;

use Pterodactyl\Tests\TestCase;
use Symfony\Component\Finder\Finder;

uses(TestCase::class);

/**
 * @return list<string>
 */
function loggedEventNames(): array
{
    $events = [];
    foreach (Finder::create()->files()->in(base_path('app'))->name('*.php') as $file) {
        preg_match_all("/event\(\s*'([a-z0-9-]+:[a-z0-9._-]+)'/i", $file->getContents(), $matches);
        $events = array_merge($events, $matches[1]);
    }

    return array_values(array_unique($events));
}

test('every activity event name has a translation', function () {
    $events = loggedEventNames();
    expect($events)->not->toBeEmpty();

    $missing = [];
    foreach ($events as $event) {
        $key = 'activity.'.str_replace(':', '.', $event);
        if (! trans()->has($key) && ! trans()->has($key.'_one')) {
            $missing[] = $event;
        }
    }

    expect($missing)->toBe([]);
});

test('every translation placeholder is a property the event always records', function () {
    $recorded = [];
    foreach (Finder::create()->files()->in(base_path('app'))->name('*.php') as $file) {
        preg_match_all("/Activity::event\(\s*'([a-z0-9-]+:[a-z0-9._-]+)'(.*?);/is", $file->getContents(), $chains, PREG_SET_ORDER);
        foreach ($chains as $chain) {
            preg_match_all("/->property\(\s*\[?\s*'([a-z0-9_.-]+)'/i", $chain[2], $props);
            $keys = $props[1];
            preg_match_all("/'([a-z0-9_.-]+)'\s*=>/i", $chain[2], $inline);
            $keys = array_merge($keys, $inline[1]);
            if (str_contains($chain[2], 'withRequestMetadata')) {
                $keys = array_merge($keys, ['ip', 'useragent']);
            }

            $recorded[$chain[1]] = isset($recorded[$chain[1]])
                ? array_intersect($recorded[$chain[1]], $keys)
                : $keys;
        }
    }

    $unmet = [];
    foreach ($recorded as $event => $properties) {
        $key = 'activity.'.str_replace(':', '.', $event);
        foreach ([$key, $key.'_one', $key.'_other'] as $candidate) {
            if (! trans()->has($candidate)) {
                continue;
            }

            $line = trans($candidate);
            expect($line)->toBeString();
            preg_match_all('/:([\w.-]+\w)/', $line, $matches);
            foreach ($matches[1] as $placeholder) {
                $root = explode('.', $placeholder)[0];
                $available = array_merge($properties, ['count'], array_map(fn (string $p): string => $p.'_count', $properties));
                if (! in_array($root, $available, true)) {
                    $unmet[] = "$candidate expects :$root, recorded: ".(implode(', ', $properties) ?: 'nothing');
                }
            }
        }
    }

    expect($unmet)->toBe([]);
});
