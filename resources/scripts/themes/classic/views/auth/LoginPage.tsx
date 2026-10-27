import { Button, PanelLink, useTranslation } from '@/sdk';
import { Slot, type ViewProps } from '@/sdk/theme';
import { TextField } from '../shared/fields';
import AuthCard, { authLinkClass } from './AuthCard';

export default function LoginPage({ model, className }: ViewProps<'page.auth.login'>) {
    const { fields, Captcha } = model;
    const { t } = useTranslation();

    return (
        <AuthCard title={t('Login to Continue')} submit={model.submit} className={className}>
            <TextField field={fields.username} light label={t('Username or Email')} />
            <div className='mt-6'>
                <TextField field={fields.password} light label={t('Password')} />
            </div>
            <div className='mt-6'>
                <Button type='submit' size='xlarge' disabled={!model.canSubmit} isLoading={model.submitting}>
                    {t('Login')}
                </Button>
            </div>
            <Slot name='auth.login.form.after' data={model.route} />
            {Captcha && <Captcha />}
            <div className='mt-6 text-center'>
                <PanelLink destination={model.forgotPassword} className={authLinkClass}>
                    {t('Forgot password?')}
                </PanelLink>
            </div>
        </AuthCard>
    );
}
