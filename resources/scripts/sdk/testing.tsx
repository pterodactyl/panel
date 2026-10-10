import { ExtensionContext } from '@/extensions/context';
import { DefaultServerCard, ServerCardContext, serverCardParts } from '@/components/dashboard/ServerCardView';
import { DefaultFileDetails, FileDetailsContext, fileDetailsParts } from '@/components/server/files/FileDetailsView';
import { DefaultFileEditor, FileEditorContext, fileEditorParts } from '@/components/server/files/FileEditorView';
import { DefaultFileManager, FileManagerContext, fileManagerParts } from '@/components/server/files/FileManagerView';
import type {
    ComponentModels,
    ComponentName,
    ReplacementProps,
    ServerCardModel,
    FileDetailsModel,
    FileEditorModel,
    FileManagerEntry,
    FileManagerModel,
} from '@/extensions/componentTypes';
import { createContext, useContext, useLayoutEffect, useMemo, type ComponentType, type ReactNode } from 'react';
import { QueryClientProvider, type QueryClient } from '@tanstack/react-query';
import {
    createMemoryHistory,
    createRootRoute,
    createRoute,
    createRouter,
    Outlet,
    RouterProvider,
} from '@tanstack/react-router';
import { queryClient } from '@/api/queryClient';
import { currentUserQueryKey } from '@/api/account/queries';
import { siteSettingsQueryKey } from '@/api/settings/queries';
import { serverQueryOptions } from '@/api/server/queries';
import { ServerContext } from '@/state/server/context';
import { useServerStore } from '@/state/server';
import { Websocket } from '@/plugins/Websocket';
import ExtensionMount from '@/extensions/ExtensionMount';
import { registerClassPrefixes } from '@/lib/cn';
import { ExtensionResourceProvider } from '@/extensions/resourceContext';
import { SCREEN_ROOTS } from '@/extensions/registry';
import type { ExtensionResourceContext } from '@/extensions/resourceContext';
import type { HistoryState } from '@tanstack/react-router';
import { resolvePanelDestination, type PanelDestination, type PanelSearch } from './navigation';
import type { SdkServer, SdkSiteSettings, SdkUser } from './index';
import type { ServerWebsocketEvent } from './index';

export interface ExtensionTestHostOptions {
    extensionId?: string;
    /** The extension's `ui.prefix`, so its prefixed classes merge with a core component's as they do in the panel. */
    prefix?: string;
    path?: string;
    user?: SdkUser;
    siteSettings?: SdkSiteSettings;
    server?: SdkServer;
    resource?: ExtensionResourceContext;
}
export interface ExtensionTestLocation {
    pathname: string;
    search: PanelSearch;
    state: HistoryState;
}
export interface ExtensionTestHost {
    Wrapper: ComponentType<{ children?: ReactNode }>;
    queryClient: QueryClient;
    navigate(destination: PanelDestination): Promise<void>;
    location(): ExtensionTestLocation;
    emitWebsocket(event: ServerWebsocketEvent, data: string): void;
    setConnected(connected: boolean): void;
    dispose(): void;
}

export function createTestServer(
    options: {
        identifier?: string;
        uuid?: string;
        name?: string;
        owner?: boolean;
        permissions?: string[];
        status?: SdkServer['attributes']['status'];
        eggFeatures?: string[];
        eggTags?: string[];
    } = {}
): SdkServer {
    const identifier = options.identifier ?? 'test-server';
    const owner = options.owner ?? true;

    return {
        object: 'server',
        attributes: {
            identifier,
            uuid: options.uuid ?? '11111111-1111-4111-8111-111111111111',
            __deprecated_uuid_short: identifier,
            server_identifier: identifier,
            internal_id: 1,
            server_owner: owner,
            name: options.name ?? 'Test server',
            node: 'Test node',
            is_node_under_maintenance: false,
            sftp_details: { ip: '127.0.0.1', port: 2022 },
            description: null,
            limits: { memory: 1024, swap: 0, disk: 1024, io: 500, cpu: 100, threads: null, oom_disabled: true },
            invocation: 'run',
            docker_image: 'test/image',
            egg_features: options.eggFeatures ?? [],
            egg_tags: options.eggTags ?? [],
            feature_limits: { databases: 1, allocations: 1, backups: 1 },
            status: options.status ?? null,
            is_suspended: options.status === 'suspended',
            is_installing: options.status === 'installing',
            is_transferring: false,
            skip_scripts: false,
        },
        meta: { is_server_owner: owner, user_permissions: options.permissions ?? [] },
    };
}

