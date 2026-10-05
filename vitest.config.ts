import { defineConfig } from 'vitest/config';
import { fileURLToPath, URL } from 'node:url';

// Standalone Vitest config — deliberately separate from vite.config.ts so the test
// run doesn't pull in the SPA build plugins (CSS-injected-by-JS, manifest emitter).
// The specs are pure-function unit tests, so the default `node` environment is enough.
export default defineConfig({
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/scripts', import.meta.url)),
            '@feature': fileURLToPath(new URL('./resources/scripts/components/server/features', import.meta.url)),
        },
    },
    test: {
        projects: [{ extends: true, test: { name: 'panel' } }, 'packages/hello-world/vitest.config.ts'],
        environment: 'node',
        setupFiles: ['./resources/scripts/setup-tests.ts'],
        include: [
            'resources/scripts/**/*.spec.{ts,tsx}',
            'examples/*/build-contract.spec.ts',
            'extensions/*/build-contract.spec.ts',
            'packages/sdk/*.spec.ts',
            'tools/oxlint/**/*.spec.ts',
        ],
        // Keep transient git worktrees (under .claude/) out of the run so the suite
        // count reflects the real tracked specs, not duplicated copies.
        exclude: ['**/node_modules/**', '.claude/**'],
    },
});
