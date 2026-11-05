import { useCallback, useEffect, useRef } from 'react';
import { toast } from 'sonner';
import { useCurrentServer, useCurrentServerUuid, type Server } from '@/api/server/queries';
import { useUploadFiles } from '@/api/server/files/queries';
import { useFileUploadActions, useServerDirectory } from '@/state/server';
import type { UploadSource } from '@/views/server/files';

const UPLOADED_DISPLAY_MS = 500;

// A dropped folder arrives as a File too; only its entry says it is a directory.
const holdsFolder = (source: DataTransfer) =>
    [...source.items].some((item) => item.kind === 'file' && item.webkitGetAsEntry()?.isDirectory);

const selectUploadSize = (server: Server) => server.attributes.upload_size;

/** Uploads into the current directory; rejects when a folder was dropped, a file is over the node's upload limit or any file failed, after its error toast. */
export default function useFileUploader(): (source: UploadSource) => Promise<void> {
    const removals = useRef(new Map<string, ReturnType<typeof setTimeout>>());
    const { mutateAsync } = useUploadFiles();
    const uuid = useCurrentServerUuid()!;
    const uploadSize = useCurrentServer(selectUploadSize)!;
    const directory = useServerDirectory();
    const { removeFileUpload, pushFileUpload, setUploadProgress } = useFileUploadActions();

    useEffect(() => {
        const pending = removals.current;

        return () => {
            for (const [id, timeout] of pending) {
                clearTimeout(timeout);
                removeFileUpload(id);
            }

            pending.clear();
        };
    }, [removeFileUpload]);

    return useCallback(
        async (source) => {
            const dropped = 'items' in source;

            if (dropped && holdsFolder(source)) {
                toast.error('Folder uploads are not supported.');
                throw new Error('Folder uploads are not supported.');
            }

            const files = dropped ? [...source.files] : [...source];

            if (files.length === 0) {
                return;
            }

            const oversized = files.filter((file) => file.size > uploadSize * 1024 * 1024);

            if (oversized.length > 0) {
                const message =
                    oversized.length === 1
                        ? `${oversized[0].name} is larger than the ${uploadSize} MiB upload limit.`
                        : `${oversized.length} files are larger than the ${uploadSize} MiB upload limit.`;

                toast.error(message);
                throw new Error(message);
            }

            const { failed } = await mutateAsync({
                uuid,
                directory,
                files,
                callbacks: {
                    onFileQueued: (id, file, abort) => {
                        pushFileUpload({ id, data: { name: file.name, abort, loaded: 0, total: file.size } });
                    },
                    onFileProgress: (id, progress) => setUploadProgress({ id, loaded: progress.loaded }),
                    onFileSettled: (id, outcome) => {
                        if (outcome !== 'uploaded') {
                            removeFileUpload(id);

                            return;
                        }

                        const timeout = setTimeout(() => {
                            removals.current.delete(id);
                            removeFileUpload(id);
                        }, UPLOADED_DISPLAY_MS);

                        removals.current.set(id, timeout);
                    },
                },
            });

            if (failed.length > 0) {
                throw new Error(
                    failed.length === 1
                        ? `Unable to upload ${failed[0].name}.`
                        : `Unable to upload ${failed.length} files.`
                );
            }
        },
        [mutateAsync, uuid, uploadSize, directory, removeFileUpload, pushFileUpload, setUploadProgress]
    );
}
