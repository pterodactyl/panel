/** @vitest-environment jsdom */
import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import type * as ExtensionRegistry from '@/extensions/registry';
import type * as Testing from '@/sdk/testing';
import type * as ViewRegistry from '@/views/registry';
import type * as ViewRoutes from './viewRoutes';

let extensions: typeof ExtensionRegistry;
let testing: typeof Testing;
let views: typeof ViewRegistry;
let routes: typeof ViewRoutes;
let host: { dispose(): void } | undefined;

beforeEach(async () => {
    vi.resetModules();
    [extensions, testing, views, routes] = await Promise.all([
        import('@/extensions/registry'),
        import('@/sdk/testing'),
        import('@/views/registry'),
        import('./viewRoutes'),
    ]);
    vi.spyOn(console, 'error').mockImplementation(() => {});

    extensions.prepareExtensions([{ id: 'ext', entry: '/ext.js' }]);
    const batch = extensions.createExtensionRegistryBatch();

    extensions.registerSlotComponent('ext', 'server.backups.before', () => <p>before slot</p>, batch);
    extensions.registerSlotComponent('ext', 'server.backups.after', () => <p>after slot</p>, batch);
    extensions.commitExtensionRegistryBatch('ext', batch);
    views.registerViewLayer('classic', 'classic', { 'page.server.backups': () => <p>classic backups</p> });
});

afterEach(() => {
    cleanup();
    host?.dispose();
    host = undefined;
    vi.restoreAllMocks();
});

function renderPage(permissions: string[]) {
    const Page = routes.pageRoute('page.server.backups', 'backup.*');
    const mounted = testing.createExtensionTestHost({
        server: testing.createTestServer({ owner: false, permissions }),
        path: '/server/test-server/backups',
    });

    host = mounted;
    render(<Page />, { wrapper: mounted.Wrapper });
}

it('renders the engine slots around a page view a theme replaced wholesale', async () => {
    views.registerViewLayer('aurora', 'theme', { 'page.server.backups': () => <p>themed backups</p> });
    renderPage(['backup.read']);

    expect(await screen.findByText('themed backups')).toBeVisible();
    expect(screen.queryByText('classic backups')).not.toBeInTheDocument();
    expect(screen.getByText('before slot').compareDocumentPosition(screen.getByText('themed backups'))).toBe(
        Node.DOCUMENT_POSITION_FOLLOWING
    );
    expect(screen.getByText('after slot')).toBeVisible();
});

it('keeps the permission guard in front of the page and its slots', async () => {
    renderPage([]);

    expect(await screen.findByText('You do not have permission to access this page.')).toBeVisible();
    expect(screen.queryByText('classic backups')).not.toBeInTheDocument();
    expect(screen.queryByText('before slot')).not.toBeInTheDocument();
});

it('preloads the chunks of the page view for the router', async () => {
    const preload = vi.fn(async () => {});

    views.registerViewLayer('classic', 'classic', {
        'page.server.backups': Object.assign(() => null, { preload }),
    });
    await routes.pageRoute('page.server.backups', 'backup.*').preload();

    expect(preload).toHaveBeenCalledOnce();
});
