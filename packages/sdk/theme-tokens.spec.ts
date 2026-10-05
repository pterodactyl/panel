import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { describe, expect, it } from 'vitest';

const read = (path: string) => readFileSync(fileURLToPath(new URL(path, import.meta.url)), 'utf8');

const panelCss = read('../../resources/scripts/assets/tailwind.css');
const sdkCss = read('./theme-tokens.css');
const extensionCss = read('./theme.css');
const terminal = read('../../resources/scripts/components/server/console/useTerminal.ts');

const unique = (values: Iterable<string>) => [...new Set(values)].sort();
const declared = (css: string) => unique([...css.matchAll(/^\s*(--[\w-]+)\s*:/gm)].map((match) => match[1]));
const referenced = (source: string) => unique([...source.matchAll(/var\((--[\w-]+)/g)].map((match) => match[1]));

// Tokens a theme may define but the panel leaves unset.
const optional = ['--selection', '--selection-foreground'];

// Owned by Tailwind's own theme rather than declared in tailwind.css.
const tailwindOwned = ['--spacing', '--font-sans', '--font-header', '--font-mono'];

const panelTokens = declared(panelCss);

describe('theme token contract', () => {
    it('defines every token the SDK theme bridge points at', () => {
        const bridged = referenced(sdkCss).filter((token) => !panelTokens.includes(token));

        expect(bridged).toEqual([]);
    });

    it('re-points only the Tailwind-owned tokens at the panel for prefixed extension builds', () => {
        expect(extensionCss).toContain("@import './theme-tokens.css';");
        expect(referenced(extensionCss)).toEqual([...tailwindOwned].sort());
        for (const token of tailwindOwned) {
            expect(extensionCss).toContain(`    ${token}: var(${token});`);
        }
    });

    it('uses no token that is never defined', () => {
        const known = [...panelTokens, ...optional, ...tailwindOwned, ...declared(sdkCss)];

        expect(referenced(panelCss).filter((token) => !known.includes(token))).toEqual([]);
    });

    it('hands the whole terminal palette to the console', () => {
        const palette = panelTokens.filter((token) => token.startsWith('--terminal-'));
        const consumed = unique([...terminal.matchAll(/'(--terminal-[\w-]+)'/g)].map((match) => match[1]));

        expect(consumed).toEqual(palette);
        expect(palette).toHaveLength(21);
    });

    it('bridges the layout tokens to the container utilities the layout uses', () => {
        expect(sdkCss).toContain('--container-panel: var(--layout-content-width);');
        expect(sdkCss).toContain('--container-auth: var(--layout-auth-width);');
        expect(sdkCss).toContain('--container-sidebar: var(--layout-sidebar-width);');
    });

    it('keeps the stock theme-color in step with the Blade shell', () => {
        const shell = read('../../resources/views/templates/wrapper.blade.php');
        const color = /--theme-color:\s*([^;]+);/.exec(panelCss)?.[1];

        expect(color).toBeDefined();
        expect(shell).toContain(`<meta name="theme-color" content="${color}" data-theme-token>`);
    });

    it('only styles text selection once a theme defines the token', () => {
        expect(panelCss).toMatch(/@container style\(--selection\) \{\s*::selection \{/);
        expect(panelTokens).not.toContain('--selection');
    });
});
