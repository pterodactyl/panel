import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';

import { setCurrentUserQueryData } from '@/api/account/queries';
import {
    clientDisableTwoFactorAuthenticationMutation,
    clientEnableTwoFactorAuthenticationMutation,
    clientGetTwoFactorSetupOptions,
} from '@/api/generated/@tanstack/react-query.gen';
import { notifyHttpError } from '@/plugins/notifications';

export const useAccountTwoFactorToken = (enabled = true) => useQuery({ ...clientGetTwoFactorSetupOptions(), enabled });

export const useEnableAccountTwoFactor = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientEnableTwoFactorAuthenticationMutation(),
        onSuccess: () => {
            setCurrentUserQueryData(queryClient, { useTotp: true });
            toast.success('Two-step verification enabled');
        },
        onError: (error) => notifyHttpError(error, 'Unable to enable two-step verification'),
    });
};

export const useDisableAccountTwoFactor = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientDisableTwoFactorAuthenticationMutation(),
        onSuccess: () => {
            setCurrentUserQueryData(queryClient, { useTotp: false });
            toast.success('Two-step verification disabled');
        },
        onError: (error) => notifyHttpError(error, 'Unable to disable two-step verification'),
    });
};
