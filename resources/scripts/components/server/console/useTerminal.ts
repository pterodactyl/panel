import { useCallback, useEffect, useRef, useState } from 'react';
import type { ITerminalOptions, ITheme } from 'ghostty-web';
import { FitAddon, Ghostty, Terminal, UrlRegexProvider } from 'ghostty-web';
import { getObjectKeys } from '@/lib/objects';
import { onThemeChange } from '@/lib/theme';

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
};

const countLineFeeds = (data: string | Uint8Array) =>
    (data instanceof Uint8Array ? data : new TextEncoder().encode(data)).filter((byte) => byte === 0x0a).length;

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

const renderWholeLines = (term: Terminal) => {
    const renderer = term.renderer;

    if (!renderer) {
        return;
    }

    const render = renderer.render.bind(renderer);

    renderer.render = (buffer, forceAll, viewportY = 0, scrollbackProvider, scrollbarOpacity) =>
        render(buffer, forceAll, Math.floor(viewportY), scrollbackProvider, scrollbarOpacity);
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
        let mountedFitAddon: FitAddon | null = null;
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
                const term = new Terminal({ ...terminalProps, ...style, ghostty });
                const fit = new FitAddon();

                term.open(mount);
                term.loadAddon(fit);
                fit.fit();
                fit.observeResize();

                term.registerLinkProvider(new UrlRegexProvider(term));
                preserveScrollPositionOnWrite(term);
                renderWholeLines(term);

                // The canvas has no DOM selection, so copy reads the terminal's own selection.
                term.attachCustomKeyEventHandler((e: KeyboardEvent) => {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'c') {
                        const selection = term.getSelection();

                        if (selection) {
                            navigator.clipboard?.writeText(selection).catch(() => {});

                            return false;
                        }
                    }

                    return true;
                });

                term.onScroll(() => {
                    if (!cancelled) {
                        setScrolledUp(term.getViewportY() > 0.5);
                    }
                });

                terminal.current = term;
                appliedStyle.current = JSON.stringify(style);
                mountedTerminal = term;
                mountedFitAddon = fit;
                setTerminalReady(true);

                // Re-fit once layout settles; a mount mid route-transition can leave the history unpainted.
                requestAnimationFrame(() =>
                    requestAnimationFrame(() => {
                        if (!cancelled) {
                            fit.fit();
                        }
                    })
                );
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
            mountedFitAddon?.dispose();
            mountedTerminal?.dispose();
            terminal.current = null;
            appliedStyle.current = null;
            setTerminalReady(false);
            setScrolledUp(false);
        };
    }, [revision]);

    return { ref, retry, scrolledUp, setScrolledUp, terminal, terminalError, terminalReady };
};
