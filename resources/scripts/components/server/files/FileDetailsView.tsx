import { createContext, useContext } from 'react';
import { FileText, FileArchive, FileInput, Folder } from 'lucide-react';
import Icon from '@/components/elements/Icon';
import dayjs from '@/lib/dayjs';
import { bytesToString } from '@/lib/formatters';
import { cn } from '@/lib/cn';
import type {
    ComponentPartProps,
    ComponentParts,
    DefaultComponentProps,
    FileDetailsModel,
} from '@/extensions/componentTypes';

export const FileDetailsContext = createContext<FileDetailsModel | null>(null);
type PartProps = ComponentPartProps<'server.files.details'>;
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
export const fileDetailsParts: ComponentParts<'server.files.details'> = {
    icon: FileIcon,
    name: Name,
    size: Size,
    modified: Modified,
};
export function DefaultFileDetails({ className, parts }: DefaultComponentProps<'server.files.details'>) {
    const model = useContext(FileDetailsContext)!;
    const IconPart = parts?.icon ?? FileIcon;
    const NamePart = parts?.name ?? Name;
    const SizePart = parts?.size ?? Size;
    const ModifiedPart = parts?.modified ?? Modified;
    return (
        <div className={cn('flex flex-1 items-center min-w-0', className)}>
            <IconPart model={model} />
            <NamePart model={model} />
            <SizePart model={model} />
            <ModifiedPart model={model} />
        </div>
    );
}
