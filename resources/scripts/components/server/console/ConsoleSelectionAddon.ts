import type { ITerminalAddon, Terminal } from 'ghostty-web';
import { continuesPreviousRow, wrappedRowsAround } from '@/components/server/console/wrappedRows';

type SelectableTerminal = Pick<
    Terminal,
    'buffer' | 'cols' | 'element' | 'getScrollbackLength' | 'getViewportY' | 'renderer' | 'rows'
>;

type BufferCell = NonNullable<ReturnType<NonNullable<ReturnType<Terminal['buffer']['active']['getLine']>>['getCell']>>;

type SelectionPoint = { col: number; absoluteRow: number };

type WordRange = { startCol: number; endCol: number };

type SelectionManager = {
    selectionStart: SelectionPoint | null;
    selectionEnd: SelectionPoint | null;
    selectionChangedEmitter: { fire: () => void };
    getWordAtCell: (col: number, row: number) => WordRange | null;
    getSelection: () => string;
    copyToClipboard: (text: string) => Promise<void>;
    markCurrentSelectionDirty: () => void;
    requestRender: () => void;
};

const WORD_SEPARATOR = /[\s()[\]{}<>'"`|,;]/;
const WORD_TRAILING_PUNCTUATION = /[.:!?]/;

const isWordCell = (cell: BufferCell | undefined) =>
    cell !== undefined &&
    (cell.getWidth() === 0 || (cell.getCodepoint() !== 0 && !WORD_SEPARATOR.test(cell.getChars())));

export class ConsoleSelectionAddon implements ITerminalAddon {
    private readonly listeners = new AbortController();
    private restore = () => {};

    constructor(private readonly terminal: SelectableTerminal) {}

    activate() {
        const element = this.terminal.element;

        if (!element) {
            throw new Error('ConsoleSelectionAddon must be loaded after the terminal is opened.');
        }

        const selection = this.selectionManager();

        if (!selection) {
            return;
        }

        const { getWordAtCell, getSelection } = selection;

        selection.getWordAtCell = (col, row) => this.wordAt(col, row);
        selection.getSelection = () => this.joinWrappedRows(selection, getSelection.call(selection));
        this.restore = () => {
            selection.getWordAtCell = getWordAtCell;
            selection.getSelection = getSelection;
        };

        element.addEventListener('mousedown', this.onMouseDown, { capture: true, signal: this.listeners.signal });
    }

    dispose() {
        this.listeners.abort();
        this.restore();
        this.restore = () => {};
    }

    private readonly onMouseDown = (event: MouseEvent) => {
        const selection = this.selectionManager();
        const metrics = this.terminal.renderer?.getMetrics();

        if (
            event.button !== 0 ||
            event.detail < 3 ||
            !(event.target instanceof HTMLCanvasElement) ||
            !selection ||
            !metrics
        ) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const row = Math.max(0, Math.min(this.terminal.rows - 1, Math.floor(event.offsetY / metrics.height)));
        const { first, last } = wrappedRowsAround(this.terminal, this.drawnLineIndex(row));

        selection.markCurrentSelectionDirty();
        selection.selectionStart = { col: 0, absoluteRow: first };
        selection.selectionEnd = { col: this.terminal.cols - 1, absoluteRow: last };
        selection.markCurrentSelectionDirty();
        selection.requestRender();

        const text = selection.getSelection();

        if (text) {
            void selection.copyToClipboard(text);
            selection.selectionChangedEmitter.fire();
        }
    };

    private selectionManager() {
        return Reflect.get(this.terminal, 'selectionManager') as SelectionManager | undefined;
    }

    private drawnLineIndex(row: number) {
        return this.terminal.getScrollbackLength() + row - Math.floor(this.terminal.getViewportY());
    }

    private wordAt(col: number, row: number): WordRange | null {
        const line = this.terminal.buffer.active.getLine(this.drawnLineIndex(row));

        if (!line || !isWordCell(line.getCell(col))) {
            return null;
        }

        let startCol = col;
        let endCol = col;

        while (startCol > 0 && isWordCell(line.getCell(startCol - 1))) {
            startCol--;
        }

        while (endCol < line.length - 1 && isWordCell(line.getCell(endCol + 1))) {
            endCol++;
        }

        while (endCol > startCol && WORD_TRAILING_PUNCTUATION.test(line.getCell(endCol)?.getChars() ?? '')) {
            endCol--;
        }

        return { startCol, endCol };
    }

    private joinWrappedRows(selection: SelectionManager, text: string) {
        const { selectionStart: start, selectionEnd: end } = selection;

        if (!text || !start || !end) {
            return text;
        }

        const first = Math.min(start.absoluteRow, end.absoluteRow);
        const rows = text.split('\n');

        if (rows.length !== Math.abs(end.absoluteRow - start.absoluteRow) + 1) {
            return text;
        }

        return rows.reduce((joined, row, index) => joined + this.rowSeparator(first + index) + row);
    }

    private rowSeparator(y: number) {
        if (!continuesPreviousRow(this.terminal, y)) {
            return '\n';
        }

        const previous = this.terminal.buffer.active.getLine(y - 1);

        return previous?.getCell(previous.length - 1)?.getCodepoint() === 32 ? ' ' : '';
    }
}
