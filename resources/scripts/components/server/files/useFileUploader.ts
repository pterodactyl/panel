import { useCallback, useEffect, useRef } from 'react';
import { toast } from 'sonner';
import { useCurrentServerUuid } from '@/api/server/queries';
import { useUploadFiles } from '@/api/server/files/queries';
import { useFileUploadActions, useServerDirectory } from '@/state/server';

const UPLOADED_DISPLAY_MS = 500;

/** Uploads into the current directory; rejects when any file failed, after its error toast. */
export default function useFileUploader(): (files: readonly File[]) => Promise<void> {
    const removals = useRef(new Map<string, ReturnType<typeof setTimeout>>());
    const { mutateAsync } = useUploadFiles();
    const uuid = useCurrentServerUuid()!;
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
        async (files) => {
            if (files.some((file) => !file.type && (!file.size || file.size === 4096))) {
                toast.error('Folder uploads are not supported.');
                throw new Error('Folder uploads are not supported.');
            }

            const { failed } = await mutateAsync({
                uuid,
                directory,
                files: [...files],
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
        [mutateAsync, uuid, directory, removeFileUpload, pushFileUpload, setUploadProgress]
    );
}
