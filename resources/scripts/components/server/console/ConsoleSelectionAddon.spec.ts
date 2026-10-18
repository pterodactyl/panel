/** @vitest-environment jsdom */
import { describe, expect, it, vi } from 'vitest';
import { ConsoleSelectionAddon } from './ConsoleSelectionAddon';

type SelectionPoint = { col: number; absoluteRow: number };

class FakeTerminal {
    cols = 80;
    rows = 30;
    viewportY = 0;
    scrollbackLength = 0;
    lines: string[] = [];
    wrappedLines = new Set<number>();
    element: HTMLElement | undefined = document.createElement('div');
    canvas = document.createElement('canvas');
    selectionPressed = vi.fn();
    renderer = { getMetrics: () => ({ width: 8, height: 10, baseline: 8 }) };
    selectionManager = {
        selectionStart: null as SelectionPoint | null,
        selectionEnd: null as SelectionPoint | null,
        selectionChangedEmitter: { fire: vi.fn() },
        copyToClipboard: vi.fn(async () => {}),
        getWordAtCell: (_col: number, _row: number): { startCol: number; endCol: number } | null => null,
        getSelection: (): string => {
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

    constructor() {
        this.element?.appendChild(this.canvas);
        this.canvas.addEventListener('mousedown', this.selectionPressed);
    }

    getViewportY() {
        return this.viewportY;
    }

    getScrollbackLength() {
        return this.scrollbackLength;
    }
}

const loaded = () => {
    const term = new FakeTerminal();
    const original = { ...term.selectionManager };
    const addon = new ConsoleSelectionAddon(term as never);

    addon.activate();

    return { term, addon, original };
};

const press = (term: FakeTerminal, offsetY: number, detail: number) => {
    const event = new MouseEvent('mousedown', { bubbles: true, cancelable: true, detail });

    Object.defineProperty(event, 'offsetX', { value: 40 });
    Object.defineProperty(event, 'offsetY', { value: offsetY });
    term.canvas.dispatchEvent(event);

    return event;
};

describe('ConsoleSelectionAddon', () => {
    it('selects the whole word under a double-click on the line drawn there', () => {
        const { term } = loaded();
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

    it('selects and copies the whole line, wrapped rows included, on a triple-click', () => {
        const { term } = loaded();
        const head = `long line start ${'-'.repeat(64)}`;

        term.scrollbackLength = 4;
        term.lines = ['before', head, 'and its wrapped end', 'after', 'live'];
        term.viewportY = 3.5;

        press(term, 15, 1);
        press(term, 15, 2);
        expect(term.selectionPressed).toHaveBeenCalledTimes(2);
        expect(term.selectionManager.selectionStart).toBeNull();

        const tripleClick = press(term, 15, 3);

        expect(tripleClick.defaultPrevented).toBe(true);
        expect(term.selectionPressed).toHaveBeenCalledTimes(2);
        expect(term.selectionManager.selectionStart).toEqual({ col: 0, absoluteRow: 1 });
        expect(term.selectionManager.selectionEnd).toEqual({ col: 79, absoluteRow: 2 });
        expect(term.selectionManager.copyToClipboard).toHaveBeenCalledWith(`${head}and its wrapped end`);
    });

    it('copies a selection without breaking lines where they only wrapped', () => {
        const { term } = loaded();
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

    it("puts Ghostty's word lookup and copy back, and stops handling triple-clicks, once disposed", () => {
        const { term, addon, original } = loaded();

        expect(term.selectionManager.getSelection).not.toBe(original.getSelection);

        addon.dispose();

        expect(term.selectionManager.getWordAtCell).toBe(original.getWordAtCell);
        expect(term.selectionManager.getSelection).toBe(original.getSelection);
        expect(press(term, 15, 3).defaultPrevented).toBe(false);
        expect(term.selectionPressed).toHaveBeenCalledOnce();
    });

    it('must be loaded after the terminal is opened', () => {
        const term = new FakeTerminal();

        term.element = undefined;

        expect(() => new ConsoleSelectionAddon(term as never).activate()).toThrow(
            'ConsoleSelectionAddon must be loaded after the terminal is opened.'
        );
    });
});
