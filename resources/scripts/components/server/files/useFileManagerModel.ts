import { useCallback, useMemo } from 'react';
import { join, normalize } from 'pathe';
import { useNavigate } from '@tanstack/react-router';
import {
    chmodFilesInput,
    compressFilesInput,
    copyFileInput,
    createDirectoryInput,
    decompressFileInput,
    deleteFilesInput,
    fileObjectKind,
    isFileObjectArchiveType,
    isFileObjectEditable,
    renameFilesInput,
    useChmodFiles,
    useCompressFiles,
    useCopyFile,
    useCreateDirectory,
    useDecompressFile,
    useDeleteFiles,
    useFileDownloadUrl,
    useRenameFiles,
    useServerFiles,
    type FileObject,
} from '@/api/server/files/queries';
import { useCurrentServer } from '@/api/server/queries';
import type { FileManagerSlotData } from '@/extensions/registry';
import type { CorePanelDestination } from '@/sdk/navigation';
import { encodePathSegments, normalizeServerPath } from '@/helpers';
import { usePermissions } from '@/plugins/usePermissions';
import { useSelectedFiles, useServerDirectory, useServerStore } from '@/state/server';
import type { FileManagerEntry, FileManagerModel, FileUpload } from '@/views/server/files';
import { fileBreadcrumbs, fileListDestination } from './breadcrumbs';
import useFileUploader from './useFileUploader';

export const FILE_LIST_LIMIT = 250;

const noFiles: FileObject[] = [];

const sortFiles = (files: FileObject[]): FileObject[] => {
    const sortedFiles: FileObject[] = files
        .sort((a, b) => a.attributes.name.localeCompare(b.attributes.name))
        // Directories before files.
        .sort((a, b) => Number(a.attributes.is_file) - Number(b.attributes.is_file));

    return sortedFiles.filter(
        (file, index) => index === 0 || file.attributes.name !== sortedFiles[index - 1].attributes.name
    );
};

function allow(granted: boolean, permission: string): void {
    if (!granted) {
        throw new Error(`This action requires the ${permission} permission.`);
    }
}

/** Where opening an entry leads: its directory, the editor for a readable text file, or nowhere. */
function entryDestination(id: string, file: FileObject, path: string, openable: boolean): CorePanelDestination | null {
    if (!openable) {
        return null;
    }

    return file.attributes.is_file
        ? { to: '/server/$id/files/$action', params: { id, action: 'edit' }, hash: encodePathSegments(path) }
        : fileListDestination(id, path);
}

/** The listing of the directory in the URL and its selection, as the file manager slots receive them. */
function useFileListing() {
    const server = useCurrentServer();
    const directory = useServerDirectory();
    const { data, refetch, isFetching } = useServerFiles(server?.attributes.uuid ?? '', directory);
    const setSelectedFiles = useServerStore((state) => state.files.setSelectedFiles);
    const storedSelection = useSelectedFiles();
    const files = data?.data ?? noFiles;
    const listed = useMemo(() => new Set(files.slice(0, FILE_LIST_LIMIT).map((file) => file.attributes.name)), [files]);
    const selection = useMemo(() => {
        const present = storedSelection.filter((name) => listed.has(name));

        return present.length === storedSelection.length ? storedSelection : present;
    }, [storedSelection, listed]);
    const select = useCallback(
        (names: readonly string[]) => {
            setSelectedFiles({ directory, files: [...new Set(names)].filter((name) => listed.has(name)) });
        },
        [listed, directory, setSelectedFiles]
    );
    const refresh = useCallback(async () => {
        const result = await refetch();

        if (result.error) {
            throw result.error;
        }
    }, [refetch]);
    const slot = useMemo<FileManagerSlotData | null>(
        () =>
            server
                ? { server, directory, files, selectedFiles: selection, isFetching, setSelectedFiles: select, refresh }
                : null,
        [server, directory, files, selection, isFetching, select, refresh]
    );

    if (!slot) {
        throw new Error('The file manager renders once its server has loaded.');
    }

    return { slot, listed: data !== undefined };
}

function useUploads() {
    const uploadsById = useServerStore((state) => state.files.uploads);
    const cancelUpload = useServerStore((state) => state.files.cancelFileUpload);
    const clearUploads = useServerStore((state) => state.files.clearFileUploads);
    const uploads = useMemo(
        () =>
            Object.entries(uploadsById)
                .sort(([a, first], [b, second]) => first.name.localeCompare(second.name) || a.localeCompare(b))
                .map<FileUpload>(([id, { name, loaded, total }]) => ({ id, name, loaded, total })),
        [uploadsById]
    );

    return { uploads, cancelUpload, clearUploads };
}

