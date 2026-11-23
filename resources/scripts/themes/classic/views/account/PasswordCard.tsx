import { Button, ContentBox, Form, SpinnerOverlay, useTranslation } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';
import { TextField } from '../shared/fields';

export default function PasswordCard({ model, className }: ViewProps<'account.password'>) {
    const { fields } = model;
    const { t } = useTranslation();

    return (
        <ContentBox className={className} title={t('Update Password')}>
            <SpinnerOverlay size='large' visible={model.submitting} />
            <Form form={{ handleSubmit: model.submit }} className='m-0'>
                <TextField field={fields.current} id='current_password' label={t('Current Password')} />
                <div className='mt-6'>
                    <TextField
                        field={fields.password}
                        id='new_password'
                        label={t('New Password')}
                        description={t(
                            'Your new password should be at least 8 characters in length and unique to this website.'
                        )}
                    />
                </div>
                <div className='mt-6'>
                    <TextField
                        field={fields.confirmPassword}
                        id='confirm_new_password'
                        label={t('Confirm New Password')}
                    />
                </div>
                <div className='mt-6'>
                    <Button type='submit' disabled={!model.canSubmit} isLoading={model.submitting}>
                        {t('Update Password')}
                    </Button>
                </div>
            </Form>
        </ContentBox>
    );
}
