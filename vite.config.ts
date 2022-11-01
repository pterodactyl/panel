import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { dirname, resolve } from 'pathe';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite';

export default defineConfig({
    build: {
        assetsInlineLimit: 0,
        emptyOutDir: true,
        manifest: true,
        outDir: 'public/build',

        rollupOptions: {
            input: 'resources/scripts/index.tsx',
        },
    },

    define: {
        'process.env': {},
        'process.platform': null,
        'process.version': null,
        'process.versions': null,
    },

    plugins: [
        laravel('resources/scripts/index.tsx'),
        react({
            babel: {
                plugins: ['babel-plugin-macros', 'babel-plugin-styled-components'],
            },
        }),
    ],

    resolve: {
        alias: {
            '@': resolve(dirname(fileURLToPath(import.meta.url)), 'resources', 'scripts'),
            '@definitions': resolve(
                dirname(fileURLToPath(import.meta.url)),
                'resources',
                'scripts',
                'api',
                'definitions',
            ),
            '@feature': resolve(
                dirname(fileURLToPath(import.meta.url)),
                'resources',
                'scripts',
                'components',
                'server',
                'features',
            ),
        },
    },
});
