import { useSyncExternalStore, type ComponentType, type JSXElementConstructor, type ReactNode } from 'react';
import { isObject } from '@/lib/objects';
import type { SlotName } from '@/extensions/registry';

/** Where a view can render; `app` covers every signed-in area. */
export type ViewContext = 'app' | 'server' | 'account' | 'admin' | 'auth';
export type ViewKind = 'layout' | 'page' | 'block';
export const VIEW_CONTEXTS: readonly ViewContext[] = ['app', 'server', 'account', 'admin', 'auth'];

/** The model of every engine view by name. Definition modules add their entries. */
export interface ViewModels {}
/** Props a view takes besides its model, such as the server of a card. */
export interface ViewInputs {}
/** Named parts of the views that publish them, which `Default` can swap individually. */
export interface ViewPartNames {}

export type ViewName = Extract<keyof ViewModels, string>;
export type ViewModel<N> = N extends keyof ViewModels ? ViewModels[N] : unknown;
// oxlint-disable-next-line rules/no-unsafe-dictionary-type -- views other packages define are typed loosely.
export type LooseViewInput = Readonly<Record<string, unknown>>;
export type ViewInput<N> = N extends keyof ViewInputs
    ? ViewInputs[N]
    : N extends keyof ViewModels
      ? unknown
      : // oxlint-disable-next-line rules/no-unsafe-dictionary-type -- views other packages define are typed loosely.
        LooseViewInput;

export type ViewPartProps<N> = { readonly model: ViewModel<N> } & ViewInput<N>;
export type ViewParts<N> = N extends keyof ViewPartNames
    ? { readonly [P in ViewPartNames[N] & string]: ComponentType<ViewPartProps<N>> }
    : never;
type PartsProp<N> = N extends keyof ViewPartNames ? { readonly parts: ViewParts<N> } : unknown;
type PartsOverride<N> = N extends keyof ViewPartNames ? { readonly parts?: Partial<ViewParts<N>> } : unknown;

/** `Default` renders the layer below; every prop is optional and replaces what this layer received. */
export type DefaultViewProps<N> = {
    readonly model?: ViewModel<N>;
    readonly className?: string;
    readonly children?: ReactNode;
} & PartsOverride<N>;

export type ViewProps<N> = {
    readonly model: ViewModel<N>;
    readonly Default: ComponentType<DefaultViewProps<N>>;
    readonly className?: string;
    readonly children?: ReactNode;
} & PartsProp<N> &
    ViewInput<N>;

/** A layer of a view; `parts` declares the parts it renders for the layers above it. */
export type ViewComponent<N> = ComponentType<ViewProps<N>> & { readonly parts?: ViewParts<N> };

/** Wraps a view rendered from a fixture, for views whose parts read an engine session. */
export type ViewFixtureScope<N> = ComponentType<{ readonly model: ViewModel<N>; readonly children?: ReactNode }>;

export interface ViewOptions<N extends ViewName> {
    readonly context: ViewContext;
    /** The engine hook producing the model; the only data source of the view. */
    use(input: ViewInput<N>): ViewModel<N>;
    /** Models for the workbench and tests; the first is the default. */
    readonly fixtures?: Readonly<Record<string, () => ViewModel<N>>>;
    /** The inputs a fixture renders with, for views that take inputs. */
    fixtureInput?(model: ViewModel<N>): ViewInput<N>;
    readonly fixtureScope?: ViewFixtureScope<N>;
    readonly slots?: readonly SlotName[];
    /** Shown while extension layers of this view load. */
    readonly placeholder?: ReactNode;
}

export interface ViewDefinition<N extends string = string> {
    readonly name: N;
    readonly kind: ViewKind;
    readonly context: ViewContext;
    use(input: ViewInput<N>): ViewModel<N>;
    readonly fixtures?: Readonly<Record<string, () => ViewModel<N>>>;
    fixtureInput?(model: ViewModel<N>): ViewInput<N>;
    readonly fixtureScope?: ViewFixtureScope<N>;
    readonly slots?: readonly SlotName[];
    readonly placeholder?: ReactNode;
    /** The extension that defined the view; core views have none. */
    readonly extensionId?: string;
}

export type ViewLayerSource = 'classic' | 'theme' | 'extension';
export type ViewPartComponents = Readonly<Partial<Record<string, JSXElementConstructor<never>>>>;
export interface ViewLayer {
    /** The package the layer belongs to, which its failures are attributed to. */
    readonly id: string;
    readonly source: ViewLayerSource;
    readonly component: JSXElementConstructor<never>;
}
export type ViewImporter<N> = () => Promise<{ default: ViewComponent<N> }>;
/** An extension layer: a component, or an importer loaded while the view shows its placeholder. */
export type ViewOverride<N> = ViewComponent<N> | { readonly load: ViewImporter<N> };

export interface ExtensionViewProps<TModel> {
    readonly model: TModel;
    readonly Default: ComponentType<{
        readonly model?: TModel;
        readonly className?: string;
        readonly children?: ReactNode;
    }>;
    readonly className?: string;
    readonly children?: ReactNode;
}
/** A view an extension adds, so themes and other extensions can restyle or place it. */
export interface ExtensionViewOptions<TModel> {
    readonly context: ViewContext;
    use(input: LooseViewInput): TModel;
    readonly Default: ComponentType<ExtensionViewProps<TModel> & LooseViewInput>;
    readonly fixtures?: Readonly<Record<string, () => TModel>>;
    readonly placeholder?: ReactNode;
}
/** Any extension's view definition, as the registry stores it. */
export interface ExtensionViewDefinition {
    readonly context: ViewContext;
    use(input: LooseViewInput): unknown;
    readonly Default: JSXElementConstructor<never>;
    readonly fixtures?: Readonly<Record<string, () => unknown>>;
    readonly placeholder?: ReactNode;
}

