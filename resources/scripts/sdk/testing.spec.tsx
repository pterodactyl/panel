/** @vitest-environment jsdom */
import { cleanup, fireEvent, screen } from '@testing-library/react';
import { useState } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import Alert from '@/components/elements/alert/Alert';
import Button from '@/components/elements/Button';
import { Main } from '@/views/a11y';
import { lazyView } from '@/views/lazy';
import { Navigation, NavigationLink } from '@/views/navigation';
import type { ViewPartProps, ViewProps } from '@/views/registry';
import { defineTheme } from '@/views/theme';
import { skin } from '@/themes/skins';
import { usePanelLocation } from './navigation';
import {
    preloadTheme,
    renderView,
    renderWithTheme,
    createTestFileEditor,
    createTestFileManager,
    createTestFileManagerEntry,
    createTestServerCard,
    type RenderedView,
} from './testing';

let rendered: RenderedView | undefined;

afterEach(() => {
    cleanup();
    rendered?.unmount();
    rendered = undefined;
});

it('renders an editor layer around native parts with a fixture model', () => {
    const save = vi.fn(async () => true);
    const change = vi.fn();

    function Buffer({ model }: ViewPartProps<'server.files.editor'>) {
        return (
            <textarea
                aria-label={model.name}
                defaultValue={model.content}
                onChange={(event) => model.change(event.target.value)}
            />
        );
    }

    function Editor({ Default }: ViewProps<'server.files.editor'>) {
        return <Default className='custom-editor' parts={{ editor: Buffer }} />;
    }

    rendered = renderView('server.files.editor', {
        themes: [defineTheme({ views: { 'server.files.editor': Editor } })],
        model: createTestFileEditor({ name: '.pteroignore', content: 'logs/*', save, change }),
    });

    expect(screen.getByLabelText('.pteroignore')).toHaveValue('logs/*');
    expect(screen.getByText(/excluded from backups/)).toBeVisible();
    fireEvent.change(screen.getByLabelText('.pteroignore'), { target: { value: 'cache/*' } });
    fireEvent.click(screen.getByRole('button', { name: 'Save Content' }));

    expect(change).toHaveBeenCalledWith('cache/*');
    expect(save).toHaveBeenCalledTimes(1);
});

it('hides the native save action from a read-only editor fixture', () => {
    rendered = renderView('server.files.editor', { fixture: 'readOnly' });

    expect(screen.queryByRole('button', { name: 'Save Content' })).not.toBeInTheDocument();
});

it('renders the native file browser from a fixture model without issuing core queries', () => {
    const select = vi.fn();
    const model = createTestFileManager({
        directory: '/config',
        entries: [
            createTestFileManagerEntry({ name: 'plugins', path: '/config/plugins', kind: 'directory', mimetype: '' }),
            createTestFileManagerEntry({ name: 'app.yml', path: '/config/app.yml', mimetype: 'text/plain' }),
        ],
        selection: ['app.yml'],
    });

    rendered = renderView('server.files.manager', { model: { ...model, actions: { ...model.actions, select } } });

    expect(screen.getByRole('link', { name: 'app.yml' })).toHaveAttribute(
        'href',
        '/server/test-server/files/edit#/config/app.yml'
    );
    expect(screen.getByRole('link', { name: 'plugins' })).toHaveAttribute(
        'href',
        '/server/test-server/files#/config/plugins'
    );
    expect(screen.getByRole('button', { name: 'New file' })).toBeVisible();
    expect(screen.getByRole('button', { name: 'Archive' })).toBeVisible();

    fireEvent.click(screen.getAllByRole('checkbox')[0]);
    expect(select).toHaveBeenCalledWith(['plugins', 'app.yml']);
});

it('gives a server card layer the classic parts and the server it was rendered for', () => {
    function Card({ Default, parts, model, server }: ViewProps<'dashboard.serverCard'>) {
        return (
            <>
                <span>{server.attributes.identifier}</span>
                <Default parts={{ metrics: () => <span>{`metrics of ${model.name}`}</span> }} />
                <parts.address model={model} server={server} />
            </>
        );
    }

    rendered = renderView('dashboard.serverCard', {
        themes: [defineTheme({ views: { 'dashboard.serverCard': Card } })],
        model: createTestServerCard({ name: 'Survival', identifier: 'abc' }),
    });

    expect(screen.getByText('metrics of Survival')).toBeVisible();
    expect(screen.getByText('abc')).toBeVisible();
    expect(screen.getAllByText('127.0.0.1:25565')).toHaveLength(2);
});

