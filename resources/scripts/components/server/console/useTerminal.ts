import { useCallback, useEffect, useRef, useState } from 'react';
import type { ITerminalOptions, ITheme } from 'ghostty-web';
import { FitAddon, Ghostty, Terminal, UrlRegexProvider } from 'ghostty-web';
import { getObjectKeys } from '@/lib/objects';
import { onThemeChange } from '@/lib/theme';
import { TerminalScrollStore } from '@/components/server/console/TerminalScrollStore';
import { TouchScrollAddon } from '@/components/server/console/TouchScrollAddon';

// Ghostty reads a slot that resolves to pure black (#000000) as unset.
const TERMINAL_COLOR_VARS = {
    background: '--terminal-background',
    foreground: '--terminal-foreground',
    cursor: '--terminal-cursor',
    cursorAccent: '--terminal-cursor',
    selectionBackground: '--terminal-selection',
    selectionForeground: '--terminal-selection-foreground',
    black: '--terminal-ansi-black',
    red: '--terminal-ansi-red',
    green: '--terminal-ansi-green',
    yellow: '--terminal-ansi-yellow',
    blue: '--terminal-ansi-blue',
    magenta: '--terminal-ansi-magenta',
    cyan: '--terminal-ansi-cyan',
    white: '--terminal-ansi-white',
    brightBlack: '--terminal-ansi-bright-black',
    brightRed: '--terminal-ansi-bright-red',
    brightGreen: '--terminal-ansi-bright-green',
    brightYellow: '--terminal-ansi-bright-yellow',
    brightBlue: '--terminal-ansi-bright-blue',
    brightMagenta: '--terminal-ansi-bright-magenta',
    brightCyan: '--terminal-ansi-bright-cyan',
    brightWhite: '--terminal-ansi-bright-white',
} satisfies Partial<Record<keyof ITheme, string>>;

const buildTerminalStyle = (): Pick<ITerminalOptions, 'fontFamily' | 'fontSize' | 'theme'> => {
    const probe = document.createElement('span');

    probe.style.cssText = 'position:absolute;visibility:hidden;pointer-events:none';
    probe.style.fontFamily = 'var(--font-mono)';
    probe.style.fontSize = 'var(--text-xs)';
    document.body.appendChild(probe);
    // Ghostty only parses hex/rgb(a); a canvas fillStyle converts oklch and other colours.
    const ctx = document.createElement('canvas').getContext('2d');
    const theme: ITheme = {};

    for (const slot of getObjectKeys(TERMINAL_COLOR_VARS)) {
        const cssVar = TERMINAL_COLOR_VARS[slot];

        probe.style.color = `var(${cssVar})`;
        const resolved = getComputedStyle(probe).color;

        if (ctx) {
            ctx.fillStyle = resolved;
            theme[slot] = ctx.fillStyle;
        } else {
            theme[slot] = resolved;
        }
    }

    const computedStyle = getComputedStyle(probe);
    const fontSize = Number.parseFloat(computedStyle.fontSize);
    const fontFamily = computedStyle.fontFamily;

    probe.remove();

    if (!Number.isFinite(fontSize) || fontFamily.length === 0) {
        throw new Error('Terminal font tokens could not be resolved.');
    }

    return { fontFamily, fontSize, theme };
};

const terminalProps: ITerminalOptions = {
    disableStdin: true,
    cursorStyle: 'underline',
    cursorBlink: false,
    allowTransparency: true,
    smoothScrollDuration: 0,
    rows: 30,
    // Ghostty eases wheel scrolls toward a target it doesn't move when output arrives, so on a busy
    // console each new line dragged the view forward and swallowed most of the scroll.
    smoothScrollDuration: 0,
};

const countLineFeeds = (data: string | Uint8Array) =>
    (data instanceof Uint8Array ? data : new TextEncoder().encode(data)).filter((byte) => byte === 0x0a).length;

type InternalWasmTerm = {
    write?: (data: string | Uint8Array) => void;
    resize?: (cols: number, rows: number) => void;
    getDimensions?: () => { cols: number; rows: number };
    getLine?: (row: number) => unknown[] | null;
    getScrollbackLine?: (offset: number) => unknown[] | null;
    getScrollbackLength?: () => number;
};

type InternalScrollbackProvider = {
    getScrollbackLine?: (offset: number) => unknown[] | null;
    getScrollbackLength?: () => number;
};

