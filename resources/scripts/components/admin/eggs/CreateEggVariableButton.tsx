import { useStore } from '@tanstack/react-form';
import { useAppForm, Form } from '@/components/form';
import { createAdminEggVariableInput, useCreateAdminEggVariable } from '@/api/admin/eggs/queries';
import { eggVariableBodyFromFormValues } from '@/components/admin/eggs/helpers';
import { Dialog } from '@/components/elements/dialog';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Button from '@/components/elements/Button';
import { NewButton } from '@/components/elements/NewButton';

interface Props {
    eggId: number;
}

type CreateEggVariableDialogProps = Props & {
    open: boolean;
    onClose: () => void;
};

const envVariableRegex = /^[\w]{1,191}$/;

const initialValues = {
    name: '',
    description: '',
    envVariable: '',
    defaultValue: '',
    userViewable: true,
    userEditable: true,
    rules: 'required|string|max:20',
};

const validateVariableName = (value: string): string | undefined => {
    if (value.length < 1) {
        return 'A variable name must be provided.';
    }

    if (value.length > 191) {
        return 'A variable name must not exceed 191 characters.';
    }

    return undefined;
};

const validateEnvVariable = (value: string): string | undefined => {
    if (value.length < 1) {
        return 'An environment variable must be provided.';
    }

    if (!envVariableRegex.test(value)) {
        return 'The environment variable may only contain letters, numbers, and underscores.';
    }

    return undefined;
};

function CreateEggVariableDialog({ eggId, open, onClose }: CreateEggVariableDialogProps) {
    const createEggVariable = useCreateAdminEggVariable();

    const form = useAppForm({
        defaultValues: initialValues,
        onSubmit: async ({ value }) => {
            try {
                await createEggVariable.mutateAsync(
                    createAdminEggVariableInput(eggId, eggVariableBodyFromFormValues(value))
                );
                onClose();
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    const isSubmitting = useStore(form.store, (state) => state.isSubmitting);

    return (
        <Dialog
            open={open}
            title='Create new egg variable'
            preventExternalClose={isSubmitting}
            hideCloseIcon={isSubmitting}
            onClose={onClose}
        >
            <SpinnerOverlay visible={isSubmitting} />
            <Form form={form} className='m-0'>
                <form.AppField
                    name='name'
                    validators={{
                        onChange: ({ value }) => validateVariableName(value),
                    }}
                >
                    {(field) => <field.TextField type='text' id='create_name' label='Name' />}
                </form.AppField>
                <div className='mt-6'>
                    <form.AppField name='description'>
                        {(field) => <field.TextAreaField id='create_description' label='Description' rows={3} />}
                    </form.AppField>
                </div>
                <div className='grid grid-cols-1 md:grid-cols-2 gap-4 mt-6'>
                    <form.AppField
                        name='envVariable'
                        validators={{
                            onChange: ({ value }) => validateEnvVariable(value),
                        }}
                    >
                        {(field) => <field.TextField type='text' id='create_env' label='Environment Variable' />}
                    </form.AppField>
                    <form.AppField name='defaultValue'>
                        {(field) => <field.TextField type='text' id='create_default' label='Default Value' />}
                    </form.AppField>
                </div>
                <div className='grid grid-cols-1 md:grid-cols-2 gap-4 mt-6'>
                    <form.AppField name='userViewable'>
                        {(field) => <field.SwitchField label='Users Can View' />}
                    </form.AppField>
                    <form.AppField name='userEditable'>
                        {(field) => <field.SwitchField label='Users Can Edit' />}
                    </form.AppField>
                </div>
                <div className='mt-6'>
                    <form.AppField
                        name='rules'
                        validators={{
                            onChange: ({ value }) =>
                                value.length >= 1 ? undefined : 'Validation rules must be provided.',
                        }}
                    >
                        {(field) => (
                            <field.TextField
                                type='text'
                                id='create_rules'
                                label='Input Rules'
                                description='Standard Laravel validation rules used to validate this variable.'
                            />
                        )}
                    </form.AppField>
                </div>
                <div className='flex flex-wrap justify-end mt-6'>
                    <Button type='button' isSecondary className='w-full sm:w-auto sm:mr-2' onClick={onClose}>
                        Cancel
                    </Button>
                    <form.AppForm>
                        <form.SubmitButton className='w-full mt-4 sm:w-auto sm:mt-0'>Create Variable</form.SubmitButton>
                    </form.AppForm>
                </div>
            </Form>
        </Dialog>
    );
}

export default function CreateEggVariableButton(props: Props) {
    return (
        <Dialog.Trigger trigger={({ onClick }) => <NewButton onClick={onClick}>New variable</NewButton>}>
            {({ open, onClose }) => <CreateEggVariableDialog {...props} open={open} onClose={onClose} />}
        </Dialog.Trigger>
    );
}
