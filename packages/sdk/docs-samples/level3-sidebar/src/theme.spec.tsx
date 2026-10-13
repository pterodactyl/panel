import { afterEach, beforeAll, expect, test } from 'vitest';
import { fireEvent, screen } from '@testing-library/react';
import { preloadTheme, renderView, type RenderedView } from '@pterodactyl/sdk/testing';
import theme from './theme';

const themes = [theme];
let rendered: RenderedView | undefined;

beforeAll(() => preloadTheme(themes));
afterEach(() => rendered?.unmount());

test('puts the links in a sidebar beside the page', () => {
    rendered = renderView('layout.app', { themes, fixture: 'admin' });

    const nav = screen.getByRole('navigation', { name: 'Main' });

    expect(nav.closest('aside')).not.toBeNull();
    expect(nav.textContent).toContain('Admin');
    expect(rendered.container.querySelector('main#main')).not.toBeNull();
});

test('opens the sidebar as a drawer from the menu button', () => {
    rendered = renderView('layout.app', { themes, fixture: 'user' });

    const menu = screen.getByRole('button', { name: 'Menu' });
    fireEvent.click(menu);

    expect(menu.getAttribute('aria-expanded')).toBe('true');
});

test('offers the scheme switch only when the theme has both schemes', () => {
    rendered = renderView('layout.app', { themes, fixture: 'user' });
    expect(screen.queryByRole('button', { name: 'Use the light scheme' })).toBeNull();

    rendered = renderView('layout.app', { themes, fixture: 'schemes' });
    expect(screen.getByRole('button', { name: 'Use the light scheme' })).not.toBeNull();
});

test('draws the server navigation, with its state and its slots, in the sidebar', () => {
    // #region slots
    rendered = renderView('layout.app', {
        themes,
        fixture: 'serverArea',
        slots: { 'server.navigation.after': ({ data }) => <span>Mods for {data.attributes.name}</span> },
    });
    // #endregion slots

    const nav = screen.getByRole('navigation', { name: 'Server' }).textContent;

    expect(nav).toContain('Survival');
    expect(nav).toContain('running');
    expect(nav).toContain('Backups');
    expect(nav).toContain('Mods for Survival');
});

test('draws the account and admin navigation with their slots', () => {
    const probe = () => <span>probe</span>;

    rendered = renderView('layout.app', {
        themes,
        fixture: 'accountArea',
        slots: { 'account.navigation.before': probe },
    });
    const account = screen.getByRole('navigation', { name: 'Account' }).textContent;

    expect(account).toContain('SSH Keys');
    expect(account).toContain('probe');

    rendered = renderView('layout.app', { themes, fixture: 'adminArea', slots: { 'panel.navigation.after': probe } });
    const admin = screen.getByRole('navigation', { name: 'Administration' }).textContent;

    expect(admin).toContain('Themes');
    expect(admin).toContain('probe');
});

test('leaves only the page to the area layouts', () => {
    rendered = renderView('layout.server', { themes, fixture: 'owner' });

    expect(screen.queryByRole('navigation')).toBeNull();
    expect(rendered.container.querySelector('main#main')).not.toBeNull();
});
