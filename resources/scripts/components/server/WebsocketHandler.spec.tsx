/** @vitest-environment jsdom */
import { act, cleanup, render, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import { getServerWebsocketCredentials, serverQueryOptions, type Server } from '@/api/server/queries';
import type * as ServerQueries from '@/api/server/queries';
import { Websocket } from '@/plugins/Websocket';
import { createExtensionTestHost, createTestServer } from '@/sdk/testing';
import WebsocketHandler from './WebsocketHandler';

vi.mock('@/api/server/queries', async (importOriginal) => ({
    ...(await importOriginal<typeof ServerQueries>()),
    getServerWebsocketCredentials: vi.fn(),
}));

const opened: string[] = [];

beforeEach(() => {
    opened.length = 0;
    Websocket.createSocket = (url) => {
        opened.push(url);

        return { readyState: 0, send() {}, close() {}, onclose: null, onerror: null, onmessage: null, onopen: null };
    };

    vi.mocked(getServerWebsocketCredentials).mockImplementation(async () => ({
        token: 'token',
        socket: `wss://node-${opened.length + 1}.test/api/servers/uuid/ws`,
    }));
});

afterEach(() => {
    cleanup();
    vi.restoreAllMocks();
    Websocket.createSocket = (url) => new WebSocket(url);
});

const moveToNode = (server: Server, node: string): Server => ({
    ...server,
    attributes: { ...server.attributes, node, sftp_details: { ...server.attributes.sftp_details, ip: `${node}.test` } },
});

it('reconnects with fresh credentials only when the server moves to another node', async () => {
    const server = createTestServer();
    const host = createExtensionTestHost({ server });
    const queryKey = serverQueryOptions(server.attributes.identifier).queryKey;

    render(<WebsocketHandler />, { wrapper: host.Wrapper });
    await waitFor(() => expect(opened).toEqual(['wss://node-1.test/api/servers/uuid/ws']));

    act(() => {
        host.queryClient.setQueryData(queryKey, {
            ...server,
            attributes: { ...server.attributes, is_transferring: true, name: 'Renamed' },
        });
    });
    await act(() => Promise.resolve());
    expect(getServerWebsocketCredentials).toHaveBeenCalledTimes(1);

    act(() => {
        host.queryClient.setQueryData(queryKey, moveToNode(server, 'destination'));
    });

    await waitFor(() =>
        expect(opened).toEqual(['wss://node-1.test/api/servers/uuid/ws', 'wss://node-2.test/api/servers/uuid/ws'])
    );
    expect(getServerWebsocketCredentials).toHaveBeenCalledTimes(2);
});
