/** @vitest-environment jsdom */
import { AxiosError, AxiosHeaders, type InternalAxiosRequestConfig } from 'axios';
import { afterEach, beforeAll, beforeEach, expect, it, vi } from 'vitest';
import http from '@/api/http';
import { setupInterceptors } from '@/api/interceptors';
import { hasBootstrapSession } from '@/bootstrap';
import { router } from '@/router/router';

vi.mock('@/router/router', () => ({
    router: { navigate: vi.fn(() => Promise.resolve()), latestLocation: { href: '/server/abc' } },
}));

type BootstrapWindow = Window & { PterodactylUser?: unknown };

/**
 * @param {number} status
 * @param {typeof http} client
 * @returns {Promise<unknown>}
 */
const request = (status: number, client: typeof http = http): Promise<unknown> =>
    client.get('/api/client/account', {
        adapter: (config: InternalAxiosRequestConfig) =>
            Promise.reject(
                new AxiosError('Request failed', AxiosError.ERR_BAD_REQUEST, config, undefined, {
                    status,
                    statusText: '',
                    headers: new AxiosHeaders(),
                    config,
                    data: { errors: [{ code: 'AuthenticationException', detail: 'Unauthenticated.' }] },
                })
            ),
    });

beforeAll(() => {
    setupInterceptors();
});

beforeEach(() => {
    (window as BootstrapWindow).PterodactylUser = { uuid: 'user', created_at: '', updated_at: '' };
});

afterEach(() => {
    vi.mocked(router.navigate).mockClear();
    delete (window as BootstrapWindow).PterodactylUser;
});

it('leaves other failures alone', async () => {
    await expect(request(403)).rejects.toBeInstanceOf(AxiosError);
    await expect(request(500)).rejects.toBeInstanceOf(AxiosError);

    expect(hasBootstrapSession()).toBe(true);
    expect(router.navigate).not.toHaveBeenCalled();
});

it.each([401, 419])('ends the session and continues at sign in after a %i', async (status) => {
    vi.resetModules();
    const { default: freshHttp } = await import('@/api/http');
    const { setupInterceptors: setup } = await import('@/api/interceptors');
    const { hasBootstrapSession: hasSession } = await import('@/bootstrap');

    setup();

    await expect(request(status, freshHttp)).rejects.toBeInstanceOf(AxiosError);
    await expect(request(status, freshHttp)).rejects.toBeInstanceOf(AxiosError);

    expect(hasSession()).toBe(false);
    expect(router.navigate).toHaveBeenCalledOnce();
    expect(router.navigate).toHaveBeenCalledWith({
        to: '/auth/login',
        search: { redirect: '/server/abc' },
        replace: true,
    });
});
