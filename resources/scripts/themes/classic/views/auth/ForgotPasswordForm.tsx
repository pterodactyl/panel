import { Button, PanelLink } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';
import { TextField } from '../shared/fields';
import AuthCard, { authLinkClass } from './AuthCard';

export default function ForgotPasswordForm({ model, className }: ViewProps<'auth.forgotPasswordForm'>) {
    const { Captcha } = model;

    return (
        <AuthCard title='Request Password Reset' submit={model.submit} className={className}>
            <TextField
                field={model.fields.email}
                light
                label='Email'
                description='Enter your account email address to receive instructions on resetting your password.'
            />
            <div className='mt-6'>
                <Button type='submit' size='xlarge' disabled={!model.canSubmit} isLoading={model.submitting}>
                    Send Email
                </Button>
            </div>
            {Captcha && <Captcha />}
            <div className='mt-6 text-center'>
                <PanelLink destination={model.login} className={authLinkClass}>
                    Return to Login
                </PanelLink>
            </div>
        </AuthCard>
    );
}