type InternalRenderer = {
    ctx?: CanvasRenderingContext2D;
    canvas?: HTMLCanvasElement;
    theme?: ITheme;
    fontSize?: number;
    fontFamily?: string;
    devicePixelRatio?: number;
    cursorVisible?: boolean;
    metrics?: { width: number; height: number; baseline: number };
    getMetrics?: () => { width: number; height: number; baseline: number };
    measureFont?: () => { width: number; height: number; baseline: number };
    remeasureFont?: () => void;
    resize?: (cols: number, rows: number) => void;
    clear?: () => void;
    renderScrollbar?: (viewportY: number, scrollbackLength: number, rows: number, opacity?: number) => void;
    render: (
        buffer: InternalWasmTerm,
        forceAll?: boolean,
        viewportY?: number,
        scrollbackProvider?: InternalScrollbackProvider,
        scrollbarOpacity?: number
    ) => void;
};

type InternalTerminal = {
    wasmTerm?: InternalWasmTerm;
    renderer?: InternalRenderer;
    viewportY?: number;
    targetViewportY?: number;
    scrollbarOpacity?: number;
    resizeEmitter?: { fire: (size: { cols: number; rows: number }) => void };
};

type InternalFitAddon = {
    _isResizing?: boolean;
};

const toInternalTerminal = (term: Pick<Terminal, keyof Terminal>): InternalTerminal => term as InternalTerminal;

const toInternalFitAddon = (fit: Pick<FitAddon, keyof FitAddon>): InternalFitAddon => fit as InternalFitAddon;

const prefixNewline = (data: string | Uint8Array): string | Uint8Array => {
    if (!(data instanceof Uint8Array)) {
        return `\r\n${data}`;
    }

    const prefixed = new Uint8Array(data.length + 2);

    prefixed[0] = 0x0d;
    prefixed[1] = 0x0a;
    prefixed.set(data, 2);

    return prefixed;
};

/** Ghostty jumps to the bottom on every write; keep a reader who scrolled up on the same lines, even as old history is trimmed. */
const preserveScrollPositionOnWrite = (term: Terminal) => {
    const internalTerm = toInternalTerminal(term);
    const write = term.write.bind(term);
    const clear = term.clear?.bind(term);
    let hasOutput = false;

    term.clear = () => {
        hasOutput = false;

        if (!internalTerm.wasmTerm?.write) {
            clear?.();

            return;
        }

        internalTerm.wasmTerm.write('\x1b[2J\x1b[3J\x1b[H');
        term.scrollToBottom?.();
        internalTerm.viewportY = 0;
        internalTerm.targetViewportY = 0;
        internalTerm.renderer?.clear?.();
    };

    term.write = (data, callback) => {
        hasOutput ||= (data instanceof Uint8Array ? data.byteLength : data.length) > 0;
        const viewportY = Math.floor(term.getViewportY());

        if (viewportY <= 0) {
            write(data, callback);

            return;
        }

        const scrollbackLength = term.getScrollbackLength();

        write(data, callback);
        const grown = term.getScrollbackLength() - scrollbackLength;

        term.scrollLines(-(viewportY + (grown < 0 ? countLineFeeds(data) : grown)));
    };
};

/**
 * Draws the viewport snapped to a whole line, since wheel and touch scrolling leave it between
 * lines. Never draws Ghostty's scrollbar: it paints over the last columns of output rather than
 * the gutter FitAddon reserves, so ConsoleScrollbar draws one in the gutter instead.
 */
const adjustRendering = (term: Terminal) => {
    const renderer = term.renderer;

    if (!renderer) {
        return;
    }

    renderer.cursorVisible = false;
    renderer.devicePixelRatio = Math.max(1, Math.round(window.devicePixelRatio || 1));
    renderer.measureFont = () => measureTerminalFont(renderer.fontSize, renderer.fontFamily);
    renderer.remeasureFont?.();
    renderer.renderScrollbar = (viewportY, scrollbackLength, rows, opacity) =>
        drawScrollbar(term, renderer, viewportY, scrollbackLength, rows, opacity);

    renderer.render = (buffer, forceAll, viewportY = 0, scrollbackProvider) =>
        render(buffer, forceAll, Math.floor(viewportY), scrollbackProvider, 0);
};

// Ghostty's scrollbar hit area: the canvas's right 12px (an 8px bar inset 4px from the edge).
const CANVAS_SCROLLBAR_HIT_WIDTH = 12;

/**
 * Ghostty still treats a press on the right edge of the canvas as grabbing the scrollbar it no
 * longer draws, which would jump the history when someone clicks the end of a line. Registered
 * before `open()` so it runs ahead of Ghostty's own listener on the mount.
 */
