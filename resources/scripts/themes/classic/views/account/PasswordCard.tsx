import { Button, ContentBox, Form, SpinnerOverlay } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';
import { PasswordField, TextField } from '../shared/fields';

export default function PasswordCard({ model, className }: ViewProps<'account.password'>) {
    const { fields } = model;

    return (
        <ContentBox className={className} title='Update Password'>
            <SpinnerOverlay size='large' visible={model.submitting} />
            <Form form={{ handleSubmit: model.submit }} className='m-0'>
                <TextField field={fields.current} id='current_password' label='Current Password' />
                <div className='mt-6'>
                    <PasswordField
                        field={fields.password}
                        id='new_password'
                        label='New Password'
                        description='Your new password should be at least 8 characters in length and unique to this website.'
                    />
                </div>
                <div className='mt-6'>
                    <TextField field={fields.confirmPassword} id='confirm_new_password' label='Confirm New Password' />
                </div>
                <div className='mt-6'>
                    <Button type='submit' disabled={!model.canSubmit} isLoading={model.submitting}>
                        Update Password
                    </Button>
                </div>
            </Form>
        </ContentBox>
    );
}
