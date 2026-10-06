import { useMemo } from 'react';
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
    type FileObject,
} from '@/api/server/files/queries';
import { encodePathSegments } from '@/helpers';
import { usePermissions } from '@/plugins/usePermissions';
import { useServerStore } from '@/state/server';
import type { FileManagerEntry, FileManagerModel } from '@/extensions/componentTypes';
import type { FileManagerSlotData } from '@/extensions/registry';
import type { FileManagerSession } from './FileManagerView';
import useFileUploader from './useFileUploader';

export const FILE_LIST_LIMIT = 250;

const sortFiles = (files: FileObject[]): FileObject[] => {
    const sortedFiles: FileObject[] = files
        .sort((a, b) => a.attributes.name.localeCompare(b.attributes.name))
        .sort((a, b) => (a.attributes.is_file === b.attributes.is_file ? 0 : a.attributes.is_file ? 1 : -1));
    return sortedFiles.filter(
        (file, index) => index === 0 || file.attributes.name !== sortedFiles[index - 1].attributes.name
    );
};

function allow(granted: boolean, permission: string): void {
    if (!granted) throw new Error(`This action requires the ${permission} permission.`);
}

export default function useFileManagerModel(data: FileManagerSlotData, listed: boolean): FileManagerSession {
    const { server, directory, selectedFiles, isFetching, setSelectedFiles, refresh } = data;
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
    const { mutateAsync: createDirectory } = useCreateDirectory();
    const { mutateAsync: renameFiles } = useRenameFiles();
    const { mutateAsync: deleteFiles } = useDeleteFiles();
    const { mutateAsync: copyFile } = useCopyFile(directory);
    const { mutateAsync: compressFiles } = useCompressFiles();
    const { mutateAsync: decompressFile } = useDecompressFile();
    const { mutateAsync: chmodFiles } = useChmodFiles();
    const { mutateAsync: downloadUrl } = useFileDownloadUrl();
    const upload = useFileUploader();

    const files = useMemo(() => sortFiles(data.files.slice(0, FILE_LIST_LIMIT)), [data.files]);
    const entries = useMemo(
        () =>
            files.map<FileManagerEntry>((file) => ({
                name: file.attributes.name,
                path: join(directory, file.attributes.name),
                kind: fileObjectKind(file),
                size: file.attributes.size ?? 0,
                mimetype: file.attributes.mimetype,
                mode: file.attributes.mode ?? '',
                modeBits: file.attributes.mode_bits ?? '',
                modifiedAt: file.attributes.modified_at,
                openable: file.attributes.is_file ? isFileObjectEditable(file) && canReadContent : canRead,
            })),
        [files, directory, canRead, canReadContent]
    );
    const permissions = useMemo(
        () => ({ create: canCreate, update: canUpdate, delete: canDelete, archive: canArchive }),
        [canCreate, canUpdate, canDelete, canArchive]
    );
    const actions = useMemo<FileManagerModel['actions']>(() => {
        const show = (target: string) =>
            navigate({ to: '/server/$id/files', params: { id }, hash: encodePathSegments(target) });
        return {
            open: async (name) => {
                const entry = entries.find((entry) => entry.name === name);
                if (!entry?.openable) throw new Error(`"${name}" cannot be opened.`);
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
            rename: async (renamed) => {
                allow(canUpdate, 'file.update');
                await renameFiles(
                    renameFilesInput(
                        uuid,
                        directory,
                        renamed.map(({ from, to }) => ({ from, to }))
                    )
                );
                clearSelectedFiles();
            },
            remove: async (names) => {
                allow(canDelete, 'file.delete');
                await deleteFiles(deleteFilesInput(uuid, directory, [...names]));
                clearSelectedFiles();
            },
            copy: async (name) => {
                allow(canCreate, 'file.create');
                await copyFile(copyFileInput(uuid, join(directory, name)));
            },
            archive: async (names) => {
                allow(canArchive, 'file.archive');
                await compressFiles(compressFilesInput(uuid, directory, [...names]));
                clearSelectedFiles();
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
            },
            download: async (name) => {
                window.location.assign(await downloadUrl({ uuid, file: join(directory, name) }));
            },
            upload: async (uploaded) => {
                allow(canCreate, 'file.create');
                allow(canUpdate, 'file.update');
                await upload(uploaded);
            },
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
        createDirectory,
        renameFiles,
        deleteFiles,
        copyFile,
        compressFiles,
        decompressFile,
        chmodFiles,
        downloadUrl,
        upload,
    ]);
    const model = useMemo<FileManagerModel>(
        () => ({
            directory,
            entries,
            truncated: data.files.length > FILE_LIST_LIMIT,
            loading: !listed,
            refreshing: listed && isFetching,
            selection: selectedFiles,
            permissions,
            actions,
        }),
        [directory, entries, data.files.length, listed, isFetching, selectedFiles, permissions, actions]
    );

    return useMemo(() => ({ model, files }), [model, files]);
}
