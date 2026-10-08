import React from 'react';
import { useStore } from '@tanstack/react-form';
import { useAppForm, Form } from '@/components/form';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { useUpdateAccountPassword } from '@/api/account/queries';

function UpdatePasswordForm() {
    const updatePassword = useUpdateAccountPassword();

    const form = useAppForm({
        defaultValues: { current: '', password: '', confirmPassword: '' },
        onSubmit: async ({ value }) => {
            try {
                await updatePassword.mutateAsync({
                    body: {
                        current_password: value.current,
                        password: value.password,
                        password_confirmation: value.confirmPassword,
                    },
                });
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    const isSubmitting = useStore(form.store, (state) => state.isSubmitting) || updatePassword.isPending;

    return (
        <>
            <SpinnerOverlay size='large' visible={isSubmitting} />
            <Form form={form} className='m-0'>
                <form.AppField
                    name='current'
                    validators={{
                        onChange: ({ value }) =>
                            value.length >= 1 ? undefined : 'You must provide your current password.',
                    }}
                >
                    {(field) => <field.TextField id='current_password' type='password' label='Current Password' />}
                </form.AppField>
                <div className='mt-6'>
                    <form.AppField
                        name='password'
                        validators={{
                            onChange: ({ value }) =>
                                value.length >= 8
                                    ? undefined
                                    : 'Your new password should be at least 8 characters in length.',
                        }}
                    >
                        {(field) => (
                            <field.TextField
                                id='new_password'
                                type='password'
                                label='New Password'
                                description='Your new password should be at least 8 characters in length and unique to this website.'
                            />
                        )}
                    </form.AppField>
                </div>
                <div className='mt-6'>
                    <form.AppField
                        name='confirmPassword'
                        validators={{
                            onChangeListenTo: ['password'],
                            onChange: ({ value, fieldApi }) =>
                                value === fieldApi.form.getFieldValue('password')
                                    ? undefined
                                    : 'Password confirmation does not match the password you entered.',
                        }}
                    >
                        {(field) => (
                            <field.TextField id='confirm_new_password' type='password' label='Confirm New Password' />
                        )}
                    </form.AppField>
                </div>
                <div className='mt-6'>
                    <form.AppForm>
                        <form.SubmitButton disabled={updatePassword.isPending}>Update Password</form.SubmitButton>
                    </form.AppForm>
                </div>
            </Form>
        </>
    );
}

export default UpdatePasswordForm;
