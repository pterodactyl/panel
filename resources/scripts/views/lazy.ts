import { createElement, use, type JSXElementConstructor } from 'react';
import type { LooseViewInput } from './registry';

interface ModuleState {
    readonly ready: Promise<void>;
    module?: object;
}

/** Load state per importer, so every view exported by one chunk is ready once any of them is. */
const modules = new WeakMap<() => Promise<object>, ModuleState>();

/**
 * Marks a load done on its promise the way React's `use` reads it. A component that suspended on the load
 * passes it to `use` on every render after, which returns at once once it has loaded. A failed load is
 * dropped and imported again, so its failure is not recorded.
 */
export function settledLoad(promise: Promise<void>): Promise<void> {
    const settled: Promise<void> & { status?: 'fulfilled'; value?: undefined } = promise;

    void promise.then(
        () => {
            settled.status = 'fulfilled';
            settled.value = undefined;
        },
        () => {}
    );

    return promise;
}

function loadModule(importer: () => Promise<object>): ModuleState {
    let state = modules.get(importer);

    if (!state) {
        const created: ModuleState = {
            ready: settledLoad(
                importer().then(
                    (module) => {
                        created.module = module;
                    },
                    (cause: unknown) => {
                        modules.delete(importer);
                        throw cause;
                    }
                )
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

export function isLazyView<T extends JSXElementConstructor<never>>(component: T): component is T & LazyViewStatics {
    return 'loaded' in component && 'preload' in component;
}

export function isPendingLazyView<T extends JSXElementConstructor<never>>(
    component: T
): component is T & LazyViewStatics {
    return isLazyView(component) && component.loaded === false;
}

/** A lazily loaded view. Views exported by one chunk share its load, and the router preloads it. */
export function lazyView<M extends { default: unknown }>(importer: () => Promise<M>): M['default'] & LazyViewStatics;
export function lazyView<M extends object, K extends keyof M & string>(
    importer: () => Promise<M>,
    exportName: K
): M[K] & LazyViewStatics;
export function lazyView(importer: () => Promise<object>, exportName = 'default') {
    const loaded = () => modules.get(importer)?.module;

    function LazyView(props: LooseViewInput) {
        const state = loadModule(importer);

        use(state.ready);

        return createElement(Reflect.get(state.module ?? {}, exportName), props);
    }

    return Object.defineProperties(LazyView, {
        preload: { value: () => loadModule(importer).ready },
        loaded: { get: () => loaded() !== undefined },
    });
}
