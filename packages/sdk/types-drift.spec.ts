import ts from 'typescript';
import { cpSync, mkdtempSync, mkdirSync, readdirSync, readFileSync, rmSync, symlinkSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, resolve } from 'node:path';
import { spawnSync } from 'node:child_process';
import { describe, expect, it } from 'vitest';

const root = resolve(__dirname, '../..');
const config = ts.readConfigFile(resolve(root, 'tsconfig.json'), ts.sys.readFile);
const options = ts.parseJsonConfigFileContent(config.config, ts.sys, root).options;
const entries = [
    'resources/scripts/sdk/index.ts',
    'resources/scripts/sdk/api.ts',
    'packages/sdk/types/index.d.ts',
    'packages/sdk/types/api.d.ts',
];

it('publishes the same value exports as the runtime entry points', () => {
    const program = ts.createProgram(
        entries.map((path) => resolve(root, path)),
        options
    );
    const checker = program.getTypeChecker();
    const values = (path: string) => {
        const source = program.getSourceFile(resolve(root, path))!;
        const symbol = checker.getSymbolAtLocation(source)!;
        return checker
            .getExportsOfModule(symbol)
            .filter((exported) => {
                const target = exported.flags & ts.SymbolFlags.Alias ? checker.getAliasedSymbol(exported) : exported;
                return target.flags & ts.SymbolFlags.Value;
            })
            .map((symbol) => symbol.name)
            .sort();
    };
    expect(values(entries[2])).toEqual(values(entries[0]));
    expect(values(entries[3])).toEqual(values(entries[1]));
    expect(values(entries[0]).length).toBeGreaterThan(20);
}, 30_000);

