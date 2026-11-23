import { Dialog, Form, SpinnerOverlay, useAppForm, useTranslation } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';

export default function GslTokenPrompt({ model }: ViewProps<'server.feature.gslToken'>) {
    const { t } = useTranslation();
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
            title={t('Invalid GSL token')}
            onClose={model.close}
            preventExternalClose={model.pending}
            hideCloseIcon={model.pending}
        >
            <SpinnerOverlay visible={model.pending} />
            <Form form={form}>
                <p className='mt-4'>
                    {t('It seems like your Gameserver Login Token (GSL token) is invalid or has expired.')}
                </p>
                <p className='mt-4'>
                    {t(
                        'You can either generate a new one and enter it below or leave the field blank to remove it completely.'
                    )}
                </p>
                <div className='sm:flex items-center mt-4'>
                    <form.AppField name='gslToken'>
                        {(field) => (
                            <field.TextField
                                label={t('GSL Token')}
                                description={t('Visit {{url}} to generate a token.', {
                                    url: 'https://steamcommunity.com/dev/managegameservers',
                                })}
                                autoFocus
                            />
                        )}
                    </form.AppField>
                </div>
                <div className='mt-8 sm:flex items-center justify-end'>
                    <div className='mt-4 sm:mt-0 sm:ml-4 w-full sm:w-auto'>
                        <form.AppForm>
                            <form.SubmitButton className='w-full sm:w-auto'>{t('Update GSL Token')}</form.SubmitButton>
                        </form.AppForm>
                    </div>
                </div>
            </Form>
        </Dialog>
    );
}
