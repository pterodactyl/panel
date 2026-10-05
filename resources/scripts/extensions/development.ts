import type { SiteExtensionEntry } from './registry';

export function startExtensionDevelopmentReload(
    entries: readonly SiteExtensionEntry[],
    reload: () => void = () => window.location.reload()
): () => void {
    const active = entries.flatMap((entry) => (entry.development ? [entry.development] : []));
    if (active.length === 0) return () => {};
    let stopped = false;
    let timer: ReturnType<typeof setTimeout> | undefined;
    let controller: AbortController | undefined;
    const stop = () => {
        stopped = true;
        clearTimeout(timer);
        controller?.abort();
        window.removeEventListener('pagehide', stop);
    };
    const poll = async () => {
        if (stopped) return;
        if (!document.hidden) {
            controller = new AbortController();
            const timeout = setTimeout(() => controller?.abort(), 5000);
            try {
                const changed = await Promise.all(
                    active.map(async ({ url, version }) => {
                        try {
                            const response = await fetch(url, { cache: 'no-store', signal: controller?.signal });
                            if (response.status === 404) {
                                const index = active.findIndex((entry) => entry.url === url);
                                if (index !== -1) active.splice(index, 1);
                                return false;
                            }
                            const next = response.ok ? (await response.text()).trim() : '';
                            return /^[a-f0-9]{64}$/.test(next) && next !== version;
                        } catch {
                            return false;
                        }
                    })
                );
                if (!stopped && changed.some(Boolean)) {
                    stop();
                    reload();
                    return;
                }
            } finally {
                clearTimeout(timeout);
            }
        }
        if (active.length === 0) stop();
        if (!stopped)
            timer = setTimeout(() => {
                void poll();
            }, 1500);
    };
    window.addEventListener('pagehide', stop);
    timer = setTimeout(() => {
        void poll();
    }, 1500);
    return stop;
}
