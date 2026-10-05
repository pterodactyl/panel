import { mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { resolve } from 'node:path';
import { spawnSync } from 'node:child_process';
import { expect, it } from 'vitest';
import { externalizeExtensionApi } from './openapi.mjs';
import { existsSync } from 'node:fs';

it('extracts all four extension API areas without matching another extension', () => {
    const directory = mkdtempSync(resolve(tmpdir(), 'extension-openapi-'));
    try {
        const paths = [
            '/api/client/extensions/probe/status',
            '/api/admin/extensions/probe/settings',
            '/api/application/extensions/probe/status',
            '/api/client/servers/{server}/extensions/probe/status',
            '/api/client/extensions/probe-other/status',
        ];
        const spec = {
            openapi: '3.0.3',
            info: { title: 'Panel', version: '1' },
            paths: Object.fromEntries(
                paths.map((path) => [
                    path,
                    {
                        get: {
                            parameters: [],
                            responses: {
                                200: {
                                    description: 'OK',
                                    content: {
                                        'application/json': { schema: { $ref: '#/components/schemas/Status' } },
                                    },
                                },
                            },
                        },
                    },
                ])
            ),
            components: {
                schemas: {
                    Status: {
                        type: 'object',
                        additionalProperties: {},
                        properties: { metadata: { $ref: '#/components/schemas/Metadata' } },
                    },
                    Metadata: { type: 'object', properties: { ready: { type: 'boolean' } } },
                    Unused: { type: 'string' },
                },
            },
        };
        const input = resolve(directory, 'input.json');
        const output = resolve(directory, 'output.json');
        writeFileSync(input, JSON.stringify(spec));

        const result = spawnSync('php', ['scripts/generate-extension-openapi.php', 'probe', input, output], {
            encoding: 'utf8',
        });

        expect(result.status, result.stderr).toBe(0);
        expect(result.stdout).toContain('(4 paths)');
        const generated = readFileSync(output, 'utf8');
        for (const path of paths.slice(0, 4)) expect(generated).toContain(path);
        expect(generated).not.toContain(paths[4]);
        expect(JSON.parse(generated)).toMatchObject({
            paths: Object.fromEntries(paths.slice(0, 4).map((path) => [path, spec.paths[path]])),
            components: {
                schemas: { Status: spec.components.schemas.Status, Metadata: spec.components.schemas.Metadata },
            },
        });
        expect(JSON.parse(generated).components.schemas).not.toHaveProperty('Unused');
    } finally {
        rmSync(directory, { recursive: true, force: true });
    }
});

it('generates a standalone extension client against the shared host transport', async () => {
    const directory = mkdtempSync(resolve(tmpdir(), 'extension-client-'));
    try {
        const input = resolve(directory, 'openapi.json');
        const output = resolve(directory, 'generated');
        writeFileSync(
            input,
            JSON.stringify({
                openapi: '3.0.3',
                info: { title: 'Probe', version: '1' },
                paths: {
                    '/api/client/servers/{server}/extensions/probe/status': {
                        get: {
                            operationId: 'getProbeStatus',
                            parameters: [{ name: 'server', in: 'path', required: true, schema: { type: 'string' } }],
                            responses: {
                                200: {
                                    description: 'OK',
                                    content: {
                                        'application/json': {
                                            schema: {
                                                type: 'object',
                                                required: ['ready'],
                                                properties: { ready: { type: 'boolean' } },
                                            },
                                        },
                                    },
                                },
                            },
                        },
                    },
                },
            })
        );
        const config = resolve(directory, 'config.mjs');
        writeFileSync(
            config,
            `export default ${JSON.stringify({
                input,
                output: { path: output },
                plugins: [
                    '@hey-api/typescript',
                    { name: '@hey-api/client-axios', baseUrl: false, throwOnError: true },
                    { name: '@hey-api/sdk', paramsStructure: 'grouped' },
                    { name: '@tanstack/react-query', queryKeys: true, queryOptions: true, mutationOptions: true },
                ],
            })};`
        );
        const result = spawnSync(resolve('node_modules/.bin/openapi-ts'), ['-f', config], { encoding: 'utf8' });
        expect(result.status, result.stderr).toBe(0);
        await externalizeExtensionApi(output);
        expect(readFileSync(resolve(output, 'sdk.gen.ts'), 'utf8')).toContain("from '@pterodactyl/sdk/api'");
        expect(readFileSync(resolve(output, '@tanstack/react-query.gen.ts'), 'utf8')).toContain('getProbeStatus');
        expect(existsSync(resolve(output, 'client'))).toBe(false);
        expect(existsSync(resolve(output, 'client.gen.ts'))).toBe(false);
    } finally {
        rmSync(directory, { recursive: true, force: true });
    }
});
