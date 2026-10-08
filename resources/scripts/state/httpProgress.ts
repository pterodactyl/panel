import { useSyncExternalStore } from 'react';

interface HttpProgressSnapshot {
    continuous: boolean;
    progress?: number;
}

let activeRequests = 0;
let snapshot: HttpProgressSnapshot = { continuous: false, progress: undefined };
const listeners = new Set<() => void>();

const publish = (next: HttpProgressSnapshot) => {
    snapshot = next;
    for (const listener of listeners) {
        listener();
    }
};

const subscribe = (listener: () => void) => {
    listeners.add(listener);

    return () => listeners.delete(listener);
};

export const startHttpProgress = (): void => {
    activeRequests++;
    if (activeRequests === 1) {
        publish({ continuous: true, progress: undefined });
    }
};

export const completeHttpProgress = (): void => {
    activeRequests = Math.max(0, activeRequests - 1);
    if (activeRequests === 0) {
        publish({ continuous: false, progress: snapshot.progress ? 100 : undefined });
    }
};

export const setHttpProgress = (progress: number | undefined): void => {
    publish({ ...snapshot, progress });
};

export const useHttpProgress = (): HttpProgressSnapshot =>
    useSyncExternalStore(
        subscribe,
        () => snapshot,
        () => snapshot
    );
