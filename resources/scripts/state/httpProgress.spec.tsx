/** @vitest-environment jsdom */
import { act, renderHook } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

describe('HTTP progress store', () => {
    beforeEach(() => vi.resetModules());

    it('waits for every concurrent request before completing', async () => {
        const progress = await import('@/state/httpProgress');
        const { result } = renderHook(() => progress.useHttpProgress());

        act(() => {
            progress.startHttpProgress();
            progress.setHttpProgress(25);
            progress.startHttpProgress();
        });
        expect(result.current).toEqual({ continuous: true, progress: 25 });

        act(() => progress.completeHttpProgress());
        expect(result.current).toEqual({ continuous: true, progress: 25 });

        act(() => progress.completeHttpProgress());
        expect(result.current).toEqual({ continuous: false, progress: 100 });
    });

    it('does not flash a progress bar for a request that completes before progress advances', async () => {
        const progress = await import('@/state/httpProgress');
        const { result } = renderHook(() => progress.useHttpProgress());

        act(() => {
            progress.startHttpProgress();
            progress.completeHttpProgress();
        });

        expect(result.current).toEqual({ continuous: false, progress: undefined });
    });
});
