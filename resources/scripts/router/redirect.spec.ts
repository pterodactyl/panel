import { expect, it } from 'vitest';
import { parseRedirectSearch } from './redirect';

it('keeps same-origin panel paths with their query and hash', () => {
    expect(parseRedirectSearch({ redirect: '/panel/nodes/42/settings' })).toEqual({
        redirect: '/panel/nodes/42/settings',
    });
    expect(parseRedirectSearch({ redirect: '/server/abc/files?dir=%2Fdata#top' })).toEqual({
        redirect: '/server/abc/files?dir=%2Fdata#top',
    });
});

it('drops anything that could leave the panel or loop back to sign in', () => {
    for (const redirect of [
        'https://evil.test/',
        '//evil.test/path',
        '/\\evil.test',
        '/\t/evil.test',
        'javascript:alert(1)',
        'panel/nodes',
        '/',
        '/auth',
        '/auth/login',
        '/auth/login/checkpoint?redirect=%2Fpanel',
        '',
        null,
        42,
    ]) {
        expect(parseRedirectSearch({ redirect })).toEqual({});
    }
});
