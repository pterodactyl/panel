/** @vitest-environment jsdom */
import { act, cleanup, fireEvent, render, screen } from '@testing-library/react';
import { Suspense, use as usePromise, useEffect, useState, type ComponentType, type ReactNode } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type * as RegistryModule from './registry';
import type * as ViewModule from './View';
import type * as LazyModule from './lazy';
import type * as SessionModule from './session';
import type * as ExtensionRegistry from '@/extensions/registry';

interface ProbeModel {
    name: string;
}
interface ProbeProps {
    model: ProbeModel;
    Default: ComponentType<{ model?: ProbeModel; className?: string }>;
    className?: string;
    children?: ReactNode;
}

let views: typeof RegistryModule;
let engine: typeof ViewModule;
let lazy: typeof LazyModule;
let session: typeof SessionModule;
let extensions: typeof ExtensionRegistry;

beforeEach(async () => {
    vi.resetModules();
    [views, engine, lazy, session, extensions] = await Promise.all([
        import('./registry'),
        import('./View'),
        import('./lazy'),
        import('./session'),
        import('@/extensions/registry'),
    ]);
    vi.spyOn(console, 'error').mockImplementation(() => {});
});

afterEach(() => {
    cleanup();
    vi.useRealTimers();
    vi.restoreAllMocks();
});

const use = vi.fn((): ProbeModel => ({ name: 'live' }));

function define(name: string, options: Partial<RegistryModule.ViewDefinition> = {}) {
    // Test views are typed loosely; the registry checks core view names where they are defined.
    views.defineView(
        name as RegistryModule.ViewName,
        {
            context: 'app',
            use,
            placeholder: <span>placeholder</span>,
            fixtures: { first: () => ({ name: 'first fixture' }), second: () => ({ name: 'second fixture' }) },
            ...options,
        } as never
    );
}

/** A layer that names itself, then renders the layer below. */
const layer = (id: string) =>
    function Layer({ model, Default }: ProbeProps) {
        return (
            <>
                <span>{`${id}:${model.name}`}</span>
                <Default />
            </>
        );
    };

function Classic({ model, className }: ProbeProps) {
    return <p className={className}>{`classic:${model.name}`}</p>;
}

function commitOverrides(entries: { id: string; component: ComponentType<never> }[], name: string) {
    extensions.prepareExtensions(entries.map(({ id }) => ({ id, entry: `/${id}.js`, views: [name] })));
    for (const { id, component } of entries) {
        const batch = extensions.createExtensionRegistryBatch();

        extensions.registerViewOverride(id, name, component, batch);
        extensions.commitExtensionRegistryBatch(id, batch);
    }
}

