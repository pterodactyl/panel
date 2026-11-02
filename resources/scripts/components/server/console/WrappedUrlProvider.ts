import type { IBufferCellPosition, ILink, ILinkProvider } from 'ghostty-web';
import { type WrappedRowSource, wrappedRowsAround } from '@/components/server/console/wrappedRows';

const URL_REGEX = /https?:\/\/[\w\-.~:/?#@!$&*+,;=%]+/gi;
const TRAILING_PUNCTUATION = /[.,;!?)\]]+$/;
const MIN_URL_LENGTH = 9;

export class WrappedUrlProvider implements ILinkProvider {
    constructor(private readonly terminal: WrappedRowSource) {}

    provideLinks(y: number, callback: (links: ILink[] | undefined) => void) {
        const { text, positions } = this.joinedRowsAround(y);
        const links: ILink[] = [];

        for (const match of text.matchAll(URL_REGEX)) {
            const url = match[0].replace(TRAILING_PUNCTUATION, '');

            if (url.length < MIN_URL_LENGTH) {
                continue;
            }

            const start = positions[match.index];
            const end = positions[match.index + url.length - 1];

            if (start.y <= y && end.y >= y) {
                links.push({ text: url, range: { start, end }, activate: () => {} });
            }
        }

        callback(links.length ? links : undefined);
    }

    private joinedRowsAround(y: number) {
        const buffer = this.terminal.buffer.active;
        const { first, last } = wrappedRowsAround(this.terminal, y);
        let text = '';
        const positions: IBufferCellPosition[] = [];

        for (let row = first; row <= last; row++) {
            const line = buffer.getLine(row);

            for (let x = 0; line && x < line.length; x++) {
                const codepoint = line.getCell(x)?.getCodepoint() ?? 0;
                const char = codepoint < 32 ? ' ' : String.fromCodePoint(codepoint);

                text += char;

                for (let unit = 0; unit < char.length; unit++) {
                    positions.push({ x, y: row });
                }
            }
        }

        return { text, positions };
    }
}
