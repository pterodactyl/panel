import { useState } from 'react';
import { useField, useForm, useStore } from '@tanstack/react-form';
import { useLocation, useSearch } from '@tanstack/react-router';
import { useLogin, useLoginCheckpoint } from '@/api/auth/queries';
import { isString } from '@/lib/objects';
import { parseRedirectSearch } from '@/router/redirect';
import { useRouteSlotData } from '@/router/routeSlots';
import { useOpenLoginCheckpoint } from '@/sdk/navigation';
import { boundField, fixtureField } from '../forms';
import { implementViews } from '../registry';
import { useCaptcha } from './captcha';
import { authPage } from './page';
import type { CheckpointFormModel, LoginFormModel } from './types';

const required = (message: string) => ({
    onChange: ({ value }: { value: string }) => (value.length >= 1 ? undefined : message),
});

function useLoginFormModel(): LoginFormModel {
    const { redirect } = parseRedirectSearch(useSearch({ strict: false }));
    const openCheckpoint = useOpenLoginCheckpoint();
    const route = useRouteSlotData();
    const login = useLogin();
    const { Captcha, token } = useCaptcha();
    const form = useForm({
        defaultValues: { username: '', password: '' },
        onSubmit: async ({ value }) => {
            const captcha = await token();

            if (captcha === null) {
                return;
            }

            try {
                const response = await login.mutateAsync({
                    body: { user: value.username, password: value.password, 'g-recaptcha-response': captcha },
                });

                if (response.data.complete) {
                    window.location.assign(redirect || response.data.intended || '/');

                    return;
                }

                void openCheckpoint(response.data.confirmation_token);
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });
    const username = useField({
        form,
        name: 'username',
        validators: required('A username or email must be provided.'),
    });
    const password = useField({ form, name: 'password', validators: required('Please enter your account password.') });
    const canSubmit = useStore(form.store, (state) => state.canSubmit);
    const submitting = useStore(form.store, (state) => state.isSubmitting) || login.isPending;

    return {
        fields: {
            username: boundField(username, { type: 'text', autoComplete: 'username', disabled: submitting }),
            password: boundField(password, {
                type: 'password',
                autoComplete: 'current-password',
                disabled: submitting,
            }),
        },
        submit: () => void form.handleSubmit(),
        canSubmit,
        submitting,
        Captcha,
        forgotPassword: { to: '/auth/password' },
        route,
    };
}

function useCheckpointFormModel(): CheckpointFormModel {
    // The login form hands over the confirmation token in history state; the route requires it.
    const state = useLocation().state;
    const token = 'token' in state && isString(state.token) ? state.token : '';
    const { redirect } = parseRedirectSearch(useSearch({ strict: false }));
    const checkpoint = useLoginCheckpoint();
    const [recovery, setRecovery] = useState(false);
    const form = useForm({
        defaultValues: { code: '', recoveryCode: '' },
        onSubmit: async ({ value }) => {
            try {
                const response = await checkpoint.mutateAsync({
                    body: {
                        confirmation_token: token,
                        authentication_code: value.code,
                        recovery_token: value.recoveryCode || undefined,
                    },
                });

                if (response.data.complete) {
                    window.location.assign(redirect || response.data.intended || '/');
                }
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });
    const code = useField({ form, name: 'code' });
    const recoveryCode = useField({ form, name: 'recoveryCode' });
    const canSubmit = useStore(form.store, (state) => state.canSubmit);
    const submitting = useStore(form.store, (state) => state.isSubmitting);

    return {
        fields: { code: boundField(recovery ? recoveryCode : code, { type: 'text', autoComplete: 'one-time-code' }) },
        submit: () => void form.handleSubmit(),
        canSubmit,
        submitting,
        recovery,
        toggleRecovery: () => {
            form.setFieldValue('code', '');
            form.setFieldValue('recoveryCode', '');
            setRecovery((current) => !current);
        },
        login: { to: '/auth/login', search: { redirect } },
    };
}

const noop = () => {};

const route = { pathname: '/auth/login', search: {}, params: {} };

function loginFixture(
    values: { username: string; password: string } = { username: '', password: '' },
    overrides: Partial<LoginFormModel> = {}
): LoginFormModel {
    const disabled = overrides.submitting ?? false;

    return {
        fields: {
            username: fixtureField('username', { type: 'text', autoComplete: 'username', disabled }, values.username),
            password: fixtureField(
                'password',
                { type: 'password', autoComplete: 'current-password', disabled },
                values.password
            ),
        },
        submit: noop,
        canSubmit: true,
        submitting: false,
        Captcha: null,
        forgotPassword: { to: '/auth/password' },
        route,
        ...overrides,
    };
}

const checkpointFixture = (recovery: boolean): CheckpointFormModel => ({
    fields: { code: fixtureField(recovery ? 'recoveryCode' : 'code', { type: 'text', autoComplete: 'one-time-code' }) },
    submit: noop,
    canSubmit: true,
    submitting: false,
    recovery,
    toggleRecovery: noop,
    login: { to: '/auth/login', search: {} },
});

implementViews({
    'page.auth.login': authPage,
    'page.auth.checkpoint': authPage,
    'auth.loginForm': {
        use: useLoginFormModel,
        fixtures: {
            default: () => loginFixture(),
            invalid: () => {
                const model = loginFixture();

                return {
                    ...model,
                    fields: {
                        username: { ...model.fields.username, error: 'A username or email must be provided.' },
                        password: { ...model.fields.password, error: 'Please enter your account password.' },
                    },
                    canSubmit: false,
                };
            },
            submitting: () => loginFixture({ username: 'alex', password: 'correct horse' }, { submitting: true }),
        },
    },
    'auth.checkpointForm': {
        use: useCheckpointFormModel,
        fixtures: { code: () => checkpointFixture(false), recovery: () => checkpointFixture(true) },
    },
});
