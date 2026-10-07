import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { ExtensionSetupContext } from '@/sdk';
import type { ExtensionModule } from '@/extensions/loader';
import type { SiteExtensionEntry } from '@/extensions/registry';
import type * as RegistryModule from '@/extensions/registry';
import type * as LoaderModule from '@/extensions/loader';

type Registry = typeof RegistryModule;
type Loader = typeof LoaderModule;

const Component = () => null;

const stubExtensions = (extensions: SiteExtensionEntry[]) => {
    vi.stubGlobal('window', { SiteConfiguration: { extensions } });
};

describe('extensions/loader', () => {
    let registry: Registry;
    let loader: Loader;

    beforeEach(async () => {
        vi.resetModules();
        vi.unstubAllGlobals();
        registry = await import('@/extensions/registry');
        loader = await import('@/extensions/loader');
        vi.spyOn(console, 'error').mockImplementation(() => {});
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('imports each advertised bundle and runs setup with its context', async () => {
        stubExtensions([
            {
                id: 'demo',
                version: '1.2.3',
                entry: '/assets/extensions/demo/client.js',
                config: { greeting: 'hi' },
                screens: [{ id: 'votes', area: 'server', path: 'votes', nav: { label: 'Votes' } }],
            },
        ]);

        const setup = vi.fn((ctx: ExtensionSetupContext) => {
            ctx.slots.register('dashboard.before', Component);
            ctx.screens.register('votes', async () => ({ default: Component }));
        });
        const importer = vi.fn(async () => ({ default: { setup } }));

        await loader.loadExtensions(importer);

        expect(importer).toHaveBeenCalledWith('/assets/extensions/demo/client.js');
        expect(setup).toHaveBeenCalledTimes(1);

        const context = setup.mock.calls[0]![0];
        expect(context.meta).toEqual({ id: 'demo', version: '1.2.3' });
        expect(context.config).toEqual({ greeting: 'hi' });

        expect(registry.getSlotComponents('dashboard.before')).toHaveLength(1);
        expect(registry.getExtensionScreens('server')).toHaveLength(1);
        expect(registry.getExtensionStates()).toEqual([{ id: 'demo', status: 'loaded' }]);
    });

    it('records a failing extension and keeps loading the rest', async () => {
        stubExtensions([
            { id: 'broken', entry: '/assets/extensions/broken/client.js' },
            { id: 'healthy', entry: '/assets/extensions/healthy/client.js' },
        ]);

        const importer = vi.fn(async (url: string) => {
            if (url.includes('broken')) {
                throw new Error('network exploded');
            }

            return { default: { setup: vi.fn() } };
        });

        await loader.loadExtensions(importer);

        expect(registry.getExtensionStates()).toEqual([
            { id: 'broken', status: 'failed', error: 'boot: network exploded' },
            { id: 'healthy', status: 'loaded' },
        ]);
    });

    it('loads healthy bundles independently and displays registrations in configured order', async () => {
        stubExtensions([
            { id: 'first', entry: '/assets/extensions/first/client.js' },
            { id: 'second', entry: '/assets/extensions/second/client.js' },
        ]);

        const resolvers = new Map<string, (module: ExtensionModule) => void>();
        const setupOrder: string[] = [];
        const importer = vi.fn(
            (url: string) =>
                new Promise<ExtensionModule>((resolve) => {
                    resolvers.set(url, resolve);
                })
        );
        const loading = loader.loadExtensions(importer);

        expect(importer).toHaveBeenCalledTimes(2);

        const extensionModule = {
            default: {
                setup: (context: ExtensionSetupContext) => {
                    setupOrder.push(context.meta.id);
                    context.slots.register('dashboard.before', Component);
                },
            },
        };
        resolvers.get('/assets/extensions/second/client.js')?.(extensionModule);
        await vi.waitFor(() => expect(setupOrder).toEqual(['second']));
        expect(registry.getSlotComponents('dashboard.before').map((item) => item.extensionId)).toEqual(['second']);

        resolvers.get('/assets/extensions/first/client.js')?.(extensionModule);
        await loading;

        expect(setupOrder).toEqual(['second', 'first']);
        expect(registry.getSlotComponents('dashboard.before').map((item) => item.extensionId)).toEqual([
            'first',
            'second',
        ]);
    });

    it('rejects bundles without a default setup export', async () => {
        stubExtensions([{ id: 'empty', entry: '/assets/extensions/empty/client.js' }]);

        await loader.loadExtensions(vi.fn(async () => ({})));

        expect(registry.getExtensionStates()).toEqual([
            { id: 'empty', status: 'failed', error: 'boot: bundle has no default export with a setup() function' },
        ]);
    });

    it('rejects async setup and does not commit staged registrations', async () => {
        stubExtensions([{ id: 'async', entry: '/assets/extensions/async/client.js' }]);

        await loader.loadExtensions(
            vi.fn(async () => ({
                default: {
                    setup: (ctx: ExtensionSetupContext) => {
                        ctx.slots.register('dashboard.before', Component);

                        return Promise.resolve();
                    },
                },
            }))
        );

        expect(registry.getSlotComponents('dashboard.before')).toEqual([]);
        expect(registry.getExtensionStates()).toEqual([
            {
                id: 'async',
                status: 'failed',
                error: 'boot: setup() must register synchronously and must not return a promise',
            },
        ]);
    });

    it('does not partially commit registrations when setup fails', async () => {
        stubExtensions([{ id: 'invalid', entry: '/assets/extensions/invalid/client.js' }]);

        await loader.loadExtensions(
            vi.fn(async () => ({
                default: {
                    setup: (ctx: ExtensionSetupContext) => {
                        ctx.slots.register('dashboard.before', Component);
                        ctx.slots.register('dashboard.typo' as never, Component);
                    },
                },
            }))
        );

        expect(registry.getSlotComponents('dashboard.before')).toEqual([]);
        expect(registry.getExtensionStates()).toEqual([
            {
                id: 'invalid',
                status: 'failed',
                error: expect.stringContaining('Unknown slot'),
            },
        ]);
    });

    it('times out a hung bundle import', async () => {
        vi.useFakeTimers();
        stubExtensions([{ id: 'hung', entry: '/assets/extensions/hung/client.js' }]);

        const loading = loader.loadExtensions(vi.fn(() => new Promise<ExtensionModule>(() => {})));
        await vi.advanceTimersByTimeAsync(loader.EXTENSION_IMPORT_TIMEOUT_MS);
        await loading;

        expect(registry.getExtensionStates()).toEqual([
            {
                id: 'hung',
                status: 'failed',
                error: `boot: bundle import exceeded ${loader.EXTENSION_IMPORT_TIMEOUT_MS}ms`,
            },
        ]);
        expect(loader.canRetryExtension('hung')).toBe(true);
    });

    it('commits a timed-out bundle on retry once its import settles', async () => {
        vi.useFakeTimers();
        stubExtensions([{ id: 'slow', entry: '/assets/extensions/slow/client.js' }]);
        let resolve!: (module: ExtensionModule) => void;
        const evaluation = new Promise<ExtensionModule>((done) => {
            resolve = done;
        });
        const importer = vi.fn(() => evaluation);
        const setup = vi.fn((context: ExtensionSetupContext) => context.slots.register('dashboard.before', Component));

        const loading = loader.loadExtensions(importer);
        await vi.advanceTimersByTimeAsync(loader.EXTENSION_IMPORT_TIMEOUT_MS);
        await loading;
        expect(registry.getExtensionLoadState('slow')?.status).toBe('failed');

        const retry = loader.retryExtension('slow');
        expect(registry.getExtensionStates()).toEqual([{ id: 'slow', status: 'loading' }]);
        expect(loader.canRetryExtension('slow')).toBe(false);
        resolve({ default: { setup } });
        await retry;

        expect(importer).toHaveBeenCalledTimes(2);
        expect(setup).toHaveBeenCalledTimes(1);
        expect(registry.getSlotComponents('dashboard.before')).toHaveLength(1);
        expect(registry.getExtensionStates()).toEqual([{ id: 'slow', status: 'loaded' }]);
    });

    it('offers no retry once setup has run', async () => {
        stubExtensions([{ id: 'broken-setup', entry: '/assets/extensions/broken-setup/client.js' }]);
        const setup = vi.fn(() => {
            throw new Error('setup exploded');
        });
        const importer = vi.fn(async () => ({ default: { setup } }));

        await loader.loadExtensions(importer);
        await loader.retryExtension('broken-setup');

        expect(loader.canRetryExtension('broken-setup')).toBe(false);
        expect(importer).toHaveBeenCalledTimes(1);
        expect(setup).toHaveBeenCalledTimes(1);
        expect(registry.getExtensionStates()).toEqual([
            { id: 'broken-setup', status: 'failed', error: 'boot: setup exploded' },
        ]);
    });

    it('does nothing when the backend advertises no extensions', async () => {
        vi.stubGlobal('window', { SiteConfiguration: {} });

        const importer = vi.fn();
        await loader.loadExtensions(importer);

        expect(importer).not.toHaveBeenCalled();
        expect(registry.getExtensionStates()).toEqual([]);
    });
});

it('never runs setup after a timed-out import eventually resolves', async () => {
    vi.resetModules();
    vi.useFakeTimers();
    vi.spyOn(console, 'error').mockImplementation(() => {});
    const registry = await import('@/extensions/registry');
    const loader = await import('@/extensions/loader');
    stubExtensions([{ id: 'late', entry: '/late.js' }]);
    let resolve!: (module: ExtensionModule) => void;
    const setup = vi.fn((context: ExtensionSetupContext) => context.slots.register('dashboard.before', Component));
    const pending = loader.loadExtensions(
        () =>
            new Promise<ExtensionModule>((done) => {
                resolve = done;
            })
    );
    await vi.advanceTimersByTimeAsync(loader.EXTENSION_IMPORT_TIMEOUT_MS);
    await pending;
    resolve({ default: { setup } });
    await Promise.resolve();
    expect(setup).not.toHaveBeenCalled();
    expect(registry.getSlotComponents('dashboard.before')).toEqual([]);
    vi.useRealTimers();
});

describe('declared component replacements', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.spyOn(console, 'error').mockImplementation(() => {});
    });
    it('registers replacements atomically and exposes their declared owner', async () => {
        stubExtensions([{ id: 'views', entry: '/views.js', components: ['dashboard.serverCard'] }]);
        const registry = await import('./registry');
        const loader = await import('./loader');
        await loader.loadExtensions(async () => ({
            default: {
                setup(ctx: ExtensionSetupContext) {
                    ctx.components.replace('dashboard.serverCard', Component);
                },
            },
        }));
        expect(registry.getComponentOwner('dashboard.serverCard')).toBe('views');
        expect(registry.getComponentReplacement('dashboard.serverCard')).toBe(Component);
    });
    it('aborts slots as well when a declared replacement implementation is missing', async () => {
        stubExtensions([{ id: 'views', entry: '/views.js', components: ['dashboard.serverCard'] }]);
        const registry = await import('./registry');
        const loader = await import('./loader');
        await loader.loadExtensions(async () => ({
            default: {
                setup(ctx: ExtensionSetupContext) {
                    ctx.slots.register('dashboard.before', Component);
                },
            },
        }));
        expect(registry.getSlotComponents('dashboard.before')).toHaveLength(0);
        expect(registry.getComponentReplacement('dashboard.serverCard')).toBeUndefined();
        expect(registry.getExtensionStates()[0].error).toContain('Missing implementation');
    });
    it('rejects undeclared duplicate and invalid component registrations', async () => {
        const registry = await import('./registry');
        registry.prepareExtensions([{ id: 'views', entry: '/views.js', components: ['dashboard.serverCard'] }]);
        const batch = registry.createExtensionRegistryBatch();
        expect(() => registry.registerComponentReplacement('views', 'server.files.details', Component, batch)).toThrow(
            'ui.components'
        );
        expect(() =>
            registry.registerComponentReplacement('views', 'dashboard.serverCard', {} as never, batch)
        ).toThrow('Invalid implementation');
        registry.registerComponentReplacement('views', 'dashboard.serverCard', Component, batch);
        expect(() => registry.registerComponentReplacement('views', 'dashboard.serverCard', Component, batch)).toThrow(
            'Duplicate'
        );
        registry.abortExtensionRegistryBatch(batch);
        expect(() => registry.registerComponentReplacement('views', 'dashboard.serverCard', Component, batch)).toThrow(
            'closed'
        );
    });
    it('rejects both conflicting owners independently of order and loads unrelated extensions', async () => {
        stubExtensions([
            { id: 'first', entry: '/first.js', components: ['dashboard.serverCard'] },
            { id: 'second', entry: '/second.js', components: ['dashboard.serverCard'] },
            { id: 'healthy', entry: '/healthy.js' },
        ]);
        const registry = await import('./registry');
        const loader = await import('./loader');
        const importer = vi.fn(async () => ({ default: { setup() {} } }));
        await loader.loadExtensions(importer);
        expect(importer).toHaveBeenCalledExactlyOnceWith('/healthy.js');
        expect(registry.getComponentOwner('dashboard.serverCard')).toBeUndefined();
        expect(
            registry
                .getExtensionStates()
                .filter((state) => state.status === 'failed')
                .map((state) => state.id)
        ).toEqual(['first', 'second']);
    });
});
