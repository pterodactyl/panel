/** @vitest-environment jsdom */
import { act, cleanup, render, screen } from '@testing-library/react';
import { Suspense, type ComponentType, type ReactNode } from 'react';
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
    Default: ComponentType<{ model?: ProbeModel; className?: string; parts?: Record<string, ComponentType<never>> }>;
    className?: string;
    children?: ReactNode;
    parts?: Record<string, ComponentType<{ model: ProbeModel }>>;
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
    views.registerViewDefinition({
        name,
        kind: 'block',
        context: 'app',
        use,
        placeholder: <span>placeholder</span>,
        fixtures: { first: () => ({ name: 'first fixture' }), second: () => ({ name: 'second fixture' }) },
        ...options,
    });
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

    it('swaps parts through Default and gives the layer above the parts of the layer below', () => {
        define('test.parts');
        function Name({ model }: { model: ProbeModel }) {
            return <b>{model.name}</b>;
        }

        function Card({ model, parts }: ProbeProps) {
            const Part = parts!.name;

            return <Part model={model} />;
        }

        views.registerViewLayer('classic', 'classic', { 'test.parts': Object.assign(Card, { parts: { name: Name } }) });
        views.registerViewLayer('theme', 'theme', {
            'test.parts': ({ Default, parts, model }: ProbeProps) => (
                <>
                    <i>{parts && 'name' in parts ? 'has parts' : 'no parts'}</i>
                    <Default parts={{ name: () => <u>{`custom ${model.name}`}</u> }} />
                </>
            ),
        });

        render(<engine.View name='test.parts' />);

        expect(screen.getByText('has parts')).toBeVisible();
        expect(screen.getByText('custom live')).toBeVisible();
    });

    it('loads a lazy classic layer before rendering, so its parts reach the layers above', async () => {
        define('test.lazy');
        function Card({ model }: ProbeProps) {
            return <p>{`lazy:${model.name}`}</p>;
        }

        const module = { default: Object.assign(Card, { parts: { name: Card } }) };

        views.registerViewLayer('classic', 'classic', { 'test.lazy': lazy.lazyView(async () => module) });
        views.registerViewLayer('theme', 'theme', {
            'test.lazy': ({ Default, parts }: ProbeProps) => (
                <>
                    <i>{parts ? 'parts known' : 'parts missing'}</i>
                    <Default />
                </>
            ),
        });

        await act(async () => {
            render(
                <Suspense fallback={<span>suspended</span>}>
                    <engine.View name='test.lazy' />
                </Suspense>
            );
        });

        expect(screen.getByText('lazy:live')).toBeVisible();
        expect(screen.getByText('parts known')).toBeVisible();
    });

    it('renders nothing for an optional unknown view and throws for a required one', () => {
        const { container } = render(<engine.View name='billing.balance' optional />);

        expect(container).toBeEmptyDOMElement();
        expect(() => render(<engine.View name='billing.balance' />)).toThrow('View "billing.balance" is not defined.');
    });

    it('refuses to render a view outside its context', () => {
        define('test.server', { context: 'server' });
        views.registerViewLayer('classic', 'classic', { 'test.server': Classic });

        expect(() =>
            render(
                <engine.ViewArea area='auth'>
                    <engine.View name='test.server' />
                </engine.ViewArea>
            )
        ).toThrow('renders in the "server" context');

        render(
            <engine.ViewArea area='app'>
                <engine.ViewArea area='server'>
                    <engine.View name='test.server' />
                </engine.ViewArea>
            </engine.ViewArea>
        );
        expect(screen.getByText('classic:live')).toBeVisible();
    });

    it('reads fixtures instead of running the hook below a fixture provider', () => {
        define('test.fixture');
        views.registerViewLayer('classic', 'classic', { 'test.fixture': Classic });
        use.mockClear();

        const { rerender } = render(
            <engine.ViewFixtureProvider>
                <engine.ViewArea area='auth'>
                    <engine.View name='test.fixture' />
                </engine.ViewArea>
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

    it('wraps each view for the inspector when inspection is on', async () => {
        vi.stubGlobal('SiteConfiguration', undefined);
        Object.assign(window, { SiteConfiguration: { theme: { inspect: true } } });
        vi.resetModules();
        const [registry, view] = await Promise.all([import('./registry'), import('./View')]);

        registry.registerViewDefinition({ name: 'test.inspect', kind: 'block', context: 'app', use });
        registry.registerViewLayer('classic', 'classic', { 'test.inspect': Classic });

        const { container } = render(<view.View name='test.inspect' />);

        expect(container.querySelector('[data-view="test.inspect"]')).toHaveStyle({ display: 'contents' });
        Object.assign(window, { SiteConfiguration: undefined });
    });
});
