import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';

import {
    adminGetSettingsOptions,
    adminGetSettingsQueryKey,
    adminSendTestMailMutation,
    adminUpdateAdvancedSettingsMutation,
    adminUpdateGeneralSettingsMutation,
    adminUpdateMailSettingsMutation,
} from '@/api/generated/@tanstack/react-query.gen';
import type {
    AdminGetSettingsResponse,
    AdminSendTestMailData,
    AdminUpdateAdvancedSettingsData,
    AdminUpdateGeneralSettingsData,
    AdminUpdateMailSettingsData,
    Options,
} from '@/api/generated';
import { setSiteSettingsQueryData } from '@/api/settings/queries';
import { notifyHttpError } from '@/plugins/notifications';

export type AdminSettings = AdminGetSettingsResponse;
type AdminGeneralSettingsBody = AdminUpdateGeneralSettingsData['body'];
type AdminMailSettingsBody = NonNullable<AdminUpdateMailSettingsData['body']>;
type AdminMailSmtpSettingsBody = NonNullable<AdminMailSettingsBody['smtp']>;
type AdminAdvancedSettingsBody = AdminUpdateAdvancedSettingsData['body'];
type UpdateAdminMailSettingsOptions = { successNotification?: boolean };
type AdminSettingsSection = 'Advanced' | 'General' | 'Mail';

export interface GeneralSettingsValues {
    name: string;
    twoFactorRequired: NonNullable<AdminGeneralSettingsBody['misc']>['required_2fa'];
    locale: string;
}

export interface MailSettingsValues {
    host: string;
    port: number;
    encryption: NonNullable<AdminMailSmtpSettingsBody['encryption']> | '';
    username: string;
    // Blank keeps the existing password; "!e" clears it.
    password: string;
    fromAddress: string;
    fromName: string;
}

export interface AdvancedSettingsValues {
    recaptchaEnabled: boolean;
    recaptchaSecretKey: string;
    recaptchaWebsiteKey: string;
    guzzleTimeout: number;
    guzzleConnectTimeout: number;
    allocationsEnabled: boolean;
    allocationsRangeStart:
        | NonNullable<AdminAdvancedSettingsBody['pterodactyl:client_features:allocations:range_start']>
        | '';
    allocationsRangeEnd:
        | NonNullable<AdminAdvancedSettingsBody['pterodactyl:client_features:allocations:range_end']>
        | '';
}

const adminMailSettingsBody = (values: MailSettingsValues): AdminMailSettingsBody => ({
    smtp: {
        host: values.host,
        port: values.port,
        encryption: values.encryption === '' ? null : values.encryption,
        username: values.username,
        password: values.password,
        from_address: values.fromAddress,
        from_name: values.fromName,
    },
});

export const updateAdminGeneralSettingsInput = (
    values: GeneralSettingsValues
): Options<AdminUpdateGeneralSettingsData> => ({
    body: {
        branding: { name: values.name },
        misc: { required_2fa: values.twoFactorRequired },
        default_locale: values.locale,
    },
});

export const updateAdminMailSettingsInput = (values: MailSettingsValues): Options<AdminUpdateMailSettingsData> => ({
    body: adminMailSettingsBody(values),
});

export const updateAdminAdvancedSettingsInput = (
    values: AdvancedSettingsValues
): Options<AdminUpdateAdvancedSettingsData> => ({
    body: {
        'recaptcha:enabled': values.recaptchaEnabled ? 'true' : 'false',
        'recaptcha:secret_key': values.recaptchaSecretKey === '' ? null : values.recaptchaSecretKey,
        'recaptcha:website_key': values.recaptchaWebsiteKey,
        'pterodactyl:guzzle:timeout': values.guzzleTimeout,
        'pterodactyl:guzzle:connect_timeout': values.guzzleConnectTimeout,
        'pterodactyl:client_features:allocations:enabled': values.allocationsEnabled ? 'true' : 'false',
        'pterodactyl:client_features:allocations:range_start':
            values.allocationsRangeStart === '' ? null : values.allocationsRangeStart,
        'pterodactyl:client_features:allocations:range_end':
            values.allocationsRangeEnd === '' ? null : values.allocationsRangeEnd,
    },
});

export const testAdminMailInput = (): Options<AdminSendTestMailData> => ({});

export const adminSettingsQueryOptions = () => adminGetSettingsOptions();

type AdminSettingsQueryOptions = { enabled?: boolean };
const adminSettingsUpdateToast = (section: AdminSettingsSection) => ({
    title: `${section} settings updated`,
    description: `${section} settings have been updated.`,
});

export const useAdminSettings = (options?: AdminSettingsQueryOptions) =>
    useQuery({ ...adminSettingsQueryOptions(), ...options });

export const useUpdateAdminGeneralSettings = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminUpdateGeneralSettingsMutation(),
        onSuccess: async (_data, { body }) => {
            setSiteSettingsQueryData(queryClient, (settings) => ({
                ...settings,
                name: body.branding?.name ?? settings.name,
                locale: body.default_locale,
            }));
            await queryClient.invalidateQueries({ queryKey: adminGetSettingsQueryKey() });
            const notification = adminSettingsUpdateToast('General');
            toast.success(notification.title, { description: notification.description });
        },
        onError: (error) => notifyHttpError(error, 'Unable to update general settings'),
    });
};

export const useUpdateAdminMailSettings = ({ successNotification = true }: UpdateAdminMailSettingsOptions = {}) => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminUpdateMailSettingsMutation(),
        onSuccess: async () => {
            await queryClient.invalidateQueries({ queryKey: adminGetSettingsQueryKey() });
            if (successNotification) {
                const notification = adminSettingsUpdateToast('Mail');
                toast.success(notification.title, { description: notification.description });
            }
        },
        onError: (error) => notifyHttpError(error, 'Unable to update mail settings'),
    });
};

export const useSendTestAdminMail = () =>
    useMutation({
        ...adminSendTestMailMutation(),
        onSuccess: () =>
            toast.success('Mail settings tested', {
                description: 'Mail settings have been saved and a test email has been sent to your account.',
            }),
        onError: (error) => notifyHttpError(error, 'Unable to test mail settings'),
    });

export const useUpdateAdminAdvancedSettings = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminUpdateAdvancedSettingsMutation(),
        onSuccess: async (_data, { body }) => {
            setSiteSettingsQueryData(queryClient, (settings) => ({
                ...settings,
                recaptcha: {
                    enabled: body['recaptcha:enabled'] === 'true',
                    siteKey: body['recaptcha:website_key'],
                },
            }));
            await queryClient.invalidateQueries({ queryKey: adminGetSettingsQueryKey() });
            const notification = adminSettingsUpdateToast('Advanced');
            toast.success(notification.title, { description: notification.description });
        },
        onError: (error) => notifyHttpError(error, 'Unable to update advanced settings'),
    });
};
