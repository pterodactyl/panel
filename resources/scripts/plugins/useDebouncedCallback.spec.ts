/** @vitest-environment jsdom */

import { renderHook } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useDebouncedCallback } from './useDebouncedCallback';

beforeEach(() => {
    vi.useFakeTimers();
});

afterEach(() => {
    vi.useRealTimers();
});

describe('useDebouncedCallback', () => {
    it('calls the latest callback once with the last arguments', () => {
        const callback = vi.fn();
        const { result } = renderHook(() => useDebouncedCallback(callback, 300));

        result.current('a');
        result.current('b');
        vi.advanceTimersByTime(300);

        expect(callback).toHaveBeenCalledOnce();
        expect(callback).toHaveBeenCalledWith('b');
    });

    it('drops a pending call when cancelled', () => {
        const callback = vi.fn();
        const { result, unmount } = renderHook(() => useDebouncedCallback(callback, 300));

        result.current('a');
        result.current.cancel();
        vi.advanceTimersByTime(300);
        unmount();

        expect(callback).not.toHaveBeenCalled();
    });

    it('runs a pending call immediately when flushed', () => {
        const callback = vi.fn();
        const { result } = renderHook(() => useDebouncedCallback(callback, 300));

        result.current('a');
        result.current.flush();
        expect(callback).toHaveBeenCalledExactlyOnceWith('a');

        result.current.flush();
        vi.advanceTimersByTime(300);
        expect(callback).toHaveBeenCalledOnce();
    });

    it('runs a pending call with the latest callback on unmount', () => {
        const first = vi.fn();
        const latest = vi.fn();
        const { result, rerender, unmount } = renderHook(({ callback }) => useDebouncedCallback(callback, 300), {
            initialProps: { callback: first },
        });

        result.current('a');
        rerender({ callback: latest });
        unmount();

        expect(first).not.toHaveBeenCalled();
        expect(latest).toHaveBeenCalledExactlyOnceWith('a');
        vi.advanceTimersByTime(300);
        expect(latest).toHaveBeenCalledOnce();
    });

    it('does nothing on unmount without a pending call', () => {
        const callback = vi.fn();
        const { result, unmount } = renderHook(() => useDebouncedCallback(callback, 300));

        result.current('a');
        vi.advanceTimersByTime(300);
        unmount();

        expect(callback).toHaveBeenCalledOnce();
    });
});
