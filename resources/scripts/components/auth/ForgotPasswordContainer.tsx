import { useRef } from 'react';
import { Link } from '@tanstack/react-router';
import LoginFormContainer from '@/components/auth/LoginFormContainer';
import { useAppForm } from '@/components/form';
import type { InvisibleRecaptchaHandle } from '@/components/elements/InvisibleRecaptcha';
import InvisibleRecaptcha from '@/components/elements/InvisibleRecaptcha';
import { useSiteSettings } from '@/api/settings/queries';
import { useRequestPasswordResetEmail } from '@/api/auth/queries';
import { toast } from 'sonner';

const isEmail = (value: string) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);

export default function ForgotPasswordContainer() {
    const recaptchaRef = useRef<InvisibleRecaptchaHandle>(null);

    const requestReset = useRequestPasswordResetEmail();
    const { enabled: recaptchaEnabled, siteKey } = useSiteSettings().recaptcha;

    const form = useAppForm({
        defaultValues: { email: '' },
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
                await requestReset.mutateAsync({
                    body: {
                        email: value.email,
                        'g-recaptcha-response': token,
                    },
                });
                form.reset();
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    return (
        <LoginFormContainer form={form} title={'Request Password Reset'} className={'w-full flex'}>
            <form.AppField
                name={'email'}
                validators={{
                    onChange: ({ value }) =>
                        isEmail(value) ? undefined : 'A valid email address must be provided to continue.',
                }}
            >
                {(field) => (
                    <field.TextField
                        light
                        type={'email'}
                        label={'Email'}
                        description={
                            'Enter your account email address to receive instructions on resetting your password.'
                        }
                    />
                )}
            </form.AppField>
            <div className={'mt-6'}>
                <form.AppForm>
                    <form.SubmitButton size={'xlarge'}>Send Email</form.SubmitButton>
                </form.AppForm>
            </div>
            {recaptchaEnabled && <InvisibleRecaptcha ref={recaptchaRef} siteKey={siteKey || ''} />}
            <div className={'mt-6 text-center'}>
                <Link
                    to={'/auth/login'}
                    className={
                        'text-xs text-muted-foreground tracking-wide uppercase no-underline hover:text-foreground'
                    }
                >
                    Return to Login
                </Link>
            </div>
        </LoginFormContainer>
    );
}
