import { afterEach, beforeAll, expect, test, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { preloadTheme, renderView, type RenderedView } from '@pterodactyl/sdk/testing';
import theme from './theme';

// #region tests
let rendered: RenderedView | undefined;

beforeAll(async () => {
    // jsdom has no ResizeObserver; classic's server navigation, which `Default` renders, measures itself.
    vi.stubGlobal(
        'ResizeObserver',
        class {
            observe() {}
            disconnect() {}
        }
    );
    // The views are lazy; loading their chunks first lets renderView render them synchronously.
    await preloadTheme(theme);
});
afterEach(() => rendered?.unmount());

test('puts the power controls in the server header', () => {
    rendered = renderView('layout.server', { theme, fixture: 'owner' });

    const header = screen.getByRole('heading', { name: 'Survival' }).closest('header');

    expect(header?.textContent).toContain('Restart');
});
// #endregion tests

test('hides them while the server installs', () => {
    rendered = renderView('layout.server', { theme, fixture: 'installing' });

    expect(screen.queryByRole('button', { name: 'Restart' })).toBeNull();
});

test('leaves them out of the console page', () => {
    rendered = renderView('page.server.console', { theme, input: { active: true } });

    expect(screen.queryByRole('button', { name: 'Restart' })).toBeNull();
});
