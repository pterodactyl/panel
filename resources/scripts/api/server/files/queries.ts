import { useQuery, queryOptions, useMutation, useQueryClient, type QueryClient } from '@tanstack/react-query';
import axios, { type AxiosProgressEvent } from 'axios';
import { toast } from 'sonner';
import { cleanDirectoryPath, fileBitsToString, newDirectoryDisplayName } from '@/helpers';

import { removeListItems, updateListItems } from '@/api/queryData';
import {
    clientChangeFilePermissionsMutation,
    clientCompressFilesMutation,
    clientCopyFileMutation,
    clientCreateFolderMutation,
    clientDecompressFileMutation,
    clientDeleteFilesMutation,
    clientGetFileContentsQueryKey,
    clientListFilesOptions,
    clientListFilesQueryKey,
    clientRenameFilesMutation,
    clientWriteFileContentsMutation,
} from '@/api/generated/@tanstack/react-query.gen';
import {
    clientGetFileContents,
    clientGetFileDownloadUrl,
    clientGetFileUploadUrl,
    type ClientChangeFilePermissionsData,
    type ClientCompressFilesData,
    type ClientCopyFileData,
    type ClientCreateFolderData,
    type ClientDecompressFileData,
    type ClientDeleteFilesData,
    type ClientFileObjectResource,
    type ClientGetFileContentsData,
    type ClientListFilesData,
    type ClientListFilesResponse,
    type ClientRenameFilesData,
    type ClientWriteFileContentsData,
    type Options,
} from '@/api/generated';
import { notifyHttpError } from '@/plugins/notifications';

export type FileObject = ClientListFilesResponse['data'][number];

export type RenameFile = { from: string; to: string };
export type ChmodFile = { file: string; mode: string };
export type FileUploadOutcome = 'uploaded' | 'cancelled' | 'failed';
export type FileUploadSummary = Record<FileUploadOutcome, File[]>;
type FileUploadCallbacks = {
    onFileQueued: (id: string, file: File, controller: AbortController) => void;
    onFileProgress: (id: string, progress: AxiosProgressEvent) => void;
    onFileSettled: (id: string, outcome: FileUploadOutcome) => void;
};

const UPLOAD_CONCURRENCY = 3;
let uploadSequence = 0;

const serverFilesInput = (uuid: string, directory: string): Options<ClientListFilesData> => ({
    path: { server_uuid: uuid },
    query: { directory: cleanDirectoryPath(directory) },
});

const serverFileContentInput = (uuid: string, file: string): Options<ClientGetFileContentsData> => ({
    path: { server_uuid: uuid },
    query: { file },
});

export const createDirectoryInput = (
    uuid: string,
    directory: string,
    name: string
): Options<ClientCreateFolderData> => ({
    path: { server_uuid: uuid },
    body: { root: directory, name },
});

export const renameFilesInput = (
    uuid: string,
    directory: string,
    files: RenameFile[]
): Options<ClientRenameFilesData> => ({
    path: { server_uuid: uuid },
    body: { root: directory, files },
});

export const chmodFilesInput = (
    uuid: string,
    directory: string,
    files: ChmodFile[]
): Options<ClientChangeFilePermissionsData> => ({
    path: { server_uuid: uuid },
    body: {
        root: directory,
        files: files.map(({ file, mode }) => ({ file, mode })),
    },
});

export const deleteFilesInput = (uuid: string, directory: string, files: string[]): Options<ClientDeleteFilesData> => ({
    path: { server_uuid: uuid },
    body: { root: directory, files },
});

export const copyFileInput = (uuid: string, location: string): Options<ClientCopyFileData> => ({
    path: { server_uuid: uuid },
    body: { location },
});

export const compressFilesInput = (
    uuid: string,
    directory: string,
    files: string[]
): Options<ClientCompressFilesData> => ({
    path: { server_uuid: uuid },
    body: { root: directory, files },
    timeout: 60000,
    timeoutErrorMessage: 'It looks like this archive is taking a long time to generate. It will appear once completed.',
});