it('renders a view synchronously from fixtures with a theme layered over classic', () => {
    function AppLayout({ Default }: ViewProps<'layout.app'>) {
        return (
            <div data-aurora-layout=''>
                <Default />
            </div>
        );
    }

    const { container, unmount } = renderView('layout.app', {
        themes: [defineTheme({ views: { 'layout.app': AppLayout } })],
        fixture: 'admin',
    });

    expect(container.querySelector('[data-aurora-layout] main')).not.toBeNull();
    expect(container.querySelector('a[aria-label="Admin"]')).toHaveAttribute('href', '/panel');
    unmount();
    expect(container.isConnected).toBe(false);
});

it('renders the lazy views of a theme once they are preloaded', async () => {
    const theme = defineTheme({
        views: {
            'page.dashboard': lazyView(async () => ({
                default: ({ model }: ViewProps<'page.dashboard'>) => <p>{`lazy dashboard ${model.error ?? 'ok'}`}</p>,
            })),
        },
    });

    expect(() => renderView('page.dashboard', { themes: [theme] })).toThrow(
        "The theme's views page.dashboard are lazy and not loaded yet."
    );

    await preloadTheme(theme);

    const { container, unmount } = renderView('page.dashboard', { themes: [theme], fixture: 'error' });

    expect(container).toHaveTextContent('lazy dashboard Request failed with status code 500.');
    unmount();
});

it('stacks a chain of themes, so a child builds on its parent rather than classic', async () => {
    function ParentLayout({ Default }: ViewProps<'layout.app'>) {
        return (
            <div data-parent-layout=''>
                <Default />
            </div>
        );
    }

    function ChildLayout({ Default }: ViewProps<'layout.app'>) {
        return (
            <div data-child-layout=''>
                <Default />
            </div>
        );
    }

    const parent = defineTheme({
        views: { 'layout.app': lazyView(async () => ({ default: ParentLayout })) },
        ui: { Button: skin('Button', { root: 'parent-pill' }) },
    });
    const child = defineTheme({
        views: { 'layout.app': ChildLayout },
        ui: { Button: skin('Button', { root: (state, below) => below('child-shadow') }) },
    });
    const themes = [parent, child];

    await preloadTheme(themes);

    const view = renderView('layout.app', { themes, fixture: 'admin' });

    expect(view.container.querySelector('[data-child-layout] > [data-parent-layout] main')).not.toBeNull();
    expect(renderWithTheme(<Button>Save</Button>, { themes }).container.querySelector('button')).toHaveClass(
        'parent-pill',
        'child-shadow'
    );
});

it('renders an element under a theme, replacing what was rendered before', () => {
    const first = renderWithTheme(<Button color='red'>Save changes</Button>, { themes: [defineTheme({})] });

    expect(first.container.querySelector('button')).toHaveTextContent('Save changes');

    const second = renderWithTheme(<Alert type='warning'>Disk is nearly full.</Alert>);

    expect(first.container.isConnected).toBe(false);
    expect(second.container.querySelector('[role="alert"]')).toHaveTextContent('Disk is nearly full.');
    second.unmount();
});

it('renders a fixture of a view that takes inputs with the inputs it declares', () => {
    const { container, unmount } = renderView('dashboard.serverCard', { fixture: 'suspended' });

    expect(container).toHaveTextContent('Test server');
    expect(container).toHaveTextContent('Suspended');
    unmount();
});

