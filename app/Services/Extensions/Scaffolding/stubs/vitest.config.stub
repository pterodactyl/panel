import { defineConfig } from 'vitest/config';
import { pterodactylTestAliases, pterodactylTestSetup, pterodactylTestDirectory } from '@pterodactyl/sdk/vitest';

export default defineConfig({
    server: { fs: { allow: [process.cwd(), pterodactylTestDirectory] } },
    resolve: { alias: pterodactylTestAliases() },
    test: { environment: 'jsdom', setupFiles: [pterodactylTestSetup] },
});
