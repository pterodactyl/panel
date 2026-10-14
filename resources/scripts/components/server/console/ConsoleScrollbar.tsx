import { useRef, useSyncExternalStore } from 'react';
import type { CSSProperties, PointerEvent, RefObject } from 'react';
import type { Terminal } from 'ghostty-web';
import type { TerminalScrollStore } from '@/components/server/console/TerminalScrollStore';

// Shortest the thumb gets, so a long history still leaves something to grab.
const MIN_THUMB_HEIGHT = '1.25rem';

/** The parts of a Ghostty terminal the scrollbar moves. */
type ScrollableTerminal = Pick<Terminal, 'getScrollbackLength' | 'getViewportY' | 'scrollLines'>;

interface ConsoleScrollbarProps {
    scroll: TerminalScrollStore;
    terminal: RefObject<ScrollableTerminal | null>;
}

/**
 * Ghostty paints its own scrollbar over the last columns of output, so the console draws this
 * one in the gutter FitAddon leaves beside the canvas instead. It is a pointer control only;
 * wheel, touch and keyboard scrolling reach the terminal directly.
 */
export default function ConsoleScrollbar({ scroll, terminal }: ConsoleScrollbarProps) {
    const { viewportY, scrollbackLength, rows } = useSyncExternalStore(scroll.subscribe, scroll.getSnapshot);
    const track = useRef<HTMLDivElement>(null);
    const thumb = useRef<HTMLDivElement>(null);
    // Where the pointer holds the thumb, measured from the thumb's top, while it is pressed.
    const grab = useRef<number | null>(null);

    if (scrollbackLength === 0) {
        return null;
    }

    const height = `max(${MIN_THUMB_HEIGHT}, var(--thumb-size))`;
    const thumbStyle = {
        // The share of the history in view, and where the view sits: 0 at the oldest output, 1 at the newest.
        '--thumb-size': `${(rows / (rows + scrollbackLength)) * 100}%`,
        '--thumb-position': 1 - viewportY / scrollbackLength,
        height,
        top: `calc((100% - ${height}) * var(--thumb-position))`,
    } as CSSProperties;

    const moveThumbTo = (clientY: number) => {
        const term = terminal.current;

        if (!term || !track.current || !thumb.current || grab.current === null) {
            return;
        }

        const { top, height: trackHeight } = track.current.getBoundingClientRect();
        const travel = trackHeight - thumb.current.offsetHeight;
        const target = travel > 0 ? Math.min(1, Math.max(0, (clientY - top - grab.current) / travel)) : 1;

        term.scrollLines(term.getViewportY() - term.getScrollbackLength() * (1 - target));
    };

    const onPointerDown = (event: PointerEvent<HTMLDivElement>) => {
        if (event.button !== 0 || !thumb.current) {
            return;
        }

        // Pressing the track centres the thumb under the pointer, then drags from there.
        grab.current =
            event.target === thumb.current
                ? event.clientY - thumb.current.getBoundingClientRect().top
                : thumb.current.offsetHeight / 2;
        event.currentTarget.setPointerCapture(event.pointerId);
        event.preventDefault();
        moveThumbTo(event.clientY);
    };

    const release = () => {
        grab.current = null;
    };

    return (
        // The track fills FitAddon's 15px gutter so a finger can grab it; the thumb inside stays 8px.
        <div
            ref={track}
            aria-hidden
            className='group absolute inset-y-1 right-0 w-3.5 touch-none'
            onPointerDown={onPointerDown}
            onPointerMove={(event) => moveThumbTo(event.clientY)}
            onPointerUp={release}
            onPointerCancel={release}
        >
            <div
                ref={thumb}
                className='absolute inset-x-0.75 rounded-full bg-scrollbar-thumb-fill transition-colors hover:bg-scrollbar-thumb group-active:bg-scrollbar-thumb'
                style={thumbStyle}
            />
        </div>
    );
}
