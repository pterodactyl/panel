import { Button, Input, PanelLink } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';
import { TextField } from '../shared/fields';
import AuthCard, { authLinkClass } from './AuthCard';

export default function ResetPasswordPage({ model, className }: ViewProps<'page.auth.passwordReset'>) {
    const { fields, Captcha } = model;

    return (
        <AuthCard title='Reset Password' submit={model.submit} className={className}>
            <div>
                <label htmlFor='password-reset-email'>Email</label>
                <Input id='password-reset-email' value={model.email} $isLight disabled />
            </div>
            <div className='mt-6'>
                <TextField
                    field={fields.password}
                    light
                    label='New Password'
                    description='Passwords must be at least 8 characters in length.'
                />
            </div>
            <div className='mt-6'>
                <TextField field={fields.passwordConfirmation} light label='Confirm New Password' />
            </div>
            <div className='mt-6'>
                <Button type='submit' size='xlarge' disabled={!model.canSubmit} isLoading={model.submitting}>
                    Reset Password
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
