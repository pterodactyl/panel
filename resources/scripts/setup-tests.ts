import '@testing-library/jest-dom/vitest';

// jsdom lacks PointerEvent, which Base UI dispatches.
if (globalThis.window?.PointerEvent === undefined && globalThis.window?.MouseEvent) {
    Object.defineProperty(globalThis.window, 'PointerEvent', { value: globalThis.window.MouseEvent });
}

if (globalThis.window?.navigator.userAgent.includes('jsdom')) {
    globalThis.window.scrollTo = () => {};
}
