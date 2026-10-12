import {
    createContext,
    createElement,
    useCallback,
    useContext,
    useLayoutEffect,
    useMemo,
    use,
    useState,
    type ComponentType,
    type ReactNode,
} from 'react';
import ExtensionMount from '@/extensions/ExtensionMount';
import { reportExtensionError } from '@/extensions/registry';
import { cn } from '@/lib/cn';
import { FailedLayerMark, InspectedViewBoundary, ViewInspectionContext } from './inspect';
import { isLazyView } from './lazy';
import {
    declaredParts,
    getViewChain,
    loadViewImplementation,
    useViewChain,
    useViewDefinition,
    type LooseViewInput,
    type ViewContext,
    type ViewDefinition,
    type ViewInput,
    type ViewLayer,
    type ViewModels,
    type ViewName,
    type ViewPartComponents,
    type ViewProps,
} from './registry';
import { useViewDecision } from './session';
import { SlotCheck } from './slotCheck';

const ViewAreaContext = createContext<readonly ViewContext[]>([]);
const noop = () => {};

/** Marks the contexts the views below can use; nested areas add to their parent's. */
export function ViewArea({ area, children }: { area: ViewContext; children?: ReactNode }) {
    const parent = useContext(ViewAreaContext);
    const value = useMemo(() => [...parent, area], [parent, area]);

    return <ViewAreaContext.Provider value={value}>{children}</ViewAreaContext.Provider>;
}

export interface ViewFixtureSelection {
    /** The fixture each view renders, extension-defined views included; views not listed render their first. */
    readonly fixtures?: { readonly [N in ViewName]?: string } & { readonly [name: string]: string | undefined };
    /** Explicit models, used before any fixture. */
    readonly models?: Readonly<Partial<ViewModels>>;
}

const FixtureContext = createContext<ViewFixtureSelection | null>(null);

/** Views below read their model from fixtures instead of running their hook, so they render without a backend. */
export function ViewFixtureProvider({ fixtures, models, children }: ViewFixtureSelection & { children?: ReactNode }) {
    const value = useMemo(() => ({ fixtures, models }), [fixtures, models]);

    return <FixtureContext.Provider value={value}>{children}</FixtureContext.Provider>;
}

function fixtureModel(definition: ViewDefinition, selection: ViewFixtureSelection): { model: unknown } | undefined {
    const models = new Map<string, unknown>(Object.entries(selection.models ?? {}));

    if (models.has(definition.name)) {
        return { model: models.get(definition.name) };
    }

    const fixtures = new Map(Object.entries(definition.fixtures ?? {}));
    const chosen = new Map<string, string | undefined>(Object.entries(selection.fixtures ?? {})).get(definition.name);
    const factory = chosen === undefined ? [...fixtures.values()][0] : fixtures.get(chosen);

    if (chosen !== undefined && !factory) {
        throw new Error(`View "${definition.name}" has no fixture "${chosen}".`);
    }

    return factory ? { model: factory() } : undefined;
}

interface LayerFrame {
    readonly model: unknown;
    readonly inputs: LooseViewInput;
    readonly className?: string;
    readonly children?: ReactNode;
    /** Part overrides passed down through `Default`. */
    readonly parts?: ViewPartComponents;
}
interface LevelValue {
    readonly name: string;
    readonly below: readonly ViewLayer[];
    readonly frame: LayerFrame;
    readonly placeholder: ReactNode;
}
interface LayerOverrides {
    readonly model?: unknown;
    readonly className?: string;
    readonly children?: ReactNode;
    readonly parts?: ViewPartComponents;
}
type LayerProps = LooseViewInput & {
    readonly model: unknown;
    readonly Default: ComponentType<LayerOverrides>;
    readonly className?: string;
    readonly children?: ReactNode;
    readonly parts?: ViewPartComponents;
};

/** The parts the top layer renders: those of the nearest layer that declares them, with overrides applied. */
function resolveParts(layers: readonly ViewLayer[], overrides: ViewPartComponents | undefined) {
    const declared = [...layers]
        .reverse()
        .map((layer) => declaredParts(layer.component))
        .find(Boolean);

    return declared || overrides ? { ...declared, ...overrides } : undefined;
}

/**
 * Each level owns a context, so its `Default` keeps one identity across renders and finds this level
 * even when rendered inside another view.
 */
