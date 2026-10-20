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
 * @returns {Promise<unknown>}
 */
const request = (status: number): Promise<unknown> =>
    http.get('/api/client/account', {
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

it('ends the session and continues at sign in once a request is unauthenticated', async () => {
    await expect(request(401)).rejects.toBeInstanceOf(AxiosError);
    await expect(request(401)).rejects.toBeInstanceOf(AxiosError);

    expect(hasBootstrapSession()).toBe(false);
    expect(router.navigate).toHaveBeenCalledOnce();
    expect(router.navigate).toHaveBeenCalledWith({
        to: '/auth/login',
        search: { redirect: '/server/abc' },
        replace: true,
    });
});

it('leaves other failures alone', async () => {
    await expect(request(403)).rejects.toBeInstanceOf(AxiosError);
    await expect(request(500)).rejects.toBeInstanceOf(AxiosError);

    expect(router.navigate).not.toHaveBeenCalled();
});
