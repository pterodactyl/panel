import { readFileSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createServer } from 'vite';
import react from '@vitejs/plugin-react-swc';
import { classicViewSources } from './classic-sources.mjs';

// Loads the engine's view definitions and writes the catalog, keyed by name, `p:extension:doctor` validates names
// against; `classic` names the file and export `pterodactyl-theme eject` copies. `slots` are the slots a layer of
// the view renders, `engineSlots` those the engine renders around a page, and `areaSlots` those of the area layouts,
// which a `layout.app` layer renders where it draws an area's navigation.
const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const output = resolve(root, 'packages/sdk/views.json');
const classic = classicViewSources(resolve(root, 'resources/scripts/themes/classic'));
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
    const { loadViewCatalog } = await server.ssrLoadModule('/resources/scripts/views/catalog.ts');
    const { pageSlots } = await server.ssrLoadModule('/resources/scripts/views/pages.ts');
    const views = await loadViewCatalog();
    const slotsOf = (name) => views.find((view) => view.name === name)?.slots ?? [];
    catalog = Object.fromEntries(
        views.map((view) => {
            const engineSlots = view.kind === 'page' ? [...(pageSlots(view.name) ?? [])] : [];

            return [
                view.name,
                {
                    kind: view.kind,
                    context: view.context,
                    slots: (view.slots ?? []).filter((slot) => !engineSlots.includes(slot)),
                    ...(engineSlots.length ? { engineSlots } : {}),
                    ...(view.name === 'layout.app'
                        ? { areaSlots: ['layout.account', 'layout.server', 'layout.admin'].flatMap(slotsOf) }
                        : {}),
                    fixtures: Object.keys(view.fixtures ?? {}),
                    ...(classic[view.name] ? { classic: classic[view.name] } : {}),
                },
            ];
        })
    );
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
console.log(`SDK: ${Object.keys(catalog).length} views`);
