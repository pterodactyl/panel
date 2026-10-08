import { useStore } from '@tanstack/react-form';
import { useAppForm, Form } from '@/components/form';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { useCreateSSHKey } from '@/api/account/ssh-keys/queries';

export default function CreateSSHKeyForm() {
    const createSshKey = useCreateSSHKey();

    const form = useAppForm({
        defaultValues: { name: '', publicKey: '' },
        onSubmit: async ({ value, formApi }) => {
            try {
                await createSshKey.mutateAsync({
                    body: {
                        name: value.name,
                        public_key: value.publicKey,
                    },
                });
                formApi.reset();
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    const isSubmitting = useStore(form.store, (state) => state.isSubmitting);

    return (
        <Form form={form}>
            <SpinnerOverlay visible={isSubmitting} />
            <div className='mb-6'>
                <form.AppField
                    name='name'
                    validators={{
                        onChange: ({ value }) => (value.length >= 1 ? undefined : 'A key name must be provided.'),
                    }}
                >
                    {(field) => <field.TextField label='SSH Key Name' />}
                </form.AppField>
            </div>
            <form.AppField
                name='publicKey'
                validators={{
                    onChange: ({ value }) => (value.length >= 1 ? undefined : 'A public key must be provided.'),
                }}
            >
                {(field) => (
                    <field.TextAreaField label='Public Key' rows={6} description='Enter your public SSH key.' />
                )}
            </form.AppField>
            <div className='flex justify-end mt-6'>
                <form.AppForm>
                    <form.SubmitButton>Save</form.SubmitButton>
                </form.AppForm>
            </div>
        </Form>
    );
}