/** Views a theme or extension provides, keyed by name; unknown names are typed loosely. */
export type ThemeViews = { readonly [N in ViewName]?: ViewComponent<N> } & {
    readonly [name: string]: JSXElementConstructor<never> | undefined;
};

interface LayerSet {
    base?: ViewLayer;
    themes: ViewLayer[];
}

const definitions = new Map<string, ViewDefinition>();
const layers = new Map<string, LayerSet>();
const chains = new Map<string, readonly ViewLayer[]>();
const listeners = new Set<() => void>();
const emptyChain: readonly ViewLayer[] = Object.freeze([]);
let sortedDefinitions: readonly ViewDefinition[] | undefined;

function publish(): void {
    chains.clear();
    sortedDefinitions = undefined;
    for (const listener of listeners) {
        listener();
    }
}

export function subscribeViews(listener: () => void): () => void {
    listeners.add(listener);

    return () => {
        listeners.delete(listener);
    };
}

export function viewKind(name: string): ViewKind {
    if (name.startsWith('layout.')) {
        return 'layout';
    }

    return name.startsWith('page.') ? 'page' : 'block';
}

export function isViewContext(value: string): value is ViewContext {
    return VIEW_CONTEXTS.some((context) => context === value);
}

/** Core definitions replace an earlier one of the same name, so a hot reload re-registers cleanly. */
export function defineView<N extends ViewName>(name: N, options: ViewOptions<N>): ViewDefinition<N> {
    const definition: ViewDefinition<N> = Object.freeze({ ...options, name, kind: viewKind(name) });

    // The inputs of <View name={name}/> are checked against the definition where it is rendered.
    definitions.set(name, definition as ViewDefinition);
    publish();

    return definition;
}

export function registerViewDefinition(definition: ViewDefinition): void {
    if (definitions.has(definition.name)) {
        throw new Error(`View "${definition.name}" is already defined.`);
    }

    definitions.set(definition.name, Object.freeze({ ...definition }));
    publish();
}

export function getViewDefinition(name: string): ViewDefinition | undefined {
    return definitions.get(name);
}

export function getViewDefinitions(): readonly ViewDefinition[] {
    return (sortedDefinitions ??= Object.freeze(
        [...definitions.values()].sort((a, b) => a.name.localeCompare(b.name))
    ));
}

/** Every defined view by name, including those extensions define as they load. */
export function useViewDefinitions(): readonly ViewDefinition[] {
    return useSyncExternalStore(subscribeViews, getViewDefinitions);
}

export function useViewDefinition(name: string): ViewDefinition | undefined {
    return useSyncExternalStore(subscribeViews, () => definitions.get(name));
}

function layerSet(name: string): LayerSet {
    let set = layers.get(name);

    if (!set) {
        set = { themes: [] };
        layers.set(name, set);
    }

    return set;
}

/** A function component, or a memo, forwardRef or lazy wrapper of one. */
export function isViewComponent<T>(value: T): value is T & JSXElementConstructor<never> {
    return (
        value instanceof Function ||
        (isObject(value) &&
            '$$typeof' in value &&
            ['react.memo', 'react.forward_ref', 'react.lazy'].some((type) => Symbol.for(type) === value.$$typeof))
    );
}

/** The parts a layer renders, read when needed since a lazy layer only knows them once loaded. */
export function declaredParts(component: JSXElementConstructor<never>): ViewPartComponents | undefined {
    if (!('parts' in component) || !isObject(component.parts)) {
        return undefined;
    }

    return Object.fromEntries(Object.entries(component.parts).filter(([, part]) => isViewComponent(part)));
}

function toLayer(id: string, source: ViewLayerSource, component: JSXElementConstructor<never>): ViewLayer {
    return Object.freeze({ id, source, component });
}

/** The bottom layer of a view: classic for core views, the defining extension's `Default` otherwise. */
export function registerBaseView(
    id: string,
    source: ViewLayerSource,
    name: string,
    component: JSXElementConstructor<never>
) {
    layerSet(name).base = toLayer(id, source, component);
    publish();
}

/** Classic is registered once at boot; theme layers stack in chain order, parent first. */
export function registerViewLayer(id: string, source: 'classic' | 'theme', views: ThemeViews): void {
    for (const [name, component] of Object.entries(views)) {
        if (!component) {
            continue;
        }

        const set = layerSet(name);
        const layer = toLayer(id, source, component);

        if (source === 'classic') {
            set.base = layer;
        } else {
            set.themes = [...set.themes.filter((existing) => existing.id !== id), layer];
        }
    }

    publish();
}

/** Drops every theme layer, leaving classic; used when a theme fails to boot. */
export function clearThemeLayers(): void {
    for (const set of layers.values()) {
        set.themes = [];
    }

    publish();
}

/** Classic (or the defining extension), then the theme chain; extension overrides come from the view session. */
export function getViewChain(name: string): readonly ViewLayer[] {
    let chain = chains.get(name);

    if (!chain) {
        const set = layers.get(name);

        chain = set ? Object.freeze([...(set.base ? [set.base] : []), ...set.themes]) : emptyChain;
        chains.set(name, chain);
    }

    return chain;
}

export function useViewChain(name: string): readonly ViewLayer[] {
    return useSyncExternalStore(subscribeViews, () => getViewChain(name));
}
