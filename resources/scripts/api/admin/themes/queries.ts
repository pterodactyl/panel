import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';

import {
    adminActivateThemeMutation,
    adminClearThemeImageMutation,
    adminGetThemeSettingsOptions,
    adminGetThemeSettingsQueryKey,
    adminListThemesOptions,
    adminListThemesQueryKey,
    adminPreviewThemeMutation,
    adminResetThemeMutation,
    adminStopThemePreviewMutation,
    adminUpdateThemeSettingsMutation,
    adminUploadThemeImageMutation,
} from '@/api/generated/@tanstack/react-query.gen';
import type {
    AdminActivateThemeData,
    AdminClearThemeImageData,
    AdminGetThemeSettingsResponse,
    AdminListThemesResponse,
    AdminPreviewThemeData,
    AdminUpdateThemeSettingsData,
    AdminUploadThemeImageData,
    Options,
} from '@/api/generated';
import { notifyHttpError } from '@/plugins/notifications';

export type AdminTheme = AdminListThemesResponse['data'][number]['attributes'];
export type AdminThemesMeta = NonNullable<AdminListThemesResponse['meta']>;
export type AdminThemeSetting = AdminGetThemeSettingsResponse['data'][number]['attributes'];
export type AdminThemeSettingValue = AdminThemeSetting['value'];

export const adminThemesQueryOptions = () => adminListThemesOptions();

export const adminThemeSettingsQueryOptions = (theme: string): ReturnType<typeof adminGetThemeSettingsOptions> =>
    adminGetThemeSettingsOptions({ path: { theme } });

export const activateAdminThemeInput = (theme: string, name: string): Options<AdminActivateThemeData> => ({
    path: { theme },
    meta: { name },
});

export const previewAdminThemeInput = (theme: string, name: string): Options<AdminPreviewThemeData> => ({
    path: { theme },
    meta: { name },
});

export const updateAdminThemeSettingsInput = (
    theme: string,
    settings: Record<string, AdminThemeSettingValue>
): Options<AdminUpdateThemeSettingsData> => ({
    path: { theme },
    body: { settings },
});

export const uploadAdminThemeImageInput = (
    theme: string,
    setting: string,
    file: File
): Options<AdminUploadThemeImageData> => ({
    path: { theme, setting },
    body: { file },
});

export const clearAdminThemeImageInput = (theme: string, setting: string): Options<AdminClearThemeImageData> => ({
    path: { theme, setting },
});

export const useAdminThemes = () => useQuery(adminThemesQueryOptions());

export const useAdminThemeSettings = (theme: string, options?: { enabled?: boolean }) =>
    useQuery({ ...adminThemeSettingsQueryOptions(theme), ...options });

/** A theme applies from the next page load on, so changing it offers a reload. */
const notifyReloadToApply = (title: string, description: string) =>
    toast.success(title, {
        description: `${description} Reload the page to see it.`,
        action: { label: 'Reload', onClick: () => window.location.reload() },
    });

const useThemeListMutation = () => {
    const queryClient = useQueryClient();

    return () => queryClient.invalidateQueries({ queryKey: adminListThemesQueryKey() });
};

export const useActivateAdminTheme = () => {
    const invalidate = useThemeListMutation();

    return useMutation({
        ...adminActivateThemeMutation(),
        onSuccess: async (_data, variables) => {
            await invalidate();
            notifyReloadToApply('Theme activated', `${String(variables.meta?.name ?? 'The theme')} is now active.`);
        },
        onError: (error) => notifyHttpError(error, 'Unable to activate the theme'),
    });
};

export const useResetAdminTheme = () => {
    const invalidate = useThemeListMutation();

    return useMutation({
        ...adminResetThemeMutation(),
        onSuccess: async () => {
            await invalidate();
            notifyReloadToApply('Theme reset', 'The classic theme is active again.');
        },
        onError: (error) => notifyHttpError(error, 'Unable to reset the theme'),
    });
};

export const usePreviewAdminTheme = () => {
    const invalidate = useThemeListMutation();

    return useMutation({
        ...adminPreviewThemeMutation(),
        onSuccess: async (_data, variables) => {
            await invalidate();
            notifyReloadToApply(
                'Previewing theme',
                `You see ${String(variables.meta?.name ?? 'the theme')} until you stop the preview; nobody else does.`
            );
        },
        onError: (error) => notifyHttpError(error, 'Unable to preview the theme'),
    });
};

export const useStopAdminThemePreview = () => {
    const invalidate = useThemeListMutation();

    return useMutation({
        ...adminStopThemePreviewMutation(),
        onSuccess: async () => {
            await invalidate();
            notifyReloadToApply('Preview stopped', 'You see the active theme again.');
        },
        onError: (error) => notifyHttpError(error, 'Unable to stop the preview'),
    });
};

/** The settings endpoints answer with the fresh settings, which replace the cached ones. */
const useThemeSettingsMutation = () => {
    const queryClient = useQueryClient();

    return (data: AdminGetThemeSettingsResponse, theme: string | undefined) =>
        queryClient.setQueryData(adminGetThemeSettingsQueryKey({ path: { theme: theme ?? '' } }), data);
};

export const useUpdateAdminThemeSettings = () => {
    const store = useThemeSettingsMutation();

    return useMutation({
        ...adminUpdateThemeSettingsMutation(),
        onSuccess: (data, variables) => {
            store(data, variables.path.theme);
            toast.success('Theme settings saved');
        },
        onError: (error) => notifyHttpError(error, 'Unable to save the theme settings'),
    });
};

export const useUploadAdminThemeImage = () => {
    const store = useThemeSettingsMutation();

    return useMutation({
        ...adminUploadThemeImageMutation(),
        onSuccess: (data, variables) => {
            store(data, variables.path.theme);
            toast.success('Image uploaded');
        },
        onError: (error) => notifyHttpError(error, 'Unable to upload the image'),
    });
};

export const useClearAdminThemeImage = () => {
    const store = useThemeSettingsMutation();

    return useMutation({
        ...adminClearThemeImageMutation(),
        onSuccess: (data, variables) => {
            store(data, variables.path.theme);
            toast.success('Image removed');
        },
        onError: (error) => notifyHttpError(error, 'Unable to remove the image'),
    });
};
