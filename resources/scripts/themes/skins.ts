import { createElement, useSyncExternalStore, type ComponentType, type ReactElement } from 'react';
import { clsx } from 'clsx';
import { cn } from '@/lib/cn';
import { isObject, isString } from '@/lib/objects';
import {
    isPrimitiveName,
    primitiveAttribute,
    UI_PRIMITIVES,
    type PrimitiveName,
    type PrimitiveProps,
    type PrimitiveState,
    type ReplaceableName,
    type SkinPart,
} from './primitives';

export {
    UI_PRIMITIVES,
    type NoState,
    type PrimitiveMeta,
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
/** A part's classes: a string, a cva recipe, or a function of the primitive's state. */
export type SkinRecipe<S> = string | SkinRecipeConfig<S> | ((state: S) => string);

/** Recipes by part. A part a skin names replaces the recipe below it wholesale; the others keep theirs. */
export type Skin<N extends PrimitiveName> = { readonly [P in SkinPart<N>]?: SkinRecipe<PrimitiveState<N>> };
/** A skin with a recipe for every part, as classic's are. */
export type CompleteSkin<N extends PrimitiveName> = { readonly [P in SkinPart<N>]: SkinRecipe<PrimitiveState<N>> };
export type CompleteSkins = { readonly [N in PrimitiveName]: CompleteSkin<N> };
/** Each part's classes as resolved from the layers below, which an extending skin builds on. */
export type ResolvedSkin<N extends PrimitiveName> = {
    readonly [P in SkinPart<N>]: (state: PrimitiveState<N>) => string;
};
/** `skin(name, (parent) => ({ ... }))`: a skin computed from the layers below it. */
export interface SkinExtension<N extends PrimitiveName> {
    (parent: ResolvedSkin<N>): Skin<N>;
    readonly skinExtension: N;
}
export type SkinDefinition<N extends PrimitiveName> = Skin<N> | SkinExtension<N>;

/**
 * A theme's `ui`: a skin per primitive. A presentational primitive can instead be replaced by a
 * component, which receives the props the primitive does.
 */
export type ThemeSkins = {
    readonly [N in PrimitiveName]?: N extends ReplaceableName
        ? SkinDefinition<N> | ComponentType<PrimitiveProps<N>>
        : SkinDefinition<N>;
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

/** Types a skin, or with a function `(parent) => skin` extends the skin of the layers below. */
export function skin<N extends PrimitiveName>(
    name: N,
    definition: (parent: ResolvedSkin<N>) => Skin<N>
): SkinExtension<N>;
export function skin<N extends PrimitiveName>(name: N, definition: Skin<N>): Skin<N>;
export function skin<N extends PrimitiveName>(
    name: N,
    definition: Skin<N> | ((parent: ResolvedSkin<N>) => Skin<N>)
): SkinDefinition<N> {
    return definition instanceof Function
        ? Object.assign((parent: ResolvedSkin<N>) => definition(parent), { skinExtension: name })
        : definition;
}

interface SkinLayer {
    readonly id: string;
    readonly skins: ThemeSkins;
}

// Resolution reads every primitive's recipes alike: state as a map, parts by name.
type Resolver = (state: SkinStateMap) => string;
type ErasedSkin = { readonly [part: string]: SkinRecipe<SkinStateMap> | undefined };
type ErasedParent = { readonly [part: string]: Resolver };
type ErasedExtension = ((parent: ErasedParent) => ErasedSkin) & { readonly skinExtension: string };
type LayerEntry =
    | { readonly kind: 'skin'; readonly skin: ErasedSkin }
    | { readonly kind: 'extension'; readonly extend: ErasedExtension }
    | { readonly kind: 'component'; readonly component: ComponentType<never> };

interface Resolution {
    readonly skin: ErasedParent;
    readonly component: ComponentType<never> | undefined;
}

let layers: readonly SkinLayer[] = [];
const resolutions = new Map<PrimitiveName, Resolution>();
const listeners = new Set<() => void>();
const noClasses = () => '';

function warn(message: string): void {
    if (import.meta.env.DEV) {
        console.warn(`[skins] ${message}`);
    }
}

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

/** A layer's entry for a primitive: a skin, a branded extending function, or a component (memo, forwardRef, lazy). */
function layerEntry(layer: SkinLayer, name: PrimitiveName): LayerEntry | undefined {
    const entry = layer.skins[name] as ErasedSkin | ErasedExtension | ComponentType<never> | undefined;

    if (entry instanceof Function) {
        return 'skinExtension' in entry
            ? { kind: 'extension', extend: entry }
            : { kind: 'component', component: entry };
    }

    if (!isObject(entry)) {
        return undefined;
    }

    return isExoticComponent(entry) ? { kind: 'component', component: entry } : { kind: 'skin', skin: entry };
}

/** The values cva treats as a variant key: booleans as `'true'`/`'false'`, empty values as unset. */
function variantKey(value: SkinStateValue): string | undefined {
    if (value === undefined || value === '') {
        return undefined;
    }

    return String(value);
}

/** cva's resolution: base, each variant's class for its value or default, then matching compounds. */
function recipeClasses(recipe: SkinRecipeConfig<SkinStateMap>, state: SkinStateMap): string {
    const defaults = recipe.defaultVariants ?? {};
    const values: SkinStateMap = {
        ...defaults,
        ...Object.fromEntries(Object.entries(state).filter(([, value]) => value !== undefined)),
    };
    const variants = Object.entries(recipe.variants ?? {}).map(([key, options]) => {
        const value = variantKey(state[key]) ?? variantKey(defaults[key]);

        return value === undefined ? undefined : options?.[value];
    });
    const compounds = (recipe.compoundVariants ?? []).flatMap(({ class: classes, className, ...conditions }) =>
        Object.entries(conditions).every(([key, expected]) =>
            Array.isArray(expected) ? expected.includes(values[key]) : values[key] === expected
        )
            ? [classes, className]
            : []
    );

    return clsx(recipe.base, variants, compounds);
}

function compile(layer: string, name: string, recipe: SkinRecipe<SkinStateMap>): Resolver {
    if (isString(recipe)) {
        return () => recipe;
    }

    if (recipe instanceof Function) {
        return (state) => {
            try {
                const classes = recipe(state);

                return isString(classes) ? classes : '';
            } catch (error) {
                console.error(`[skins] The "${layer}" recipe of ${name} failed.`, error);

                return '';
            }
        };
    }

    return (state) => recipeClasses(recipe, state);
}

/** Folds the layers bottom up: a part's recipe is the topmost one, the component the topmost replacement. */
function resolve(name: PrimitiveName): Resolution {
    const parts: readonly string[] = UI_PRIMITIVES[name].parts;
    const resolvers = new Map<string, Resolver>();
    const complete = (): ErasedParent =>
        Object.fromEntries(parts.map((part) => [part, resolvers.get(part) ?? noClasses]));
    let component: ComponentType<never> | undefined;

    for (const layer of layers) {
        const entry = layerEntry(layer, name);

        if (entry?.kind === 'component') {
            // Base UI primitives keep their markup; registerSkins warned about the replacement.
            component = UI_PRIMITIVES[name].replaceable ? entry.component : component;
            continue;
        }

        let definition: ErasedSkin | undefined;

        try {
            definition = entry?.kind === 'extension' ? entry.extend(complete()) : entry?.skin;
        } catch (error) {
            console.error(`[skins] The "${layer.id}" skin of ${name} failed to extend the layers below.`, error);
        }

        for (const [part, recipe] of Object.entries(definition ?? {})) {
            if (!parts.includes(part)) {
                warn(`${name} has no "${part}" part; the "${layer.id}" recipe for it is ignored.`);
            } else if (isString(recipe) || recipe instanceof Function || isObject(recipe)) {
                resolvers.set(part, compile(layer.id, name, recipe));
            }
        }
    }

    return { skin: complete(), component };
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

    for (const name of Object.keys(skins)) {
        if (!isPrimitiveName(name)) {
            warn(`The "${layerId}" layer skins "${name}", which is not a UI primitive.`);
        } else if (layerEntry(layer, name)?.kind === 'component' && !UI_PRIMITIVES[name].replaceable) {
            warn(`${name} keeps its behaviour in core and can only be skinned; the "${layerId}" component is ignored.`);
        }
    }

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