export const decompressFileInput = (
    uuid: string,
    directory: string,
    file: string
): Options<ClientDecompressFileData> => ({
    path: { server_uuid: uuid },
    body: { root: directory, file },
    timeout: 300000,
    timeoutErrorMessage:
        'It looks like this archive is taking a long time to be unarchived. Once completed the unarchived files will appear.',
});

export const writeFileContentsInput = (
    uuid: string,
    file: string,
    content: string
): Options<ClientWriteFileContentsData> => ({
    path: { server_uuid: uuid },
    query: { file },
    body: content,
});

export const serverFilesQueryKey = (uuid: string, directory: string) =>
    clientListFilesQueryKey(serverFilesInput(uuid, directory));

const allServerFilesQueryKey = (uuid: string) => clientListFilesQueryKey({ path: { server_uuid: uuid } });

const serverFileContentQueryKey = (uuid: string, file: string) =>
    clientGetFileContentsQueryKey(serverFileContentInput(uuid, file));

const serverFilesQueryUpdate = async (
    queryClient: QueryClient,
    uuid: string,
    directory: string,
    updater: (current: ClientListFilesResponse | undefined) => ClientListFilesResponse | undefined
) => {
    await queryClient.cancelQueries({ queryKey: serverFilesQueryKey(uuid, directory) });
    return queryClient.setQueryData<ClientListFilesResponse>(serverFilesQueryKey(uuid, directory), updater);
};

const updateServerFileContent = async (queryClient: QueryClient, uuid: string, file: string, content: string) => {
    await queryClient.cancelQueries({ queryKey: serverFileContentQueryKey(uuid, file) });
    return queryClient.setQueryData<string>(serverFileContentQueryKey(uuid, file), content);
};

const getFileContents = async (uuid: string, file: string, signal: AbortSignal): Promise<string> => {
    const response = await clientGetFileContents({ ...serverFileContentInput(uuid, file), signal });

    return response.data ?? '';
};

const getFileDownloadUrl = async (uuid: string, file: string): Promise<string> => {
    const response = await clientGetFileDownloadUrl({
        path: { server_uuid: uuid },
        query: { file },
    });

    return response.data.attributes.url;
};

const getFileUploadUrl = async (uuid: string, signal?: AbortSignal): Promise<string> => {
    const response = await clientGetFileUploadUrl({ path: { server_uuid: uuid }, signal });

    return response.data.attributes.url;
};

export const fileObjectKey = ({ attributes }: FileObject) =>
    `${attributes.is_file ? 'file' : 'dir'}_${attributes.name}`;

export const isFileObjectArchiveType = ({ attributes }: FileObject) =>
    attributes.is_file &&
    [
        'application/vnd.rar', // .rar
        'application/x-rar-compressed', // .rar
        'application/x-tar', // .tar
        'application/x-br', // .tar.br
        'application/x-bzip2', // .tar.bz2, .bz2
        'application/gzip', // .tar.gz, .gz
        'application/x-gzip',
        'application/x-lzip', // .tar.lz4, .lz4
        'application/x-sz', // .tar.sz, .sz
        'application/x-xz', // .tar.xz, .xz
        'application/zstd', // .tar.zst, .zst
        'application/zip', // .zip
        'application/x-7z-compressed', // .7z
    ].includes(attributes.mimetype);

export const fileObjectKind = (file: FileObject): 'file' | 'directory' | 'archive' | 'symlink' =>
    !file.attributes.is_file
        ? 'directory'
        : file.attributes.is_symlink
          ? 'symlink'
          : isFileObjectArchiveType(file)
            ? 'archive'
            : 'file';

export const isFileObjectEditable = (file: FileObject) => {
    if (isFileObjectArchiveType(file) || !file.attributes.is_file) return false;

    const matches = ['application/jar', 'application/octet-stream', 'inode/directory', /^image\/(?!svg\+xml)/];

    return matches.every((m) => !file.attributes.mimetype.match(m));
};

const generateDirectoryData = (name: string): ClientFileObjectResource => ({
    object: 'file_object',
    attributes: {
        name: newDirectoryDisplayName(name),
        mode: 'drwxr-xr-x',
        mode_bits: '0755',
        size: 0,
        is_file: false,
        is_symlink: false,
        mimetype: '',
        created_at: new Date().toISOString(),
        modified_at: new Date().toISOString(),
    },
});