/** Create one host per test; call dispose after unmounting its Wrapper. */
export function createExtensionTestHost(options: ExtensionTestHostOptions = {}): ExtensionTestHost {
    registerClassPrefixes([options.prefix]);
    queryClient.clear();
    queryClient.setQueryData(
        currentUserQueryKey,
        options.user ?? {
            uuid: 'test-user',
            username: 'Alex',
            email: 'alex@example.test',
            language: 'en',
            rootAdmin: false,
            useTotp: false,
            createdAt: new Date(0),
            updatedAt: new Date(0),
        }
    );
    queryClient.setQueryData(
        siteSettingsQueryKey,
        options.siteSettings ?? {
            name: 'Test panel',
            logo: null,
            locale: 'en',
            recaptcha: { enabled: false, siteKey: '' },
        }
    );
    if (options.server) {
        queryClient.setQueryData(serverQueryOptions(options.server.attributes.identifier).queryKey, options.server);
    }

    const socket = new Websocket();
    let updateConnected: (connected: boolean) => void = () => {
        throw new Error('Mount the test host before changing its connection state.');
    };

    function SocketFixture() {
        const setInstance = useServerStore((state) => state.socket.setInstance);
        const setConnected = useServerStore((state) => state.socket.setConnectionState);

        useLayoutEffect(() => {
            updateConnected = setConnected;
            setInstance(socket);
            setConnected(true);

            return () => {
                setConnected(false);
                setInstance(null);
                socket.removeAllListeners();
            };
        }, [setInstance, setConnected]);

        return null;
    }

    const Content = createContext<ReactNode>(null);

    function RenderContent() {
        return useContext(Content);
    }

    function Root() {
        const content = (
            <ExtensionMount extensionId={options.extensionId ?? 'test-extension'} context='test host' resetKey='test'>
                <ExtensionResourceProvider.Provider value={options.resource ?? null}>
                    <Outlet />
                </ExtensionResourceProvider.Provider>
            </ExtensionMount>
        );

        return options.server ? (
            <ServerContext.Provider>
                <SocketFixture />
                {content}
            </ServerContext.Provider>
        ) : (
            content
        );
    }

    const root = createRootRoute({ component: Root });
    const authenticated = createRoute({ getParentRoute: () => root, id: 'authenticated', component: Outlet });
    const routes = Object.values(SCREEN_ROOTS).map((path) => {
        const parent = createRoute({ getParentRoute: () => authenticated, path, component: Outlet });

        return parent.addChildren([
            createRoute({ getParentRoute: () => parent, path: '/', component: RenderContent }),
            createRoute({ getParentRoute: () => parent, path: '$', component: RenderContent }),
        ]);
    });
    const router = createRouter({
        routeTree: root.addChildren([
            authenticated.addChildren(routes),
            createRoute({ getParentRoute: () => root, path: '$', component: RenderContent }),
        ]),
        history: createMemoryHistory({
            initialEntries: [
                options.path ?? (options.server ? `/server/${options.server.attributes.identifier}` : '/account'),
            ],
        }),
    });

    function Wrapper({ children }: { children?: ReactNode }) {
        return (
            <QueryClientProvider client={queryClient}>
                <Content.Provider value={children}>
                    <RouterProvider router={router} />
                </Content.Provider>
            </QueryClientProvider>
        );
    }

    return {
        Wrapper,
        queryClient,
        navigate: async (destination) => {
            const { pathname, ...navigation } = resolvePanelDestination(destination);

            await router.navigate({ to: pathname as never, ...navigation, search: navigation.search as never });
        },
        location: () => {
            const { pathname, search, state } = router.state.location;

            return { pathname, search: { ...(search as PanelSearch) }, state: { ...state } };
        },
        emitWebsocket: (event, data) => {
            socket.emit(event, data);
        },
        setConnected: (connected) => updateConnected(connected),
        dispose: () => {
            socket.removeAllListeners();
            socket.close();
            queryClient.clear();
        },
    };
}

export function createTestServerCard(options: Partial<ServerCardModel> = {}): ServerCardModel {
    return {
        identifier: 'test-server',
        uuid: '11111111-1111-4111-8111-111111111111',
        name: 'Test server',
        description: null,
        address: '127.0.0.1:25565',
        state: {
            kind: 'ready',
            power: 'running',
            cpu: { value: 12, limit: 100, alarm: false },
            memory: { value: 256 * 1024 * 1024, limit: 1024 * 1024 * 1024, alarm: false },
            disk: { value: 512 * 1024 * 1024, limit: 2048 * 1024 * 1024, alarm: false },
        },
        ...options,
    };
}

export function createTestFileDetails(options: Partial<FileDetailsModel> = {}): FileDetailsModel {
    return { name: 'config.json', kind: 'file', size: 1024, modifiedAt: '2026-01-01T12:00:00Z', ...options };
}

export function createTestFileEditor(options: Partial<FileEditorModel> = {}): FileEditorModel {
    return {
        path: '/config/server.properties',
        name: 'server.properties',
        isNew: false,
        content: 'motd=Hello\n',
        language: 'text/x-properties',
        readOnly: false,
        dirty: false,
        saving: false,
        change: () => {},
        save: async () => true,
        saveAs: async () => true,
        ...options,
    };
}

export function createTestFileManagerEntry(options: Partial<FileManagerEntry> = {}): FileManagerEntry {
    const name = options.name ?? 'config.json';

    return {
        name,
        path: `/${name}`,
        kind: 'file',
        size: 1024,
        mimetype: 'application/json',
        mode: '-rw-r--r--',
        modeBits: '0644',
        modifiedAt: '2026-01-01T12:00:00Z',
        openable: true,
        ...options,
    };
}

