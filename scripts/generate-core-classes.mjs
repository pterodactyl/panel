// Writes every Tailwind class candidate in the panel's markup to packages/sdk/core-classes.txt,
// which a theme stylesheet sources so it can replace the panel's, and the list's hash beside
// the build, where Blade compares it with a theme's dist/theme.json.
import { Scanner } from '@tailwindcss/oxide';
import { createHash } from 'node:crypto';
import { mkdirSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('..', import.meta.url));
const scanner = new Scanner({
    sources: [
        { base: `${root}resources/scripts`, pattern: '**/*.{ts,tsx}', negated: false },
        { base: `${root}resources/views`, pattern: '**/*.blade.php', negated: false },
    ],
});
const contents = `${[...new Set(scanner.scan())].sort().join('\n')}\n`;

writeFileSync(`${root}packages/sdk/core-classes.txt`, contents);
mkdirSync(`${root}public/assets`, { recursive: true });
writeFileSync(
    `${root}public/assets/core-classes.json`,
    `${JSON.stringify({ hash: createHash('sha256').update(contents).digest('hex').slice(0, 16) })}\n`
);
