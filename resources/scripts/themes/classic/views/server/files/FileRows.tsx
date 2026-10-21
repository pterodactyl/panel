import { createContext, memo, use, useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import { join } from 'pathe';
import {
    Copy,
    CornerLeftUp,
    FileArchive,
    FileCode,
    FileDown,
    ImageIcon,
    MoreHorizontal,
    PackageOpen,
    Pencil,
    Trash2,
} from 'lucide-react';
import {
    Checkbox,
    cn,
    ContextDropdownMenu,
    Dialog,
    DropdownMenu,
    DropdownMenuItem,
    Icon,
    PanelLink,
    SpinnerOverlay,
    type FileManagerSlotData,
} from '@/sdk';
import { isFileObjectImageType, useFileDownloadUrl } from '@/api/server/files/queries';
import { useCurrentServerUuid } from '@/api/server/queries';
import { Slot, View, type FileManagerEntry, type FileManagerModel } from '@/sdk/theme';
import { usePending } from '../usePending';
import { ChmodDialog, RenameDialog } from './FileDialogs';
import ImagePreviewModal from '@/components/server/files/ImagePreviewModal';

const fileRowClass =
    'flex items-center cursor-pointer bg-card rounded-xs mb-px text-sm no-underline hover:text-foreground hover:bg-popover';
const detailsClass = 'flex flex-1 items-center text-muted-foreground no-underline px-4 py-2 overflow-hidden truncate';

type RowDialog = 'rename' | 'move' | 'chmod' | 'delete' | 'image';

interface RowActions {
    readonly permissions: FileManagerModel['permissions'];
    readonly open: (dialog: RowDialog, entry: FileManagerEntry) => void;
    readonly openImage: (entry: FileManagerEntry) => void;
    readonly run: (action: Promise<void>) => void;
    readonly actions: FileManagerModel['actions'];
    readonly toggle: (name: string, selected: boolean) => void;
}

const RowActionsContext = createContext<RowActions | null>(null);
// Apart from the row actions, so a selection change re-renders no row.
const SlotContext = createContext<FileManagerSlotData | null>(null);

function useRowActions(): RowActions {
    const actions = use(RowActionsContext);

    if (!actions) {
        throw new Error('File rows render inside the file list.');
    }

    return actions;
}

/** Renders the row dialogs once for every row below it. */
export function RowActionsProvider({ model, children }: { model: FileManagerModel; children: ReactNode }) {
    const [dialog, setDialog] = useState<RowDialog | null>(null);
    const [target, setTarget] = useState<FileManagerEntry | null>(null);
    const [imageUrl, setImageUrl] = useState<string | null>(null);
    const [pending, run] = usePending();
    const selection = useRef(model.selection);
    const { actions, permissions } = model;
    const uuid = useCurrentServerUuid()!;
    const { mutateAsync: download } = useFileDownloadUrl();

    useEffect(() => {
        selection.current = model.selection;
    });

    const openImage = (entry: FileManagerEntry) => {
        setTarget(entry);
        setImageUrl(null);
        setDialog('image');
        run(
            download({ uuid, file: join(model.directory, entry.name) })
                .then(setImageUrl)
                .catch(() => setDialog(null))
        );
    };

    const value = useMemo<RowActions>(
        () => ({
            permissions,
            actions,
            open: (next, entry) => {
                setTarget(entry);
                setDialog(next);
            },
            openImage,
            run,
            toggle: (name, selected) =>
                actions.select(
                    selected
                        ? [...selection.current.filter((file) => file !== name), name]
                        : selection.current.filter((file) => file !== name)
                ),
        }),
        [actions, openImage, permissions, run]
    );
    const close = () => {
        setDialog(null);
        setImageUrl(null);
    };
    const name = target?.name ?? '';

    return (
        <RowActionsContext.Provider value={value}>
            <SlotContext.Provider value={model.slot}>
                {children}
                <Dialog.Confirm
                    open={dialog === 'delete'}
                    onClose={close}
                    title={`Delete ${target?.kind === 'directory' ? 'Directory' : 'File'}`}
                    confirm='Delete'
                    onConfirmed={() => {
                        close();
                        void actions.remove([name]).catch(() => {});
                    }}
                >
                    You will not be able to recover the contents of&nbsp;
                    <span className='font-semibold text-foreground'>{name}</span> once deleted.
                </Dialog.Confirm>
                {target && dialog === 'chmod' && (
                    <ChmodDialog
                        key={name}
                        model={model}
                        open
                        files={[{ file: name, mode: target.modeBits }]}
                        onClose={close}
                    />
                )}
                {target && (dialog === 'rename' || dialog === 'move') && (
                    <RenameDialog
                        key={`${dialog}:${name}`}
                        model={model}
                        open
                        files={[name]}
                        move={dialog === 'move'}
                        onClose={close}
                    />
                )}
                <ImagePreviewModal open={dialog === 'image'} name={name} src={imageUrl} onClose={close} />
                <SpinnerOverlay visible={pending} fixed size='large' />
            </SlotContext.Provider>
        </RowActionsContext.Provider>
    );
}

function ActionItems({ entry }: { entry: FileManagerEntry }) {
    const { permissions, actions, open, openImage, run } = useRowActions();
    const isFile = entry.kind !== 'directory';

    return (
        <>
            {isFileObjectImageType(entry.file) && (
                <DropdownMenuItem onClick={() => openImage(entry)} icon={ImageIcon} description='Preview image'>
                    Open Image
                </DropdownMenuItem>
            )}
            {permissions.update && (
                <>
                    <DropdownMenuItem
                        onClick={() => open('rename', entry)}
                        icon={Pencil}
                        description='Change item name'
                    >
                        Rename
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        onClick={() => open('move', entry)}
                        icon={CornerLeftUp}
                        description='Move to a path'
                    >
                        Move
                    </DropdownMenuItem>
                    <DropdownMenuItem onClick={() => open('chmod', entry)} icon={FileCode} description='Edit mode bits'>
                        Permissions
                    </DropdownMenuItem>
                </>
            )}
            {isFile && permissions.create && (
                <DropdownMenuItem
                    onClick={() => run(actions.copy(entry.name))}
                    icon={Copy}
                    description='Duplicate in place'
                >
                    Copy
                </DropdownMenuItem>
            )}
            {entry.extractable
                ? permissions.create &&
                  permissions.update && (
                      <DropdownMenuItem
                          onClick={() => run(actions.extract(entry.name))}
                          icon={PackageOpen}
                          description='Extract contents'
                      >
                          Unarchive
                      </DropdownMenuItem>
                  )
                : permissions.archive && (
                      <DropdownMenuItem
                          onClick={() => run(actions.archive([entry.name]))}
                          icon={FileArchive}
                          description='Compress item'
                      >
                          Archive
                      </DropdownMenuItem>
                  )}
            {isFile && (
                <DropdownMenuItem
                    onClick={() => run(actions.download(entry.name))}
                    icon={FileDown}
                    description='Save locally'
                >
                    Download
                </DropdownMenuItem>
            )}
            {permissions.delete && (
                <DropdownMenuItem
                    onClick={() => open('delete', entry)}
                    icon={Trash2}
                    danger
                    description='Permanently remove'
                    className='mt-1 border-t border-border/70 pt-3'
                >
                    Delete
                </DropdownMenuItem>
            )}
        </>
    );
}

function RowActionsSlot({ entry }: { entry: FileManagerEntry }) {
    const slot = use(SlotContext);

    return slot && <Slot name='server.files.rowActions' data={{ ...slot, file: entry.file }} />;
}

function Clickable({ entry, children }: { entry: FileManagerEntry; children: ReactNode }) {
    if (!entry.destination) {
        return <div className={cn(detailsClass, 'cursor-default')}>{children}</div>;
    }

    return (
        <PanelLink aria-label={entry.name} className={detailsClass} destination={entry.destination}>
            {children}
        </PanelLink>
    );
}

export const FileRow = memo(({ entry, selected }: { entry: FileManagerEntry; selected: boolean }) => {
    const { toggle } = useRowActions();
    const items = (
        <>
            <ActionItems entry={entry} />
            <RowActionsSlot entry={entry} />
        </>
    );

    return (
        <ContextDropdownMenu className={fileRowClass} menuClassName='w-64' items={items}>
            <div className='flex-none px-4 py-2 absolute self-center z-30'>
                <Checkbox
                    name='selectedFiles'
                    value={entry.name}
                    aria-label={`Select ${entry.name}`}
                    checked={selected}
                    onChange={(checked) => toggle(entry.name, checked)}
                />
            </div>
            <Clickable entry={entry}>
                <View name='server.files.details' file={entry.file} />
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
                {items}
            </DropdownMenu>
        </ContextDropdownMenu>
    );
});
