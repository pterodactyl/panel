import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { externalizeExtensionApi } from '../packages/sdk/openapi.mjs';

const extension = process.argv[2];
if (!extension || !/^[a-z][a-z0-9-]{0,47}$/.test(extension)) {
    throw new Error('Usage: node scripts/externalize-extension-api-runtime.mjs <extension-id> [package-path]');
}
const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
await externalizeExtensionApi(resolve(process.argv[3] ?? resolve(root, 'extensions', extension), 'src/client/generated'));
console.log(`Externalized HeyAPI runtime for ${extension}.`);
