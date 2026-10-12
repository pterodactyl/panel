import { defineConfig } from 'vitest/config';
import { fileURLToPath } from 'node:url';
import { pterodactylTestAliases, pterodactylTestSetup } from '../vitest.mjs';

// Runs the docs' sample theme tests against the SDK test runtime, as a theme's own vitest.config does.
export default defineConfig({
    root: fileURLToPath(new URL('.', import.meta.url)),
    resolve: {
        alias: [
            ...pterodactylTestAliases(),
            // The child theme sample imports Aurora's built entry; the Aurora sample's source stands in for it.
            {
                find: /^aurora\/dist\/client\.js$/,
                replacement: fileURLToPath(new URL('./aurora/src/theme.tsx', import.meta.url)),
            },
        ],
    },
    test: {
        name: 'docs-samples',
        environment: 'jsdom',
        setupFiles: [pterodactylTestSetup],
        include: ['**/*.spec.{ts,tsx}'],
    },
});
