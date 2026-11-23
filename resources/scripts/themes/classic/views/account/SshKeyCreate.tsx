import { Button, ContentBox, Form, SpinnerOverlay, useTranslation } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';
import { TextAreaField, TextField } from '../shared/fields';

export default function SshKeyCreate({ model, className }: ViewProps<'account.sshKeys.create'>) {
    const { t } = useTranslation();

    return (
        <ContentBox title={t('Add SSH Key')} className={className}>
            <Form form={{ handleSubmit: model.submit }}>
                <SpinnerOverlay visible={model.submitting} />
                <div className='mb-6'>
                    <TextField field={model.fields.name} label={t('SSH Key Name')} />
                </div>
                <TextAreaField
                    field={model.fields.publicKey}
                    label={t('Public Key')}
                    rows={6}
                    description={t('Enter your public SSH key.')}
                />
                <div className='flex justify-end mt-6'>
                    <Button type='submit' disabled={!model.canSubmit} isLoading={model.submitting}>
                        {t('Save')}
                    </Button>
                </div>
            </Form>
        </ContentBox>
    );
}
