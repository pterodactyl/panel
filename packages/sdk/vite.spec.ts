import { mkdirSync, mkdtempSync, readFileSync, rmSync, symlinkSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, resolve } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { spawnSync } from 'node:child_process';
import tailwind from '@tailwindcss/postcss';
import { build, createLogger } from 'vite';
import { expect, it } from 'vitest';
import { defineExtensionConfig, defineThemeConfig } from './vite.mjs';

const buildTheme = async (directory: string, files: Record<string, string>, entry = true, sourcemap?: boolean) => {
    for (const [name, contents] of Object.entries(files)) {
        writeFileSync(resolve(directory, name), contents);
    }

    await build({
        ...defineThemeConfig({
            entry: entry ? resolve(directory, 'theme.jsx') : undefined,
            stylesheet: resolve(directory, 'theme.css'),
            outDir: resolve(directory, 'dist'),
            sourcemap,
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
            build: info.build,
        });
        expect(info.build).toMatch(/^[a-f0-9]{16}$/);
    } finally {
        rmSync(directory, { recursive: true, force: true });
    }
});

/** A theme package in `directory`, with the SDK linked as a theme installs it, built with Tailwind. */
const themePackage = (directory: string) => {
    const write = (path: string, contents: string | Buffer) => {
        mkdirSync(dirname(resolve(directory, path)), { recursive: true });
        writeFileSync(resolve(directory, path), contents);
    };
    const build_ = (options: Parameters<typeof defineThemeConfig>[0]) =>
        build({
            ...defineThemeConfig({ ...options, outDir: resolve(directory, 'dist') }),
            css: { postcss: { plugins: [tailwind()] } },
            cacheDir: resolve(directory, 'node_modules/.vite'),
            configFile: false,
            logLevel: 'silent',
        });
    for (const [name, target] of [
        ['@pterodactyl/sdk', '.'],
        ['@tailwindcss/forms', '../../node_modules/@tailwindcss/forms'],
    ]) {
        mkdirSync(dirname(resolve(directory, 'node_modules', name)), { recursive: true });
        symlinkSync(fileURLToPath(new URL(target, import.meta.url)), resolve(directory, 'node_modules', name));
    }

    return { write, build: build_, dist: (file: string) => readFileSync(resolve(directory, 'dist', file), 'utf8') };
};

