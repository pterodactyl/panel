/** @vitest-environment jsdom */
import { act, cleanup, fireEvent, render } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import ConsoleScrollbar from './ConsoleScrollbar';
import { TerminalScrollStore } from './TerminalScrollStore';

// A 200px track starting 100px down the page, with a 48px thumb: 152px of travel.
const TRACK_TOP = 100;
const TRACK_HEIGHT = 200;
const THUMB_HEIGHT = 48;
const TRAVEL = TRACK_HEIGHT - THUMB_HEIGHT;

const createTerminal = (scrollbackLength: number) => {
    let scrollListener = (_viewportY: number) => {};

    const terminal = {
        rows: 24,
        viewportY: 0,
        onScroll: (listener: (viewportY: number) => void) => {
            scrollListener = listener;

            return { dispose: () => {} };
        },
        emitScroll: () => scrollListener(terminal.viewportY),
        onResize: () => ({ dispose: () => {} }),
        write: () => {},
        getViewportY: () => terminal.viewportY,
        getScrollbackLength: () => scrollbackLength,
        scrollLines: (amount: number) => {
            terminal.viewportY = Math.max(0, Math.min(scrollbackLength, terminal.viewportY - amount));
        },
    };

    return terminal;
};

const renderScrollbar = (scrollbackLength: number) => {
    const terminal = createTerminal(scrollbackLength);
    const store = new TerminalScrollStore();

    store.attach(terminal);
    act(() => {
        vi.advanceTimersByTime(16);
    });

    const { container } = render(<ConsoleScrollbar scroll={store} terminal={{ current: terminal }} />);
    const track = container.firstElementChild as HTMLElement | null;
    const thumb = track?.firstElementChild as HTMLElement | null;

    if (track && thumb) {
        const thumbTop = () => TRACK_TOP + TRAVEL * (1 - terminal.viewportY / scrollbackLength);

        track.getBoundingClientRect = () => DOMRect.fromRect({ y: TRACK_TOP, height: TRACK_HEIGHT });
        thumb.getBoundingClientRect = () => DOMRect.fromRect({ y: thumbTop(), height: THUMB_HEIGHT });
        Object.defineProperty(thumb, 'offsetHeight', { value: THUMB_HEIGHT });
    }

    return { terminal, track, thumb };
};

describe('ConsoleScrollbar', () => {
    beforeEach(() => {
        vi.useFakeTimers({ toFake: ['requestAnimationFrame', 'cancelAnimationFrame'] });
        HTMLElement.prototype.setPointerCapture = vi.fn();
    });

    afterEach(() => {
        cleanup();
        vi.useRealTimers();
    });

    it('draws nothing until there is history to scroll', () => {
        expect(renderScrollbar(0).track).toBeNull();
    });

    it('sizes the thumb to the share of history in view and follows the scroll position', () => {
        const { terminal, thumb } = renderScrollbar(76);

        expect(thumb?.style.getPropertyValue('--thumb-size')).toBe('24%');
        expect(thumb?.style.getPropertyValue('--thumb-position')).toBe('1');

        act(() => {
            terminal.scrollLines(-19);
            terminal.emitScroll();
            vi.advanceTimersByTime(16);
        });
        expect(thumb?.style.getPropertyValue('--thumb-position')).toBe('0.75');
    });

    it('scrolls the history with the thumb, keeping the point where it was grabbed under the pointer', () => {
        const { terminal, track, thumb } = renderScrollbar(76);
        const grabbedAt = TRACK_TOP + TRAVEL + 10;

        fireEvent.pointerDown(thumb!, { button: 0, pointerId: 1, clientY: grabbedAt });
        expect(terminal.viewportY).toBe(0);

        fireEvent.pointerMove(track!, { pointerId: 1, clientY: grabbedAt - TRAVEL });
        expect(terminal.viewportY).toBe(76);

        fireEvent.pointerMove(track!, { pointerId: 1, clientY: grabbedAt - TRAVEL / 2 });
        expect(terminal.viewportY).toBe(38);

        fireEvent.pointerUp(track!, { pointerId: 1 });
        fireEvent.pointerMove(track!, { pointerId: 1, clientY: grabbedAt });
        expect(terminal.viewportY).toBe(38);
    });

    it('centres the thumb on a press in the track', () => {
        const { terminal, track } = renderScrollbar(76);

        fireEvent.pointerDown(track!, { button: 0, pointerId: 1, clientY: TRACK_TOP + THUMB_HEIGHT / 2 });

        expect(terminal.viewportY).toBe(76);
    });
});
