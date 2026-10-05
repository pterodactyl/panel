/** @vitest-environment jsdom */
import { act, cleanup, render, screen } from '@testing-library/react';
import { afterEach, expect, it, vi } from 'vitest';
import { createExtensionTestHost, createTestServer } from '@/sdk/testing';
import { useSocketConnected } from '@/state/server';
import { SocketEvent } from '@/components/server/events';
import InstallListener from './InstallListener';

function ConnectionProbe() {
    return <span>{useSocketConnected() ? 'connected' : 'disconnected'}</span>;
}

afterEach(() => {
    cleanup();
    vi.restoreAllMocks();
});

it.each([SocketEvent.INSTALL_COMPLETED, SocketEvent.BACKUP_RESTORE_COMPLETED])(
    'refreshes every resource of the server after "%s"',
    async (event) => {
        const server = createTestServer();
        const host = createExtensionTestHost({ server });
        vi.spyOn(host.queryClient, 'refetchQueries').mockResolvedValue();
        const ownKey = [{ _id: 'clientListAllocations', path: { server_uuid: server.attributes.uuid } }];
        const otherKey = [{ _id: 'clientListAllocations', path: { server_uuid: 'another-server' } }];
        host.queryClient.setQueryData(ownKey, { data: [] });
        host.queryClient.setQueryData(otherKey, { data: [] });

        render(
            <>
                <InstallListener />
                <ConnectionProbe />
            </>,
            { wrapper: host.Wrapper }
        );
        await screen.findByText('connected');

        act(() => host.emitWebsocket(event, ''));

        expect(host.queryClient.getQueryState(ownKey)?.isInvalidated).toBe(true);
        expect(host.queryClient.getQueryState(otherKey)?.isInvalidated).toBe(false);
    }
);
