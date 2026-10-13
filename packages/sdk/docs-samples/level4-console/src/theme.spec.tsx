import { afterEach, beforeAll, expect, test } from 'vitest';
import { screen } from '@testing-library/react';
import { preloadTheme, renderView, type RenderedView } from '@pterodactyl/sdk/testing';
import theme from './theme';

// #region tests
const themes = [theme];
let rendered: RenderedView | undefined;

// The views are lazy; loading their chunks first lets renderView render them synchronously.
beforeAll(() => preloadTheme(themes));
afterEach(() => rendered?.unmount());

test('puts the power controls in the server header', () => {
    rendered = renderView('layout.server', { themes, fixture: 'owner' });

    const header = screen.getByRole('heading', { name: 'Survival' }).closest('header');

    expect(header?.textContent).toContain('Restart');
});
// #endregion tests

test('hides them while the server installs', () => {
    rendered = renderView('layout.server', { themes, fixture: 'installing' });

    expect(screen.queryByRole('button', { name: 'Restart' })).toBeNull();
});

test('leaves them out of the console page', () => {
    rendered = renderView('page.server.console', { themes, input: { active: true } });

    expect(screen.queryByRole('button', { name: 'Restart' })).toBeNull();
});
