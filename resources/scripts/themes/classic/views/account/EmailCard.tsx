import { Button, ContentBox, Form, SpinnerOverlay, useTranslation } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';
import { TextField } from '../shared/fields';

export default function EmailCard({ model, className }: ViewProps<'account.email'>) {
    const { t } = useTranslation();

    return (
        <ContentBox className={className} title={t('Update Email Address')}>
            <SpinnerOverlay size='large' visible={model.submitting} />
            <Form form={{ handleSubmit: model.submit }} className='m-0'>
                <TextField field={model.fields.email} id='current_email' label={t('Email')} />
                <div className='mt-6'>
                    <TextField field={model.fields.password} id='confirm_password' label={t('Confirm Password')} />
                </div>
                <div className='mt-6'>
                    <Button type='submit' disabled={!model.canSubmit} isLoading={model.submitting}>
                        {t('Update Email')}
                    </Button>
                </div>
            </Form>
        </ContentBox>
    );
}