const ignoreCanvasScrollbarPresses = (mount: HTMLElement, signal: AbortSignal) => {
    mount.addEventListener(
        'mousedown',
        (event) => {
            const canvas = event.target;

            if (
                canvas instanceof HTMLCanvasElement &&
                event.offsetX >= canvas.clientWidth - CANVAS_SCROLLBAR_HIT_WIDTH
            ) {
                event.stopImmediatePropagation();
            }
        },
        { capture: true, signal }
    );
};

/** The canvas has no DOM selection, so copy reads the terminal's own selection. */
const copySelectionOnShortcut = (term: Terminal) => {
    term.attachCustomKeyEventHandler((e: KeyboardEvent) => {
        if (!(e.ctrlKey || e.metaKey) || e.key !== 'c') {
            return true;
        }

        const selection = term.getSelection();

        if (!selection) {
            return true;
        }

        navigator.clipboard?.writeText(selection).catch(() => {});

        return false;
    });
};

/** Opens a read-only console terminal in `mount`, sized to fill it; `signal` removes its listeners on the mount. */
const openTerminal = (mount: HTMLElement, options: ITerminalOptions, signal: AbortSignal) => {
    const term = new Terminal({ ...terminalProps, ...options });
    const fit = new FitAddon();

    // Ghostty makes the mount editable and focuses it on open, then focuses its hidden textarea on
    // every tap, even with stdin disabled. Commands go through the console's own input, so keep the
    // on-screen keyboard closed for both.
    mount.setAttribute('inputmode', 'none');
    ignoreCanvasScrollbarPresses(mount, signal);
    term.open(mount);
    term.textarea?.setAttribute('inputmode', 'none');

    term.loadAddon(fit);
    term.loadAddon(new TouchScrollAddon(term));
    fitFullWidth(term, fit);
    preserveScrollPositionOnWrite(term);
    renderWholeLines(term);
    fit.fit();
    fit.observeResize();

    term.registerLinkProvider(new UrlRegexProvider(term));
    preserveScrollPositionOnWrite(term);
    adjustRendering(term);
    copySelectionOnShortcut(term);

    return { term, fit };
};

/** Runs after the browser has laid out and painted the next frame. */
const afterNextLayout = (callback: () => void) => {
    requestAnimationFrame(() => requestAnimationFrame(callback));
};

const hasStyleChanged = (appliedStyle: string) => {
    try {
        return JSON.stringify(buildTerminalStyle()) !== appliedStyle;
    } catch {
        return false;
    }
};

export const useTerminal = () => {
    const ref = useRef<HTMLDivElement>(null);
    const terminal = useRef<Terminal | null>(null);
    const [terminalReady, setTerminalReady] = useState(false);
    const [scroll] = useState(() => new TerminalScrollStore());
    const [terminalError, setTerminalError] = useState<Error | null>(null);
    const [revision, setRevision] = useState(0);
    const appliedStyle = useRef<string | null>(null);

    // Ghostty fixes the palette when a terminal is created, so a theme change rebuilds it.
    useEffect(
        () =>
            onThemeChange(() => {
                if (appliedStyle.current !== null && hasStyleChanged(appliedStyle.current)) {
                    setRevision((current) => current + 1);
                }
            }),
        []
    );

    const retry = useCallback(() => {
        setTerminalError(null);
        setRevision((current) => current + 1);
    }, []);

    useEffect(() => {
        let cancelled = false;
        let mountedTerminal: Terminal | null = null;
        const listeners = new AbortController();
        const mount = ref.current;

        if (!mount) {
            return;
        }

        Ghostty.load()
            .then((ghostty) => {
                if (cancelled) {
                    return;
                }

                const style = buildTerminalStyle();
                const { term, fit } = openTerminal(mount, { ...style, ghostty }, listeners.signal);

                scroll.attach(term);
                terminal.current = term;
                appliedStyle.current = JSON.stringify(style);
                mountedTerminal = term;
                setTerminalReady(true);

                // Re-fit once layout settles; a mount mid route-transition can leave the history unpainted.
                afterNextLayout(() => {
                    if (!cancelled) {
                        fit.fit();
                    }
                });
            })
            .catch((error) => {
                if (cancelled) {
                    return;
                }

                console.error('Failed to load the console terminal.', error);
                setTerminalError(error instanceof Error ? error : new Error('Failed to load the console terminal.'));
            });

        return () => {
            cancelled = true;
            listeners.abort();
            scroll.detach();
            // Also disposes the terminal's addons.
            mountedTerminal?.dispose();
            terminal.current = null;
            appliedStyle.current = null;
            setTerminalReady(false);
        };
    }, [revision, scroll]);

    return { ref, retry, scroll, terminal, terminalError, terminalReady };
};
