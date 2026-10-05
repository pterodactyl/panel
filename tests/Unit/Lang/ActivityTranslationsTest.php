<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Lang\ActivityTranslationsTest;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use PhpToken;
use Pterodactyl\Tests\TestCase;
use Symfony\Component\Finder\SplFileInfo;

uses(TestCase::class);
test('every activity event logged by the panel has an english description', function (): void {
    $events = collect(File::allFiles(app_path()))
        ->filter(fn (SplFileInfo $file): bool => $file->getExtension() === 'php')
        ->flatMap(fn (SplFileInfo $file): array => loggedActivityEvents($file->getContents()))
        ->unique()
        ->sort()
        ->values();

    expect($events)->not->toBeEmpty()
        ->and($events->reject(fn (string $event): bool => preg_match('/^[a-z][\w-]*:[\w.-]+$/', $event) === 1)->values()->all())->toBe([]);

    $missing = $events->reject(function (string $event): bool {
        $key = 'activity.'.str_replace(':', '.', $event);

        return Lang::has($key, 'en', false) || Lang::has($key.'_one', 'en', false);
    })->values()->all();

    expect($missing)->toBe([]);
});
/**
 * Returns the string literals passed as the event name to Activity::event() calls,
 * including both branches of a ternary but not array keys read inside the condition.
 *
 * @return list<string>
 */
function loggedActivityEvents(string $source): array
{
    $tokens = array_values(array_filter(PhpToken::tokenize($source), fn (PhpToken $token): bool => ! $token->isIgnorable()));
    $events = [];

    foreach ($tokens as $index => $token) {
        if (! $token->is('Activity') || ! ($tokens[$index + 1] ?? null)?->is(T_DOUBLE_COLON) || ! ($tokens[$index + 2] ?? null)?->is('event')) {
            continue;
        }

        $depth = 0;
        $counter = count($tokens);
        for ($position = $index + 3; $position < $counter; $position++) {
            $current = $tokens[$position];
            $depth += match ($current->text) {
                '(', '[' => 1,
                ')', ']' => -1,
                default => 0,
            };

            if ($depth === 0) {
                break;
            }

            if ($depth === 1 && $current->is(T_CONSTANT_ENCAPSED_STRING)) {
                $events[] = mb_substr($current->text, 1, -1);
            }
        }
    }

    return $events;
}
