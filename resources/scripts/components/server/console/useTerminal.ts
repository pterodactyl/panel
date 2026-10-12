import { useCallback, useEffect, useRef, useState } from 'react';
import type * as GhosttyWeb from 'ghostty-web';
import type { ITerminalOptions, ITheme, Terminal } from 'ghostty-web';
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
    rows: 30,
    // Ghostty eases wheel scrolls toward a target it doesn't move when output arrives, so on a busy
    // console each new line dragged the view forward and swallowed most of the scroll.
    smoothScrollDuration: 0,
};

const countLineFeeds = (data: string | Uint8Array) =>
    (data instanceof Uint8Array ? data : new TextEncoder().encode(data)).filter((byte) => byte === 0x0a).length;

/** Ghostty jumps to the bottom on every write; keep a reader who scrolled up on the same lines, even as old history is trimmed. */
const preserveScrollPositionOnWrite = (term: Terminal) => {
    const write = term.write.bind(term);

    term.write = (data, callback) => {
        const viewportY = term.getViewportY();

        if (viewportY === 0) {
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

    const render = renderer.render.bind(renderer);

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
const openTerminal = (
    { FitAddon, Terminal: GhosttyTerminal, UrlRegexProvider }: typeof GhosttyWeb,
    mount: HTMLElement,
    options: ITerminalOptions,
    signal: AbortSignal
) => {
    const term = new GhosttyTerminal({ ...terminalProps, ...options });
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

/** Opens the console terminal in the element given to `ref`; Ghostty loads with the first one. */
export const useTerminal = () => {
    const [mount, ref] = useState<HTMLDivElement | null>(null);
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

        if (!mount) {
            return;
        }

        import('ghostty-web')
            .then(async (module) => ({ module, ghostty: await module.Ghostty.load() }))
            .then(({ module, ghostty }) => {
                if (cancelled) {
                    return;
                }

                const style = buildTerminalStyle();
                const { term, fit } = openTerminal(module, mount, { ...style, ghostty }, listeners.signal);

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
                if (!cancelled) {
                    console.error('Failed to load the console terminal.', error);
                    setTerminalError(
                        error instanceof Error ? error : new Error('Failed to load the console terminal.')
                    );
                }
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
    }, [mount, revision, scroll]);

    return { ref, retry, scroll, terminal, terminalError, terminalReady };
};
