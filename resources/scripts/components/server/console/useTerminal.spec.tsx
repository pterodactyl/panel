/** @vitest-environment jsdom */
import { act, cleanup, fireEvent, render, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { ITerminalOptions } from 'ghostty-web';
import type { notifyThemeChange as NotifyThemeChange } from '@/lib/theme';
import type { useTerminal as UseTerminal } from './useTerminal';

const terminals: { options: ITerminalOptions; dispose: ReturnType<typeof vi.fn> }[] = [];
const ghostty = vi.hoisted(() => ({ load: vi.fn<() => Promise<object>>() }));

vi.mock('ghostty-web', () => ({
    Ghostty: { load: () => ghostty.load() },
    FitAddon: class {
        fit() {}
        observeResize() {}
        dispose() {}
    },
    UrlRegexProvider: class {},
    Terminal: class {
        dispose = vi.fn();
        constructor(public options: ITerminalOptions) {
            terminals.push(this);
        }
        open() {}
        loadAddon() {}
        registerLinkProvider() {}
        attachCustomKeyEventHandler() {}
        onScroll() {}
        getViewportY() {
            return 0;
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
            <button type={'button'} onClick={retry}>
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
