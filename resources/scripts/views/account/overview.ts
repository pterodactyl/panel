import { useState } from 'react';
import { useField, useForm, useStore } from '@tanstack/react-form';
import { useLocation } from '@tanstack/react-router';
import { useCurrentUser, useUpdateAccountEmail, useUpdateAccountPassword } from '@/api/account/queries';
import {
    useAccountTwoFactorToken,
    useDisableAccountTwoFactor,
    useEnableAccountTwoFactor,
} from '@/api/account/two-factor/queries';
import { httpErrorToHuman } from '@/api/http';
import { boundField, fixtureField, stateField, type FieldAttributes } from '../forms';
import { VIEW_PAGES } from '../pages';
import { defineView } from '../registry';
import type {
    AccountEmailModel,
    AccountOverviewModel,
    AccountPasswordModel,
    AccountTwoFactorModel,
    TwoFactorDialog,
} from './types';

const isEmail = (value: string) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
const currentPassword = { type: 'password', autoComplete: 'current-password' } as const;
const newPassword = { type: 'password', autoComplete: 'new-password' } as const;
const totpCode: FieldAttributes = {
    type: 'text',
    inputMode: 'numeric',
    autoComplete: 'one-time-code',
    pattern: '[0-9]{6}',
};

function useAccountOverviewModel(): AccountOverviewModel {
    const state = useLocation().state;

    return { twoFactorRequired: 'twoFactorRedirect' in state && state.twoFactorRedirect === true };
}

