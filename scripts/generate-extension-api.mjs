import { spawnSync } from 'node:child_process';
import { existsSync, readFileSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const target = process.argv[2];
if (!target) throw new Error('Usage: npm run extension:api:generate -- <extension-id-or-package-path>');
const directory = /^[a-z][a-z0-9-]{0,47}$/.test(target) ? resolve(root, 'extensions', target) : resolve(target);
const manifest = JSON.parse(readFileSync(resolve(directory, 'extension.json'), 'utf8'));
const extension = manifest.id;
if (!/^[a-z][a-z0-9-]{0,47}$/.test(extension)) throw new Error('Invalid extension id.');
const openapiConfig = resolve(directory, 'openapi-ts.config.ts');
if (!existsSync(openapiConfig)) throw new Error(`OpenAPI TS config not found: ${openapiConfig}`);

const run = (command, args, cwd = root) => {
    const result = spawnSync(command, args, { cwd, stdio: 'inherit', shell: process.platform === 'win32' });
    if (result.error) throw result.error;
    if (result.status !== 0) process.exit(result.status ?? 1);
};

run('php', [resolve(root, 'scripts/generate-extension-openapi.php'), extension,
    resolve(root, 'storage/app/private/scribe/openapi.yaml'), resolve(directory, 'openapi.yaml')]);
run(resolve(root, 'node_modules/.bin/openapi-ts' + (process.platform === 'win32' ? '.cmd' : '')), ['-f', openapiConfig], directory);
run('node', [resolve(root, 'scripts/externalize-extension-api-runtime.mjs'), extension, directory]);