function createLevel() {
    const Context = createContext<LevelValue | null>(null);

    function Default({ model, className, children, parts }: LayerOverrides) {
        const level = useContext(Context);

        if (!level) {
            throw new Error('Render Default inside the view layer that received it.');
        }

        const { frame } = level;

        return (
            // oxlint-disable-next-line no-use-before-define -- Default renders the chain below, which renders a Default in turn.
            <ViewChain
                name={level.name}
                layers={level.below}
                placeholder={level.placeholder}
                frame={{
                    model: model ?? frame.model,
                    inputs: frame.inputs,
                    className: cn(frame.className, className) || undefined,
                    children: children ?? frame.children,
                    parts: parts ? { ...frame.parts, ...parts } : frame.parts,
                }}
            />
        );
    }

    return { Context, Default };
}

function SuspendedLayer({ onSuspend }: { onSuspend: () => void }) {
    useLayoutEffect(onSuspend, [onSuspend]);

    return null;
}

/** A failing layer is reported against its package and replaced by the layer below. */
function LayerBoundary({
    name,
    layer,
    placeholder,
    fallback,
    children,
}: {
    name: string;
    layer: ViewLayer;
    placeholder: ReactNode;
    fallback: ReactNode;
    children: ReactNode;
}) {
    const [suspended, setSuspended] = useState(false);
    const context = `view "${name}"`;
    const onSuspend = useCallback(() => {
        reportExtensionError(
            layer.id,
            context,
            new Error('View layer suspended outside a local Suspense boundary; using the layer below.')
        );
        setSuspended(true);
    }, [layer.id, context]);

    if (suspended) {
        return fallback;
    }

    return (
        <ExtensionMount
            extensionId={layer.id}
            context={context}
            resetKey={name}
            // Theme views may be lazy; an extension's suspension falls back like its failures do.
            loading={layer.source === 'theme' ? placeholder : <SuspendedLayer onSuspend={onSuspend} />}
            failure={fallback}
        >
            {children}
        </ExtensionMount>
    );
}

function ViewChain({
    name,
    layers,
    frame,
    placeholder,
}: {
    name: string;
    layers: readonly ViewLayer[];
    frame: LayerFrame;
    placeholder: ReactNode;
}) {
    const [level] = useState(createLevel);
    const below = useMemo(() => layers.slice(0, -1), [layers]);
    const value = useMemo(() => ({ name, below, frame, placeholder }), [name, below, frame, placeholder]);
    const top = layers.at(-1);

    if (!top) {
        return null;
    }

    const props: LayerProps = {
        ...frame.inputs,
        model: frame.model,
        className: frame.className,
        children: frame.children,
        parts: resolveParts(layers, frame.parts),
        Default: level.Default,
    };
    // Each layer is typed by its own view name where it is registered.
    const element = createElement(top.component as ComponentType<LayerProps>, props);

    return (
        <level.Context.Provider value={value}>
            {top.source === 'classic' ? (
                element
            ) : (
                <LayerBoundary
                    name={name}
                    layer={top}
                    placeholder={placeholder}
                    fallback={
                        <>
                            <FailedLayerMark layerId={top.id} />
                            <ViewChain name={name} layers={below} frame={frame} placeholder={placeholder} />
                        </>
                    }
                >
                    {element}
                </LayerBoundary>
            )}
        </level.Context.Provider>
    );
}

interface BodyProps {
    definition: ViewDefinition;
    inputs: LooseViewInput;
    className?: string;
    children?: ReactNode;
}

function ViewBody({ definition, model, inputs, className, children }: BodyProps & { model: unknown }) {
    const fixtures = useContext(FixtureContext);
    const inspection = useContext(ViewInspectionContext);
    const base = useViewChain(definition.name);
    const decision = useViewDecision(definition.name);
    const layers = useMemo(
        () => (decision.status === 'ready' && decision.layers.length ? [...base, ...decision.layers] : base),
        [base, decision]
    );
    const placeholder = definition.placeholder ?? null;

    if (decision.status === 'loading') {
        return placeholder;
    }

    if (!layers.length) {
        // Engine-only views, such as the search, need the live panel; from fixtures they render nothing.
        if (fixtures) {
            return null;
        }

        throw new Error(`View "${definition.name}" has no implementation.`);
    }

    // Classic's lazy layers load up front, so their parts are known and layers above do not wait in turn.
    // Theme and extension layers load inside their own boundary, which falls back when they fail.
    for (const { source, component } of layers) {
        if (source === 'classic' && isLazyView(component)) {
            use(component.preload());
        }
    }

    const chain = (
        <SlotCheck definition={definition} layer={layers.at(-1)}>
            <ViewChain
                name={definition.name}
                layers={layers}
                placeholder={placeholder}
                frame={{ model, inputs, className, children }}
            />
        </SlotCheck>
    );

    return inspection ? (
        <InspectedViewBoundary
            inspection={inspection}
            definition={definition}
            model={model}
            inputs={inputs}
            layers={layers}
        >
            {chain}
        </InspectedViewBoundary>
    ) : (
        chain
    );
}

