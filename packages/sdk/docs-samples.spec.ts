import { mkdtempSync, readFileSync, rmSync, statSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { resolve } from 'node:path';
import postcss from 'postcss';
import tailwind from '@tailwindcss/postcss';
import { build } from 'vite';
import { expect, it } from 'vitest';
import { defineThemeConfig } from './vite.mjs';

// The README's sample theme: its types are checked by `npm run sdk:check` and its tests run with the suite.
const aurora = resolve(__dirname, 'docs-samples/aurora');

it('compiles the sample theme stylesheet within the stylesheet budget', async () => {
    const from = resolve(aurora, 'src/theme.css');
    const { css } = await postcss([tailwind()]).process(readFileSync(from, 'utf8'), { from });

    expect(css).toContain("font-family: 'Aurora Sans'");
    expect(css).toMatch(/\.glass \{/);
    expect(css).toContain("[data-aurora-sidebar='right']");
    expect(Buffer.byteLength(css)).toBeLessThan(250 * 1024);
}, 60_000);

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
