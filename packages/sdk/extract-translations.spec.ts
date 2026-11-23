import { spawnSync } from 'node:child_process';
import { mkdirSync, mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { resolve } from 'node:path';
import { afterEach, beforeEach, expect, it } from 'vitest';

const script = resolve(process.cwd(), 'scripts/extract-translations.mjs');
let directory: string;

const run = (...args: string[]) =>
    spawnSync(
        process.execPath,
        [script, '--src', resolve(directory, 'src'), '--out', resolve(directory, 'en.json'), ...args],
        {
            encoding: 'utf8',
        }
    );

const write = (path: string, source: string) => {
    mkdirSync(resolve(directory, 'src', path, '..'), { recursive: true });
    writeFileSync(resolve(directory, 'src', path), source);
};

const catalogue = (): Record<string, string> => JSON.parse(readFileSync(resolve(directory, 'en.json'), 'utf8'));

beforeEach(() => {
    directory = mkdtempSync(resolve(tmpdir(), 'panel-lang-'));
});

afterEach(() => rmSync(directory, { recursive: true, force: true }));

it('collects the english keys of t() calls and Translate elements', () => {
    write(
        'Page.tsx',
        [
            'const { t } = useTranslation();',
            "t('Login to Continue');",
            't(`Password`);',
            "i18n.t('Welcome back, {{name}}', { name });",
            "t('{{count}} file', { count });",
            "t('greeting', { ns: 'activity' });",
            "export const nav = { label: translatable('Servers') };",
            "t('Showing {{start}} of {{count}} servers', range);",
            "export const Probe = () => <><Translate i18nKey='Save changes' /><Translate ns='activity' i18nKey='auth.fail' /></>;",
        ].join('\n')
    );
    write(
        'nested/Notes.tsx',
        "const { t } = useExtensionTranslation();\nt('messages.title');\nt('Login to Continue');"
    );
    write('Page.spec.tsx', "t('Only in a test');");
    write('api/generated/types.gen.ts', "t('Generated');");

    expect(run().status).toBe(0);
    expect(catalogue()).toEqual({
        'Login to Continue': 'Login to Continue',
        Password: 'Password',
        'Save changes': 'Save changes',
        Servers: 'Servers',
        'Showing {{start}} of {{count}} servers_one': 'Showing {{start}} of {{count}} servers',
        'Showing {{start}} of {{count}} servers_other': 'Showing {{start}} of {{count}} servers',
        'Welcome back, {{name}}': 'Welcome back, {{name}}',
        '{{count}} file_one': '{{count}} file',
        '{{count}} file_other': '{{count}} file',
    });
});

it('keeps hand-written english plurals and drops keys the code no longer uses', () => {
    write('Page.tsx', "t('{{count}} file', { count });");
    writeFileSync(
        resolve(directory, 'en.json'),
        JSON.stringify({
            '{{count}} file_one': '{{count}} file',
            '{{count}} file_other': '{{count}} files',
            Removed: 'Removed',
        })
    );

    expect(run().status).toBe(0);
    expect(catalogue()).toEqual({ '{{count}} file_one': '{{count}} file', '{{count}} file_other': '{{count}} files' });
});

it('fails the check until the catalogue is extracted again', () => {
    write('Page.tsx', "t('Login');");

    expect(run('--check').status).toBe(1);
    expect(run().status).toBe(0);
    expect(run('--check').status).toBe(0);

    write('Page.tsx', "t('Login');\nt('Logout');");
    const result = run('--check');

    expect(result.status).toBe(1);
    expect(result.stderr).toContain('is out of date. Run npm run lang:extract.');
});