describe('<View>', () => {
    it('stacks classic, the theme chain parent first, then extensions in configured order', async () => {
        define('test.order');
        views.registerViewLayer('classic', 'classic', { 'test.order': Classic });
        views.registerViewLayer('parent', 'theme', { 'test.order': layer('parent') });
        views.registerViewLayer('child', 'theme', { 'test.order': layer('child') });
        commitOverrides(
            [
                { id: 'first', component: layer('first') },
                { id: 'second', component: layer('second') },
            ],
            'test.order'
        );

        const { container } = render(<engine.View name='test.order' />);

        await screen.findByText('classic:live');
        expect([...container.querySelectorAll('span, p')].map((node) => node.textContent)).toEqual([
            'second:live',
            'first:live',
            'child:live',
            'parent:live',
            'classic:live',
        ]);
        expect(use).toHaveBeenCalled();
    });

    it('lets Default transform the model and merge classes for the layer below', () => {
        define('test.default');
        views.registerViewLayer('classic', 'classic', { 'test.default': Classic });
        views.registerViewLayer('theme', 'theme', {
            'test.default': ({ model, Default }: ProbeProps) => (
                <Default model={{ ...model, name: model.name.toUpperCase() }} className='mt-2' />
            ),
        });

        render(<engine.View name='test.default' className='mt-4 text-sm' />);

        expect(screen.getByText('classic:LIVE')).toHaveClass('mt-2', 'text-sm');
        expect(screen.getByText('classic:LIVE')).not.toHaveClass('mt-4');
    });

    it('falls back to the layer below a theme layer that throws, and reports it', () => {
        define('test.fallback');
        views.registerViewLayer('classic', 'classic', { 'test.fallback': Classic });
        views.registerViewLayer('aurora', 'theme', {
            'test.fallback': () => {
                throw new Error('theme exploded');
            },
        });

        render(<engine.View name='test.fallback' />);

        expect(screen.getByText('classic:live')).toBeVisible();
        expect(extensions.getExtensionStates()).toEqual([
            expect.objectContaining({
                id: 'aurora',
                status: 'failed',
                error: expect.stringContaining('theme exploded'),
            }),
        ]);
    });

    it('falls back once when the layer below fails too, and shows the error notice instead of looping', () => {
        define('test.chain');
        let theme = 0;
        let classic = 0;

        function FailingClassic() {
            // A chart measuring itself in an effect, as server.graphs does; capped so a regression fails, not hangs.
            useEffect(() => {
                classic += 1;
                if (classic < 20) {
                    throw new Error('ResizeObserver is not defined');
                }
            });

            return <p>classic</p>;
        }

        views.registerViewLayer('classic', 'classic', { 'test.chain': FailingClassic });
        views.registerViewLayer('aurora', 'theme', {
            'test.chain': () => {
                theme += 1;
                throw new Error('theme exploded');
            },
        });

        render(<engine.View name='test.chain' />);

        expect(screen.getByRole('alert')).toHaveTextContent('An error was encountered');
        expect(classic).toBe(1);

        const before = theme;

        fireEvent.click(screen.getByRole('button', { name: 'Retry' }));
        expect(theme).toBeGreaterThan(before);
        expect(classic).toBe(2);
        expect(screen.getByRole('alert')).toBeVisible();
    });

    it('falls back once when a layer renders a Default that fails in an effect', () => {
        define('test.default-chain');
        let classic = 0;

        function FailingClassic() {
            useEffect(() => {
                classic += 1;
                if (classic < 20) {
                    throw new Error('classic exploded');
                }
            });

            return <p>classic</p>;
        }

        views.registerViewLayer('classic', 'classic', { 'test.default-chain': FailingClassic });
        views.registerViewLayer('aurora', 'theme', { 'test.default-chain': layer('aurora') });

        render(<engine.View name='test.default-chain' />);

        expect(screen.getByRole('alert')).toHaveTextContent('An error was encountered');
        expect(classic).toBe(2);
    });

    it('reports a failed layer once, naming the view and the layer', () => {
        define('test.report');
        views.registerViewLayer('classic', 'classic', { 'test.report': Classic });
        views.registerViewLayer('aurora', 'theme', {
            'test.report': () => {
                throw new Error('theme exploded');
            },
        });

        render(<engine.View name='test.report' />);

        expect(extensions.getExtensionErrors()).toEqual([
            { extensionId: 'aurora', context: 'view "test.report"', message: 'theme exploded' },
        ]);
        expect(console.error).toHaveBeenCalledWith(
            '[extensions] "aurora" failed in view "test.report":',
            expect.objectContaining({ message: 'theme exploded' })
        );
    });

    it('renders a lazy classic layer below a theme layer once its chunk loads', async () => {
        define('test.lazy');
        function Card({ model }: ProbeProps) {
            return <p>{`lazy:${model.name}`}</p>;
        }

        views.registerViewLayer('classic', 'classic', {
            'test.lazy': lazy.lazyView(async () => ({ default: Card })),
        });
        views.registerViewLayer('theme', 'theme', { 'test.lazy': layer('theme') });

        await act(async () => {
            render(
                <Suspense fallback={<span>suspended</span>}>
                    <engine.View name='test.lazy' />
                </Suspense>
            );
        });

        expect(screen.getByText('theme:live')).toBeVisible();
        expect(screen.getByText('lazy:live')).toBeVisible();
    });

    it('throws for a view no package defines', () => {
        expect(() => render(<engine.View name='billing.balance' />)).toThrow('View "billing.balance" is not defined.');
    });

    it('reads fixtures instead of running the hook below a fixture provider', () => {
        define('test.fixture');
        views.registerViewLayer('classic', 'classic', { 'test.fixture': Classic });
        use.mockClear();

        const { rerender } = render(
            <engine.ViewFixtureProvider>
                <engine.View name='test.fixture' />
            </engine.ViewFixtureProvider>
        );

        expect(screen.getByText('classic:first fixture')).toBeVisible();
        rerender(
            <engine.ViewFixtureProvider fixtures={{ 'test.fixture': 'second' } as never}>
                <engine.View name='test.fixture' />
            </engine.ViewFixtureProvider>
        );
        expect(screen.getByText('classic:second fixture')).toBeVisible();
        expect(use).not.toHaveBeenCalled();
        expect(() =>
            render(
                <engine.ViewFixtureProvider fixtures={{ 'test.fixture': 'missing' } as never}>
                    <engine.View name='test.fixture' />
                </engine.ViewFixtureProvider>
            )
        ).toThrow('has no fixture "missing"');
    });

    it('shows the placeholder while a declared extension loads, then keeps classic after the timeout', async () => {
        vi.useFakeTimers();
        define('test.slow');
        views.registerViewLayer('classic', 'classic', { 'test.slow': Classic });
        extensions.prepareExtensions([{ id: 'slow', entry: '/slow.js', views: ['test.slow'] }]);

        render(<engine.View name='test.slow' />);

        expect(screen.getByText('placeholder')).toBeVisible();
        await act(async () => {
            await vi.advanceTimersByTimeAsync(5000);
        });
        expect(screen.getByText('classic:live')).toBeVisible();
        expect(extensions.getExtensionStates()[0]).toMatchObject({ id: 'slow', status: 'failed' });

        // A bundle that arrives late cannot replace the committed view.
        const batch = extensions.createExtensionRegistryBatch();

        extensions.registerViewOverride('slow', 'test.slow', layer('slow'), batch);
        act(() => extensions.commitExtensionRegistryBatch('slow', batch));
        expect(screen.queryByText('slow:live')).not.toBeInTheDocument();
    });

    it('renders an extension layer that loads in time, importing a lazy override once', async () => {
        define('test.ext');
        views.registerViewLayer('classic', 'classic', { 'test.ext': Classic });
        extensions.prepareExtensions([{ id: 'ext', entry: '/ext.js', views: ['test.ext'] }]);
        const load = vi.fn(async () => ({ default: layer('ext') }));

        render(
            <session.ViewSessionProvider>
                <engine.View name='test.ext' />
                <engine.View name='test.ext' />
            </session.ViewSessionProvider>
        );
        expect(screen.getAllByText('placeholder')).toHaveLength(2);

        const batch = extensions.createExtensionRegistryBatch();

        extensions.registerViewOverride('ext', 'test.ext', { load }, batch);
        act(() => extensions.commitExtensionRegistryBatch('ext', batch));

        expect(await screen.findAllByText('ext:live')).toHaveLength(2);
        expect(screen.getAllByText('classic:live')).toHaveLength(2);
        expect(load).toHaveBeenCalledTimes(1);
    });

    it('shows the placeholder while an extension layer suspends, then renders it', async () => {
        define('test.suspending');
        views.registerViewLayer('classic', 'classic', { 'test.suspending': Classic });

        let arrive = () => {};

        const arrived = new Promise<void>((resolve) => {
            arrive = resolve;
        });

        function Suspending({ model }: ProbeProps) {
            usePromise(arrived);

            return <p>{`suspending:${model.name}`}</p>;
        }

        commitOverrides([{ id: 'ext', component: Suspending }], 'test.suspending');
        await act(async () => {
            render(<engine.View name='test.suspending' />);
        });

        expect(screen.getByText('placeholder')).toBeVisible();
        expect(extensions.getExtensionErrors()).toEqual([]);

        await act(async () => arrive());

        expect(screen.getByText('suspending:live')).toBeVisible();
    });

    it('renders a fixture with the inputs its view declares', () => {
        function Card({ model, server }: ProbeProps & { server?: string }) {
            return <p>{`${model.name} on ${server}`}</p>;
        }

        define('test.inputs', {
            fixtureInput: (model) => ({ server: `${Reflect.get(Object(model), 'name')} host` }),
        });
        views.registerViewLayer('classic', 'classic', { 'test.inputs': Card });

        render(
            <engine.ViewFixtureProvider>
                <engine.View name='test.inputs' />
            </engine.ViewFixtureProvider>
        );

        expect(screen.getByText('first fixture on first fixture host')).toBeVisible();
    });
});

