// Browser APIs the panel's components use that jsdom lacks. Tests that need them to report something
// can still replace them with vi.stubGlobal.
const window = globalThis.window;

// Base UI dispatches PointerEvent; jsdom supplies the MouseEvent fields these tests use.
if (window?.PointerEvent === undefined && window?.MouseEvent) {
    Object.defineProperty(window, 'PointerEvent', {value: window.MouseEvent});
}

// The memory router restores scroll positions; jsdom has no scrolling implementation.
if (window?.navigator.userAgent.includes('jsdom')) {
    window.scrollTo = () => {};
}

// Charts and the sub-navigation measure themselves; nothing resizes in jsdom, so nothing is observed.
class InertObserver {
    observe() {}
    unobserve() {}
    disconnect() {}
    takeRecords() {
        return [];
    }
}

for (const name of ['ResizeObserver', 'IntersectionObserver']) {
    if (window && window[name] === undefined) {
        Object.defineProperty(window, name, {value: InertObserver, writable: true, configurable: true});
    }
}

// The scheme switch reads the system preference; jsdom matches no media query.
if (window && window.matchMedia === undefined) {
    Object.defineProperty(window, 'matchMedia', {
        writable: true,
        configurable: true,
        value: (media) => ({
            media,
            matches: false,
            onchange: null,
            addEventListener() {},
            removeEventListener() {},
            addListener() {},
            removeListener() {},
            dispatchEvent: () => false,
        }),
    });
}
