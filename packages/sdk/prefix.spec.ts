import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { __unstable__loadDesignSystem as loadDesignSystem } from 'tailwindcss';
import { beforeAll, describe, expect, it } from 'vitest';

const path = (file: string) => fileURLToPath(new URL(file, import.meta.url));
const require = createRequire(import.meta.url);
const schema = JSON.parse(readFileSync(path('./manifest.schema.json'), 'utf8')).properties.ui.properties.prefix;
const pattern = new RegExp(schema.pattern);
const reserved = new Set<string>(schema.not.enum);
/** The names that could be declared as `ui.prefix` at all, so the only ones worth reserving. */
const declarable = (names: Iterable<string>) => [...new Set(names)].filter((name) => pattern.test(name)).sort();

const panelStylesheet = path('../../resources/scripts/assets/tailwind.css');
const panelStylesheets = [panelStylesheet, path('./theme-tokens.css'), path('../../resources/scripts/router/view-transitions.css')];

const locate = (id: string, base: string) => (id.startsWith('.') ? resolve(base, id) : require.resolve(id));

/** The panel's own Tailwind build, with its plugins and custom variants. */
const loadPanelDesignSystem = () =>
    loadDesignSystem(readFileSync(panelStylesheet, 'utf8'), {
        base: dirname(panelStylesheet),
        loadStylesheet: async (id, base) => {
            const file = locate(id === 'tailwindcss' ? 'tailwindcss/index.css' : id, base);

            return { path: file, base: dirname(file), content: readFileSync(file, 'utf8') };
        },
        loadModule: async (id, base) => {
            const file = locate(id, base);
            const module = await import(file);

            return { path: file, base: dirname(file), module: module.default ?? module };
        },
    });

describe('reserved extension Tailwind prefixes', () => {
    let design: Awaited<ReturnType<typeof loadPanelDesignSystem>>;

    beforeAll(async () => {
        design = await loadPanelDesignSystem();
    });

    it('accepts what Tailwind accepts as a prefix: lowercase letters only', async () => {
        const build = (prefix: string) =>
            loadDesignSystem(`@theme prefix(${prefix}) { --color-brand: red; }`, { base: dirname(panelStylesheet) });

        expect(pattern.source).toBe('^[a-z]{2,12}$');
        await expect(build('hw')).resolves.toBeDefined();
        for (const invalid of ['h-w', 'h2', 'HW', 'h_w']) {
            await expect(build(invalid), invalid).rejects.toThrow();
        }
    });

    it('reserves every variant of the panel build, whose classes a prefix would otherwise read as', () => {
        const variants = declarable(design.getVariants().map((variant) => variant.name));

        expect(variants).toContain('sm');
        expect(variants).toContain('dark');
        expect(variants).toContain('group');
        expect(variants.filter((name) => !reserved.has(name))).toEqual([]);
    });

    it('reserves every theme namespace, so a prefixed theme variable is never an unprefixed one', () => {
        const namespaces = declarable([...design.theme.entries()].map(([name]) => name.slice(2).split('-')[0]!));

        expect(namespaces).toContain('color');
        expect(namespaces).toContain('text');
        expect(namespaces.filter((name) => !reserved.has(name))).toEqual([]);
    });

    it('reserves the first segment of every custom property the panel declares', () => {
        const properties = declarable(
            panelStylesheets.flatMap((file) =>
                [...readFileSync(file, 'utf8').matchAll(/(?<![\w-])--([a-z]+)[-:]/g)].map((match) => match[1]!)
            )
        );

        expect(properties).toContain('terminal');
        expect(properties).toContain('layout');
        expect(properties.filter((name) => !reserved.has(name))).toEqual([]);
        // Tailwind's own runtime properties (`--tw-translate-x`) are never prefixed.
        expect(reserved.has('tw')).toBe(true);
    });
});
