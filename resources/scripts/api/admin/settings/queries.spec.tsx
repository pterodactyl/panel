// @vitest-environment jsdom

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { act, renderHook } from '@testing-library/react';
import type { PropsWithChildren } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { AdminUpdateMailSettingsData, Options } from '@/api/generated';
import { useUpdateAdminMailSettings } from '@/api/admin/settings/queries';

const mocks = vi.hoisted(() => ({
    updateMail: vi.fn(),
    notifyError: vi.fn(),
    toastSuccess: vi.fn(),
}));

const settingsQueryKey = [{ _id: 'adminGetSettings' }] as const;

vi.mock('sonner', () => ({ toast: { success: mocks.toastSuccess } }));
vi.mock('@/plugins/notifications', () => ({ notifyHttpError: mocks.notifyError }));
vi.mock('@/api/generated/@tanstack/react-query.gen', () => ({
    adminGetSettingsOptions: () => ({ queryKey: settingsQueryKey, queryFn: vi.fn() }),
    adminGetSettingsQueryKey: () => settingsQueryKey,
    adminSendTestMailMutation: () => ({ mutationFn: vi.fn() }),
    adminUpdateAdvancedSettingsMutation: () => ({ mutationFn: vi.fn() }),
    adminUpdateGeneralSettingsMutation: () => ({ mutationFn: vi.fn() }),
    adminUpdateMailSettingsMutation: () => ({ mutationFn: mocks.updateMail }),
}));

describe('admin settings mutations', () => {
    beforeEach(() => vi.clearAllMocks());

    it('invalidates settings without showing the optional mail notification', async () => {
        const queryClient = new QueryClient({ defaultOptions: { mutations: { retry: false } } });
        const invalidate = vi.spyOn(queryClient, 'invalidateQueries').mockResolvedValue();
        const wrapper = ({ children }: PropsWithChildren) => (
            <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
        );

        mocks.updateMail.mockResolvedValue(undefined);
        const { result } = renderHook(() => useUpdateAdminMailSettings({ successNotification: false }), { wrapper });

        await act(async () => result.current.mutateAsync({ body: {} } as Options<AdminUpdateMailSettingsData>));

        expect(invalidate).toHaveBeenCalledWith({ queryKey: settingsQueryKey });
        expect(mocks.toastSuccess).not.toHaveBeenCalled();
    });
});
