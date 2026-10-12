import { ExtensionContext } from '@/extensions/context';
import '@/components/server/files/FileDetailsView';
import { FileEditorContext } from '@/components/server/files/FileEditorView';
import { FileManagerContext } from '@/components/server/files/FileManagerView';
import type { FileObject } from '@/api/server/files/queries';
import type { FileDetailsModel, FileEditorModel, FileManagerModel } from '@/views/files';
import type { ServerCardModel } from '@/views/dashboard';
import { registerClassicTheme } from '@/views/classic';
import '@/views/dashboard';
import '@/views/layouts';
import {
    clearThemeLayers,
    getViewDefinition,
    registerViewLayer,
    type ViewInput,
    type ViewModel,
    type ViewModels,
    type ViewName,
    type ViewProps,
} from '@/views/registry';
import { registerThemeIcons, type ThemeDefinition } from '@/views/theme';
import { clearThemeSkins, registerSkins } from '@/themes/skins';
import classic from '@/themes/classic';
import { createViewHarness, LooseView, preloadView, ViewFixtureProvider } from '@/views/View';
import { act, createContext, useContext, useLayoutEffect, useMemo, type ComponentType, type ReactNode } from 'react';
import { createRoot } from 'react-dom/client';
import { QueryClientProvider, type QueryClient } from '@tanstack/react-query';
import {
    createMemoryHistory,
    createRootRoute,
    createRoute,
    createRouter,
    Outlet,
    RouterContextProvider,
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
import { createTestServer } from '@/views/fixtures';
import type { SdkServer, SdkSiteSettings, SdkUser } from './index';
import type { ServerWebsocketEvent } from './index';

export {
    createTestFileDetails,
    createTestFileEditor,
    createTestFileManager,
    createTestFileManagerEntry,
    createTestServer,
    createTestServerCard,
    type TestServerOptions,
} from '@/views/fixtures';

registerClassicTheme();
// Classic's own views are lazy; loading them here lets a test render them synchronously.
await Promise.all(Object.keys(classic.views ?? {}).map(preloadView));

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

export interface ViewTestHostOptions<N extends ViewName> {
    /** Defaults to the named fixture, or the view's first. */
    model?: ViewModel<N>;
    fixture?: string;
    /** Defaults to inputs matching the model where the view takes any. */
    input?: ViewInput<N>;
    extensionId?: string;
    /** The extension's `ui.prefix`, so its prefixed classes merge with the view's as they do in the panel. */
    prefix?: string;
}
export interface ViewTestHost<N extends ViewName> {
    Wrapper: ComponentType<{ children?: ReactNode }>;
    /** What a layer above classic receives, `Default` included. */
    props: ViewProps<N>;
    /** Call after unmounting the Wrapper. */
    dispose(): void;
}

function fixtureModel(name: ViewName, fixture: string | undefined) {
    const fixtures = new Map(Object.entries(getViewDefinition(name)?.fixtures ?? {}));
    const factory = fixture === undefined ? [...fixtures.values()][0] : fixtures.get(fixture);

    if (!factory) {
        throw new Error(`View "${name}" has no fixture${fixture === undefined ? 's' : ` "${fixture}"`}.`);
    }

    return factory();
}

function fileObject(model: FileDetailsModel): FileObject {
    const directory = model.kind === 'directory';

    return {
        object: 'file_object',
        attributes: {
            name: model.name,
            mode: directory ? 'drwxr-xr-x' : '-rw-r--r--',
            mode_bits: directory ? '0755' : '0644',
            size: model.size,
            is_file: !directory,
            is_symlink: model.kind === 'symlink',
            mimetype: directory ? 'inode/directory' : 'text/plain',
            created_at: model.modifiedAt,
            modified_at: model.modifiedAt,
        },
    };
}

type Session = ComponentType<{ children?: ReactNode }>;
const NoSession: Session = ({ children }) => children;

function editorSession(model: FileEditorModel): Session {
    return function EditorSession({ children }) {
        const value = useMemo(() => ({ model, read: () => model.content, setLanguage: () => {} }), []);

        return <FileEditorContext.Provider value={value}>{children}</FileEditorContext.Provider>;
    };
}

function managerSession(model: FileManagerModel): Session {
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

    return function ManagerSession({ children }) {
        const setSelectedFiles = useServerStore((state) => state.files.setSelectedFiles);
        const value = useMemo(() => ({ model, files }), []);

        useLayoutEffect(() => {
            setSelectedFiles({ directory: model.directory, files: [...model.selection] });
        }, [setSelectedFiles]);

        return <FileManagerContext.Provider value={value}>{children}</FileManagerContext.Provider>;
    };
}

/**
 * Tests a layer against the view's classic layers without issuing core queries: render it with `props`
 * inside `Wrapper`, and its `Default` renders classic with the same model. Nested views read fixtures.
 */
export async function createViewTestHost<N extends ViewName>(
    name: N,
    options: ViewTestHostOptions<N> = {}
): Promise<ViewTestHost<N>> {
    registerClassPrefixes([options.prefix]);
    registerClassicTheme();
    await preloadView(name);

    const view: ViewName = name;
    const extensionId = options.extensionId ?? 'test-extension';
    const model = options.model ?? fixtureModel(view, options.fixture);
    const manager = view === 'server.files.manager' ? (model as FileManagerModel) : undefined;
    const host = createExtensionTestHost({
        extensionId,
        server: createTestServer(),
        path: manager ? `/server/test-server/files#${manager.directory}` : undefined,
    });
    const portal =
        manager && !document.getElementById('modal-portal')
            ? document.body.appendChild(Object.assign(document.createElement('div'), { id: 'modal-portal' }))
            : null;
    const derivedInput = () => {
        if (view === 'dashboard.serverCard') {
            const card = model as ServerCardModel;

            return { server: createTestServer({ identifier: card.identifier, uuid: card.uuid, name: card.name }) };
        }

        return view === 'server.files.details' ? { file: fileObject(model as FileDetailsModel) } : {};
    };

    const session = (): Session => {
        if (view === 'server.files.editor') {
            return editorSession(model as FileEditorModel);
        }

        return manager ? managerSession(manager) : NoSession;
    };

    const input = options.input === undefined ? derivedInput() : Object(options.input);
    const Session = session();

    const harness = createViewHarness(name, { model, inputs: input });

    function Wrapper({ children }: { children?: ReactNode }) {
        const context = useMemo(() => ({ extensionId, context: `view "${name}" test host` }), []);

        return (
            <host.Wrapper>
                <ExtensionContext.Provider value={context}>
                    <ViewFixtureProvider>
                        <Session>
                            <harness.Scope>{children}</harness.Scope>
                        </Session>
                    </ViewFixtureProvider>
                </ExtensionContext.Provider>
            </host.Wrapper>
        );
    }

    return {
        Wrapper,
        props: harness.props,
        dispose: () => {
            portal?.remove();
            host.dispose();
        },
    };
}

export interface RenderViewOptions<N extends ViewName> {
    /** Defaults to the view's first fixture. */
    fixture?: string;
    model?: ViewModel<N>;
    input?: ViewInput<N>;
    /** The theme to render the view with: what its entry exports. */
    theme?: ThemeDefinition;
}
export interface RenderedView {
    container: HTMLElement;
    unmount(): void;
}

const viewRouter = createRouter({
    routeTree: createRootRoute(),
    history: createMemoryHistory({ initialEntries: ['/'] }),
});
let rendered: RenderedView | undefined;

/**
 * Renders a view synchronously from fixtures, with a theme's layers over classic when given. Each call
 * replaces the view rendered before it; nested views read their fixtures too.
 */
export function renderView<N extends ViewName>(name: N, options: RenderViewOptions<N> = {}): RenderedView {
    rendered?.unmount();
    clearThemeLayers();
    clearThemeSkins();
    if (options.theme) {
        registerSkins('theme', options.theme.ui ?? {});
        registerViewLayer('theme', 'theme', options.theme.views ?? {});
        registerThemeIcons(options.theme.icons ?? {});
    }

    const container = document.body.appendChild(document.createElement('div'));
    const root = createRoot(container);
    // The model is the one of the view being rendered.
    const models = options.model === undefined ? undefined : ({ [name]: options.model } as Partial<ViewModels>);
    const fixtures = options.fixture === undefined ? undefined : { [name]: options.fixture };
    const view = (
        <QueryClientProvider client={queryClient}>
            <RouterContextProvider router={viewRouter}>
                <ViewFixtureProvider fixtures={fixtures} models={models}>
                    <LooseView name={name} {...Object(options.input ?? {})} />
                </ViewFixtureProvider>
            </RouterContextProvider>
        </QueryClientProvider>
    );

    Reflect.set(globalThis, 'IS_REACT_ACT_ENVIRONMENT', true);
    act(() => root.render(view));
    rendered = {
        container,
        unmount: () => {
            act(() => root.unmount());
            container.remove();
            rendered = undefined;
        },
    };

    return rendered;
}
