import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { isAxiosError } from 'axios';
import type { AxiosResponse } from 'axios';
import { toast } from 'sonner';

import {
    adminClearExtensionSettingFileMutation,
    adminDisableExtensionMutation,
    adminEnableExtensionMutation,
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
    AdminGetExtensionSettingsResponse,
    AdminInstallExtensionData,
    AdminListExtensionsResponse,
    AdminRemoveExtensionData,
    AdminUpdateExtensionSettingsData,
    AdminUploadExtensionSettingFileData,
    Options,
} from '@/api/generated';
import { isObject, isString } from '@/lib/objects';
import { notifyHttpError } from '@/plugins/notifications';

export type AdminExtensionsResponse = AdminListExtensionsResponse;
export type AdminExtension = NonNullable<AdminExtensionsResponse['data']>[number];

type AdminExtensionsQueryOptions = { enabled?: boolean };

export const adminExtensionsQueryOptions = () => adminListExtensionsOptions();

export const installAdminExtensionInput = (
    file: File,
    enable: boolean,
    replace = false
): Options<AdminInstallExtensionData> => ({
    body: { package: file, enable, replace },
});

/** The installed extension an uploaded package would replace, which the admin has to confirm. */
export type AdminExtensionReplacement = {
    id: string;
    version: string;
    installedVersion: string | null;
    enabled: boolean;
};

/** The `meta` object of the first JSON:API error in a response body. */
const firstErrorMeta = (response: AxiosResponse): unknown => {
    const data: unknown = response.data;
    const first: unknown = isObject(data) && 'errors' in data && Array.isArray(data.errors) ? data.errors[0] : null;

    return isObject(first) && 'meta' in first ? first.meta : null;
};

/** Reads the 409 the install endpoint answers with when the package's id is already installed. */
export const extensionReplacement = (cause: unknown): AdminExtensionReplacement | null => {
    if (!isAxiosError(cause) || cause.response?.status !== 409) {
        return null;
    }

    const meta = firstErrorMeta(cause.response);

    if (!isObject(meta) || !('identifier' in meta) || !('version' in meta) || !isString(meta.identifier)) {
        return null;
    }

    const installed = 'installed_version' in meta ? meta.installed_version : null;

    return {
        id: meta.identifier,
        version: isString(meta.version) ? meta.version : '',
        installedVersion: isString(installed) ? installed : null,
        enabled: 'enabled' in meta && meta.enabled === true,
    };
};

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
        onError: (error) => {
            // The install dialog asks the admin to confirm a replacement instead.
            if (!extensionReplacement(error)) {
                notifyHttpError(error, 'Unable to install extension');
            }
        },
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
