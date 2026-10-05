import { QueryClient } from '@tanstack/react-query';
import { AxiosError, AxiosHeaders, type AxiosAdapter } from 'axios';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import http from '@/api/http';
import { queryClient as applicationClient } from '@/api/queryClient';
import { retryUnlessClientError } from '@/api/queryRetry';
import { serverStartupQueryOptions } from '@/api/server/startup/queries';

const failure = (status?: number) => {
    const config = { headers: new AxiosHeaders() };

    return new AxiosError(
        'Request failed',
        status === undefined ? AxiosError.ERR_NETWORK : AxiosError.ERR_BAD_RESPONSE,
        config,
        undefined,
        status === undefined ? undefined : { status, statusText: '', headers: {}, config, data: {} }
    );
};

describe('retryUnlessClientError', () => {
    it.each([400, 401, 403, 404, 409, 422, 429])('never retries a %i response', (status) => {
        expect(retryUnlessClientError()(0, failure(status))).toBe(false);
        expect(retryUnlessClientError(3)(0, failure(status))).toBe(false);
    });

    it.each([500, 502, 503, undefined])('retries a %s failure up to the configured count', (status) => {
        const retryOnce = retryUnlessClientError();
        const retryThrice = retryUnlessClientError(3);

        expect(retryOnce(0, failure(status))).toBe(true);
        expect(retryOnce(1, failure(status))).toBe(false);
        expect(retryThrice(2, failure(status))).toBe(true);
        expect(retryThrice(3, failure(status))).toBe(false);
    });

    it('retries errors that carry no response status', () => {
        expect(retryUnlessClientError()(0, new Error('offline'))).toBe(true);
    });
});

describe('query retry policy', () => {
    const adapter = vi.fn<AxiosAdapter>();
    const originalAdapter = http.defaults.adapter;
    let client: QueryClient;

    beforeEach(() => {
        adapter.mockReset();
        http.defaults.adapter = adapter;
        client = new QueryClient({
            defaultOptions: { queries: { ...applicationClient.getDefaultOptions().queries, retryDelay: 0 } },
        });
    });

    afterEach(() => {
        client.clear();
        http.defaults.adapter = originalAdapter;
    });

    const respondWith = (status: number) =>
        adapter.mockImplementation(async (config) => {
            throw new AxiosError('Request failed', AxiosError.ERR_BAD_RESPONSE, config, undefined, {
                status,
                statusText: '',
                headers: {},
                config,
                data: { errors: [{ code: 'TwoFactorAuthRequiredException', status: String(status), detail: '' }] },
            });
        });

    it('requests a 4xx resource once by default', async () => {
        respondWith(400);
        await expect(
            client.fetchQuery({ queryKey: ['default'], queryFn: () => http.get('/api/thing') })
        ).rejects.toThrow();
        expect(adapter).toHaveBeenCalledOnce();
    });

    it('retries a 5xx response once by default', async () => {
        respondWith(503);
        await expect(
            client.fetchQuery({ queryKey: ['default'], queryFn: () => http.get('/api/thing') })
        ).rejects.toThrow();
        expect(adapter).toHaveBeenCalledTimes(2);
    });

    it('retries startup reads three times for 5xx responses and never for 4xx responses', async () => {
        respondWith(403);
        await expect(client.fetchQuery({ ...serverStartupQueryOptions('alpha'), retryDelay: 0 })).rejects.toThrow();
        expect(adapter).toHaveBeenCalledOnce();

        client.clear();
        adapter.mockReset();
        respondWith(503);
        await expect(client.fetchQuery({ ...serverStartupQueryOptions('alpha'), retryDelay: 0 })).rejects.toThrow();
        expect(adapter).toHaveBeenCalledTimes(4);
    });
});
