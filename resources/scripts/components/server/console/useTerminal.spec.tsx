/** @vitest-environment jsdom */
import { act, cleanup, fireEvent, render, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { CanvasRenderer, IRenderable, ITerminalAddon, ITerminalOptions } from 'ghostty-web';
import type { notifyThemeChange as NotifyThemeChange } from '@/lib/theme';
import type { useTerminal as UseTerminal } from './useTerminal';

// Ghostty doesn't export the interface itself.
type ScrollbackProvider = Parameters<CanvasRenderer['render']>[3];

type MockTerminal = {
    options: ITerminalOptions;
    dispose: ReturnType<typeof vi.fn>;
    viewportY: number;
    scrollbackLength: number;
    scrollbackLimit: number;
    renderedViewportY: number | undefined;
    renderedScrollbarOpacity: number | undefined;
    renderer: {
        render: (
            buffer: IRenderable,
            forceAll: boolean,
            viewportY: number,
            scrollbackProvider?: ScrollbackProvider,
            scrollbarOpacity?: number
        ) => void;
    };
    canvas: HTMLCanvasElement;
    canvasPressed: ReturnType<typeof vi.fn>;
    selectionPressed: ReturnType<typeof vi.fn>;
    lines: string[];
    wrappedLines: Set<number>;
    selectionManager: {
        selectionStart: { col: number; absoluteRow: number } | null;
        selectionEnd: { col: number; absoluteRow: number } | null;
        copyToClipboard: ReturnType<typeof vi.fn>;
        getWordAtCell: (col: number, row: number) => { startCol: number; endCol: number } | null;
        getSelection: () => string;
    };
    write: (data: string) => void;
    writeln: (data: string) => void;
};

const terminals: MockTerminal[] = [];
const ghostty = vi.hoisted(() => ({ load: vi.fn<() => Promise<object>>() }));

vi.mock('ghostty-web', () => ({
    Ghostty: { load: () => ghostty.load() },
    FitAddon: class {
        activate() {}
        fit() {}
        observeResize() {}
        dispose() {}
    },
    OSC8LinkProvider: class {},
    UrlRegexProvider: class {},
    Terminal: class {
        dispose = vi.fn();
        viewportY = 0;
        scrollbackLength = 0;
        scrollbackLimit = Number.POSITIVE_INFINITY;
        renderedViewportY: number | undefined;
        renderedScrollbarOpacity: number | undefined;
        renderer = {
            render: (
                _buffer: IRenderable,
                _forceAll: boolean,
                viewportY: number,
                _scrollbackProvider?: ScrollbackProvider,
                scrollbarOpacity = 1
            ) => {
                this.renderedViewportY = viewportY;
                this.renderedScrollbarOpacity = scrollbarOpacity;
            },
            getMetrics: () => ({ width: 8, height: 10, baseline: 8 }),
        };
        cols = 80;
        rows = 30;
        element: HTMLElement | undefined;
        textarea: HTMLTextAreaElement | undefined;
        canvas = document.createElement('canvas');
        // Stands in for Ghostty's own capture listener, which hit-tests its scrollbar.
        canvasPressed = vi.fn();
        // Stands in for Ghostty's selection listener on the canvas.
        selectionPressed = vi.fn();
        lines: string[] = [];
        wrappedLines = new Set<number>();
        selectionManager = {
            selectionStart: null as { col: number; absoluteRow: number } | null,
            selectionEnd: null as { col: number; absoluteRow: number } | null,
            selectionChangedEmitter: { fire: () => {} },
            copyToClipboard: vi.fn(async () => {}),
            getWordAtCell: (): { startCol: number; endCol: number } | null => null,
            getSelection: () => {
                const { selectionStart: start, selectionEnd: end } = this.selectionManager;

                return start && end
                    ? this.lines
                          .slice(start.absoluteRow, end.absoluteRow + 1)
                          .map((line) => line.replaceAll('\0', ' ').trimEnd())
                          .join('\n')
                    : '';
            },
            markCurrentSelectionDirty: () => {},
            requestRender: () => {},
        };
        buffer = {
            active: {
                getLine: (y: number) => {
                    const text = this.lines[y];

                    return text === undefined
                        ? undefined
                        : {
                              length: this.cols,
                              isWrapped: this.wrappedLines.has(y),
                              getCell: (x: number) =>
                                  x < 0 || x >= this.cols
                                      ? undefined
                                      : {
                                            getChars: () => (text[x] ?? '\0').replace('\0', ''),
                                            getCodepoint: () => text.codePointAt(x) ?? 0,
                                            getWidth: () => 1,
                                        },
                          };
                },
            },
        };
        constructor(public options: ITerminalOptions) {
            terminals.push(this);
        }
        open(element: HTMLElement) {
            this.element = element;
            element.addEventListener('mousedown', this.canvasPressed, { capture: true });
            element.appendChild(this.canvas);
            this.canvas.addEventListener('mousedown', this.selectionPressed);
            this.textarea = element.appendChild(document.createElement('textarea'));
        }
        onResize() {}
        loadAddon(addon: ITerminalAddon) {
            addon.activate(this);
        }
        registerLinkProvider() {}
        attachCustomKeyEventHandler() {}
        onScroll() {}
        getViewportY() {
            return this.viewportY;
        }
        getScrollbackLength() {
            return this.scrollbackLength;
        }
        scrollLines(amount: number) {
            this.viewportY = Math.max(0, Math.min(this.scrollbackLength, this.viewportY - amount));
        }
        write(data: string) {
            this.scrollbackLength += data.split('\n').length - 1;
            if (this.scrollbackLength > this.scrollbackLimit) {
                this.scrollbackLength -= this.scrollbackLimit / 2;
            }

            this.viewportY = 0;
        }
        writeln(data: string) {
            this.write(`${data}\r\n`);
        }
    },
}));

// jsdom resolves neither var() nor canvas colours, so probes read `var(--token)` from this map.
const tokens = new Map<string, string>();
let fontSize = '12px';

let useTerminal: typeof UseTerminal;
let notifyThemeChange: typeof NotifyThemeChange;

function Harness() {
    const { ref, retry, terminalError, terminalReady } = useTerminal();

    return (
        <div ref={ref} data-ready={terminalReady} data-error={terminalError?.message ?? ''}>
            <button type='button' onClick={retry}>
                Retry
            </button>
        </div>
    );
}

const frame = () => act(() => new Promise<void>((resolve) => requestAnimationFrame(() => resolve())));

describe('useTerminal', () => {
    beforeEach(async () => {
        vi.resetModules();
        ({ useTerminal } = await import('./useTerminal'));
        ({ notifyThemeChange } = await import('@/lib/theme'));
        ghostty.load.mockReset();
        ghostty.load.mockImplementation(async () => ({}));
        fontSize = '12px';
        terminals.length = 0;
        tokens.clear();
        tokens.set('--terminal-foreground', '#cccccc');
        tokens.set('--terminal-background', '#1e2430');
        vi.spyOn(HTMLCanvasElement.prototype, 'getContext').mockReturnValue(null);
        vi.spyOn(window, 'getComputedStyle').mockImplementation((element) => {
            const declared = (element as HTMLElement).style.color;
            const token = /^var\((--[\w-]+)\)$/.exec(declared)?.[1];

            return {
                color: token ? (tokens.get(token) ?? '#000001') : declared,
                fontFamily: 'monospace',
                fontSize,
            } as CSSStyleDeclaration;
        });
    });

    afterEach(() => {
        cleanup();
        vi.restoreAllMocks();
    });

    it('hands every terminal token, including the default text colour, to Ghostty', async () => {
        render(<Harness />);
        await waitFor(() => expect(terminals).toHaveLength(1));

        const theme = terminals[0].options.theme ?? {};

        expect(theme.foreground).toBe('#cccccc');
        expect(theme.background).toBe('#1e2430');
        expect(Object.keys(theme)).toHaveLength(22);
    });

    it('converts tokens in oklch() and other syntaxes to the hex or rgba() Ghostty parses', async () => {
        // jsdom has no canvas, so this one paints the pixels Chrome paints for these colours.
        const pixels = new Map([
            ['oklch(0.8 0.12 150)', [130, 212, 148, 255]],
            ['oklch(0.3 0.05 260 / 50%)', [32, 46, 72, 128]],
        ]);
        let fill: CanvasRenderingContext2D['fillStyle'] = 'transparent';
        const canvas = {
            clearRect(_x: number, _y: number, _w: number, _h: number) {},
            fillRect(_x: number, _y: number, _w: number, _h: number) {},
            get fillStyle() {
                return fill;
            },
            set fillStyle(color: CanvasRenderingContext2D['fillStyle']) {
                fill = color;
            },
            getImageData(_x: number, _y: number, _w: number, _h: number): ImageData {
                const data = new Uint8ClampedArray(pixels.get(String(fill)) ?? [0, 0, 0, 0]);

                return { colorSpace: 'srgb', data, width: 1, height: 1 };
            },
        };

        vi.mocked(HTMLCanvasElement.prototype.getContext).mockReturnValue(canvas as CanvasRenderingContext2D);
        tokens.set('--terminal-foreground', 'oklch(0.8 0.12 150)');
        tokens.set('--terminal-selection', 'oklch(0.3 0.05 260 / 50%)');

        render(<Harness />);
        await waitFor(() => expect(terminals).toHaveLength(1));

        expect(terminals[0].options.theme?.foreground).toBe('#82d494');
        expect(terminals[0].options.theme?.selectionBackground).toBe('rgba(32, 46, 72, 0.502)');
    });

    it('scrolls without easing, so new output cannot drag a scrolled-up view along', async () => {
        render(<Harness />);
        await waitFor(() => expect(terminals).toHaveLength(1));

        expect(terminals[0].options.smoothScrollDuration).toBe(0);
    });

    it('keeps the on-screen keyboard closed when the read-only terminal takes focus', async () => {
        const { container } = render(<Harness />);

        await waitFor(() => expect(container.firstElementChild).toHaveAttribute('data-ready', 'true'));

        expect(container.firstElementChild).toHaveAttribute('inputmode', 'none');
        expect(container.querySelector('textarea')).toHaveAttribute('inputmode', 'none');
    });

    it('keeps the page where it is when a press focuses the terminal', async () => {
        const focus = vi.spyOn(HTMLElement.prototype, 'focus');
        const { container } = render(<Harness />);

        await waitFor(() => expect(container.firstElementChild).toHaveAttribute('data-ready', 'true'));

        const mount = container.firstElementChild as HTMLElement;
        const textarea = container.querySelector('textarea') as HTMLTextAreaElement;

        mount.focus();
        textarea.focus();

        expect(focus.mock.contexts).toEqual([mount, textarea]);
        expect(focus.mock.calls).toEqual([[{ preventScroll: true }], [{ preventScroll: true }]]);
    });

    it('scrolls the history with a vertical swipe', async () => {
        const { container } = render(<Harness />);

        await waitFor(() => expect(container.firstElementChild).toHaveAttribute('data-ready', 'true'));

        const mount = container.firstElementChild!;

        terminals[0].scrollbackLength = 100;
        fireEvent.touchStart(mount, { touches: [{ clientY: 200 }] });
        fireEvent.touchMove(mount, { touches: [{ clientY: 300 }] });

        expect(terminals[0].viewportY).toBe(10);
    });

    it('keeps a scrolled-up viewport on the same lines when new output arrives', async () => {
        const { container } = render(<Harness />);

        await waitFor(() => expect(container.firstElementChild).toHaveAttribute('data-ready', 'true'));

        const term = terminals[0];

        term.scrollbackLength = 100;
        term.viewportY = 20;
        term.writeln('one');
        term.write('two\r\nthree\r\n');

        expect(term.viewportY).toBe(23);
    });

    it('keeps a scrolled-up viewport on the same lines when old history is trimmed', async () => {
        const { container } = render(<Harness />);

        await waitFor(() => expect(container.firstElementChild).toHaveAttribute('data-ready', 'true'));

        const term = terminals[0];

        term.scrollbackLimit = 100;
        term.scrollbackLength = 100;
        term.viewportY = 20;
        term.writeln('one');

        expect(term.scrollbackLength).toBe(51);
        expect(term.viewportY).toBe(21);
    });

    it('renders a fractional scroll position as whole lines', async () => {
        const { container } = render(<Harness />);

        await waitFor(() => expect(container.firstElementChild).toHaveAttribute('data-ready', 'true'));

        const buffer: IRenderable = {
            getLine: () => null,
            getCursor: () => ({ x: 0, y: 0, visible: false }),
            getDimensions: () => ({ cols: 80, rows: 24 }),
            isRowDirty: () => false,
            clearDirty: () => {},
        };

        terminals[0].renderer.render(buffer, false, 4.29, undefined, 1);

        expect(terminals[0].renderedViewportY).toBe(4);
        // Ghostty's scrollbar would cover the last columns; ConsoleScrollbar draws one in the gutter.
        expect(terminals[0].renderedScrollbarOpacity).toBe(0);
    });

    it("keeps presses on the canvas's right edge from grabbing Ghostty's hidden scrollbar", async () => {
        const { container } = render(<Harness />);

        await waitFor(() => expect(container.firstElementChild).toHaveAttribute('data-ready', 'true'));

        const { canvas, canvasPressed } = terminals[0];

        Object.defineProperty(canvas, 'clientWidth', { value: 600 });
        const press = (offsetX: number) => {
            const event = new MouseEvent('mousedown', { bubbles: true });

            Object.defineProperty(event, 'offsetX', { value: offsetX });
            canvas.dispatchEvent(event);
        };

        press(595);
        expect(canvasPressed).not.toHaveBeenCalled();

        press(300);
        expect(canvasPressed).toHaveBeenCalledOnce();
    });

    it('selects the whole word under a double-click on the line drawn there', async () => {
        const { container } = render(<Harness />);

        await waitFor(() => expect(container.firstElementChild).toHaveAttribute('data-ready', 'true'));

        const term = terminals[0];
        const wordAt = (col: number, row: number) => {
            const word = term.selectionManager.getWordAtCell(col, row);

            return (
                word &&
                term.lines[term.scrollbackLength + row - Math.floor(term.viewportY)].slice(
                    word.startCol,
                    word.endCol + 1
                )
            );
        };

        term.scrollbackLength = 2;
        term.lines = [
            '[12:00:01 INFO]: Joined 127.0.0.1:25565.',
            '<Steve_99> see https://example.com/a-b!',
            'live screen line\0\0\0',
        ];

        expect(wordAt(2, 0)).toBe('live');
        expect(wordAt(14, 0)).toBe('line');
        expect(wordAt(11, 0)).toBeNull();
        expect(wordAt(17, 0)).toBeNull();

        term.viewportY = 2.6;

        expect(wordAt(3, 0)).toBe('12:00:01');
        expect(wordAt(11, 0)).toBe('INFO');
        expect(wordAt(30, 0)).toBe('127.0.0.1:25565');
        expect(wordAt(3, 1)).toBe('Steve_99');
        expect(wordAt(20, 1)).toBe('https://example.com/a-b');
        expect(wordAt(9, 1)).toBeNull();
    });

    it('selects and copies the whole line, wrapped rows included, on a triple-click', async () => {
        const { container } = render(<Harness />);

        await waitFor(() => expect(container.firstElementChild).toHaveAttribute('data-ready', 'true'));

        const term = terminals[0];
        const press = (offsetY: number, detail: number) => {
            const event = new MouseEvent('mousedown', { bubbles: true, cancelable: true, detail });

            Object.defineProperty(event, 'offsetX', { value: 40 });
            Object.defineProperty(event, 'offsetY', { value: offsetY });
            term.canvas.dispatchEvent(event);

            return event;
        };

        Object.defineProperty(term.canvas, 'clientWidth', { value: 640 });
        const head = `long line start ${'-'.repeat(64)}`;

        term.scrollbackLength = 4;
        term.lines = ['before', head, 'and its wrapped end', 'after', 'live'];
        term.viewportY = 3.5;

        press(15, 1);
        press(15, 2);
        expect(term.selectionPressed).toHaveBeenCalledTimes(2);
        expect(term.selectionManager.selectionStart).toBeNull();

        const tripleClick = press(15, 3);

        expect(tripleClick.defaultPrevented).toBe(true);
        expect(term.selectionPressed).toHaveBeenCalledTimes(2);
        expect(term.selectionManager.selectionStart).toEqual({ col: 0, absoluteRow: 1 });
        expect(term.selectionManager.selectionEnd).toEqual({ col: 79, absoluteRow: 2 });
        expect(term.selectionManager.copyToClipboard).toHaveBeenCalledWith(`${head}and its wrapped end`);
    });

    it('copies a selection without breaking lines where they only wrapped', async () => {
        const { container } = render(<Harness />);

        await waitFor(() => expect(container.firstElementChild).toHaveAttribute('data-ready', 'true'));

        const term = terminals[0];
        const history = `history ${'a'.repeat(72)}`;

        term.scrollbackLength = 2;
        term.lines = [
            history,
            'continues here',
            'screen line'.padEnd(80, ' '),
            'wrapped onto the next row',
            'own line',
        ];
        term.wrappedLines.add(3);
        term.selectionManager.selectionStart = { col: 0, absoluteRow: 0 };
        term.selectionManager.selectionEnd = { col: 79, absoluteRow: 4 };

        expect(term.selectionManager.getSelection()).toBe(
            `${history}continues here\nscreen line wrapped onto the next row\nown line`
        );
    });

    it('follows new output when the viewport is at the bottom', async () => {
        const { container } = render(<Harness />);

        await waitFor(() => expect(container.firstElementChild).toHaveAttribute('data-ready', 'true'));

        const term = terminals[0];

        term.scrollbackLength = 100;
        term.writeln('one');

        expect(term.viewportY).toBe(0);
    });

    it('rebuilds the terminal when a theme changes the palette, and only then', async () => {
        const { container } = render(<Harness />);

        await waitFor(() => expect(container.firstElementChild).toHaveAttribute('data-ready', 'true'));

        notifyThemeChange();
        await frame();
        expect(terminals).toHaveLength(1);
        expect(terminals[0].dispose).not.toHaveBeenCalled();

        tokens.set('--terminal-foreground', '#f3f5f9');
        notifyThemeChange();
        await frame();
        await waitFor(() => expect(terminals).toHaveLength(2));

        expect(terminals[0].dispose).toHaveBeenCalledTimes(1);
        expect(terminals[1].options.theme?.foreground).toBe('#f3f5f9');
        expect(terminals[1].options.ghostty).toBeDefined();
        expect(terminals[1].options.ghostty).not.toBe(terminals[0].options.ghostty);
        await waitFor(() => expect(container.firstElementChild).toHaveAttribute('data-ready', 'true'));
    });

    it('reports a failed module load and loads the module again on retry', async () => {
        vi.spyOn(console, 'error').mockImplementation(() => {});
        ghostty.load.mockRejectedValueOnce(new Error('WASM unavailable'));
        const { container, getByRole } = render(<Harness />);

        await waitFor(() => expect(container.firstElementChild).toHaveAttribute('data-error', 'WASM unavailable'));
        expect(terminals).toHaveLength(0);

        fireEvent.click(getByRole('button'));

        await waitFor(() => expect(container.firstElementChild).toHaveAttribute('data-ready', 'true'));
        expect(container.firstElementChild).toHaveAttribute('data-error', '');
        expect(ghostty.load).toHaveBeenCalledTimes(2);
    });

    it('reports terminal styles that cannot be resolved instead of loading forever', async () => {
        vi.spyOn(console, 'error').mockImplementation(() => {});
        fontSize = 'auto';
        const { container, getByRole } = render(<Harness />);

        await waitFor(() =>
            expect(container.firstElementChild).toHaveAttribute(
                'data-error',
                'Terminal font tokens could not be resolved.'
            )
        );

        fontSize = '12px';
        fireEvent.click(getByRole('button'));

        await waitFor(() => expect(terminals).toHaveLength(1));
        await waitFor(() => expect(container.firstElementChild).toHaveAttribute('data-ready', 'true'));
    });
});
