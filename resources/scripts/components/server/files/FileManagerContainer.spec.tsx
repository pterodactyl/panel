/** @vitest-environment jsdom */
import { cleanup, fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import { useState } from 'react';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import type { AxiosAdapter } from 'axios';
import type { ComponentReplacement, ReplacementProps } from '@/extensions/componentTypes';
import type * as Registry from '@/extensions/registry';
import type * as Testing from '@/sdk/testing';
import type * as Http from '@/api/http';
import type FileManagerContainer from './FileManagerContainer';
import type SelectFileCheckbox from './SelectFileCheckbox';

const rowRenders = new Map<string, number>();

let registry: typeof Registry;
let testing: typeof Testing;
let http: typeof Http.default;
let Container: typeof FileManagerContainer;
let host: Testing.ExtensionTestHost | undefined;
let portal: HTMLElement;
let requests: { path: string; body: unknown }[];
let originalAdapter: typeof http.defaults.adapter;

const entry = (name: string, mimetype: string, isFile = true) => ({
    object: 'file_object',
    attributes: {
        name,
        mode: isFile ? '-rw-r--r--' : 'drwxr-xr-x',
        mode_bits: isFile ? '0644' : '0755',
        size: isFile ? 12 : 0,
        is_file: isFile,
        is_symlink: false,
        mimetype,
        created_at: '2026-01-01T12:00:00Z',
        modified_at: '2026-01-01T12:00:00Z',
    },
});
const listings = new Map([
    [
        '/config',
        [entry('b.txt', 'text/plain'), entry('plugins', 'inode/directory', false), entry('a.zip', 'application/zip')],
    ],
    ['/config/plugins', [entry('inner.yml', 'text/plain')]],
]);
const manyNames = Array.from({ length: 252 }, (_, index) => `f${String(index).padStart(3, '0')}.txt`);

listings.set(
    '/many',
    manyNames.map((name) => entry(name, 'text/plain'))
);

beforeEach(async () => {
    vi.resetModules();
    rowRenders.clear();
    vi.doMock('./SelectFileCheckbox', async (importOriginal) => {
        const { default: Checkbox } = await importOriginal<{ default: typeof SelectFileCheckbox }>();

        return {
            default: ({ name }: { name: string }) => {
                rowRenders.set(name, (rowRenders.get(name) ?? 0) + 1);

                return <Checkbox name={name} />;
            },
        };
    });
    const [registered, sdk, transport, container] = await Promise.all([
        import('@/extensions/registry'),
        import('@/sdk/testing'),
        import('@/api/http'),
        import('./FileManagerContainer'),
    ]);

    registry = registered;
    testing = sdk;
    http = transport.default;
    Container = container.default;
    requests = [];
    originalAdapter = http.defaults.adapter;
    const adapter: AxiosAdapter = async (config) => {
        const url = new URL(config.url!, 'https://panel.test');
        const directory = url.searchParams.get('directory') ?? (config.params as { directory?: string })?.directory;

        requests.push({ path: url.pathname, body: config.data });
        const data = url.pathname.endsWith('/files/list')
            ? { object: 'list', data: listings.get(directory ?? '') ?? [] }
            : '';

        return { data, config, status: 200, statusText: 'OK', headers: {} };
    };

    http.defaults.adapter = adapter;
    vi.spyOn(console, 'error').mockImplementation(() => {});
    portal = document.body.appendChild(Object.assign(document.createElement('div'), { id: 'modal-portal' }));
}, 20000);
afterEach(() => {
    cleanup();
    host?.dispose();
    host = undefined;
    portal.remove();
    http.defaults.adapter = originalAdapter;
    vi.restoreAllMocks();
});

function replace(replacement: ComponentReplacement<'server.files.manager'>) {
    registry.prepareExtensions([{ id: 'browser', entry: '/browser.js', components: ['server.files.manager'] }]);
    const batch = registry.createExtensionRegistryBatch();

    registry.registerComponentReplacement('browser', 'server.files.manager', replacement, batch);
    registry.commitExtensionRegistryBatch('browser', batch);
}

function Custom({ model }: ReplacementProps<'server.files.manager'>) {
    const [error, setError] = useState('');
    const run = (action: Promise<void>) => void action.catch((cause: Error) => setError(cause.message));
    const { directory, loading, selection, permissions, actions } = model;

    return (
        <div>
            <output>
                {JSON.stringify({
                    directory,
                    loading,
                    entries: model.entries.map((entry) => `${entry.kind}:${entry.path}`),
                    selection,
                    permissions,
                })}
            </output>
            <button onClick={() => actions.select(['b.txt', 'missing'])}>Select</button>
            <button onClick={() => run(actions.open('plugins'))}>Open plugins</button>
            <button onClick={() => run(actions.navigate('..'))}>Up</button>
            <button onClick={() => run(actions.remove(['b.txt']))}>Remove</button>
            <button onClick={() => actions.select([...manyNames, 'a.zip', 'b.txt'])}>Select all</button>
            <button onClick={() => run(actions.refresh())}>Refresh</button>
            <span>{error}</span>
        </div>
    );
}

function open(permissions?: string[], directory = '/config') {
    host = testing.createExtensionTestHost({
        server: testing.createTestServer(permissions ? { owner: false, permissions } : {}),
        path: `/server/test-server/files#${directory}`,
    });
    render(<Container />, { wrapper: host.Wrapper });
}

const state = () => JSON.parse(screen.getByRole('status').textContent ?? '{}');
const deletes = () => requests.filter((request) => request.path.endsWith('/files/delete'));

it('gives a replacement the listing and runs selection, navigation and deletion through core', async () => {
    replace(Custom);
    open();

    await waitFor(() => expect(state().loading).toBe(false));
    expect(state()).toEqual({
        directory: '/config',
        loading: false,
        entries: ['directory:/config/plugins', 'archive:/config/a.zip', 'file:/config/b.txt'],
        selection: [],
        permissions: { create: true, update: true, delete: true, archive: true },
    });

    fireEvent.click(screen.getByRole('button', { name: 'Select' }));
    expect(state().selection).toEqual(['b.txt']);

    fireEvent.click(screen.getByRole('button', { name: 'Remove' }));
    await waitFor(() => expect(state().entries).toEqual(['directory:/config/plugins', 'archive:/config/a.zip']));
    expect(JSON.parse(String(deletes()[0].body))).toEqual({ root: '/config', files: ['b.txt'] });
    expect(state().selection).toEqual([]);

    fireEvent.click(screen.getByRole('button', { name: 'Open plugins' }));
    await waitFor(() => expect(state().entries).toEqual(['file:/config/plugins/inner.yml']));
    expect(state().directory).toBe('/config/plugins');

    fireEvent.click(screen.getByRole('button', { name: 'Up' }));
    await waitFor(() => expect(state().directory).toBe('/config'));
});

it('refuses a mutation the user has no permission for before any request is sent', async () => {
    replace(Custom);
    open(['file.read']);

    await waitFor(() => expect(state().loading).toBe(false));
    expect(state().permissions).toEqual({ create: false, update: false, delete: false, archive: false });
    fireEvent.click(screen.getByRole('button', { name: 'Remove' }));

    expect(await screen.findByText('This action requires the file.delete permission.')).toBeVisible();
    expect(deletes()).toEqual([]);
});

it('composes native parts and keeps the native browser when the replacement fails', async () => {
    replace(({ Default, model }) => {
        if (model.selection.length) {
            throw new Error('browser crashed');
        }

        return <Default parts={{ selection: () => <p>Custom selection</p> }} />;
    });
    open();

    expect(await screen.findByRole('link', { name: 'b.txt' })).toHaveAttribute(
        'href',
        '/server/test-server/files/edit#/config/b.txt'
    );
    expect(screen.getByRole('button', { name: 'New file' })).toBeVisible();
    expect(screen.getByText('Custom selection')).toBeVisible();

    fireEvent.click(screen.getAllByRole('checkbox')[0]);

    expect(await screen.findByRole('button', { name: 'Archive' })).toBeVisible();
    expect(screen.queryByText('Custom selection')).not.toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'plugins' })).toBeVisible();
    expect(registry.getExtensionStates()[0].error).toContain('browser crashed');
});

