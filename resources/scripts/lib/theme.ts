export const THEME_CHANGE_EVENT = 'pterodactyl:theme-change';

type ThemeChangeListener = () => void;

const listeners = new Set<ThemeChangeListener>();
let stopWatching: (() => void) | undefined;
let frame: number | undefined;

const isStylesheetNode = (node: Node | null): boolean =>
    node instanceof HTMLStyleElement || (node instanceof HTMLLinkElement && node.relList.contains('stylesheet'));

const affectsTheme = (mutation: MutationRecord): boolean => {
    if (mutation.target === document.documentElement) {
        return mutation.type === 'attributes';
    }

    if (mutation.type === 'childList') {
        return (
            isStylesheetNode(mutation.target) ||
            [...mutation.addedNodes, ...mutation.removedNodes].some((node) => isStylesheetNode(node))
        );
    }

    // <style> text edits, or attribute changes on a stylesheet element.
    return isStylesheetNode(mutation.type === 'characterData' ? mutation.target.parentNode : mutation.target);
};

const flush = () => {
    frame = undefined;
    for (const listener of listeners) {
        listener();
    }
};

const schedule = () => {
    frame ??= requestAnimationFrame(flush);
};

const watch = (): (() => void) => {
    const observer = new MutationObserver((mutations) => {
        if (mutations.some(affectsTheme)) {
            schedule();
        }
    });

    observer.observe(document.documentElement, { attributes: true });
    observer.observe(document.head, { attributes: true, characterData: true, childList: true, subtree: true });

    // `load` does not bubble, so listen in the capture phase.
    const onLoad = (event: Event) => {
        if (event.target instanceof Node && isStylesheetNode(event.target)) {
            schedule();
        }
    };

    document.head.addEventListener('load', onLoad, true);
    window.addEventListener(THEME_CHANGE_EVENT, schedule);

    const scheme = window.matchMedia?.('(prefers-color-scheme: dark)');

    scheme?.addEventListener('change', schedule);

    return () => {
        observer.disconnect();
        document.head.removeEventListener('load', onLoad, true);
        window.removeEventListener(THEME_CHANGE_EVENT, schedule);
        scheme?.removeEventListener('change', schedule);
        if (frame !== undefined) {
            cancelAnimationFrame(frame);
            frame = undefined;
        }
    };
};

/** Calls `listener` at most once a frame; a hint, not a diff. */
export const onThemeChange = (listener: ThemeChangeListener): (() => void) => {
    listeners.add(listener);
    stopWatching ??= watch();

    return () => {
        listeners.delete(listener);
        if (listeners.size === 0) {
            stopWatching?.();
            stopWatching = undefined;
        }
    };
};

export const notifyThemeChange = (): void => {
    window.dispatchEvent(new Event(THEME_CHANGE_EVENT));
};

export const readThemeToken = (name: `--${string}`): string =>
    getComputedStyle(document.documentElement).getPropertyValue(name).trim();

/** Keeps the panel's `<meta name="theme-color" data-theme-token>` equal to `--theme-color`. */
export const syncThemeColorMeta = (): (() => void) => {
    const apply = () => {
        const color = readThemeToken('--theme-color');
        const meta = document.querySelector<HTMLMetaElement>('meta[name="theme-color"][data-theme-token]');

        if (meta && color.length > 0 && meta.content !== color) {
            meta.content = color;
        }
    };

    apply();

    return onThemeChange(apply);
};