export const serverFilesQueryOptions = (uuid: string, directory: string) =>
    clientListFilesOptions(serverFilesInput(uuid, directory));

export const useServerFiles = (uuid: string, directory: string) =>
    useQuery({ ...serverFilesQueryOptions(uuid, directory), enabled: uuid.length > 0 });

export const serverFileContentQueryOptions = (uuid: string, file: string) =>
    queryOptions({
        queryKey: serverFileContentQueryKey(uuid, file),
        queryFn: ({ signal }) => getFileContents(uuid, file, signal),
        staleTime: 0,
    });

export const useServerFileContent = (uuid: string, file: string, enabled = true) =>
    useQuery({ ...serverFileContentQueryOptions(uuid, file), enabled: uuid.length > 0 && file.length > 0 && enabled });

export const useCreateDirectory = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientCreateFolderMutation(),
        onSuccess: async (_data, { path, body }) => {
            if (body?.root && body.name) {
                await serverFilesQueryUpdate(queryClient, path.server_uuid, body.root, (files) => ({
                    object: files?.object ?? 'list',
                    data: [...(files?.data ?? []), generateDirectoryData(body.name)],
                }));
            }
            toast.success('Directory created');
        },
        onError: (error) => notifyHttpError(error, 'Unable to create directory'),
    });
};

export const useRenameFiles = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientRenameFilesMutation(),
        onSuccess: async (_data, { path, body }) => {
            if (body?.root && body.files?.length === 1) {
                const [{ from, to }] = body.files;
                const staysInDirectory = to.split('/').length === 1;

                await serverFilesQueryUpdate(queryClient, path.server_uuid, body.root, (current) =>
                    staysInDirectory
                        ? updateListItems(
                              current,
                              (file) => file.attributes.name === from,
                              (file) => ({ ...file, attributes: { ...file.attributes, name: to } })
                          )
                        : removeListItems(current, (file) => file.attributes.name === from)
                );
            }
            await queryClient.invalidateQueries({ queryKey: allServerFilesQueryKey(path.server_uuid) });
            toast.success('Files updated');
        },
        onError: (error) => notifyHttpError(error, 'Unable to update files'),
    });
};

export const useChmodFiles = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientChangeFilePermissionsMutation(),
        onSuccess: async (_data, { path, body }) => {
            if (body?.root && body.files?.length === 1) {
                const [{ file, mode }] = body.files;
                const modeBits = mode.padStart(4, '0');

                await serverFilesQueryUpdate(queryClient, path.server_uuid, body.root, (current) =>
                    updateListItems(
                        current,
                        (item) => item.attributes.name === file,
                        (item) => ({
                            ...item,
                            attributes: {
                                ...item.attributes,
                                mode: fileBitsToString(modeBits, !item.attributes.is_file),
                                mode_bits: modeBits,
                            },
                        })
                    )
                );
            }
            if (body?.root) {
                await queryClient.invalidateQueries({ queryKey: serverFilesQueryKey(path.server_uuid, body.root) });
            }
            toast.success('Permissions updated');
        },
        onError: (error) => notifyHttpError(error, 'Unable to update permissions'),
    });
};

export const useDeleteFiles = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientDeleteFilesMutation(),
        onSuccess: async (_data, { path, body }) => {
            if (body?.root && body.files) {
                const files = body.files;
                await serverFilesQueryUpdate(queryClient, path.server_uuid, body.root, (current) =>
                    removeListItems(current, (file) => files.includes(file.attributes.name))
                );
            }
            toast.success(body?.files?.length === 1 ? 'File deleted' : 'Files deleted');
        },
        onError: (error) => notifyHttpError(error, 'Unable to delete files'),
    });
};

export const useCopyFile = (directory: string) => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientCopyFileMutation(),
        onSuccess: async (_data, { path }) => {
            if (directory) {
                await queryClient.invalidateQueries({ queryKey: serverFilesQueryKey(path.server_uuid, directory) });
            }
            toast.success('File copied');
        },
        onError: (error) => notifyHttpError(error, 'Unable to copy file'),
    });
};