it('selects only the listed rows of a truncated directory', async () => {
    replace(Custom);
    open(undefined, '/many');

    await waitFor(() => expect(state().loading).toBe(false));
    expect(state().entries).toHaveLength(250);
    fireEvent.click(screen.getByRole('button', { name: 'Select all' }));

    expect(state().selection).toEqual(manyNames.slice(0, 250));
});

it('drops files that leave the listing from the selection', async () => {
    replace(Custom);
    open();
    await waitFor(() => expect(state().loading).toBe(false));
    fireEvent.click(screen.getByRole('button', { name: 'Select all' }));
    expect(state().selection).toEqual(['a.zip', 'b.txt']);

    const config = listings.get('/config')!;

    listings.set(
        '/config',
        config.filter((file) => file.attributes.name !== 'b.txt')
    );
    try {
        fireEvent.click(screen.getByRole('button', { name: 'Refresh' }));
        await waitFor(() => expect(state().selection).toEqual(['a.zip']));
    } finally {
        listings.set('/config', config);
    }
});

it('re-renders no file row when the selection changes', async () => {
    open();
    const row = (await screen.findByRole('link', { name: 'b.txt' })).parentElement!;
    const before = new Map(rowRenders);

    fireEvent.click(within(row).getByRole('checkbox'));

    expect(await screen.findByRole('button', { name: 'Archive' })).toBeVisible();
    expect(within(row).getByRole('checkbox')).toHaveAttribute('aria-checked', 'true');
    expect(rowRenders).toEqual(before);
});

it('removes a file deleted from its row menu from the selection', async () => {
    open();
    const row = (await screen.findByRole('link', { name: 'b.txt' })).parentElement!;

    fireEvent.click(within(row).getByRole('checkbox'));
    expect(await screen.findByRole('button', { name: 'Archive' })).toBeVisible();

    fireEvent.click(within(row).getByRole('button', { name: 'Open file options' }));
    fireEvent.click(await screen.findByRole('menuitem', { name: /Delete/ }));
    const dialog = await screen.findByRole('dialog');

    fireEvent.click(within(dialog).getByRole('button', { name: 'Delete' }));

    await waitFor(() => expect(deletes()).toHaveLength(1));
    expect(JSON.parse(String(deletes()[0].body))).toEqual({ root: '/config', files: ['b.txt'] });
    await waitFor(() => expect(screen.queryByRole('button', { name: 'Archive' })).not.toBeInTheDocument());
    expect(screen.queryByRole('link', { name: 'b.txt' })).not.toBeInTheDocument();
});
