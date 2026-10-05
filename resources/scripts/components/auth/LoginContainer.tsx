import { useRef } from 'react';
import { Link, useNavigate } from '@tanstack/react-router';
import LoginFormContainer from '@/components/auth/LoginFormContainer';
import { useStore } from '@tanstack/react-form';
import { useAppForm } from '@/components/form';
import type { InvisibleRecaptchaHandle } from '@/components/elements/InvisibleRecaptcha';
import InvisibleRecaptcha from '@/components/elements/InvisibleRecaptcha';
import { useSiteSettings } from '@/api/settings/queries';
import { useLogin } from '@/api/auth/queries';
import { toast } from 'sonner';
import Slot from '@/extensions/Slot';
import { useRouteSlotData } from '@/router/routeSlots';

const LoginContainer = () => {
    const navigate = useNavigate();
    const slotData = useRouteSlotData();
    const recaptchaRef = useRef<InvisibleRecaptchaHandle>(null);

    const loginMutation = useLogin();
    const { enabled: recaptchaEnabled, siteKey } = useSiteSettings().recaptcha;

    const form = useAppForm({
        defaultValues: { username: '', password: '' },
        onSubmit: async ({ value }) => {
            let token = '';
            if (recaptchaEnabled) {
                token = (await recaptchaRef.current?.execute()) ?? '';
                if (!token) {
                    toast.error('Captcha verification failed, please try again.');
                    return;
                }
            }

            try {
                const response = await loginMutation.mutateAsync({
                    body: {
                        user: value.username,
                        password: value.password,
                        'g-recaptcha-response': token,
                    },
                });
                if (response.data.complete) {
                    window.location.assign(response.data.intended || '/');
                    return;
                }

                navigate({
                    to: '/auth/login/checkpoint',
                    replace: true,
                    state: { token: response.data.confirmation_token },
                });
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    const isSubmitting = useStore(form.store, (state) => state.isSubmitting) || loginMutation.isPending;

    return (
        <LoginFormContainer form={form} title={'Login to Continue'} className={'w-full flex'}>
            <form.AppField
                name={'username'}
                validators={{
                    onChange: ({ value }) => (value.length >= 1 ? undefined : 'A username or email must be provided.'),
                }}
            >
                {(field) => <field.TextField light type={'text'} label={'Username or Email'} disabled={isSubmitting} />}
            </form.AppField>
            <div className={'mt-6'}>
                <form.AppField
                    name={'password'}
                    validators={{
                        onChange: ({ value }) =>
                            value.length >= 1 ? undefined : 'Please enter your account password.',
                    }}
                >
                    {(field) => <field.TextField light type={'password'} label={'Password'} disabled={isSubmitting} />}
                </form.AppField>
            </div>
            <div className={'mt-6'}>
                <form.AppForm>
                    <form.SubmitButton size={'xlarge'}>Login</form.SubmitButton>
                </form.AppForm>
            </div>
            <Slot name={'auth.login.form.after'} data={slotData} />
            {recaptchaEnabled && <InvisibleRecaptcha ref={recaptchaRef} siteKey={siteKey || ''} />}
            <div className={'mt-6 text-center'}>
                <Link
                    to={'/auth/password'}
                    className={
                        'text-xs text-muted-foreground tracking-wide no-underline uppercase hover:text-foreground'
                    }
                >
                    Forgot password?
                </Link>
            </div>
        </LoginFormContainer>
    );
};

export default LoginContainer;