export default function useFileManagerModel(): FileManagerModel {
    const { slot, listed } = useFileListing();
    const { server, directory, selectedFiles, isFetching, setSelectedFiles, refresh } = slot;
    const { identifier: id, uuid } = server.attributes;
    const navigate = useNavigate();
    const [canRead, canReadContent, canCreate, canUpdate, canDelete, canArchive] = usePermissions([
        'file.read',
        'file.read-content',
        'file.create',
        'file.update',
        'file.delete',
        'file.archive',
    ]);
    const clearSelectedFiles = useServerStore((state) => state.files.clearSelectedFiles);
    const removeSelectedFile = useServerStore((state) => state.files.removeSelectedFile);
    const { mutateAsync: createDirectory } = useCreateDirectory();
    const { mutateAsync: renameFiles } = useRenameFiles();
    const { mutateAsync: deleteFiles } = useDeleteFiles();
    const { mutateAsync: copyFile } = useCopyFile(directory);
    const { mutateAsync: compressFiles } = useCompressFiles();
    const { mutateAsync: decompressFile } = useDecompressFile();
    const { mutateAsync: chmodFiles } = useChmodFiles();
    const { mutateAsync: downloadUrl } = useFileDownloadUrl();
    const upload = useFileUploader();
    const { uploads, cancelUpload, clearUploads } = useUploads();

    const files = useMemo(() => sortFiles(slot.files.slice(0, FILE_LIST_LIMIT)), [slot.files]);
    const entries = useMemo(
        () =>
            files.map<FileManagerEntry>((file) => {
                const path = join(directory, file.attributes.name);
                const openable = file.attributes.is_file ? isFileObjectEditable(file) && canReadContent : canRead;

                return {
                    name: file.attributes.name,
                    path,
                    kind: fileObjectKind(file),
                    size: file.attributes.size ?? 0,
                    mimetype: file.attributes.mimetype,
                    mode: file.attributes.mode ?? '',
                    modeBits: file.attributes.mode_bits ?? '',
                    modifiedAt: file.attributes.modified_at,
                    openable,
                    extractable: isFileObjectArchiveType(file),
                    destination: entryDestination(id, file, path, openable),
                    file,
                };
            }),
        [files, id, directory, canRead, canReadContent]
    );
    const permissions = useMemo(
        () => ({ create: canCreate, update: canUpdate, delete: canDelete, archive: canArchive }),
        [canCreate, canUpdate, canDelete, canArchive]
    );
    const actions = useMemo<FileManagerModel['actions']>(() => {
        const show = (target: string) =>
            navigate({ to: '/server/$id/files', params: { id }, hash: encodePathSegments(target) });
        const deselect = (names: readonly string[]) => {
            for (const name of names) {
                removeSelectedFile({ directory, name });
            }
        };

        const rename = async (renamed: readonly { readonly from: string; readonly to: string }[]) => {
            allow(canUpdate, 'file.update');
            await renameFiles(
                renameFilesInput(
                    uuid,
                    directory,
                    renamed.map(({ from, to }) => ({ from, to }))
                )
            );
            clearSelectedFiles();
        };

        return {
            open: async (name) => {
                const entry = entries.find((entry) => entry.name === name);

                if (!entry?.openable) {
                    throw new Error(`"${name}" cannot be opened.`);
                }

                await (entry.kind === 'directory'
                    ? show(entry.path)
                    : navigate({
                          to: '/server/$id/files/$action',
                          params: { id, action: 'edit' },
                          hash: encodePathSegments(entry.path),
                      }));
            },
            navigate: async (target) => {
                await show(normalize(target.startsWith('/') ? target : join(directory, target)));
            },
            newFile: async () => {
                allow(canCreate, 'file.create');
                await navigate({
                    to: '/server/$id/files/$action',
                    params: { id, action: 'new' },
                    hash: encodePathSegments(directory),
                });
            },
            select: setSelectedFiles,
            refresh,
            createDirectory: async (name) => {
                allow(canCreate, 'file.create');
                await createDirectory(createDirectoryInput(uuid, directory, name));
            },
            rename,
            move: (names, to) => rename(names.map((from) => ({ from, to: names.length > 1 ? join(to, from) : to }))),
            remove: async (names) => {
                allow(canDelete, 'file.delete');
                await deleteFiles(deleteFilesInput(uuid, directory, [...names]));
                deselect(names);
            },
            copy: async (name) => {
                allow(canCreate, 'file.create');
                await copyFile(copyFileInput(uuid, join(directory, name)));
            },
            archive: async (names) => {
                allow(canArchive, 'file.archive');
                await compressFiles(compressFilesInput(uuid, directory, [...names]));
                deselect(names);
            },
            extract: async (name) => {
                allow(canCreate, 'file.create');
                allow(canUpdate, 'file.update');
                await decompressFile(decompressFileInput(uuid, directory, name));
            },
            chmod: async (changed) => {
                allow(canUpdate, 'file.update');
                await chmodFiles(
                    chmodFilesInput(
                        uuid,
                        directory,
                        changed.map(({ file, mode }) => ({ file, mode }))
                    )
                );
                clearSelectedFiles();
            },
            download: async (name) => {
                window.location.assign(await downloadUrl({ uuid, file: join(directory, name) }));
            },
            upload: async (uploaded) => {
                allow(canCreate, 'file.create');
                allow(canUpdate, 'file.update');
                await upload(uploaded);
            },
            cancelUpload,
            clearUploads,
        };
    }, [
        navigate,
        id,
        uuid,
        directory,
        entries,
        canCreate,
        canUpdate,
        canDelete,
        canArchive,
        setSelectedFiles,
        refresh,
        clearSelectedFiles,
        removeSelectedFile,
        createDirectory,
        renameFiles,
        deleteFiles,
        copyFile,
        compressFiles,
        decompressFile,
        chmodFiles,
        downloadUrl,
        upload,
        cancelUpload,
        clearUploads,
    ]);

    return useMemo<FileManagerModel>(
        () => ({
            directory,
            breadcrumbs: fileBreadcrumbs(id, directory, false),
            entries,
            truncated: slot.files.length > FILE_LIST_LIMIT,
            loading: !listed,
            refreshing: listed && isFetching,
            selection: selectedFiles,
            permissions,
            newFileDestination: {
                to: '/server/$id/files/$action',
                params: { id, action: 'new' },
                hash: encodePathSegments(directory),
            },
            uploads,
            resolve: (name) => normalizeServerPath(join(directory, name)),
            actions,
            slot,
        }),
        [id, directory, entries, slot, listed, isFetching, selectedFiles, permissions, uploads, actions]
    );
}
