/** @vitest-environment jsdom */
import { cleanup } from '@testing-library/react';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import Slot from '@/extensions/Slot';
import { renderView, type RenderedView } from '@/sdk/testing';
import { defineTheme } from '@/views/theme';
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
