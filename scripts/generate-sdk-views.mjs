import { readFileSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createServer } from 'vite';
import react from '@vitejs/plugin-react-swc';

// Loads the engine's view definitions and writes the catalog `p:extension:doctor` validates names against.
const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const output = resolve(root, 'packages/sdk/views.json');
const server = await createServer({
    configFile: false,
    root,
    logLevel: 'error',
    appType: 'custom',
    plugins: [react()],
    resolve: { alias: { '@': resolve(root, 'resources/scripts'), '@feature': resolve(root, 'resources/scripts/components/server/features') } },
    server: { middlewareMode: true, hmr: false, ws: false },
    optimizeDeps: { noDiscovery: true, include: [] },
});
let catalog;
try {
    const { getViewDefinitions } = await server.ssrLoadModule('/resources/scripts/views/catalog.ts');
    catalog = getViewDefinitions().map((view) => ({
        name: view.name,
        kind: view.kind,
        context: view.context,
        slots: view.slots ?? [],
        fixtures: Object.keys(view.fixtures ?? {}),
    }));
} finally {
    await server.close();
}
const json = JSON.stringify(catalog, null, 4) + '\n';
if (process.argv.includes('--check')) {
    let existing = '';
    try {
        existing = readFileSync(output, 'utf8');
    } catch {}
    if (existing !== json) {
        console.error('packages/sdk/views.json is stale. Run npm run sdk:generate.');
        process.exit(1);
    }
} else {
    writeFileSync(output, json);
}
console.log(`SDK: ${catalog.length} views`);
