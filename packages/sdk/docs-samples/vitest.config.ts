import { defineConfig } from 'vitest/config';
import { fileURLToPath } from 'node:url';
import { pterodactylTestAliases, pterodactylTestSetup } from '../vitest.mjs';

// Runs the README's sample theme tests against the SDK test runtime, as a theme's own vitest.config does.
export default defineConfig({
    root: fileURLToPath(new URL('.', import.meta.url)),
    resolve: { alias: pterodactylTestAliases() },
    test: {
        name: 'docs-samples',
        environment: 'jsdom',
        setupFiles: [pterodactylTestSetup],
        include: ['**/*.spec.{ts,tsx}'],
    },
});
