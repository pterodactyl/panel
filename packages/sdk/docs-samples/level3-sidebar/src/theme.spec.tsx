import { afterEach, beforeAll, expect, test } from 'vitest';
import { screen } from '@testing-library/react';
import { preloadTheme, renderView, type RenderedView } from '@pterodactyl/sdk/testing';
import theme from './theme';

let rendered: RenderedView | undefined;

beforeAll(() => preloadTheme(theme));
afterEach(() => rendered?.unmount());

test('puts the links in a sidebar beside the page', () => {
    rendered = renderView('layout.app', { theme, fixture: 'admin' });

    const nav = screen.getByRole('navigation', { name: 'Main' });

    expect(nav.closest('aside')).not.toBeNull();
    expect(nav.textContent).toContain('Admin');
    expect(rendered.container.querySelector('main#main')).not.toBeNull();
});

test('offers the scheme switch only when the theme has both schemes', () => {
    rendered = renderView('layout.app', { theme, fixture: 'user' });
    expect(screen.queryByRole('button', { name: 'Use the light scheme' })).toBeNull();

    rendered = renderView('layout.app', { theme, fixture: 'schemes' });
    expect(screen.getByRole('button', { name: 'Use the light scheme' })).not.toBeNull();
});

test('lists extension screens in the account menu', () => {
    rendered = renderView('layout.account', { theme, fixture: 'extension' });

    expect(screen.getByRole('navigation', { name: 'Account' }).textContent).toContain('Tickets');
});
