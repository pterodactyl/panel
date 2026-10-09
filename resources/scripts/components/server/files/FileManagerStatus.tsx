import React from 'react';
import { useServerStore } from '@/state/server';
import { CloudUpload, X } from 'lucide-react';
import { Dialog } from '@/components/elements/dialog';
import Button from '@/components/elements/Button';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import Code from '@/components/elements/Code';

const svgProps = {
    cx: 16,
    cy: 16,
    r: 14,
    strokeWidth: 3,
    fill: 'none',
    stroke: 'currentColor',
};

const Spinner = ({ progress, className }: { progress: number; className?: string }) => (
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

const FileUploadList = ({ onClose }: { onClose: () => void }) => {
    const cancelFileUpload = useServerStore((state) => state.files.cancelFileUpload);
    const clearFileUploads = useServerStore((state) => state.files.clearFileUploads);
    const uploadsById = useServerStore((state) => state.files.uploads);
    const uploads = React.useMemo(
        () =>
            Object.entries(uploadsById).sort(
                ([a, first], [b, second]) => first.name.localeCompare(second.name) || a.localeCompare(b)
            ),
        [uploadsById]
    );

    return (
        <div className='space-y-2 mt-6'>
            {uploads.map(([id, file]) => (
                <div key={id} className='flex items-center space-x-3 bg-card p-3 rounded-sm'>
                    <Tooltip content={`${Math.floor(percent(file.loaded, file.total))}%`} placement='left'>
                        <div className='shrink-0'>
                            <Spinner progress={percent(file.loaded, file.total)} className='w-6 h-6' />
                        </div>
                    </Tooltip>
                    <Code className='flex-1 truncate'>{file.name}</Code>
                    <button
                        type='button'
                        aria-label={`Cancel upload of ${file.name}`}
                        onClick={() => cancelFileUpload(id)}
                        className='text-muted-foreground hover:text-foreground transition-colors duration-75'
                    >
                        <X className='w-5 h-5' />
                    </button>
                </div>
            ))}
            <Dialog.Footer>
                <Button.Danger isSecondary onClick={() => clearFileUploads()}>
                    Cancel Uploads
                </Button.Danger>
                <Button.Text onClick={onClose}>Close</Button.Text>
            </Dialog.Footer>
        </div>
    );
};

export default function FileManagerStatus() {
    const count = useServerStore((state) => Object.keys(state.files.uploads).length);
    const uploaded = useServerStore((state) =>
        Object.values(state.files.uploads).reduce((count, file) => count + file.loaded, 0)
    );
    const total = useServerStore((state) =>
        Object.values(state.files.uploads).reduce((count, file) => count + file.total, 0)
    );

    if (count === 0) {
        return null;
    }

    return (
        <Dialog.Trigger
            trigger={({ onClick }) => (
                <Tooltip content={`${count} files are uploading, click to view`}>
                    <button type='button' className='flex items-center justify-center w-10 h-10' onClick={onClick}>
                        <Spinner progress={percent(uploaded, total)} className='w-8 h-8' />
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
                    <FileUploadList onClose={onClose} />
                </Dialog>
            )}
        </Dialog.Trigger>
    );
}
