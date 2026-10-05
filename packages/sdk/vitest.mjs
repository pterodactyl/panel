import { fileURLToPath } from 'node:url';
import { createRequire } from 'node:module';
import { readFileSync, readdirSync } from 'node:fs';
import { resolve } from 'node:path';

/** Merge these aliases into Vitest's resolve.alias to use the real SDK runtime. */
export function pterodactylTestAliases({ root = process.cwd() } = {}) {
    const require = createRequire(resolve(root, 'package.json'));
    const runtime = fileURLToPath(new URL('./testing-runtime/', import.meta.url));
    const peers = Object.keys(JSON.parse(readFileSync(new URL('./package.json', import.meta.url), 'utf8')).peerDependencies);
    const imports = new Set(readdirSync(runtime).filter(file => file.endsWith('.js')).flatMap(file =>
        [...readFileSync(resolve(runtime, file), 'utf8').matchAll(/(?:from\s*|import\s*)["']([^"']+)["']/g)].map(match => match[1])
    ));
    const shared = [...imports].filter(id => peers.some(peer => id === peer || id.startsWith(`${peer}/`)));
    return [
        {find: /^@pterodactyl\/sdk\/testing$/, replacement: fileURLToPath(new URL('./testing-runtime/testing.js', import.meta.url))},
        {find: /^@pterodactyl\/sdk\/api$/, replacement: fileURLToPath(new URL('./testing-runtime/api.js', import.meta.url))},
        {find: /^@pterodactyl\/sdk$/, replacement: fileURLToPath(new URL('./testing-runtime/sdk.js', import.meta.url))},
        // Resolve peers from the consumer even when a local SDK dependency is symlinked.
        ...shared.map(id => ({ find: new RegExp(`^${id.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}$`), replacement: require.resolve(id) })),
    ];
}

export const pterodactylTestSetup = fileURLToPath(new URL('./vitest-setup.mjs', import.meta.url));
export const pterodactylTestDirectory = fileURLToPath(new URL('.', import.meta.url));
