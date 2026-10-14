import { Button, ContentBox, Form, SpinnerOverlay } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';
import { TextAreaField, TextField } from '../shared/fields';

export default function SshKeyCreate({ model, className }: ViewProps<'account.sshKeys.create'>) {
    return (
        <ContentBox title='Add SSH Key' className={className}>
            <Form form={{ handleSubmit: model.submit }}>
                <SpinnerOverlay visible={model.submitting} />
                <div className='mb-6'>
                    <TextField field={model.fields.name} label='SSH Key Name' />
                </div>
                <TextAreaField
                    field={model.fields.publicKey}
                    label='Public Key'
                    rows={6}
                    description='Enter your public SSH key.'
                />
                <div className='flex justify-end mt-6'>
                    <Button type='submit' disabled={!model.canSubmit} isLoading={model.submitting}>
                        Save
                    </Button>
                </div>
            </Form>
        </ContentBox>
    );
}
