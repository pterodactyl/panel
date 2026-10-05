/** @vitest-environment jsdom */
import { useRef } from 'react';
import { act, cleanup, render } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useAnimationClock, useElementVisible } from './chart';

let frames = new Map<number, FrameRequestCallback>();
let nextFrame = 0;
let observerCallback: IntersectionObserverCallback | null = null;
let renders = 0;

const runFrame = (timestamp: number) =>
    act(() => {
        const pending = [...frames.values()];
        frames = new Map();
        pending.forEach((callback) => callback(timestamp));
    });

const reportIntersection = (isIntersecting: boolean) =>
    act(() => observerCallback?.([{ isIntersecting } as IntersectionObserverEntry], {} as IntersectionObserver));

function Chart({ live }: { live: boolean }) {
    const ref = useRef<HTMLDivElement>(null);
    const visible = useElementVisible(ref);
    const now = useAnimationClock(live && visible);
    renders++;

    return <div ref={ref} data-now={now} />;
}

describe('chart animation clock', () => {
    beforeEach(() => {
        frames = new Map();
        renders = 0;
        observerCallback = null;
        vi.stubGlobal('requestAnimationFrame', (callback: FrameRequestCallback) => {
            frames.set(++nextFrame, callback);
            return nextFrame;
        });
        vi.stubGlobal('cancelAnimationFrame', (id: number) => frames.delete(id));
        vi.stubGlobal(
            'IntersectionObserver',
            class {
                constructor(callback: IntersectionObserverCallback) {
                    observerCallback = callback;
                }
                observe() {}
                disconnect() {}
            }
        );
    });

    afterEach(() => {
        cleanup();
        vi.unstubAllGlobals();
        vi.restoreAllMocks();
    });

    it('stays idle until the chart is on screen', async () => {
        const { container } = render(<Chart live />);
        const before = renders;

        await runFrame(1_000);
        await runFrame(2_000);

        expect(frames.size).toBe(0);
        expect(renders).toBe(before);

        await reportIntersection(true);
        await runFrame(3_000);

        expect(container.firstElementChild).toHaveAttribute('data-now', '3000');
    });

    it('stops ticking when the chart leaves the screen, the tab hides, or the server is not live', async () => {
        const { container, rerender } = render(<Chart live />);
        await reportIntersection(true);
        await runFrame(1_000);
        expect(container.firstElementChild).toHaveAttribute('data-now', '1000');

        await reportIntersection(false);
        expect(frames.size).toBe(0);

        await reportIntersection(true);
        const visibility = vi.spyOn(document, 'visibilityState', 'get').mockReturnValue('hidden');
        await act(() => document.dispatchEvent(new Event('visibilitychange')));
        expect(frames.size).toBe(0);

        visibility.mockReturnValue('visible');
        await act(() => document.dispatchEvent(new Event('visibilitychange')));
        await runFrame(2_000);
        expect(container.firstElementChild).toHaveAttribute('data-now', '2000');

        rerender(<Chart live={false} />);
        expect(frames.size).toBe(0);
        await runFrame(3_000);
        expect(container.firstElementChild).toHaveAttribute('data-now', '2000');
    });
});
