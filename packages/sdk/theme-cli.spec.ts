import ts from 'typescript';
import { spawnSync } from 'node:child_process';
import { existsSync, mkdirSync, mkdtempSync, readFileSync, rmSync, symlinkSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, resolve } from 'node:path';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';
// @ts-expect-error The bin is plain JavaScript without declarations.
import { classicSkins, ejectView, run } from './bin/pterodactyl-theme.mjs';

const root = resolve(__dirname, '../..');
const bin = resolve(__dirname, 'bin/pterodactyl-theme.mjs');
const views = JSON.parse(readFileSync(resolve(__dirname, 'views.json'), 'utf8'));
const { version } = JSON.parse(readFileSync(resolve(__dirname, 'package.json'), 'utf8'));

let directory = '';

beforeEach(() => {
    directory = mkdtempSync(resolve(tmpdir(), 'pterodactyl-theme-'));
});

afterEach(() => {
    rmSync(directory, { recursive: true, force: true });
});

const cli = (...args: string[]) => spawnSync(process.execPath, [bin, ...args], { cwd: directory, encoding: 'utf8' });

const write = (path: string, content: string) => {
    mkdirSync(dirname(resolve(directory, path)), { recursive: true });
    writeFileSync(resolve(directory, path), content);
};

describe('list', () => {
    it('prints every view with its kind and context, and marks the ones classic can eject', () => {
        const lines: string[] = [];

        run(['list', '--kind', 'block'], (line: string) => lines.push(line));

        const table = lines.join('\n');

        expect(table).toMatch(/^VIEW\s+KIND\s+CONTEXT\s+FIXTURES/);
        expect(table).toMatch(/dashboard\.serverCard\s+block\s+app\s+running, offline, suspended, loading\s+eject/);
        expect(table).not.toContain('layout.app');
    });

    it('prints the catalog as JSON', () => {
        const result = cli('list', '--json');

        expect(result.status).toBe(0);
        expect(JSON.parse(result.stdout)).toEqual(views);
    });
});

describe('eject', () => {
    it('copies classic source with SDK imports and prints the views entry', () => {
        const result = cli('eject', 'dashboard.serverCard');
        const ejected = readFileSync(resolve(directory, 'src/views/dashboard/ServerCard.tsx'), 'utf8');

        expect(result.status, result.stderr).toBe(0);
        expect(ejected.split('\n')[0]).toBe(
            `// Ejected from the classic theme of @pterodactyl/sdk ${version} (views/dashboard/ServerCard.tsx).`
        );
        expect(ejected).toContain("from '@pterodactyl/sdk';");
        expect(ejected).toContain("from '@pterodactyl/sdk/theme';");
        expect(ejected).not.toContain("'@/");
        expect(result.stdout).toContain("import ServerCard from './views/dashboard/ServerCard';");
        expect(result.stdout).toContain("views: { 'dashboard.serverCard': ServerCard },");
    });

    it('refuses to overwrite an earlier eject without --force', () => {
        expect(cli('eject', 'page.dashboard').status).toBe(0);
        writeFileSync(resolve(directory, 'src/views/dashboard/Dashboard.tsx'), 'edited');

        const again = cli('eject', 'page.dashboard');

        expect(again.status).toBe(1);
        expect(again.stderr).toContain('already exists. Pass --force to replace it.');
        expect(cli('eject', 'page.dashboard', '--force').status).toBe(0);
        expect(readFileSync(resolve(directory, 'src/views/dashboard/Dashboard.tsx'), 'utf8')).not.toBe('edited');
    });

    it('suggests views for an unknown name', () => {
        const unknown = cli('eject', 'server.card');

        expect(unknown.status).toBe(1);
        expect(unknown.stderr).toContain('There is no view named "server.card". Did you mean dashboard.serverCard');
    });

    it('follows relative imports within classic, keeping files an earlier eject copied', () => {
        const classic = resolve(directory, 'classic');

        write('classic/views/server/Power.tsx', "import { Pill } from './parts/Pill';\nexport default Pill;\n");
        write('classic/views/server/parts/Pill.tsx', "import { cn } from '@/sdk';\nexport * from '../../../shared/tone';\n");
        write('classic/shared/tone.ts', "export const tone = 'muted';\n");
        write('out/shared/tone.ts', 'kept');

        const result = ejectView('server.power', {
            views: { 'server.power': { classic: { file: 'views/server/Power.tsx', export: 'default' } } },
            root: classic,
            outDir: resolve(directory, 'out/views'),
            entryDir: resolve(directory, 'out'),
        });

        expect(result.written).toEqual([
            resolve(directory, 'out/views/server/Power.tsx'),
            resolve(directory, 'out/views/server/parts/Pill.tsx'),
        ]);
        expect(readFileSync(resolve(directory, 'out/views/server/parts/Pill.tsx'), 'utf8')).toContain(
            "import { cn } from '@pterodactyl/sdk';"
        );
        expect(readFileSync(resolve(directory, 'out/shared/tone.ts'), 'utf8')).toBe('kept');
        expect(result.importLine).toBe("import Power from './views/server/Power';");
    });

    it('produces code that typechecks against the SDK types', () => {
        for (const name of Object.keys(views).filter((view) => views[view].classic)) {
            expect(cli('eject', name).status).toBe(0);
        }

        symlinkSync(resolve(root, 'node_modules'), resolve(directory, 'node_modules'), 'junction');
        write(
            'src/theme.tsx',
            `import { defineTheme } from '@pterodactyl/sdk/theme';
import AppLayout from './views/layout/AppLayout';
import Dashboard from './views/dashboard/Dashboard';
import ServerList from './views/dashboard/ServerList';
import ServerCard from './views/dashboard/ServerCard';

export default defineTheme({
    views: { 'layout.app': AppLayout, 'page.dashboard': Dashboard, 'dashboard.serverList': ServerList, 'dashboard.serverCard': ServerCard },
});
`
        );

        const program = ts.createProgram([resolve(directory, 'src/theme.tsx')], {
            strict: true,
            noEmit: true,
            skipLibCheck: true,
            target: ts.ScriptTarget.ES2022,
            module: ts.ModuleKind.ESNext,
            moduleResolution: ts.ModuleResolutionKind.Bundler,
            jsx: ts.JsxEmit.ReactJSX,
            types: [],
            paths: {
                '@pterodactyl/sdk': [resolve(__dirname, 'types/index.d.ts')],
                '@pterodactyl/sdk/theme': [resolve(__dirname, 'types/theme.d.ts')],
            },
        });

        expect(program.getSourceFiles().some((file) => file.fileName.endsWith('views/dashboard/ServerCard.tsx'))).toBe(
            true
        );
        expect(
            ts.formatDiagnostics(ts.getPreEmitDiagnostics(program), {
                getCanonicalFileName: (file) => file,
                getCurrentDirectory: () => directory,
                getNewLine: () => '\n',
            })
        ).toBe('');
    }, 60_000);
});

