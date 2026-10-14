import { createContext, useCallback, useContext, useState, useSyncExternalStore, type ReactNode } from 'react';
import {
    getExtensionLoadState,
    getViewOwners,
    getViewOverride,
    reportExtensionError,
    subscribeExtensionRegistry,
} from '@/extensions/registry';
import { isViewComponent, type ViewLayer } from './registry';

export const VIEW_LOAD_TIMEOUT_MS = 5000;

export type ViewDecision = { status: 'loading' } | { status: 'ready'; layers: readonly ViewLayer[] };

const loading: ViewDecision = Object.freeze({ status: 'loading' });
const noLayers: ViewDecision = Object.freeze({ status: 'ready', layers: Object.freeze([]) });

/** Settles once per view name which extension layers it shows; a late bundle cannot replace a committed view. */
export function createViewSession() {
    const decisions = new Map<string, ViewDecision>();
    const listeners = new Map<string, Set<() => void>>();
    const pending = new Map<string, () => void>();
    const imports = new Map<string, Promise<ViewLayer>>();

    const snapshot = (name: string): ViewDecision => {
        let decision = decisions.get(name);

        if (!decision) {
            decision = getViewOwners(name).length ? loading : noLayers;
            decisions.set(name, decision);
        }

        return decision;
    };

    const importLayer = (extensionId: string, name: string): Promise<ViewLayer> | null => {
        const override = getViewOverride(extensionId, name);

        if (!override) {
            return null;
        }

        const key = `${extensionId}:${name}`;
        let imported = imports.get(key);

        if (!imported) {
            imported = Promise.resolve().then(async () => {
                const component = 'load' in override ? (await override.load()).default : override;

                if (!isViewComponent(component)) {
                    throw new Error('The view importer must return a default React component.');
                }

                return { id: extensionId, source: 'extension', component };
            });
            imports.set(key, imported);
        }

        return imported;
    };

    const start = (name: string) => {
        if (snapshot(name).status !== 'loading' || pending.has(name)) {
            return;
        }

        const owners = getViewOwners(name);
        const settled = new Map<string, ViewLayer | null>();
        const started = new Set<string>();
        let active = true;
        let unsubscribe: () => void = () => {};

        // oxlint-disable-next-line prefer-const -- stop() reads timer before it is assigned.
        let timer: ReturnType<typeof setTimeout>;
        const stop = () => {
            active = false;
            clearTimeout(timer);
            unsubscribe();
            pending.delete(name);
        };

        const settle = () => {
            stop();
            decisions.set(name, {
                status: 'ready',
                layers: Object.freeze(owners.flatMap((owner) => settled.get(owner) ?? [])),
            });
            for (const listener of listeners.get(name) ?? []) {
                listener();
            }
        };

        const resolve = (owner: string, layer: ViewLayer | null) => {
            if (!active) {
                return;
            }

            settled.set(owner, layer);
            if (settled.size === owners.length) {
                settle();
            }
        };

        timer = setTimeout(() => {
            for (const owner of owners.filter((candidate) => !settled.has(candidate))) {
                reportExtensionError(
                    owner,
                    `view "${name}"`,
                    new Error(
                        `View loading exceeded ${VIEW_LOAD_TIMEOUT_MS}ms; the layer below is kept until this page is reopened.`
                    )
                );
            }

            settle();
        }, VIEW_LOAD_TIMEOUT_MS);

        const check = () => {
            for (const owner of owners.filter((candidate) => !started.has(candidate))) {
                const status = getExtensionLoadState(owner)?.status;

                if (status === 'failed') {
                    started.add(owner);
                    resolve(owner, null);
                } else if (status === 'loaded') {
                    started.add(owner);
                    const imported = importLayer(owner, name);

                    if (!imported) {
                        resolve(owner, null);
                        continue;
                    }

                    void imported.then(
                        (layer) => resolve(owner, layer),
                        (error) => {
                            if (active) {
                                reportExtensionError(owner, `view "${name}"`, error);
                            }

                            resolve(owner, null);
                        }
                    );
                }
            }
        };

        pending.set(name, stop);
        unsubscribe = subscribeExtensionRegistry(check);
        check();
    };

    return {
        snapshot,
        subscribe(name: string, listener: () => void): () => void {
            const subscribers = listeners.get(name) ?? new Set<() => void>();

            subscribers.add(listener);
            listeners.set(name, subscribers);
            start(name);

            return () => {
                subscribers.delete(listener);
                if (subscribers.size === 0) {
                    listeners.delete(name);
                    pending.get(name)?.();
                }
            };
        },
    };
}

export type ViewSession = ReturnType<typeof createViewSession>;

const ViewSessionContext = createContext<ViewSession | null>(null);

/** Views below share one loading decision per name, as rows of a page do. */
export function ViewSessionProvider({ children }: { children?: ReactNode }) {
    const [session] = useState(createViewSession);

    return <ViewSessionContext.Provider value={session}>{children}</ViewSessionContext.Provider>;
}

export function useViewDecision(name: string): ViewDecision {
    const shared = useContext(ViewSessionContext);
    const [local] = useState(createViewSession);
    const session = shared ?? local;
    const subscribe = useCallback((listener: () => void) => session.subscribe(name, listener), [session, name]);
    const snapshot = useCallback(() => session.snapshot(name), [session, name]);

    return useSyncExternalStore(subscribe, snapshot);
}
