import { createElement, use, type JSXElementConstructor } from 'react';
import type { LooseViewInput } from './registry';

interface ModuleState {
    readonly ready: Promise<void>;
    module?: object;
}

/** Load state per importer, so every view exported by one chunk is ready once any of them is. */
const modules = new WeakMap<() => Promise<object>, ModuleState>();

function loadModule(importer: () => Promise<object>): ModuleState {
    let state = modules.get(importer);

    if (!state) {
        const created: ModuleState = {
            ready: importer().then(
                (module) => {
                    created.module = module;
                },
                (cause: unknown) => {
                    modules.delete(importer);
                    throw cause;
                }
            ),
        };

        state = created;
        modules.set(importer, created);
    }

    return state;
}

export interface LazyViewStatics {
    preload(): Promise<void>;
    readonly loaded: boolean;
}

export function isPendingLazyView<T extends JSXElementConstructor<never>>(
    component: T
): component is T & LazyViewStatics {
    return 'loaded' in component && component.loaded === false && 'preload' in component;
}

/**
 * A lazily loaded view. Views exported by one chunk share its load, the router preloads it, and the
 * engine loads every lazy layer of a view before rendering so their parts are known.
 */
export function lazyView<M extends { default: unknown }>(importer: () => Promise<M>): M['default'] & LazyViewStatics;
export function lazyView<M extends object, K extends keyof M & string>(
    importer: () => Promise<M>,
    exportName: K
): M[K] & LazyViewStatics;
export function lazyView(importer: () => Promise<object>, exportName = 'default') {
    const loaded = () => modules.get(importer)?.module;

    function LazyView(props: LooseViewInput) {
        const state = loadModule(importer);

        if (!state.module) {
            use(state.ready);
        }

        return createElement(Reflect.get(state.module ?? {}, exportName), props);
    }

    return Object.defineProperties(LazyView, {
        preload: { value: () => loadModule(importer).ready },
        loaded: { get: () => loaded() !== undefined },
        parts: { get: () => Reflect.get(Reflect.get(loaded() ?? {}, exportName) ?? {}, 'parts') },
    });
}
