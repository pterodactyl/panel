import { describe, expect, it } from 'vitest';
import { encodePathSegments, hashToPath, newDirectoryDisplayName, normalizeServerPath } from '@/helpers';

describe('file manager paths', () => {
    it.each(['', '#', '/', '#/', '//'])('normalizes the root hash %j', (hash) => {
        expect(hashToPath(hash)).toBe('/');
    });

    it.each(['#/server.properties', '/server.properties', 'server.properties'])(
        'normalizes the file hash %j',
        (hash) => {
            expect(hashToPath(hash)).toBe('/server.properties');
        }
    );

    it('normalizes repeated separators and decodes path segments', () => {
        expect(hashToPath('#//plugins//My%20Plugin//config.yml')).toBe('/plugins/My Plugin/config.yml');
    });

    it('encodes file names without adding another root separator', () => {
        expect(encodePathSegments('/file editor QA.txt')).toBe('/file%20editor%20QA.txt');
    });
});

describe('new directory names', () => {
    it.each([
        ['test', 'test'],
        ['./test', 'test'],
        ['/test', 'test'],
        ['../test', 'test'],
        ['../../test/nested', 'test'],
        ['test/nested/deeper', 'test'],
        ['./a/../b', 'b'],
    ])('lists %j as %j', (name, expected) => {
        expect(newDirectoryDisplayName(name)).toBe(expected);
    });

    it('normalizes a joined preview path without leading traversal', () => {
        expect(normalizeServerPath('/plugins/./config')).toBe('plugins/config');
        expect(normalizeServerPath('../../etc')).toBe('etc');
    });
});
