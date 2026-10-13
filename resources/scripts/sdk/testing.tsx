import { ExtensionContext } from '@/extensions/context';
import type { FileManagerModel } from '@/views/server/files';
import { registerClassicTheme } from '@/views/classic';
import { loadViewCatalog } from '@/views/catalog';
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
import { isPendingLazyView } from '@/views/lazy';
import {
    createViewHarness,
    LooseView,
    onViewLayerFailure,
    preloadView,
    reportViewLayerFailure,
    ViewFixtureProvider,
    ViewLayerError,
} from '@/views/View';
import {
    act,
    createContext,
    createElement,
    useContext,
    useLayoutEffect,
    useMemo,
    useSyncExternalStore,
    type ComponentProps,
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
import StatsListener from '@/components/server/StatsListener';
import { ServerContext } from '@/state/server/context';
import { useServerStore } from '@/state/server';
import { Websocket } from '@/plugins/Websocket';
import ExtensionMount from '@/extensions/ExtensionMount';
import { registerClassPrefixes } from '@/lib/cn';
import { ExtensionResourceProvider } from '@/extensions/resourceContext';
import {
    mountSlotComponents,
    SCREEN_ROOTS,
    type SlotComponentProps,
    type SlotData,
    type SlotName,
} from '@/extensions/registry';
import type { ExtensionResourceContext } from '@/extensions/resourceContext';
import type { HistoryState } from '@tanstack/react-router';
import { resolvePanelDestination, type PanelDestination, type PanelSearch } from './navigation';
import { createTestServer } from '@/views/fixtures';
import {
    Alert,
    Avatar,
    Badge,
    Button,
    Card,
    ChartCard,
    Checkbox,
    Code,
    ContentBox,
    CopyOnClick,
    Dialog,
    DropdownMenu,
    Empty,
    FileInput,
    Input,
    Label,
    ListRow,
    NewButton,
    NumberInput,
    PageFooter,
    PageHeading,
    Pagination,
    ProgressBar,
    Select,
    Spinner,
    SpinnerOverlay,
    Stat,
    Switch,
    Table,
    TextArea,
    TitledGreyBox,
    Tooltip,
} from './index';
import type { SdkServer, SdkSiteSettings, SdkUser } from './index';
import type { ServerWebsocketEvent } from './index';

export { createTestServer, createTestServerCard, type TestServerOptions } from '@/views/fixtures';
export {
    createTestFileDetails,
    createTestFileEditor,
    createTestFileManager,
    createTestFileManagerEntry,
} from '@/views/server/fileFixtures';
export { ViewLayerError };

/** The component of each UI kit primitive `renderPrimitive` renders, by name. */
export interface UiPrimitiveComponents {
    Button: typeof Button;
    NewButton: typeof NewButton;
    Input: typeof Input;
    TextArea: typeof TextArea;
    NumberInput: typeof NumberInput;
    FileInput: typeof FileInput;
    Select: typeof Select;
    Checkbox: typeof Checkbox;
    Switch: typeof Switch;
    Label: typeof Label;
    Card: typeof Card;
    ListRow: typeof ListRow;
    Stat: typeof Stat;
    ChartCard: typeof ChartCard;
    PageHeading: typeof PageHeading;
    PageFooter: typeof PageFooter;
    Badge: typeof Badge;
    Alert: typeof Alert;
    ProgressBar: typeof ProgressBar;
    Dialog: typeof Dialog;
    Tooltip: typeof Tooltip;
    DropdownMenu: typeof DropdownMenu;
    Table: typeof Table;
    Pagination: typeof Pagination;
    Empty: typeof Empty;
    Spinner: typeof Spinner;
    SpinnerOverlay: typeof SpinnerOverlay;
    Avatar: typeof Avatar;
    Code: typeof Code;
    CopyOnClick: typeof CopyOnClick;
    ContentBox: typeof ContentBox;
    TitledGreyBox: typeof TitledGreyBox;
}

const uiPrimitives: UiPrimitiveComponents = {
    Button,
    NewButton,
    Input,
    TextArea,
    NumberInput,
    FileInput,
    Select,
    Checkbox,
    Switch,
    Label,
    Card,
    ListRow,
    Stat,
    ChartCard,
    PageHeading,
    PageFooter,
    Badge,
    Alert,
    ProgressBar,
    Dialog,
    Tooltip,
    DropdownMenu,
    Table,
    Pagination,
    Empty,
    Spinner,
    SpinnerOverlay,
    Avatar,
    Code,
    CopyOnClick,
    ContentBox,
    TitledGreyBox,
};

export type UiPrimitiveName = keyof UiPrimitiveComponents;
export type UiPrimitiveProps<N extends UiPrimitiveName> = ComponentProps<UiPrimitiveComponents[N]>;

registerClassicTheme();
// Views' hooks and fixtures and classic's own views are lazy; loading them here lets a test render them synchronously.
await Promise.all([loadViewCatalog(), ...Object.keys(classic.views ?? {}).map(preloadView)]);

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

/** Reports what the host's mount caught, which a strict view test host fails on. */
function ReportLayerFailure({ view, layer, error }: { view: string; layer: string; error: Error }) {
    useLayoutEffect(() => reportViewLayerFailure(new ViewLayerError(view, layer, error)), [view, layer, error]);

    return null;
}

/** `failing` names the view a strict view test host renders, whose failures it reports instead of showing a notice. */
function createHost(options: ExtensionTestHostOptions, failing?: string): ExtensionTestHost {
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

    const extensionId = options.extensionId ?? 'test-extension';
    const failure =
        failing === undefined
            ? undefined
            : (error: Error) => <ReportLayerFailure view={failing} layer={extensionId} error={error} />;

    function Root() {
        const content = (
            <ExtensionMount extensionId={extensionId} context='test host' resetKey='test' failure={failure}>
                <ExtensionResourceProvider.Provider value={options.resource ?? null}>
                    <Outlet />
                </ExtensionResourceProvider.Provider>
            </ExtensionMount>
        );

        return options.server ? (
            <ServerContext.Provider>
                <SocketFixture />
                <StatsListener />
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

/** Create one host per test; call dispose after unmounting its Wrapper. */
export function createExtensionTestHost(options: ExtensionTestHostOptions = {}): ExtensionTestHost {
    return createHost(options);
}

/** The theme a test renders with, or a chain of them. */
export interface TestThemeOptions {
    /** The theme to render with: what its entry exports. */
    theme?: ThemeDefinition;
    /**
     * A chain of themes, oldest first, as the panel stacks them over classic: `[parent, child]`. Each
     * one's `Default` and the `below` of its recipes build on the theme before it.
     */
    themes?: readonly ThemeDefinition[];
}

function chainOf({ theme, themes }: TestThemeOptions): readonly ThemeDefinition[] {
    if (theme && themes) {
        throw new Error('Pass `theme` or `themes`, not both.');
    }

    return themes ?? (theme ? [theme] : []);
}

/** How layer failures name a theme of the chain: "theme" for the last, its parents "parent", "parent 2" and so on. */
function layerId(distance: number): string {
    if (distance === 0) {
        return 'theme';
    }

    return distance === 1 ? 'parent' : `parent ${distance}`;
}

/** Replaces the registered theme layers with the chain's skins, views and icons, parent first. */
function registerThemes(themes: readonly ThemeDefinition[]): void {
    clearThemeLayers();
    clearThemeSkins();
    for (const [index, theme] of themes.entries()) {
        const id = layerId(themes.length - 1 - index);

        registerSkins(id, theme.ui ?? {});
        registerViewLayer(id, 'theme', theme.views ?? {});
        registerThemeIcons(theme.icons ?? {});
    }
}

/** Loads the chunks of a theme's `lazyView` views, or those of a chain of themes, which `renderView` renders synchronously. */
export async function preloadTheme(theme: ThemeDefinition | readonly ThemeDefinition[]): Promise<void> {
    const themes: readonly ThemeDefinition[] = 'length' in theme ? theme : [theme];

    await Promise.all(
        themes.flatMap((each) =>
            Object.values(each.views ?? {}).map((view) => (view && isPendingLazyView(view) ? view.preload() : null))
        )
    );
}

export interface ViewTestHostOptions<N extends ViewName> extends TestThemeOptions {
    /** Defaults to the named fixture, or the view's first. */
    model?: ViewModel<N>;
    fixture?: string;
    /** Defaults to inputs matching the model where the view takes any. */
    input?: ViewInput<N>;
    extensionId?: string;
    /** The extension's `ui.prefix`, so its prefixed classes merge with the view's as they do in the panel. */
    prefix?: string;
    /** Fail when the layer or a theme or extension layer of a nested view throws (the default); false lets them fall back. */
    strict?: boolean;
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

interface LayerFailures {
    current(): ViewLayerError | null;
    subscribe(listener: () => void): () => void;
    stop(): void;
}

/** Keeps the first layer failure from now on, for the strict test renderers. */
function collectLayerFailures(): LayerFailures {
    let first: ViewLayerError | null = null;
    const listeners = new Set<() => void>();
    const stop = onViewLayerFailure((failure) => {
        first ??= failure;
        for (const listener of listeners) {
            listener();
        }
    });

    return {
        current: () => first,
        subscribe(listener) {
            listeners.add(listener);

            return () => {
                listeners.delete(listener);
            };
        },
        stop,
    };
}

const noFailures: LayerFailures = { current: () => null, subscribe: () => () => {}, stop: () => {} };

/** Throws the first failure, outside every boundary below it, so the render fails instead of falling back. */
function FailOnLayerError({ failures, children }: { failures: LayerFailures; children?: ReactNode }) {
    const failure = useSyncExternalStore(failures.subscribe, failures.current);

    if (failure) {
        throw failure;
    }

    return children;
}

/**
 * Tests a layer against the view's classic layers, or the layers of `themes` over them, without issuing
 * core queries: render it with `props` inside `Wrapper`, and its `Default` renders the layer below with
 * the same model. Nested views read fixtures.
 */
export async function createViewTestHost<N extends ViewName>(
    name: N,
    options: ViewTestHostOptions<N> = {}
): Promise<ViewTestHost<N>> {
    registerClassPrefixes([options.prefix]);
    registerClassicTheme();

    const themes = chainOf(options);

    await Promise.all([preloadView(name), preloadTheme(themes)]);
    registerThemes(themes);

    const view: ViewName = name;
    const definition = getViewDefinition(view);
    const extensionId = options.extensionId ?? 'test-extension';
    const model = options.model ?? fixtureModel(view, options.fixture);
    const manager = view === 'server.files.manager' ? (model as FileManagerModel) : undefined;
    const strict = options.strict !== false;
    const failures = strict ? collectLayerFailures() : noFailures;
    const host = createHost(
        {
            extensionId,
            server: createTestServer(),
            path: manager ? `/server/test-server/files#${manager.directory}` : undefined,
        },
        strict ? name : undefined
    );
    const input = options.input === undefined ? (definition?.fixtureInput?.(model) ?? {}) : Object(options.input);
    const Session = definition?.fixtureScope ?? NoSession;
    const harness = createViewHarness(name, { model, inputs: input });

    function Wrapper({ children }: { children?: ReactNode }) {
        const context = useMemo(() => ({ extensionId, context: `view "${name}" test host` }), []);

        return (
            <FailOnLayerError failures={failures}>
                <host.Wrapper>
                    <ExtensionContext.Provider value={context}>
                        <ViewFixtureProvider>
                            <Session model={model}>
                                <harness.Scope>{children}</harness.Scope>
                            </Session>
                        </ViewFixtureProvider>
                    </ExtensionContext.Provider>
                </host.Wrapper>
            </FailOnLayerError>
        );
    }

    return {
        Wrapper,
        props: harness.props,
        dispose: () => {
            failures.stop();
            host.dispose();
        },
    };
}

export interface RenderViewOptions<N extends ViewName> extends TestThemeOptions {
    /** Defaults to the view's first fixture. */
    fixture?: string;
    model?: ViewModel<N>;
    input?: ViewInput<N>;
    /** Passed to the view as `<View className>` would; it lands on the view's root element. */
    className?: string;
    /** Components to mount in slots, as an extension's `slots.register` would. */
    slots?: TestSlots;
    /** Fail when a theme or extension layer throws (the default); false lets it fall back to the layer below. */
    strict?: boolean;
}
export type TestSlots = { readonly [N in SlotName]?: ComponentType<SlotComponentProps<SlotData<N>>> };
export interface RenderedView {
    container: HTMLElement;
    /** Throws what failed after the first render, such as a layer failing on a click. */
    unmount(): void;
}

interface MountOptions {
    themes: readonly ThemeDefinition[];
    slots?: TestSlots;
    strict?: boolean;
}

const viewRouter = createRouter({
    routeTree: createRootRoute(),
    history: createMemoryHistory({ initialEntries: ['/'] }),
});
let rendered: RenderedView | undefined;

/**
 * Replaces whatever was rendered before with `content`, under the themes' skins, views and icons, and
 * throws when it fails to render (and, unless `strict` is false, when one of its layers falls back).
 */
function mount(content: ReactNode, { themes, slots = {}, strict = true }: MountOptions): RenderedView {
    rendered?.unmount();
    registerThemes(themes);

    // Each slot's props are typed by its name where they are given.
    const removeSlots = mountSlotComponents('test-slots', slots as Parameters<typeof mountSlotComponents>[1]);
    const failures = strict ? collectLayerFailures() : null;
    const crashes: unknown[] = [];
    const container = document.body.appendChild(document.createElement('div'));
    const root = createRoot(container, { onUncaughtError: (error) => crashes.push(error) });
    const failure = (): unknown => failures?.current() ?? crashes.at(0);
    const current: RenderedView = {
        container,
        unmount: () => {
            if (rendered !== current) {
                return;
            }

            rendered = undefined;
            act(() => root.unmount());
            container.remove();
            removeSlots();
            failures?.stop();

            const error = failure();

            if (error !== undefined) {
                throw error;
            }
        },
    };

    rendered = current;
    Reflect.set(globalThis, 'IS_REACT_ACT_ENVIRONMENT', true);
    act(() =>
        root.render(
            <QueryClientProvider client={queryClient}>
                <RouterContextProvider router={viewRouter}>{content}</RouterContextProvider>
            </QueryClientProvider>
        )
    );

    if (failure() !== undefined) {
        current.unmount();
    }

    return current;
}

const pendingViews = (themes: readonly ThemeDefinition[]): string[] =>
    themes.flatMap((theme) =>
        Object.entries(theme.views ?? {}).flatMap(([name, view]) => (view && isPendingLazyView(view) ? [name] : []))
    );

/**
 * Renders a view synchronously from fixtures, with the layers of a theme, or a chain of `themes`, over
 * classic. Each call replaces what was rendered before it; nested views read their fixtures too. Await
 * `preloadTheme(theme)` first when the themes have lazy views. It throws when the view fails to render,
 * and when a theme or extension layer throws unless `strict` is false.
 */
export function renderView<N extends ViewName>(name: N, options: RenderViewOptions<N> = {}): RenderedView {
    const themes = chainOf(options);
    const pending = pendingViews(themes);

    if (pending.length) {
        throw new Error(
            `The theme's views ${pending.join(', ')} are lazy and not loaded yet. ` +
                'Await preloadTheme(theme) before renderView, for example in beforeAll.'
        );
    }

    // The model is the one of the view being rendered.
    const models = options.model === undefined ? undefined : ({ [name]: options.model } as Partial<ViewModels>);
    const fixtures = options.fixture === undefined ? undefined : { [name]: options.fixture };

    return mount(
        <ViewFixtureProvider fixtures={fixtures} models={models}>
            <LooseView name={name} className={options.className} {...Object(options.input ?? {})} />
        </ViewFixtureProvider>,
        { themes, slots: options.slots, strict: options.strict }
    );
}

export type RenderPrimitiveOptions = TestThemeOptions;

/**
 * Renders a UI kit primitive synchronously with the skins and replacements of a theme, or a chain of
 * `themes`, over classic's. Each call replaces what was rendered before it.
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
    const primitive = uiPrimitives[name] as JSXElementConstructor<UiPrimitiveProps<UiPrimitiveName>>;

    return mount(createElement(primitive, props), { themes: chainOf(options) });
}
