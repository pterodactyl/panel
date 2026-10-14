import { Button, ContentBox, CopyOnClick, Dialog, Form, SpinnerOverlay } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';
import { TextAreaField, TextField } from '../shared/fields';

function CreatedKeyDialog({ apiKey, onClose }: { apiKey: string | null; onClose: () => void }) {
    return (
        <Dialog open={apiKey !== null} onClose={onClose} preventExternalClose title='Your API key'>
            <p className='text-sm mb-6'>
                The API key you have requested is shown below. Please store this in a safe location, it will not be
                shown again.
            </p>
            <pre className='overflow-x-scroll text-sm bg-muted rounded-sm py-2 px-4 font-mono'>
                <CopyOnClick text={apiKey ?? ''}>
                    <code className='font-mono'>{apiKey}</code>
                </CopyOnClick>
            </pre>
            <div className='flex justify-end mt-6'>
                <Button type='button' onClick={onClose}>
                    Close
                </Button>
            </div>
        </Dialog>
    );
}

export default function ApiKeyCreate({ model, className }: ViewProps<'account.apiKeys.create'>) {
    return (
        <ContentBox title='Create API Key' className={className}>
            <CreatedKeyDialog apiKey={model.createdKey} onClose={model.dismissCreatedKey} />
            <Form form={{ handleSubmit: model.submit }}>
                <SpinnerOverlay visible={model.submitting} />
                <div className='mb-6'>
                    <TextField
                        field={model.fields.description}
                        label='Description'
                        description='A description of this API key.'
                    />
                </div>
                <TextAreaField
                    field={model.fields.allowedIps}
                    label='Allowed IPs'
                    rows={6}
                    description='Leave blank to allow any IP address to use this API key, otherwise provide each IP address on a new line.'
                />
                <div className='flex justify-end mt-6'>
                    <Button type='submit' disabled={!model.canSubmit} isLoading={model.submitting}>
                        Create
                    </Button>
                </div>
            </Form>
        </ContentBox>
    );
}
