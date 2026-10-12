/** @vitest-environment jsdom */
import { act, cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, beforeAll, expect, it, vi } from 'vitest';
import { createExtensionTestHost, type ExtensionTestHost } from '@/sdk/testing';
import { registerClassicTheme } from './classic';
import { registerViewLayer } from './registry';
import { ViewFixtureProvider, View, ViewArea } from './View';
import type { AppLayoutModel } from './layouts';
import './layouts';

let host: ExtensionTestHost | undefined;

beforeAll(() => {
    registerClassicTheme();
    registerViewLayer('classic', 'classic', {
        'app.search': ({ className }: { className?: string }) => (
            <button type='button' className={className}>
                Search
            </button>
        ),
    });
});

afterEach(() => {
    cleanup();
    host?.dispose();
    host = undefined;
});

const model = (rootAdmin: boolean, signOut = vi.fn()): AppLayoutModel => ({
    brand: { name: 'Panel' },
    links: [
        { id: 'dashboard', label: 'Dashboard', destination: { to: '/' }, exact: true },
        ...(rootAdmin ? [{ id: 'admin', label: 'Admin', destination: { to: '/panel' }, exact: false } as const] : []),
        { id: 'account', label: 'Account Settings', destination: { to: '/account' }, exact: false },
    ],
    user: { uuid: 'user', username: 'alex', email: 'alex@example.test', rootAdmin },
    signOut,
    signingOut: false,
});

async function renderLayout(layout: AppLayoutModel) {
    host = createExtensionTestHost({ path: '/' });

    await act(async () => {
        render(
            <ViewFixtureProvider models={{ 'layout.app': layout }}>
                <ViewArea area='app'>
                    <View name='layout.app'>
                        <p>Routed page</p>
                    </View>
                </ViewArea>
            </ViewFixtureProvider>,
            { wrapper: host!.Wrapper }
        );
    });
}

it('renders the navigation from its model and the routed page inside main', async () => {
    const signOut = vi.fn();

    await renderLayout(model(true, signOut));

    expect(await screen.findByRole('link', { name: 'Panel' })).toHaveAttribute('href', '/');
    expect(screen.getByRole('link', { name: 'Dashboard' })).toHaveAttribute('data-status', 'active');
    expect(screen.getByRole('link', { name: 'Admin' })).toHaveAttribute('href', '/panel');
    expect(screen.getByRole('link', { name: 'Account Settings' })).toHaveAttribute('href', '/account');
    expect(screen.getByRole('button', { name: 'Search' })).toHaveClass('px-3', 'sm:px-6');
    expect(screen.getByRole('main')).toHaveTextContent('Routed page');

    fireEvent.click(screen.getByRole('button', { name: 'Sign Out' }));
    expect(signOut).toHaveBeenCalledOnce();
});

it('leaves the admin link out for other users', async () => {
    await renderLayout(model(false));

    expect(await screen.findByRole('link', { name: 'Dashboard' })).toBeVisible();
    expect(screen.queryByRole('link', { name: 'Admin' })).not.toBeInTheDocument();
});
