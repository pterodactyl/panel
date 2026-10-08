/** @vitest-environment jsdom */
import { afterEach, expect, it, vi } from 'vitest';
import { startExtensionDevelopmentReload } from './development';

afterEach(() => {
    vi.useRealTimers();
    vi.unstubAllGlobals();
});
const version = 'a'.repeat(64);
const entry = {
    id: 'probe',
    entry: '/probe.js',
    development: { url: '/assets/extensions/probe/_development', version },
};

it('reloads once after a successful replacement and ignores failed responses', async () => {
    vi.useFakeTimers();
    const reload = vi.fn();
    const fetcher = vi
        .fn()
        .mockRejectedValueOnce(new Error('offline'))
        .mockResolvedValueOnce(new Response(version))
        .mockResolvedValueOnce(new Response('b'.repeat(64)));

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

it('stops after the development command removes its marker and leaves normal extensions idle', async () => {
    vi.useFakeTimers();
    const fetcher = vi.fn().mockResolvedValue(new Response('', { status: 404 }));

    vi.stubGlobal('fetch', fetcher);
    const reload = vi.fn();
    const stop = startExtensionDevelopmentReload([entry], reload);
    const regular = startExtensionDevelopmentReload([{ id: 'normal', entry: '/normal.js' }], reload);

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
