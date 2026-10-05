import { useMutation } from '@tanstack/react-query';
import { toast } from 'sonner';
import {
    authCompleteLoginCheckpointMutation,
    authLoginMutation,
    authLogoutMutation,
    authRequestPasswordResetEmailMutation,
    authResetPasswordMutation,
} from '@/api/generated/@tanstack/react-query.gen';
import { authGetCsrfCookie } from '@/api/generated/sdk.gen';
import { notifyHttpError } from '@/plugins/notifications';

export const useLogin = () =>
    useMutation({
        ...authLoginMutation(),
        onMutate: async () => {
            await authGetCsrfCookie();
        },
        onError: (error) => notifyHttpError(error, 'Unable to log in'),
    });

export const useLoginCheckpoint = () =>
    useMutation({
        ...authCompleteLoginCheckpointMutation(),
        onError: (error) => notifyHttpError(error, 'Unable to verify two-step code'),
    });

export const useRequestPasswordResetEmail = () =>
    useMutation({
        ...authRequestPasswordResetEmailMutation(),
        onSuccess: (response) => toast.success(response.status || 'Password reset email sent'),
        onError: (error) => notifyHttpError(error, 'Unable to request password reset'),
    });

export const usePerformPasswordReset = () =>
    useMutation({
        ...authResetPasswordMutation(),
        onError: (error) => notifyHttpError(error, 'Unable to reset password'),
    });

export const useLogout = () =>
    useMutation({
        ...authLogoutMutation(),
        onSettled: () => {
            window.location.assign('/');
        },
    });