describe('failing layers', () => {
    const failing = defineTheme({
        views: {
            'server.graphs': () => {
                throw new Error('theme exploded');
            },
        },
    });

    beforeEach(() => {
        vi.spyOn(console, 'error').mockImplementation(() => {});
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('fails the render naming the view and the layer', () => {
        expect(() => renderView('server.graphs', { themes: [failing] })).toThrow(
            'The "theme" layer of view "server.graphs" failed: theme exploded'
        );
        expect(document.body.children).toHaveLength(0);
    });

    it('fails on a layer of a nested view too', () => {
        const nested = defineTheme({
            views: {
                'dashboard.serverCard': () => {
                    throw new Error('card exploded');
                },
            },
        });

        expect(() => renderView('dashboard.serverList', { themes: [nested], fixture: 'servers' })).toThrow(
            'The "theme" layer of view "dashboard.serverCard" failed: card exploded'
        );
    });

    it('falls back to classic, once, when not strict', () => {
        const { container, unmount } = renderView('server.graphs', { themes: [failing], strict: false });

        expect(container).toHaveTextContent('CPU Load');
        unmount();
    });

    it('throws a layer failure after the first render from unmount', () => {
        function Graphs({ Default }: ViewProps<'server.graphs'>) {
            const [broken, setBroken] = useState(false);

            if (broken) {
                throw new Error('graphs exploded');
            }

            return (
                <>
                    <button type='button' onClick={() => setBroken(true)}>
                        Break
                    </button>
                    <Default />
                </>
            );
        }

        const view = renderView('server.graphs', { themes: [defineTheme({ views: { 'server.graphs': Graphs } })] });

        fireEvent.click(screen.getByRole('button', { name: 'Break' }));

        expect(() => view.unmount()).toThrow('The "theme" layer of view "server.graphs" failed: graphs exploded');
    });
});

it('mounts slot components given to renderView, and removes them afterwards', () => {
    const first = renderView('layout.app', {
        fixture: 'user',
        slots: { 'nav.items.before': () => <span>slot probe</span> },
    });

    expect(first.container).toHaveTextContent('slot probe');
    first.unmount();

    const second = renderView('layout.app', { fixture: 'user' });

    expect(second.container).not.toHaveTextContent('slot probe');
    second.unmount();
});

it('renders a layer reading the panel location from fixtures, outside any route', () => {
    function Shell({ Default }: ViewProps<'layout.app'>) {
        const { pathname, params, search } = usePanelLocation();

        return (
            <>
                <p>{`at ${pathname} with ${Object.keys(params).length} params and ${JSON.stringify(search)}`}</p>
                <Default />
            </>
        );
    }

    const { container, unmount } = renderView('layout.app', {
        themes: [defineTheme({ views: { 'layout.app': Shell } })],
    });

    expect(container).toHaveTextContent('at / with 0 params and {}');
    unmount();
});

it('gives the app layout the area it renders, from a fixture of each area', () => {
    function Shell({ model, children }: ViewProps<'layout.app'>) {
        const { area } = model;

        return (
            <>
                <p>{area.kind === 'server' ? `${area.name} is ${area.state}` : area.kind}</p>
                {'nav' in area && (
                    <Navigation items={area.nav.items}>
                        {(item) => <NavigationLink item={item}>{`${item.icon}:${item.label}`}</NavigationLink>}
                    </Navigation>
                )}
                <Main>{children}</Main>
            </>
        );
    }

    const theme = defineTheme({ views: { 'layout.app': Shell } });
    const kinds = ['user', 'accountArea', 'serverArea', 'adminArea'].map((fixture) => {
        const { container, unmount } = renderView('layout.app', { themes: [theme], fixture });
        const text = `${container.querySelector('p')?.textContent} ${container.querySelector('a')?.textContent}`;

        unmount();

        return text;
    });

    expect(kinds).toEqual([
        'dashboard undefined',
        'account user-round:Account',
        'Survival is running square-terminal:Console',
        'admin layout-dashboard:Overview',
    ]);
});

it('renders a view measuring itself without stubbing browser APIs', () => {
    const { container, unmount } = renderView('server.graphs');

    expect(container).toHaveTextContent('Memory');
    unmount();
});

it('renders nested views from their fixtures, each from the inputs it is placed with', () => {
    const { container } = renderView('dashboard.serverList', { fixture: 'servers' });
    const creative = container.querySelector('a[aria-label="Creative"]');

    expect(creative).toHaveAttribute('href', '/server/e5f6a7b8');
    // Each row's card is its own server's, with the state of its row.
    expect(creative).toHaveTextContent('Creative');
    expect(creative).toHaveAttribute('data-status', 'danger');
    expect(container.querySelector('a[aria-label="Survival"]')).toHaveTextContent('Survival');
    expect(container.querySelector('a[aria-label="Lobby"]')).toHaveTextContent('Suspended');
    expect(container.querySelectorAll('[data-ui="list-row"][data-part="status"]')).toHaveLength(3);
});
