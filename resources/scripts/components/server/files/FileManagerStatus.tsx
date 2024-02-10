import { CloudUploadIcon, XIcon } from '@heroicons/react/solid';
import { useSignal } from '@preact/signals-react';
import { useContext, useEffect } from 'react';

import { Button } from '@/components/elements/button/index';
import { Dialog, DialogWrapperContext } from '@/components/elements/dialog';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import Code from '@/components/elements/Code';
import asDialog from '@/hoc/asDialog';
import { ServerContext } from '@/state/server';

const svgProps = {
    cx: 16,
    cy: 16,
    r: 14,
    strokeWidth: 3,
    fill: 'none',
    stroke: 'currentColor',
};

const Spinner = ({ progress, className }: { progress: number; className?: string }) => (
    <svg viewBox={'0 0 32 32'} className={className}>
        <circle {...svgProps} className={'opacity-25'} />
        <circle
            {...svgProps}
            stroke={'white'}
            strokeDasharray={28 * Math.PI}
            className={'origin-[50%_50%] rotate-[-90deg] transition-[stroke-dashoffset] duration-300'}
            style={{ strokeDashoffset: ((100 - progress) / 100) * 28 * Math.PI }}
        />
    </svg>
);

const FileUploadList = () => {
    const { close } = useContext(DialogWrapperContext);
    const cancelFileUpload = ServerContext.useStoreActions(actions => actions.files.cancelFileUpload);
    const clearFileUploads = ServerContext.useStoreActions(actions => actions.files.clearFileUploads);
    const uploads = ServerContext.useStoreState(state =>
        Object.entries(state.files.uploads).sort(([a], [b]) => a.localeCompare(b)),
    );

    return (
        <div className={'mt-6 space-y-2'}>
            {uploads.map(([name, file]) => (
                <div key={name} className={'flex items-center space-x-3 rounded bg-slate-700 p-3'}>
                    <Tooltip content={`${Math.floor((file.loaded / file.total) * 100)}%`} placement={'left'}>
                        <div className={'flex-shrink-0'}>
                            <Spinner progress={(file.loaded / file.total) * 100} className={'h-6 w-6'} />
                        </div>
                    </Tooltip>
                    <Code className={'flex-1 truncate'}>{name}</Code>
                    <button
                        onClick={cancelFileUpload.bind(this, name)}
                        className={'text-slate-500 transition-colors duration-75 hover:text-slate-200'}
                    >
                        <XIcon className={'h-5 w-5'} />
                    </button>
                </div>
            ))}
            <Dialog.Footer>
                <Button.Danger variant={Button.Variants.Secondary} onClick={() => clearFileUploads()}>
                    Cancel Uploads
                </Button.Danger>
                <Button.Text onClick={close}>Close</Button.Text>
            </Dialog.Footer>
        </div>
    );
};

const FileUploadListDialog = asDialog({
    title: 'File Uploads',
    description: 'The following files are being uploaded to your server.',
})(FileUploadList);

export default () => {
    const open = useSignal(false);

    const count = ServerContext.useStoreState(state => Object.keys(state.files.uploads).length);
    const progress = ServerContext.useStoreState(state => ({
        uploaded: Object.values(state.files.uploads).reduce((count, file) => count + file.loaded, 0),
        total: Object.values(state.files.uploads).reduce((count, file) => count + file.total, 0),
    }));

    useEffect(() => {
        if (count === 0) {
            open.value = false;
        }
    }, [count]);

    return (
        <>
            {count > 0 && (
                <Tooltip content={`${count} files are uploading, click to view`}>
                    <button
                        className={'flex h-10 w-10 items-center justify-center'}
                        onClick={() => (open.value = true)}
                    >
                        <Spinner progress={(progress.uploaded / progress.total) * 100} className={'h-8 w-8'} />
                        <CloudUploadIcon className={'absolute mx-auto h-3 animate-pulse'} />
                    </button>
                </Tooltip>
            )}
            <FileUploadListDialog open={open.value} onClose={() => (open.value = false)} />
        </>
    );
};
