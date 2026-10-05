import tailwind from '@tailwindcss/postcss';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import postcss from 'postcss';
import { beforeAll, describe, expect, it } from 'vitest';

const path = (file: string) => fileURLToPath(new URL(file, import.meta.url));
const template = readFileSync(path('../../app/Services/Extensions/Scaffolding/stubs/client-styles.stub'), 'utf8');
/** The scaffolded stylesheet of an extension that declares the given `ui.prefix`. */
const stylesheet = (prefix: string) => template.replaceAll('{{prefix}}', prefix);
const stub = stylesheet('hw');

/** Compile the scaffolded extension stylesheet for the given class names with the real Tailwind. */
const build = async (stylesheet: string, classes: string[]) => {
    const source = stylesheet
        .replace("'@pterodactyl/sdk/theme.css'", "'./theme.css'")
        .replace("@source './**/*.{ts,tsx}';", `@source inline('${classes.join(' ')}');`)
        .replaceAll('layer(utilities)', 'layer(utilities) source(none)');

    return (await postcss([tailwind({ optimize: false })]).process(source, { from: path('./styles.css') })).css;
};

const layer = (css: string, name: string) => {
    const start = css.indexOf(`@layer ${name} {`);

    return start < 0 ? '' : css.slice(start, css.indexOf('\n}', start));
};

describe('extension stylesheet', () => {
    let css = '';

    beforeAll(async () => {
        css = await build(stub, [
            'hw:flex',
            'hw:lg:hidden',
            'hw:hover:bg-accent',
            'hw:p-4',
            'hw:-mt-2',
            'hw:mt-4!',
            'hw:w-[13px]',
            'hw:font-sans',
            'hw:font-header',
            'hw:font-mono',
            'hw:text-sm',
            'hw:bg-background',
            'hw:text-foreground',
            'hw:bg-terminal',
            'hw:w-sidebar',
            'hw:max-w-panel',
            'hw:rounded-lg',
            'hw:dark:bg-card',
            'flex',
            'lg:hidden',
            'lg:hw:hidden',
        ]);
    });

    it('builds Tailwind with the prefix of the extension', () => {
        expect(stub).toContain("@import 'tailwindcss/theme.css' layer(theme) prefix(hw);");
        expect(stub).not.toContain('preflight');
    });

    it('emits only prefixed utilities, with the prefix ahead of every variant', () => {
        const selectors = [...layer(css, 'utilities').matchAll(/^ {2}(\.\S+) \{$/gm)].map((match) => match[1]);

        expect(selectors).toContain('.hw\\:flex');
        expect(selectors).toContain('.hw\\:lg\\:hidden');
        expect(selectors).toContain('.hw\\:hover\\:bg-accent');
        expect(selectors).toContain('.hw\\:-mt-2');
        expect(selectors).toContain('.hw\\:mt-4\\!');
        expect(selectors).toContain('.hw\\:w-\\[13px\\]');
        expect(selectors.filter((selector) => !selector.startsWith('.hw\\:'))).toEqual([]);
        expect(selectors).not.toContain('.lg\\:hw\\:hidden');
    });

    it('declares no unprefixed theme variable', () => {
        const declared = [...layer(css, 'theme').matchAll(/^\s*(--[\w-]+):/gm)].map((match) => match[1]);

        expect(declared).toContain('--hw-text-sm');
        expect(declared.filter((name) => !name.startsWith('--hw-'))).toEqual([]);
    });

    it("follows the panel's density and fonts instead of Tailwind defaults", () => {
        expect(css).toContain('padding: calc(var(--spacing) * 4);');
        expect(css).toContain('margin-top: calc(var(--spacing) * -2);');
        expect(css).toContain('font-family: var(--font-sans);');
        expect(css).toContain('font-family: var(--font-header);');
        expect(css).toContain('font-family: var(--font-mono);');
        expect(css).not.toMatch(/--hw-(spacing|font-sans|font-header|font-mono)\b/);
    });

    it("resolves role, terminal and layout utilities to the panel's live tokens", () => {
        expect(css).toContain('background-color: var(--background);');
        expect(css).toContain('color: var(--foreground);');
        expect(css).toContain('background-color: var(--terminal-background);');
        expect(css).toContain('width: var(--layout-sidebar-width);');
        expect(css).toContain('max-width: var(--layout-content-width);');
        expect(css).toContain('border-radius: var(--radius);');
        expect(css).toMatch(/\.hw\\:dark\\:bg-card \{\s*&:is\(\.dark \*\) \{\s*background-color: var\(--card\);/);
    });

    it('shares no selector or theme variable with an extension built on another prefix', async () => {
        const names = (built: string) => [
            ...[...layer(built, 'utilities').matchAll(/^ {2}(\.\S+) \{$/gm)].map((match) => match[1]),
            ...[...layer(built, 'theme').matchAll(/^\s*(--[\w-]+):/gm)].map((match) => match[1]),
        ];
        const classes = ['grid', 'grid-cols-2', 'sm:grid-cols-3', 'text-sm'];
        const prefixed = (prefix: string, used: string[]) =>
            build(
                stylesheet(prefix),
                used.map((name) => `${prefix}:${name}`)
            );
        const first = names(await prefixed('aa', classes));
        const second = names(await prefixed('bb', classes.slice(0, 2).concat('text-sm')));

        expect(first).toContain('.aa\\:sm\\:grid-cols-3');
        expect(first).toContain('--aa-text-sm');
        expect(second).toContain('.bb\\:grid-cols-2');
        expect(second).toContain('--bb-text-sm');
        expect(first.filter((name) => second.includes(name))).toEqual([]);

        // On one shared prefix the later stylesheet's `grid-cols-2` overrode the earlier `sm:grid-cols-3`.
        const shared = names(await prefixed('aa', classes.slice(0, 2)));
        expect(first.filter((name) => shared.includes(name))).toContain('.aa\\:grid-cols-2');
    });

    it('would redeclare the panel theme without the prefix, which the doctor rejects', async () => {
        const unprefixed = await build(stub.replaceAll(' prefix(hw)', ''), ['flex', 'p-4']);

        expect(layer(unprefixed, 'utilities')).toContain('.flex {');
        expect(layer(unprefixed, 'theme')).toContain('--spacing: var(--spacing);');
    });
});
