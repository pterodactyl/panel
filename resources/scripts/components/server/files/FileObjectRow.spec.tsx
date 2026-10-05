/** @vitest-environment jsdom */
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import type { FileObject } from '@/api/server/files/queries';
import type * as Registry from '@/extensions/registry';
import type * as Testing from '@/sdk/testing';
import type FileObjectRow from './FileObjectRow';
import type { FileRowActionsProvider } from './useFileActions';

let host: { dispose(): void } | undefined;
let registry: typeof Registry;
let testing: typeof Testing;
let Row: typeof FileObjectRow;
let RowActions: typeof FileRowActionsProvider;
beforeEach(async () => {
    vi.resetModules();
    const [registered, sdk, row, actions] = await Promise.all([
        import('@/extensions/registry'),
        import('@/sdk/testing'),
        import('./FileObjectRow'),
        import('./useFileActions'),
    ]);
    registry = registered;
    testing = sdk;
    Row = row.default;
    RowActions = actions.FileRowActionsProvider;
    vi.spyOn(console, 'error').mockImplementation(() => {});
}, 10000);
afterEach(() => {
    cleanup();
    host?.dispose();
    host = undefined;
    vi.restoreAllMocks();
});
const file: FileObject = {
    object: 'file_object',
    attributes: {
        name: 'config.json',
        mode: '-rw-r--r--',
        mode_bits: '644',
        size: 1024,
        is_file: true,
        is_symlink: false,
        mimetype: 'application/json',
        created_at: '2026-01-01T12:00:00Z',
        modified_at: '2026-01-01T12:00:00Z',
    },
};
function renderRow(owner = true) {
    const { createExtensionTestHost, createTestServer } = testing;
    const mounted = createExtensionTestHost({ server: createTestServer({ owner, permissions: [] }) });
    host = mounted;
    render(
        <RowActions>
            <Row file={file} />
        </RowActions>,
        { wrapper: mounted.Wrapper }
    );
}
it('preserves the core file link checkbox and menu when details are replaced', async () => {
    registry.prepareExtensions([{ id: 'details', entry: '/details.js', components: ['server.files.details'] }]);
    const batch = registry.createExtensionRegistryBatch();
    registry.registerComponentReplacement(
        'details',
        'server.files.details',
        () => <span>Custom presentation</span>,
        batch
    );
    registry.commitExtensionRegistryBatch('details', batch);
    renderRow();
    await screen.findByText('Custom presentation');
    expect(screen.getByRole('link', { name: 'config.json' })).toHaveAttribute(
        'href',
        '/server/test-server/files/edit#/config.json'
    );
    fireEvent.click(screen.getByRole('checkbox'));
    expect(screen.getByRole('checkbox')).toHaveAttribute('aria-checked', 'true');
    expect(screen.getByRole('button', { name: 'Open file options' })).toBeVisible();
});
it('does not expose a core edit link without file-content permission', async () => {
    registry.prepareExtensions([{ id: 'details', entry: '/details.js', components: ['server.files.details'] }]);
    const batch = registry.createExtensionRegistryBatch();
    registry.registerComponentReplacement(
        'details',
        'server.files.details',
        () => <span>Custom presentation</span>,
        batch
    );
    registry.commitExtensionRegistryBatch('details', batch);
    renderRow(false);
    await screen.findByText('Custom presentation');
    expect(screen.queryByRole('link', { name: 'config.json' })).not.toBeInTheDocument();
});
it('restores native details after a renderer failure with core controls intact', async () => {
    registry.prepareExtensions([{ id: 'details', entry: '/details.js', components: ['server.files.details'] }]);
    const batch = registry.createExtensionRegistryBatch();
    registry.registerComponentReplacement(
        'details',
        'server.files.details',
        () => {
            throw new Error('bad details');
        },
        batch
    );
    registry.commitExtensionRegistryBatch('details', batch);
    renderRow();
    expect(await screen.findByText('config.json')).toBeVisible();
    expect(screen.getByRole('link', { name: 'config.json' })).toBeVisible();
    expect(screen.getByRole('button', { name: 'Open file options' })).toBeVisible();
    expect(registry.getExtensionStates().find((state) => state.id === 'details')?.error).toContain('bad details');
});
