import { Button, PanelLink, useTranslation } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';
import { TextField } from '../shared/fields';
import AuthCard, { authLinkClass } from './AuthCard';

export default function ForgotPasswordPage({ model, className }: ViewProps<'page.auth.password'>) {
    const { Captcha } = model;
    const { t } = useTranslation();

    return (
        <AuthCard title={t('Request Password Reset')} submit={model.submit} className={className}>
            <TextField
                field={model.fields.email}
                light
                label={t('Email')}
                description={t('Enter your account email address to receive instructions on resetting your password.')}
            />
            <div className='mt-6'>
                <Button type='submit' size='xlarge' disabled={!model.canSubmit} isLoading={model.submitting}>
                    {t('Send Email')}
                </Button>
            </div>
            {Captcha && <Captcha />}
            <div className='mt-6 text-center'>
                <PanelLink destination={model.login} className={authLinkClass}>
                    {t('Return to Login')}
                </PanelLink>
            </div>
        </AuthCard>
    );
}
