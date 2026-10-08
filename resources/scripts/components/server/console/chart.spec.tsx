/** @vitest-environment jsdom */
import { act, cleanup, render, waitFor } from '@testing-library/react';
import { afterEach, beforeAll, beforeEach, describe, expect, it, vi } from 'vitest';
import AreaChart from './AreaChart';
import type { ChartPoint } from './chart';

interface FakeAnimation {
    keyframes: Keyframe[];
    currentTime: number | null;
    pause: ReturnType<typeof vi.fn>;
    cancel: ReturnType<typeof vi.fn>;
}

let animations: FakeAnimation[] = [];
let resize: ResizeObserverCallback | null = null;

const SERIES = [{ dataKey: 'value', color: 'var(--chart-1)' }];

const reportWidth = (width: number) =>
    act(() => resize?.([{ contentRect: { width } } as ResizeObserverEntry], {} as ResizeObserver));

const renderChart = (data: ChartPoint[], live = true) =>
    render(<AreaChart data={data} series={SERIES} live={live} windowMs={20_000} suggestedMax={100} />);

describe('console area chart', () => {
    // The chart lazy-loads recharts; a cold import can outlast waitFor's timeout.
    beforeAll(() => import('recharts'));

    beforeEach(() => {
        animations = [];
        vi.spyOn(performance, 'now').mockReturnValue(10_000);
        vi.stubGlobal(
            'ResizeObserver',
            class {
                constructor(callback: ResizeObserverCallback) {
                    resize = callback;
                }
                observe() {}
                disconnect() {}
            }
        );
        // jsdom has no Web Animations API.
        Object.defineProperty(Element.prototype, 'animate', {
            configurable: true,
            value: (keyframes: Keyframe[]) => {
                const animation: FakeAnimation = { keyframes, currentTime: 0, pause: vi.fn(), cancel: vi.fn() };

                animations.push(animation);

                return animation;
            },
        });
    });

    afterEach(() => {
        cleanup();
        vi.unstubAllGlobals();
        vi.restoreAllMocks();
        Reflect.deleteProperty(Element.prototype, 'animate');
        resize = null;
    });

    it('starts the slide as far in as the newest sample is old', async () => {
        renderChart([
            { t: 9_000, value: 10 },
            { t: 9_800, value: 20 },
        ]);
        await reportWidth(572);

        await waitFor(() => expect(animations).toHaveLength(1));

        // 500px of plot shows 20s, so the 4s of slack beyond the right edge is 100px of travel.
        expect(animations[0].keyframes.at(-1)).toEqual({ transform: 'translateX(-100px)' });
        // The newest sample arrived 200ms ago, so the slide starts that far in.
        expect(animations[0].currentTime).toBe(200);
        expect(animations[0].pause).not.toHaveBeenCalled();
    });

    it('restarts the slide when a sample arrives and holds it while the server is not live', async () => {
        const { rerender } = renderChart([{ t: 9_800, value: 20 }]);

        await reportWidth(572);
        await waitFor(() => expect(animations).toHaveLength(1));

        rerender(
            <AreaChart
                data={[
                    { t: 9_800, value: 20 },
                    { t: 10_000, value: 30 },
                ]}
                series={SERIES}
                live
                windowMs={20_000}
                suggestedMax={100}
            />
        );

        expect(animations[0].cancel).toHaveBeenCalled();
        expect(animations).toHaveLength(2);
        expect(animations[1].currentTime).toBe(0);

        rerender(
            <AreaChart
                data={[
                    { t: 9_800, value: 20 },
                    { t: 10_000, value: 30 },
                ]}
                series={SERIES}
                live={false}
                windowMs={20_000}
                suggestedMax={100}
            />
        );

        expect(animations).toHaveLength(3);
        expect(animations[2].pause).toHaveBeenCalled();
    });

    it('does not animate before the first sample', async () => {
        renderChart([]);
        await reportWidth(572);
        await waitFor(() => expect(document.querySelector('.recharts-wrapper')).not.toBeNull());

        expect(animations).toHaveLength(0);
    });
});
