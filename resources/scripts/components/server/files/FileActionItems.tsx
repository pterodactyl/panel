import { PackageOpen, Copy, FileArchive, FileCode, FileDown, CornerLeftUp, Pencil, Trash2 } from 'lucide-react';
import Can from '@/components/elements/Can';
import { DropdownMenuItem } from '@/components/elements/dropdown/DropdownMenu';
import { isFileObjectArchiveType, type FileObject } from '@/api/server/files/queries';

interface Props {
    file: FileObject;
    onRename: () => void;
    onMove: () => void;
    onChmod: () => void;
    onCopy: () => void;
    onUnarchive: () => void;
    onArchive: () => void;
    onDownload: () => void;
    onDelete: () => void;
}

export default function FileActionItems({
    file,
    onRename,
    onMove,
    onChmod,
    onCopy,
    onUnarchive,
    onArchive,
    onDownload,
    onDelete,
}: Props) {
    const { attributes } = file;

    return (
        <>
            <Can action='file.update'>
                <DropdownMenuItem onClick={onRename} icon={Pencil} description='Change item name'>
                    Rename
                </DropdownMenuItem>
                <DropdownMenuItem onClick={onMove} icon={CornerLeftUp} description='Move to a path'>
                    Move
                </DropdownMenuItem>
                <DropdownMenuItem onClick={onChmod} icon={FileCode} description='Edit mode bits'>
                    Permissions
                </DropdownMenuItem>
            </Can>
            {attributes.is_file && (
                <Can action='file.create'>
                    <DropdownMenuItem onClick={onCopy} icon={Copy} description='Duplicate in place'>
                        Copy
                    </DropdownMenuItem>
                </Can>
            )}
            {isFileObjectArchiveType(file) ? (
                <Can action={['file.create', 'file.update']}>
                    <DropdownMenuItem onClick={onUnarchive} icon={PackageOpen} description='Extract contents'>
                        Unarchive
                    </DropdownMenuItem>
                </Can>
            ) : (
                <Can action='file.archive'>
                    <DropdownMenuItem onClick={onArchive} icon={FileArchive} description='Compress item'>
                        Archive
                    </DropdownMenuItem>
                </Can>
            )}
            {attributes.is_file && (
                <DropdownMenuItem onClick={onDownload} icon={FileDown} description='Save locally'>
                    Download
                </DropdownMenuItem>
            )}
            <Can action='file.delete'>
                <DropdownMenuItem
                    onClick={onDelete}
                    icon={Trash2}
                    danger
                    description='Permanently remove'
                    className='mt-1 border-t border-border/70 pt-3'
                >
                    Delete
                </DropdownMenuItem>
            </Can>
        </>
    );
}
