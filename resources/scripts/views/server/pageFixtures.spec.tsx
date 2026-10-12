/** @vitest-environment jsdom */
import { act, cleanup, render, screen } from '@testing-library/react';
import { afterAll, afterEach, beforeAll, describe, expect, it, vi } from 'vitest';
import http from '@/api/http';
import { createExtensionTestHost, type ExtensionTestHost } from '@/sdk/testing';
import { getViewDefinition, getViewDefinitions } from '../registry';
import { ViewArea, ViewFixtureProvider, View } from '../View';

vi.mock('ghostty-web', () => ({ Ghostty: { load: () => new Promise(() => {}) } }));

const views = getViewDefinitions().filter(({ name }) =>
    /^(page\.server\.(console|files|files\.editor|databases|network|startup)|server\.(terminal|power|stats|graphs|features?|files|databases|allocations|startup)\b)/.test(
        name
    )
);
const cases = views.flatMap(({ name, fixtures }) =>
    Object.keys(fixtures ?? {}).map((fixture) => [name, fixture] as const)
);
const originalAdapter = http.defaults.adapter;
let host: ExtensionTestHost | undefined;
let portal: HTMLElement;
let requests: string[] = [];

beforeAll(() => {
    vi.stubGlobal(
        'ResizeObserver',
        class {
            observe() {}
            disconnect() {}
        }
    );
    portal = document.body.appendChild(Object.assign(document.createElement('div'), { id: 'modal-portal' }));
    http.defaults.adapter = async (config) => {
        requests.push(String(config.url));

        throw new Error('Fixtures must not reach the network.');
    };
});

afterAll(() => {
    portal.remove();
    http.defaults.adapter = originalAdapter;
    vi.unstubAllGlobals();
});

afterEach(() => {
    cleanup();
    host?.dispose();
    host = undefined;
});

async function renderFixture(name: string, fixture: string) {
    host = createExtensionTestHost({ path: '/' });
    requests = [];

    await act(async () => {
        render(
            <ViewArea area='server'>
                <ViewFixtureProvider fixtures={{ [name]: fixture }}>
                    <View name={name} />
                </ViewFixtureProvider>
            </ViewArea>,
            { wrapper: host!.Wrapper }
        );
    });
}

describe('server views from fixtures', () => {
    it('covers every page and block of the lane', () => {
        expect(views.map(({ name }) => name)).toEqual(
            expect.arrayContaining([
                'page.server.console',
                'server.terminal',
                'server.power',
                'server.stats',
                'server.graphs',
                'server.features',
                'server.feature.eula',
                'page.server.files',
                'server.files.manager',
                'server.files.details',
                'page.server.files.editor',
                'server.files.editor',
                'page.server.databases',
                'server.databases',
                'page.server.network',
                'server.allocations',
                'page.server.startup',
                'server.startup.command',
                'server.startup.image',
                'server.startup.variables',
            ])
        );
    });

    it.each(cases)('renders %s from its %s fixture without the network', async (name, fixture) => {
        await renderFixture(name, fixture);

        expect(requests).toEqual([]);
        expect(getViewDefinition(name)?.fixtures?.[fixture]).toBeDefined();
    });
});

describe('page compositions', () => {
    it('places the console blocks on the console page', async () => {
        await renderFixture('page.server.console', 'default');

        expect(await screen.findByRole('heading', { name: 'Survival' })).toBeVisible();
        expect(screen.getByRole('button', { name: 'Restart' })).toBeVisible();
        expect(screen.getByText('203.0.113.10:25565')).toBeVisible();
        expect(screen.getByRole('textbox', { name: 'Console command input.' })).toBeVisible();
        expect(screen.getByRole('heading', { name: 'Network' })).toBeVisible();
    });

    it('warns above the console while the server installs', async () => {
        await renderFixture('page.server.console', 'installing');

        expect(await screen.findByText(/running its installation process/)).toBeVisible();
    });

    it('lists the directory on the files page', async () => {
        await renderFixture('page.server.files', 'default');

        expect(await screen.findByRole('link', { name: 'config.json' })).toHaveAttribute(
            'href',
            '/server/test-server/files/edit#/config.json'
        );
        expect(screen.getByRole('link', { name: 'plugins' })).toHaveAttribute(
            'href',
            '/server/test-server/files#/plugins'
        );
        expect(screen.getByRole('button', { name: 'New file' })).toBeVisible();
    });

    it('shows the editor with its breadcrumbs on the editor page', async () => {
        await renderFixture('page.server.files.editor', 'file');

        expect(await screen.findByRole('link', { name: 'config' })).toHaveAttribute(
            'href',
            '/server/test-server/files#/config'
        );
        expect(screen.getByText('server.properties')).toBeVisible();
        expect(screen.getByRole('button', { name: 'Save Content' })).toBeVisible();
    });

    it('swaps a page for its error', async () => {
        await renderFixture('page.server.databases', 'error');

        expect(await screen.findByText('Request failed with status code 500.')).toBeVisible();
    });

    it('composes the startup page from its blocks', async () => {
        await renderFixture('page.server.startup', 'default');

        expect(await screen.findByText('java -Xms128M -Xmx2048M -jar server.jar nogui')).toBeVisible();
        expect(screen.getByText('Docker Image')).toBeVisible();
        expect(screen.getByRole('heading', { name: 'Variables' })).toBeVisible();
        expect(screen.getByText('Read Only')).toHaveAttribute('data-ui', 'badge');
    });
});
