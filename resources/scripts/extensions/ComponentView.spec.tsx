/** @vitest-environment jsdom */
import { act, cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { createContext, useContext, useState, type ComponentType } from 'react';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import type { ReplacementProps, ComponentReplacement } from './componentTypes';
import type * as Registry from './registry';

let registry: typeof Registry;
beforeEach(async () => {
    vi.resetModules();
    registry = await import('./registry');
    vi.spyOn(console, 'error').mockImplementation(() => {});
});
afterEach(() => {
    cleanup();
    vi.useRealTimers();
    vi.restoreAllMocks();
});
const model = { name: 'config.json', kind: 'file' as const, size: 12, modifiedAt: '2026-10-02T12:00:00Z' };
const Part = () => null;
const parts = { icon: Part, name: Part, size: Part, modified: Part };
const Default = () => <span>Native details</span>;
function prepare() {
    registry.prepareExtensions([{ id: 'presentation', entry: '/client.js', components: ['server.files.details'] }]);
}
function register(replacement: ComponentReplacement<'server.files.details'>) {
    const batch = registry.createExtensionRegistryBatch();
    registry.registerComponentReplacement('presentation', 'server.files.details', replacement, batch);
    registry.commitExtensionRegistryBatch('presentation', batch);
}
async function components() {
    const { default: View } = await import('./ComponentView');
    const { ComponentReplacementSession: Session, COMPONENT_LOAD_TIMEOUT_MS } = await import('./componentSession');
    function Row({ name = model.name }: { name?: string }) {
        return (
            <View
                name='server.files.details'
                resetKey={name}
                props={{ model: { ...model, name }, Default, parts }}
                loading={<span>Loading details</span>}
            />
        );
    }
    return { Row, View, Session, timeout: COMPONENT_LOAD_TIMEOUT_MS };
}
it('renders the native view immediately when no extension declares a replacement', async () => {
    const { Row } = await components();
    render(<Row />);
    expect(screen.getByText('Native details')).toBeVisible();
});
it('shares one lazy import across rows and preserves replacement state when data changes', async () => {
    prepare();
    function Custom({ model }: ReplacementProps<'server.files.details'>) {
        const [count, setCount] = useState(0);
        return (
            <button onClick={() => setCount(count + 1)}>
                {model.name}:{count}
            </button>
        );
    }
    const load = vi.fn(async () => ({ default: Custom }));
    register({ load });
    const { Row, Session } = await components();
    const view = render(
        <Session>
            <Row />
            <Row name='other.json' />
        </Session>
    );
    fireEvent.click(await screen.findByRole('button', { name: 'config.json:0' }));
    view.rerender(
        <Session>
            <Row />
            <Row name='changed.json' />
        </Session>
    );
    expect(screen.getByRole('button', { name: 'config.json:1' })).toBeVisible();
    expect(screen.getByRole('button', { name: 'changed.json:0' })).toBeVisible();
    expect(load).toHaveBeenCalledTimes(1);
});
it('keeps all rows native after a deadline even when setup finishes or another row mounts later', async () => {
    prepare();
    const { Row, Session, timeout } = await components();
    vi.useFakeTimers();
    const view = render(
        <Session>
            <Row />
        </Session>
    );
    expect(screen.getByText('Loading details')).toBeVisible();
    await act(async () => {
        await vi.advanceTimersByTimeAsync(timeout);
    });
    expect(screen.getByText('Native details')).toBeVisible();
    act(() => register(() => <span>Late replacement</span>));
    view.rerender(
        <Session>
            <Row />
            <Row name='later.json' />
        </Session>
    );
    expect(screen.getAllByText('Native details')).toHaveLength(2);
    expect(screen.queryByText('Late replacement')).not.toBeInTheDocument();
    expect(registry.getExtensionLoadState('presentation')?.status).toBe('loaded');
    expect(registry.getExtensionStates()[0].error).toContain('Component loading exceeded');
});
it('does not swap native content when a timed-out lazy chunk resolves', async () => {
    prepare();
    let resolve!: (module: { default: ComponentType<ReplacementProps<'server.files.details'>> }) => void;
    register({
        load: () =>
            new Promise((done) => {
                resolve = done;
            }),
    });
    const { Row, timeout } = await components();
    vi.useFakeTimers();
    render(<Row />);
    await act(async () => {
        await vi.advanceTimersByTimeAsync(timeout);
    });
    await act(async () => resolve({ default: () => <span>Late chunk</span> }));
    expect(screen.getByText('Native details')).toBeVisible();
    expect(screen.queryByText('Late chunk')).not.toBeInTheDocument();
});
it('restores native presentation on import failure while core controls stay usable', async () => {
    prepare();
    register({
        load: async () => {
            throw new Error('chunk unavailable');
        },
    });
    const { Row } = await components();
    function Host() {
        const [count, setCount] = useState(0);
        return (
            <>
                <button onClick={() => setCount(count + 1)}>Core {count}</button>
                <Row />
            </>
        );
    }
    render(<Host />);
    fireEvent.click(screen.getByRole('button', { name: 'Core 0' }));
    expect(await screen.findByText('Native details')).toBeVisible();
    expect(screen.getByRole('button', { name: 'Core 1' })).toBeVisible();
    expect(registry.getExtensionStates()[0].error).toContain('chunk unavailable');
});
it('keeps core draft state during render failure and never replays its action', async () => {
    prepare();
    const Draft = createContext('');
    const Native = () => <output>{useContext(Draft)}</output>;
    const save = vi.fn();
    function Custom() {
        const draft = useContext(Draft);
        if (draft === 'saved draft') throw new Error('presentation failed');
        return <span>Custom details</span>;
    }
    register(Custom);
    const { View } = await components();
    function Host() {
        const [draft, setDraft] = useState('');
        return (
            <Draft.Provider value={draft}>
                <input aria-label='Core draft' value={draft} onChange={(event) => setDraft(event.target.value)} />
                <button onClick={save}>Save</button>
                <View
                    name='server.files.details'
                    resetKey='same-file'
                    props={{ model, Default: Native, parts }}
                    loading={null}
                />
            </Draft.Provider>
        );
    }
    render(<Host />);
    await screen.findByText('Custom details');
    fireEvent.change(screen.getByRole('textbox'), { target: { value: 'saved draft' } });
    fireEvent.click(screen.getByRole('button', { name: 'Save' }));
    expect(screen.getByRole('textbox')).toHaveValue('saved draft');
    expect(screen.getByRole('status')).toHaveTextContent('saved draft');
    expect(save).toHaveBeenCalledTimes(1);
    expect(registry.getExtensionStates()[0].error).toContain('presentation failed');
});
it('uses a stable default component without recursively resolving the replacement', async () => {
    prepare();
    const custom = vi.fn(({ Default }: ReplacementProps<'server.files.details'>) => <Default />);
    register(custom);
    const { Row } = await components();
    const view = render(<Row />);
    await screen.findByText('Native details');
    const before = custom.mock.calls[0][0];
    view.rerender(<Row name='updated.json' />);
    expect(custom.mock.calls.at(-1)![0].Default).toBe(before.Default);
    expect(custom.mock.calls.at(-1)![0].parts).toBe(before.parts);
});
it.each(['throw', 'reject'] as const)(
    'attributes a guarded callback %s to its replacement without replay',
    async (failure) => {
        prepare();
        const { useExtensionCallback } = await import('./context');
        const action = vi.fn(() => {
            if (failure === 'throw') throw new Error('callback failed');
            return Promise.reject(new Error('callback failed'));
        });
        function Custom() {
            const onClick = useExtensionCallback('inspect', action);
            return <button onClick={onClick}>Inspect details</button>;
        }
        register(Custom);
        const { Row } = await components();
        render(<Row />);
        fireEvent.click(await screen.findByRole('button', { name: 'Inspect details' }));
        await waitFor(() => {
            expect(registry.getExtensionStates()[0].error).toContain(
                'component "server.files.details" (config.json), event "inspect": callback failed'
            );
        });
        expect(action).toHaveBeenCalledTimes(1);
        expect(screen.getByRole('button', { name: 'Inspect details' })).toBeVisible();
    }
);
it('retains the native view when an uncontained suspension eventually resolves', async () => {
    prepare();
    let resolve!: () => void;
    let ready = false;
    const promise = new Promise<void>((done) => {
        resolve = () => {
            ready = true;
            done();
        };
    });
    register(() => {
        if (!ready) throw promise;
        return <span>Late suspense</span>;
    });
    const { Row } = await components();
    render(<Row />);
    await screen.findByText('Native details');
    await act(async () => resolve());
    expect(screen.getByText('Native details')).toBeVisible();
    expect(screen.queryByText('Late suspense')).not.toBeInTheDocument();
});
it('cancels waiting when the page unmounts', async () => {
    prepare();
    const { Row, timeout } = await components();
    vi.useFakeTimers();
    const view = render(<Row />);
    view.unmount();
    await act(async () => {
        await vi.advanceTimersByTimeAsync(timeout);
    });
    expect(registry.getExtensionStates()[0].status).toBe('loading');
});
