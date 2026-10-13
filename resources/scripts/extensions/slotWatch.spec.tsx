/** @vitest-environment jsdom */
import { act, cleanup } from '@testing-library/react';
import { use } from 'react';
import { createRoot } from 'react-dom/client';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import Slot from '@/extensions/Slot';
import { renderView, type RenderedView } from '@/sdk/testing';
import { clearThemeLayers, registerViewLayer } from '@/views/registry';
import { defineTheme } from '@/views/theme';
import { LooseView, ViewFixtureProvider } from '@/views/View';
import { mountSlotComponents } from './registry';
import { SLOT_CHECK_DELAY_MS } from './slotWatch';

let rendered: RenderedView | undefined;
let warn: ReturnType<typeof vi.spyOn>;

beforeEach(() => {
    vi.useFakeTimers();
    warn = vi.spyOn(console, 'warn').mockImplementation(() => {});
});

afterEach(() => {
    cleanup();
    rendered?.unmount();
    rendered = undefined;
    vi.useRealTimers();
    vi.restoreAllMocks();
});

it('warns once when a layer leaves out a slot that has components, naming both packages', () => {
    rendered = renderView('page.server.console', {
        themes: [defineTheme({ views: { 'page.server.console': () => <p>console</p> } })],
        slots: { 'server.console.after': () => <span>extension</span> },
        strict: false,
    });

    vi.advanceTimersByTime(SLOT_CHECK_DELAY_MS);

    expect(warn).toHaveBeenCalledTimes(1);
    expect(warn).toHaveBeenCalledWith(
        '[views] The "theme" layer of "page.server.console" does not render the "server.console.after" slot, so "test-slots" does not show there.'
    );
});

it('stays quiet when the slot renders, or has nothing registered', () => {
    rendered = renderView('page.account.overview', {
        themes: [
            defineTheme({
                views: {
                    // `account.overview.after` has nothing registered, so leaving it out goes unreported here.
                    'page.account.overview': () => (
                        <>
                            <Slot name='account.overview.before' />
                            <p>account</p>
                        </>
                    ),
                },
            }),
        ],
        slots: { 'account.overview.before': () => <span>extension</span> },
        strict: false,
    });

    vi.advanceTimersByTime(SLOT_CHECK_DELAY_MS);

    expect(warn).not.toHaveBeenCalled();
});

it('waits for a layer that is still loading before it checks', async () => {
    let finish: () => void = () => {};
    const ready = new Promise<void>((resolve) => {
        finish = resolve;
    });

    function Slow() {
        use(ready);

        return <p>files</p>;
    }

    // renderView renders synchronously; a layer that suspends needs an awaited render.
    registerViewLayer('theme', 'theme', { 'page.server.files': Slow });
    const removeSlots = mountSlotComponents('slow-test', { 'server.files.before': () => <span>extension</span> });
    const container = document.body.appendChild(document.createElement('div'));
    const root = createRoot(container);

    await act(async () =>
        root.render(
            <ViewFixtureProvider>
                <LooseView name='page.server.files' />
            </ViewFixtureProvider>
        )
    );
    vi.advanceTimersByTime(SLOT_CHECK_DELAY_MS * 2);
    expect(warn).not.toHaveBeenCalled();

    await act(async () => {
        finish();
        await ready;
    });
    vi.advanceTimersByTime(SLOT_CHECK_DELAY_MS);

    expect(container.textContent).toContain('files');
    expect(warn).toHaveBeenCalledWith(expect.stringContaining('does not render the "server.files.before" slot'));

    act(() => root.unmount());
    container.remove();
    removeSlots();
    clearThemeLayers();
});
