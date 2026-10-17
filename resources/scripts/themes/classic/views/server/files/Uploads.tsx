import { useEffect, useRef, useState } from 'react';
import { CloudUpload, X } from 'lucide-react';
import { toast } from 'sonner';
import { Button, Code, Dialog, NewButton, Portal, Tooltip } from '@/sdk';
import type { FileManagerModel } from '@/sdk/theme';

const isFileOrDirectory = (event: DragEvent): boolean =>
    event.dataTransfer?.types.some((value) => value.toLowerCase() === 'files') ?? false;

/** Shows the drop target while files are dragged over the window. */
function useDropOverlay(): [boolean, (open: boolean) => void] {
    const [open, setOpen] = useState(false);

    useEffect(() => {
        const enter = (event: DragEvent) => {
            event.preventDefault();
            event.stopPropagation();
            if (isFileOrDirectory(event)) {
                setOpen(true);
            }
        };

        const hide = () => setOpen(false);

        window.addEventListener('dragenter', enter, { capture: true });
        window.addEventListener('dragexit', hide, { capture: true });
        window.addEventListener('keydown', hide);

        return () => {
            window.removeEventListener('dragenter', enter, true);
            window.removeEventListener('dragexit', hide, true);
            window.removeEventListener('keydown', hide);
        };
    }, []);

    return [open, setOpen];
}

export function UploadButton({ model }: { model: FileManagerModel }) {
    const input = useRef<HTMLInputElement>(null);
    const [dropping, setDropping] = useDropOverlay();
    // Failures have already been reported with error toasts.
    const upload = (files: FileList) => void model.actions.upload([...files]).catch(() => {});

    return (
        <>
            <Portal>
                {dropping && (
                    <div
                        className='fixed z-50 overflow-auto flex w-full inset-0 bg-background/70'
                        onDragOver={(event) => event.preventDefault()}
                        onDrop={(event) => {
                            event.preventDefault();
                            event.stopPropagation();

                            setDropping(false);
                            if (
                                Array.from(event.dataTransfer.items).some(
                                    (item) => item.kind === 'file' && item.webkitGetAsEntry?.()?.isDirectory
                                )
                            ) {
                                toast.error('Folder uploads are not supported.');
                                return;
                            }
                            if (!event.dataTransfer?.files.length) {
                                return;
                            }
                            upload(event.dataTransfer.files);
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
                ref={input}
                className='hidden'
                aria-label='Upload files'
                onChange={(event) => {
                    if (!event.currentTarget.files) {
                        return;
                    }

                    upload(event.currentTarget.files);
                    event.currentTarget.value = '';
                }}
                multiple
            />
            <NewButton icon={CloudUpload} onClick={() => input.current?.click()}>
                Upload
            </NewButton>
        </>
    );
}

const svgProps = { cx: 16, cy: 16, r: 14, strokeWidth: 3, fill: 'none', stroke: 'currentColor' };

const Progress = ({ progress, className }: { progress: number; className?: string }) => (
    <svg viewBox='0 0 32 32' className={className}>
        <circle {...svgProps} className='opacity-25' />
        <circle
            {...svgProps}
            stroke='currentColor'
            strokeDasharray={28 * Math.PI}
            className='-rotate-90 origin-[50%_50%] transition-[stroke-dashoffset] duration-300'
            style={{ strokeDashoffset: ((100 - progress) / 100) * 28 * Math.PI }}
        />
    </svg>
);

const percent = (loaded: number, total: number) => (total > 0 ? (loaded / total) * 100 : 0);

function UploadList({ model, onClose }: { model: FileManagerModel; onClose: () => void }) {
    return (
        <div className='space-y-2 mt-6'>
            {model.uploads.map((file) => (
                <div key={file.id} className='flex items-center space-x-3 bg-card p-3 rounded-sm'>
                    <Tooltip content={`${Math.floor(percent(file.loaded, file.total))}%`} placement='left'>
                        <div className='shrink-0'>
                            <Progress progress={percent(file.loaded, file.total)} className='w-6 h-6' />
                        </div>
                    </Tooltip>
                    <Code className='flex-1 truncate'>{file.name}</Code>
                    <button
                        type='button'
                        aria-label={`Cancel upload of ${file.name}`}
                        onClick={() => model.actions.cancelUpload(file.id)}
                        className='text-muted-foreground hover:text-foreground transition-colors duration-75'
                    >
                        <X className='w-5 h-5' />
                    </button>
                </div>
            ))}
            <Dialog.Footer>
                <Button.Danger isSecondary onClick={model.actions.clearUploads}>
                    Cancel Uploads
                </Button.Danger>
                <Button.Text onClick={onClose}>Close</Button.Text>
            </Dialog.Footer>
        </div>
    );
}

export function UploadStatus({ model }: { model: FileManagerModel }) {
    const count = model.uploads.length;

    if (count === 0) {
        return null;
    }

    const uploaded = model.uploads.reduce((sum, file) => sum + file.loaded, 0);
    const total = model.uploads.reduce((sum, file) => sum + file.total, 0);

    return (
        <Dialog.Trigger
            trigger={({ onClick }) => (
                <Tooltip content={`${count} files are uploading, click to view`}>
                    <button type='button' className='flex items-center justify-center w-10 h-10' onClick={onClick}>
                        <Progress progress={percent(uploaded, total)} className='w-8 h-8' />
                        <CloudUpload className='h-3 absolute mx-auto animate-pulse' />
                    </button>
                </Tooltip>
            )}
        >
            {({ open, onClose }) => (
                <Dialog
                    open={open}
                    onClose={onClose}
                    title='File Uploads'
                    description='The following files are being uploaded to your server.'
                >
                    <UploadList model={model} onClose={onClose} />
                </Dialog>
            )}
        </Dialog.Trigger>
    );
}
