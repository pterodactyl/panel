import React from 'react';
import { useStore } from '@tanstack/react-form';
import { useAppForm, Form } from '@/components/form';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { useCurrentUser, useUpdateAccountEmail } from '@/api/account/queries';

const isEmail = (value: string) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);

function UpdateEmailAddressForm() {
    const user = useCurrentUser();
    const updateEmail = useUpdateAccountEmail();

    const form = useAppForm({
        defaultValues: { email: user.email, password: '' },
        onSubmit: async ({ value, formApi }) => {
            try {
                await updateEmail.mutateAsync({ body: value });
                formApi.setFieldValue('password', '');
            } catch {
                // Error toast is handled by the mutation.
            } finally {
                formApi.setFieldMeta('password', (meta) => ({ ...meta, errors: [] }));
            }
        },
    });

    const isSubmitting = useStore(form.store, (state) => state.isSubmitting) || updateEmail.isPending;

    return (
        <React.Fragment>
            <SpinnerOverlay size={'large'} visible={isSubmitting} />
            <Form form={form} className={'m-0'}>
                <form.AppField
                    name={'email'}
                    validators={{
                        onChange: ({ value }) =>
                            isEmail(value) ? undefined : 'A valid email address must be provided.',
                    }}
                >
                    {(field) => <field.TextField id={'current_email'} type={'email'} label={'Email'} />}
                </form.AppField>
                <div className={'mt-6'}>
                    <form.AppField
                        name={'password'}
                        validators={{
                            onChange: ({ value }) =>
                                value.length >= 1 ? undefined : 'You must provide your current account password.',
                        }}
                    >
                        {(field) => (
                            <field.TextField id={'confirm_password'} type={'password'} label={'Confirm Password'} />
                        )}
                    </form.AppField>
                </div>
                <div className={'mt-6'}>
                    <form.AppForm>
                        <form.SubmitButton disabled={updateEmail.isPending}>Update Email</form.SubmitButton>
                    </form.AppForm>
                </div>
            </Form>
        </React.Fragment>
    );
}

export default UpdateEmailAddressForm;