describe('views implemented by their area module', () => {
    const renders = vi.fn();

    function useConflict() {
        const [kind] = useState<'installing' | null>('installing');

        renders();

        return { kind };
    }

    /** An area module, implementing two views, that loads when `arrive` is called. */
    function area() {
        let arrive = () => {};

        const arrived = new Promise<void>((resolve) => {
            arrive = resolve;
        });
        const load = vi.fn(async () => {
            await arrived;
            views.implementViews({
                'server.conflict': { use: useConflict, fixtures: { installing: () => ({ kind: 'installing' }) } },
                'page.server.settings': { use: () => ({}) },
            });
        });

        views.defineView('server.conflict', { context: 'server', load });
        views.defineView('page.server.settings', { context: 'server', load });
        views.registerViewLayer('classic', 'classic', {
            'server.conflict': ({ model }) => <p>{`conflict:${model.kind}`}</p>,
            'page.server.settings': () => <p>settings</p>,
        });

        return { load, arrive };
    }

    const suspended = (name: 'server.conflict' | 'page.server.settings') => (
        <Suspense fallback={<span>loading area</span>}>
            <engine.View name={name} />
        </Suspense>
    );

    it('suspends once while the module loads, then renders every view of the area without loading it again', async () => {
        const { load, arrive } = area();
        const { rerender } = await act(async () => render(suspended('server.conflict')));

        expect(screen.getByText('loading area')).toBeVisible();
        expect(views.getViewDefinition('server.conflict')?.use).toBeUndefined();

        await act(async () => arrive());

        expect(screen.getByText('conflict:installing')).toBeVisible();

        const before = renders.mock.calls.length;

        rerender(suspended('server.conflict'));
        render(<engine.View name='page.server.settings' />);

        expect(screen.getByText('conflict:installing')).toBeVisible();
        expect(screen.getByText('settings')).toBeVisible();
        expect(renders.mock.calls.length).toBeGreaterThan(before);
        expect(load).toHaveBeenCalledOnce();
        expect(console.error).not.toHaveBeenCalled();
    });

    it('loads the module and the classic chunks together when the router preloads the view', async () => {
        const { load, arrive } = area();
        const importClassic = vi.fn(async () => ({
            default: ({ model }: { model: { kind: string | null } }) => <p>{model.kind}</p>,
        }));

        views.registerViewLayer('classic', 'classic', { 'server.conflict': lazy.lazyView(importClassic) });

        const preloading = engine.preloadView('server.conflict');

        expect(load).toHaveBeenCalledOnce();
        expect(importClassic).toHaveBeenCalledOnce();

        arrive();
        await preloading;
        render(<engine.View name='server.conflict' />);

        expect(screen.getByText('installing')).toBeVisible();
    });

    it('reads fixtures from the module, so tests render the view without a backend', async () => {
        const { arrive } = area();

        arrive();
        await act(async () => {
            render(
                <engine.ViewFixtureProvider>
                    <Suspense fallback={<span>loading area</span>}>
                        <engine.View name='server.conflict' />
                    </Suspense>
                </engine.ViewFixtureProvider>
            );
        });

        expect(screen.getByText('conflict:installing')).toBeVisible();
        expect(Object.keys(views.getViewDefinition('server.conflict')?.fixtures ?? {})).toEqual(['installing']);
    });

    it('imports a module that failed to load again the next time a view needs it', async () => {
        let attempts = 0;
        const load = async () => {
            attempts += 1;

            if (attempts === 1) {
                throw new Error('chunk failed');
            }

            views.implementViews({ 'page.server.settings': { use: () => ({}) } });
        };

        views.defineView('page.server.settings', { context: 'server', load });

        await expect(engine.preloadView('page.server.settings')).rejects.toThrow('chunk failed');
        await engine.preloadView('page.server.settings');

        expect(attempts).toBe(2);
        expect(views.getViewDefinition('page.server.settings')?.use).toBeDefined();
    });
});
