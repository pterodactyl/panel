import { expect, it, vi } from 'vitest';
import { resolvePanelDestination } from './navigation';
import { prepareExtensions } from '@/extensions/registry';

it('encodes parameters and preserves search and history options for core navigation', () => {
    expect(
        resolvePanelDestination({
            to: '/server/$id/files/$action',
            params: { id: 'one/two', action: 'edit' },
            search: { file: '/hello.txt' },
            replace: true,
        })
    ).toEqual({
        pathname: '/server/one%2Ftwo/files/edit',
        search: { file: '/hello.txt' },
        replace: true,
        hash: undefined,
    });
    expect(() => resolvePanelDestination({ to: '/server/$id', params: { id: '' } })).toThrow(
        'Missing navigation parameter'
    );
});

it('resolves declared extension screens before implementations load and rejects unknown IDs', () => {
    vi.spyOn(console, 'error').mockImplementation(() => {});
    prepareExtensions([
        {
            id: 'probe',
            entry: '/probe.js',
            screens: [
                { id: 'main', area: 'server', path: 'probe/$tab' },
                { id: 'node', area: 'admin', parent: 'admin.node', path: 'probe' },
            ],
        },
    ]);

    expect(
        resolvePanelDestination({ extension: 'probe', screen: 'main', params: { id: 'server', tab: 'settings' } })
            .pathname
    ).toBe('/server/server/probe/settings');
    expect(resolvePanelDestination({ extension: 'probe', screen: 'node', params: { id: '42' } }).pathname).toBe(
        '/panel/nodes/42/probe'
    );
    expect(() => resolvePanelDestination({ extension: 'probe', screen: 'missing' })).toThrow(
        'Unknown extension screen'
    );
});