describe('skins', () => {
    it('lists the primitives classic skins and prints one with the constants it reads', () => {
        write(
            'classic/skins/forms.ts',
            [
                "import type { CompleteSkin } from '@/sdk/theme';",
                '',
                "// Shared by the inputs; a ; in a comment does not end it.",
                "const control = { base: 'p-3 border', variants: { error: { true: 'border-destructive' } } };",
                "const unused = 'x';",
                "export const input = { root: control } satisfies CompleteSkin<'Input'>;",
                "export const label = { root: 'block; text-sm' } satisfies CompleteSkin<'Label'>;",
                '',
            ].join('\n')
        );
        write(
            'classic/skins/index.ts',
            "import type { CompleteSkins } from '@/sdk/theme';\nimport { input, label as fieldLabel } from './forms';\n\nexport const classicSkins = {\n    Input: input,\n    Label: fieldLabel,\n} satisfies CompleteSkins;\n"
        );

        const skins: Map<string, () => string> = classicSkins(resolve(directory, 'classic'));
        const input = skins.get('Input')!();

        expect([...skins.keys()]).toEqual(['Input', 'Label']);
        expect(input).toContain("import type { CompleteSkin } from '@pterodactyl/sdk/theme';");
        expect(input).toContain("// Shared by the inputs; a ; in a comment does not end it.\nconst control = {");
        expect(input).toContain("export const input = { root: control } satisfies CompleteSkin<'Input'>;");
        expect(input).not.toContain('unused');
        expect(input).not.toContain('export const label');
        expect(skins.get('Label')!()).toContain("export const label = { root: 'block; text-sm' }");
    });

    it('prints classic skin of every primitive as code that typechecks against the SDK types', () => {
        const listed = cli('skins');
        const primitives = listed.stdout.trim().split('\n');

        expect(listed.status).toBe(0);
        expect(primitives).toEqual(expect.arrayContaining(['Button', 'Card', 'Dialog', 'Select']));
        for (const primitive of primitives) {
            write(`skins/${primitive}.ts`, cli('skins', primitive.toLowerCase()).stdout);
        }

        expect(cli('skins', 'Buton').stderr).toContain('Classic has no skin for "Buton". It skins Alert, Avatar');

        const program = ts.createProgram(
            primitives.map((primitive) => resolve(directory, `skins/${primitive}.ts`)),
            {
                strict: true,
                noEmit: true,
                skipLibCheck: true,
                target: ts.ScriptTarget.ES2022,
                module: ts.ModuleKind.ESNext,
                moduleResolution: ts.ModuleResolutionKind.Bundler,
                types: [],
                typeRoots: [resolve(root, 'node_modules/@types')],
                paths: { '@pterodactyl/sdk/theme': [resolve(__dirname, 'types/theme.d.ts')] },
            }
        );

        expect(
            ts.formatDiagnostics(ts.getPreEmitDiagnostics(program), {
                getCanonicalFileName: (file) => file,
                getCurrentDirectory: () => directory,
                getNewLine: () => '\n',
            })
        ).toBe('');
    }, 60_000);
});

it('ships the bin and classic sources in the package', () => {
    const metadata = JSON.parse(readFileSync(resolve(__dirname, 'package.json'), 'utf8'));
    const prepack = readFileSync(resolve(__dirname, 'prepack.mjs'), 'utf8');

    expect(metadata.bin).toEqual({ 'pterodactyl-theme': './bin/pterodactyl-theme.mjs' });
    expect(metadata.files).toEqual(expect.arrayContaining(['bin', 'classic']));
    expect(prepack).toContain('resources/scripts/themes/classic');
    expect(existsSync(bin)).toBe(true);
});
