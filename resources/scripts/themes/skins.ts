import { createElement, useSyncExternalStore, type ComponentType, type ReactElement } from 'react';
import { cva } from 'class-variance-authority';
import { cn } from '@/lib/cn';
import { isObject, isString } from '@/lib/objects';
import {
    primitiveAttribute,
    UI_PRIMITIVE_PARTS,
    type PrimitiveName,
    type PrimitiveProps,
    type PrimitiveState,
    type ReplaceableName,
    type SkinPart,
} from './primitives';

export {
    UI_PRIMITIVE_PARTS,
    type NoState,
    type PrimitiveName,
    type PrimitiveProps,
    type PrimitiveState,
    type ReplaceableName,
    type SkinPart,
    type UiPrimitives,
    type UiTone,
} from './primitives';

/** Classes appended at the call site. */
export type SkinClassName = string | false | null | undefined;
/** A value a recipe reads; booleans render as present-or-absent `data-*` attributes. */
export type SkinStateValue = string | number | boolean | undefined;
export type SkinStateMap = { readonly [key: string]: SkinStateValue };

type StateKey<S> = Extract<keyof S, string>;
type VariantKey<T> = T extends boolean ? 'true' | 'false' : T extends string | number ? `${T}` : never;

/** A class-variance-authority recipe over the primitive's state. */
export interface SkinRecipeConfig<S> {
    readonly base?: string;
    readonly variants?: {
        readonly [K in StateKey<S>]?: { readonly [V in VariantKey<NonNullable<S[K]>>]?: string };
    };
    readonly compoundVariants?: readonly SkinCompoundVariant<S>[];
    readonly defaultVariants?: { readonly [K in StateKey<S>]?: S[K] };
}
export type SkinCompoundVariant<S> = { readonly [K in StateKey<S>]?: S[K] | readonly S[K][] } & {
    readonly class?: string;
    readonly className?: string;
};
/**
 * The classes the layers below give a part in the current state. `below(extra)` merges `extra` in: a
 * class of it replaces the one below it conflicts with, as `cn` does.
 */
export type SkinBelow = (extra?: SkinClassName) => string;
/**
 * A part's classes. A string or a cva recipe replaces the recipe below; a function of the state
 * replaces it too, unless it builds on `below`.
 */
export type SkinRecipe<S> = string | SkinRecipeConfig<S> | ((state: S, below: SkinBelow) => string);

/** Recipes by part. The parts a skin leaves out keep the recipes below. */
export type Skin<N extends PrimitiveName> = { readonly [P in SkinPart<N>]?: SkinRecipe<PrimitiveState<N>> };
/** A skin with a recipe for every part, as classic's are. */
export type CompleteSkin<N extends PrimitiveName> = { readonly [P in SkinPart<N>]: SkinRecipe<PrimitiveState<N>> };
export type CompleteSkins = { readonly [N in PrimitiveName]: CompleteSkin<N> };

/**
 * A theme's `ui`: a skin per primitive. A presentational primitive can instead be replaced by a
 * component, which receives the props the primitive does.
 */
export type ThemeSkins = {
    readonly [N in PrimitiveName]?: N extends ReplaceableName ? Skin<N> | ComponentType<PrimitiveProps<N>> : Skin<N>;
};

/** What a part spreads onto its element. */
export interface SkinPartProps {
    readonly className: string | undefined;
    readonly 'data-ui': string;
    readonly 'data-part': string;
    readonly [attribute: `data-${string}`]: string | undefined;
}

export interface SkinBinding<N extends PrimitiveName> {
    /**
     * A part's classes with `className` appended, `data-ui`, `data-part`, and a `data-*` attribute for
     * each state value: the bound state on `root`, and `state` on any part given one.
     */
    (part: SkinPart<N>, className?: SkinClassName, state?: Partial<PrimitiveState<N>>): SkinPartProps;
    /** Only the classes, for a part that is another primitive or a library's className option. */
    classes(part: SkinPart<N>, className?: SkinClassName, state?: Partial<PrimitiveState<N>>): string;
}

/** Types a skin by the parts and state of the primitive `name`. */
export function skin<N extends PrimitiveName>(_name: N, definition: Skin<N>): Skin<N> {
    return definition;
}

interface SkinLayer {
    readonly id: string;
    readonly skins: ThemeSkins;
}

// Resolution reads every primitive's recipes alike: state as a map, parts by name.
type Resolver = (state: SkinStateMap) => string;
type ErasedSkin = { readonly [part: string]: SkinRecipe<SkinStateMap> | undefined };
type LayerEntry =
    | { readonly kind: 'skin'; readonly skin: ErasedSkin }
    | { readonly kind: 'component'; readonly component: ComponentType<never> };

interface Resolution {
    readonly skin: { readonly [part: string]: Resolver };
    readonly component: ComponentType<never> | undefined;
}

let layers: readonly SkinLayer[] = [];
const resolutions = new Map<PrimitiveName, Resolution>();
const listeners = new Set<() => void>();
const noClasses = () => '';

function publish(): void {
    resolutions.clear();
    for (const listener of listeners) {
        listener();
    }
}

function subscribe(listener: () => void): () => void {
    listeners.add(listener);

    return () => {
        listeners.delete(listener);
    };
}

const isExoticComponent = (value: ErasedSkin | ComponentType<never>): value is ComponentType<never> =>
    '$$typeof' in value;

/** A layer's entry for a primitive: a skin, or a component (a function, memo, forwardRef or lazy). */
function layerEntry(layer: SkinLayer, name: PrimitiveName): LayerEntry | undefined {
    const entry = layer.skins[name] as ErasedSkin | ComponentType<never> | undefined;

    if (entry instanceof Function) {
        return { kind: 'component', component: entry };
    }

    if (!isObject(entry)) {
        return undefined;
    }

    return isExoticComponent(entry) ? { kind: 'component', component: entry } : { kind: 'skin', skin: entry };
}

