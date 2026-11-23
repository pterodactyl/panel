<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Lang\TranslationKeysTest;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use PhpToken;
use Pterodactyl\Models\Permission;
use Pterodactyl\Tests\TestCase;
use Symfony\Component\Finder\SplFileInfo;

uses(TestCase::class);
test('every translation key used by the panel has an english translation', function (): void {
    $keys = collect(File::allFiles(app_path()))
        ->filter(fn (SplFileInfo $file): bool => $file->getExtension() === 'php')
        ->flatMap(fn (SplFileInfo $file): array => array_map(
            fn (string $key): string => $file->getRelativePathname().': '.$key,
            translationKeys($file->getContents()),
        ))
        ->merge(collect(File::allFiles(resource_path('views')))
            ->filter(fn (SplFileInfo $file): bool => str_ends_with($file->getFilename(), '.blade.php'))
            ->flatMap(function (SplFileInfo $file): array {
                preg_match_all("/(?:\\b__|\\btrans|\\btrans_choice|@lang)\\(\\s*'([^']+)'/", $file->getContents(), $matches);

                return array_map(fn (string $key): string => $file->getRelativePathname().': '.$key, $matches[1]);
            }))
        ->unique()
        ->sort()
        ->values();

    expect($keys)->not->toBeEmpty();

    $missing = $keys->reject(fn (string $usage): bool => Lang::has(explode(': ', $usage, 2)[1], 'en', false))->values()->all();

    expect($missing)->toBe([]);
});
test('every core subuser permission has an english description', function (): void {
    $untranslated = [];
    foreach (Permission::permissions() as $group => $permission) {
        if ($permission['description'] === "permissions.{$group}.description") {
            $untranslated[] = $group;
        }

        foreach ($permission['keys'] as $key => $description) {
            if ($description === "permissions.{$group}.keys.{$key}") {
                $untranslated[] = "{$group}.{$key}";
            }
        }
    }

    expect($untranslated)->toBe([]);
});
/**
 * @return list<string>
 */
function translationKeys(string $source): array
{
    $tokens = array_values(array_filter(PhpToken::tokenize($source), fn (PhpToken $token): bool => ! $token->isIgnorable()));
    $keys = [];

    foreach ($tokens as $index => $token) {
        $previous = $tokens[$index - 1] ?? null;
        $offset = match (true) {
            $token->is(['__', 'trans', 'trans_choice']) && ! $previous?->is([T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION]) => 1,
            $token->is('Lang') && ($tokens[$index + 1] ?? null)?->is(T_DOUBLE_COLON) && ($tokens[$index + 2] ?? null)?->is(['get', 'has', 'choice']) => 3,
            $token->is('$fail') && ($tokens[$index + 4] ?? null)?->is(T_OBJECT_OPERATOR) && ($tokens[$index + 5] ?? null)?->is('translate') => 1,
            default => null,
        };

        if ($offset === null) {
            continue;
        }

        $open = $tokens[$index + $offset] ?? null;
        $key = $tokens[$index + $offset + 1] ?? null;
        $after = $tokens[$index + $offset + 2] ?? null;
        if ($open?->is('(') && $key?->is(T_CONSTANT_ENCAPSED_STRING) && $after?->is([',', ')'])) {
            $keys[] = mb_substr($key->text, 1, -1);
        }
    }

    return $keys;
}
