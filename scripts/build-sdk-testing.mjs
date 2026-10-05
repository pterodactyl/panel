import { build } from 'vite';
import { esmExternalRequirePlugin } from 'rolldown/plugins';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { readFileSync } from 'node:fs';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const sdk = JSON.parse(readFileSync(resolve(root, 'packages/sdk/package.json'), 'utf8'));
const peers = Object.keys(sdk.peerDependencies);
await build({
    configFile: false,
    root,
    resolve: {alias: {'@': resolve(root, 'resources/scripts'), '@feature': resolve(root, 'resources/scripts/components/server/features')}},
    build: {
        target: 'esnext', minify: false, sourcemap: true, outDir: resolve(root, 'packages/sdk/testing-runtime'), emptyOutDir: true,
        lib: {entry: {sdk: resolve(root, 'resources/scripts/sdk/index.ts'), api: resolve(root, 'resources/scripts/sdk/api.ts'), testing: resolve(root, 'resources/scripts/sdk/testing.tsx')}, formats: ['es']},
        rolldownOptions: {
            plugins: [esmExternalRequirePlugin({external: peers.map(peer => new RegExp(`^${peer.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}(?:/|$)`))})],
            output: {entryFileNames: '[name].js', chunkFileNames: '[name]-[hash].js'},
        },
    },
});
