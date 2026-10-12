import { ExtensionContext } from '@/extensions/context';
import '@/components/server/files/FileDetailsView';
import '@/components/server/files/FileEditorView';
import '@/components/server/files/FileManagerView';
import type { FileManagerModel } from '@/views/files';
import { registerClassicTheme } from '@/views/classic';
import '@/views/account';
import '@/views/activity';
import '@/views/auth';
import '@/views/dashboard';
import '@/views/layouts';
import '@/views/server/backups';
import '@/views/server/conflict';
import '@/views/server/schedules';
import '@/views/server/settings';
import '@/views/server/users';
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
import { uiPrimitives, type UiPrimitiveName, type UiPrimitiveProps } from '@/themes/gallery';
import { createViewHarness, LooseView, preloadView, ViewFixtureProvider } from '@/views/View';
import {
    act,
    createContext,
    createElement,
    useContext,
    useLayoutEffect,
    useMemo,
    type ComponentType,
    type JSXElementConstructor,
    type ReactNode,
} from 'react';
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
export type { UiPrimitiveName, UiPrimitiveProps };

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

const NoSession = ({ children }: { model: unknown; children?: ReactNode }) => children;

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
    const definition = getViewDefinition(view);
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
    const input = options.input === undefined ? (definition?.fixtureInput?.(model) ?? {}) : Object(options.input);
    const Session = definition?.fixtureScope ?? NoSession;
    const harness = createViewHarness(name, { model, inputs: input });

    function Wrapper({ children }: { children?: ReactNode }) {
        const context = useMemo(() => ({ extensionId, context: `view "${name}" test host` }), []);

        return (
            <host.Wrapper>
                <ExtensionContext.Provider value={context}>
                    <ViewFixtureProvider>
                        <Session model={model}>
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

/** Replaces whatever was rendered before with `content`, under a theme's skins, views and icons. */
function mount(content: ReactNode, theme: ThemeDefinition | undefined): RenderedView {
    rendered?.unmount();
    clearThemeLayers();
    clearThemeSkins();
    if (theme) {
        registerSkins('theme', theme.ui ?? {});
        registerViewLayer('theme', 'theme', theme.views ?? {});
        registerThemeIcons(theme.icons ?? {});
    }

    const container = document.body.appendChild(document.createElement('div'));
    const root = createRoot(container);

    Reflect.set(globalThis, 'IS_REACT_ACT_ENVIRONMENT', true);
    act(() =>
        root.render(
            <QueryClientProvider client={queryClient}>
                <RouterContextProvider router={viewRouter}>{content}</RouterContextProvider>
            </QueryClientProvider>
        )
    );
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

/**
 * Renders a view synchronously from fixtures, with a theme's layers over classic when given. Each call
 * replaces what was rendered before it; nested views read their fixtures too.
 */
export function renderView<N extends ViewName>(name: N, options: RenderViewOptions<N> = {}): RenderedView {
    // The model is the one of the view being rendered.
    const models = options.model === undefined ? undefined : ({ [name]: options.model } as Partial<ViewModels>);
    const fixtures = options.fixture === undefined ? undefined : { [name]: options.fixture };

    return mount(
        <ViewFixtureProvider fixtures={fixtures} models={models}>
            <LooseView name={name} {...Object(options.input ?? {})} />
        </ViewFixtureProvider>,
        options.theme
    );
}

export interface RenderPrimitiveOptions {
    /** The theme to render the primitive with: what its entry exports. */
    theme?: ThemeDefinition;
}

/**
 * Renders a UI kit primitive synchronously with the theme's skins and replacements over classic's.
 * Each call replaces what was rendered before it.
 */
export function renderPrimitive<N extends UiPrimitiveName>(
    name: N,
    props: UiPrimitiveProps<N>,
    options?: RenderPrimitiveOptions
): RenderedView;
export function renderPrimitive(
    name: UiPrimitiveName,
    props: UiPrimitiveProps<UiPrimitiveName>,
    options: RenderPrimitiveOptions = {}
): RenderedView {
    // The overload ties the props to the named primitive.
    const primitive = uiPrimitives[name].component as JSXElementConstructor<UiPrimitiveProps<UiPrimitiveName>>;

    return mount(createElement(primitive, props), options.theme);
}
