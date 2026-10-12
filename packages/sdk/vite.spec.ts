import { mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { resolve } from 'node:path';
import { pathToFileURL } from 'node:url';
import { spawnSync } from 'node:child_process';
import { build } from 'vite';
import { expect, it } from 'vitest';
import { defineExtensionConfig, defineThemeConfig } from './vite.mjs';

const buildTheme = async (directory: string, files: Record<string, string>, entry = true) => {
    for (const [name, contents] of Object.entries(files)) {
        writeFileSync(resolve(directory, name), contents);
    }

    await build({
        ...defineThemeConfig({
            entry: entry ? resolve(directory, 'theme.jsx') : undefined,
            stylesheet: resolve(directory, 'theme.css'),
            outDir: resolve(directory, 'dist'),
        }),
        configFile: false,
        logLevel: 'silent',
    });

    return (file: string) => readFileSync(resolve(directory, 'dist', file), 'utf8');
};

it('builds a theme entry without a style loader, its stylesheet and its build info', async () => {
    const directory = mkdtempSync(resolve(tmpdir(), 'theme-build-'));
    try {
        const dist = await buildTheme(directory, {
            'theme.css': '.aurora-frame { display: grid; }',
            'theme.jsx': [
                "import { defineTheme, skin } from '@pterodactyl/sdk/theme';",
                'const Shell = ({ Default }) => <main><Default /></main>;',
                "export default defineTheme({ ui: { Button: skin('Button', {}) }, views: { 'layout.app': Shell, page: Shell, 'server.power': () => null } });",
            ].join('\n'),
        });

        expect(dist('client.js')).toContain('from"@pterodactyl/sdk/theme"');
        expect(dist('client.js')).not.toContain('document.head.append');
        expect(dist('theme.css')).toContain('.aurora-frame');
        const info = JSON.parse(dist('theme.json'));

        // The core class manifest is generated with the panel build, so a bare checkout has none.
        expect(info.core === null || /^[a-f0-9]{16}$/.test(info.core)).toBe(true);
        expect(info).toEqual({
            core: info.core,
            sdk: JSON.parse(readFileSync(new URL('./package.json', import.meta.url), 'utf8')).version,
            views: ['layout.app', 'page', 'server.power'],
        });
    } finally {
        rmSync(directory, { recursive: true, force: true });
    }
});

it('leaves out views it cannot read without running the theme, and builds a stylesheet-only theme', async () => {
    const directory = mkdtempSync(resolve(tmpdir(), 'theme-build-'));
    try {
        const computed = await buildTheme(directory, {
            'theme.css': '.a { color: red; }',
            'theme.jsx':
                "import { defineTheme } from '@pterodactyl/sdk/theme'; const views = {}; export default defineTheme({ views });",
        });
        expect(JSON.parse(computed('theme.json'))).not.toHaveProperty('views');

        rmSync(resolve(directory, 'dist'), { recursive: true, force: true });
        const stylesheet = await buildTheme(directory, { 'theme.css': '.b { color: blue; }' }, false);
        expect(stylesheet('theme.css')).toContain('.b');
        expect(() => stylesheet('client.js')).toThrow();
    } finally {
        rmSync(directory, { recursive: true, force: true });
    }
});

it('refuses theme JavaScript that imports styles the panel would never load', async () => {
    const directory = mkdtempSync(resolve(tmpdir(), 'theme-build-'));
    try {
        await expect(
            buildTheme(directory, {
                'theme.css': '.a { color: red; }',
                'extra.css': '.b { color: blue; }',
                'theme.jsx':
                    "import './extra.css'; import { defineTheme } from '@pterodactyl/sdk/theme'; export default defineTheme({});",
            })
        ).rejects.toThrow('Theme styles belong in the theme stylesheet');
    } finally {
        rmSync(directory, { recursive: true, force: true });
    }
});

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
        writeFileSync(
            resolve(directory, 'one.js'),
            "import { shared } from './shared.js'; export default () => shared() + 'one';"
        );
        writeFileSync(
            resolve(directory, 'two.js'),
            "import { shared } from './shared.js'; export default () => shared() + 'two';"
        );
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
