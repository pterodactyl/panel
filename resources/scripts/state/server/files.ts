import type { ServerSet, ServerGet } from '@/state/server/types';

export interface FileUploadData {
    readonly name: string;
    loaded: number;
    readonly abort: AbortController;
    readonly total: number;
}

export interface ServerFileStore {
    selectedDirectory: string | null;
    selectedFiles: string[];
    uploads: Record<string, FileUploadData>;

    clearSelectedFiles: () => void;
    setSelectedFiles: (payload: { directory: string; files: string[] }) => void;
    appendSelectedFile: (payload: { directory: string; name: string }) => void;
    removeSelectedFile: (payload: { directory: string; name: string }) => void;

    pushFileUpload: (payload: { id: string; data: FileUploadData }) => void;
    setUploadProgress: (payload: { id: string; loaded: number }) => void;
    /** Aborts and forgets every upload. */
    clearFileUploads: () => void;
    removeFileUpload: (id: string) => void;
    /** Aborts and forgets one upload. */
    cancelFileUpload: (id: string) => void;
}

const withoutUpload = (uploads: Record<string, FileUploadData>, id: string) => {
    const { [id]: _removed, ...rest } = uploads;

    return rest;
};

export const createFiles = (set: ServerSet, get: ServerGet): ServerFileStore => ({
    selectedDirectory: null,
    selectedFiles: [],
    uploads: {},

    clearSelectedFiles: () =>
        set((state) => ({
            files: {
                ...state.files,
                selectedDirectory: null,
                selectedFiles: [],
            },
        })),

    setSelectedFiles: ({ directory, files }) =>
        set((state) => ({
            files: {
                ...state.files,
                selectedDirectory: directory,
                selectedFiles: files,
            },
        })),

    appendSelectedFile: ({ directory, name }) =>
        set((state) => ({
            files: {
                ...state.files,
                selectedDirectory: directory,
                selectedFiles:
                    state.files.selectedDirectory === directory
                        ? [...state.files.selectedFiles.filter((file) => file !== name), name]
                        : [name],
            },
        })),

    removeSelectedFile: ({ directory, name }) =>
        set((state) => ({
            files: {
                ...state.files,
                selectedDirectory: directory,
                selectedFiles:
                    state.files.selectedDirectory === directory
                        ? state.files.selectedFiles.filter((file) => file !== name)
                        : [],
            },
        })),

    clearFileUploads: () => {
        for (const upload of Object.values(get().files.uploads)) {
            upload.abort.abort();
        }

        set((state) => ({
            files: {
                ...state.files,
                uploads: {},
            },
        }));
    },

    pushFileUpload: ({ id, data }) =>
        set((state) => ({
            files: {
                ...state.files,
                uploads: {
                    ...state.files.uploads,
                    [id]: data,
                },
            },
        })),

    setUploadProgress: ({ id, loaded }) =>
        set((state) => {
            const upload = state.files.uploads[id];

            if (!upload) {
                return state;
            }

            return {
                files: {
                    ...state.files,
                    uploads: {
                        ...state.files.uploads,
                        [id]: {
                            ...upload,
                            loaded,
                        },
                    },
                },
            };
        }),

    removeFileUpload: (id) =>
        set((state) => {
            if (!state.files.uploads[id]) {
                return state;
            }

            return {
                files: {
                    ...state.files,
                    uploads: withoutUpload(state.files.uploads, id),
                },
            };
        }),

    cancelFileUpload: (id) => {
        const upload = get().files.uploads[id];

        if (upload) {
            upload.abort.abort();

            set((state) => ({
                files: {
                    ...state.files,
                    uploads: withoutUpload(state.files.uploads, id),
                },
            }));
        }
    },
});
