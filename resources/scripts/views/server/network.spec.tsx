// @vitest-environment jsdom

import { act, cleanup, fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import type { AxiosAdapter } from 'axios';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import http from '@/api/http';
import { serverAllocationsQueryOptions, type ServerAllocation } from '@/api/server/network/queries';
import { createExtensionTestHost, createTestServer, type ExtensionTestHost } from '@/sdk/testing';
import { View } from '../View';

vi.mock('@/plugins/notifications', () => ({ notifyHttpError: vi.fn() }));

const allocation = (id: number, port: number, primary = false): ServerAllocation => ({
    object: 'allocation',
    attributes: { id, ip: '10.0.0.1', ip_alias: null, port, notes: null, is_default: primary },
});
const originalAdapter = http.defaults.adapter;
const server = createTestServer();
let requests: { method: string; path: string; body: unknown }[];
let host: ExtensionTestHost | undefined;
let portal: HTMLElement;

beforeEach(() => {
    vi.useFakeTimers({ shouldAdvanceTime: true });
    requests = [];
    portal = document.body.appendChild(Object.assign(document.createElement('div'), { id: 'modal-portal' }));
    http.defaults.adapter = (async (config) => {
        const url = new URL(config.url!, 'https://panel.test');
        const body = config.data ? (JSON.parse(String(config.data)) as { notes?: string }) : undefined;

        requests.push({ method: config.method!, path: url.pathname, body });

        // Making an allocation primary reloads the server.
        if (config.method === 'get' && /\/servers\/[^/]+$/.test(url.pathname)) {
            return { config, headers: {}, status: 200, statusText: 'OK', data: server };
        }

        const saved = { ...allocation(7, 25565).attributes, notes: body?.notes ?? null };

        return {
            config,
            headers: {},
            status: 200,
            statusText: 'OK',
            data: { object: 'allocation', attributes: saved },
        };
    }) satisfies AxiosAdapter;
});

afterEach(() => {
    cleanup();
    host?.dispose();
    host = undefined;
    portal.remove();
    http.defaults.adapter = originalAdapter;
    vi.useRealTimers();
});

async function renderAllocations(allocations: ServerAllocation[], limit = 3) {
    server.attributes.feature_limits.allocations = limit;
    host = createExtensionTestHost({ server });
    host.queryClient.setQueryData(serverAllocationsQueryOptions(server.attributes.uuid).queryKey, {
        object: 'list',
        data: allocations,
    });

    const view = render(<View name='page.server.network' />, { wrapper: host.Wrapper });

    await screen.findAllByText('10.0.0.1', { selector: 'code' });

    return view;
}

const settle = () =>
    act(async () => {
        await vi.advanceTimersByTimeAsync(1);
    });
const notes = () => requests.filter((request) => request.method === 'post' && request.path.endsWith('/allocations/7'));

it('saves the notes as soon as the field loses focus', async () => {
    await renderAllocations([allocation(7, 25565)]);
    const field = screen.getByRole('textbox', { name: 'Notes for 10.0.0.1:25565' });

    fireEvent.change(field, { target: { value: 'Game port' } });
    fireEvent.blur(field);
    await settle();

    expect(notes().map((request) => request.body)).toEqual([{ notes: 'Game port' }]);
    await act(async () => {
        await vi.advanceTimersByTimeAsync(750);
    });
    expect(notes()).toHaveLength(1);
});

it('saves pending notes when the table unmounts', async () => {
    const view = await renderAllocations([allocation(7, 25565)]);

    fireEvent.change(screen.getByRole('textbox'), { target: { value: 'Query port' } });
    view.unmount();
    await settle();

    expect(notes().map((request) => request.body)).toEqual([{ notes: 'Query port' }]);
});

it('marks the primary allocation and lets another one become primary or be removed', async () => {
    await renderAllocations([allocation(1, 25565, true), allocation(7, 25575)], 4);

    expect(screen.getByText('Primary')).toHaveAttribute('data-ui', 'badge');
    expect(screen.getByText('You are currently using 2 of 4 allowed allocations for this server.')).toBeVisible();
    expect(screen.getAllByRole('button', { name: 'New allocation' })).toHaveLength(1);

    fireEvent.click(screen.getByRole('button', { name: 'Delete allocation 10.0.0.1:25575' }));
    const dialog = await screen.findByRole('dialog');

    fireEvent.click(within(dialog).getByRole('button', { name: 'Delete' }));
    await waitFor(() =>
        expect(requests).toContainEqual(
            expect.objectContaining({ method: 'delete', path: expect.stringMatching(/\/allocations\/7$/) })
        )
    );
});

it('makes another allocation the primary one', async () => {
    await renderAllocations([allocation(1, 25565, true), allocation(7, 25575)]);

    fireEvent.click(screen.getByRole('button', { name: 'Make Primary' }));
    await waitFor(() => expect(screen.getAllByText('Primary')).toHaveLength(1));
    expect(requests.some((request) => request.path.endsWith('/allocations/7/primary'))).toBe(true);
    expect(screen.getByRole('button', { name: 'Make Primary' })).toBeVisible();
});

it('leaves allocations to an administrator when the server has no limit', async () => {
    await renderAllocations([allocation(1, 25565, true)], 0);

    expect(screen.queryByText(/allowed allocations/)).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'New allocation' })).not.toBeInTheDocument();
});
