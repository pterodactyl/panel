// Base UI dispatches PointerEvent; jsdom supplies the MouseEvent fields these tests use.
if (globalThis.window?.PointerEvent === undefined && globalThis.window?.MouseEvent) {
    Object.defineProperty(globalThis.window, 'PointerEvent', {value: globalThis.window.MouseEvent});
}

// The memory router restores scroll positions; jsdom has no scrolling implementation.
if (globalThis.window?.navigator.userAgent.includes('jsdom')) {
    globalThis.window.scrollTo = () => {};
}