export function createTestFileManager(options: Partial<FileManagerModel> = {}): FileManagerModel {
    return {
        directory: '/',
        entries: [
            createTestFileManagerEntry({ name: 'plugins', kind: 'directory', mimetype: 'inode/directory', size: 0 }),
            createTestFileManagerEntry(),
        ],
        truncated: false,
        loading: false,
        refreshing: false,
        selection: [],
        permissions: { create: true, update: true, delete: true, archive: true },
        actions: {
            open: async () => {},
            navigate: async () => {},
            newFile: async () => {},
            select: () => {},
            refresh: async () => {},
            createDirectory: async () => {},
            rename: async () => {},
            remove: async () => {},
            copy: async () => {},
            archive: async () => {},
            extract: async () => {},
            chmod: async () => {},
            download: async () => {},
            upload: async () => {},
        },
        ...options,
    };
}

export interface ComponentTestHost<TName extends ComponentName> {
    Wrapper: ComponentType<{ children?: ReactNode }>;
    props: ReplacementProps<TName>;
    /** Call after unmounting the Wrapper. */
    dispose(): void;
}
const noDispose = () => {};

/** Test presentation against the native default and parts without issuing core queries. */
export function createComponentTestHost<TName extends ComponentName>(
    name: TName,
    options: { model: ComponentModels[TName]; extensionId?: string; prefix?: string }
): ComponentTestHost<TName>;
export function createComponentTestHost(
    name: ComponentName,
    options: { model: ComponentModels[ComponentName]; extensionId?: string; prefix?: string }
): { [TName in ComponentName]: ComponentTestHost<TName> }[ComponentName] {
    const extensionId = options.extensionId ?? 'test-extension';

    registerClassPrefixes([options.prefix]);
    function Context({ children }: { children?: ReactNode }) {
        const context = useMemo(() => ({ extensionId, context: `component "${name}" test host` }), []);

        return <ExtensionContext.Provider value={context}>{children}</ExtensionContext.Provider>;
    }

    if (name === 'dashboard.serverCard') {
        const model = options.model as ServerCardModel;

        function Wrapper({ children }: { children?: ReactNode }) {
            const context = useMemo(
                () => ({
                    model,
                    server: createTestServer({ identifier: model.identifier, uuid: model.uuid, name: model.name }),
                }),
                []
            );

            return (
                <Context>
                    <ServerCardContext.Provider value={context}>{children}</ServerCardContext.Provider>
                </Context>
            );
        }

        return { Wrapper, props: { model, Default: DefaultServerCard, parts: serverCardParts }, dispose: noDispose };
    }

    if (name === 'server.files.editor') {
        const model = options.model as FileEditorModel;

        function Wrapper({ children }: { children?: ReactNode }) {
            const session = useMemo(() => ({ model, read: () => model.content, setLanguage: () => {} }), []);

            return (
                <Context>
                    <FileEditorContext.Provider value={session}>{children}</FileEditorContext.Provider>
                </Context>
            );
        }

        return { Wrapper, props: { model, Default: DefaultFileEditor, parts: fileEditorParts }, dispose: noDispose };
    }

    if (name === 'server.files.manager') {
        const model = options.model as FileManagerModel;
        const host = createExtensionTestHost({
            extensionId,
            server: createTestServer(),
            path: `/server/test-server/files#${model.directory}`,
        });
        const files = model.entries.map((entry) => ({
            object: 'file_object',
            attributes: {
                name: entry.name,
                mode: entry.mode,
                mode_bits: entry.modeBits,
                size: entry.size,
                is_file: entry.kind !== 'directory',
                is_symlink: entry.kind === 'symlink',
                mimetype: entry.mimetype,
                created_at: entry.modifiedAt,
                modified_at: entry.modifiedAt,
            },
        }));
        const portal = document.getElementById('modal-portal')
            ? null
            : document.body.appendChild(Object.assign(document.createElement('div'), { id: 'modal-portal' }));

        function Selection() {
            const setSelectedFiles = useServerStore((state) => state.files.setSelectedFiles);

            useLayoutEffect(() => {
                setSelectedFiles({ directory: model.directory, files: [...model.selection] });
            }, [setSelectedFiles]);

            return null;
        }

        function Wrapper({ children }: { children?: ReactNode }) {
            const session = useMemo(() => ({ model, files }), []);

            return (
                <host.Wrapper>
                    <Context>
                        <Selection />
                        <FileManagerContext.Provider value={session}>{children}</FileManagerContext.Provider>
                    </Context>
                </host.Wrapper>
            );
        }

        return {
            Wrapper,
            props: { model, Default: DefaultFileManager, parts: fileManagerParts },
            dispose: () => {
                portal?.remove();
                host.dispose();
            },
        };
    }

    const model = options.model as FileDetailsModel;

    function Wrapper({ children }: { children?: ReactNode }) {
        return (
            <Context>
                <FileDetailsContext.Provider value={model}>{children}</FileDetailsContext.Provider>
            </Context>
        );
    }

    return { Wrapper, props: { model, Default: DefaultFileDetails, parts: fileDetailsParts }, dispose: noDispose };
}
