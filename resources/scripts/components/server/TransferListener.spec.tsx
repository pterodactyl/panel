/** @vitest-environment jsdom */
import { act, cleanup, render, screen } from '@testing-library/react';
import { afterEach, expect, it, vi } from 'vitest';
import { serverQueryOptions } from '@/api/server/queries';
import { createExtensionTestHost, createTestServer } from '@/sdk/testing';
import { useSocketConnected } from '@/state/server';
import TransferListener from './TransferListener';

function ConnectionProbe() {
    return <span>{useSocketConnected() ? 'connected' : 'disconnected'}</span>;
}

const mount = async (isTransferring: boolean) => {
    const server = createTestServer();
    const host = createExtensionTestHost({
        server: { ...server, attributes: { ...server.attributes, is_transferring: isTransferring } },
    });
    const refetch = vi.spyOn(host.queryClient, 'refetchQueries').mockResolvedValue();
    const invalidate = vi.spyOn(host.queryClient, 'invalidateQueries').mockResolvedValue();
    const isTransferringNow = () =>
        host.queryClient.getQueryData(serverQueryOptions(server.attributes.identifier).queryKey)?.attributes
            .is_transferring;

    render(
        <>
            <TransferListener />
            <ConnectionProbe />
        </>,
        { wrapper: host.Wrapper }
    );
    await screen.findByText('connected');

    return { host, refetch, invalidate, isTransferringNow };
};

afterEach(() => {
    cleanup();
    vi.restoreAllMocks();
});

it('tracks in-progress and failed transfers using the statuses Wings publishes', async () => {
    const { host, isTransferringNow } = await mount(false);

    act(() => host.emitWebsocket('transfer status', 'processing'));
    expect(isTransferringNow()).toBe(true);

    act(() => host.emitWebsocket('transfer status', 'failure'));
    expect(isTransferringNow()).toBe(false);
});

it('refetches the server once the transfer completes', async () => {
    const { host, refetch, isTransferringNow } = await mount(true);

    act(() => host.emitWebsocket('transfer status', 'completed'));

    expect(refetch).toHaveBeenCalledOnce();
    expect(isTransferringNow()).toBe(true);
});

it('refetches the server when the socket disconnects mid-transfer, and only then', async () => {
    const { host, invalidate } = await mount(true);

    expect(invalidate).not.toHaveBeenCalled();

    act(() => host.setConnected(false));

    expect(invalidate).toHaveBeenCalledOnce();
});
