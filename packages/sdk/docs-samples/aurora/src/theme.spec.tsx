import { afterEach, beforeAll, expect, test } from 'vitest';
import { screen } from '@testing-library/react';
import { preloadTheme, renderPrimitive, renderView, type RenderedView } from '@pterodactyl/sdk/testing';
import theme from './theme';

let rendered: RenderedView | undefined;

// Aurora's views are lazy; loading their chunks first lets renderView render them synchronously.
beforeAll(() => preloadTheme(theme));
afterEach(() => rendered?.unmount());

// #region tests
test('lays the dashboard out as cards with power buttons beside their links', () => {
    rendered = renderView('dashboard.serverList', { theme, fixture: 'servers' });

    const survival = screen.getByRole('link', { name: 'Survival' });

    expect(survival.getAttribute('href')).toBe('/server/a1b2c3d4');
    expect(survival.closest('li')?.textContent).toContain('Restart');
    expect(screen.getByRole('link', { name: 'Creative' }).closest('li')?.textContent).toContain('Start');
});

test('puts the power controls in the server header and asks before a kill', () => {
    rendered = renderView('layout.server', { theme, fixture: 'owner' });

    const header = screen.getByRole('heading', { name: 'Survival' }).closest('header');

    expect(header?.textContent).toContain('Restart');

    rendered = renderView('server.power', { theme, fixture: 'stopping' });
    expect(screen.getByRole('button', { name: 'Kill' }).hasAttribute('disabled')).toBe(false);
});

test('keeps the engine field attributes on the sign-in form', () => {
    rendered = renderView('auth.loginForm', { theme, fixture: 'invalid' });

    const password = screen.getByLabelText('Password');

    expect(password.getAttribute('autocomplete')).toBe('current-password');
    expect(password.getAttribute('aria-invalid')).toBe('true');
    expect(screen.getByText('Please enter your account password.')).not.toBeNull();
});

test('draws buttons as pills and keeps their state attributes', () => {
    rendered = renderPrimitive('Button', { children: 'Save', color: 'red' }, { theme });

    const button = rendered.container.querySelector('button');

    expect(button?.className).toContain('rounded-full');
    expect(button?.dataset).toMatchObject({ ui: 'button', part: 'root', color: 'red', size: 'small' });
});
// #endregion tests
