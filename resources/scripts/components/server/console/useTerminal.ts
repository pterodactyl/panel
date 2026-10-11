import { useCallback, useEffect, useRef, useState } from 'react';
import type { ITerminalOptions, ITheme } from 'ghostty-web';
import { FitAddon, Ghostty, Terminal, UrlRegexProvider } from 'ghostty-web';
import { getObjectKeys } from '@/lib/objects';
import { onThemeChange } from '@/lib/theme';
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

    term.writeln = (data, callback) => {
        const payload = hasOutput || term.getScrollbackLength() > 0 ? prefixNewline(data) : data;

        hasOutput = true;
        term.write(payload, callback);
    };
};

const fitFullWidth = (term: Terminal, fit: FitAddon) => {
    const internalFit = toInternalFitAddon(fit);
    const originalFit = fit.fit.bind(fit);

    // Ghostty's FitAddon locks _isResizing for 50ms via setTimeout, which drops immediate post-layout re-fits.
    fit.fit = () => {
        internalFit._isResizing = false;
        originalFit();
    };

    // Ghostty renders its scrollbar on the canvas rather than in an external 15px DOM track.
    fit.proposeDimensions = () => {
        const element = term.element;
        const metrics = term.renderer?.getMetrics?.();

        if (
            !element ||
            !metrics ||
            metrics.width === 0 ||
            metrics.height === 0 ||
            element.clientWidth === 0 ||
            element.clientHeight === 0
        ) {
            return;
        }

        const style = window.getComputedStyle(element);
        const padX = (Number.parseFloat(style.paddingLeft) || 0) + (Number.parseFloat(style.paddingRight) || 0);
        const padY = (Number.parseFloat(style.paddingTop) || 0) + (Number.parseFloat(style.paddingBottom) || 0);

        return {
            cols: Math.max(4, Math.floor(Math.max(0, element.clientWidth - padX) / metrics.width)),
            rows: Math.max(1, Math.floor(Math.max(0, element.clientHeight - padY) / metrics.height)),
        };
    };
};

const drawRoundedRect = (
    ctx: CanvasRenderingContext2D,
    x: number,
    y: number,
    width: number,
    height: number,
    radius: number
) => {
    const optionalRoundRectCtx: { roundRect?: CanvasRenderingContext2D['roundRect'] } = ctx;

    if (!optionalRoundRectCtx.roundRect) {
        ctx.fillRect(x, y, width, height);

        return;
    }

    ctx.beginPath();
    optionalRoundRectCtx.roundRect.call(ctx, x, y, width, height, radius);
    ctx.fill();
};

const measureTerminalFont = (fontSize = 12, fontFamily = 'monospace') => {
    const ctx = document.createElement('canvas').getContext('2d');

    if (!ctx) {
        return {
            width: Math.ceil(fontSize * 0.6),
            height: Math.ceil(fontSize * 1.35),
            baseline: Math.ceil(fontSize),
        };
    }

    ctx.font = `${fontSize}px ${fontFamily}`;
    const width = Math.max(1, Math.ceil(ctx.measureText('M').width));
    const box = ctx.measureText('Mgjpqy[]()_|');
    const ascent = box.actualBoundingBoxAscent || fontSize * 0.8;
    const descent = box.actualBoundingBoxDescent || fontSize * 0.2;

    return {
        width,
        height: Math.ceil(ascent + descent) + 2,
        baseline: Math.ceil(ascent) + 1,
    };
};

const drawScrollbar = (
    term: Terminal,
    renderer: InternalRenderer,
    viewportY: number,
    scrollbackLength: number,
    rows: number,
    opacity = 1
) => {
    const ctx = renderer.ctx;
    const metrics = renderer.getMetrics?.();
    const foreground = renderer.theme?.foreground;

    if (!ctx || !metrics || !foreground || opacity <= 0 || scrollbackLength <= 0) {
        return;
    }

    const barWidth = Math.min(6, Math.max(4, metrics.width - 2));
    const x = term.cols * metrics.width - barWidth;
    const trackHeight = rows * metrics.height - 4;
    const thumbHeight = Math.max(20, (rows / (scrollbackLength + rows)) * trackHeight);
    const clampedY = Math.max(0, Math.min(scrollbackLength, viewportY));
    const thumbY = 2 + (trackHeight - thumbHeight) * (1 - clampedY / scrollbackLength);

    ctx.save();
    ctx.fillStyle = foreground;
    ctx.globalAlpha = 0.12 * opacity;
    drawRoundedRect(ctx, x, 2, barWidth, trackHeight, barWidth / 2);
    ctx.globalAlpha = (clampedY > 0 ? 0.5 : 0.35) * opacity;
    drawRoundedRect(ctx, x, thumbY, barWidth, thumbHeight, barWidth / 2);
    ctx.restore();
};

