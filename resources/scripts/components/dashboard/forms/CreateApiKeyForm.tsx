import { useState } from 'react';
import { useStore } from '@tanstack/react-form';
import { useAppForm, Form } from '@/components/form';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import ApiKeyModal from '@/components/dashboard/ApiKeyModal';
import { useCreateAccountApiKey } from '@/api/account/api-keys/queries';

function CreateApiKeyForm() {
    const [apiKey, setApiKey] = useState('');
    const createApiKey = useCreateAccountApiKey();

    const form = useAppForm({
        defaultValues: { description: '', allowedIps: '' },
        onSubmit: async ({ value, formApi }) => {
            try {
                const created = await createApiKey.mutateAsync({
                    body: {
                        description: value.description,
                        allowed_ips: value.allowedIps.length > 0 ? value.allowedIps.split('\n') : [],
                    },
                });
                formApi.reset();
                setApiKey(`${created.attributes.identifier}${created.meta?.secret_token ?? ''}`);
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    const isSubmitting = useStore(form.store, (state) => state.isSubmitting);

    return (
        <>
            <ApiKeyModal open={apiKey.length > 0} onClose={() => setApiKey('')} apiKey={apiKey} />
            <Form form={form}>
                <SpinnerOverlay visible={isSubmitting} />
                <div className={'mb-6'}>
                    <form.AppField
                        name={'description'}
                        validators={{
                            onChange: ({ value }) =>
                                value.length >= 4
                                    ? undefined
                                    : 'A description of at least 4 characters must be provided.',
                        }}
                    >
                        {(field) => (
                            <field.TextField label={'Description'} description={'A description of this API key.'} />
                        )}
                    </form.AppField>
                </div>
                <form.AppField name={'allowedIps'}>
                    {(field) => (
                        <field.TextAreaField
                            label={'Allowed IPs'}
                            rows={6}
                            description={
                                'Leave blank to allow any IP address to use this API key, otherwise provide each IP address on a new line.'
                            }
                        />
                    )}
                </form.AppField>
                <div className={'flex justify-end mt-6'}>
                    <form.AppForm>
                        <form.SubmitButton>Create</form.SubmitButton>
                    </form.AppForm>
                </div>
            </Form>
        </>
    );
}

export default CreateApiKeyForm;