const consumer = `
import { createExtensionTestHost } from '@pterodactyl/sdk/testing';
import { createComponentTestHost, createTestServerCard, createTestFileDetails } from '@pterodactyl/sdk/testing';
import type { ReplacementProps, ComponentPartProps } from '@pterodactyl/sdk';
import { createTestFileEditor, createTestFileManager } from '@pterodactyl/sdk/testing';
const editorHost = createComponentTestHost('server.files.editor', {model: createTestFileEditor({readOnly: true})});
const managerHost = createComponentTestHost('server.files.manager', {model: createTestFileManager()});
// @ts-expect-error An editor model is not a file browser model.
createComponentTestHost('server.files.manager', {model: createTestFileEditor()});
function Editor({model, Default}: ReplacementProps<'server.files.editor'>) {
  const saved: Promise<boolean> = model.saveAs('copy.txt');
  // @ts-expect-error The model is read-only; report edits through change().
  model.content = '';
  return <Default parts={{editor: ({model}) => <textarea defaultValue={model.content} readOnly={model.readOnly} onChange={event => model.change(event.target.value)} />}} />;
}
function Browser({model, parts}: ReplacementProps<'server.files.manager'>) {
  const removed: Promise<void> = model.actions.remove(model.selection);
  // @ts-expect-error Entries have a closed set of kinds.
  const isSocket = model.entries[0]?.kind === 'socket';
  return <ul><parts.toolbar model={model} />{model.entries.map(entry => <li key={entry.path} onClick={() => void model.actions.open(entry.name)}>{entry.name}</li>)}</ul>;
}
const cardHost = createComponentTestHost('dashboard.serverCard', {model: createTestServerCard()});
const fileHost = createComponentTestHost('server.files.details', {model: createTestFileDetails()});
const nativeCard = <cardHost.props.Default />;
// @ts-expect-error Component models remain distinct.
createComponentTestHost('dashboard.serverCard', {model: createTestFileDetails()});
function Metrics({model}: ComponentPartProps<'dashboard.serverCard'>) {return <span>{model.name}</span>;}
function Card({Default, model, parts}: ReplacementProps<'dashboard.serverCard'>) {
  const identity = <parts.identity model={model} />;
  return <Default parts={{metrics: Metrics}} />;
}
const host = createExtensionTestHost({path: '/account'});
host.emitWebsocket('status', 'running');
// @ts-expect-error The test host only emits known socket events.
host.emitWebsocket('made-up', 'bad');
import { createRef } from 'react';
import { Activity, useEffectEvent } from 'react';
import { Button, Form, useAppForm, useCurrentServer, definePterodactylExtension, type SdkServer } from '@pterodactyl/sdk';
import { client, createClient, type Options } from '@pterodactyl/sdk/api';
import { PanelLink, Table, serverFilesQueryOptions, queryClient, type PanelDestination } from '@pterodactyl/sdk';
const serverLink = <PanelLink destination={{to: '/server/$id/files', params: {id: 'abc'}}}>Files</PanelLink>;
// @ts-expect-error Core routes require their parameters.
const invalidDestination: PanelDestination = {to: '/server/$id/files'};
// @ts-expect-error Core navigation only accepts known routes.
const invalidRoute: PanelDestination = {to: '/made-up'};
import { useQuery } from '@tanstack/react-query';
import { serverFileContentQueryOptions, serverResourcesQueryOptions, useNavigationBlocker } from '@pterodactyl/sdk';
import { useSendServerPower, useSendServerCommand } from '@pterodactyl/sdk';
const fileContent: string | undefined = useQuery(serverFileContentQueryOptions('abc', '/server.properties')).data;
// @ts-expect-error File content is text.
const fileLength: number | undefined = useQuery(serverFileContentQueryOptions('abc', '/server.properties')).data;
const powerState = useQuery({...serverResourcesQueryOptions('abc'), select: resources => resources.attributes.current_state}).data;
const isRunning: boolean = powerState === 'running';
// @ts-expect-error Power states are a closed set.
const isPaused: boolean = powerState === 'paused';
void useSendServerPower('abc').mutateAsync({signal: 'restart'});
// @ts-expect-error Power signals are a closed set.
void useSendServerPower('abc').mutateAsync({signal: 'pause'});
void useSendServerCommand('abc').mutateAsync({command: 'say hi'});
useNavigationBlocker(true, {message: 'Discard?', confirm: transition => transition.next.pathname === transition.current.pathname});
// @ts-expect-error The prompt decides with a boolean.
useNavigationBlocker(() => true, {confirm: () => 'leave'});
const files = queryClient.getQueryData(serverFilesQueryOptions('abc', '/').queryKey);
const fileName: string | undefined = files?.data[0]?.attributes.name;
// @ts-expect-error Host file names are strings.
const fileSize: number | undefined = files?.data[0]?.attributes.name;
const table = <Table rows={[{id: 'one'}]} columns={[{id: 'name', label: 'Name', cell: row => row.id}]} keyOf={row => row.id} emptyState="No rows" />;
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@pterodactyl/sdk';
const empty = <Empty className="border"><EmptyHeader><EmptyMedia variant="icon" /><EmptyTitle>No rows</EmptyTitle><EmptyDescription>Add one.</EmptyDescription></EmptyHeader><EmptyContent /></Empty>;
// @ts-expect-error Empty media variants are a closed set.
const invalidEmptyMedia = <EmptyMedia variant="avatar" />;
const name: string | undefined = useCurrentServer(server => server.attributes.name);
// @ts-expect-error Selected data is a string, not a number.
const count: number | undefined = useCurrentServer(server => server.attributes.name);
const button = <Button ref={createRef<HTMLButtonElement>()} onClick={event => event.currentTarget.focus()}>OK</Button>;
// @ts-expect-error Invalid component prop.
const invalidButton = <Button isLoading="yes" />;
const form = useAppForm({ defaultValues: { name: '', count: 0 } });
form.setFieldValue('count', 1);
// @ts-expect-error Field values remain typed.
form.setFieldValue('count', 'one');
// @ts-expect-error Field names remain typed.
form.setFieldValue('missing', 'value');
const fields = <Form form={form}><form.AppField name="name">{field => <field.TextField label="Name" />}</form.AppField></Form>;
// @ts-expect-error Form requires its form instance.
const invalidForm = <Form />;
definePterodactylExtension({ setup({ slots, screens, columns, components }) {
  components.replace('dashboard.serverCard', Card);
  components.replace('dashboard.serverCard', {load: async () => ({default: Card})});
  components.replace('server.files.editor', Editor);
  components.replace('server.files.manager', {load: async () => ({default: Browser})});
  // @ts-expect-error A file browser cannot replace the editor.
  components.replace('server.files.editor', Browser);
  // @ts-expect-error Names are stable catalog identifiers.
  components.replace('components/dashboard/ServerRow', Card);
  // @ts-expect-error A file view cannot replace a server card.
  components.replace('server.files.details', Card);
  // @ts-expect-error Parts must be named by this contract.
  const unknownPart = <cardHost.props.Default parts={{unknown: Metrics}} />;
  slots.register('panel.users.detail.form', ({ data }) => <data.form.AppField name="username">{field => <field.TextField label="Custom username" />}</data.form.AppField>);
  // @ts-expect-error Native form contributions preserve core field names.
  slots.register('panel.users.detail.form', ({ data }) => <data.form.AppField name="unknown">{field => null}</data.form.AppField>);
  slots.register('server.console.before', ({ data }) => <span>{data.attributes.name}</span>);
  // @ts-expect-error This slot has route data, not server data.
  slots.register('auth.login.before', ({ data }: {data: SdkServer}) => <span>{data.attributes.name}</span>);
  columns.register('admin.nodes', {id: 'health', label: 'Health', component: ({data}) => <span>{data.attributes.fqdn}</span>});
  // @ts-expect-error Tables accept only known targets.
  columns.register('unknown', {id: 'health', label: 'Health', component: () => null});
  slots.register('server.startup.form', ({data}) => <button onClick={() => data.setDockerImage('test/image')}>{data.configuration.meta?.startup_command}</button>);
  screens.register('main', async () => ({default: () => null}));
  // @ts-expect-error Route metadata is declared in the manifest.
  screens.registerServerScreen({path: 'old'});
} });
`;

