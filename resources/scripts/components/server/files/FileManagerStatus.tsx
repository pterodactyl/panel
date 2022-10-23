import React, { useContext, useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import { CloudUploadIcon } from '@heroicons/react/solid';
import asDialog from '@/hoc/asDialog';
import { Dialog, DialogWrapperContext } from '@/components/elements/dialog';
import { Button } from '@/components/elements/button/index';
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
    <svg viewBox={'0 0 32 32'} className={className}>
        <circle {...svgProps} className={'opacity-25'} />
        <circle
            {...svgProps}
            stroke={'white'}
            strokeDasharray={28 * Math.PI}
            className={'rotate-[-90deg] origin-[50%_50%] transition-[stroke-dashoffset] duration-300'}
            style={{ strokeDashoffset: ((100 - progress) / 100) * 28 * Math.PI }}
        />
    </svg>
);

const FileUploadList = () => {
    const { close } = useContext(DialogWrapperContext);
    const uploads = ServerContext.useStoreState((state) =>
        state.files.uploads.sort((a, b) => a.name.localeCompare(b.name))
    );

    return (
        <div className={'space-y-2 mt-6'}>
            {uploads.map((file) => (
                <div key={file.name} className={'flex items-center space-x-3 bg-gray-700 p-3 rounded'}>
                    <Tooltip content={`${Math.floor((file.loaded / file.total) * 100)}%`} placement={'left'}>
                        <div className={'flex-shrink-0'}>
                            <Spinner progress={(file.loaded / file.total) * 100} className={'w-6 h-6'} />
                        </div>
                    </Tooltip>
                    <Code>{file.name}</Code>
                </div>
            ))}
            <Dialog.Footer>
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
    const [open, setOpen] = useState(false);

    const count = ServerContext.useStoreState((state) => state.files.uploads.length);
    const progress = ServerContext.useStoreState((state) => ({
        uploaded: state.files.uploads.reduce((count, file) => count + file.loaded, 0),
        total: state.files.uploads.reduce((count, file) => count + file.total, 0),
    }));

    useEffect(() => {
        if (count === 0) {
            setOpen(false);
        }
    }, [count]);

    return (
        <>
            {count > 0 && (
                <Tooltip content={`${count} files are uploading, click to view`}>
                    <button className={'flex items-center justify-center w-10 h-10'} onClick={setOpen.bind(this, true)}>
                        <Spinner progress={(progress.uploaded / progress.total) * 100} className={'w-8 h-8'} />
                        <CloudUploadIcon className={'h-3 absolute mx-auto animate-pulse'} />
                    </button>
                </Tooltip>
            )}
            <FileUploadListDialog open={open} onClose={setOpen.bind(this, false)} />
        </>
    );
};
