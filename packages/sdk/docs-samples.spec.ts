import { cpSync, mkdtempSync, readFileSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { resolve } from 'node:path';
import postcss from 'postcss';
import tailwind from '@tailwindcss/postcss';
import { build } from 'vite';
import { expect, it } from 'vitest';
import { defineThemeConfig } from './vite.mjs';

// The docs' sample themes: their types are checked by `npm run sdk:check` and their tests run with the suite.
const samples = resolve(__dirname, 'docs-samples');
const aurora = resolve(samples, 'aurora');

async function compile(path: string): Promise<string> {
    const from = resolve(samples, path);
    const { css } = await postcss([tailwind()]).process(readFileSync(from, 'utf8'), { from });

    return css;
}

it('compiles the sample theme stylesheet', async () => {
    const css = await compile('aurora/src/theme.css');

    expect(css).toContain("font-family: 'Aurora Sans'");
    expect(css).toMatch(/\.glass \{/);
    expect(css).toContain("[data-aurora-sidebar='right']");
}, 60_000);

it('compiles the stylesheets of the level samples', async () => {
    expect(await compile('level1-css/src/theme.css')).toContain("font-family: 'Sunrise Sans'");
    expect(await compile('level2-skins/src/theme.css')).toContain("[data-ui='button'][data-color='red']");
    expect(await compile('settings/src/theme.css')).toContain("[data-sunrise-density='compact']");
}, 120_000);

it('builds the sample theme entry and records the views it overrides', async () => {
    const outDir = mkdtempSync(resolve(tmpdir(), 'aurora-build-'));
    try {
        await build({
            ...defineThemeConfig({ entry: resolve(aurora, 'src/theme.tsx'), outDir }),
            configFile: false,
            logLevel: 'silent',
        });

        const info = JSON.parse(readFileSync(resolve(outDir, 'theme.json'), 'utf8'));

        expect(info.views).toEqual([
            'layout.auth',
            'page.auth.login',
            'layout.app',
            'layout.server',
            'server.power',
            'page.server.console',
            'server.terminal',
            'server.stats',
            'dashboard.serverList',
            'dashboard.serverCard',
        ]);
    } finally {
        rmSync(outDir, { recursive: true, force: true });
    }
}, 60_000);

it("builds the child theme sample over Aurora's stylesheet and classes", async () => {
    const extensions = mkdtempSync(resolve(tmpdir(), 'extensions-'));
    const child = resolve(samples, 'child-theme');
    try {
        // Aurora installed in a panel: its manifest beside its build.
        cpSync(resolve(aurora, 'extension.json'), resolve(extensions, 'aurora/extension.json'));
        await build({
            ...defineThemeConfig({
                entry: resolve(aurora, 'src/theme.tsx'),
                stylesheet: resolve(aurora, 'src/theme.css'),
                outDir: resolve(extensions, 'aurora/dist'),
            }),
            configFile: false,
            logLevel: 'silent',
        });
        const parent = (file: string) => readFileSync(resolve(extensions, 'aurora/dist', file), 'utf8');

        expect(parent('theme-source.css')).toContain('@utility glass');
        expect(parent('theme-source.css')).not.toContain('@pterodactyl/sdk');
        expect(parent('classes.txt').split('\n')).toContain('glass');

        await build({
            ...defineThemeConfig({
                entry: resolve(child, 'src/theme.tsx'),
                stylesheet: resolve(child, 'src/theme.css'),
                outDir: resolve(extensions, 'aurora-sunset/dist'),
                extensions,
            }),
            // The build keeps its copy of Aurora's stylesheet in Vite's cache directory.
            cacheDir: resolve(extensions, '.vite'),
            configFile: false,
            logLevel: 'silent',
        });
        const css = readFileSync(resolve(extensions, 'aurora-sunset/dist/theme.css'), 'utf8');

        // Aurora's utilities, variants and fonts, then the child's own rules after them.
        expect(css).toMatch(/data-aurora-sidebar=['"]?right/);
        expect(css).toMatch(/font-family:\s*['"]?Aurora Sans/);
        expect(css.search(/data-sunset-glow=['"]?true['"]?\]\s*\.glass/)).toBeGreaterThan(css.search(/\.glass\s*\{/));
        expect(css.search(/\.glass\s*\{/)).toBeGreaterThan(-1);
        expect(JSON.parse(readFileSync(resolve(extensions, 'aurora-sunset/dist/theme.json'), 'utf8')).parents).toEqual({
            aurora: JSON.parse(parent('theme.json')).build,
        });
    } finally {
        rmSync(extensions, { recursive: true, force: true });
    }
}, 120_000);
