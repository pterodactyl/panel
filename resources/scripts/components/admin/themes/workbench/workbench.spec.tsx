/** @vitest-environment jsdom */
import { act, cleanup, fireEvent, render, screen, within } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, beforeAll, beforeEach, describe, expect, it, vi } from 'vitest';
import PageContentBlock from '@/components/elements/PageContentBlock';
import { PortalContainerContext } from '@/components/elements/Portal';
import { Dialog } from '@/components/elements/dialog';
import { parseWorkbenchSearch } from '@/router/search';
import { createExtensionTestHost, type ExtensionTestHost } from '@/sdk/testing';
import '@/views/catalog';
import { getViewDefinition, registerViewDefinition, registerViewLayer, type ViewDefinition } from '@/views/registry';
import { matchesQuery, workbenchGroups } from './catalog';
import type * as PreviewFrameModule from './PreviewFrame';
import { mirrorDocument } from './PreviewFrame';
import { frameSize } from './stage';
import ViewPreview, { ALL_FIXTURES } from './ViewPreview';

// jsdom loads no srcdoc, so previews render in place; the frame itself is covered by mirrorDocument.
vi.mock('./PreviewFrame', async (importOriginal) => ({
    ...(await importOriginal<typeof PreviewFrameModule>()),
    default: ({ label, children }: { label: string; children?: ReactNode }) => (
        <div role='figure' aria-label={label}>
            {children}
        </div>
    ),
}));

let host: ExtensionTestHost | undefined;

beforeAll(() => {
    registerViewDefinition({
        name: 'test.broken',
        kind: 'block',
        context: 'app',
        use: () => ({}),
        fixtures: { default: () => ({}) },
    });
    registerViewLayer('classic', 'classic', {
        'test.broken': () => {
            throw new Error('The fixture is missing a field.');
        },
    });
    registerViewLayer('aurora', 'theme', { 'dashboard.serverCard': () => <p>Aurora card</p> });
});

beforeEach(() => {
    vi.spyOn(console, 'error').mockImplementation(() => {});
    vi.spyOn(HTMLElement.prototype, 'getBoundingClientRect').mockReturnValue(new DOMRect(0, 0, 1000, 400));
    vi.stubGlobal(
        'ResizeObserver',
        class {
            observe() {}
            disconnect() {}
        }
    );
});

afterEach(() => {
    cleanup();
    host?.dispose();
    host = undefined;
    vi.restoreAllMocks();
    vi.unstubAllGlobals();
});

async function preview(name: string, fixture?: string) {
    const onFixture = vi.fn();

    host = createExtensionTestHost({ path: '/panel/theme-workbench' });
    await act(async () => {
        render(
            <ViewPreview
                definition={getViewDefinition(name)!}
                search={{ view: name, fixture }}
                scheme='dark'
                width='phone'
                onFixture={onFixture}
            />,
            { wrapper: host!.Wrapper }
        );
    });

    return onFixture;
}

describe('workbench catalog', () => {
    const definition = (name: string, kind: ViewDefinition['kind'], extensionId?: string): ViewDefinition => ({
        name,
        kind,
        context: 'app',
        use: () => ({}),
        fixtures: kind === 'block' ? { one: () => ({}) } : undefined,
        extensionId,
    });
    const definitions = [
        definition('layout.app', 'layout'),
        definition('page.dashboard', 'page'),
        definition('dashboard.serverCard', 'block'),
        definition('billing.balance', 'block', 'billing'),
    ];

    it('groups views by kind, then extension views and the UI kit', () => {
        const groups = workbenchGroups(definitions, { query: '', overridden: false });

        expect(groups.map((group) => [group.label, group.entries.length])).toEqual([
            ['Layouts', 1],
            ['Pages', 1],
            ['Blocks', 1],
            ['Extension views', 1],
            ['UI kit', groups.at(-1)!.entries.length],
        ]);
        expect(groups[2].entries[0]).toEqual({
            name: 'dashboard.serverCard',
            fixtures: ['one'],
            overrides: ['aurora'],
        });
        expect(groups.at(-1)!.entries.find((entry) => entry.name === 'Button')?.fixtures).toContain('primary');
    });

    it('searches by every term and filters to overridden views', () => {
        expect(matchesQuery('dashboard.serverCard', 'serv card')).toBe(true);
        expect(matchesQuery('dashboard.serverList', 'serv card')).toBe(false);
        expect(
            workbenchGroups(definitions, { query: '', overridden: true }).flatMap((group) =>
                group.entries.map((entry) => entry.name)
            )
        ).toEqual(['dashboard.serverCard']);
    });

    it('reads deep links, dropping values it does not know', () => {
        expect(
            parseWorkbenchSearch({
                view: 'page.dashboard',
                ui: 'Button',
                fixture: '*',
                scheme: 'light',
                width: 'phone',
            })
        ).toEqual({ view: 'page.dashboard', fixture: '*', scheme: 'light', width: 'phone' });
        expect(parseWorkbenchSearch({ ui: 'Button', scheme: 'sepia', width: 'watch' })).toEqual({ ui: 'Button' });
    });

    it('shrinks a viewport preset to the space available', () => {
        expect(frameSize('phone', 1000)).toEqual({ width: 390, scale: 1 });
        expect(frameSize('desktop', 640)).toEqual({ width: 1280, scale: 0.5 });
        expect(frameSize('full', 640)).toEqual({ width: 640, scale: 1 });
    });
});

