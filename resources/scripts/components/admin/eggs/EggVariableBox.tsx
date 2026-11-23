import { useAppForm, Form } from '@/components/form';
import type { AdminEggVariable } from '@/api/admin/eggs/queries';
import {
    deleteAdminEggVariableInput,
    updateAdminEggVariableInput,
    useDeleteAdminEggVariable,
    useUpdateAdminEggVariable,
} from '@/api/admin/eggs/queries';
import {
    eggVariableBodyFromFormValues,
    type EggVariableValues,
    validateEnvVariable,
    validateVariableName,
} from '@/components/admin/eggs/helpers';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Button from '@/components/elements/Button';
import Code from '@/components/elements/Code';
import { Dialog } from '@/components/elements/dialog';
import Translate from '@/components/elements/Translate';
import { useTranslation } from '@/sdk/localization';

interface Props {
    eggId: number;
    variable: AdminEggVariable;
}

const variableToFormValues = (variable: AdminEggVariable): EggVariableValues => ({
    name: variable.attributes.name,
    description: variable.attributes.description,
    envVariable: variable.attributes.env_variable,
    defaultValue: variable.attributes.default_value,
    userViewable: variable.attributes.user_viewable,
    userEditable: variable.attributes.user_editable,
    rules: variable.attributes.rules,
});

export default function EggVariableBox({ eggId, variable }: Props) {
    const { t } = useTranslation();
    const updateEggVariable = useUpdateAdminEggVariable();
    const deleteEggVariable = useDeleteAdminEggVariable();

    const form = useAppForm({
        defaultValues: variableToFormValues(variable),
        onSubmit: async ({ value }) => {
            try {
                await updateEggVariable.mutateAsync(
                    updateAdminEggVariableInput(eggId, variable.attributes.id, eggVariableBodyFromFormValues(value))
                );
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    const confirmDelete = async () => {
        try {
            await deleteEggVariable.mutateAsync(
                deleteAdminEggVariableInput(eggId, variable.attributes.id, variable.attributes.name)
            );
        } catch {
            // Error toast is handled by the mutation.
        }
    };

    return (
        <Form form={form}>
            <TitledGreyBox title={variable.attributes.name}>
                <form.AppField
                    name='name'
                    validators={{
                        onChange: ({ value }) => validateVariableName(value),
                    }}
                >
                    {(field) => (
                        <field.TextField
                            type='text'
                            id={`name_${variable.attributes.id}`}
                            label={t('Name')}
                            description={t('A human-readable name for this variable.')}
                        />
                    )}
                </form.AppField>
                <div className='mt-4'>
                    <form.AppField name='description'>
                        {(field) => (
                            <field.TextAreaField
                                id={`description_${variable.attributes.id}`}
                                label={t('Description')}
                                rows={3}
                            />
                        )}
                    </form.AppField>
                </div>
                <div className='grid grid-cols-1 md:grid-cols-2 gap-4 mt-4'>
                    <form.AppField
                        name='envVariable'
                        validators={{
                            onChange: ({ value }) => validateEnvVariable(value),
                        }}
                    >
                        {(field) => (
                            <field.TextField
                                type='text'
                                id={`env_${variable.attributes.id}`}
                                label={t('Environment Variable')}
                            />
                        )}
                    </form.AppField>
                    <form.AppField name='defaultValue'>
                        {(field) => (
                            <field.TextField
                                type='text'
                                id={`default_${variable.attributes.id}`}
                                label={t('Default Value')}
                            />
                        )}
                    </form.AppField>
                </div>
                <p className='text-xs text-muted-foreground mt-2'>
                    <Translate
                        i18nKey='Access this variable in the startup command with <code>{{name}}</code>.'
                        values={{ name: variable.attributes.env_variable }}
                        components={{ code: <Code>{null}</Code> }}
                    />
                </p>
                <div className='grid grid-cols-1 md:grid-cols-2 gap-4 mt-4'>
                    <form.AppField name='userViewable'>
                        {(field) => <field.SwitchField label={t('Users Can View')} />}
                    </form.AppField>
                    <form.AppField name='userEditable'>
                        {(field) => <field.SwitchField label={t('Users Can Edit')} />}
                    </form.AppField>
                </div>
                <div className='mt-4'>
                    <form.AppField
                        name='rules'
                        validators={{
                            onChange: ({ value }) =>
                                value.length >= 1 ? undefined : t('Validation rules must be provided.'),
                        }}
                    >
                        {(field) => (
                            <field.TextField
                                type='text'
                                id={`rules_${variable.attributes.id}`}
                                label={t('Input Rules')}
                                description={t('Standard Laravel validation rules used to validate this variable.')}
                            />
                        )}
                    </form.AppField>
                </div>
                <div className='flex items-center justify-between mt-6'>
                    <Dialog.ConfirmTrigger
                        title={t('Delete {{name}}', { name: variable.attributes.name })}
                        confirm={t('Delete Variable')}
                        onConfirmed={confirmDelete}
                        trigger={({ onClick }) => (
                            <Button type='button' color='red' isSecondary onClick={onClick}>
                                {t('Delete')}
                            </Button>
                        )}
                    >
                        <Translate
                            i18nKey='This will permanently remove the <bold>{{name}}</bold> variable from this egg.'
                            values={{ name: variable.attributes.name }}
                            components={{ bold: <strong /> }}
                        />
                    </Dialog.ConfirmTrigger>
                    <form.AppForm>
                        <form.SubmitButton>{t('Save')}</form.SubmitButton>
                    </form.AppForm>
                </div>
            </TitledGreyBox>
        </Form>
    );
}
