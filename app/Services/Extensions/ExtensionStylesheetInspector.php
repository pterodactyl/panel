<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Illuminate\Support\Facades\File;

/**
 * Extension stylesheets load after the panel's and after each other, all into the same
 * `theme` and `utilities` cascade layers, and every one of them is sorted on its own. Two
 * builds that share a class name therefore override each other (a later `.grid-cols-2`
 * beating an earlier `.sm:grid-cols-3`), so each extension builds Tailwind with the prefix
 * it declares under `ui.prefix`. This finds a build that does not before it is installed;
 * an extension's hand-written classes outside those two layers are left alone.
 */
final class ExtensionStylesheetInspector
{
    /** Comments, escapes and strings are single tokens, so the braces inside them open nothing. */
    private const string TOKENS = '~/\*.*?(?:\*/|$)|\\\\.|"[^"\\\\]*+(?:\\\\.[^"\\\\]*+)*+"?|\'[^\'\\\\]*+(?:\\\\.[^\'\\\\]*+)*+\'?|[{};,()\[\]]|[^/\\\\"\'{};,()\[\]]++|/~s';

    private const int REPORTED = 5;

    /** Why the built stylesheets of an extension would break the panel or another extension, or null when they are safe. */
    public function conflictReason(ExtensionManifest $manifest): ?string
    {
        if (! $manifest->hasUi() || ! is_dir($manifest->path('dist'))) {
            return null;
        }

        foreach (ExtensionDistFiles::list($manifest->path('dist')) as $relative => $path) {
            if (pathinfo($path, PATHINFO_EXTENSION) !== 'css') {
                continue;
            }

            $foreign = $this->foreign(File::get($path), $manifest->uiPrefix);
            $found = [...$foreign['utilities'], ...$foreign['theme']];
            if ($found === []) {
                continue;
            }

            $listed = implode(', ', array_slice($found, 0, self::REPORTED)).(count($found) > self::REPORTED ? ', ...' : '');
            $what = match (true) {
                $foreign['utilities'] === [] => 'Tailwind theme variables',
                $foreign['theme'] === [] => 'Tailwind utilities',
                default => 'Tailwind utilities and theme variables',
            };
            $prefix = $manifest->uiPrefix ?? ExtensionManifest::defaultUiPrefix($manifest->id);
            $problem = $manifest->uiPrefix === null
                ? "but declares no Tailwind prefix - add \"prefix\": \"{$prefix}\" to \"ui\" in ".ExtensionManifest::FILENAME.', build'
                : "without its \"{$prefix}\" prefix, which override the panel's or another extension's styles - build";

            return "Extension \"{$manifest->id}\" ships {$what} in dist/{$relative} ({$listed}) {$problem} Tailwind with `prefix({$prefix})`, write its classes as `{$prefix}:flex` (see the SDK README, \"Styling\") and rebuild.";
        }

        return null;
    }

    /**
     * The selectors in `@layer utilities` that are not built on a utility class with the
     * given prefix and the custom properties declared in `@layer theme` without it. With no
     * prefix every rule and property in those layers is reported.
     *
     * @return array{utilities: list<string>, theme: list<string>}
     */
    public function foreign(string $css, ?string $prefix): array
    {
        $class = $prefix === null ? null : '.'.$prefix.'\\:';
        $property = $prefix === null ? null : '--'.$prefix.'-';
        $utilities = [];
        $theme = [];
        // The prelude of every block that is open at the cursor.
        $blocks = [];
        // The prelude or declaration being read: its finished comma separated parts and the open one.
        $parts = [];
        $part = '';
        $depth = 0;
        preg_match_all(self::TOKENS, $css, $tokens);

        foreach ($tokens[0] as $token) {
            if (str_starts_with($token, '/*')) {
                continue;
            }

            if ($token === ',' && $depth === 0) {
                $parts[] = $part;
                $part = '';

                continue;
            }

            if (! in_array($token, ['{', '}', ';'], true)) {
                $depth += match ($token) {
                    '(', '[' => 1,
                    ')', ']' => -1,
                    default => 0,
                };
                $part .= $token;

                continue;
            }

            $selectors = array_map(trim(...), [...$parts, $part]);
            if ($token === '{') {
                if ($this->layer($blocks) === 'utilities' && ! str_starts_with($selectors[0], '@') && $this->groupsRules($blocks)) {
                    $utilities = [...$utilities, ...array_filter($selectors, fn (string $selector): bool => $selector !== '' && ($class === null || ! str_contains($selector, $class)))];
                }

                $blocks[] = implode(',', $selectors);
            } elseif ($this->layer($blocks) === 'theme' && preg_match('/^(--[\w-]+)\s*:/', $selectors[0], $matches) === 1 && ($property === null || ! str_starts_with($matches[1], $property))) {
                $theme[] = $matches[1];
            }

            if ($token === '}') {
                array_pop($blocks);
            }

            $parts = [];
            $part = '';
            $depth = 0;
        }

        return ['utilities' => array_values(array_unique($utilities)), 'theme' => array_values(array_unique($theme))];
    }

    /**
     * The cascade layer the open blocks sit in.
     *
     * @param  list<string>  $blocks
     */
    private function layer(array $blocks): ?string
    {
        foreach ($blocks as $prelude) {
            if (preg_match('/^@layer\s+([\w.-]+)$/', $prelude, $matches) === 1) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Whether every open block only groups style rules, so the next rule is a top-level one
     * rather than a nested rule (`&:hover`) or a keyframe. A minifier may still hoist the
     * utility class into a pseudo-class (`:where(.hw\:space-y-2 > *)`), which is why the
     * class is looked for anywhere in such a rule's selector.
     *
     * @param  list<string>  $blocks
     */
    private function groupsRules(array $blocks): bool
    {
        return array_all($blocks, fn (string $prelude): bool => preg_match('/^@(layer|media|supports|container|starting-style)\b/', $prelude) === 1);
    }
}