/** A recipe as a resolver; a function recipe reads `below`, the resolver of the layers under it. */
function compile(layer: string, name: string, recipe: SkinRecipe<SkinStateMap>, below: Resolver): Resolver {
    if (isString(recipe)) {
        return () => recipe;
    }

    if (recipe instanceof Function) {
        return (state) => {
            try {
                const classes = recipe(state, (extra) => cn(below(state), extra));

                return isString(classes) ? classes : '';
            } catch (error) {
                console.error(`[skins] The "${layer}" recipe of ${name} failed.`, error);

                return '';
            }
        };
    }

    const { base, variants = {}, ...config } = recipe;

    // cva reads the state as its variant props, each typed by the primitive where the recipe is written.
    // It skips compound variants unless `variants` is set.
    return cva(base, { ...config, variants } as never) as Resolver;
}

/** Folds the layers bottom up: a part's recipe is the topmost one, the component the topmost replacement. */
function resolve(name: PrimitiveName): Resolution {
    const parts: readonly string[] = UI_PRIMITIVE_PARTS[name];
    const resolvers = new Map<string, Resolver>();
    let component: ComponentType<never> | undefined;

    for (const layer of layers) {
        const entry = layerEntry(layer, name);

        // Only presentational primitives render the replacement; the others keep their markup.
        if (entry?.kind === 'component') {
            component = entry.component;
            continue;
        }

        for (const [part, recipe] of Object.entries(entry?.skin ?? {})) {
            if (isString(recipe) || recipe instanceof Function || isObject(recipe)) {
                resolvers.set(part, compile(layer.id, name, recipe, resolvers.get(part) ?? noClasses));
            }
        }
    }

    return { skin: Object.fromEntries(parts.map((part) => [part, resolvers.get(part) ?? noClasses])), component };
}

function resolution(name: PrimitiveName): Resolution {
    let resolved = resolutions.get(name);

    if (!resolved) {
        resolved = resolve(name);
        resolutions.set(name, resolved);
    }

    return resolved;
}

/** A primitive's state as the map recipes read. */
const stateMap = <S extends object>(state: S | undefined): SkinStateMap =>
    Object.fromEntries(Object.entries(state ?? {}));

/** `data-*` for each set state value: `true` as a present attribute, `false` and `undefined` as none. */
const stateAttributes = (state: SkinStateMap) =>
    Object.fromEntries(
        Object.entries(state).flatMap(([key, value]) =>
            value === undefined || value === false
                ? []
                : [
                      [
                          `data-${key.replaceAll(/[A-Z]/g, (letter) => `-${letter.toLowerCase()}`)}`,
                          value === true ? '' : String(value),
                      ],
                  ]
        )
    );

function bind<N extends PrimitiveName>(
    name: N,
    { skin: resolved }: Resolution,
    state: PrimitiveState<N>
): SkinBinding<N> {
    const ui = primitiveAttribute(name);
    const bound = stateMap(state);
    const classes = (part: SkinPart<N>, className?: SkinClassName, partState?: Partial<PrimitiveState<N>>) =>
        cn((resolved[part] ?? noClasses)({ ...bound, ...stateMap(partState) }), className);

    return Object.assign(
        (part: SkinPart<N>, className?: SkinClassName, partState?: Partial<PrimitiveState<N>>): SkinPartProps => {
            const own = stateMap(partState);

            return {
                className: classes(part, className, partState) || undefined,
                'data-ui': ui,
                'data-part': part,
                ...stateAttributes(part === 'root' ? { ...bound, ...own } : own),
            };
        },
        { classes }
    );
}

/**
 * Adds a skin layer. Classic is registered first, then each theme of the chain parent first; a layer
 * registered again keeps classic first and moves to the top otherwise.
 */
export function registerSkins(layerId: string, skins: ThemeSkins): void {
    const layer = { id: layerId, skins };
    const others = layers.filter((existing) => existing.id !== layerId);

    layers = layerId === 'classic' ? [layer, ...others] : [...others, layer];
    publish();
}

/** Drops every layer but classic; used when a theme fails to boot. */
export function clearThemeSkins(): void {
    layers = layers.filter((layer) => layer.id === 'classic');
    publish();
}

/** Binds a primitive's skin to its state; re-renders when the layers change. */
export function useSkin<N extends PrimitiveName>(name: N, state: PrimitiveState<N>): SkinBinding<N> {
    return bind(
        name,
        useSyncExternalStore(subscribe, () => resolution(name)),
        state
    );
}

/** `useSkin` outside a component, read from the current layers. */
export function getSkin<N extends PrimitiveName>(name: N, state: PrimitiveState<N>): SkinBinding<N> {
    return bind(name, resolution(name), state);
}

/** A presentational primitive: the topmost layer's replacement component, given the same props, or `Core`. */
export function replaceable<N extends ReplaceableName>(
    name: N,
    Core: ComponentType<PrimitiveProps<N>>
): (props: PrimitiveProps<N>) => ReactElement {
    function Primitive(props: PrimitiveProps<N>): ReactElement {
        const { component } = useSyncExternalStore(subscribe, () => resolution(name));
        // Only a layer's entry for this primitive lands here, so it takes the primitive's props.
        const Component = (component as ComponentType<PrimitiveProps<N>> | undefined) ?? Core;

        return createElement(Component, props);
    }

    Primitive.displayName = name;

    return Primitive;
}