it('ships its own stylesheet and class candidates for the themes that extend it', async () => {
    const directory = mkdtempSync(resolve(tmpdir(), 'theme-build-'));
    try {
        const theme = themePackage(directory);
        theme.write('extension.json', JSON.stringify({ id: 'parent', type: 'theme' }));
        // Over Vite's inline limit, so the font is emitted as a file rather than a data URL.
        theme.write('src/fonts/Brand.woff2', Buffer.alloc(8192, 1));
        theme.write('src/glow.css', '@utility glow { box-shadow: 0 0 1rem var(--primary); }');
        theme.write('src/frame.css', '.frame { padding: 1rem; }');
        theme.write('src/Card.jsx', 'export const Card = () => <div className="glow sm:rounded-3xl" />;');
        theme.write(
            'src/theme.jsx',
            "import { defineTheme } from '@pterodactyl/sdk/theme'; import { Card } from './Card.jsx'; export default defineTheme({ views: { 'dashboard.serverCard': Card } });"
        );
        theme.write(
            'src/theme.css',
            [
                "@import '@pterodactyl/sdk/theme-entry.css';",
                "@import './glow.css';",
                "@import './frame.css' layer(components);",
                "@source inline('underline');",
                "@font-face { font-family: 'Brand'; src: url('./fonts/Brand.woff2') format('woff2'); }",
            ].join('\n')
        );
        await theme.build({ entry: resolve(directory, 'src/theme.jsx'), stylesheet: resolve(directory, 'src/theme.css') });
        const source = theme.dist('theme-source.css');

        expect(source).not.toContain('@pterodactyl/sdk');
        expect(source).toContain('@utility glow');
        expect(source).toMatch(/@layer components\s*\{\s*\.frame/);
        expect(source).toContain("@source inline('underline')");
        // The font is the file theme.css links, beside both in dist/.
        const font = source.match(/url\("\.\/(Brand\.[\w-]+\.woff2)"\)/)?.[1];
        expect(font).toBeDefined();
        expect(theme.dist('theme.css')).toContain(`url(./${font})`);
        expect(theme.dist('classes.txt').split('\n')).toEqual(expect.arrayContaining(['glow', 'sm:rounded-3xl']));
        expect(JSON.parse(theme.dist('theme.json'))).not.toHaveProperty('parents');
    } finally {
        rmSync(directory, { recursive: true, force: true });
    }
}, 60_000);

it("builds a child theme over its parent's stylesheet and classes, which it records", async () => {
    const directory = mkdtempSync(resolve(tmpdir(), 'theme-chain-'));
    try {
        const child = themePackage(resolve(directory, 'child'));
        const parent = (path: string, contents: string | Buffer) => child.write(`../extensions/parent/${path}`, contents);
        parent('extension.json', JSON.stringify({ id: 'parent', type: 'theme', theme: { stylesheet: 'dist/theme.css' } }));
        parent('dist/theme.json', JSON.stringify({ core: null, build: '0123456789abcdef' }));
        parent('dist/Brand.AbCd1234.woff2', Buffer.alloc(8192, 1));
        parent('dist/classes.txt', 'glow\nsm:rounded-3xl\n');
        parent(
            'dist/theme-source.css',
            "@font-face { font-family: 'Brand'; src: url('./Brand.AbCd1234.woff2'); }\n@utility glow { color: red; }\n.parent-rule { color: blue; }"
        );
        child.write('extension.json', JSON.stringify({ id: 'child', type: 'theme', theme: { extends: 'parent' } }));
        child.write('src/theme.css', "@import '@pterodactyl/sdk/theme-entry.css';\n.child-rule { color: green; }");
        const options = { stylesheet: resolve(directory, 'child/src/theme.css'), extensions: '../extensions' };
        await child.build(options);
        const css = child.dist('theme.css');

        expect(css).toMatch(/\.glow\s*\{/);
        expect(css).toMatch(/\.sm\\:rounded-3xl/);
        expect(css.indexOf('.child-rule')).toBeGreaterThan(css.indexOf('.parent-rule'));
        // The parent's font is copied from its build under the name its build hashed, not hashed again.
        expect(css).toContain('url(./Brand.AbCd1234.woff2)');
        expect(JSON.parse(child.dist('theme.json')).parents).toEqual({ parent: '0123456789abcdef' });

        rmSync(resolve(directory, 'extensions/parent/dist/theme-source.css'));
        await expect(child.build(options)).rejects.toThrow('Theme "parent" in');
        rmSync(resolve(directory, 'extensions/parent'), { recursive: true });
        await expect(child.build(options)).rejects.toThrow('Theme "child" extends "parent", which is not in');
    } finally {
        rmSync(directory, { recursive: true, force: true });
    }
}, 60_000);

it('ships no source map, which would embed the theme sources, unless asked to', async () => {
    const directory = mkdtempSync(resolve(tmpdir(), 'theme-build-'));
    const files = {
        'theme.css': '.a { color: red; }',
        'theme.jsx': "import { defineTheme } from '@pterodactyl/sdk/theme'; export default defineTheme({});",
    };
    try {
        const plain = await buildTheme(directory, files);

        expect(plain('client.js')).not.toContain('sourceMappingURL');
        expect(() => plain('client.js.map')).toThrow();

        const mapped = await buildTheme(directory, files, true, true);

        expect(JSON.parse(mapped('client.js.map')).sourcesContent.join('')).toContain('defineTheme');
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

it('ships fonts beside the theme stylesheet, linked relative to it', async () => {
    const directory = mkdtempSync(resolve(tmpdir(), 'theme-build-'));
    try {
        // Over Vite's inline limit, so the font is emitted as a file rather than a data URL.
        writeFileSync(resolve(directory, 'Brand.woff2'), Buffer.alloc(8192, 1));
        const dist = await buildTheme(
            directory,
            {
                'theme.css':
                    "@font-face { font-family: 'Brand'; src: url('./Brand.woff2') format('woff2'); }\n.a { font-family: Brand; }",
            },
            false
        );

        expect(dist('theme.css')).toMatch(/url\(\.\/Brand\.[\w-]+\.woff2\)/);
    } finally {
        rmSync(directory, { recursive: true, force: true });
    }
});

it("leaves the panel's own /assets/ URLs for the panel to serve, without a warning", async () => {
    const directory = mkdtempSync(resolve(tmpdir(), 'theme-build-'));
    const warnings: string[] = [];
    const logger = createLogger('warn');
    logger.warn = logger.warnOnce = (message) => void warnings.push(message);
    try {
        writeFileSync(
            resolve(directory, 'theme.css'),
            "@font-face { font-family: 'Panel'; src: url('/assets/fonts/panel.woff2') format('woff2'); }"
        );
        await build({
            ...defineThemeConfig({ stylesheet: resolve(directory, 'theme.css'), outDir: resolve(directory, 'dist') }),
            configFile: false,
            logLevel: 'warn',
            customLogger: logger,
        });

        expect(readFileSync(resolve(directory, 'dist/theme.css'), 'utf8')).toContain('url(/assets/fonts/panel.woff2)');
        expect(warnings).toEqual([]);
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
