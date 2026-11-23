import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import i18next from 'i18next';

import {
    adminActivateThemeMutation,
    adminClearThemeImageMutation,
    adminGetThemeSettingsOptions,
    adminGetThemeSettingsQueryKey,
    adminListThemesOptions,
    adminListThemesQueryKey,
    adminPreviewThemeMutation,
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

export type AdminThemesResponse = AdminListThemesResponse;
export type AdminThemeSettingsResponse = AdminGetThemeSettingsResponse;
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
        description,
        action: { label: i18next.t('Reload'), onClick: () => window.location.reload() },
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
            const name = variables.meta?.name;

            notifyReloadToApply(
                i18next.t('Theme activated'),
                name === undefined
                    ? i18next.t('The theme is now active. Reload the page to see it.')
                    : i18next.t('{{name}} is now active. Reload the page to see it.', { name })
            );
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to activate the theme')),
    });
};

export const usePreviewAdminTheme = () => {
    const invalidate = useThemeListMutation();

    return useMutation({
        ...adminPreviewThemeMutation(),
        onSuccess: async (_data, variables) => {
            await invalidate();
            const name = variables.meta?.name;

            notifyReloadToApply(
                i18next.t('Previewing theme'),
                name === undefined
                    ? i18next.t(
                          'You see the theme until you stop the preview; nobody else does. Reload the page to see it.'
                      )
                    : i18next.t(
                          'You see {{name}} until you stop the preview; nobody else does. Reload the page to see it.',
                          { name }
                      )
            );
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to preview the theme')),
    });
};

export const useStopAdminThemePreview = () => {
    const invalidate = useThemeListMutation();

    return useMutation({
        ...adminStopThemePreviewMutation(),
        onSuccess: async () => {
            await invalidate();
            notifyReloadToApply(
                i18next.t('Preview stopped'),
                i18next.t('You see the active theme again. Reload the page to see it.')
            );
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to stop the preview')),
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
            notifyReloadToApply(
                i18next.t('Theme settings saved'),
                i18next.t('Pages render with them from now on. Reload the page to see it.')
            );
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to save the theme settings')),
    });
};

export const useUploadAdminThemeImage = () => {
    const store = useThemeSettingsMutation();

    return useMutation({
        ...adminUploadThemeImageMutation(),
        onSuccess: (data, variables) => {
            store(data, variables.path.theme);
            notifyReloadToApply(
                i18next.t('Image uploaded'),
                i18next.t('Pages render with it from now on. Reload the page to see it.')
            );
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to upload the image')),
    });
};

export const useClearAdminThemeImage = () => {
    const store = useThemeSettingsMutation();

    return useMutation({
        ...adminClearThemeImageMutation(),
        onSuccess: (data, variables) => {
            store(data, variables.path.theme);
            notifyReloadToApply(
                i18next.t('Image removed'),
                i18next.t('Pages render without it from now on. Reload the page to see it.')
            );
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to remove the image')),
    });
};
