import { useRef } from 'react';
import { Link, useParams, useSearch } from '@tanstack/react-router';
import LoginFormContainer from '@/components/auth/LoginFormContainer';
import { useAppForm } from '@/components/form';
import { TextInput } from '@/components/form/controls';
import type { InvisibleRecaptchaHandle } from '@/components/elements/InvisibleRecaptcha';
import InvisibleRecaptcha from '@/components/elements/InvisibleRecaptcha';
import { useSiteSettings } from '@/api/settings/queries';
import { usePerformPasswordReset } from '@/api/auth/queries';
import { toast } from 'sonner';

export default function ResetPasswordContainer() {
    const params = useParams({ strict: false });
    const search = useSearch({ from: '/auth/password/reset/$token' });
    const email = search.email ?? '';
    const recaptchaRef = useRef<InvisibleRecaptchaHandle>(null);
    const resetPassword = usePerformPasswordReset();
    const { enabled: recaptchaEnabled, siteKey } = useSiteSettings().recaptcha;

    const form = useAppForm({
        defaultValues: { password: '', passwordConfirmation: '' },
        onSubmit: async ({ value }) => {
            let recaptchaToken = '';

            if (recaptchaEnabled) {
                recaptchaToken = (await recaptchaRef.current?.execute()) ?? '';
                if (!recaptchaToken) {
                    toast.error('Captcha verification failed, please try again.');

                    return;
                }
            }

            try {
                await resetPassword.mutateAsync({
                    body: {
                        email,
                        token: params.token ?? '',
                        password: value.password,
                        password_confirmation: value.passwordConfirmation,
                        'g-recaptcha-response': recaptchaToken,
                    },
                });
                window.location.assign('/');
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    return (
        <LoginFormContainer form={form} title='Reset Password' className='w-full flex'>
            <div>
                <label htmlFor='password-reset-email'>Email</label>
                <TextInput id='password-reset-email' value={email} $isLight disabled />
            </div>
            <div className='mt-6'>
                <form.AppField
                    name='password'
                    validators={{
                        onChange: ({ value }) =>
                            value.length >= 8
                                ? undefined
                                : 'Your new password should be at least 8 characters in length.',
                    }}
                >
                    {(field) => (
                        <field.TextField
                            light
                            label='New Password'
                            type='password'
                            description='Passwords must be at least 8 characters in length.'
                        />
                    )}
                </form.AppField>
            </div>
            <div className='mt-6'>
                <form.AppField
                    name='passwordConfirmation'
                    validators={{
                        onChangeListenTo: ['password'],
                        onChange: ({ value, fieldApi }) =>
                            value === fieldApi.form.getFieldValue('password')
                                ? undefined
                                : 'Your new password does not match.',
                    }}
                >
                    {(field) => <field.TextField light label='Confirm New Password' type='password' />}
                </form.AppField>
            </div>
            <div className='mt-6'>
                <form.AppForm>
                    <form.SubmitButton size='xlarge'>Reset Password</form.SubmitButton>
                </form.AppForm>
            </div>
            {recaptchaEnabled && <InvisibleRecaptcha ref={recaptchaRef} siteKey={siteKey || ''} />}
            <div className='mt-6 text-center'>
                <Link
                    to='/auth/login'
                    className='text-xs text-muted-foreground tracking-wide no-underline uppercase hover:text-foreground'
                >
                    Return to Login
                </Link>
            </div>
        </LoginFormContainer>
    );
}
