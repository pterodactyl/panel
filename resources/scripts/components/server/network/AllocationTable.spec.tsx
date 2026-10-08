// @vitest-environment jsdom

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { flexRender, getCoreRowModel, useReactTable } from '@tanstack/react-table';
import { act, cleanup, fireEvent, render, screen } from '@testing-library/react';
import type { AxiosAdapter } from 'axios';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import http from '@/api/http';
import type { ServerAllocation } from '@/api/server/network/queries';
import { allocationColumns } from './AllocationTable';

vi.mock('@/api/server/queries', () => ({ useCurrentServerUuid: () => 'server' }));
vi.mock('@/plugins/notifications', () => ({ notifyHttpError: vi.fn() }));

const allocation: ServerAllocation = {
    object: 'allocation',
    attributes: { id: 7, ip: '10.0.0.1', ip_alias: null, port: 25565, notes: null, is_default: false },
};
const originalAdapter = http.defaults.adapter;
let notes: unknown[];
let client: QueryClient;

function NotesCell() {
    const table = useReactTable({
        data: [allocation],
        columns: allocationColumns,
        getCoreRowModel: getCoreRowModel(),
        getRowId: (row) => String(row.attributes.id),
    });
    const cell = table
        .getRowModel()
        .rows[0].getVisibleCells()
        .find((visible) => visible.column.id === 'notes')!;

    return <>{flexRender(cell.column.columnDef.cell, cell.getContext())}</>;
}

const renderNotes = () =>
    render(
        <QueryClientProvider client={client}>
            <NotesCell />
        </QueryClientProvider>
    );
const settle = () =>
    act(async () => {
        await vi.advanceTimersByTimeAsync(1);
    });

beforeEach(() => {
    vi.useFakeTimers();
    notes = [];
    client = new QueryClient();
    http.defaults.adapter = (async (config) => {
        const body = JSON.parse(String(config.data)) as { notes: string };

        notes.push(body.notes);

        return {
            config,
            headers: {},
            status: 200,
            statusText: 'OK',
            data: { ...allocation, attributes: { ...allocation.attributes, notes: body.notes } },
        };
    }) satisfies AxiosAdapter;
});

afterEach(() => {
    cleanup();
    client.clear();
    http.defaults.adapter = originalAdapter;
    vi.useRealTimers();
});

it('saves the notes as soon as the field loses focus', async () => {
    renderNotes();
    const field = screen.getByRole('textbox', { name: 'Notes for 10.0.0.1:25565' });

    fireEvent.change(field, { target: { value: 'Game port' } });
    fireEvent.blur(field);
    await settle();

    expect(notes).toEqual(['Game port']);
    await act(async () => {
        await vi.advanceTimersByTimeAsync(750);
    });
    expect(notes).toEqual(['Game port']);
});

it('saves pending notes when the table unmounts', async () => {
    const view = renderNotes();

    fireEvent.change(screen.getByRole('textbox'), { target: { value: 'Query port' } });
    view.unmount();
    await settle();

    expect(notes).toEqual(['Query port']);
});
