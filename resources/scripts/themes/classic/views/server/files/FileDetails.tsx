import { FileArchive, FileInput, FileText, Folder } from 'lucide-react';
import { bytesToString, cn, formatDate, fromNow, Icon } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';

const icons = { file: FileText, directory: Folder, archive: FileArchive, symlink: FileInput };

const HOUR = 60 * 60 * 1000;

export default function FileDetails({ className, model }: ViewProps<'server.files.details'>) {
    const hours = Math.trunc((Date.parse(model.modifiedAt) - Date.now()) / HOUR);

    return (
        <div className={cn('flex flex-1 items-center min-w-0', className)}>
            <div className='flex-none text-muted-foreground ml-6 mr-4 text-lg pl-3'>
                <Icon icon={icons[model.kind]} />
            </div>
            <div className='flex-1 truncate'>{model.name}</div>
            {model.kind !== 'directory' && (
                <div className='w-1/6 text-right mr-4 hidden sm:block'>{bytesToString(model.size)}</div>
            )}
            <div className='w-1/5 text-right mr-4 hidden md:block' title={model.modifiedAt}>
                {Math.abs(hours) > 48 ? formatDate(model.modifiedAt, 'MMM Do, YYYY h:mmA') : fromNow(model.modifiedAt)}
            </div>
        </div>
    );
}
