import { queryOptions, useMutation, useQuery, useQueryClient, type QueryClient } from '@tanstack/react-query';
import i18next from 'i18next';
import { toast } from 'sonner';
import type { UserData } from '@/api/account/types';
import { getBootstrapUser } from '@/bootstrap';
import {
    clientDeleteAccountAvatarMutation,
    clientUpdateAccountAvatarMutation,
    clientUpdateAccountEmailMutation,
    clientUpdateAccountLanguageMutation,
    clientUpdateAccountPasswordMutation,
} from '@/api/generated/@tanstack/react-query.gen';
import { clientGetAccount, type ClientUserResource } from '@/api/generated';
import { notifyHttpError } from '@/plugins/notifications';

export const currentUserQueryKey = ['account', 'current-user'] as const;

type CurrentUserUpdater = Partial<UserData>;

const getBootstrapCurrentUser = (): UserData => {
    const user = getBootstrapUser();

    if (!user) {
        throw new Error('Current user has not been hydrated.');
    }

    return user;
};

/** Keeps the fields the account endpoint does not return. */
const withAccount = (user: UserData, { attributes }: ClientUserResource): UserData => ({
    ...user,
    username: attributes.username,
    email: attributes.email,
    language: attributes.language,
    rootAdmin: attributes.admin,
    avatar: attributes.avatar,
});

export const currentUserQueryOptions = () =>
    queryOptions({
        queryKey: currentUserQueryKey,
        queryFn: async ({ client, signal }): Promise<UserData> => {
            const { data } = await clientGetAccount({ signal });

            return withAccount(client.getQueryData<UserData>(currentUserQueryKey) ?? getBootstrapCurrentUser(), data);
        },
        initialData: getBootstrapCurrentUser,
        staleTime: Infinity,
    });

export const useCurrentUser = () => useQuery(currentUserQueryOptions()).data;

export const setCurrentUserQueryData = (queryClient: QueryClient, updater: CurrentUserUpdater) => {
    queryClient.setQueryData<UserData>(currentUserQueryKey, (user) => {
        const current = user ?? getBootstrapCurrentUser();

        return { ...current, ...updater };
    });
};

export const useUpdateAccountEmail = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientUpdateAccountEmailMutation(),
        onSuccess: (_data, variables) => {
            setCurrentUserQueryData(queryClient, { email: variables.body.email });
            toast.success(i18next.t('Email address updated'));
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to update email address')),
    });
};

export const useUpdateAccountPassword = () =>
    useMutation({
        ...clientUpdateAccountPasswordMutation(),
        onSuccess: () => window.location.assign('/auth/login'),
        onError: (error) => notifyHttpError(error, i18next.t('Unable to update password')),
    });

export const useUpdateAccountLanguage = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientUpdateAccountLanguageMutation(),
        onSuccess: ({ attributes }) => {
            setCurrentUserQueryData(queryClient, { language: attributes.language });
            toast.success(i18next.t('Language updated'));
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to update language')),
    });
};

export const useUpdateAccountAvatar = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientUpdateAccountAvatarMutation(),
        onSuccess: async ({ attributes }) => {
            setCurrentUserQueryData(queryClient, { avatar: attributes.avatar });
            await queryClient.invalidateQueries({ queryKey: currentUserQueryKey });
            toast.success(i18next.t('Avatar updated'));
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to update avatar')),
    });
};

export const useDeleteAccountAvatar = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientDeleteAccountAvatarMutation(),
        onSuccess: async () => {
            setCurrentUserQueryData(queryClient, { avatar: null });
            await queryClient.invalidateQueries({ queryKey: currentUserQueryKey });
            toast.success(i18next.t('Avatar removed'));
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to remove avatar')),
    });
};
