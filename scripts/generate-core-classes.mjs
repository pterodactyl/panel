// Writes every Tailwind class candidate in the panel's markup to packages/sdk/core-classes.txt, which
// theme-entry.css sources so a theme's Tailwind build orders its utilities with the panel's.
import { Scanner } from '@tailwindcss/oxide';
import { writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('..', import.meta.url));
const scanner = new Scanner({
    sources: [
        { base: `${root}resources/scripts`, pattern: '**/*.{ts,tsx}', negated: false },
        { base: `${root}resources/views`, pattern: '**/*.blade.php', negated: false },
    ],
});

writeFileSync(`${root}packages/sdk/core-classes.txt`, `${[...new Set(scanner.scan())].sort().join('\n')}\n`);