const configureScrollbarGutter = (term: Terminal, internalTerm: InternalTerminal, renderer: InternalRenderer) => {
    const wasmTerm = internalTerm.wasmTerm;

    if (!wasmTerm) {
        return;
    }

    const getLine = wasmTerm.getLine?.bind(wasmTerm);
    const getScrollbackLine = wasmTerm.getScrollbackLine?.bind(wasmTerm);

    wasmTerm.getLine = getLine ? (row) => getLine(row) ?? [] : wasmTerm.getLine;
    wasmTerm.getScrollbackLine = getScrollbackLine
        ? (offset) => getScrollbackLine(offset) ?? []
        : wasmTerm.getScrollbackLine;
    wasmTerm.getDimensions = () => ({ cols: term.cols, rows: term.rows });

    if (!wasmTerm.resize || !renderer.resize) {
        return;
    }

    wasmTerm.resize(Math.max(2, term.cols - 2), term.rows);
    renderer.resize(term.cols, term.rows);

    term.resize = (newCols, newRows) => {
        if (newCols === term.cols && newRows === term.rows) {
            return;
        }

        term.cols = newCols;
        term.rows = newRows;
        wasmTerm.resize?.(Math.max(2, newCols - 2), newRows);
        renderer.resize?.(newCols, newRows);

        const clampedViewportY = Math.min(term.getViewportY(), term.getScrollbackLength());

        internalTerm.viewportY = clampedViewportY;
        internalTerm.targetViewportY = clampedViewportY;
        internalTerm.resizeEmitter?.fire({ cols: newCols, rows: newRows });
        renderer.render(wasmTerm, true, clampedViewportY, term);
    };
};

/** Wheel and touch scrolling leave the viewport between lines; draw it snapped to a whole line. */
const renderWholeLines = (term: Terminal) => {
    const internalTerm = toInternalTerminal(term);
    const renderer = internalTerm.renderer;

    if (!renderer) {
        return;
    }

    renderer.cursorVisible = false;
    renderer.devicePixelRatio = Math.max(1, Math.round(window.devicePixelRatio || 1));
    renderer.measureFont = () => measureTerminalFont(renderer.fontSize, renderer.fontFamily);
    renderer.remeasureFont?.();
    renderer.renderScrollbar = (viewportY, scrollbackLength, rows, opacity) =>
        drawScrollbar(term, renderer, viewportY, scrollbackLength, rows, opacity);

    configureScrollbarGutter(term, internalTerm, renderer);

    const render = renderer.render.bind(renderer);
    let lastViewportY = 0;
    let lastOpacity = 0;

    renderer.render = (buffer, forceAll, viewportY = 0, scrollbackProvider, scrollbarOpacity) => {
        const opacity = scrollbarOpacity ?? internalTerm.scrollbarOpacity ?? 0;
        const maxScrollback = scrollbackProvider?.getScrollbackLength?.() ?? Number.POSITIVE_INFINITY;
        const wholeViewportY = Math.max(0, Math.min(maxScrollback, Math.floor(viewportY)));
        const forceRedraw = Boolean(forceAll) || wholeViewportY !== lastViewportY || opacity !== lastOpacity;
        const metrics = renderer.getMetrics?.();

        lastViewportY = wholeViewportY;
        lastOpacity = opacity;

        if (forceRedraw && renderer.ctx && renderer.theme?.background && metrics) {
            renderer.ctx.fillStyle = renderer.theme.background;
            renderer.ctx.fillRect(0, 0, term.cols * metrics.width, term.rows * metrics.height);
        }

        return render(buffer, forceRedraw, wholeViewportY, scrollbackProvider, opacity);
    };
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

/** Opens a read-only console terminal in `mount`, sized to fill it. */
const openTerminal = (mount: HTMLElement, options: ITerminalOptions) => {
    const term = new Terminal({ ...terminalProps, ...options });
    const fit = new FitAddon();

    // Ghostty makes the mount editable and focuses it on open, then focuses its hidden textarea on
    // every tap, even with stdin disabled. Commands go through the console's own input, so keep the
    // on-screen keyboard closed for both.
    mount.setAttribute('inputmode', 'none');
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
    const [scrolledUp, setScrolledUp] = useState(false);
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
                const { term, fit } = openTerminal(mount, { ...style, ghostty });

                term.onScroll(() => {
                    if (!cancelled) {
                        setScrolledUp(Math.floor(term.getViewportY()) > 0);
                    }
                });

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
            // Also disposes the terminal's addons.
            mountedTerminal?.dispose();
            terminal.current = null;
            appliedStyle.current = null;
            setTerminalReady(false);
            setScrolledUp(false);
        };
    }, [revision]);

    return { ref, retry, scrolledUp, setScrolledUp, terminal, terminalError, terminalReady };
};
