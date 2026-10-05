<?php

declare(strict_types=1);

namespace Rules\Support;

/**
 * Lexical reader for `@phpstan-type` / `@psalm-type` declarations on a class
 * docblock. The TypeScript original resolved alias chains lexically within one
 * file; this mirrors that by resolving chains within one docblock. Resolving
 * through `@phpstan-import-type` would need PHPStan's internal TypeNodeResolver
 * service, which is not public API — that limitation is documented.
 */
final class TypeAliasDocblock
{
    /**
     * @return array<string, string> alias name => declared type string
     */
    public function aliases(string $docComment): array
    {
        preg_match_all(
            '/@(?:phpstan|psalm)-type\s+(\w+)\s*=?\s*([^\r\n]*?)\s*(?:\*\/|\r|\n)/',
            $docComment,
            $matches,
            PREG_SET_ORDER,
        );

        $aliases = [];
        foreach ($matches as $match) {
            $aliases[$match[1]] = rtrim(trim($match[2]), '*/ ');
        }

        return $aliases;
    }

    /**
     * Whether the alias body is `mixed` or a top-level union containing it,
     * following references to other aliases declared in the same docblock.
     *
     * @param  array<string, string>  $aliases
     * @param  array<string, true>  $visiting
     */
    public function resolvesToMixed(string $body, array $aliases, array $visiting = []): bool
    {
        foreach ($this->topLevelUnionParts($body) as $part) {
            if (strcasecmp($part, 'mixed') === 0) {
                return true;
            }

            if (isset($aliases[$part]) && ! isset($visiting[$part])
                && $this->resolvesToMixed($aliases[$part], $aliases, $visiting + [$part => true])
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function topLevelUnionParts(string $body): array
    {
        $parts = [];
        $depth = 0;
        $current = '';
        foreach (str_split($body) as $character) {
            if ($character === '<' || $character === '{' || $character === '(' || $character === '[') {
                $depth++;
            } elseif ($character === '>' || $character === '}' || $character === ')' || $character === ']') {
                $depth--;
            } elseif ($character === '|' && $depth === 0) {
                $parts[] = trim($current);
                $current = '';

                continue;
            }

            $current .= $character;
        }

        $parts[] = trim($current);

        return array_values(array_filter($parts, static fn (string $part): bool => $part !== ''));
    }
}
