import { Card, Form, SpinnerOverlay, useAppForm, useTranslation } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';

export default function ServerRename({ model, className }: ViewProps<'server.rename'>) {
    const { t } = useTranslation();
    const form = useAppForm({
        defaultValues: { ...model.values },
        onSubmit: ({ value }) => model.save(value),
    });

    if (!model.allowed) {
        return null;
    }

    return (
        <div className={className}>
            <Card title={t('Change Server Details')} className='relative'>
                <form.Subscribe selector={(state) => state.isSubmitting}>
                    {(submitting) => <SpinnerOverlay visible={submitting} />}
                </form.Subscribe>
                <Form form={form} className='mb-0'>
                    <form.AppField name='name' validators={{ onChange: ({ value }) => model.validateName(value) }}>
                        {(field) => <field.TextField id='name' label={t('Server Name')} type='text' />}
                    </form.AppField>
                    <div className='mt-6'>
                        <form.AppField name='description'>
                            {(field) => <field.TextAreaField label={t('Server Description')} rows={3} />}
                        </form.AppField>
                    </div>
                    <div className='mt-6 text-right'>
                        <form.AppForm>
                            <form.SubmitButton>{t('Save')}</form.SubmitButton>
                        </form.AppForm>
                    </div>
                </Form>
            </Card>
        </div>
    );
}
