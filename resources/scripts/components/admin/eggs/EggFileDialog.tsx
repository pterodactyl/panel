import { useId, useState } from 'react';
import { Dialog } from '@/components/elements/dialog';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Label from '@/components/elements/Label';
import Button from '@/components/elements/Button';
import { FileInput } from '@/components/form/controls';

interface Props {
    open: boolean;
    onClose: () => void;
    title: string;
    description: string;
    submitLabel: string;
    submitColor?: 'primary' | 'red';
    submitting: boolean;
    onSubmit: (file: File) => Promise<void>;
}

export default function EggFileDialog({
    open,
    onClose,
    title,
    description,
    submitLabel,
    submitColor = 'primary',
    submitting,
    onSubmit,
}: Props) {
    const fileId = useId();
    const [file, setFile] = useState<File | null>(null);

    const submit = async () => {
        if (!file || submitting) {
            return;
        }

        try {
            await onSubmit(file);
            onClose();
        } catch {
            // Error toast is handled by the mutation.
        }
    };

    return (
        <Dialog
            open={open}
            title={title}
            description={description}
            preventExternalClose={submitting}
            hideCloseIcon={submitting}
            onClose={onClose}
        >
            <SpinnerOverlay visible={submitting} />
            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    void submit();
                }}
            >
                <Label htmlFor={fileId}>Egg File</Label>
                <FileInput
                    id={fileId}
                    accept='application/json,.json'
                    disabled={submitting}
                    onChange={(event) => setFile(event.currentTarget.files?.[0] ?? null)}
                />
                <div className='mt-6 flex flex-wrap justify-end gap-3'>
                    <Button
                        type='button'
                        isSecondary
                        className='w-full sm:w-auto'
                        disabled={submitting}
                        onClick={onClose}
                    >
                        Cancel
                    </Button>
                    <Button
                        type='submit'
                        color={submitColor}
                        className='w-full sm:w-auto'
                        disabled={!file || submitting}
                        isLoading={submitting}
                    >
                        {submitLabel}
                    </Button>
                </div>
            </form>
        </Dialog>
    );
}
