import type { Terminal } from 'ghostty-web';

/** Where a terminal's viewport sits in its history. `viewportY` counts lines scrolled up from the newest output. */
export interface TerminalScroll {
    viewportY: number;
    scrollbackLength: number;
    rows: number;
}

/** The parts of a Ghostty terminal the store reads and listens to. */
type ScrollSource = Pick<Terminal, 'onScroll' | 'onResize' | 'write' | 'getViewportY' | 'getScrollbackLength' | 'rows'>;

const NO_HISTORY: TerminalScroll = { viewportY: 0, scrollbackLength: 0, rows: 0 };

const sameScroll = (a: TerminalScroll, b: TerminalScroll) =>
    a.viewportY === b.viewportY && a.scrollbackLength === b.scrollbackLength && a.rows === b.rows;

/**
 * Publishes a terminal's scroll position for `useSyncExternalStore`, at most once a frame so a
 * busy console doesn't re-render on every line. Ghostty reports scrolls and resizes but not the
 * history a write adds, so writes are watched as well.
 */
export class TerminalScrollStore {
    private terminal: ScrollSource | null = null;
    private snapshot = NO_HISTORY;
    private frame = 0;
    private readonly listeners = new Set<() => void>();

    readonly subscribe = (listener: () => void) => {
        this.listeners.add(listener);

        return () => {
            this.listeners.delete(listener);
        };
    };

    readonly getSnapshot = () => this.snapshot;

    /** Follows `terminal` until `detach()`; call it before the terminal is disposed. */
    attach(terminal: ScrollSource) {
        const write = terminal.write.bind(terminal);

        this.terminal = terminal;
        terminal.onScroll(this.schedule);
        terminal.onResize(this.schedule);
        terminal.write = (data, callback) => {
            write(data, callback);
            this.schedule();
        };

        this.schedule();
    }

    detach() {
        cancelAnimationFrame(this.frame);
        this.frame = 0;
        this.terminal = null;
        this.publish(NO_HISTORY);
    }

    private readonly schedule = () => {
        if (this.frame === 0) {
            this.frame = requestAnimationFrame(this.sync);
        }
    };

    private readonly sync = () => {
        this.frame = 0;
        if (this.terminal) {
            this.publish({
                viewportY: this.terminal.getViewportY(),
                scrollbackLength: this.terminal.getScrollbackLength(),
                rows: this.terminal.rows,
            });
        }
    };

    private publish(next: TerminalScroll) {
        if (sameScroll(next, this.snapshot)) {
            return;
        }

        this.snapshot = next;
        for (const listener of this.listeners) {
            listener();
        }
    }
}
