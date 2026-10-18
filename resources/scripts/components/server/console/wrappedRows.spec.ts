import { describe, expect, it } from 'vitest';
import { continuesPreviousRow, wrappedRowsAround } from './wrappedRows';

const COLS = 10;

const createTerminal = (rows: string[], scrollbackLength: number, wrapped: number[] = []) => ({
    getScrollbackLength: () => scrollbackLength,
    buffer: {
        active: {
            getLine: (y: number) => {
                const text = rows[y];

                return text === undefined
                    ? undefined
                    : {
                          length: COLS,
                          isWrapped: wrapped.includes(y),
                          getCell: (x: number) =>
                              x < 0 || x >= COLS ? undefined : { getCodepoint: () => text.codePointAt(x) ?? 0 },
                      };
            },
        },
    },
});

describe('continuesPreviousRow', () => {
    it("reads a screen row's own wrap flag, including the first screen row", () => {
        const terminal = createTerminal(['ABCDEFGHIJ', 'KLMNOPQRST', 'UVWXYZ', 'abcdefghij', 'next'], 1, [1, 2]);

        expect(continuesPreviousRow(terminal as never, 1)).toBe(true);
        expect(continuesPreviousRow(terminal as never, 2)).toBe(true);
        expect(continuesPreviousRow(terminal as never, 4)).toBe(false);
    });

    it('treats a history row as continued when the row before it fills the last column', () => {
        const terminal = createTerminal(['ABCDEFGHIJ', 'KLMNOPQRST', 'UVWXYZ', 'next', 'live'], 4);

        expect(continuesPreviousRow(terminal as never, 0)).toBe(false);
        expect(continuesPreviousRow(terminal as never, 1)).toBe(true);
        expect(continuesPreviousRow(terminal as never, 2)).toBe(true);
        expect(continuesPreviousRow(terminal as never, 3)).toBe(false);
    });

    it('treats a row as its own line when the row before ends in a space', () => {
        const terminal = createTerminal(['ABCDEFGHI ', 'next'], 2);

        expect(continuesPreviousRow(terminal as never, 1)).toBe(false);
    });
});

describe('wrappedRowsAround', () => {
    it('finds every row of a line wrapped across the history and the screen', () => {
        const terminal = createTerminal(['before', 'ABCDEFGHIJ', 'KLMNOPQRST', 'UVWXYZ', 'after'], 2, [2, 3]);

        expect(wrappedRowsAround(terminal as never, 1)).toEqual({ first: 1, last: 3 });
        expect(wrappedRowsAround(terminal as never, 3)).toEqual({ first: 1, last: 3 });
        expect(wrappedRowsAround(terminal as never, 4)).toEqual({ first: 4, last: 4 });
    });

    it('stops 64 rows either side of the starting row', () => {
        const terminal = createTerminal(Array.from({ length: 200 }, () => '=========='), 200);

        expect(wrappedRowsAround(terminal as never, 100)).toEqual({ first: 36, last: 164 });
    });
});
