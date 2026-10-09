import { NewButton } from '@/components/elements/NewButton';
import { useRef } from 'react';
import useEventListener from '@/plugins/useEventListener';
import type { WithClassname } from '@/components/types';
import Portal from '@/components/elements/Portal';
import { CloudUpload } from 'lucide-react';
import { useDialogState } from '@/components/elements/dialog';
import useFileUploader from '@/components/server/files/useFileUploader';

function isFileOrDirectory(event: DragEvent): boolean {
    if (!event.dataTransfer?.types) {
        return false;
    }

    return event.dataTransfer.types.some((value) => value.toLowerCase() === 'files');
}

export default function UploadButton({ className }: WithClassname) {
    const fileUploadInput = useRef<HTMLInputElement>(null);

    const dropOverlay = useDialogState();
    const upload = useFileUploader();

    useEventListener(
        'dragenter',
        (e: DragEvent) => {
            e.preventDefault();
            e.stopPropagation();
            if (isFileOrDirectory(e)) {
                dropOverlay.show();
            }
        },
        { capture: true }
    );

    useEventListener('dragexit', dropOverlay.hide, { capture: true });

    useEventListener('keydown', dropOverlay.hide);

    const onFileSubmission = (files: FileList) => {
        // Failures have already been reported with error toasts.
        upload([...files]).catch(() => {});
    };

    return (
        <>
            <Portal>
                {dropOverlay.open && (
                    <div
                        className='fixed z-50 overflow-auto flex w-full inset-0 bg-background/70'
                        onDragOver={(e) => e.preventDefault()}
                        onDrop={(e) => {
                            e.preventDefault();
                            e.stopPropagation();

                            dropOverlay.hide();
                            if (!e.dataTransfer?.files.length) {
                                return;
                            }

                            onFileSubmission(e.dataTransfer.files);
                        }}
                    >
                        <div className='w-full flex items-center justify-center pointer-events-none'>
                            <div className='flex items-center space-x-4 bg-muted w-full ring-4 ring-ring/50 rounded-sm p-6 mx-10 max-w-sm'>
                                <CloudUpload className='w-10 h-10 shrink-0' />
                                <p className='font-header flex-1 text-lg text-foreground text-center'>
                                    Drag and drop files to upload.
                                </p>
                            </div>
                        </div>
                    </div>
                )}
            </Portal>
            <input
                type='file'
                ref={fileUploadInput}
                className='hidden'
                aria-label='Upload files'
                onChange={(e) => {
                    if (!e.currentTarget.files) {
                        return;
                    }

                    onFileSubmission(e.currentTarget.files);
                    e.currentTarget.value = '';
                }}
                multiple
            />
            <NewButton
                icon={CloudUpload}
                className={className}
                onClick={() => fileUploadInput.current && fileUploadInput.current.click()}
            >
                Upload
            </NewButton>
        </>
    );
}