export const useCompressFiles = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientCompressFilesMutation(),
        onSuccess: async (_data, { path, body }) => {
            if (body?.root) {
                await queryClient.invalidateQueries({ queryKey: serverFilesQueryKey(path.server_uuid, body.root) });
            }
            toast.success('Archive started');
        },
        onError: (error) => notifyHttpError(error, 'Unable to archive files'),
    });
};

export const useDecompressFile = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientDecompressFileMutation(),
        onSuccess: async (_data, { path, body }) => {
            if (body?.root) {
                await queryClient.invalidateQueries({ queryKey: serverFilesQueryKey(path.server_uuid, body.root) });
            }
            toast.success('Unarchive started');
        },
        onError: (error) => notifyHttpError(error, 'Unable to unarchive file'),
    });
};

export const useFileDownloadUrl = () =>
    useMutation({
        mutationFn: ({ uuid, file }: { uuid: string; file: string }) => getFileDownloadUrl(uuid, file),
        onError: (error) => notifyHttpError(error, 'Unable to create file download'),
    });

export const useSaveFileContent = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientWriteFileContentsMutation(),
        onSuccess: async (_data, { path, query, body }) => {
            await updateServerFileContent(queryClient, path.server_uuid, query.file, body);
            toast.success('File saved');
        },
        onError: (error) => notifyHttpError(error, 'Unable to save file'),
    });
};

const runPooled = async <T>(items: readonly T[], concurrency: number, task: (item: T) => Promise<void>) => {
    let next = 0;
    const worker = async (): Promise<void> => {
        if (next >= items.length) return;
        await task(items[next++]);
        return worker();
    };

    await Promise.all(Array.from({ length: Math.min(concurrency, items.length) }, worker));
};

const uploadFile = async (
    uuid: string,
    directory: string,
    file: File,
    signal: AbortSignal,
    onUploadProgress: (progress: AxiosProgressEvent) => void
) => {
    const url = await getFileUploadUrl(uuid, signal);
    await axios.post(
        url,
        { files: file },
        {
            signal,
            headers: { 'Content-Type': 'multipart/form-data' },
            params: { directory },
            onUploadProgress,
        }
    );
};

/** Uploads files a few at a time, then refreshes the listing once every file has settled. */
export const useUploadFiles = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async ({
            callbacks,
            directory,
            files,
            uuid,
        }: {
            callbacks: FileUploadCallbacks;
            directory: string;
            files: File[];
            uuid: string;
        }): Promise<FileUploadSummary> => {
            const queued = files.map((file) => {
                const id = `upload-${++uploadSequence}`;
                const controller = new AbortController();
                callbacks.onFileQueued(id, file, controller);

                return { id, file, controller };
            });
            const summary: FileUploadSummary = { uploaded: [], cancelled: [], failed: [] };

            await runPooled(queued, UPLOAD_CONCURRENCY, async ({ id, file, controller }) => {
                let outcome: FileUploadOutcome = 'cancelled';
                if (!controller.signal.aborted) {
                    try {
                        await uploadFile(uuid, directory, file, controller.signal, (progress) =>
                            callbacks.onFileProgress(id, progress)
                        );
                        outcome = 'uploaded';
                    } catch (error) {
                        if (!controller.signal.aborted && !axios.isCancel(error)) {
                            outcome = 'failed';
                            notifyHttpError(error, `Unable to upload ${file.name}`);
                        }
                    }
                }

                summary[outcome].push(file);
                callbacks.onFileSettled(id, outcome);
            });

            return summary;
        },
        onSuccess: ({ uploaded, failed }) => {
            if (uploaded.length > 0 && failed.length === 0) {
                toast.success('Upload complete');
            }
        },
        onError: (error) => notifyHttpError(error, 'Unable to upload files'),
        onSettled: (_data, _error, { uuid, directory }) =>
            queryClient.invalidateQueries({ queryKey: serverFilesQueryKey(uuid, directory) }),
    });
};
