/** @vitest-environment jsdom */
import { act, cleanup, fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import { afterEach, beforeAll, describe, expect, it, vi } from 'vitest';
import { serverResourceUsageQueryOptions, type ServerPowerState } from '@/api/account/servers/queries';
import type { ClientGetServerResourcesResponse } from '@/api/generated';
import { publishAreaNav } from '@/router/nav';
import { createExtensionTestHost, createTestServer, type ExtensionTestHost } from '@/sdk/testing';
import { Main } from './a11y';
import { cssUrl } from './brand';
import { registerClassicTheme } from './classic';
import { Navigation, NavigationLink } from './navigation';
import { clearThemeLayers, getViewDefinition, registerViewLayer, type ViewModels, type ViewProps } from './registry';
import { ViewFixtureProvider, View, ViewArea, type ViewFixtureSelection } from './View';
import type { LayoutViewName } from '@/router/viewRoutes';
import type { AppLayoutModel } from './layouts';
import './layouts';

let host: ExtensionTestHost | undefined;

beforeAll(() => {
    // The sub-navigation measures its row as it resizes.
    vi.stubGlobal(
        'ResizeObserver',
        class {
            observe() {}
            disconnect() {}
        }
    );
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
    delete (window as Window & { SiteConfiguration?: unknown }).SiteConfiguration;
});

const contexts = {
    'layout.auth': 'auth',
    'layout.app': 'app',
    'layout.account': 'account',
    'layout.server': 'server',
    'layout.admin': 'admin',
} as const satisfies Record<LayoutViewName, string>;

async function renderLayout(name: LayoutViewName, selection: ViewFixtureSelection | null, path = '/') {
    host = createExtensionTestHost({ path });

    const layout = (
        <ViewArea area={contexts[name]}>
            <View name={name}>
                <p>Routed page</p>
            </View>
        </ViewArea>
    );

    await act(async () => {
        render(selection ? <ViewFixtureProvider {...selection}>{layout}</ViewFixtureProvider> : layout, {
            wrapper: host!.Wrapper,
        });
    });

    return host;
}

const appModel = (overrides: Partial<AppLayoutModel> = {}): AppLayoutModel => ({
    ...(getViewDefinition('layout.app')!.fixtures!.admin!() as AppLayoutModel),
    brand: { name: 'Panel', logoUrl: null },
    ...overrides,
});

describe('classic layouts', () => {
    const fixtures = Object.keys(contexts).flatMap((name) =>
        Object.keys(getViewDefinition(name)?.fixtures ?? {}).map(
            (fixture) => [name as LayoutViewName, fixture] as const
        )
    );

    it.each(fixtures)('renders %s from its %s fixture with the page inside main', async (name, fixture) => {
        await renderLayout(name, { fixtures: { [name]: fixture } });

        expect(await screen.findByRole('main')).toHaveTextContent('Routed page');
    });
});

describe('layout.app', () => {
    it('renders the navigation from its model and the routed page inside main', async () => {
        const signOut = vi.fn();

        await renderLayout('layout.app', { models: { 'layout.app': appModel({ signOut }) } });

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
        await renderLayout('layout.app', { fixtures: { 'layout.app': 'user' } });

        expect(await screen.findByRole('link', { name: 'Dashboard' })).toBeVisible();
        expect(screen.queryByRole('link', { name: 'Admin' })).not.toBeInTheDocument();
    });

    it('shows the theme logo in place of the panel name', async () => {
        await renderLayout('layout.app', { fixtures: { 'layout.app': 'logo' } });

        expect(await screen.findByRole('img', { name: 'Pterodactyl' })).toHaveAttribute(
            'src',
            '/favicons/android-chrome-192x192.png'
        );
    });

    it('offers a scheme switcher only when the theme has more than one scheme', async () => {
        await renderLayout('layout.app', { fixtures: { 'layout.app': 'user' } });
        expect(screen.queryByRole('button', { name: 'Color scheme' })).not.toBeInTheDocument();
        cleanup();
        host?.dispose();

        const setScheme = vi.fn();

        await renderLayout('layout.app', {
            models: { 'layout.app': appModel({ schemes: ['dark', 'light'], setScheme }) },
        });
        fireEvent.click(screen.getByRole('button', { name: 'Color scheme' }));
        fireEvent.click(await screen.findByRole('menuitem', { name: 'Light' }));

        expect(setScheme).toHaveBeenCalledWith('light');
    });

    it('switches the scheme of the live page without a reload', async () => {
        (window as Window & { SiteConfiguration?: unknown }).SiteConfiguration = {
            theme: { scheme: 'dark', schemes: ['dark', 'light'] },
        };
        document.documentElement.className = 'dark';

        await renderLayout('layout.app', null);
        fireEvent.click(await screen.findByRole('button', { name: 'Color scheme' }));
        fireEvent.click(await screen.findByRole('menuitem', { name: 'Light' }));

        expect(document.documentElement).toHaveClass('light');
        expect(document.documentElement).not.toHaveClass('dark');
        expect(document.cookie).toContain('pterodactyl_scheme=light');
    });
});

describe('the area layout.app renders', () => {
    const server = createTestServer({ identifier: 'a1b2c3d4', name: 'Survival', owner: false, permissions: ['file.read'] });

    function AreaProbe({ model, children }: ViewProps<'layout.app'>) {
        const { area } = model;

        return (
            <>
                <p data-testid='area'>{area.kind === 'server' ? `${area.kind} ${area.name} ${area.state}` : area.kind}</p>
                {'nav' in area && (
                    <nav aria-label='Area'>
                        <Navigation items={area.nav.items}>
                            {(item) => (
                                <NavigationLink item={item}>
                                    {item.icon} {item.label}
                                </NavigationLink>
                            )}
                        </Navigation>
                    </nav>
                )}
                <Main>{children}</Main>
            </>
        );
    }

    async function renderArea(path: string, power: ServerPowerState = 'running') {
        registerViewLayer('aurora', 'theme', { 'layout.app': AreaProbe });
        host = createExtensionTestHost({ path, server });
        host.queryClient.setQueryData(serverResourceUsageQueryOptions(server.attributes.uuid).queryKey, {
            object: 'stats',
            attributes: { current_state: power },
        } as ClientGetServerResourcesResponse);

        await act(async () => {
            render(
                <ViewArea area='app'>
                    <View name='layout.app'>
                        <p>Routed page</p>
                    </View>
                </ViewArea>,
                { wrapper: host!.Wrapper }
            );
        });

        return screen.findByTestId('area');
    }

    beforeAll(() => {
        publishAreaNav('account', [
            { segment: '', label: 'Account', exact: true, icon: 'user-round' },
            { segment: 'api', label: 'API Credentials', icon: 'key-round' },
        ]);
        publishAreaNav('server', [
            { segment: '', label: 'Console', exact: true, icon: 'square-terminal' },
            { segment: 'files', label: 'Files', permission: 'file.*', icon: 'folder' },
            { segment: 'databases', label: 'Databases', permission: 'database.*', icon: 'database' },
        ]);
        publishAreaNav('admin', [{ segment: 'users', label: 'Users', icon: 'users' }]);
    });

    afterEach(() => clearThemeLayers());

    it('is the dashboard outside the areas', async () => {
        expect(await renderArea('/')).toHaveTextContent('dashboard');
        expect(screen.queryByRole('navigation', { name: 'Area' })).not.toBeInTheDocument();
    });

    it('carries the navigation of the account and admin areas, with their icons', async () => {
        expect(await renderArea('/account/api')).toHaveTextContent('account');
        expect(screen.getByRole('link', { name: 'key-round API Credentials' })).toHaveAttribute('data-status', 'active');
        cleanup();
        host?.dispose();

        expect(await renderArea('/panel/users')).toHaveTextContent('admin');
        expect(screen.getByRole('link', { name: 'users Users' })).toHaveAttribute('href', '/panel/users');
    });

    it('carries the server, its power state and the pages its subuser may open', async () => {
        expect(await renderArea('/server/a1b2c3d4/files', 'stopped')).toHaveTextContent('server Survival offline');
        expect(
            within(screen.getByRole('navigation', { name: 'Area' }))
                .getAllByRole('link')
                .map((link) => link.textContent)
        ).toEqual(['square-terminal Console', 'folder Files']);
        expect(screen.getByRole('main')).toHaveTextContent('Routed page');
    });
});

describe('layout.server', () => {
    it('lists the model navigation and, for root admins, the admin link', async () => {
        await renderLayout('layout.server', { fixtures: { 'layout.server': 'admin' } }, '/server/a1b2c3d4/files');
        const navigation = screen.getByRole('link', { name: 'Console' }).parentElement!;

        expect(within(navigation).getByRole('link', { name: 'Files' })).toHaveAttribute('data-status', 'active');
        expect(within(navigation).getByRole('link', { name: 'Files' })).toHaveAttribute(
            'href',
            '/server/a1b2c3d4/files#/'
        );
        expect(within(navigation).getByRole('link', { name: 'Open Admin Area' })).toHaveAttribute(
            'href',
            '/panel/servers/1'
        );
    });

    it('leaves out what a subuser cannot open', async () => {
        await renderLayout('layout.server', { fixtures: { 'layout.server': 'subuser' } }, '/server/a1b2c3d4');

        expect(screen.getAllByRole('link').map((link) => link.textContent)).toEqual(['Console', 'Files', 'Backups']);
    });
});

describe('layout.admin', () => {
    const settingsModel = (): ViewModels['layout.admin'] => {
        const model = getViewDefinition('layout.admin')!.fixtures!.default!() as ViewModels['layout.admin'];
        const settings = model.nav.items.find((item) => item.id === 'settings')!;

        return {
            ...model,
            nav: {
                items: [
                    ...model.nav.items,
                    { ...settings, id: 'settings/mail', label: 'Mail', to: '/panel/settings/mail', icon: null },
                ],
            },
        };
    };

    it('labels the mobile disclosure with the most specific active item', async () => {
        await renderLayout('layout.admin', { models: { 'layout.admin': settingsModel() } }, '/panel/settings/mail');

        expect(screen.getByRole('button', { name: 'Mail' })).toHaveAttribute('aria-expanded', 'false');
    });

    it('toggles the disclosure and closes it after navigation', async () => {
        const panel = await renderLayout(
            'layout.admin',
            { fixtures: { 'layout.admin': 'default' } },
            '/panel/settings'
        );
        const disclosure = screen.getByRole('button', { name: 'Settings' });

        fireEvent.click(disclosure);
        expect(disclosure).toHaveAttribute('aria-expanded', 'true');
        fireEvent.click(disclosure);
        expect(disclosure).toHaveAttribute('aria-expanded', 'false');
        fireEvent.click(disclosure);
        await act(async () => panel.navigate({ to: '/panel/users' }));

        await waitFor(() =>
            expect(screen.getByRole('button', { name: 'Users' })).toHaveAttribute('aria-expanded', 'false')
        );
        expect(screen.getByRole('link', { name: 'Users' })).toHaveAttribute('data-status', 'active');
    });

    it('does not confuse items with a shared path prefix', async () => {
        await renderLayout('layout.admin', { fixtures: { 'layout.admin': 'default' } }, '/panel/users-extra');

        expect(screen.getByRole('button', { name: 'Administration' })).toBeInTheDocument();
    });
});

it('reads the logo from a url() token, undoing CSS escapes', () => {
    expect(cssUrl('url("/files/logo.png")')).toBe('/files/logo.png');
    expect(cssUrl(" url('/a b.svg') ")).toBe('/a b.svg');
    expect(cssUrl('url("/files/\\3c x\\22.png")')).toBe('/files/<x".png');
    expect(cssUrl('url(/plain.png)')).toBe('/plain.png');
    expect(cssUrl('"/not-a-url.png"')).toBeNull();
    expect(cssUrl('')).toBeNull();
});
