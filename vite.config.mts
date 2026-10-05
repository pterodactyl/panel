import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react-swc';
import { fileURLToPath, URL } from 'node:url';
import { createHash } from 'node:crypto';
import { readFileSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';

// A single build hash exposed to the app the same way the old webpack EnvironmentPlugin did.
const buildHash = process.env.PTERODACTYL_BUILD_HASH ?? Date.now().toString(16);

type ViteManifestEntry = {
    file: string;
    integrity: string;
    css?: string[];
    cssIntegrity?: Record<string, string>;
};

function integrityFor(outDir: string, fileName: string) {
    const contents = readFileSync(join(outDir, fileName));

    return 'sha384-' + createHash('sha384').update(contents).digest('base64');
}

/** Add SRI data to Vite's native manifest without changing its shape or keys. */
function manifestIntegrity() {
    return {
        name: 'pterodactyl-manifest-integrity',
        apply: 'build' as const,
        enforce: 'post' as const,
        writeBundle(options: { dir?: string }) {
            const outDir = options.dir!;
            const manifestPath = join(outDir, 'manifest.json');
            const manifest = JSON.parse(readFileSync(manifestPath, 'utf8')) as Record<string, ViteManifestEntry>;

            for (const entry of Object.values(manifest)) {
                entry.integrity = integrityFor(outDir, entry.file);
                if (entry.css?.length) {
                    entry.cssIntegrity = Object.fromEntries(
                        entry.css.map((fileName) => [fileName, integrityFor(outDir, fileName)])
                    );
                }
            }

            writeFileSync(manifestPath, JSON.stringify(manifest, null, 2) + '\n');
        },
    };
}

export default defineConfig(({ mode }) => {
    const isProduction = mode === 'production';

    return {
        base: '/assets/',
        // Laravel already serves public/ directly; copying it into public/assets
        // would recurse because the build output itself lives under public/.
        publicDir: false,
        define: {
            // process.env.NODE_ENV is still consumed by bundled third-party libs (React et al.).
            'process.env.NODE_ENV': JSON.stringify(isProduction ? 'production' : 'development'),
            // App code reads the build hash via import.meta.env (see resources/scripts/vite-env.d.ts);
            // debug toggling uses Vite's built-in import.meta.env.DEV.
            'import.meta.env.WEBPACK_BUILD_HASH': JSON.stringify(buildHash),
        },
        resolve: {
            alias: {
                '@': fileURLToPath(new URL('./resources/scripts', import.meta.url)),
                '@feature': fileURLToPath(new URL('./resources/scripts/components/server/features', import.meta.url)),
            },
        },
        plugins: [
            // SWC-based React plugin (Fast Refresh + automatic JSX runtime) — replaces the
            // Babel-based @vitejs/plugin-react, removing Babel from the toolchain entirely.
            // No custom transforms are needed (twin.macro / styled-components are long gone).
            react(),
            manifestIntegrity(),
        ],
        build: {
            outDir: 'public/assets',
            manifest: 'manifest.json',
            // Don't wipe the directory — it holds tracked legacy webpack output + fonts/images.
            // Hashed filenames prevent collisions; stale files can be pruned separately.
            emptyOutDir: false,
            assetsDir: '',
            sourcemap: !isProduction,
            chunkSizeWarningLimit: 2000,
            rollupOptions: {
                input: {
                    main: fileURLToPath(new URL('./resources/scripts/index.tsx', import.meta.url)),
                    // Shared extension-runtime entries: each is emitted as a real module
                    // chunk whose exports ARE the package's exports, at a URL recorded in
                    // manifest.json. The import map maps bare specifiers ("react", ...)
                    // to these URLs, so runtime-loaded extension bundles share the exact
                    // module instances the panel itself uses — one React, one QueryClient.
                    // Each entry goes through a tiny wrapper module (see ext-runtime/*.ts):
                    // React et al. are CommonJS, and a direct package entry would emit a
                    // default-only facade — the wrappers force full named-export synthesis.
                    react: fileURLToPath(new URL('./resources/scripts/ext-runtime/react.ts', import.meta.url)),
                    'react-jsx-runtime': fileURLToPath(
                        new URL('./resources/scripts/ext-runtime/react-jsx-runtime.ts', import.meta.url)
                    ),
                    'react-dom': fileURLToPath(
                        new URL('./resources/scripts/ext-runtime/react-dom.ts', import.meta.url)
                    ),
                    'react-dom-client': fileURLToPath(
                        new URL('./resources/scripts/ext-runtime/react-dom-client.ts', import.meta.url)
                    ),
                    'tanstack-react-query': fileURLToPath(
                        new URL('./resources/scripts/ext-runtime/tanstack-react-query.ts', import.meta.url)
                    ),
                    sdk: fileURLToPath(new URL('./resources/scripts/sdk/index.ts', import.meta.url)),
                    'sdk-api': fileURLToPath(new URL('./resources/scripts/sdk/api.ts', import.meta.url)),
                },
                // Keep every entry's export signature intact — the runtime entries are
                // consumed as libraries by extension bundles via the import map.
                preserveEntrySignatures: 'strict' as const,
                output: {
                    entryFileNames: (chunk: { name: string }) =>
                        chunk.name === 'main' ? 'bundle.[hash].js' : 'ext-runtime.[name].[hash].js',
                    chunkFileNames: '[name].[hash].js',
                    assetFileNames: '[name].[hash][extname]',
                },
            },
        },
        server: {
            host: true,
            port: 5173,
            strictPort: false,
        },
    };
});
