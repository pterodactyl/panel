import { AxiosError, AxiosHeaders } from 'axios';
import { describe, expect, it } from 'vitest';
import { httpErrorToHuman } from '@/api/http';

const FALLBACK = 'An unexpected error occurred.';

/** The bodies these cases send, including malformed ones the parser has to tolerate. */
type ResponseBody =
    | string
    | null
    | readonly string[]
    | { errors?: readonly { detail?: string | number | { text: string } }[]; error?: string | number };

const failure = (data?: ResponseBody) => {
    const config = { headers: new AxiosHeaders() };

    return new AxiosError(
        'Request failed with status code 422',
        AxiosError.ERR_BAD_REQUEST,
        config,
        undefined,
        data === undefined ? undefined : { status: 422, statusText: '', headers: {}, config, data }
    );
};

describe('httpErrorToHuman', () => {
    it('prefers the first JSON:API error detail', () => {
        const error = failure({ errors: [{ detail: 'Name is required.' }, { detail: 'Other' }], error: 'Wings' });

        expect(httpErrorToHuman(error)).toBe('Name is required.');
    });

    it('parses a string body that holds JSON', () => {
        expect(httpErrorToHuman(failure(JSON.stringify({ errors: [{ detail: 'From text.' }] })))).toBe('From text.');
        expect(httpErrorToHuman(failure(JSON.stringify({ error: 'Wings text.' })))).toBe('Wings text.');
    });

    it('reads a Wings-style error body', () => {
        expect(httpErrorToHuman(failure({ error: 'Disk is full.' }))).toBe('Disk is full.');
    });

    it('falls back to the error message for a non-JSON string body', () => {
        expect(httpErrorToHuman(failure('<html>Bad Gateway</html>'))).toBe('Request failed with status code 422');
    });

    it('ignores a detail that is not a string', () => {
        expect(httpErrorToHuman(failure({ errors: [{ detail: 42 }], error: 'Wings fallback.' }))).toBe(
            'Wings fallback.'
        );
        expect(httpErrorToHuman(failure({ errors: [{ detail: { text: 'x' } }] }))).toBe(
            'Request failed with status code 422'
        );
    });

    it('ignores bodies without a recognised message', () => {
        expect(httpErrorToHuman(failure({ errors: [] }))).toBe('Request failed with status code 422');
        expect(httpErrorToHuman(failure({ error: 500 }))).toBe('Request failed with status code 422');
        expect(httpErrorToHuman(failure(['not', 'an', 'object']))).toBe('Request failed with status code 422');
    });

    it('uses the error message when there is no response or no data', () => {
        expect(httpErrorToHuman(failure())).toBe('Request failed with status code 422');
        expect(httpErrorToHuman(failure(''))).toBe('Request failed with status code 422');
        expect(httpErrorToHuman(failure(null))).toBe('Request failed with status code 422');
    });

    it('uses the message of a plain Error', () => {
        expect(httpErrorToHuman(new Error('Network down'))).toBe('Network down');
    });

    it.each([undefined, null, 'oops', 42, { detail: 'not an error' }])(
        'returns the generic fallback for non-Error cause %s',
        (cause) => {
            expect(httpErrorToHuman(cause)).toBe(FALLBACK);
        }
    );
});
