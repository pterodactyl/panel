import type { CanvasRenderer, ITerminalAddon, Terminal } from 'ghostty-web';

// Share of fling velocity kept per ms; iOS's normal scroll deceleration rate.
const FLING_DECELERATION = 0.998;
// Lines per ms below which a fling stops.
const FLING_MIN_VELOCITY = 0.005;
// A finger lifted within this many ms of its last move was still moving, so the history keeps going.
const FLING_RELEASE_MS = 100;
// Weight of the newest touchmove when smoothing swipe velocity.
const VELOCITY_SMOOTHING = 0.8;

/** The parts of a Ghostty terminal that touch scrolling drives. */
type ScrollableTerminal = Pick<Terminal, 'element' | 'getViewportY' | 'scrollLines'> & {
    renderer?: Pick<CanvasRenderer, 'getMetrics'>;
};

/**
 * Ghostty scrolls its history only on wheel events. This scrolls it with a one-finger
 * vertical swipe and lets a fast swipe keep going after the finger lifts. At the top or
 * bottom of the history the swipe is left alone, so the page scrolls instead.
 */
export class TouchScrollAddon implements ITerminalAddon {
    private readonly listeners = new AbortController();
    private lastY = 0;
    private lastTime = 0;
    private velocity = 0;
    private frame = 0;

    constructor(private readonly terminal: ScrollableTerminal) {}

    activate() {
        const element = this.terminal.element;

        if (!element) {
            throw new Error('TouchScrollAddon must be loaded after the terminal is opened.');
        }

        const signal = this.listeners.signal;

        element.addEventListener('touchstart', this.onTouchStart, { passive: true, signal });
        element.addEventListener('touchmove', this.onTouchMove, { passive: false, signal });
        element.addEventListener('touchend', this.onTouchEnd, { passive: true, signal });
    }

    dispose() {
        this.listeners.abort();
        cancelAnimationFrame(this.frame);
    }

    private readonly onTouchStart = (event: TouchEvent) => {
        cancelAnimationFrame(this.frame);
        this.anchor(event);
    };

    private readonly onTouchMove = (event: TouchEvent) => {
        // Two or more fingers is a pinch, which belongs to the browser.
        const touch = event.touches.length === 1 ? event.touches[0] : undefined;

        if (!touch) {
            return;
        }

        const lines = (this.lastY - touch.clientY) / (this.terminal.renderer?.getMetrics().height ?? 20);
        const instant = lines / Math.max(1, event.timeStamp - this.lastTime);

        this.velocity = VELOCITY_SMOOTHING * instant + (1 - VELOCITY_SMOOTHING) * this.velocity;
        this.lastY = touch.clientY;
        this.lastTime = event.timeStamp;

        if (this.scrollBy(lines) && event.cancelable) {
            event.preventDefault();
        }
    };

    private readonly onTouchEnd = (event: TouchEvent) => {
        if (event.touches.length > 0) {
            // A finger lifted mid-gesture; follow the one still down without jumping.
            this.anchor(event);
        } else if (event.timeStamp - this.lastTime < FLING_RELEASE_MS) {
            this.frame = requestAnimationFrame(this.fling(performance.now()));
        }
    };

    private anchor(event: TouchEvent) {
        this.lastY = event.touches[0]?.clientY ?? 0;
        this.lastTime = event.timeStamp;
        this.velocity = 0;
    }

    private fling(previous: number) {
        return (now: number) => {
            const elapsed = Math.max(0, now - previous);

            this.velocity *= FLING_DECELERATION ** elapsed;
            if (Math.abs(this.velocity) >= FLING_MIN_VELOCITY && this.scrollBy(this.velocity * elapsed)) {
                this.frame = requestAnimationFrame(this.fling(now));
            }
        };
    }

    /** Scrolls toward newer output for positive `lines`; false once the history can't move that way. */
    private scrollBy(lines: number) {
        const before = this.terminal.getViewportY();

        this.terminal.scrollLines(lines);

        return this.terminal.getViewportY() !== before;
    }
}
