import { FileArchive, FileInput, FileText, Folder } from 'lucide-react';
import { bytesToString, cn, formatDate, fromNow, Icon } from '@/sdk';
import type { ViewPartProps, ViewParts, ViewProps } from '@/sdk/theme';

type PartProps = ViewPartProps<'server.files.details'>;

const icons = { file: FileText, directory: Folder, archive: FileArchive, symlink: FileInput };

function FileIcon({ model }: PartProps) {
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

const HOUR = 60 * 60 * 1000;

function Modified({ model }: PartProps) {
    const hours = Math.trunc((Date.parse(model.modifiedAt) - Date.now()) / HOUR);

    return (
        <div className='w-1/5 text-right mr-4 hidden md:block' title={model.modifiedAt}>
            {Math.abs(hours) > 48 ? formatDate(model.modifiedAt, 'MMM Do, YYYY h:mmA') : fromNow(model.modifiedAt)}
        </div>
    );
}

const fileDetailsParts: ViewParts<'server.files.details'> = {
    icon: FileIcon,
    name: Name,
    size: Size,
    modified: Modified,
};

export default function FileDetails({ className, parts, model, file }: ViewProps<'server.files.details'>) {
    return (
        <div className={cn('flex flex-1 items-center min-w-0', className)}>
            <parts.icon model={model} file={file} />
            <parts.name model={model} file={file} />
            <parts.size model={model} file={file} />
            <parts.modified model={model} file={file} />
        </div>
    );
}

FileDetails.parts = fileDetailsParts;
