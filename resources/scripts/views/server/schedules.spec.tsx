/** @vitest-environment jsdom */
import { act, cleanup, fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import type { ReactNode } from 'react';
import type { AxiosAdapter } from 'axios';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type * as Router from '@tanstack/react-router';
import http from '@/api/http';
import {
    serverScheduleQueryOptions,
    serverSchedulesQueryOptions,
    type Schedule,
    type Task,
} from '@/api/server/schedules/queries';
import { createExtensionTestHost, createTestServer, type ExtensionTestHost } from '@/sdk/testing';
import { View, ViewArea, ViewFixtureProvider } from '@/views/View';
import { scheduleTaskForm } from './schedules';

vi.mock('@tanstack/react-router', async (importOriginal) => {
    const router = await importOriginal<typeof Router>();
    const useParams: typeof router.useParams = (options) => ({ ...router.useParams(options), scheduleId: 7 });

    return { ...router, useParams };
});

const server = createTestServer({ identifier: 'a1b2c3d4' });
const { uuid } = server.attributes;

const task = (id: number, sequence: number, action: Task['attributes']['action'], payload: string): Task => ({
    object: 'schedule_task',
    attributes: {
        id,
        sequence_id: sequence,
        action,
        payload,
        time_offset: sequence > 1 ? 30 : 0,
        is_queued: false,
        continue_on_failure: false,
        created_at: '2026-10-01T00:00:00Z',
        updated_at: '2026-10-01T00:00:00Z',
    },
});

const schedule = (id: number, name: string, options: Partial<Schedule['attributes']> = {}): Schedule => ({
    object: 'server_schedule',
    attributes: {
        id,
        name,
        cron: { minute: '0', hour: '4', day_of_month: '*', month: '*', day_of_week: '*' },
        is_active: true,
        is_processing: false,
        only_when_online: true,
        last_run_at: null,
        next_run_at: '2026-10-13T04:00:00Z',
        created_at: '2026-10-01T00:00:00Z',
        updated_at: '2026-10-01T00:00:00Z',
        relationships: { tasks: { object: 'list', data: [] } },
        ...options,
    },
});

let host: ExtensionTestHost | undefined;
let requests: { method: string; url: string; body: unknown }[];
const adapter = http.defaults.adapter;

beforeEach(() => {
    requests = [];
    http.defaults.adapter = (async (config) => {
        const body: unknown = config.data ? JSON.parse(String(config.data)) : undefined;

        requests.push({ method: config.method ?? 'get', url: config.url ?? '', body });

        return {
            data: config.method === 'post' && config.url?.endsWith('/schedules') ? schedule(9, 'Created') : '',
            status: 200,
            statusText: 'OK',
            headers: {},
            config,
        };
    }) satisfies AxiosAdapter;
});

afterEach(() => {
    cleanup();
    host?.dispose();
    host = undefined;
    http.defaults.adapter = adapter;
});

async function renderInServer(children: ReactNode, seed: (host: ExtensionTestHost) => void) {
    host = createExtensionTestHost({ server, path: '/server/a1b2c3d4/schedules' });
    seed(host);

    await act(async () => {
        render(<ViewArea area='server'>{children}</ViewArea>, { wrapper: host!.Wrapper });
    });
}

describe('server.schedules', () => {
    it('lists the schedules with their status and a link to each', async () => {
        await renderInServer(<View name='server.schedules' />, ({ queryClient }) =>
            queryClient.setQueryData(serverSchedulesQueryOptions(uuid).queryKey, {
                object: 'list',
                data: [schedule(1, 'Nightly restart'), schedule(2, 'Hourly backup', { is_processing: true })],
            })
        );

        expect(screen.getByRole('link', { name: 'Nightly restart' })).toHaveAttribute(
            'href',
            '/server/a1b2c3d4/schedules/1'
        );
        expect(screen.getByText('active')).toHaveClass('bg-success/90', 'rounded-full');
        expect(screen.getByText('processing')).toHaveAttribute('data-tone', 'neutral');
        expect(screen.getByRole('link', { name: 'Edit Hourly backup' })).toHaveAttribute(
            'href',
            '/server/a1b2c3d4/schedules/2'
        );
    });

    it('creates a schedule from the dialog', async () => {
        await renderInServer(<View name='server.schedules' />, ({ queryClient }) =>
            queryClient.setQueryData(serverSchedulesQueryOptions(uuid).queryKey, { object: 'list', data: [] })
        );

        fireEvent.click(screen.getAllByRole('button', { name: 'New schedule' })[0]);
        const dialog = await screen.findByRole('dialog');

        fireEvent.change(within(dialog).getByLabelText('Schedule name'), { target: { value: 'Nightly' } });
        await act(async () => {
            fireEvent.click(within(dialog).getByRole('button', { name: 'Create schedule' }));
        });

        await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
        expect(requests).toContainEqual({
            method: 'post',
            url: `/api/client/servers/${uuid}/schedules`,
            body: {
                is_active: true,
                only_when_online: true,
                name: 'Nightly',
                minute: '*/5',
                hour: '*',
                day_of_month: '*',
                month: '*',
                day_of_week: '*',
            },
        });
        expect(await screen.findByRole('link', { name: 'Created' })).toBeVisible();
    });

    it('offers no create or edit actions without permission', async () => {
        await renderInServer(
            <ViewFixtureProvider fixtures={{ 'server.schedules': 'readOnly' }}>
                <View name='server.schedules' />
            </ViewFixtureProvider>,
            () => {}
        );

        expect(screen.queryByRole('button', { name: 'New schedule' })).not.toBeInTheDocument();
        expect(screen.queryByRole('link', { name: /^Edit / })).not.toBeInTheDocument();
    });
});

describe('page.server.schedules.detail', () => {
    const seeded = schedule(7, 'Nightly restart', {
        relationships: {
            tasks: {
                object: 'list',
                data: [task(2, 2, 'power', 'restart'), task(1, 1, 'command', 'say Restarting soon')],
            },
        },
    });
    const seed = ({ queryClient }: ExtensionTestHost) =>
        queryClient.setQueryData(serverScheduleQueryOptions(uuid, 7).queryKey, seeded);

    it('shows the schedule with its tasks in order', async () => {
        await renderInServer(<View name='page.server.schedules.detail' />, seed);

        const rows = screen.getAllByRole('row').slice(1);

        expect(screen.getByRole('heading', { name: /Nightly restart/ })).toHaveTextContent('Active');
        expect(rows.map((row) => within(row).getAllByRole('cell')[0].textContent)).toEqual([
            'Send Commandsay Restarting soon',
            'Send Power Actionrestart',
        ]);
    });

    it('runs the schedule now', async () => {
        await renderInServer(<View name='page.server.schedules.detail' />, seed);

        await act(async () => {
            fireEvent.click(screen.getByRole('button', { name: 'Run Now' }));
        });

        expect(requests).toContainEqual({
            method: 'post',
            url: `/api/client/servers/${uuid}/schedules/7/execute`,
            body: undefined,
        });
        await waitFor(() => expect(screen.getByRole('button', { name: 'Run Now' })).toBeDisabled());
        expect(screen.getByRole('heading', { name: /Nightly restart/ })).toHaveTextContent('Processing');
    });

    it('deletes a task once confirmed', async () => {
        await renderInServer(<View name='page.server.schedules.detail' />, seed);

        fireEvent.click(screen.getByRole('button', { name: 'Delete task 2 (Send Power Action)' }));
        await act(async () => {
            fireEvent.click(await screen.findByRole('button', { name: 'Delete Task' }));
        });

        expect(requests).toContainEqual({
            method: 'delete',
            url: `/api/client/servers/${uuid}/schedules/7/tasks/2`,
            body: undefined,
        });
        await waitFor(() => expect(screen.getAllByRole('row')).toHaveLength(2));
    });

    it('returns to the list once the schedule is deleted', async () => {
        await renderInServer(<View name='page.server.schedules.detail' />, seed);

        fireEvent.click(screen.getByRole('button', { name: 'Delete' }));
        await act(async () => {
            fireEvent.click(within(await screen.findByRole('dialog')).getByRole('button', { name: 'Delete' }));
        });

        await waitFor(() => expect(host!.location().pathname).toBe('/server/a1b2c3d4/schedules'));
        // The deleted schedule is not fetched again while the page leaves.
        expect(requests).toEqual([
            { method: 'delete', url: `/api/client/servers/${uuid}/schedules/7`, body: undefined },
        ]);
    });
});

describe('the task form', () => {
    const form = scheduleTaskForm(0);
    const restart = {
        id: 3,
        sequence: 2,
        action: 'power',
        payload: 'stop',
        timeOffset: 60,
        continueOnFailure: true,
    } as const;

    it('starts from the task, or a command for a new one', () => {
        expect(form.values()).toEqual({ action: 'command', payload: '', timeOffset: 0, continueOnFailure: false });
        expect(form.values(restart)).toEqual({
            action: 'power',
            payload: 'stop',
            timeOffset: 60,
            continueOnFailure: true,
        });
    });

    it('resets the payload for another action and restores the task own', () => {
        expect(form.payloadFor('power')).toBe('start');
        expect(form.payloadFor('backup', restart)).toBe('');
        expect(form.payloadFor('power', restart)).toBe('stop');
    });

    it('validates the payload, the offset and the backup limit', () => {
        expect(form.validatePayload('command', '')).toBe('A task payload must be provided.');
        expect(form.validatePayload('backup', '')).toBeUndefined();
        expect(form.validateTimeOffset(901)).toBe('The time offset must be less than 900 seconds.');
        expect(form.validateTimeOffset(null)).toBe('A time offset must be provided.');
        expect(form.validate({ ...form.values(), action: 'backup' })).toContain('backup limit is set to 0');
        expect(scheduleTaskForm(2).validate({ ...form.values(), action: 'backup' })).toBeNull();
    });
});
