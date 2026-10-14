import { Button, PanelLink } from '@/sdk';
import { Slot, type ViewProps } from '@/sdk/theme';
import { TextField } from '../shared/fields';
import AuthCard, { authLinkClass } from './AuthCard';

export default function LoginPage({ model, className }: ViewProps<'page.auth.login'>) {
    const { fields, Captcha } = model;

    return (
        <AuthCard title='Login to Continue' submit={model.submit} className={className}>
            <TextField field={fields.username} light label='Username or Email' />
            <div className='mt-6'>
                <TextField field={fields.password} light label='Password' />
            </div>
            <div className='mt-6'>
                <Button type='submit' size='xlarge' disabled={!model.canSubmit} isLoading={model.submitting}>
                    Login
                </Button>
            </div>
            <Slot name='auth.login.form.after' data={model.route} />
            {Captcha && <Captcha />}
            <div className='mt-6 text-center'>
                <PanelLink destination={model.forgotPassword} className={authLinkClass}>
                    Forgot password?
                </PanelLink>
            </div>
        </AuthCard>
    );
}
