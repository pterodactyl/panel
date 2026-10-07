import {
    createContext,
    useCallback,
    useContext,
    useState,
    useSyncExternalStore,
    type ComponentType,
    type ReactNode,
} from 'react';
import {
    getComponentOwner,
    getComponentReplacement,
    getExtensionLoadState,
    reportExtensionError,
    subscribeExtensionRegistry,
} from './registry';
import { isReplacementComponent, type ComponentName, type ReplacementProps } from './componentTypes';

export const COMPONENT_LOAD_TIMEOUT_MS = 5000;
type Decision<TProps> =
    | { status: 'loading' }
    | { status: 'default' }
    | { status: 'ready'; extensionId: string; component: ComponentType<TProps> };

export type ComponentDecision<TName extends ComponentName> = Decision<ReplacementProps<TName>>;

/** One replacement decision per component name, shared by every mount on the page. */
export function createComponentSession() {
    const decisions = new Map<ComponentName, Decision<never>>();
    const listeners = new Map<ComponentName, Set<() => void>>();
    const pending = new Map<ComponentName, () => void>();
    const imports = new Map<ComponentName, Promise<ComponentType<never>>>();
    const snapshot = (name: ComponentName): Decision<never> => {
        let decision = decisions.get(name);
        if (!decision) {
            decision = getComponentOwner(name) ? { status: 'loading' } : { status: 'default' };
            decisions.set(name, decision);
        }
        return decision;
    };
    const start = (name: ComponentName) => {
        if (snapshot(name).status !== 'loading' || pending.has(name)) return;
        const extensionId = getComponentOwner(name)!;
        let active = true;
        let unsubscribe: () => void = () => {};
        let timer: ReturnType<typeof setTimeout>;
        const stop = () => {
            active = false;
            clearTimeout(timer);
            unsubscribe();
            pending.delete(name);
        };
        const settle = (decision: Decision<never>) => {
            if (!active) return;
            stop();
            decisions.set(name, decision);
            listeners.get(name)?.forEach((listener) => listener());
        };
        timer = setTimeout(() => {
            reportExtensionError(
                extensionId,
                `component "${name}"`,
                new Error(
                    `Component loading exceeded ${COMPONENT_LOAD_TIMEOUT_MS}ms; the native view is retained until this page is reopened.`
                )
            );
            settle({ status: 'default' });
        }, COMPONENT_LOAD_TIMEOUT_MS);
        const check = () => {
            const state = getExtensionLoadState(extensionId);
            if (state?.status === 'failed') {
                settle({ status: 'default' });
            } else if (state?.status === 'loaded') {
                const replacement = getComponentReplacement(name);
                if (!replacement) {
                    settle({ status: 'default' });
                    return;
                }
                let imported = imports.get(name);
                if (!imported) {
                    imported = Promise.resolve().then(async () => {
                        const Component = 'load' in replacement ? (await replacement.load()).default : replacement;
                        if (!isReplacementComponent(Component)) {
                            throw new Error('The component importer must return a default React component.');
                        }
                        return Component;
                    });
                    imports.set(name, imported);
                }
                void imported.then(
                    (component) => settle({ status: 'ready', extensionId, component }),
                    (error) => {
                        if (!active) return;
                        reportExtensionError(extensionId, `component "${name}"`, error);
                        settle({ status: 'default' });
                    }
                );
            }
        };
        pending.set(name, stop);
        unsubscribe = subscribeExtensionRegistry(check);
        check();
    };
    return {
        snapshot,
        subscribe(name: ComponentName, listener: () => void): () => void {
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
const ComponentSession = createContext<ReturnType<typeof createComponentSession> | null>(null);
export function ComponentReplacementSession({ children }: { children: ReactNode }) {
    const [session] = useState(createComponentSession);
    return <ComponentSession.Provider value={session}>{children}</ComponentSession.Provider>;
}
export function useComponentDecision<TName extends ComponentName>(name: TName): ComponentDecision<TName> {
    const shared = useContext(ComponentSession);
    const [local] = useState(createComponentSession);
    const session = shared ?? local;
    const subscribe = useCallback((listener: () => void) => session.subscribe(name, listener), [session, name]);
    const snapshot = useCallback(() => session.snapshot(name), [session, name]);
    return useSyncExternalStore(subscribe, snapshot) as ComponentDecision<TName>;
}
