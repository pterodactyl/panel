import ComponentView from '@/extensions/ComponentView';
import type { FileDetailsModel } from '@/extensions/componentTypes';
import { DefaultFileDetails, FileDetailsContext, fileDetailsParts } from './FileDetailsView';
import { MoreHorizontal } from 'lucide-react';
import Icon from '@/components/elements/Icon';
import { encodePathSegments } from '@/helpers';
import React, { memo, useMemo } from 'react';
import { fileObjectKind, isFileObjectEditable, type FileObject } from '@/api/server/files/queries';
import { useCurrentServerIdentifier, useCurrentServerUuid } from '@/api/server/queries';
import DropdownMenu, { ContextDropdownMenu } from '@/components/elements/dropdown/DropdownMenu';
import useFileActions from '@/components/server/files/useFileActions';
import { useServerDirectory } from '@/state/server';
import { Link } from '@tanstack/react-router';
import isEqual from 'react-fast-compare';
import SelectFileCheckbox from '@/components/server/files/SelectFileCheckbox';
import { usePermissions } from '@/plugins/usePermissions';
import { join } from 'pathe';
import { cn } from '@/lib/cn';
import Slot from '@/extensions/Slot';
import useFileManagerExtensionData from './useFileManagerExtensionData';

const fileRowClass =
    'flex items-center cursor-pointer bg-card rounded-xs mb-px text-sm no-underline hover:text-foreground hover:bg-popover';
const detailsClass = 'flex flex-1 items-center text-muted-foreground no-underline px-4 py-2 overflow-hidden truncate';

interface ClickableProps {
    file: FileObject;
    children: React.ReactNode;
}

const Clickable = memo(({ file, children }: ClickableProps) => {
    const [canRead] = usePermissions(['file.read']);
    const [canReadContents] = usePermissions(['file.read-content']);
    const directory = useServerDirectory();
    const id = useCurrentServerIdentifier()!;
    const { attributes } = file;

    const hash = encodePathSegments(join(directory, attributes.name));

    const openable = attributes.is_file ? isFileObjectEditable(file) && canReadContents : canRead;

    if (!openable) {
        return <div className={cn(detailsClass, 'cursor-default')}>{children}</div>;
    }

    if (attributes.is_file) {
        return (
            <Link
                aria-label={attributes.name}
                className={detailsClass}
                to='/server/$id/files/$action'
                params={{ id, action: 'edit' }}
                hash={hash}
            >
                {children}
            </Link>
        );
    }

    return (
        <Link aria-label={attributes.name} className={detailsClass} to='/server/$id/files' params={{ id }} hash={hash}>
            {children}
        </Link>
    );
}, isEqual);

const RowActionsSlot = ({ file }: { file: FileObject }) => {
    const extensionData = useFileManagerExtensionData();

    return extensionData && <Slot name='server.files.rowActions' data={{ ...extensionData, file }} />;
};

const FileObjectRow = ({ file }: { file: FileObject }) => {
    const { items } = useFileActions(file);
    const { attributes } = file;
    const uuid = useCurrentServerUuid();
    const directory = useServerDirectory();
    const model = useMemo<FileDetailsModel>(
        () => ({
            name: attributes.name,
            kind: fileObjectKind(file),
            size: attributes.size ?? 0,
            modifiedAt: attributes.modified_at,
        }),
        [attributes, file]
    );
    const actionItems = (
        <>
            {items}
            <RowActionsSlot file={file} />
        </>
    );

    return (
        <ContextDropdownMenu className={fileRowClass} menuClassName='w-64' items={actionItems}>
            <SelectFileCheckbox name={attributes.name} />
            <Clickable file={file}>
                <FileDetailsContext.Provider value={model}>
                    <ComponentView
                        name='server.files.details'
                        resetKey={`${uuid ?? ''}:${directory}:${attributes.name}`}
                        props={{ model, Default: DefaultFileDetails, parts: fileDetailsParts }}
                        loading={
                            <div
                                className='h-5 w-full animate-pulse rounded-sm bg-muted'
                                aria-label='Loading file details'
                            />
                        }
                    />
                </FileDetailsContext.Provider>
            </Clickable>
            <DropdownMenu
                className='w-64'
                triggerClassName='mr-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring'
                triggerContent={
                    <>
                        <Icon icon={MoreHorizontal} className='h-4 w-4' />
                        <span className='sr-only'>Open file options</span>
                    </>
                }
            >
                {actionItems}
            </DropdownMenu>
        </ContextDropdownMenu>
    );
};

export default memo(FileObjectRow, (prevProps, nextProps) => isEqual(prevProps.file, nextProps.file));
