// @vitest-environment jsdom

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { act, renderHook, waitFor } from '@testing-library/react';
import type { PropsWithChildren } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type {
    ClientCreateAccountApiKeyData,
    ClientDeleteAccountApiKeyData,
    ClientListAccountApiKeysResponse,
    Options,
} from '@/api/generated';
import { useCreateAccountApiKey, useDeleteAccountApiKey, type ApiKey } from '@/api/account/api-keys/queries';

const mocks = vi.hoisted(() => ({
    create: vi.fn(),
    delete: vi.fn(),
    notifyError: vi.fn(),
    toastSuccess: vi.fn(),
}));

const queryKey = [{ _id: 'clientListAccountApiKeys' }] as const;

vi.mock('sonner', () => ({ toast: { success: mocks.toastSuccess } }));
vi.mock('@/plugins/notifications', () => ({ notifyHttpError: mocks.notifyError }));
vi.mock('@/api/generated/@tanstack/react-query.gen', () => ({
    clientCreateAccountApiKeyMutation: () => ({ mutationFn: mocks.create }),
    clientDeleteAccountApiKeyMutation: () => ({ mutationFn: mocks.delete }),
    clientListAccountApiKeysOptions: () => ({ queryKey, queryFn: vi.fn() }),
    clientListAccountApiKeysQueryKey: () => queryKey,
}));

const apiKey = (identifier: string): ApiKey => ({ object: 'api_key', attributes: { identifier } }) as ApiKey;

const setup = () => {
    const queryClient = new QueryClient({ defaultOptions: { mutations: { retry: false }, queries: { retry: false } } });
    const wrapper = ({ children }: PropsWithChildren) => (
        <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
    );

    return { queryClient, wrapper };
};

describe('account API key mutations', () => {
    beforeEach(() => vi.clearAllMocks());

    it('updates the list, notifies once, and preserves native per-call callbacks', async () => {
        const { queryClient, wrapper } = setup();
        const created = apiKey('created');
        const perCallSuccess = vi.fn();

        mocks.create.mockResolvedValue(created);
        queryClient.setQueryData<ClientListAccountApiKeysResponse>(queryKey, { object: 'list', data: [] });
        const { result } = renderHook(() => useCreateAccountApiKey(), { wrapper });

        await act(async () => {
            await result.current.mutateAsync({} as Options<ClientCreateAccountApiKeyData>, {
                onSuccess: perCallSuccess,
            });
        });

        expect(queryClient.getQueryData<ClientListAccountApiKeysResponse>(queryKey)?.data).toEqual([created]);
        expect(mocks.toastSuccess).toHaveBeenCalledOnce();
        expect(perCallSuccess).toHaveBeenCalledOnce();
    });

    it('rolls an optimistic delete back before reporting the error', async () => {
        const { queryClient, wrapper } = setup();
        const existing = apiKey('existing');
        let rejectDelete: (error: Error) => void = () => {};

        mocks.delete.mockImplementation(
            () =>
                new Promise((_resolve, reject) => {
                    rejectDelete = reject;
                })
        );
        queryClient.setQueryData<ClientListAccountApiKeysResponse>(queryKey, {
            object: 'list',
            data: [existing],
        });
        const { result } = renderHook(() => useDeleteAccountApiKey(), { wrapper });

        act(() => {
            result.current.mutate({ path: { identifier: 'existing' } } as Options<ClientDeleteAccountApiKeyData>);
        });
        await waitFor(() =>
            expect(queryClient.getQueryData<ClientListAccountApiKeysResponse>(queryKey)?.data).toEqual([])
        );

        act(() => rejectDelete(new Error('failed')));

        await waitFor(() => expect(mocks.notifyError).toHaveBeenCalledOnce());
        expect(queryClient.getQueryData<ClientListAccountApiKeysResponse>(queryKey)?.data).toEqual([existing]);
        expect(mocks.toastSuccess).not.toHaveBeenCalled();
    });

    it('does not overwrite a concurrent creation when a deletion rolls back', async () => {
        const { queryClient, wrapper } = setup();
        const existing = apiKey('existing');
        const created = apiKey('created');
        let rejectDelete: (error: Error) => void = () => {};

        mocks.delete.mockImplementation(
            () =>
                new Promise((_resolve, reject) => {
                    rejectDelete = reject;
                })
        );
        mocks.create.mockResolvedValue(created);
        queryClient.setQueryData<ClientListAccountApiKeysResponse>(queryKey, { object: 'list', data: [existing] });
        const deletion = renderHook(() => useDeleteAccountApiKey(), { wrapper });
        const creation = renderHook(() => useCreateAccountApiKey(), { wrapper });

        act(() =>
            deletion.result.current.mutate({
                path: { identifier: 'existing' },
            } as Options<ClientDeleteAccountApiKeyData>)
        );
        await waitFor(() =>
            expect(queryClient.getQueryData<ClientListAccountApiKeysResponse>(queryKey)?.data).toEqual([])
        );
        await act(async () => {
            await creation.result.current.mutateAsync({} as Options<ClientCreateAccountApiKeyData>);
        });

        act(() => rejectDelete(new Error('failed')));

        await waitFor(() => expect(mocks.notifyError).toHaveBeenCalledOnce());
        expect(queryClient.getQueryData<ClientListAccountApiKeysResponse>(queryKey)?.data).toEqual([created, existing]);
    });
});
