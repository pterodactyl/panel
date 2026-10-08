import { useAppForm, Form } from '@/components/form';
import type { DialogProps } from '@/components/elements/dialog';
import { Dialog } from '@/components/elements/dialog';
import { useServerDirectory } from '@/state/server';
import { join } from 'pathe';

type Props = DialogProps & {
    onFileNamed: (name: string) => void;
};

export default function FileNameModal({ onFileNamed, onClose, ...props }: Props) {
    const directory = useServerDirectory();

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
