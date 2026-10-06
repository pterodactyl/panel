import { newExtensionFieldValues } from '@/extensions/useExtensionFormFields';
import { useStore } from '@tanstack/react-form';
import { useAppForm, Form } from '@/components/form';
import { createAdminLocationInput, useCreateAdminLocation } from '@/api/admin/locations/queries';
import { Dialog } from '@/components/elements/dialog';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Button from '@/components/elements/Button';
import { NewButton } from '@/components/elements/NewButton';
import Slot from '@/extensions/Slot';
import type { LocationValues } from '@/api/admin/locations/queries';

type CreateLocationDialogProps = {
    open: boolean;
    onClose: () => void;
};

function CreateLocationDialog({ open, onClose }: CreateLocationDialogProps) {
    const createLocation = useCreateAdminLocation();

    const defaultValues: LocationValues = {
        short: '',
        long: '',
        extensions: newExtensionFieldValues('admin.location'),
    };
    const form = useAppForm({
        defaultValues,
        onSubmit: async ({ value }) => {
            try {
                await createLocation.mutateAsync(createAdminLocationInput(value));
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
            title={'Create new location'}
            preventExternalClose={isSubmitting}
            hideCloseIcon={isSubmitting}
            onClose={onClose}
        >
            <SpinnerOverlay visible={isSubmitting} />
            <Form form={form} className={'m-0'}>
                <form.AppField
                    name={'short'}
                    validators={{
                        onChange: ({ value }) =>
                            value.length < 1
                                ? 'A short code must be provided.'
                                : value.length <= 60
                                  ? undefined
                                  : 'A short code must not exceed 60 characters.',
                    }}
                >
                    {(field) => (
                        <field.TextField
                            type={'text'}
                            id={'short'}
                            label={'Short Code'}
                            description={'A short identifier used to distinguish this location from others.'}
                        />
                    )}
                </form.AppField>
                <div className={'mt-6'}>
                    <form.AppField
                        name={'long'}
                        validators={{
                            onChange: ({ value }) =>
                                value.length <= 191 ? undefined : 'The description must not exceed 191 characters.',
                        }}
                    >
                        {(field) => (
                            <field.TextField
                                type={'text'}
                                id={'long'}
                                label={'Description'}
                                description={'A longer description of this location.'}
                            />
                        )}
                    </form.AppField>
                </div>
                <Slot name={'panel.locations.create.form'} data={{ kind: 'admin.location', mode: 'create', form }} />
                <div className={'flex flex-wrap justify-end mt-6'}>
                    <Button type={'button'} isSecondary className={'w-full sm:w-auto sm:mr-2'} onClick={onClose}>
                        Cancel
                    </Button>
                    <form.AppForm>
                        <form.SubmitButton className={'w-full mt-4 sm:w-auto sm:mt-0'}>
                            Create Location
                        </form.SubmitButton>
                    </form.AppForm>
                </div>
            </Form>
        </Dialog>
    );
}

export default function CreateLocationButton() {
    return (
        <Dialog.Trigger trigger={({ onClick }) => <NewButton onClick={onClick}>New location</NewButton>}>
            {({ open, onClose }) => <CreateLocationDialog open={open} onClose={onClose} />}
        </Dialog.Trigger>
    );
}
