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
import { existsSync, readFileSync } from 'node:fs';
import { parseAst } from 'vite';

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

/**
 * The view names a `defineTheme({ views: { ... } })` call lists, when they are plain keys;
 * null when the call is there but its views cannot be read without running it.
 */
function definedViews(program) {
    const found = [];
    const key = (property) =>
        property.type === 'Property' && !property.computed ? String(property.key.name ?? property.key.value) : null;
    const read = (theme) => {
        if (theme?.type !== 'ObjectExpression' || theme.properties.some((property) => key(property) === null)) {
            return null;
        }
        const views = theme.properties.find((property) => key(property) === 'views');
        if (views === undefined) return [];
        const keys = views.value.type === 'ObjectExpression' ? views.value.properties.map(key) : [null];

        return keys.includes(null) ? null : keys;
    };
    const visit = (node) => {
        if (Array.isArray(node)) return node.forEach(visit);
        if (!node || typeof node.type !== 'string') return;
        const callee = node.type === 'CallExpression' ? node.callee : null;
        if ((callee?.type === 'Identifier' ? callee.name : callee?.property?.name) === 'defineTheme') {
            found.push(read(node.arguments[0]));
        }
        for (const value of Object.values(node)) if (value && typeof value === 'object') visit(value);
    };
    visit(program);
    return found;
}

/** Writes dist/theme.json, which the panel reads to decide whether the theme stylesheet can replace its own. */
const themeBuildInfo = () => {
    let views = [];
    return {
        name: 'pterodactyl-theme-build-info',
        enforce: 'post',
        buildStart() {
            views = [];
        },
        transform(code, id) {
            if (!id.includes('/node_modules/') && /\bdefineTheme\b/.test(code))
                views.push(...definedViews(parseAst(code)));
            return null;
        },
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
            const manifest = new URL('./core-classes.txt', import.meta.url);
            const info = {
                core: existsSync(manifest)
                    ? createHash('sha256').update(readFileSync(manifest)).digest('hex').slice(0, 16)
                    : null,
                sdk: JSON.parse(readFileSync(new URL('./package.json', import.meta.url), 'utf8')).version,
                ...(views.length === 1 && views[0] !== null ? { views: views[0] } : {}),
            };
            this.emitFile({ type: 'asset', fileName: 'theme.json', source: `${JSON.stringify(info, null, 4)}\n` });
        },
    };
};

/**
 * Builds a theme: `entry` (optional) to dist/client.js with the shared modules external and
 * no stylesheet loader, `stylesheet` to dist/theme.css with Tailwind, and dist/theme.json.
 * `sourcemap` is off: a map embeds the theme's sources, so turn it on only for builds you keep.
 */
export function defineThemeConfig({ entry, stylesheet, outDir = 'dist', plugins = [], sourcemap = false } = {}) {
    if (!stylesheet && !entry) {
        throw new Error(
            'defineThemeConfig requires a { stylesheet } path such as "src/theme.css", an { entry } such as "src/theme.tsx", or both.'
        );
    }

    return {
        plugins: [...plugins, themeBuildInfo()],
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
                external: SHARED_RUNTIME_MODULES,
                preserveEntrySignatures: 'strict',
                output: {
                    format: 'es',
                    entryFileNames: '[name].js',
                    chunkFileNames: 'chunk.[name].[hash].js',
                    assetFileNames: ({ names }) =>
                        names.includes('theme.css') ? 'theme.css' : '[name].[hash][extname]',
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
