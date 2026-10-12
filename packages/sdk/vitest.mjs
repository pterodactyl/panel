import { fileURLToPath } from 'node:url';
import { createRequire } from 'node:module';
import { readFileSync, readdirSync } from 'node:fs';
import { resolve } from 'node:path';
import { themeParents } from './theme-chain.mjs';

/**
 * Merge these aliases into Vitest's resolve.alias to use the real SDK runtime. A theme that extends
 * others also gets `<id>/dist/…` for each of them, found as its build finds them (`extensions`, the
 * panel's extensions directory, then node_modules), so its tests import `aurora/dist/client.js`.
 */
export function pterodactylTestAliases({ root = process.cwd(), extensions } = {}) {
    const require = createRequire(resolve(root, 'package.json'));
    const runtime = fileURLToPath(new URL('./testing-runtime/', import.meta.url));
    const peers = Object.keys(JSON.parse(readFileSync(new URL('./package.json', import.meta.url), 'utf8')).peerDependencies);
    const imports = new Set(readdirSync(runtime).filter(file => file.endsWith('.js')).flatMap(file =>
        [...readFileSync(resolve(runtime, file), 'utf8').matchAll(/(?:from\s*|import\s*)["']([^"']+)["']/g)].map(match => match[1])
    ));
    const shared = [...imports].filter(id => peers.some(peer => id === peer || id.startsWith(`${peer}/`)));
    const escape = (id) => id.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    // A parent that cannot be found fails the import that names it, not every test.
    let parents = [];
    try {
        parents = themeParents({ root, extensions });
    } catch {}
    return [
        {find: /^@pterodactyl\/sdk\/testing$/, replacement: fileURLToPath(new URL('./testing-runtime/testing.js', import.meta.url))},
        {find: /^@pterodactyl\/sdk\/api$/, replacement: fileURLToPath(new URL('./testing-runtime/api.js', import.meta.url))},
        {find: /^@pterodactyl\/sdk\/theme$/, replacement: fileURLToPath(new URL('./testing-runtime/theme.js', import.meta.url))},
        {find: /^@pterodactyl\/sdk$/, replacement: fileURLToPath(new URL('./testing-runtime/sdk.js', import.meta.url))},
        // Resolve peers from the consumer even when a local SDK dependency is symlinked.
        ...shared.map(id => ({ find: new RegExp(`^${escape(id)}$`), replacement: require.resolve(id) })),
        ...parents.map(({ id, directory }) => ({ find: new RegExp(`^${escape(id)}/dist/`), replacement: `${resolve(directory, 'dist')}/` })),
    ];
}

export const pterodactylTestSetup = fileURLToPath(new URL('./vitest-setup.mjs', import.meta.url));
export const pterodactylTestDirectory = fileURLToPath(new URL('.', import.meta.url));
