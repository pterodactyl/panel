/** @vitest-environment jsdom */
import { afterEach, expect, it, vi } from 'vitest';
import { startExtensionDevelopmentReload } from './development';

afterEach(() => {
    vi.useRealTimers();
    vi.unstubAllGlobals();
    document.head.innerHTML = '';
});

const version = 'a'.repeat(64);
const scripts = 'c'.repeat(64);
const marker = (next: string, nextScripts = scripts) =>
    new Response(JSON.stringify({ version: next, scripts: nextScripts }));
const entry = {
    id: 'probe',
    entry: '/probe.js',
    development: { url: '/assets/extensions/probe/_development', version, scripts },
};

it('reloads once after a rebuild that changed scripts and ignores failed responses', async () => {
    vi.useFakeTimers();
    const reload = vi.fn();
    const fetcher = vi
        .fn()
        .mockRejectedValueOnce(new Error('offline'))
        .mockResolvedValueOnce(marker(version))
        .mockResolvedValueOnce(marker('b'.repeat(64), 'd'.repeat(64)));

    vi.stubGlobal('fetch', fetcher);
    const stop = startExtensionDevelopmentReload([entry], reload);

    try {
        await vi.advanceTimersByTimeAsync(1500);
        expect(reload).not.toHaveBeenCalled();
        await vi.advanceTimersByTimeAsync(1500);
        expect(reload).not.toHaveBeenCalled();
        await vi.advanceTimersByTimeAsync(1500);
        expect(reload).toHaveBeenCalledTimes(1);
        await vi.advanceTimersByTimeAsync(6000);
        expect(fetcher).toHaveBeenCalledTimes(3);
    } finally {
        stop();
    }
});

it('swaps the stylesheets of a CSS-only rebuild in place without reloading', async () => {
    vi.useFakeTimers();
    const next = 'b'.repeat(64);
    const later = 'e'.repeat(64);

    document.head.innerHTML = [
        `<link rel="stylesheet" href="/assets/extensions/probe/${version}/theme.css">`,
        '<link rel="stylesheet" href="/assets/stylesheet.css">',
    ].join('');
    const reload = vi.fn();
    const fetcher = vi.fn().mockResolvedValueOnce(marker(next)).mockResolvedValueOnce(marker(later));

    vi.stubGlobal('fetch', fetcher);
    const stop = startExtensionDevelopmentReload([entry], reload);

    try {
        await vi.advanceTimersByTimeAsync(1500);
        const links = () => [...document.querySelectorAll('link')].map((link) => link.getAttribute('href'));

        expect(reload).not.toHaveBeenCalled();
        expect(links()).toEqual([
            `/assets/extensions/probe/${version}/theme.css`,
            `/assets/extensions/probe/${next}/theme.css`,
            '/assets/stylesheet.css',
        ]);

        // The old link goes once its replacement has loaded, and the next swap starts from the new build.
        document.querySelectorAll('link')[1].dispatchEvent(new Event('load'));
        expect(links()).toEqual([`/assets/extensions/probe/${next}/theme.css`, '/assets/stylesheet.css']);

        await vi.advanceTimersByTimeAsync(1500);
        expect(links()).toContain(`/assets/extensions/probe/${later}/theme.css`);
        expect(reload).not.toHaveBeenCalled();
    } finally {
        stop();
    }
});

it('stops after the development command removes its marker and leaves normal extensions idle', async () => {
    vi.useFakeTimers();
    const fetcher = vi.fn().mockResolvedValue(new Response('', { status: 404 }));

    vi.stubGlobal('fetch', fetcher);
    const reload = vi.fn();
    const stop = startExtensionDevelopmentReload([entry], reload);
    const regular = startExtensionDevelopmentReload([{ development: null }], reload);

    try {
        await vi.advanceTimersByTimeAsync(1500);
        await vi.advanceTimersByTimeAsync(6000);
        expect(fetcher).toHaveBeenCalledTimes(1);
        expect(reload).not.toHaveBeenCalled();
    } finally {
        stop();
        regular();
    }
});
