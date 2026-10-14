import { isObject, isString } from '@/lib/objects';

/** Published by `p:extension:dev --watch`: `scripts` changes with every file but the stylesheets. */
export interface DevelopmentPayload {
    url: string;
    version: string;
    scripts?: string;
}

type DevelopmentMarker = { version: string; scripts: string };

const build = /^[a-f0-9]{64}$/;

const readMarker = (text: string): DevelopmentMarker | null => {
    try {
        const marker: unknown = JSON.parse(text);

        if (!isObject(marker) || !('version' in marker) || !('scripts' in marker)) {
            return null;
        }

        const { version, scripts } = marker;

        return isString(version) && isString(scripts) && build.test(version) && build.test(scripts)
            ? { version, scripts }
            : null;
    } catch {
        return null;
    }
};

/** Points the stylesheets of one published build at another, removing each old link once its replacement loads. */
export function swapDevelopmentStylesheets(url: string, from: string, to: string): void {
    const root = new URL(url, window.location.href).pathname.replace(/_development$/, '');

    for (const link of document.querySelectorAll<HTMLLinkElement>('link[rel="stylesheet"]')) {
        const href = new URL(link.href, window.location.href);

        if (!href.pathname.startsWith(`${root}${from}/`)) {
            continue;
        }

        const next = link.cloneNode() as HTMLLinkElement;

        next.href = `${root}${to}/${href.pathname.slice(`${root}${from}/`.length)}${href.search}`;
        next.addEventListener('load', () => link.remove(), { once: true });
        next.addEventListener('error', () => next.remove(), { once: true });
        link.after(next);
    }
}

/**
 * Polls the development marker of every package being watched. A rebuild that only changed
 * stylesheets swaps them in place; any other rebuild reloads the page.
 */
export function startExtensionDevelopmentReload(
    entries: readonly { development?: DevelopmentPayload | null }[],
    reload: () => void = () => window.location.reload()
): () => void {
    const active = entries.flatMap((entry) => (entry.development ? [{ ...entry.development }] : []));

    if (active.length === 0) {
        return () => {};
    }

    let stopped = false;
    let timer: ReturnType<typeof setTimeout> | undefined;
    let controller: AbortController | undefined;
    const stop = () => {
        stopped = true;
        clearTimeout(timer);
        controller?.abort();
        window.removeEventListener('pagehide', stop);
    };

    const check = async (payload: DevelopmentPayload): Promise<boolean> => {
        try {
            const response = await fetch(payload.url, { cache: 'no-store', signal: controller?.signal });

            if (response.status === 404) {
                active.splice(active.indexOf(payload), 1);

                return false;
            }

            const marker = response.ok ? readMarker(await response.text()) : null;

            if (!marker || marker.version === payload.version) {
                return false;
            }

            if (payload.scripts === undefined || marker.scripts !== payload.scripts) {
                return true;
            }

            swapDevelopmentStylesheets(payload.url, payload.version, marker.version);
            payload.version = marker.version;

            return false;
        } catch {
            return false;
        }
    };

    const poll = async () => {
        if (stopped) {
            return;
        }

        if (!document.hidden) {
            controller = new AbortController();
            const timeout = setTimeout(() => controller?.abort(), 5000);

            try {
                const changed = await Promise.all([...active].map(check));

                if (!stopped && changed.some(Boolean)) {
                    stop();
                    reload();

                    return;
                }
            } finally {
                clearTimeout(timeout);
            }
        }

        if (active.length === 0) {
            stop();
        }

        if (!stopped) {
            timer = setTimeout(() => {
                void poll();
            }, 1500);
        }
    };

    window.addEventListener('pagehide', stop);
    timer = setTimeout(() => {
        void poll();
    }, 1500);

    return stop;
}
