import { spawnSync } from 'node:child_process';
import { mkdirSync, mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { afterAll, beforeAll, expect, it } from 'vitest';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '../../..');
let directory: string;

beforeAll(() => {
    directory = mkdtempSync(resolve(tmpdir(), 'panel-lint-'));
    const config = JSON.parse(readFileSync(resolve(root, '.oxlintrc.json'), 'utf8'));
    config.jsPlugins = config.jsPlugins.map((plugin: { name: string; specifier: string }) => ({
        ...plugin,
        specifier: resolve(root, plugin.specifier),
    }));
    writeFileSync(resolve(directory, '.oxlintrc.json'), JSON.stringify(config));
});

afterAll(() => rmSync(directory, { recursive: true, force: true }));

const components = 'resources/scripts/components/Probe.tsx';
const api = 'resources/scripts/api/queries.ts';
const generated = 'resources/scripts/api/generated/types.gen.ts';

it.each([
    [
        'rejects aliased generated imports',
        components,
        "import type { User } from '@/api/generated/types.gen';",
        'generated-api-imports',
    ],
    [
        'rejects relative generated imports',
        components,
        "import type { User } from '../api/generated/types.gen';",
        'generated-api-imports',
    ],
    ['rejects generated re-exports', components, "export * from '../api/generated';", 'generated-api-imports'],
    [
        'rejects dynamic generated imports',
        components,
        "export const load = () => import('@/api/generated/sdk.gen');",
        'generated-api-imports',
    ],
    ['rejects Zustand imports outside state', components, "import { create } from 'zustand';", 'no-restricted-imports'],
    [
        'rejects Zustand subpath imports outside state',
        components,
        "import { createStore } from 'zustand/vanilla';",
        'no-restricted-imports',
    ],
    [
        'rejects direct JSON transport',
        api,
        "export const load = () => http.get('/api/client');",
        'no-restricted-properties',
    ],
    [
        'rejects handwritten JSON mutations',
        api,
        'export const options = { mutationFn: save };',
        'generated-api-mutations',
    ],
    [
        'rejects computed handwritten mutation keys',
        api,
        "export const options = { ['mutationFn']: save };",
        'generated-api-mutations',
    ],
    [
        'rejects double assertions',
        api,
        'export const value = input as unknown as string;',
        'no-chained-type-assertions',
    ],
    ['rejects empty generated types', generated, 'export type User = {};', 'no-empty-object-type'],
    ['rejects unknown generated aliases', generated, 'export type UserResponse = unknown;', 'generated-api-types'],
    ['rejects unknown generated properties', generated, 'export type User = { name: unknown };', 'generated-api-types'],
    ['rejects unknown generated arrays', generated, 'export type Users = unknown[];', 'generated-api-types'],
    [
        'rejects nested unknown in accepted responses',
        generated,
        'export type Responses = { 202: { body: unknown } };',
        'generated-api-types',
    ],
    [
        'rejects palette classes',
        components,
        'export const View = () => <div className="bg-red-500" />;',
        'design-tokens',
    ],
    [
        'rejects hardcoded colors',
        components,
        "export const View = () => <div style={{ color: '#ff0000' }} />;",
        'design-tokens',
    ],
    ['rejects literal template colors', components, 'export const colors = `bg-red-500`;', 'design-tokens'],
    ['rejects escaped template colors', components, 'export const colors = `bg-\\u0072ed-500`;', 'design-tokens'],
    [
        'rejects embedded font stacks',
        components,
        "export const View = () => <div style={{ fontFamily: 'Arial' }} />;",
        'design-tokens',
    ],
    [
        'rejects literal SVG palette paint',
        components,
        'export const View = () => <svg fill="black" />;',
        'design-tokens',
    ],
])('%s', (_name, path, source, rule) => {
    const result = lint(path, source);
    expect(result.status).toBe(1);
    expect(result.diagnostics.some((diagnostic) => diagnostic.code?.includes(rule))).toBe(true);
});

it.each([
    ['allows API wrappers to import generated clients', api, "import { getUsers } from '@/api/generated/sdk.gen';"],
    ['allows the shared SDK API runtime', 'resources/scripts/sdk/api.ts', "export * from '@/api/generated/client';"],
    ['allows state modules to import Zustand', 'resources/scripts/state/probe.ts', "import { create } from 'zustand';"],
    ['allows generated mutation options', api, 'export const options = { ...generatedMutation() };'],
    [
        'allows browser file transport and custom mutations',
        'resources/scripts/api/server/files/queries.ts',
        "export const options = { mutationFn: () => http.put('/upload') };",
    ],
    [
        'allows signed backup download mutations',
        'resources/scripts/api/server/backups/queries.ts',
        'export const options = { mutationFn: download };',
    ],
    ['allows concrete generated schemas', generated, 'export type User = { name: string };'],
    ['allows a documented empty accepted response', generated, 'export type Responses = { 202: unknown };'],
    [
        'allows theme tokens',
        components,
        'export const View = () => <div className="bg-surface" style={{ color: "var(--color-text)" }} />;',
    ],
    [
        'ignores examples in comments',
        components,
        '// bg-red-500; mutationFn: save; http.get(); as unknown as\nexport const value = 1;',
    ],
    [
        'ignores generated imports in unrelated strings',
        components,
        'export const example = "import { User } from \'@/api/generated/types.gen\'";',
    ],
])('%s', (_name, path, source) => {
    const result = lint(path, source);
    expect(result.diagnostics.filter((diagnostic) => diagnostic.severity === 'error')).toEqual([]);
    expect(result.status).toBe(0);
});

function lint(
    path: string,
    source: string
): { status: number | null; diagnostics: { code?: string; severity: string }[] } {
    const filename = resolve(directory, path);
    mkdirSync(dirname(filename), { recursive: true });
    writeFileSync(filename, source);
    const result = spawnSync(resolve(root, 'node_modules/.bin/oxlint'), ['--format', 'json', path], {
        cwd: directory,
        encoding: 'utf8',
    });
    rmSync(filename);
    expect(result.stderr).toBe('');
    return { status: result.status, diagnostics: JSON.parse(result.stdout).diagnostics };
}
