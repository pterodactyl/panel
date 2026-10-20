import type { Terminal } from 'ghostty-web';

export type WrappedRowSource = Pick<Terminal, 'buffer' | 'getScrollbackLength'>;

const MAX_JOINED_ROWS = 64;

export const continuesPreviousRow = (terminal: WrappedRowSource, y: number) => {
    const buffer = terminal.buffer.active;
    const line = buffer.getLine(y);

    if (!line || y <= 0) {
        return false;
    }

    if (y >= terminal.getScrollbackLength()) {
        return line.isWrapped;
    }

    const previous = buffer.getLine(y - 1);

    return (previous?.getCell(previous.length - 1)?.getCodepoint() ?? 0) > 32;
};

export const wrappedRowsAround = (terminal: WrappedRowSource, y: number) => {
    let first = y;
    let last = y;

    while (y - first < MAX_JOINED_ROWS && continuesPreviousRow(terminal, first)) {
        first--;
    }

    while (last - y < MAX_JOINED_ROWS && continuesPreviousRow(terminal, last + 1)) {
        last++;
    }

    return { first, last };
};
