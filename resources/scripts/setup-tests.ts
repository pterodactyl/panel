import '@testing-library/jest-dom/vitest';
import { classicSkins } from '@/themes/classic/skins';
import { registerSkins } from '@/themes/skins';

// jsdom lacks PointerEvent, which Base UI dispatches.
if (globalThis.window?.PointerEvent === undefined && globalThis.window?.MouseEvent) {
    Object.defineProperty(globalThis.window, 'PointerEvent', { value: globalThis.window.MouseEvent });
}

if (globalThis.window?.navigator.userAgent.includes('jsdom')) {
    globalThis.window.scrollTo = () => {};
}

// The panel registers classic before its first render; components under test render without booting.
registerSkins('classic', classicSkins);
