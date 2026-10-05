import { useAppForm, Form } from '@/components/form';
import type { AdminEggVariable } from '@/api/admin/eggs/queries';
import {
    deleteAdminEggVariableInput,
    updateAdminEggVariableInput,
    useDeleteAdminEggVariable,
    useUpdateAdminEggVariable,
} from '@/api/admin/eggs/queries';
import { eggVariableBodyFromFormValues, type EggVariableValues } from '@/components/admin/eggs/helpers';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Button from '@/components/elements/Button';
import Code from '@/components/elements/Code';
import { Dialog } from '@/components/elements/dialog';

interface Props {
    eggId: number;
    variable: AdminEggVariable;
}

const envVariableRegex = /^[\w]{1,191}$/;

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
                    name={'name'}
                    validators={{
                        onChange: ({ value }) =>
                            value.length < 1
                                ? 'A variable name must be provided.'
                                : value.length > 191
                                  ? 'A variable name must not exceed 191 characters.'
                                  : undefined,
                    }}
                >
                    {(field) => (
                        <field.TextField
                            type={'text'}
                            id={`name_${variable.attributes.id}`}
                            label={'Name'}
                            description={'A human-readable name for this variable.'}
                        />
                    )}
                </form.AppField>
                <div className={'mt-4'}>
                    <form.AppField name={'description'}>
                        {(field) => (
                            <field.TextAreaField
                                id={`description_${variable.attributes.id}`}
                                label={'Description'}
                                rows={3}
                            />
                        )}
                    </form.AppField>
                </div>
                <div className={'grid grid-cols-1 md:grid-cols-2 gap-4 mt-4'}>
                    <form.AppField
                        name={'envVariable'}
                        validators={{
                            onChange: ({ value }) =>
                                value.length < 1
                                    ? 'An environment variable must be provided.'
                                    : !envVariableRegex.test(value)
                                      ? 'The environment variable may only contain letters, numbers, and underscores.'
                                      : undefined,
                        }}
                    >
                        {(field) => (
                            <field.TextField
                                type={'text'}
                                id={`env_${variable.attributes.id}`}
                                label={'Environment Variable'}
                            />
                        )}
                    </form.AppField>
                    <form.AppField name={'defaultValue'}>
                        {(field) => (
                            <field.TextField
                                type={'text'}
                                id={`default_${variable.attributes.id}`}
                                label={'Default Value'}
                            />
                        )}
                    </form.AppField>
                </div>
                <p className={'text-xs text-muted-foreground mt-2'}>
                    Access this variable in the startup command with <Code>{variable.attributes.env_variable}</Code>.
                </p>
                <div className={'grid grid-cols-1 md:grid-cols-2 gap-4 mt-4'}>
                    <form.AppField name={'userViewable'}>
                        {(field) => <field.SwitchField label={'Users Can View'} />}
                    </form.AppField>
                    <form.AppField name={'userEditable'}>
                        {(field) => <field.SwitchField label={'Users Can Edit'} />}
                    </form.AppField>
                </div>
                <div className={'mt-4'}>
                    <form.AppField
                        name={'rules'}
                        validators={{
                            onChange: ({ value }) =>
                                value.length >= 1 ? undefined : 'Validation rules must be provided.',
                        }}
                    >
                        {(field) => (
                            <field.TextField
                                type={'text'}
                                id={`rules_${variable.attributes.id}`}
                                label={'Input Rules'}
                                description={'Standard Laravel validation rules used to validate this variable.'}
                            />
                        )}
                    </form.AppField>
                </div>
                <div className={'flex items-center justify-between mt-6'}>
                    <Dialog.ConfirmTrigger
                        title={`Delete ${variable.attributes.name}`}
                        confirm={'Delete Variable'}
                        onConfirmed={confirmDelete}
                        trigger={({ onClick }) => (
                            <Button type={'button'} color={'red'} isSecondary onClick={onClick}>
                                Delete
                            </Button>
                        )}
                    >
                        This will permanently remove the <strong>{variable.attributes.name}</strong> variable from this
                        egg.
                    </Dialog.ConfirmTrigger>
                    <form.AppForm>
                        <form.SubmitButton>Save</form.SubmitButton>
                    </form.AppForm>
                </div>
            </TitledGreyBox>
        </Form>
    );
}
