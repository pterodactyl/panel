/** @vitest-environment jsdom */
import { fireEvent } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { TouchScrollAddon } from './TouchScrollAddon';

const LINE_HEIGHT = 10;

const createTerminal = (scrollbackLength: number) => {
    const terminal = {
        element: document.createElement('div'),
        viewportY: 0,
        getViewportY: () => terminal.viewportY,
        scrollLines: (amount: number) => {
            terminal.viewportY = Math.max(0, Math.min(scrollbackLength, terminal.viewportY - amount));
        },
        renderer: { getMetrics: () => ({ width: 8, height: LINE_HEIGHT, baseline: 8 }) },
    };

    return terminal;
};

const touches = (...clientYs: number[]) => ({ touches: clientYs.map((clientY) => ({ clientY })) });

describe('TouchScrollAddon', () => {
    let terminal: ReturnType<typeof createTerminal>;
    let addon: TouchScrollAddon;

    beforeEach(() => {
        vi.useFakeTimers({ toFake: ['Date', 'performance', 'requestAnimationFrame', 'cancelAnimationFrame'] });
        terminal = createTerminal(1000);
        addon = new TouchScrollAddon(terminal);
        addon.activate();
    });

    afterEach(() => {
        addon.dispose();
        vi.useRealTimers();
    });

    /** Swipes from `from` to `to` in 16ms steps of `step` pixels, then lifts the finger. */
    const swipe = (from: number, to: number, step: number) => {
        fireEvent.touchStart(terminal.element, touches(from));
        for (let y = from + step; step > 0 ? y <= to : y >= to; y += step) {
            vi.advanceTimersByTime(16);
            fireEvent.touchMove(terminal.element, touches(y));
        }

        fireEvent.touchEnd(terminal.element, touches());
    };

    it('drags the history with the finger and keeps the page still', () => {
        fireEvent.touchStart(terminal.element, touches(200));

        expect(fireEvent.touchMove(terminal.element, touches(300))).toBe(false);
        expect(terminal.viewportY).toBe(10);

        fireEvent.touchMove(terminal.element, touches(250));
        expect(terminal.viewportY).toBe(5);
    });

    it('lets the page scroll once the history cannot move any further', () => {
        fireEvent.touchStart(terminal.element, touches(300));

        expect(fireEvent.touchMove(terminal.element, touches(200))).toBe(true);
        expect(terminal.viewportY).toBe(0);
    });

    it('leaves two-finger gestures to the browser', () => {
        fireEvent.touchStart(terminal.element, touches(200, 400));

        expect(fireEvent.touchMove(terminal.element, touches(300, 500))).toBe(true);
        expect(terminal.viewportY).toBe(0);
    });

    it('follows the remaining finger when one of two lifts', () => {
        fireEvent.touchStart(terminal.element, touches(200, 400));
        fireEvent.touchEnd(terminal.element, touches(400));
        fireEvent.touchMove(terminal.element, touches(450));

        expect(terminal.viewportY).toBe(5);
    });

    it('keeps a fast swipe moving after the finger lifts, then comes to rest', () => {
        swipe(200, 360, 80);
        const released = terminal.viewportY;

        vi.advanceTimersByTime(100);
        expect(terminal.viewportY).toBeGreaterThan(released);

        vi.advanceTimersByTime(5000);
        const settled = terminal.viewportY;

        vi.advanceTimersByTime(1000);
        expect(terminal.viewportY).toBe(settled);
    });

    it('does not fling when the finger stops before lifting', () => {
        fireEvent.touchStart(terminal.element, touches(200));
        vi.advanceTimersByTime(16);
        fireEvent.touchMove(terminal.element, touches(300));
        vi.advanceTimersByTime(500);
        fireEvent.touchEnd(terminal.element, touches());

        vi.advanceTimersByTime(1000);
        expect(terminal.viewportY).toBe(10);
    });

    it('stops a fling when the history is touched again', () => {
        swipe(200, 360, 80);
        vi.advanceTimersByTime(50);
        fireEvent.touchStart(terminal.element, touches(500));
        const caught = terminal.viewportY;

        vi.advanceTimersByTime(1000);
        expect(terminal.viewportY).toBe(caught);
    });

    it('stops listening and flinging once disposed', () => {
        swipe(200, 360, 80);
        addon.dispose();
        const disposed = terminal.viewportY;

        vi.advanceTimersByTime(1000);
        fireEvent.touchStart(terminal.element, touches(200));
        fireEvent.touchMove(terminal.element, touches(300));

        expect(terminal.viewportY).toBe(disposed);
    });
});
