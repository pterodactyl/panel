import { useAppForm, Form } from '@/components/form';
import type { DialogProps } from '@/components/elements/dialog';
import { Dialog } from '@/components/elements/dialog';
import { join } from 'pathe';

type Props = DialogProps & {
    /** The directory the new file is written to. */
    directory: string;
    onFileNamed: (name: string) => void;
};

/** Names a new file before the editor first writes it. */
export default function FileNameModal({ directory, onFileNamed, onClose, ...props }: Props) {
    const form = useAppForm({
        defaultValues: { fileName: '' },
        onSubmit: async ({ value }) => {
            onFileNamed(join(directory, value.fileName));
        },
    });

    return (
        <Dialog
            onClose={() => {
                form.reset();
                onClose();
            }}
            {...props}
        >
            <Form form={form}>
                <form.AppField
                    name='fileName'
                    validators={{ onChange: ({ value }) => (value.length >= 1 ? undefined : 'Required') }}
                >
                    {(field) => (
                        <field.TextField
                            id='fileName'
                            label='File Name'
                            description='Enter the name that this file should be saved as.'
                            autoFocus
                        />
                    )}
                </form.AppField>
                <div className='mt-6 text-right'>
                    <form.AppForm>
                        <form.SubmitButton>Create File</form.SubmitButton>
                    </form.AppForm>
                </div>
            </Form>
        </Dialog>
    );
}
