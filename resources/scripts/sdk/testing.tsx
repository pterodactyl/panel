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
} from '@/views/registry';
import { registerThemeIcons, type ThemeDefinition } from '@/views/theme';
import { clearThemeSkins, registerSkins } from '@/themes/skins';
import classic from '@/themes/classic';
import { isPendingLazyView } from '@/views/lazy';
import { mountedSlots, mountedViews } from '@/extensions/slotWatch';
import { loadCodemirrorEditor } from '@/components/elements/LazyCodemirrorEditor';
import { LooseView, preloadView, ViewFixtureProvider } from '@/views/View';
import {
    act,
    createContext,
    useContext,
    useLayoutEffect,
    type ComponentType,
    type ReactElement,
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
    type HistoryState,
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
import { ExtensionResourceProvider, type ExtensionResourceContext } from '@/extensions/resourceContext';
import {
    clearExtensionError,
    getExtensionErrors,
    mountSlotComponents,
    SCREEN_ROOTS,
    type SlotComponentProps,
    type SlotData,
    type SlotName,
} from '@/extensions/registry';
import { resolvePanelDestination, type PanelDestination, type PanelSearch } from './navigation';
import type { SdkServer, SdkSiteSettings, SdkUser, ServerWebsocketEvent } from './index';

export { createTestServer, createTestServerCard, type TestServerOptions } from '@/views/fixtures';
export {
    createTestFileDetails,
    createTestFileEditor,
    createTestFileManager,
    createTestFileManagerEntry,
} from '@/views/server/fileFixtures';

const causeMessage = (cause: unknown): string => (cause instanceof Error ? cause.message : String(cause));

/** A theme or extension layer of a view that failed, which the strict test renderers throw. */
export class ViewLayerError extends Error {
    constructor(
        readonly view: string,
        readonly layer: string,
        cause: unknown
    ) {
        super(`The "${layer}" layer of view "${view}" failed: ${causeMessage(cause)}`, { cause });
        this.name = 'ViewLayerError';
    }
}

registerClassicTheme();
// Views' hooks and fixtures, classic's own views and the code editor are lazy; loading them here lets a test
// render them synchronously.
await Promise.all([loadViewCatalog(), ...Object.keys(classic.views ?? {}).map(preloadView), loadCodemirrorEditor()]);

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
            logo: null,
            locale: 'en',
            userAvatarsEnabled: false,
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

    function Root() {
        const content = (
            <ExtensionMount extensionId={extensionId} context='test host' resetKey='test'>
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

/** The themes a test renders with. */
export interface TestThemeOptions {
    /**
     * The themes, oldest first, as the panel stacks them over classic: `[theme]`, or `[parent, child]`
     * for a child theme. Each one's `Default` and the `below` of its recipes build on the theme before it.
     */
    themes?: readonly ThemeDefinition[];
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

export interface RenderViewOptions<N extends ViewName> extends TestThemeOptions {
    /** Defaults to the view's first fixture. */
    fixture?: string;
    model?: ViewModel<N>;
    input?: ViewInput<N>;
    /** Passed to the view as `<View className>` would; it lands on the view's root element. */
    className?: string;
    /** Components to mount in slots, as an extension's `slots.register` would. */
    slots?: TestSlots;
    /** An extension's `ui.prefix`, so its prefixed classes merge with the view's as they do in the panel. */
    prefix?: string;
    /**
     * Fail when a theme or extension layer throws, or when the themes leave out a slot classic renders for
     * the same fixture (the default); false lets a layer fall back and slots go missing.
     */
    strict?: boolean;
}
export type TestSlots = { readonly [N in SlotName]?: ComponentType<SlotComponentProps<SlotData<N>>> };
export interface RenderedView {
    container: HTMLElement;
    /** Throws what failed after the first render, such as a layer failing on a click. */
    unmount(): void;
}

interface MountOptions {
    themes?: readonly ThemeDefinition[];
    slots?: TestSlots;
    prefix?: string;
    strict?: boolean;
}

const viewRouter = createRouter({
    routeTree: createRootRoute(),
    history: createMemoryHistory({ initialEntries: ['/'] }),
});
let rendered: RenderedView | undefined;

const viewContext = /^view "(.+)"$/;

/** Clears the failures recorded against view layers, so a render reads only its own. */
function clearLayerFailures(): void {
    for (const { extensionId, context } of getExtensionErrors()) {
        if (viewContext.test(context)) {
            clearExtensionError(extensionId, context);
        }
    }
}

/** The first failure recorded against a view layer, as a `ViewLayerError`. */
function layerFailure(): ViewLayerError | undefined {
    for (const { extensionId, context, message } of getExtensionErrors()) {
        const view = viewContext.exec(context)?.[1];

        if (view !== undefined) {
            return new ViewLayerError(view, extensionId, new Error(message));
        }
    }

    return undefined;
}

/**
 * Replaces whatever was rendered before with `content`, under the themes' skins, views and icons, and
 * throws when it fails to render (and, unless `strict` is false, when one of its layers falls back).
 */
function mount(content: ReactNode, { themes = [], slots = {}, prefix, strict = true }: MountOptions): RenderedView {
    rendered?.unmount();
    registerClassPrefixes([prefix]);
    registerThemes(themes);
    clearLayerFailures();

    // Each slot's props are typed by its name where they are given.
    const removeSlots = mountSlotComponents('test-slots', slots as Parameters<typeof mountSlotComponents>[1]);
    const crashes: unknown[] = [];
    const container = document.body.appendChild(document.createElement('div'));
    const root = createRoot(container, { onUncaughtError: (error) => crashes.push(error) });
    const failure = (): unknown => crashes.at(0) ?? (strict ? layerFailure() : undefined);
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

const AREA_LAYOUTS = new Set(['layout.account', 'layout.server', 'layout.admin']);

const pendingViews = (themes: readonly ThemeDefinition[]): string[] =>
    themes.flatMap((theme) =>
        Object.entries(theme.views ?? {}).flatMap(([name, view]) => (view && isPendingLazyView(view) ? [name] : []))
    );

/**
 * Renders a view synchronously from fixtures, with the layers of `themes` over classic. Each call
 * replaces what was rendered before it; nested views read their fixtures too. Await
 * `preloadTheme(themes)` first when the themes have lazy views. It throws when the view fails to
 * render, and when a theme or extension layer throws unless `strict` is false.
 */
export function renderView<N extends ViewName>(name: N, options: RenderViewOptions<N> = {}): RenderedView {
    const { themes = [] } = options;
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
    const content = (
        <ViewFixtureProvider fixtures={fixtures} models={models}>
            <LooseView name={name} className={options.className} {...Object(options.input ?? {})} />
        </ViewFixtureProvider>
    );
    const strict = options.strict ?? true;
    const mountOptions = { slots: options.slots, prefix: options.prefix, strict };

    if (!strict || !themes.length) {
        return mount(content, { ...mountOptions, themes });
    }

    // The slots classic renders here, which extensions may mount in, must survive the themes. A slot counts
    // only while a view that renders it is on the page: a theme can move a view, and its slots go with it. A
    // theme's app layout draws the area layouts' navigation, so their slots are its to render.
    mount(content, { ...mountOptions, themes: [] });
    const expected = mountedSlots();
    const view = mount(content, { ...mountOptions, themes });
    const shell = themes.some((theme) => theme.views?.['layout.app']);
    const owned = new Set<string>(
        [...mountedViews()]
            .filter((viewName) => !(shell && AREA_LAYOUTS.has(viewName)))
            .flatMap((viewName) => getViewDefinition(viewName)?.slots ?? [])
    );
    const missing = [...expected].filter((slot) => owned.has(slot) && !mountedSlots().has(slot));

    if (missing.length) {
        view.unmount();
        throw new ViewLayerError(
            name,
            layerId(0),
            new Error(
                `The themes leave out the ${missing.map((slot) => `"${slot}"`).join(', ')} slot${missing.length > 1 ? 's' : ''} ` +
                    'that classic renders here, so extensions mounted there disappear. Render <Slot name="…" /> or <Default />.'
            )
        );
    }

    return view;
}

/**
 * Renders any element synchronously with the skins, views and icons of `themes` over classic's, such
 * as a UI kit primitive: `renderWithTheme(<Button>Save</Button>, { themes: [theme] })`. Each call
 * replaces what was rendered before it.
 */
export function renderWithTheme(element: ReactElement, options: TestThemeOptions = {}): RenderedView {
    return mount(element, { themes: options.themes });
}
