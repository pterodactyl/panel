import {
    createContext,
    createElement,
    useContext,
    useEffect,
    useMemo,
    use,
    useState,
    type ComponentType,
    type ReactNode,
} from 'react';
import ErrorBoundary from '@/components/elements/ErrorBoundary';
import ExtensionMount from '@/extensions/ExtensionMount';
import { viewMounted, watchViewSlots } from '@/extensions/slotWatch';
import { cn } from '@/lib/cn';
import {
    getViewChain,
    loadViewImplementation,
    useViewChain,
    useViewDefinition,
    type LooseViewInput,
    type ViewDefinition,
    type ViewInput,
    type ViewLayer,
    type ViewModels,
    type ViewName,
} from './registry';
import type { SlotName } from '@/extensions/registry';
import { useViewDecision } from './session';

const noop = () => {};

export interface ViewFixtureSelection {
    /** The fixture each view renders; views not listed render their first. */
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

/**
 * A view's model from fixtures: an explicit model, the chosen fixture, the model its inputs give (a card
 * placed for each server of a list), or its first fixture.
 */
function fixtureModel(
    definition: ViewDefinition,
    selection: ViewFixtureSelection,
    inputs: LooseViewInput
): { model: unknown } | undefined {
    const models = new Map<string, unknown>(Object.entries(selection.models ?? {}));

    if (models.has(definition.name)) {
        return { model: models.get(definition.name) };
    }

    const fixtures = new Map(Object.entries(definition.fixtures ?? {}));
    const chosen = new Map<string, string | undefined>(Object.entries(selection.fixtures ?? {})).get(definition.name);

    if (chosen === undefined && definition.fixtureFromInput && Object.keys(inputs).length) {
        // Inputs are checked against the view's own type where it is placed.
        return { model: definition.fixtureFromInput(inputs as never) };
    }

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
}
type LayerProps = LooseViewInput & {
    readonly model: unknown;
    readonly Default: ComponentType<LayerOverrides>;
    readonly className?: string;
    readonly children?: ReactNode;
};

/**
 * Each level owns a context, so its `Default` keeps one identity across renders and finds this level
 * even when rendered inside another view.
 */
function createLevel() {
    const Context = createContext<LevelValue | null>(null);

    function Default({ model, className, children }: LayerOverrides) {
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
                }}
            />
        );
    }

    return { Context, Default };
}

/** A failing layer is reported against its package and replaced by the layer below. */
function LayerBoundary({
    name,
    layer,
    placeholder,
    below,
    children,
}: {
    name: string;
    layer: ViewLayer;
    placeholder: ReactNode;
    below: ReactNode;
    children: ReactNode;
}) {
    return (
        <ExtensionMount
            extensionId={layer.id}
            context={`view "${name}"`}
            resetKey={name}
            // A lazy or suspending layer shows the view's placeholder while it loads.
            loading={placeholder}
            // The layers below render in its place. Their own failures stop at the panel's error notice, whose
            // Retry tries the failed layer again, so a failing chain falls back once instead of re-rendering.
            failure={(_error, retry) => <ErrorBoundary onReset={retry}>{below}</ErrorBoundary>}
        >
            {children}
        </ExtensionMount>
    );
}

/**
 * Watches the slots of a view whose top layer is a theme's or an extension's. It renders beside that
 * layer, so its effect runs once the layer has rendered, never while the view shows its placeholder.
 */
function SlotWatcher({ view, owners, slots }: { view: string; owners: string; slots: readonly SlotName[] }) {
    useEffect(() => watchViewSlots(view, owners.split(','), slots), [view, owners, slots]);

    return null;
}

function ViewChain({
    name,
    layers,
    frame,
    placeholder,
    watcher,
}: {
    name: string;
    layers: readonly ViewLayer[];
    frame: LayerFrame;
    placeholder: ReactNode;
    /** Only the outermost chain of a view watches its slots. */
    watcher?: ReactNode;
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
                    below={<ViewChain name={name} layers={below} frame={frame} placeholder={placeholder} />}
                >
                    {element}
                    {watcher}
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
    const base = useViewChain(definition.name);
    const decision = useViewDecision(definition.name);
    const layers = useMemo(
        () => (decision.status === 'ready' && decision.layers.length ? [...base, ...decision.layers] : base),
        [base, decision]
    );
    const placeholder = definition.placeholder ?? null;
    const owners = layers.flatMap(({ id, source }) => (source === 'classic' ? [] : [id])).join(',');

    useEffect(() => viewMounted(definition.name), [definition.name]);

    if (decision.status === 'loading') {
        return placeholder;
    }

    if (!layers.length) {
        throw new Error(`View "${definition.name}" has no implementation.`);
    }

    return (
        <ViewChain
            name={definition.name}
            layers={layers}
            placeholder={placeholder}
            frame={{ model, inputs, className, children }}
            // Classic renders every slot of a view; layers above it decide for themselves.
            watcher={
                owners && definition.slots?.length ? (
                    <SlotWatcher view={definition.name} owners={owners} slots={definition.slots} />
                ) : undefined
            }
        />
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
    readonly className?: string;
    readonly children?: ReactNode;
};
export type ViewElementProps<N extends ViewName> = ViewElementBase & { readonly name: N } & ViewInput<N>;

function RenderView({ name, className, children, ...inputs }: ViewElementBase & LooseViewInput) {
    const definition = useViewDefinition(name);
    const selection = useContext(FixtureContext);

    if (!definition) {
        throw new Error(`View "${name}" is not defined.`);
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

    const fixture = selection ? fixtureModel(definition, selection, inputs) : undefined;

    if (!fixture) {
        return (
            <LiveView key={name} definition={definition} use={definition.use} inputs={inputs} className={className}>
                {children}
            </LiveView>
        );
    }

    return (
        <ViewBody
            definition={definition}
            inputs={{ ...definition.fixtureInput?.(fixture.model), ...inputs }}
            className={className}
            model={fixture.model}
        >
            {children}
        </ViewBody>
    );
}

/** `View` without the per-name typing, for `renderView`. */
export { RenderView as LooseView };

/**
 * Renders a view: runs its hook once (or reads a fixture), resolves classic, the theme chain and
 * extension overrides, and renders the top layer with `{ model, Default, ...inputs, children }`.
 */
export const View = RenderView as <N extends ViewName>(props: ViewElementProps<N>) => ReactNode;