function useAccountPasswordModel(): AccountPasswordModel {
    const updatePassword = useUpdateAccountPassword();
    const form = useForm({
        defaultValues: { current: '', password: '', confirmPassword: '' },
        onSubmit: async ({ value }) => {
            try {
                await updatePassword.mutateAsync({
                    body: {
                        current_password: value.current,
                        password: value.password,
                        password_confirmation: value.confirmPassword,
                    },
                });
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });
    const current = useField({
        form,
        name: 'current',
        validators: {
            onChange: ({ value }) => (value.length >= 1 ? undefined : 'You must provide your current password.'),
        },
    });
    const password = useField({
        form,
        name: 'password',
        validators: {
            onChange: ({ value }) =>
                value.length >= 8 ? undefined : 'Your new password should be at least 8 characters in length.',
        },
    });
    const confirmPassword = useField({
        form,
        name: 'confirmPassword',
        validators: {
            onChangeListenTo: ['password'],
            onChange: ({ value, fieldApi }) =>
                value === fieldApi.form.getFieldValue('password')
                    ? undefined
                    : 'Password confirmation does not match the password you entered.',
        },
    });
    const canSubmit = useStore(form.store, (state) => state.canSubmit);
    const submitting = useStore(form.store, (state) => state.isSubmitting);

    return {
        fields: {
            current: boundField(current, currentPassword),
            password: boundField(password, newPassword),
            confirmPassword: boundField(confirmPassword, newPassword),
        },
        submit: () => void form.handleSubmit(),
        canSubmit: canSubmit && !updatePassword.isPending,
        submitting: submitting || updatePassword.isPending,
    };
}

function useAccountEmailModel(): AccountEmailModel {
    const user = useCurrentUser();
    const updateEmail = useUpdateAccountEmail();
    const form = useForm({
        defaultValues: { email: user.email, password: '' },
        onSubmit: async ({ value, formApi }) => {
            try {
                await updateEmail.mutateAsync({ body: value });
                formApi.setFieldValue('password', '');
            } catch {
                // Error toast is handled by the mutation.
            } finally {
                formApi.setFieldMeta('password', (meta) => ({ ...meta, errors: [] }));
            }
        },
    });
    const email = useField({
        form,
        name: 'email',
        validators: {
            onChange: ({ value }) => (isEmail(value) ? undefined : 'A valid email address must be provided.'),
        },
    });
    const password = useField({
        form,
        name: 'password',
        validators: {
            onChange: ({ value }) =>
                value.length >= 1 ? undefined : 'You must provide your current account password.',
        },
    });
    const canSubmit = useStore(form.store, (state) => state.canSubmit);
    const submitting = useStore(form.store, (state) => state.isSubmitting);

    return {
        fields: {
            email: boundField(email, { type: 'email', autoComplete: 'email' }),
            password: boundField(password, currentPassword),
        },
        submit: () => void form.handleSubmit(),
        canSubmit: canSubmit && !updateEmail.isPending,
        submitting: submitting || updateEmail.isPending,
    };
}

function useAccountTwoFactorModel(): AccountTwoFactorModel {
    const enabled = useCurrentUser().useTotp;
    const [dialog, setDialog] = useState<TwoFactorDialog | null>(null);
    const [recoveryTokens, setRecoveryTokens] = useState<readonly string[]>([]);
    const [code, setCode] = useState('');
    const [setupPassword, setSetupPassword] = useState('');
    const [disablePassword, setDisablePassword] = useState('');
    const secret = useAccountTwoFactorToken(dialog === 'enable');
    const enable = useEnableAccountTwoFactor();
    const disable = useDisableAccountTwoFactor();
    const token = secret.data?.data;
    const failed = secret.error && !secret.isFetching ? httpErrorToHuman(secret.error) : null;

    const submitSetup = async () => {
        if (enable.isPending) {
            return;
        }

        try {
            const response = await enable.mutateAsync({ body: { code, password: setupPassword } });

            setRecoveryTokens(response.attributes.tokens);
            setDialog(null);
        } catch {
            // Error toast is handled by the mutation.
        }
    };

    const submitDisable = async () => {
        if (disable.isPending) {
            return;
        }

        try {
            await disable.mutateAsync({ body: { password: disablePassword } });
            setDialog(null);
        } catch {
            // Error toast is handled by the mutation.
        }
    };

    return {
        enabled,
        dialog,
        // Each dialog opens empty, as it did when its form mounted with it.
        openDialog: (next) => {
            setCode('');
            setSetupPassword('');
            setDisablePassword('');
            setDialog(next);
        },
        closeDialog: () => setDialog(null),
        setup: {
            fields: {
                code: stateField('code', code, setCode, totpCode),
                password: stateField('password', setupPassword, setSetupPassword, currentPassword),
            },
            submit: () => void submitSetup(),
            canSubmit: !!token && code.length === 6 && setupPassword.length > 0 && !enable.isPending,
            submitting: enable.isPending,
            secret: token?.secret ?? null,
            qrCode: token?.image_url_data ?? null,
            error: failed,
            retry: () => void secret.refetch(),
        },
        disable: {
            fields: { password: stateField('password', disablePassword, setDisablePassword, currentPassword) },
            submit: () => void submitDisable(),
            canSubmit: disablePassword.length > 0 && !disable.isPending,
            submitting: disable.isPending,
        },
        recoveryTokens,
        dismissRecoveryTokens: () => setRecoveryTokens([]),
    };
}

const noop = () => {};

const passwordFixture = (error: string | null = null): AccountPasswordModel => ({
    fields: {
        current: fixtureField('current', currentPassword),
        password: fixtureField('password', newPassword, error ? 'short' : '', error),
        confirmPassword: fixtureField('confirmPassword', newPassword),
    },
    submit: noop,
    canSubmit: error === null,
    submitting: false,
});

const emailFixture = (submitting = false): AccountEmailModel => ({
    fields: {
        email: fixtureField('email', { type: 'email', autoComplete: 'email' }, 'alex@example.test'),
        password: fixtureField('password', currentPassword, submitting ? 'correct horse' : ''),
    },
    submit: noop,
    canSubmit: !submitting,
    submitting,
});

const SECRET = 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP';

function twoFactorFixture(overrides: Partial<AccountTwoFactorModel> = {}): AccountTwoFactorModel {
    return {
        enabled: false,
        dialog: null,
        openDialog: noop,
        closeDialog: noop,
        setup: {
            fields: { code: fixtureField('code', totpCode), password: fixtureField('password', currentPassword) },
            submit: noop,
            canSubmit: false,
            submitting: false,
            secret: SECRET,
            qrCode: `otpauth://totp/Pterodactyl:alex%40example.test?secret=${SECRET}&issuer=Pterodactyl`,
            error: null,
            retry: noop,
        },
        disable: {
            fields: { password: fixtureField('password', currentPassword) },
            submit: noop,
            canSubmit: false,
            submitting: false,
        },
        recoveryTokens: [],
        dismissRecoveryTokens: noop,
        ...overrides,
    };
}

export const overviewViews = [
    defineView('page.account.overview', {
        ...VIEW_PAGES['page.account.overview'],
        use: useAccountOverviewModel,
        slots: ['account.overview.before', 'account.overview.after'],
        fixtures: {
            default: () => ({ twoFactorRequired: false }),
            twoFactorRequired: () => ({ twoFactorRequired: true }),
        },
    }),
    defineView('account.password', {
        context: 'account',
        use: useAccountPasswordModel,
        fixtures: {
            default: () => passwordFixture(),
            invalid: () => passwordFixture('Your new password should be at least 8 characters in length.'),
        },
    }),
    defineView('account.email', {
        context: 'account',
        use: useAccountEmailModel,
        fixtures: { default: () => emailFixture(), submitting: () => emailFixture(true) },
    }),
    defineView('account.twoFactor', {
        context: 'account',
        use: useAccountTwoFactorModel,
        fixtures: {
            disabled: () => twoFactorFixture(),
            enabled: () => twoFactorFixture({ enabled: true }),
            setup: () => twoFactorFixture({ dialog: 'enable' }),
            setupLoading: () => {
                const model = twoFactorFixture({ dialog: 'enable' });

                return { ...model, setup: { ...model.setup, secret: null, qrCode: null } };
            },
            disabling: () => twoFactorFixture({ enabled: true, dialog: 'disable' }),
            recoveryTokens: () =>
                twoFactorFixture({
                    enabled: true,
                    recoveryTokens: [
                        'a1b2c3d4e5',
                        'f6g7h8i9j0',
                        'k1l2m3n4o5',
                        'p6q7r8s9t0',
                        'u1v2w3x4y5',
                        'z6a7b8c9d0',
                    ],
                }),
        },
    }),
];
