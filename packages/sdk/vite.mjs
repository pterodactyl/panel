/**
 * Vite build presets for Pterodactyl extensions and themes.
 *
 * Produces a single ESM file (dist/client.js) with every shared-runtime module left
 * as a bare import - the panel's import map resolves them at load time, so React,
 * TanStack Query, and the SDK are the panel's own instances and the extension bundle
 * stays tiny. Usage in an extension's vite.config.mjs:
 *
 *   import { defineExtensionConfig } from '@pterodactyl/sdk/vite';
 *   export default defineExtensionConfig({ entry: 'src/client/index.tsx' });
 *
 * and in a theme's:
 *
 *   import { defineThemeConfig } from '@pterodactyl/sdk/vite';
 *   export default defineThemeConfig({ entry: 'src/theme.tsx', stylesheet: 'src/theme.css' });
 */
import { createHash } from 'node:crypto';
import { existsSync, mkdirSync, readFileSync, realpathSync, writeFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { dirname, isAbsolute, parse, relative, resolve, sep } from 'node:path';
import { fileURLToPath } from 'node:url';
import { packageRoot, themeParents } from './theme-chain.mjs';

/** Modules provided by the panel's import map - never bundled into extensions. */
export const SHARED_RUNTIME_MODULES = [
    'react',
    'react/jsx-runtime',
    'react-dom',
    'react-dom/client',
    '@tanstack/react-query',
    '@pterodactyl/sdk',
    '@pterodactyl/sdk/api',
    '@pterodactyl/sdk/theme',
];

/** Entry CSS must load before setup; native ESM imports do not load Vite's extracted CSS. */
const extensionStyles = () => ({
    name: 'pterodactyl-extension-styles',
    enforce: 'post',
    generateBundle(_options, bundle) {
        for (const chunk of Object.values(bundle)) {
            if (chunk.type !== 'chunk' || !chunk.isEntry) continue;
            const styles = new Set();
            const visited = new Set();
            const collect = (file) => {
                if (visited.has(file)) return;
                visited.add(file);
                const dependency = bundle[file];
                if (dependency?.type !== 'chunk') return;
                for (const imported of dependency.imports) collect(imported);
                for (const style of dependency.viteMetadata?.importedCss ?? []) styles.add(style);
            };
            collect(chunk.fileName);
            if (!styles.size) continue;
            const prelude = `await Promise.all(${JSON.stringify([...styles])}.map(file => new Promise((resolve, reject) => {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = new URL(file, import.meta.url).href;
    link.onload = () => resolve();
    link.onerror = () => { link.remove(); reject(new Error('Unable to load extension stylesheet: ' + file)); };
    document.head.append(link);
})));\n`;
            chunk.code = prelude + chunk.code;
            const sourceMap = bundle[`${chunk.fileName}.map`];
            if (sourceMap?.type === 'asset') {
                const map = JSON.parse(String(sourceMap.source));
                map.mappings = ';'.repeat(prelude.split('\n').length - 1) + map.mappings;
                sourceMap.source = JSON.stringify(map);
            }
        }
    },
});

const sdkDirectory = fileURLToPath(new URL('.', import.meta.url));
const quote = (value) => JSON.stringify(value);

/** A path a stylesheet in `from` can reference `to` by, with forward slashes. */
function cssPath(from, to) {
    const path = relative(from, to).split(sep).join('/');

    return path.startsWith('.') ? path : `./${path}`;
}

/** A module of the theme package's dependencies (its PostCSS, Tailwind's scanner), else of the SDK's. */
function dependency(root, id, via) {
    try {
        const require = createRequire(resolve(root, 'package.json'));

        return via ? createRequire(require.resolve(via))(id) : require(id);
    } catch {
        return createRequire(import.meta.url)(id);
    }
}

/** Calls `replace` with each relative url() of a declaration value: a file beside the stylesheet. */
const relativeUrls = (value, replace) =>
    value.replace(/url\(\s*(['"]?)([^'")]*)\1\s*\)/g, (match, _quote, url) => {
        const path = url.split(/[?#]/)[0];
        if (!path || /^(?:[a-z][a-z\d+.-]*:|\/|#)/i.test(url)) return match;
        const replaced = replace(path, url.slice(path.length));

        return replaced === null ? match : `url(${quote(replaced)})`;
    });

/**
 * The theme's own stylesheet for a child theme's build: local @imports inlined, the SDK's left out
 * (the child imports it itself), url()s pointing at copies beside dist/theme-source.css, and the
 * @source paths moved out to `sources`, which classes.txt scans instead.
 */
function ownStylesheet(file, { postcss, asset, sources }, seen = new Set()) {
    const base = dirname(file);
    const root = postcss.parse(seen.has(file) ? '' : readFileSync(file, 'utf8'), { from: file });
    seen.add(file);

    root.walkDecls((declaration) => {
        declaration.value = relativeUrls(declaration.value, (path, suffix) => {
            const target = resolve(base, decodeURI(path));

            return existsSync(target) ? `./${asset(target)}${suffix}` : null;
        });
    });
    root.walkAtRules('source', (rule) => {
        const negated = /^not\s/.test(rule.params);
        const pattern = rule.params.replace(/^not\s+/, '').trim();
        if (pattern.startsWith('inline(')) return;
        sources.push({ base, pattern: pattern.replace(/^(['"])(.*)\1$/, '$2'), negated });
        rule.remove();
    });
    root.each((node) => {
        const [, , specifier, conditions = ''] =
            node.type === 'atrule' && node.name === 'import'
                ? (node.params.match(/^\s*(?:url\(\s*)?(['"]?)([^'")\s]+)\1\s*\)?\s*(.*)$/s) ?? [])
                : [];
        if (!specifier || /^(?:[a-z]+:)?\/\//i.test(specifier)) return;
        if (specifier.startsWith('@pterodactyl/sdk/')) return void node.remove();

        let target;
        try {
            target =
                specifier.startsWith('.') || isAbsolute(specifier)
                    ? resolve(base, specifier)
                    : createRequire(file).resolve(specifier);
        } catch {
            throw new Error(`${file} imports "${specifier}", which the theme build cannot resolve to a stylesheet.`);
        }
        if (target.startsWith(sdkDirectory)) return void node.remove();

        let inlined = ownStylesheet(target, { postcss, asset, sources }, seen).nodes;
        const [, layer, rest] = conditions.match(/^(?:layer(?:\(([^)]*)\))?\s*)?(.*)$/s);
        const [, supports, media] = rest.match(/^(?:supports\(((?:[^()]|\([^()]*\))*)\)\s*)?(.*)$/s);
        for (const [name, params] of [
            ['media', media.trim()],
            ['supports', supports && `(${supports})`],
            ['layer', conditions.startsWith('layer') ? (layer ?? '') : undefined],
        ]) {
            if (params !== undefined && (params !== '' || name === 'layer')) {
                inlined = [postcss.atRule({ name, params, nodes: inlined })];
            }
        }
        node.replaceWith(inlined);
    });

    return root;
}

/**
 * The theme build: what a child theme builds on, dist/theme-source.css and dist/classes.txt, and
 * dist/theme.json, which lists the parents the stylesheet was built over. When the theme extends
 * others, their stylesheets and classes come into its own after the SDK's.
 */
function themeBuild({ entry, stylesheet, extensions }, inherited) {
    let config;
    let root;
    let sheet;
    let parents = [];

    return [
        {
            name: 'pterodactyl-theme-parents',
            enforce: 'pre',
            configResolved(resolved) {
                config = resolved;
                // Vite names the stylesheet module by its real path.
                sheet = stylesheet && resolve(config.root, stylesheet);
                sheet = sheet && existsSync(sheet) ? realpathSync(sheet) : sheet;
                const from = dirname(sheet ?? resolve(config.root, entry));
                root = packageRoot(from) ?? from;
            },
            buildStart() {
                parents = sheet
                    ? themeParents({ root, extensions })
                          .map((parent) => inherit(this, parent))
                          .filter(Boolean)
                    : [];
            },
            transform(code, id) {
                if (id.split('?')[0] !== sheet || !parents.length) return null;

                const postcss = dependency(root, 'postcss');
                const css = postcss.parse(code, { from: sheet });
                const imports = parents.map(
                    (parent) =>
                        `@import ${quote(cssPath(dirname(sheet), parent.stylesheet))};\n` +
                        `@source ${quote(cssPath(dirname(sheet), parent.classes))};`
                );
                const inherited = postcss.parse(`/* The themes this one extends, oldest first. */\n${imports.join('\n')}`).nodes;
                const sdk = css.nodes.find(
                    (node) => node.type === 'atrule' && node.name === 'import' && /^\s*(?:url\(\s*)?['"]?@pterodactyl\/sdk\//.test(node.params)
                );
                sdk ? sdk.after(inherited) : css.prepend(inherited);

                return { code: css.toString(), map: null };
            },
        },
        {
            name: 'pterodactyl-theme-build-info',
            enforce: 'post',
            generateBundle(_options, bundle) {
                for (const chunk of Object.values(bundle)) {
                    if (
                        chunk.type === 'chunk' &&
                        !chunk.facadeModuleId?.endsWith('.css') &&
                        chunk.viteMetadata?.importedCss.size
                    ) {
                        this.error(
                            'Theme styles belong in the theme stylesheet: dist/client.js does not load CSS its modules import.'
                        );
                    }
                }

                // A file the stylesheet uses, copied beside it unless the build already emitted the same bytes.
                const asset = (file) => {
                    const source = readFileSync(file);
                    const emitted = Object.values(bundle).find(
                        (output) => output.type === 'asset' && Buffer.from(output.source).equals(source)
                    );
                    if (emitted) return emitted.fileName;
                    const { name, ext } = parse(file);
                    const fileName = `${name}.${createHash('sha256').update(source).digest('base64url').slice(0, 8)}${ext}`;
                    if (!bundle[fileName]) this.emitFile({ type: 'asset', fileName, source });

                    return fileName;
                };
                const sources = [];
                const own = sheet
                    ? ownStylesheet(sheet, { postcss: dependency(root, 'postcss'), asset, sources }).toString().trim()
                    : '';
                const source = `/* This theme's own stylesheet, without the panel's: the build of a theme that extends it imports it. */\n${own}\n`;
                const { Scanner } = dependency(root, '@tailwindcss/oxide', '@tailwindcss/postcss');
                const scanner = new Scanner({
                    sources: [
                        { base: root, pattern: '**/*', negated: false },
                        ...sources,
                        { base: resolve(config.root, config.build.outDir), pattern: '**/*', negated: true },
                    ],
                });
                const classes = `${[...new Set(scanner.scan())].sort().join('\n')}\n`;
                const info = { parents: parents.map(({ id }) => id) };
                this.emitFile({ type: 'asset', fileName: 'theme-source.css', source });
                this.emitFile({ type: 'asset', fileName: 'classes.txt', source: classes });
                this.emitFile({ type: 'asset', fileName: 'theme.json', source: `${JSON.stringify(info, null, 4)}\n` });
            },
        },
    ];

    /**
     * A parent's build, its stylesheet copied into Vite's cache with absolute url()s: Tailwind inlines
     * imports without rebasing their urls, and rebuilds when an imported file changes. Null for a
     * parent with neither styles nor JavaScript, which gives the child nothing to build over.
     */
    function inherit(context, parent) {
        const dist = (file) => resolve(parent.directory, 'dist', file);
        if (!parent.manifest.ui && !parent.manifest.theme?.stylesheet) return null;

        if (!existsSync(dist('theme-source.css')) || !existsSync(dist('classes.txt'))) {
            throw new Error(
                `Theme "${parent.id}" in ${parent.directory} has no build to extend: build it with @pterodactyl/sdk, which writes dist/theme-source.css and dist/classes.txt.`
            );
        }

        const postcss = dependency(root, 'postcss');
        const css = postcss.parse(readFileSync(dist('theme-source.css'), 'utf8'), { from: dist('theme-source.css') });
        css.walkDecls((declaration) => {
            declaration.value = relativeUrls(declaration.value, (path, suffix) => dist(path).split(sep).join('/') + suffix);
        });
        const copy = resolve(config.cacheDir, 'pterodactyl-theme', `${parent.id}.css`);
        const text = css.toString();
        if (!existsSync(copy) || readFileSync(copy, 'utf8') !== text) {
            mkdirSync(dirname(copy), { recursive: true });
            writeFileSync(copy, text);
        }
        for (const file of ['theme-source.css', 'classes.txt']) context.addWatchFile(dist(file));
        inherited.add(`${relative(config.root, dist('')).split(sep).join('/')}/`);

        return { ...parent, stylesheet: copy, classes: dist('classes.txt') };
    }
}

/**
 * Builds a theme: `entry` (optional) to dist/client.js with the shared modules external and
 * no stylesheet loader, `stylesheet` to dist/theme.css with Tailwind, dist/theme-source.css and
 * dist/classes.txt for themes that extend this one, and dist/theme.json, which lists its parents.
 * When extension.json extends another theme, its stylesheet and classes are built in after the
 * SDK's. Parents are found in `extensions` (the panel's extensions directory), then node_modules.
 * `sourcemap` is off: a map embeds the theme's sources, so turn it on only for builds you keep.
 */
export function defineThemeConfig({ entry, stylesheet, extensions, outDir = 'dist', plugins = [], sourcemap = false } = {}) {
    if (!stylesheet && !entry) {
        throw new Error(
            'defineThemeConfig requires a { stylesheet } path such as "src/theme.css", an { entry } such as "src/theme.tsx", or both.'
        );
    }

    // The dist directories of the themes this one extends, relative to the root: their files are named by hash already.
    const inherited = new Set();

    return {
        plugins: [...plugins, ...themeBuild({ entry, stylesheet, extensions }, inherited)],
        // The import map shares react/jsx-runtime, never the development runtime.
        oxc: { jsx: { runtime: 'automatic', development: false } },
        // The panel serves a build from its own directory, so fonts and images resolve beside the file using them.
        base: './',
        publicDir: false,
        build: {
            outDir,
            emptyOutDir: true,
            sourcemap,
            target: 'es2022',
            modulePreload: false,
            rollupOptions: {
                input: { ...(entry ? { client: entry } : {}), ...(stylesheet ? { theme: stylesheet } : {}) },
                // The panel serves /assets/ itself, such as the font panel.css links, so the build leaves those URLs alone.
                external: [...SHARED_RUNTIME_MODULES, /^\/assets\//],
                preserveEntrySignatures: 'strict',
                output: {
                    format: 'es',
                    entryFileNames: '[name].js',
                    chunkFileNames: 'chunk.[name].[hash].js',
                    assetFileNames: ({ names, originalFileNames }) => {
                        if (names.includes('theme.css')) return 'theme.css';

                        return originalFileNames.some((file) => [...inherited].some((dist) => file.startsWith(dist)))
                            ? '[name][extname]'
                            : '[name].[hash][extname]';
                    },
                },
            },
        },
    };
}

export function defineExtensionConfig({ entry, outDir = 'dist', plugins = [] } = {}) {
    if (!entry) {
        throw new Error('defineExtensionConfig requires an { entry } path, e.g. "src/client/index.tsx".');
    }

    return {
        plugins: [...plugins, extensionStyles()],
        // The panel serves a build from its own directory, so fonts and images resolve beside the file using them.
        base: './',
        publicDir: false,
        build: {
            outDir,
            emptyOutDir: true,
            // Installs record the file hash; a stable name keeps manifests simple.
            sourcemap: true,
            target: 'es2022',
            // Vite's preload helper resolves chunk names against the document, so a shared lazy chunk would be requested
            // from the site root. The chunks import each other with relative URLs, which resolve against the bundle.
            modulePreload: false,
            rollupOptions: {
                input: entry,
                external: SHARED_RUNTIME_MODULES,
                preserveEntrySignatures: 'strict',
                output: {
                    format: 'es',
                    entryFileNames: 'client.js',
                    chunkFileNames: 'chunk.[name].[hash].js',
                    assetFileNames: '[name].[hash][extname]',
                },
            },
        },
    };
}
