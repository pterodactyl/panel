/** @vitest-environment jsdom */
import { act, cleanup, fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import AppToaster from '@/components/elements/AppToaster';
import { lazy, Suspense, useState } from 'react';
import { QueryClient, QueryClientProvider, useSuspenseQuery } from '@tanstack/react-query';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { SlotComponentProps } from '@/extensions/registry';

afterEach(cleanup);

describe('extension Slot', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.spyOn(console, 'error').mockImplementation(() => {});
    });

    it('renders registrations in order with the anchor payload', async () => {
        const registry = await import('@/extensions/registry');
        const { default: Slot } = await import('@/extensions/Slot');
        const First = ({ data }: SlotComponentProps) => <span>first:{(data as { pathname: string }).pathname}</span>;
        const Second = ({ data }: SlotComponentProps) => <span>second:{(data as { pathname: string }).pathname}</span>;

        registry.registerSlotComponent('first-extension', 'auth.login.before', First);
        registry.registerSlotComponent('second-extension', 'auth.login.before', Second);

        render(<Slot name={'auth.login.before'} data={{ pathname: 'survival', params: {}, search: {} }} />);

        expect(screen.getAllByText(/survival/).map((element) => element.textContent)).toEqual([
            'first:survival',
            'second:survival',
        ]);
    });

    it('isolates a crashing registration without hiding healthy extensions', async () => {
        const registry = await import('@/extensions/registry');
        const { default: Slot } = await import('@/extensions/Slot');
        const Broken = () => {
            throw new Error('render failed');
        };
        const Healthy = () => <span>healthy extension</span>;

        registry.registerSlotComponent('broken-extension', 'dashboard.before', Broken);
        registry.registerSlotComponent('healthy-extension', 'dashboard.before', Healthy);

        render(<Slot name={'dashboard.before'} />);

        expect(screen.getByText('healthy extension')).toBeInTheDocument();
        expect(registry.getExtensionStates()).toContainEqual({
            id: 'broken-extension',
            status: 'failed',
            error: expect.stringContaining('render failed'),
        });
    });
});

it('keeps the host and healthy registrations visible while a lazy slot loads', async () => {
    const registry = await import('@/extensions/registry');
    const { default: Slot } = await import('@/extensions/Slot');
    let resolve!: (module: { default: () => React.ReactNode }) => void;
    const Lazy = lazy(
        () =>
            new Promise<{ default: () => React.ReactNode }>((done) => {
                resolve = done;
            })
    );
    registry.registerSlotComponent('lazy', 'dashboard.after', Lazy);
    registry.registerSlotComponent('healthy', 'dashboard.after', () => <span>healthy sibling</span>);
    render(
        <Suspense fallback={<p>whole page waiting</p>}>
            <button>Core control</button>
            <Slot name='dashboard.after' />
        </Suspense>
    );
    expect(screen.getByRole('button', { name: 'Core control' })).toBeVisible();
    expect(screen.getByText('healthy sibling')).toBeVisible();
    expect(screen.queryByText('whole page waiting')).not.toBeInTheDocument();
    await act(async () => resolve({ default: () => <span>loaded mod</span> }));
    expect(screen.getByText('loaded mod')).toBeVisible();
});

it('isolates suspense queries and lets an errored query recover on explicit retry', async () => {
    const registry = await import('@/extensions/registry');
    const { default: Slot } = await import('@/extensions/Slot');
    const queryFn = vi.fn().mockRejectedValueOnce(new Error('temporary')).mockResolvedValue('recovered');
    function QuerySlot() {
        const { data } = useSuspenseQuery({ queryKey: ['extension-recovery'], queryFn, retry: false });
        return <span>{data}</span>;
    }
    registry.registerSlotComponent('query', 'account.overview.before', QuerySlot);
    const client = new QueryClient({ defaultOptions: { queries: { retry: false } } });
    render(
        <QueryClientProvider client={client}>
            <AppToaster />
            <span>host stays</span>
            <Slot name='account.overview.before' />
        </QueryClientProvider>
    );
    expect(screen.getByText('host stays')).toBeVisible();
    fireEvent.click(await screen.findByRole('button', { name: 'Retry extension' }));
    expect(await screen.findByText('recovered')).toBeVisible();
    expect(queryFn).toHaveBeenCalledTimes(2);
    client.clear();
});

it('shows late registrations without resetting healthy mod state', async () => {
    const registry = await import('@/extensions/registry');
    const { default: Slot } = await import('@/extensions/Slot');
    function Counter() {
        const [count, setCount] = useState(0);
        return <button onClick={() => setCount(count + 1)}>Count {count}</button>;
    }
    registry.registerSlotComponent('counter', 'account.overview.after', Counter);
    render(<Slot name='account.overview.after' />);
    fireEvent.click(screen.getByRole('button', { name: 'Count 0' }));
    act(() => registry.registerSlotComponent('late', 'account.overview.after', () => <span>late arrival</span>));
    await waitFor(() => expect(screen.getByText('late arrival')).toBeVisible());
    expect(screen.getByRole('button', { name: 'Count 1' })).toBeVisible();
});

it('keeps recovery controls outside clickable slot hosts', async () => {
    const registry = await import('@/extensions/registry');
    const { default: Slot } = await import('@/extensions/Slot');
    registry.registerSlotComponent('row-failure', 'panel.overview.before', () => {
        throw new Error('broken row');
    });
    render(
        <>
            <AppToaster />
            <a href='/host'>
                Host row
                <Slot name='panel.overview.before' />
            </a>
        </>
    );
    expect(within(screen.getByRole('link', { name: 'Host row' })).queryByRole('button')).not.toBeInTheDocument();
    expect(await screen.findByRole('button', { name: 'Retry extension' })).toBeVisible();
});

it('reports a failure repeated in every row once and retries every row together', async () => {
    const errors = vi.spyOn(console, 'error').mockImplementation(() => {});
    const registry = await import('@/extensions/registry');
    const { default: Slot } = await import('@/extensions/Slot');
    let broken = true;
    registry.registerSlotComponent('rows', 'dashboard.serverRow.after', () => {
        if (broken) throw new Error('row failed');
        return <span>row ready</span>;
    });
    render(
        <>
            <AppToaster />
            {['a', 'b', 'c'].map((uuid) => (
                <Slot key={uuid} name='dashboard.serverRow.after' data={{ attributes: { uuid } } as never} />
            ))}
        </>
    );

    expect(await screen.findAllByRole('button', { name: 'Retry extension' })).toHaveLength(1);
    const reports = errors.mock.calls.filter(([message]) => String(message).startsWith('[extensions] "rows"'));
    expect(reports).toHaveLength(1);
    expect(registry.getExtensionStates().find((state) => state.id === 'rows')).toEqual({
        id: 'rows',
        status: 'failed',
        error: 'slot "dashboard.serverRow.after": row failed',
    });

    broken = false;
    fireEvent.click(screen.getByRole('button', { name: 'Retry extension' }));

    expect(await screen.findAllByText('row ready')).toHaveLength(3);
    await waitFor(() => expect(screen.queryByRole('button', { name: 'Retry extension' })).not.toBeInTheDocument());
});