function LiveView({ use: useModel, ...props }: BodyProps & { use: (input: LooseViewInput) => unknown }) {
    return <ViewBody {...props} model={useModel(props.inputs)} />;
}

/** Loads a view's implementation and the chunks of its lazy layers, so the router waits for them like a route chunk. */
export async function preloadView(name: string): Promise<void> {
    await Promise.all([
        loadViewImplementation(name),
        ...getViewChain(name).map(({ source, component }) => {
            if (!('preload' in component) || !(component.preload instanceof Function)) {
                return null;
            }

            const loading = Promise.resolve(component.preload());

            // A theme layer that fails to load falls back when it renders, so it must not fail the route.
            return source === 'classic' ? loading : loading.catch(noop);
        }),
    ]);
}

type ViewElementBase = {
    readonly name: string;
    /** Renders nothing when no installed package defines the view. */
    readonly optional?: boolean;
    readonly className?: string;
    readonly children?: ReactNode;
};
export type ViewElementProps<N extends string> = ViewElementBase & { readonly name: N } & ViewInput<N>;

function FixtureScope({ children }: { children?: ReactNode }) {
    return children;
}

function RenderView({ name, optional = false, className, children, ...inputs }: ViewElementBase & LooseViewInput) {
    const definition = useViewDefinition(name);
    const selection = useContext(FixtureContext);
    const areas = useContext(ViewAreaContext);

    if (!definition) {
        if (optional) {
            return null;
        }

        throw new Error(`View "${name}" is not defined.`);
    }

    // Outside every area (a component test) there is nothing to check against.
    if (!selection && areas.length && !areas.includes(definition.context)) {
        throw new Error(`View "${name}" renders in the "${definition.context}" context and was rendered outside it.`);
    }

    const loading = loadViewImplementation(name);

    if (loading) {
        if (!definition.use) {
            // Its classic chunks load alongside its implementation rather than after it.
            preloadView(name).catch(noop);
        }

        use(loading);
    }

    if (!definition.use) {
        throw new Error(`View "${name}" is not implemented by the module its definition loads.`);
    }

    const fixture = selection ? fixtureModel(definition, selection) : undefined;

    if (!fixture) {
        return (
            <LiveView key={name} definition={definition} use={definition.use} inputs={inputs} className={className}>
                {children}
            </LiveView>
        );
    }

    const Scope = definition.fixtureScope ?? FixtureScope;

    return (
        <Scope model={fixture.model}>
            <ViewBody
                definition={definition}
                inputs={{ ...definition.fixtureInput?.(fixture.model), ...inputs }}
                className={className}
                model={fixture.model}
            >
                {children}
            </ViewBody>
        </Scope>
    );
}

/** `View` without the per-name typing, for the SDK test host. */
export { RenderView as LooseView };

/**
 * Renders a view: runs its hook once (or reads a fixture), resolves classic, the theme chain and
 * extension overrides, and renders the top layer with `{ model, Default, ...inputs, children }`.
 */
export const View = RenderView as <N extends string>(props: ViewElementProps<N>) => ReactNode;

/**
 * Binds a `Default` to a view's registered layers and builds the props a layer above them receives,
 * so a single layer can be rendered by hand inside `Scope` (the SDK's view test host).
 */
export function createViewHarness<N extends string>(
    name: N,
    frame: { model: unknown; inputs: object }
): { Scope: ComponentType<{ children?: ReactNode }>; props: ViewProps<N> };
export function createViewHarness(name: string, { model, inputs }: { model: unknown; inputs: object }) {
    const level = createLevel();
    const frame: LayerFrame = { model, inputs: Object.fromEntries(Object.entries(inputs)) };

    function Scope({ children }: { children?: ReactNode }) {
        const below = useViewChain(name);
        const value = useMemo(() => ({ name, below, frame, placeholder: null }), [below]);

        return <level.Context.Provider value={value}>{children}</level.Context.Provider>;
    }

    const props: LayerProps = {
        ...frame.inputs,
        model: frame.model,
        className: frame.className,
        parts: resolveParts(getViewChain(name), frame.parts),
        Default: level.Default,
    };

    return { Scope, props };
}
