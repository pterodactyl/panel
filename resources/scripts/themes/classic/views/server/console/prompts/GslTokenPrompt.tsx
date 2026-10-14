import { Dialog, Form, SpinnerOverlay, useAppForm } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';

export default function GslTokenPrompt({ model }: ViewProps<'server.feature.gslToken'>) {
    const form = useAppForm({
        defaultValues: { gslToken: '' },
        onSubmit: async ({ value }) => {
            // The failure has already been reported.
            await model.submit(value.gslToken).catch(() => {});
        },
    });

    return (
        <Dialog
            open={model.open}
            title='Invalid GSL token'
            onClose={model.close}
            preventExternalClose={model.pending}
            hideCloseIcon={model.pending}
        >
            <SpinnerOverlay visible={model.pending} />
            <Form form={form}>
                <p className='mt-4'>It seems like your Gameserver Login Token (GSL token) is invalid or has expired.</p>
                <p className='mt-4'>
                    You can either generate a new one and enter it below or leave the field blank to remove it
                    completely.
                </p>
                <div className='sm:flex items-center mt-4'>
                    <form.AppField name='gslToken'>
                        {(field) => (
                            <field.TextField
                                label='GSL Token'
                                description='Visit https://steamcommunity.com/dev/managegameservers to generate a token.'
                                autoFocus
                            />
                        )}
                    </form.AppField>
                </div>
                <div className='mt-8 sm:flex items-center justify-end'>
                    <div className='mt-4 sm:mt-0 sm:ml-4 w-full sm:w-auto'>
                        <form.AppForm>
                            <form.SubmitButton className='w-full sm:w-auto'>Update GSL Token</form.SubmitButton>
                        </form.AppForm>
                    </div>
                </div>
            </Form>
        </Dialog>
    );
}
