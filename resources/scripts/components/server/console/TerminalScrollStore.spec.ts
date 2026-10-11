/** @vitest-environment jsdom */
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { Mock } from 'vitest';
import { TerminalScrollStore } from './TerminalScrollStore';

const createTerminal = () => {
    const scrollListeners: ((viewportY: number) => void)[] = [];
    const terminal = {
        rows: 24,
        viewportY: 0,
        scrollbackLength: 0,
        onScroll: (listener: (viewportY: number) => void) => {
            scrollListeners.push(listener);

            return { dispose: () => {} };
        },
        onResize: () => ({ dispose: () => {} }),
        write: (data: string | Uint8Array) => {
            terminal.scrollbackLength += String(data).split('\n').length - 1;
        },
        getViewportY: () => terminal.viewportY,
        getScrollbackLength: () => terminal.scrollbackLength,
        scrollTo: (viewportY: number) => {
            terminal.viewportY = viewportY;
            for (const listener of scrollListeners) {
                listener(viewportY);
            }
        },
    };

    return terminal;
};

const nextFrame = () => vi.advanceTimersByTime(16);

describe('TerminalScrollStore', () => {
    let terminal: ReturnType<typeof createTerminal>;
    let store: TerminalScrollStore;
    let listener: Mock<() => void>;

    beforeEach(() => {
        vi.useFakeTimers({ toFake: ['requestAnimationFrame', 'cancelAnimationFrame'] });
        terminal = createTerminal();
        store = new TerminalScrollStore();
        listener = vi.fn<() => void>();
        store.subscribe(listener);
        terminal.scrollbackLength = 100;
        store.attach(terminal);
        nextFrame();
        listener.mockClear();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('publishes the position on attach', () => {
        expect(store.getSnapshot()).toEqual({ viewportY: 0, scrollbackLength: 100, rows: 24 });
    });

    it('publishes once a frame however often the terminal scrolls', () => {
        terminal.scrollTo(5);
        terminal.scrollTo(10);
        expect(listener).not.toHaveBeenCalled();

        nextFrame();
        expect(listener).toHaveBeenCalledOnce();
        expect(store.getSnapshot().viewportY).toBe(10);
    });

    it('picks up history a write adds without scrolling', () => {
        terminal.write('one\ntwo\n');
        nextFrame();

        expect(store.getSnapshot().scrollbackLength).toBe(102);
    });

    it('keeps the same snapshot when nothing moved', () => {
        const before = store.getSnapshot();

        terminal.scrollTo(0);
        nextFrame();

        expect(listener).not.toHaveBeenCalled();
        expect(store.getSnapshot()).toBe(before);
    });

    it('stops following the terminal once detached', () => {
        terminal.scrollTo(10);
        store.detach();
        nextFrame();

        expect(store.getSnapshot()).toEqual({ viewportY: 0, scrollbackLength: 0, rows: 0 });

        terminal.write('three\n');
        nextFrame();
        expect(store.getSnapshot().scrollbackLength).toBe(0);
    });
});
