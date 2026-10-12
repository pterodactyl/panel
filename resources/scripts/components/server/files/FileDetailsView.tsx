import { createElement, useMemo } from 'react';
import { FileText, FileArchive, FileInput, Folder } from 'lucide-react';
import Icon from '@/components/elements/Icon';
import dayjs from '@/lib/dayjs';
import { bytesToString } from '@/lib/formatters';
import { cn } from '@/lib/cn';
import { fileObjectKind, type FileObject } from '@/api/server/files/queries';
import { createTestFileDetails } from '@/views/fixtures';
import type { FileDetailsModel } from '@/views/files';
import { defineView, registerBaseView, type ViewPartProps, type ViewParts, type ViewProps } from '@/views/registry';

type PartProps = ViewPartProps<'server.files.details'>;
function FileIcon({ model }: PartProps) {
    const icons = { file: FileText, directory: Folder, archive: FileArchive, symlink: FileInput };

    return (
        <div className='flex-none text-muted-foreground ml-6 mr-4 text-lg pl-3'>
            <Icon icon={icons[model.kind]} />
        </div>
    );
}

function Name({ model }: PartProps) {
    return <div className='flex-1 truncate'>{model.name}</div>;
}

function Size({ model }: PartProps) {
    return model.kind === 'directory' ? null : (
        <div className='w-1/6 text-right mr-4 hidden sm:block'>{bytesToString(model.size)}</div>
    );
}

function Modified({ model }: PartProps) {
    return (
        <div className='w-1/5 text-right mr-4 hidden md:block' title={model.modifiedAt}>
            {Math.abs(dayjs(model.modifiedAt).diff(dayjs(), 'hour')) > 48
                ? dayjs(model.modifiedAt).format('MMM Do, YYYY h:mmA')
                : dayjs(model.modifiedAt).fromNow()}
        </div>
    );
}

export const fileDetailsParts: ViewParts<'server.files.details'> = {
    icon: FileIcon,
    name: Name,
    size: Size,
    modified: Modified,
};
export function DefaultFileDetails({ className, parts, model, file }: ViewProps<'server.files.details'>) {
    return (
        <div className={cn('flex flex-1 items-center min-w-0', className)}>
            <parts.icon model={model} file={file} />
            <parts.name model={model} file={file} />
            <parts.size model={model} file={file} />
            <parts.modified model={model} file={file} />
        </div>
    );
}

DefaultFileDetails.parts = fileDetailsParts;

function useFileDetailsModel({ file }: { readonly file: FileObject }): FileDetailsModel {
    return useMemo(
        () => ({
            name: file.attributes.name,
            kind: fileObjectKind(file),
            size: file.attributes.size ?? 0,
            modifiedAt: file.attributes.modified_at,
        }),
        [file]
    );
}

export const fileDetailsView = defineView('server.files.details', {
    context: 'server',
    use: useFileDetailsModel,
    placeholder: createElement('div', {
        className: 'h-5 w-full animate-pulse rounded-sm bg-muted',
        'aria-label': 'Loading file details',
    }),
    fixtures: {
        file: () => createTestFileDetails(),
        directory: () => createTestFileDetails({ name: 'plugins', kind: 'directory', size: 0 }),
    },
});
registerBaseView('classic', 'classic', 'server.files.details', DefaultFileDetails);
