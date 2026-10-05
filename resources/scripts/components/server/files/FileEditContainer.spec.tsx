/** @vitest-environment jsdom */
import { cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { QueryClientProvider } from '@tanstack/react-query';
import {
    createMemoryHistory,
    createRootRoute,
    createRoute,
    createRouter,
    Outlet,
    RouterProvider,
    useLocation,
} from '@tanstack/react-router';
import { useState } from 'react';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import type { AxiosAdapter } from 'axios';
import type { ComponentPartProps, ComponentReplacement, ReplacementProps } from '@/extensions/componentTypes';
import type * as Registry from '@/extensions/registry';
import type * as Testing from '@/sdk/testing';
import type * as QueryClientModule from '@/api/queryClient';
import type * as Http from '@/api/http';
import type * as ServerQueries from '@/api/server/queries';
import type FileEditContainer from './FileEditContainer';
import { readNewFileDraft } from '@/lib/fileDrafts';

const serverUuid = '11111111-1111-4111-8111-111111111111';

vi.mock('@/components/elements/LazyCodemirrorEditor', () => ({
    default: (props: { initialContent?: string; onContentChanged?: (content: string) => void }) => (
        <textarea
            aria-label='Native buffer'
            defaultValue={props.initialContent}
            onChange={(event) => props.onContentChanged?.(event.target.value)}
        />
    ),
}));

interface Loaded {
    registry: typeof Registry;
    testing: typeof Testing;
    queryClient: typeof QueryClientModule.queryClient;
    http: typeof Http.default;
    serverQueryOptions: typeof ServerQueries.serverQueryOptions;
    Container: typeof FileEditContainer;
}
let loaded: Loaded;
let requests: { method: string; path: string; file: string | null; body: unknown }[];
let originalAdapter: Loaded['http']['defaults']['adapter'];
beforeEach(async () => {
    vi.resetModules();
    const [registry, testing, client, http, queries, container] = await Promise.all([
        import('@/extensions/registry'),
        import('@/sdk/testing'),
        import('@/api/queryClient'),
        import('@/api/http'),
        import('@/api/server/queries'),
        import('./FileEditContainer'),
    ]);
    loaded = {
        registry,
        testing,
        queryClient: client.queryClient,
        http: http.default,
        serverQueryOptions: queries.serverQueryOptions,
        Container: container.default,
    };
    requests = [];
    originalAdapter = loaded.http.defaults.adapter;
    const adapter: AxiosAdapter = async (config) => {
        const url = new URL(config.url!, 'https://panel.test');
        const file = url.searchParams.get('file') ?? (config.params as { file?: string } | undefined)?.file ?? null;
        requests.push({ method: config.method!, path: url.pathname, file, body: config.data });
        const data = url.pathname.endsWith('/files/contents') ? `loaded:${file}` : '';
        return { data, config, status: 200, statusText: 'OK', headers: {} };
    };
    loaded.http.defaults.adapter = adapter;
    vi.spyOn(console, 'error').mockImplementation(() => {});
    sessionStorage.clear();
}, 20000);
afterEach(() => {
    cleanup();
    loaded.http.defaults.adapter = originalAdapter;
    loaded.queryClient.clear();
    vi.restoreAllMocks();
});

function replace(replacement: ComponentReplacement<'server.files.editor'>) {
    const { registry } = loaded;
    registry.prepareExtensions([{ id: 'editor', entry: '/editor.js', components: ['server.files.editor'] }]);
    const batch = registry.createExtensionRegistryBatch();
    registry.registerComponentReplacement('editor', 'server.files.editor', replacement, batch);
    registry.commitExtensionRegistryBatch('editor', batch);
}
function Custom({ model }: ReplacementProps<'server.files.editor'>) {
    const [result, setResult] = useState('');
    const { path, name, isNew, language, readOnly, dirty } = model;
    return (
        <div>
            <output>{JSON.stringify({ path, name, isNew, language, readOnly, dirty })}</output>
            <textarea
                aria-label='Buffer'
                defaultValue={model.content}
                onChange={(event) => model.change(event.target.value)}
            />
            <button onClick={() => void model.save().then((saved) => setResult(`save:${saved}`))}>Custom save</button>
            <button onClick={() => void model.saveAs('copy.yml').then((saved) => setResult(`saveAs:${saved}`))}>
                Custom save as
            </button>
            <span>{result}</span>
        </div>
    );
}
function Where() {
    const { pathname, hash } = useLocation();
    return <p aria-label='Location'>{`${pathname}#${hash}`}</p>;
}
function open(path: string, permissions?: string[]) {
    const { queryClient, testing, serverQueryOptions, Container } = loaded;
    queryClient.setQueryData(
        serverQueryOptions('test-server').queryKey,
        testing.createTestServer(permissions ? { owner: false, permissions } : {})
    );
    const root = createRootRoute({
        component: () => (
            <>
                <Where />
                <Outlet />
            </>
        ),
    });
    const authenticated = createRoute({ getParentRoute: () => root, id: 'authenticated', component: Outlet });
    const server = createRoute({ getParentRoute: () => authenticated, path: '/server/$id', component: Outlet });
    const router = createRouter({
        routeTree: root.addChildren([
            authenticated.addChildren([
                server.addChildren([
                    createRoute({ getParentRoute: () => server, path: 'files/$action', component: Container }),
                    createRoute({ getParentRoute: () => server, path: 'files', component: () => <p>File list</p> }),
                ]),
            ]),
        ]),
        history: createMemoryHistory({ initialEntries: [path] }),
    });
    render(
        <QueryClientProvider client={queryClient}>
            <RouterProvider router={router} />
        </QueryClientProvider>
    );
    return router;
}
const state = () => JSON.parse(screen.getByRole('status').textContent ?? '{}');
const writes = () => requests.filter((request) => request.path.endsWith('/files/write'));

it('opens the loaded document in a replacement and saves the reported buffer through core without reopening it', async () => {
    replace(Custom);
    open('/server/test-server/files/edit#/config/app.yml');

    expect(await screen.findByLabelText('Buffer')).toHaveValue('loaded:/config/app.yml');
    expect(state()).toEqual({
        path: '/config/app.yml',
        name: 'app.yml',
        isNew: false,
        language: 'text/x-yaml',
        readOnly: false,
        dirty: false,
    });

    fireEvent.change(screen.getByLabelText('Buffer'), { target: { value: 'edited' } });
    expect(state().dirty).toBe(true);
    fireEvent.click(screen.getByRole('button', { name: 'Custom save' }));

    expect(await screen.findByText('save:true')).toBeVisible();
    expect(writes()).toEqual([expect.objectContaining({ method: 'post', file: '/config/app.yml', body: 'edited' })]);
    expect(state().dirty).toBe(false);
    expect(screen.getByLabelText('Buffer')).toHaveValue('edited');
});

it('opens a previously cached document with freshly fetched content', async () => {
    const { serverFileContentQueryOptions } = await import('@/api/server/files/queries');
    loaded.queryClient.setQueryData(
        serverFileContentQueryOptions(serverUuid, '/config/app.yml').queryKey,
        'cached a moment ago'
    );
    replace(Custom);
    open('/server/test-server/files/edit#/config/app.yml');

    expect(await screen.findByLabelText('Buffer')).toHaveValue('loaded:/config/app.yml');
});

it('keeps the document read-only and unsaved without the update permission', async () => {
    replace(Custom);
    open('/server/test-server/files/edit#/config/app.yml', ['file.read', 'file.read-content']);

    fireEvent.change(await screen.findByLabelText('Buffer'), { target: { value: 'edited' } });
    fireEvent.click(screen.getByRole('button', { name: 'Custom save' }));
    expect(await screen.findByText('save:false')).toBeVisible();
    fireEvent.click(screen.getByRole('button', { name: 'Custom save as' }));
    expect(await screen.findByText('saveAs:false')).toBeVisible();

    expect(state()).toMatchObject({ readOnly: true, dirty: false });
    expect(writes()).toEqual([]);
});

it('asks before navigation discards unsaved changes', async () => {
    replace(Custom);
    const router = open('/server/test-server/files/edit#/config/app.yml');
    const confirm = vi.spyOn(window, 'confirm').mockReturnValue(false);

    fireEvent.change(await screen.findByLabelText('Buffer'), { target: { value: 'edited' } });
    void router.navigate({ to: '/server/$id/files', params: { id: 'test-server' } });
    await waitFor(() => expect(confirm).toHaveBeenCalledTimes(1));
    expect(screen.getByLabelText('Buffer')).toHaveValue('edited');

    confirm.mockReturnValue(true);
    void router.navigate({ to: '/server/$id/files', params: { id: 'test-server' } });
    expect(await screen.findByText('File list')).toBeVisible();
});

it('writes a copy beside the file and opens it without asking', async () => {
    replace(Custom);
    open('/server/test-server/files/edit#/config/app.yml');
    const confirm = vi.spyOn(window, 'confirm').mockReturnValue(false);

    fireEvent.change(await screen.findByLabelText('Buffer'), { target: { value: 'edited' } });
    fireEvent.click(screen.getByRole('button', { name: 'Custom save as' }));

    await waitFor(() =>
        expect(screen.getByLabelText('Location')).toHaveTextContent('/server/test-server/files/edit#/config/copy.yml')
    );
    expect(writes()).toEqual([expect.objectContaining({ file: '/config/copy.yml', body: 'edited' })]);
    expect(confirm).not.toHaveBeenCalled();
    await waitFor(() => expect(state()).toMatchObject({ path: '/config/copy.yml', dirty: false }));
});

it('names a new file in the core dialog before creating it', async () => {
    replace(Custom);
    open('/server/test-server/files/new#/config');

    expect(await screen.findByLabelText('Buffer')).toHaveValue('');
    expect(state()).toMatchObject({ path: '/config', name: '', isNew: true });
    fireEvent.change(screen.getByLabelText('Buffer'), { target: { value: 'fresh' } });
    fireEvent.click(screen.getByRole('button', { name: 'Custom save' }));

    fireEvent.change(await screen.findByLabelText('File Name'), { target: { value: 'new.txt' } });
    fireEvent.click(screen.getByRole('button', { name: 'Create File' }));

    await waitFor(() =>
        expect(screen.getByLabelText('Location')).toHaveTextContent('/server/test-server/files/edit#/config/new.txt')
    );
    expect(writes()).toEqual([expect.objectContaining({ file: '/config/new.txt', body: 'fresh' })]);
    expect(readNewFileDraft(serverUuid, '/config')).toBe('');
});

it('keeps an unsaved new file draft typed just before leaving', async () => {
    replace(Custom);
    const router = open('/server/test-server/files/new#/config');

    fireEvent.change(await screen.findByLabelText('Buffer'), { target: { value: 'draft' } });
    void router.navigate({ to: '/server/$id/files', params: { id: 'test-server' } });

    expect(await screen.findByText('File list')).toBeVisible();
    expect(readNewFileDraft(serverUuid, '/config')).toBe('draft');
});

it('saves a replaced editor part with the native action', async () => {
    function Buffer({ model }: ComponentPartProps<'server.files.editor'>) {
        return <textarea aria-label='Part buffer' onChange={(event) => model.change(event.target.value)} />;
    }
    replace(({ Default }) => <Default parts={{ editor: Buffer }} />);
    open('/server/test-server/files/edit#/config/app.yml');

    fireEvent.change(await screen.findByLabelText('Part buffer'), { target: { value: 'from part' } });
    fireEvent.click(screen.getByRole('button', { name: 'Save Content' }));

    await waitFor(() => expect(writes()).toEqual([expect.objectContaining({ body: 'from part' })]));
});

it('keeps the page and the buffer when the replacement fails to render', async () => {
    function Unstable(props: ReplacementProps<'server.files.editor'>) {
        if (props.model.dirty) throw new Error('editor crashed');
        return <Custom {...props} />;
    }
    replace(Unstable);
    open('/server/test-server/files/edit#/config/app.yml');

    fireEvent.change(await screen.findByLabelText('Buffer'), { target: { value: 'kept' } });
    expect(await screen.findByLabelText('Native buffer')).toHaveValue('kept');
    fireEvent.click(screen.getByRole('button', { name: 'Save Content' }));

    await waitFor(() => expect(writes()).toEqual([expect.objectContaining({ body: 'kept' })]));
    expect(loaded.registry.getExtensionStates()[0].error).toContain('editor crashed');
});
