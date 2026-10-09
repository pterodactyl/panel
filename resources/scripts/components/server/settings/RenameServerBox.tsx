import { useStore } from '@tanstack/react-form';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { useAppForm, Form } from '@/components/form';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { useCurrentServer, useRenameServer } from '@/api/server/queries';

const RenameServerBox = () => {
    const server = useCurrentServer()!;
    const renameServer = useRenameServer(server.attributes.identifier);

    const form = useAppForm({
        defaultValues: {
            name: server.attributes.name,
            description: server.attributes.description ?? '',
        },
        onSubmit: async ({ value }) => {
            try {
                await renameServer.mutateAsync({
                    path: { server_uuid: server.attributes.uuid },
                    body: {
                        name: value.name,
                        description: value.description,
                    },
                });
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    const isSubmitting = useStore(form.store, (state) => state.isSubmitting);

    return (
        <TitledGreyBox title='Change Server Details' className='relative'>
            <SpinnerOverlay visible={isSubmitting} />
            <Form form={form} className='mb-0'>
                <form.AppField
                    name='name'
                    validators={{ onChange: ({ value }) => (value.length >= 1 ? undefined : 'Required') }}
                >
                    {(field) => <field.TextField id='name' label='Server Name' type='text' />}
                </form.AppField>
                <div className='mt-6'>
                    <form.AppField name='description'>
                        {(field) => <field.TextAreaField label='Server Description' rows={3} />}
                    </form.AppField>
                </div>
                <div className='mt-6 text-right'>
                    <form.AppForm>
                        <form.SubmitButton>Save</form.SubmitButton>
                    </form.AppForm>
                </div>
            </Form>
        </TitledGreyBox>
    );
};

export default RenameServerBox;
