import { mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { resolve } from 'node:path';
import { pathToFileURL } from 'node:url';
import { spawnSync } from 'node:child_process';
import { build } from 'vite';
import { expect, it } from 'vitest';
import { defineExtensionConfig } from './vite.mjs';

it('loads extracted entry styles before setup and rejects unavailable styles', async () => {
    const directory = mkdtempSync(resolve(tmpdir(), 'extension-styles-'));
    try {
        writeFileSync(
            resolve(directory, 'entry.js'),
            "import './style.css'; export default { setup() { return 'ready'; } };"
        );
        writeFileSync(resolve(directory, 'style.css'), '.extension-probe { display: grid; }');
        await build({
            ...defineExtensionConfig({ entry: resolve(directory, 'entry.js'), outDir: resolve(directory, 'dist') }),
            configFile: false,
            logLevel: 'silent',
        });
        const entry = pathToFileURL(resolve(directory, 'dist/client.js')).href;
        const run = (failure: boolean) =>
            spawnSync(
                process.execPath,
                [
                    '--input-type=module',
                    '-e',
                    `
            const links = [];
            globalThis.document = {
                createElement: () => ({ remove() {} }),
                head: { append(link) { links.push(link.href); setImmediate(() => link.${failure ? 'onerror' : 'onload'}()); } }
            };
            try {
                const module = await import(${JSON.stringify(entry)});
                console.log(JSON.stringify({ result: module.default.setup(), links }));
            } catch (error) { console.error(error.message); process.exitCode = 1; }
        `,
                ],
                { encoding: 'utf8' }
            );

        const success = run(false);
        expect(success.status, success.stderr).toBe(0);
        const result = JSON.parse(success.stdout);
        expect(result.result).toBe('ready');
        expect(result.links).toHaveLength(1);
        expect(result.links[0]).toMatch(/\/dist\/entry\.[\w-]+\.css$/);
        const failure = run(true);
        expect(failure.status).toBe(1);
        expect(failure.stderr).toContain('Unable to load extension stylesheet');
        expect(JSON.parse(readFileSync(resolve(directory, 'dist/client.js.map'), 'utf8')).mappings).toMatch(/^;+/);
    } finally {
        rmSync(directory, { recursive: true, force: true });
    }
});

it('does not emit a preload map that resolves lazy chunks against the page instead of the bundle', async () => {
    const directory = mkdtempSync(resolve(tmpdir(), 'extension-preload-'));
    try {
        writeFileSync(resolve(directory, 'shared.js'), 'export const shared = () => "shared";');
        writeFileSync(resolve(directory, 'one.js'), "import { shared } from './shared.js'; export default () => shared() + 'one';");
        writeFileSync(resolve(directory, 'two.js'), "import { shared } from './shared.js'; export default () => shared() + 'two';");
        writeFileSync(
            resolve(directory, 'entry.js'),
            "export default { setup() { return [import('./one.js'), import('./two.js')]; } };"
        );
        await build({
            ...defineExtensionConfig({ entry: resolve(directory, 'entry.js'), outDir: resolve(directory, 'dist') }),
            configFile: false,
            logLevel: 'silent',
        });
        // The shared module becomes its own chunk, so the dynamic imports have a dependency to preload.
        const entry = readFileSync(resolve(directory, 'dist/client.js'), 'utf8');
        expect(entry).toContain('import(');
        expect(entry).not.toContain('__vite__mapDeps');
    } finally {
        rmSync(directory, { recursive: true, force: true });
    }
});
