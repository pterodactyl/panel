/**
 * Vite build preset for Pterodactyl extensions.
 *
 * Produces a single ESM file (dist/client.js) with every shared-runtime module left
 * as a bare import - the panel's import map resolves them at load time, so React,
 * TanStack Query, and the SDK are the panel's own instances and the extension bundle
 * stays tiny. Usage in an extension's vite.config.mjs:
 *
 *   import { defineExtensionConfig } from '@pterodactyl/sdk/vite';
 *   export default defineExtensionConfig({ entry: 'src/client/index.tsx' });
 */

/** Modules provided by the panel's import map - never bundled into extensions. */
export const SHARED_RUNTIME_MODULES = [
    'react',
    'react/jsx-runtime',
    'react-dom',
    'react-dom/client',
    '@tanstack/react-query',
    '@pterodactyl/sdk',
    '@pterodactyl/sdk/api',
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

export function defineExtensionConfig({ entry, outDir = 'dist', plugins = [] } = {}) {
    if (!entry) {
        throw new Error('defineExtensionConfig requires an { entry } path, e.g. "src/client/index.tsx".');
    }

    return {
        plugins: [...plugins, extensionStyles()],
        publicDir: false,
        build: {
            outDir,
            emptyOutDir: true,
            // Installs record the file hash; a stable name keeps manifests simple.
            sourcemap: true,
            target: 'es2022',
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