// Validate the exact imports emitted into standalone extension clients.
const externalizedClientImports = [
    ...new Set(
        Array.from(
            readFileSync(resolve(root, 'packages/sdk/openapi.mjs'), 'utf8').matchAll(
                /import [^;'"]+ from '@pterodactyl\/sdk\/api';/g
            ),
            ([statement]) => statement
        )
    ),
].join('\n');
if (!externalizedClientImports) throw new Error('No shared runtime imports found in the extension API generator.');

describe('packed SDK consumer', () => {
    it('resolves without panel aliases and rejects invalid props, fields, payloads and selectors', () => {
        const directory = mkdtempSync(resolve(tmpdir(), 'pterodactyl-sdk-'));
        try {
            const packed = spawnSync(
                'npm',
                [
                    'pack',
                    '--ignore-scripts',
                    '--cache',
                    resolve(directory, 'npm-cache'),
                    '--json',
                    '--pack-destination',
                    directory,
                ],
                {
                    cwd: __dirname,
                    encoding: 'utf8',
                    // npm is a command script on Windows.
                    shell: process.platform === 'win32',
                }
            );
            expect(packed.status, packed.stderr).toBe(0);
            const filename = JSON.parse(packed.stdout)[0].filename;
            const target = resolve(directory, 'node_modules/@pterodactyl/sdk');
            mkdirSync(target, { recursive: true });
            // Relative paths: GNU tar reads a drive letter as a remote host.
            expect(
                spawnSync('tar', ['-xzf', filename, '-C', 'node_modules/@pterodactyl/sdk', '--strip-components=1'], {
                    cwd: directory,
                }).status
            ).toBe(0);
            const metadata = JSON.parse(readFileSync(resolve(target, 'package.json'), 'utf8'));
            for (const dependency of [...Object.keys(metadata.peerDependencies), '@types/react', '@types/react-dom']) {
                const destination = resolve(directory, 'node_modules', dependency);
                mkdirSync(dirname(destination), { recursive: true });
                symlinkSync(resolve(root, 'node_modules', dependency), destination, 'junction');
            }
            const runtime = spawnSync(
                'node',
                [
                    '--input-type=module',
                    '--eval',
                    "const sdk = await import('./node_modules/@pterodactyl/sdk/testing-runtime/sdk.js'); if (typeof sdk.useExtensionTranslation !== 'function') throw new Error('SDK runtime export missing');",
                ],
                { cwd: directory, encoding: 'utf8' }
            );
            expect(runtime.status, runtime.stderr).toBe(0);
            const source = resolve(directory, 'consumer.tsx');
            writeFileSync(source, consumer);
            const generatedClient = resolve(directory, 'generated-client.ts');
            writeFileSync(
                generatedClient,
                `${externalizedClientImports}\nexport type Options<TData extends TDataShape = TDataShape> = Options2<TData> & { client?: Client };\n`
            );
            const program = ts.createProgram([source, generatedClient], {
                strict: true,
                noEmit: true,
                skipLibCheck: false,
                target: ts.ScriptTarget.ES2022,
                module: ts.ModuleKind.ESNext,
                moduleResolution: ts.ModuleResolutionKind.Bundler,
                jsx: ts.JsxEmit.ReactJSX,
                esModuleInterop: true,
                types: ['react'],
                typeRoots: [resolve(directory, 'node_modules/@types')],
            });
            const diagnostics = ts.getPreEmitDiagnostics(program);
            expect(externalizedClientImports).toContain('RequestData as TDataShape');
            expect(
                ts.formatDiagnostics(diagnostics, {
                    getCanonicalFileName: (file) => file,
                    getCurrentDirectory: () => directory,
                    getNewLine: () => '\n',
                })
            ).toBe('');
        } finally {
            rmSync(directory, { recursive: true, force: true });
        }
    }, 60_000);
});

describe('linked SDK consumer', () => {
    // A file: dependency is a link into the panel checkout, whose own node_modules hold other versions of the peers.
    it('resolves SDK peers from the scaffolded extension rather than from the panel checkout', () => {
        const directory = mkdtempSync(resolve(tmpdir(), 'pterodactyl-sdk-link-'));
        try {
            const link = (name: string) => {
                const destination = resolve(directory, 'node_modules', name);
                mkdirSync(dirname(destination), { recursive: true });
                symlinkSync(resolve(root, 'node_modules', name), destination, 'junction');
            };
            const own = ['@tanstack/react-query', '@tanstack/query-core'];
            for (const name of readdirSync(resolve(root, 'node_modules'))) {
                if (name.startsWith('.')) continue;
                if (!name.startsWith('@')) link(name);
                else
                    for (const scoped of readdirSync(resolve(root, 'node_modules', name)))
                        if (!own.includes(`${name}/${scoped}`)) link(`${name}/${scoped}`);
            }
            for (const name of own) {
                const destination = resolve(directory, 'node_modules', name);
                cpSync(resolve(root, 'node_modules', name), destination, { recursive: true, dereference: true });
                const metadata = JSON.parse(readFileSync(resolve(destination, 'package.json'), 'utf8'));
                // Same declarations under another version: TypeScript only merges copies of one name and version.
                metadata.version += '-consumer';
                writeFileSync(resolve(destination, 'package.json'), JSON.stringify(metadata));
            }
            mkdirSync(resolve(directory, 'node_modules/@pterodactyl'), { recursive: true });
            symlinkSync(__dirname, resolve(directory, 'node_modules/@pterodactyl/sdk'), 'junction');
            mkdirSync(resolve(directory, 'src/client'), { recursive: true });
            writeFileSync(
                resolve(directory, 'src/client/index.tsx'),
                `import { useQuery } from '@tanstack/react-query';
import { serverFileContentQueryOptions } from '@pterodactyl/sdk';
export const useContent = (uuid: string, file: string): string | undefined =>
    useQuery(serverFileContentQueryOptions(uuid, file)).data;
`
            );
            const stub = readFileSync(resolve(root, 'app/Services/Extensions/Scaffolding/stubs/tsconfig.stub'), 'utf8');
            const check = (overrides: ts.CompilerOptions) => {
                const parsed = ts.parseJsonConfigFileContent(JSON.parse(stub), ts.sys, directory);
                expect(parsed.fileNames).toHaveLength(1);
                const program = ts.createProgram(parsed.fileNames, { ...parsed.options, ...overrides });
                return ts.formatDiagnostics(ts.getPreEmitDiagnostics(program), {
                    getCanonicalFileName: (file) => file,
                    getCurrentDirectory: () => directory,
                    getNewLine: () => '\n',
                });
            };
            expect(check({})).toBe('');
            // Without the scaffolded option the link resolves to the checkout and its second QueryClient.
            expect(check({ preserveSymlinks: false })).toContain("Property '#private' in type 'QueryClient'");
        } finally {
            rmSync(directory, { recursive: true, force: true });
        }
    }, 120_000);
});

it('publishes the same component catalog as the manifest schema', async () => {
    const { COMPONENT_NAMES } = await import('../../resources/scripts/extensions/componentTypes');
    const schema = JSON.parse(readFileSync(resolve(root, 'packages/sdk/manifest.schema.json'), 'utf8'));
    expect(schema.properties.ui.properties.components.items.enum).toEqual([...COMPONENT_NAMES]);
});
