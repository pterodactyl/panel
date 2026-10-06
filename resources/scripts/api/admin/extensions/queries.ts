import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';

import {
    adminClearExtensionSettingFileMutation,
    adminDisableExtensionMutation,
    adminEnableExtensionMutation,
    adminGetExtensionFormValuesOptions,
    adminGetExtensionSettingsOptions,
    adminGetExtensionSettingsQueryKey,
    adminInstallExtensionMutation,
    adminListExtensionsOptions,
    adminListExtensionsQueryKey,
    adminRemoveExtensionMutation,
    adminUpdateExtensionSettingsMutation,
    adminUploadExtensionSettingFileMutation,
} from '@/api/generated/@tanstack/react-query.gen';
import type {
    AdminClearExtensionSettingFileData,
    AdminDisableExtensionData,
    AdminEnableExtensionData,
    AdminExtensionFieldValues,
    AdminGetExtensionSettingsResponse,
    AdminInstallExtensionData,
    AdminListExtensionsResponse,
    AdminRemoveExtensionData,
    AdminUpdateExtensionSettingsData,
    AdminUploadExtensionSettingFileData,
    Options,
} from '@/api/generated';
import { notifyHttpError } from '@/plugins/notifications';

export type AdminExtensionsResponse = AdminListExtensionsResponse;
export type AdminExtension = NonNullable<AdminExtensionsResponse['data']>[number];
export type ExtensionFormValuesResponse = AdminExtensionFieldValues;

export const adminExtensionFormValuesQueryOptions = (form: string, id: number) => ({
    ...adminGetExtensionFormValuesOptions({ path: { form, id } }),
    select: (response: { data: AdminExtensionFieldValues }) => response.data,
});

type AdminExtensionsQueryOptions = { enabled?: boolean };

export const adminExtensionsQueryOptions = () => adminListExtensionsOptions();

export const installAdminExtensionInput = (file: File, enable: boolean): Options<AdminInstallExtensionData> => ({
    body: { package: file, enable },
});

export const enableAdminExtensionInput = (extension: string): Options<AdminEnableExtensionData> => ({
    path: { extension },
    meta: { name: extension },
});

export const disableAdminExtensionInput = (extension: string): Options<AdminDisableExtensionData> => ({
    path: { extension },
    meta: { name: extension },
});

export const removeAdminExtensionInput = (extension: string, name?: string): Options<AdminRemoveExtensionData> => ({
    path: { extension },
    meta: { name: name ?? extension },
});

export type AdminExtensionSettingField = AdminGetExtensionSettingsResponse['data']['schema'][number];
export type AdminExtensionSettingValue = AdminExtensionSettingField['value'];

export const adminExtensionSettingsQueryOptions = (
    extension: string
): ReturnType<typeof adminGetExtensionSettingsOptions> => adminGetExtensionSettingsOptions({ path: { extension } });

export const updateAdminExtensionSettingsInput = (
    extension: string,
    settings: Record<string, AdminExtensionSettingValue>
): Options<AdminUpdateExtensionSettingsData> => ({
    // Scribe names the PATCH path parameter extension_id; the GET uses extension.
    path: { extension_id: extension },
    body: { settings },
    meta: { name: extension },
});

export const uploadAdminExtensionSettingFileInput = (
    extension: string,
    input: string,
    file: File
): Options<AdminUploadExtensionSettingFileData> => ({
    path: { extension, input },
    body: { file },
});

export const clearAdminExtensionSettingFileInput = (
    extension: string,
    input: string
): Options<AdminClearExtensionSettingFileData> => ({
    path: { extension, input },
});

export const useAdminExtensions = (options?: AdminExtensionsQueryOptions) =>
    useQuery({ ...adminExtensionsQueryOptions(), ...options });

export const useAdminExtensionSettings = (extension: string, options?: { enabled?: boolean }) =>
    useQuery({ ...adminExtensionSettingsQueryOptions(extension), ...options });

export const useUpdateAdminExtensionSettings = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminUpdateExtensionSettingsMutation(),
        onSuccess: async (_data, variables) => {
            await queryClient.invalidateQueries({
                queryKey: adminGetExtensionSettingsQueryKey({
                    path: { extension: variables.path.extension_id },
                }),
            });
            const name = variables.meta?.name ?? 'the extension';
            toast.success('Settings saved', { description: `Settings for ${name} have been updated.` });
        },
        onError: (error) => notifyHttpError(error, 'Unable to update extension settings'),
    });
};

export const useUploadAdminExtensionSettingFile = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminUploadExtensionSettingFileMutation(),
        onSuccess: (data, variables) => {
            queryClient.setQueryData(
                adminGetExtensionSettingsQueryKey({ path: { extension: variables.path.extension } }),
                data
            );
            toast.success('File uploaded');
        },
        onError: (error) => notifyHttpError(error, 'Unable to upload the file'),
    });
};

export const useClearAdminExtensionSettingFile = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminClearExtensionSettingFileMutation(),
        onSuccess: (data, variables) => {
            queryClient.setQueryData(
                adminGetExtensionSettingsQueryKey({ path: { extension: variables.path.extension } }),
                data
            );
            toast.success('File removed');
        },
        onError: (error) => notifyHttpError(error, 'Unable to remove the file'),
    });
};

const notifyReloadToApply = (title: string, description: string) =>
    toast.success(title, {
        description: `${description} Reload the page to apply it.`,
        action: { label: 'Reload', onClick: () => window.location.reload() },
    });

export const useInstallAdminExtension = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminInstallExtensionMutation(),
        onSuccess: async (data) => {
            await queryClient.invalidateQueries({ queryKey: adminListExtensionsQueryKey() });
            const description = `${data.data?.name ?? 'The extension'} has been installed.`;
            if (data.data?.enabled) {
                notifyReloadToApply('Extension installed', description);
            } else {
                toast.success('Extension installed', { description });
            }
        },
        onError: (error) => notifyHttpError(error, 'Unable to install extension'),
    });
};

export const useEnableAdminExtension = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminEnableExtensionMutation(),
        onSuccess: async (data, variables) => {
            await queryClient.invalidateQueries({ queryKey: adminListExtensionsQueryKey() });
            const fallback = variables.meta?.name ?? 'The extension';
            notifyReloadToApply('Extension enabled', `${data.data?.name ?? fallback} has been enabled.`);
        },
        onError: (error) => notifyHttpError(error, 'Unable to enable extension'),
    });
};

export const useDisableAdminExtension = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminDisableExtensionMutation(),
        onSuccess: async (data, variables) => {
            await queryClient.invalidateQueries({ queryKey: adminListExtensionsQueryKey() });
            const fallback = variables.meta?.name ?? 'The extension';
            notifyReloadToApply('Extension disabled', `${data.data?.name ?? fallback} has been disabled.`);
        },
        onError: (error) => notifyHttpError(error, 'Unable to disable extension'),
    });
};

export const useRemoveAdminExtension = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminRemoveExtensionMutation(),
        onSuccess: async (_data, variables) => {
            await queryClient.invalidateQueries({ queryKey: adminListExtensionsQueryKey() });
            const name = variables.meta?.name ?? 'The extension';
            notifyReloadToApply('Extension removed', `${name} has been removed.`);
        },
        onError: (error) => notifyHttpError(error, 'Unable to remove extension'),
    });
};
