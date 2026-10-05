import { useStore } from '@tanstack/react-form';
import { useAppForm, Form } from '@/components/form';
import type { AdminEgg } from '@/api/admin/eggs/queries';
import { deleteAdminEggInput, useDeleteAdminEgg } from '@/api/admin/eggs/queries';
import { Dialog } from '@/components/elements/dialog';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Button from '@/components/elements/Button';

interface Props {
    egg: AdminEgg;
    onDeleted: () => Promise<void>;
}

type DeleteEggDialogProps = Props & {
    open: boolean;
    onClose: () => void;
};

// The API rejects deleting an egg that servers or child eggs still use.
function DeleteEggDialog({ egg, onDeleted, open, onClose }: DeleteEggDialogProps) {
    const deleteEgg = useDeleteAdminEgg();

    const form = useAppForm({
        defaultValues: { confirm: '' },
        onSubmit: async () => {
            try {
                await deleteEgg.mutateAsync(deleteAdminEggInput(egg.attributes.id, egg.attributes.name));
                await onDeleted();
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
            title={'Confirm egg deletion'}
            preventExternalClose={isSubmitting}
            hideCloseIcon={isSubmitting}
            onClose={() => {
                onClose();
                form.reset();
            }}
        >
            <SpinnerOverlay visible={isSubmitting} />
            <p className={'text-sm'}>
                Deleting an egg is a permanent action, it cannot be undone. This will permanently delete the{' '}
                <strong>{egg.attributes.name}</strong> egg. An egg with attached servers cannot be deleted.
            </p>
            <Form form={form} className={'m-0 mt-6'}>
                <form.AppField
                    name={'confirm'}
                    validators={{
                        onChange: ({ value }) =>
                            value === egg.attributes.name ? undefined : 'The egg name must be provided.',
                    }}
                >
                    {(field) => (
                        <field.TextField
                            type={'text'}
                            id={'confirm_egg_name'}
                            label={'Confirm Name'}
                            description={'Enter the name of this egg to confirm deletion.'}
                        />
                    )}
                </form.AppField>
                <div className={'mt-6 text-right'}>
                    <Button type={'button'} isSecondary className={'mr-2'} onClick={onClose}>
                        Cancel
                    </Button>
                    <form.AppForm>
                        <form.SubmitButton color={'red'}>Delete Egg</form.SubmitButton>
                    </form.AppForm>
                </div>
            </Form>
        </Dialog>
    );
}

export default function DeleteEggButton({ egg, onDeleted }: Props) {
    return (
        <Dialog.Trigger
            trigger={({ onClick }) => (
                <Button color={'red'} isSecondary onClick={onClick}>
                    Delete Egg
                </Button>
            )}
        >
            {({ open, onClose }) => <DeleteEggDialog egg={egg} onDeleted={onDeleted} open={open} onClose={onClose} />}
        </Dialog.Trigger>
    );
}
