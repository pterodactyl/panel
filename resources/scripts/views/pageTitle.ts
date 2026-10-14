import { useSyncExternalStore } from 'react';

const listeners = new Set<() => void>();
let current: string | undefined;

const read = (): string => current ?? document.title;

/** Titles the document; `PageContentBlock` and `ServerContentBlock` call it with their `title`. */
export function setPageTitle(title: string): void {
    current = title;
    document.title = title;
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

/** The document title the page on show set, such as "Survival | Console", for a shell to show in its own bar. */
export function usePageTitle(): string {
    return useSyncExternalStore(subscribe, read, read);
}
