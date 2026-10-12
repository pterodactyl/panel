import { mkdtempSync, readFileSync, rmSync, statSync } from 'node:fs';
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

    expect(Buffer.byteLength(css)).toBeLessThan(250 * 1024);

    return css;
}

it('compiles the sample theme stylesheet within the stylesheet budget', async () => {
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
            'auth.loginForm',
            'layout.app',
            'layout.server',
            'server.power',
            'page.server.console',
            'server.terminal',
            'server.stats',
            'dashboard.serverList',
            'dashboard.serverCard',
        ]);
        expect(statSync(resolve(outDir, 'client.js')).size).toBeLessThan(200 * 1024);
    } finally {
        rmSync(outDir, { recursive: true, force: true });
    }
}, 60_000);
