import { useField, useForm, useStore } from '@tanstack/react-form';
import { useParams, useSearch } from '@tanstack/react-router';
import { usePerformPasswordReset, useRequestPasswordResetEmail } from '@/api/auth/queries';
import { parseEmailSearch } from '@/router/pageSearch';
import { boundField, fixtureField } from '../forms';
import { implementViews } from '../registry';
import { useCaptcha } from './captcha';
import type { ForgotPasswordFormModel, ResetPasswordFormModel } from './types';

const isEmail = (value: string) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
const newPassword = { type: 'password', autoComplete: 'new-password' } as const;

function useForgotPasswordFormModel(): ForgotPasswordFormModel {
    const requestReset = useRequestPasswordResetEmail();
    const { Captcha, token } = useCaptcha();
    const form = useForm({
        defaultValues: { email: '' },
        onSubmit: async ({ value, formApi }) => {
            const captcha = await token();

            if (captcha === null) {
                return;
            }

            try {
                await requestReset.mutateAsync({ body: { email: value.email, 'g-recaptcha-response': captcha } });
                formApi.reset();
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });
    const email = useField({
        form,
        name: 'email',
        validators: {
            onChange: ({ value }) =>
                isEmail(value) ? undefined : 'A valid email address must be provided to continue.',
        },
    });
    const canSubmit = useStore(form.store, (state) => state.canSubmit);
    const submitting = useStore(form.store, (state) => state.isSubmitting);

    return {
        fields: { email: boundField(email, { type: 'email', autoComplete: 'email' }) },
        submit: () => void form.handleSubmit(),
        canSubmit,
        submitting,
        Captcha,
        login: { to: '/auth/login' },
    };
}

function useResetPasswordFormModel(): ResetPasswordFormModel {
    const { token: resetToken = '' } = useParams({ strict: false });
    const { email = '' } = parseEmailSearch(useSearch({ strict: false }));
    const resetPassword = usePerformPasswordReset();
    const { Captcha, token } = useCaptcha();
    const form = useForm({
        defaultValues: { password: '', passwordConfirmation: '' },
        onSubmit: async ({ value }) => {
            const captcha = await token();

            if (captcha === null) {
                return;
            }

            try {
                await resetPassword.mutateAsync({
                    body: {
                        email,
                        token: resetToken,
                        password: value.password,
                        password_confirmation: value.passwordConfirmation,
                        'g-recaptcha-response': captcha,
                    },
                });
                window.location.assign('/');
            } catch {
                // Error toast is handled by the mutation.
            }
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
    const confirmation = useField({
        form,
        name: 'passwordConfirmation',
        validators: {
            onChangeListenTo: ['password'],
            onChange: ({ value, fieldApi }) =>
                value === fieldApi.form.getFieldValue('password') ? undefined : 'Your new password does not match.',
        },
    });
    const canSubmit = useStore(form.store, (state) => state.canSubmit);
    const submitting = useStore(form.store, (state) => state.isSubmitting);

    return {
        fields: {
            password: boundField(password, newPassword),
            passwordConfirmation: boundField(confirmation, newPassword),
        },
        submit: () => void form.handleSubmit(),
        canSubmit,
        submitting,
        Captcha,
        email,
        login: { to: '/auth/login' },
    };
}

const noop = () => {};

const forgotFixture = (error: string | null = null): ForgotPasswordFormModel => ({
    fields: { email: fixtureField('email', { type: 'email', autoComplete: 'email' }, '', error) },
    submit: noop,
    canSubmit: error === null,
    submitting: false,
    Captcha: null,
    login: { to: '/auth/login' },
});

const resetFixture = (mismatch: boolean): ResetPasswordFormModel => ({
    fields: {
        password: fixtureField('password', newPassword, 'correct horse'),
        passwordConfirmation: fixtureField(
            'passwordConfirmation',
            newPassword,
            mismatch ? 'correct horse battery' : '',
            mismatch ? 'Your new password does not match.' : null
        ),
    },
    submit: noop,
    canSubmit: !mismatch,
    submitting: false,
    Captcha: null,
    email: 'alex@example.test',
    login: { to: '/auth/login' },
});

implementViews({
    'page.auth.password': {
        use: useForgotPasswordFormModel,
        fixtures: {
            default: () => forgotFixture(),
            invalid: () => forgotFixture('A valid email address must be provided to continue.'),
        },
    },
    'page.auth.passwordReset': {
        use: useResetPasswordFormModel,
        fixtures: { default: () => resetFixture(false), mismatch: () => resetFixture(true) },
    },
});
