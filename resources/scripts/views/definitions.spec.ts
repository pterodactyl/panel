/** @vitest-environment jsdom */
import { QueryClient } from '@tanstack/react-query';
import { createMemoryHistory, createRouter, type AnyRouter } from '@tanstack/react-router';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type * as Registry from './registry';
import type * as Engine from './View';
import type * as Lazy from './lazy';

let views: typeof Registry;
let engine: typeof Engine;
let lazy: typeof Lazy;
let router: AnyRouter;

beforeEach(async () => {
    vi.resetModules();
    vi.spyOn(console, 'error').mockImplementation(() => {});

    const [routes, classic] = await Promise.all([import('@/router/routeTree'), import('./classic')]);

    [views, engine, lazy] = await Promise.all([import('./registry'), import('./View'), import('./lazy')]);
    // What the boot imports: every view declared, classic's layers registered, no area module loaded.
    await import('./definitions');
    classic.registerClassicTheme();
    router = createRouter({
        routeTree: routes.buildRouteTree([]),
        context: { queryClient: new QueryClient() },
        history: createMemoryHistory(),
    });
});

/** Implemented, with every classic chunk of the view loaded: it renders without suspending. */
const ready = (name: string) =>
    views.getViewDefinition(name)?.use !== undefined &&
    views.getViewChain(name).every(({ component }) => !lazy.isPendingLazyView(component));

/** What the router loads before rendering a route: the chunks of each route from the root down. */
const loadRoutes = (ids: readonly string[]) =>
    Promise.all(ids.map((id) => router.loadRouteChunk(router.looseRoutesById[id])));

describe('view implementations', () => {
    it('declares every view up front and loads none of their hooks or fixtures at boot', () => {
        // Only the opaque views, whose model is empty, are implemented where they are declared.
        const real = views
            .getViewDefinitions()
            .filter(({ name }) => !name.startsWith('page.admin.') && name !== 'app.search');

        expect(real.length).toBeGreaterThan(60);
        expect(real.filter((definition) => !definition.load || definition.use || definition.fixtures)).toEqual([]);
        expect(views.getViewDefinition('dashboard.serverList')?.slots).toContain('dashboard.serverRow.before');
    });

    it('loads each page with its route, so a sign-in through to a server backup never waits on another chunk', async () => {
        // The views each page places, as classic composes it.
        const journey = [
            { routes: ['/auth', '/auth/login'], views: ['layout.auth', 'page.auth.login', 'auth.loginForm'] },
            {
                routes: ['/authenticated', '/authenticated/'],
                views: ['layout.app', 'page.dashboard', 'dashboard.serverList', 'dashboard.serverCard'],
            },
            {
                routes: ['/authenticated/server/$id', '/authenticated/server/$id/'],
                views: ['layout.server', 'server.conflict'],
            },
            {
                routes: ['/authenticated/server/$id/files'],
                views: ['page.server.files', 'server.files.manager', 'server.files.details'],
            },
            { routes: ['/authenticated/server/$id/backups'], views: ['page.server.backups', 'server.backups'] },
        ];

        for (const step of journey) {
            expect(step.views.every(ready), step.routes.at(-1)).toBe(false);
            // oxlint-disable-next-line no-await-in-loop -- each navigation finds what the ones before it loaded.
            await loadRoutes(step.routes);
            expect(
                step.views.filter((name) => !ready(name)),
                step.routes.at(-1)
            ).toEqual([]);
        }
    });

    it('loads the console with its blocks and feature prompts, which the server layout keeps mounted', async () => {
        const placed = [
            'page.server.console',
            'server.terminal',
            'server.power',
            'server.stats',
            'server.graphs',
            'server.features',
            'server.feature.eula',
            'server.feature.javaVersion',
            'server.feature.gslToken',
            'server.feature.pidLimit',
            'server.feature.steamDiskSpace',
            'server.feature.hytaleOauth',
        ];

        await engine.preloadView('page.server.console');

        expect(placed.filter((name) => !ready(name))).toEqual([]);
    });

    it('loads every fixture when the test runtime loads the catalog, and only then', async () => {
        expect(views.getViewDefinition('server.backups')?.fixtures).toBeUndefined();
        await (await import('./catalog')).loadViewCatalog();

        const definitions = views.getViewDefinitions().filter((definition) => definition.load);

        expect(definitions.filter((definition) => !definition.use)).toEqual([]);
        expect(Object.keys(views.getViewDefinition('server.backups')?.fixtures ?? {})).toContain('empty');
    });
});
