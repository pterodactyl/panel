import { Button, ContentBox, CopyOnClick, Dialog, Form, SpinnerOverlay, useTranslation } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';
import { TextAreaField, TextField } from '../shared/fields';

function CreatedKeyDialog({ apiKey, onClose }: { apiKey: string | null; onClose: () => void }) {
    const { t } = useTranslation();

    return (
        <Dialog open={apiKey !== null} onClose={onClose} preventExternalClose title={t('Your API key')}>
            <p className='text-sm mb-6'>
                {t(
                    'The API key you have requested is shown below. Please store this in a safe location, it will not be shown again.'
                )}
            </p>
            <pre className='overflow-x-scroll text-sm bg-muted rounded-sm py-2 px-4 font-mono'>
                <CopyOnClick text={apiKey ?? ''}>
                    <code className='font-mono'>{apiKey}</code>
                </CopyOnClick>
            </pre>
            <div className='flex justify-end mt-6'>
                <Button type='button' onClick={onClose}>
                    {t('Close')}
                </Button>
            </div>
        </Dialog>
    );
}

export default function ApiKeyCreate({ model, className }: ViewProps<'account.apiKeys.create'>) {
    const { t } = useTranslation();

    return (
        <ContentBox title={t('Create API Key')} className={className}>
            <CreatedKeyDialog apiKey={model.createdKey} onClose={model.dismissCreatedKey} />
            <Form form={{ handleSubmit: model.submit }}>
                <SpinnerOverlay visible={model.submitting} />
                <div className='mb-6'>
                    <TextField
                        field={model.fields.description}
                        label={t('Description')}
                        description={t('A description of this API key.')}
                    />
                </div>
                <TextAreaField
                    field={model.fields.allowedIps}
                    label={t('Allowed IPs')}
                    rows={6}
                    description={t(
                        'Leave blank to allow any IP address to use this API key, otherwise provide each IP address on a new line.'
                    )}
                />
                <div className='flex justify-end mt-6'>
                    <Button type='submit' disabled={!model.canSubmit} isLoading={model.submitting}>
                        {t('Create')}
                    </Button>
                </div>
            </Form>
        </ContentBox>
    );
}
