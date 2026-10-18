import { describe, expect, it } from 'vitest';
import type { ILink } from 'ghostty-web';
import { WrappedUrlProvider } from './WrappedUrlProvider';

const COLS = 20;

const createTerminal = (rows: string[], scrollbackLength = rows.length, wrapped: number[] = []) => ({
    getScrollbackLength: () => scrollbackLength,
    buffer: {
        active: {
            getLine: (y: number) => {
                const text = rows[y];

                if (text === undefined) {
                    return undefined;
                }

                const cells = Array.from(text.padEnd(COLS, '\0'));

                return {
                    length: COLS,
                    isWrapped: wrapped.includes(y),
                    getCell: (x: number) =>
                        x < 0 || x >= COLS ? undefined : { getCodepoint: () => cells[x]?.codePointAt(0) ?? 0 },
                };
            },
        },
    },
});

const linksOn = (terminal: ReturnType<typeof createTerminal>, y: number) => {
    let provided: ILink[] | undefined;

    new WrappedUrlProvider(terminal as never).provideLinks(y, (links) => {
        provided = links;
    });

    return provided?.map(({ text, range }) => ({ text, range }));
};

const historyLinksOn = (rows: string[], y: number) => linksOn(createTerminal(rows), y);

describe('WrappedUrlProvider', () => {
    it('finds a link that fits on one row', () => {
        expect(historyLinksOn(['go https://a.co/x now'], 0)).toEqual([
            { text: 'https://a.co/x', range: { start: { x: 3, y: 0 }, end: { x: 16, y: 0 } } },
        ]);
    });

    it('joins a link that fills its row in the history onto the next row', () => {
        const rows = ['see https://example.', 'com/path/to/page ok'];
        const link = {
            text: 'https://example.com/path/to/page',
            range: { start: { x: 4, y: 0 }, end: { x: 15, y: 1 } },
        };

        expect(historyLinksOn(rows, 0)).toEqual([link]);
        expect(historyLinksOn(rows, 1)).toEqual([link]);
    });

    it('joins a link across several rows', () => {
        const rows = ['https://example.com/', 'aaaaaaaaaaaaaaaaaaaa', 'bbbb'];

        expect(historyLinksOn(rows, 1)?.[0].text).toBe('https://example.com/aaaaaaaaaaaaaaaaaaaabbbb');
        expect(historyLinksOn(rows, 2)?.[0].range).toEqual({ start: { x: 0, y: 0 }, end: { x: 3, y: 2 } });
    });

    it('does not join a history row that ends short of the last column', () => {
        expect(historyLinksOn(['https://example.co', 'm/a'], 0)?.[0].text).toBe('https://example.co');
        expect(historyLinksOn(['https://example.co', 'm/a'], 1)).toBeUndefined();
    });

    it("trusts the screen's own wrap flag over a row that happens to fill the width", () => {
        const rows = ['see https://example.', 'com/a', 'https://example.com/', 'next line'];
        const terminal = createTerminal(rows, 0, [1]);

        expect(linksOn(terminal, 1)?.[0].text).toBe('https://example.com/a');
        expect(linksOn(terminal, 2)?.[0].text).toBe('https://example.com/');
        expect(linksOn(terminal, 3)).toBeUndefined();
    });

    it('only provides links that touch the requested row', () => {
        const rows = ['https://one.example/', ' plain', 'https://two.example'];

        expect(historyLinksOn(rows, 2)?.map((link) => link.text)).toEqual(['https://two.example']);
    });

    it('drops trailing punctuation and very short matches', () => {
        expect(historyLinksOn(['(https://a.co/b).'], 0)?.[0].text).toBe('https://a.co/b');
        expect(historyLinksOn(['http://a'], 0)).toBeUndefined();
    });

    it('finds only http and https links', () => {
        expect(historyLinksOn(['mailto:a@b.c ssh://h'], 0)).toBeUndefined();
    });
});
