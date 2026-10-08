import { beforeEach, describe, expect, it, vi } from 'vitest';
import type * as RegistryModule from '@/extensions/registry';

type Registry = typeof RegistryModule;

const Component = () => null;

describe('extensions/registry', () => {
    let registry: Registry;

    beforeEach(async () => {
        vi.resetModules();
        registry = await import('@/extensions/registry');
        vi.spyOn(console, 'error').mockImplementation(() => {});
    });

    const registerSlot = (extensionId: string, name: RegistryModule.SlotName) => {
        const batch = registry.createExtensionRegistryBatch();

        registry.registerSlotComponent(extensionId, name, Component, batch);
        registry.commitExtensionRegistryBatch(extensionId, batch);
    };

    it('returns slot components in registration order', () => {
        registerSlot('one', 'dashboard.before');
        registerSlot('two', 'dashboard.before');

        expect(registry.getSlotComponents('dashboard.before').map((r) => r.extensionId)).toEqual(['one', 'two']);
        expect(new Set(registry.getSlotComponents('dashboard.before').map((r) => r.id)).size).toBe(2);
        expect(registry.getSlotComponents('dashboard.after')).toEqual([]);
    });

    it('rejects unknown slot names loudly', () => {
        const batch = registry.createExtensionRegistryBatch();

        expect(() => registry.registerSlotComponent('ext', 'dashboard.typo' as never, Component, batch)).toThrow(
            /Unknown slot/
        );
    });

    it('validates screen paths before accepting any registrations', () => {
        registry.prepareExtensions(
            ['/absolute', 'a/../b', '', 'votes', 'votes/$id', 'votes/$tab/$tab'].map((path, index) => ({
                id: `ext-${index}`,
                entry: '/client.js',
                screens: [{ id: 'main', area: 'server', path }],
            }))
        );
        expect(registry.getExtensionScreens('server').map((screen) => screen.path)).toEqual(['votes']);
        expect(registry.getLoadableExtensions()?.map((entry) => entry.id)).toEqual(['ext-3']);
        expect(registry.getExtensionScreens('account')).toHaveLength(0);
    });

    it('rejects collisions across extensions, within a batch, and against core routes', () => {
        registry.prepareExtensions(
            [
                { id: 'first', entry: '/a.js', screens: [{ id: 'main', area: 'server', path: 'votes/$tab' }] },
                {
                    id: 'second',
                    entry: '/b.js',
                    screens: [
                        { id: 'unique', area: 'account', path: 'unique' },
                        { id: 'main', area: 'server', path: 'votes/$other/' },
                    ],
                },
                {
                    id: 'duplicate',
                    entry: '/c.js',
                    screens: [
                        { id: 'one', area: 'server', path: 'same' },
                        { id: 'two', area: 'server', path: 'same/' },
                    ],
                },
                {
                    id: 'core-collision',
                    entry: '/d.js',
                    screens: [{ id: 'main', area: 'server', path: 'files/extra' }],
                },
                { id: 'healthy', entry: '/e.js', screens: [{ id: 'main', area: 'account', path: 'votes/$tab' }] },
            ],
            { server: ['files'], account: [], admin: [] }
        );
        expect(registry.getLoadableExtensions()?.map((entry) => entry.id)).toEqual(['first', 'healthy']);
        expect(registry.getExtensionScreens('account').map((screen) => screen.path)).toEqual(['votes/$tab']);
        expect(registry.getExtensionStates().find((state) => state.id === 'second')?.error).toContain('first');
    });

    it('rejects missing and undeclared implementations without publishing partial slots', () => {
        registry.prepareExtensions([
            { id: 'demo', entry: '/demo.js', screens: [{ id: 'main', area: 'server', path: 'demo' }] },
        ]);
        const batch = registry.createExtensionRegistryBatch();

        registry.registerSlotComponent('demo', 'dashboard.before', Component, batch);
        expect(() => registry.registerScreen('demo', 'other', async () => ({ default: Component }), batch)).toThrow(
            'Undeclared'
        );
        expect(() => registry.commitExtensionRegistryBatch('demo', batch)).toThrow('Missing implementation');
        expect(registry.getSlotComponents('dashboard.before')).toEqual([]);
        registry.abortExtensionRegistryBatch(batch);
        expect(() => registry.registerScreen('demo', 'main', async () => ({ default: Component }), batch)).toThrow(
            'closed'
        );
    });

    it('publishes stable immutable slot snapshots once per committed batch', () => {
        const changed = vi.fn();
        const unsubscribe = registry.subscribeExtensionRegistry(changed);
        const before = registry.getSlotComponents('dashboard.before');
        const after = registry.getSlotComponents('dashboard.after');

        expect(registry.getSlotComponents('dashboard.before')).toBe(before);
        const batch = registry.createExtensionRegistryBatch();

        registry.registerSlotComponent('demo', 'dashboard.before', Component, batch);
        registry.registerSlotComponent('demo', 'dashboard.before', Component, batch);
        expect(changed).not.toHaveBeenCalled();
        registry.commitExtensionRegistryBatch('demo', batch);
        expect(before).toHaveLength(0);
        expect(registry.getSlotComponents('dashboard.before')).toHaveLength(2);
        expect(Object.isFrozen(registry.getSlotComponents('dashboard.before'))).toBe(true);
        expect(registry.getSlotComponents('dashboard.after')).toBe(after);
        expect(changed).toHaveBeenCalledTimes(1);
        unsubscribe();
        registerSlot('late', 'dashboard.before');
        expect(changed).toHaveBeenCalledTimes(1);
    });

    it('publishes table columns atomically and rejects duplicate contributions', () => {
        registry.prepareExtensions([
            { id: 'demo', entry: '/demo.js', screens: [{ id: 'main', area: 'account', path: 'probe' }] },
        ]);
        const changed = vi.fn();

        registry.subscribeExtensionRegistry(changed);
        const batch = registry.createExtensionRegistryBatch();
        const column = {
            extensionId: 'demo',
            name: 'admin.nodes' as const,
            id: 'health',
            label: 'Health',
            component: Component,
        };
        const before = registry.getExtensionTableColumns('admin.nodes');

        registry.registerExtensionTableColumn(column, batch);
        expect(() => registry.registerExtensionTableColumn(column, batch)).toThrow('Duplicate');
        expect(() => registry.commitExtensionRegistryBatch('demo', batch)).toThrow('Missing implementation');
        expect(registry.getExtensionTableColumns('admin.nodes')).toBe(before);
        expect(changed).not.toHaveBeenCalled();
        registry.registerScreen('demo', 'main', async () => ({ default: Component }), batch);
        registry.commitExtensionRegistryBatch('demo', batch);
        expect(registry.getExtensionTableColumns('admin.nodes').map((column) => column.id)).toEqual(['health']);
        expect(Object.isFrozen(registry.getExtensionTableColumns('admin.nodes'))).toBe(true);
        expect(changed).toHaveBeenCalledTimes(1);
    });

    it('tracks runtime state, with failures overriding loaded', () => {
        registry.setExtensionState({ id: 'demo', status: 'loaded' });
        registry.reportExtensionError('demo', 'slot "dashboard.before"', new Error('boom'));

        expect(registry.getExtensionStates()).toEqual([
            { id: 'demo', status: 'failed', error: 'slot "dashboard.before": boom' },
        ]);
    });

    it('shows the latest remaining failure once another context recovers', () => {
        registry.setExtensionState({ id: 'demo', status: 'loaded' });
        registry.reportExtensionError('demo', 'first', new Error('one'));
        registry.reportExtensionError('demo', 'second', new Error('two'));
        registry.reportExtensionError('demo', 'third', new Error('three'));
        registry.clearExtensionError('demo', 'third');

        expect(registry.getExtensionStates()).toEqual([{ id: 'demo', status: 'failed', error: 'second: two' }]);
        expect(registry.getExtensionLoadState('demo')?.status).toBe('loaded');
    });

    it('publishes stable diagnostics snapshots and restores state after a mount recovers', () => {
        const changed = vi.fn();

        registry.subscribeExtensionRegistry(changed);
        registry.setExtensionState({ id: 'demo', status: 'loaded' });
        const before = registry.getExtensionStates();

        expect(registry.getExtensionStates()).toBe(before);
        registry.reportExtensionError('demo', 'action', new Error('failed'));
        expect(before[0].status).toBe('loaded');
        expect(Object.isFrozen(registry.getExtensionStates()[0])).toBe(true);
        registry.clearExtensionError('demo', 'action');
        expect(registry.getExtensionStates()[0].status).toBe('loaded');
        expect(changed).toHaveBeenCalledTimes(3);
    });

    it('requires navigable parameter defaults and orders extension entries deterministically', () => {
        registry.prepareExtensions([
            {
                id: 'bad',
                entry: '/bad.js',
                screens: [{ id: 'main', area: 'account', path: 'probe/$tab', nav: { label: 'Bad' } }],
            },
            {
                id: 'good',
                entry: '/good.js',
                screens: [
                    { id: 'late', area: 'account', path: 'later', nav: { label: 'Later', order: 20 } },
                    {
                        id: 'main',
                        area: 'account',
                        path: 'probe/$tab',
                        nav: { label: 'Probe', params: { tab: 'two words' }, order: 10 },
                    },
                ],
            },
        ]);
        const accepted = registry.getExtensionScreens('account');

        expect(accepted.map((screen) => screen.id)).toEqual(['main', 'late']);
        expect(registry.resolveScreenPath(accepted[0].path, accepted[0].nav?.params)).toBe('probe/two%20words');
        expect(registry.getExtensionStates().find((state) => state.id === 'bad')?.status).toBe('failed');
    });

    it('evaluates egg rules against the server payload, ignoring case', () => {
        const server = (features: string[] | null, tags: string[]) =>
            ({ attributes: { egg_features: features, egg_tags: tags } }) as Parameters<
                Registry['matchesScreenCondition']
            >[1];
        const match = registry.matchesScreenCondition;

        expect(match(undefined, undefined)).toBe(true);
        expect(match({ runtime: true }, undefined)).toBe(true);
        expect(match({ eggTags: { any: ['minecraft'] } }, undefined)).toBe(false);
        expect(match({ eggTags: { any: ['minecraft', 'rust'] } }, server(null, ['Rust']))).toBe(true);
        expect(match({ eggTags: { all: ['minecraft', 'paper'] } }, server(null, ['minecraft']))).toBe(false);
        expect(
            match({ eggFeatures: { all: ['eula'], any: ['java_version', 'pid_limit'] } }, server(['EULA'], []))
        ).toBe(false);
        expect(
            match({ eggFeatures: { all: ['eula'], any: ['java_version'] } }, server(['eula', 'java_version'], []))
        ).toBe(true);
        const either = { eggFeatures: { any: ['eula'] }, eggTags: { any: ['minecraft'] } };

        expect(match(either, server(['eula'], []))).toBe(false);
        expect(match({ ...either, match: 'any' }, server(['eula'], []))).toBe(true);
        expect(match({ ...either, match: 'any' }, server(null, []))).toBe(false);
    });

    it('rejects egg rules outside the server area and keeps conditions on the registration', () => {
        registry.prepareExtensions([
            {
                id: 'misplaced',
                entry: '/a.js',
                screens: [{ id: 'main', area: 'account', path: 'a', when: { eggTags: { any: ['minecraft'] } } }],
            },
            {
                id: 'gated',
                entry: '/b.js',
                screens: [{ id: 'main', area: 'server', path: 'b', when: { eggTags: { any: ['minecraft'] } } }],
            },
        ]);
        expect(registry.getLoadableExtensions()?.map((entry) => entry.id)).toEqual(['gated']);
        expect(registry.getExtensionScreens('server')[0].when).toEqual({ eggTags: { any: ['minecraft'] } });
    });

    it('ties visibility predicates to the manifest flag and commits screen options atomically', () => {
        registry.prepareExtensions([
            {
                id: 'demo',
                entry: '/demo.js',
                config: { limit: 3 },
                screens: [
                    { id: 'gated', area: 'account', path: 'gated', when: { runtime: true } },
                    { id: 'open', area: 'account', path: 'open' },
                ],
            },
        ]);
        const importer = async () => ({ default: Component });
        const visible = () => true;
        const badge = () => 3;

        const undeclared = registry.createExtensionRegistryBatch();

        expect(() => registry.registerScreen('demo', 'open', importer, undeclared, { visible })).toThrow(
            '"runtime": true'
        );
        expect(() =>
            registry.registerScreen('demo', 'open', importer, undeclared, { badge: 'three' as never })
        ).toThrow('must be functions');
        registry.abortExtensionRegistryBatch(undeclared);

        const missing = registry.createExtensionRegistryBatch();

        registry.registerScreen('demo', 'gated', importer, missing);
        registry.registerScreen('demo', 'open', importer, missing, { badge });
        expect(() => registry.commitExtensionRegistryBatch('demo', missing)).toThrow('Missing visibility predicate');
        expect(registry.getScreenOptions('demo', 'open')).toBeUndefined();
        registry.abortExtensionRegistryBatch(missing);

        const changed = vi.fn();

        registry.subscribeExtensionRegistry(changed);
        const batch = registry.createExtensionRegistryBatch();

        registry.registerScreen('demo', 'gated', importer, batch, { visible });
        registry.registerScreen('demo', 'open', importer, batch, { badge });
        expect(registry.getScreenOptions('demo', 'gated')).toBeUndefined();
        registry.commitExtensionRegistryBatch('demo', batch);
        expect(registry.getScreenOptions('demo', 'gated')).toEqual({ visible, badge: undefined });
        expect(registry.getScreenOptions('demo', 'open')?.badge).toBe(badge);
        expect(Object.isFrozen(registry.getScreenOptions('demo', 'open'))).toBe(true);
        expect(changed).toHaveBeenCalledTimes(1);
        expect(registry.getExtensionConfig('demo')).toEqual({ limit: 3 });
        expect(registry.getExtensionConfig('unknown')).toBe(registry.getExtensionConfig('missing'));
    });

    it('places resource screens in their own parent and rejects collisions without rejecting healthy extensions', () => {
        registry.prepareExtensions(
            [
                {
                    id: 'probe',
                    entry: '/probe.js',
                    screens: [
                        { id: 'node', area: 'admin', parent: 'admin.node', path: 'probe' },
                        { id: 'server', area: 'admin', parent: 'admin.server', path: 'probe' },
                    ],
                },
                {
                    id: 'collision',
                    entry: '/collision.js',
                    screens: [{ id: 'node', area: 'admin', parent: 'admin.node', path: 'settings/extra' }],
                },
                {
                    id: 'duplicate',
                    entry: '/duplicate.js',
                    screens: [{ id: 'node', area: 'admin', parent: 'admin.node', path: 'probe' }],
                },
            ],
            { admin: ['nodes/$id'], server: [], account: [] },
            { 'admin.node': ['settings'] }
        );

        expect(registry.getExtensionScreens('admin')).toEqual([]);
        expect(registry.getExtensionScreens('admin', 'admin.node').map((screen) => screen.id)).toEqual(['node']);
        expect(registry.getExtensionScreens('admin', 'admin.server').map((screen) => screen.id)).toEqual(['server']);
        expect(registry.getLoadableExtensions()?.map((entry) => entry.id)).toEqual(['probe']);
    });
});