describe('preview frames', () => {
    it('mirror the panel stylesheets and document attributes with their own scheme', () => {
        const settings = document.head.appendChild(
            Object.assign(document.createElement('style'), { id: 'theme-settings' })
        );
        const sheet = document.head.appendChild(
            Object.assign(document.createElement('link'), { rel: 'stylesheet', href: '/assets/theme.css' })
        );
        const target = document.implementation.createHTMLDocument('frame');
        const clones = new Map<Element, Element>();

        settings.textContent = ':root{--accent:red}';
        document.documentElement.className = 'dark';
        document.documentElement.setAttribute('data-density', 'compact');
        document.body.className = 'bg-background';
        mirrorDocument(target, 'light', clones);

        expect(target.head.querySelector('#theme-settings')?.textContent).toBe(':root{--accent:red}');
        expect(target.head.querySelector('link')?.getAttribute('href')).toBe('/assets/theme.css');
        expect(target.documentElement.className).toBe('light');
        expect(target.documentElement.getAttribute('data-density')).toBe('compact');
        expect(target.body.className).toBe('bg-background');

        settings.textContent = ':root{--accent:blue}';
        sheet.remove();
        mirrorDocument(target, 'dark', clones);

        expect(target.head.querySelector('#theme-settings')?.textContent).toBe(':root{--accent:blue}');
        expect(target.head.querySelector('link')).toBeNull();
        expect(target.documentElement.className).toBe('dark');
        settings.remove();
    });

    it('portal dialogs into the frame and title the frame instead of the workbench', async () => {
        const frame = document.implementation.createHTMLDocument('frame');
        const overlays = frame.body.appendChild(frame.createElement('div'));

        document.title = 'Admin · Theme workbench';
        await act(async () => {
            render(
                <PortalContainerContext.Provider value={overlays}>
                    <PageContentBlock title='Servers'>
                        <Dialog open onClose={() => {}} title='Rename server'>
                            Choose a name.
                        </Dialog>
                    </PageContentBlock>
                </PortalContainerContext.Provider>
            );
        });

        // A document without a window, which the DOM matchers cannot read.
        expect(overlays.querySelector('[role="dialog"]')?.textContent).toContain('Choose a name.');
        expect(screen.queryByRole('dialog')).toBeNull();
        expect(frame.title).toBe('Servers');
        expect(document.title).toBe('Admin · Theme workbench');
    });
});

describe('view preview', () => {
    it('renders every fixture side by side through the layer chain, with its details', async () => {
        await preview('dashboard.serverList', ALL_FIXTURES);

        const servers = await screen.findByRole('figure', { name: 'dashboard.serverList: servers' });

        expect(within(servers).getByRole('link', { name: 'Survival' })).toHaveAttribute('href', '/server/a1b2c3d4');
        expect(within(servers).getAllByText('Aurora card')).toHaveLength(2);
        expect(within(screen.getByRole('figure', { name: 'dashboard.serverList: empty' })).getByText('No servers yet'));
        expect(screen.getByRole('figure', { name: 'dashboard.serverList: loading' })).toBeInTheDocument();

        const details = screen.getByRole('complementary', { name: 'View details' });

        expect(within(details).getByText("'dashboard.serverList': ({ model, Default }) => <Default />,")).toBeVisible();
        expect(within(details).getByText('npx pterodactyl-theme eject dashboard.serverList')).toBeVisible();
        expect(within(details).getByText('dashboard.serverRow.metrics.after')).toBeInTheDocument();
        expect(within(details).getByRole('link', { name: 'dashboard.serverCard' })).toHaveAttribute(
            'href',
            '/panel/theme-workbench?view=dashboard.serverCard'
        );
    });

    it('switches fixtures through the URL and nested fixtures in place', async () => {
        const onFixture = await preview('page.dashboard');

        expect(await screen.findAllByText('Aurora card')).toHaveLength(2);

        fireEvent.click(screen.getByRole('tab', { name: 'error' }));
        expect(onFixture).toHaveBeenCalledWith('error');

        fireEvent.change(screen.getByRole('combobox', { name: 'Fixture of dashboard.serverList' }), {
            target: { value: 'empty' },
        });
        expect(await screen.findByText('No servers yet')).toBeInTheDocument();
    });

    it('shows the layer that rendered, and why a view has nothing to show', async () => {
        await preview('dashboard.serverCard');

        expect(await screen.findByText('Aurora card')).toBeInTheDocument();
        expect(screen.getByText(/rendered by aurora/)).toBeInTheDocument();

        cleanup();
        host?.dispose();
        await preview('page.admin.nodes');

        expect(screen.getByText('No fixtures yet')).toBeInTheDocument();
        expect(screen.getByText(/Classic has no source of its own to eject here yet/)).toBeInTheDocument();
    });

    it('contains a fixture that fails to render', async () => {
        await preview('test.broken');

        expect(await screen.findByRole('alert')).toHaveTextContent('The fixture is missing a field.');
    });

    it('renders a layout around a chosen page', async () => {
        await preview('layout.app');

        expect(screen.getByText('The routed page renders here.')).toBeInTheDocument();

        fireEvent.change(screen.getByRole('combobox', { name: 'Page inside' }), {
            target: { value: 'page.dashboard' },
        });

        expect(await screen.findByRole('heading', { name: 'Servers' })).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Account Settings' })).toHaveAttribute('href', '/account');
    });
});
