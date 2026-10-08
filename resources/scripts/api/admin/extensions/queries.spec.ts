import { AxiosError, AxiosHeaders } from 'axios';
import { describe, expect, it } from 'vitest';
import { extensionReplacement } from './queries';

/** Conflict meta as the install endpoint sends it, loosened to cover malformed values. */
type ConflictMeta = {
    identifier?: string | number;
    version?: string | null;
    installed_version?: string | number;
    enabled?: boolean | string;
};

type ConflictBody = null | {
    errors?: string | readonly { code?: string; detail?: string; meta?: ConflictMeta | string }[];
};

const failure = (status: number, data: ConflictBody) => {
    const config = { headers: new AxiosHeaders() };

    return new AxiosError('Request failed', AxiosError.ERR_BAD_REQUEST, config, undefined, {
        status,
        statusText: '',
        headers: {},
        config,
        data,
    });
};

const conflict = (meta: ConflictMeta | string) =>
    failure(409, { errors: [{ code: 'ExtensionAlreadyInstalled', meta }] });

describe('extensionReplacement', () => {
    it('reads the installed extension from a 409', () => {
        expect(
            extensionReplacement(
                conflict({ identifier: 'billing', version: '2.0.0', installed_version: '1.4.0', enabled: true })
            )
        ).toEqual({ id: 'billing', version: '2.0.0', installedVersion: '1.4.0', enabled: true });
    });

    it('defaults the optional fields', () => {
        expect(extensionReplacement(conflict({ identifier: 'billing', version: null }))).toEqual({
            id: 'billing',
            version: '',
            installedVersion: null,
            enabled: false,
        });
    });

    it('only treats a literal true as enabled', () => {
        expect(
            extensionReplacement(
                conflict({ identifier: 'billing', version: '2.0.0', installed_version: 3, enabled: 'true' })
            )
        ).toEqual({ id: 'billing', version: '2.0.0', installedVersion: null, enabled: false });
    });

    it.each([new Error('boom'), 'conflict', null, undefined])('returns null for a non-axios cause (%s)', (cause) => {
        expect(extensionReplacement(cause)).toBeNull();
    });

    it('returns null for a status other than 409', () => {
        expect(
            extensionReplacement(failure(422, { errors: [{ meta: { identifier: 'x', version: '1' } }] }))
        ).toBeNull();
    });

    it('returns null for an axios error without a response', () => {
        expect(extensionReplacement(new AxiosError('Network Error', AxiosError.ERR_NETWORK))).toBeNull();
    });

    it.each([
        ['no body', null],
        ['no errors', {}],
        ['errors not an array', { errors: 'conflict' }],
        ['empty errors', { errors: [] }],
        ['no meta', { errors: [{ detail: 'Already installed.' }] }],
        ['meta not an object', { errors: [{ meta: 'billing' }] }],
    ])('returns null for a 409 with %s', (_label, data) => {
        expect(extensionReplacement(failure(409, data))).toBeNull();
    });

    it.each([
        ['no identifier', { version: '1.0.0' }],
        ['no version', { identifier: 'billing' }],
        ['non-string identifier', { identifier: 7, version: '1.0.0' }],
    ])('returns null when meta has %s', (_label, meta) => {
        expect(extensionReplacement(conflict(meta))).toBeNull();
    });
});
