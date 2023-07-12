import type { Action } from 'easy-peasy';
import { action } from 'easy-peasy';

import { cleanDirectoryPath } from '@/helpers';

interface FileUploadData {
    loaded: number;
    readonly abort: AbortController;
    readonly total: number;
}

interface ServerFileStore {
    directory: string;
    selectedFiles: string[];
    uploads: Record<string, FileUploadData>;

    setDirectory: Action<ServerFileStore, string>;
    setSelectedFiles: Action<ServerFileStore, string[]>;
    appendSelectedFile: Action<ServerFileStore, string>;
    removeSelectedFile: Action<ServerFileStore, string>;

    pushFileUpload: Action<ServerFileStore, { name: string; data: FileUploadData }>;
    setUploadProgress: Action<ServerFileStore, { name: string; loaded: number }>;
    clearFileUploads: Action<ServerFileStore>;
    removeFileUpload: Action<ServerFileStore, string>;
    cancelFileUpload: Action<ServerFileStore, string>;
}

const files: ServerFileStore = {
    directory: '/',
    selectedFiles: [],
    uploads: {},

    setDirectory: action((state, payload) => {
        state.directory = cleanDirectoryPath(payload);
    }),

    setSelectedFiles: action((state, payload) => {
        state.selectedFiles = payload;
    }),

    appendSelectedFile: action((state, payload) => {
        state.selectedFiles = state.selectedFiles.filter(f => f !== payload).concat(payload);
    }),

    removeSelectedFile: action((state, payload) => {
        state.selectedFiles = state.selectedFiles.filter(f => f !== payload);
    }),

    clearFileUploads: action(state => {
        Object.values(state.uploads).forEach(upload => upload.abort.abort());

        state.uploads = {};
    }),

    pushFileUpload: action((state, payload) => {
        state.uploads[payload.name] = payload.data;
    }),

    setUploadProgress: action((state, { name, loaded }) => {
        const upload = state.uploads[name];
        if (upload === undefined) {
            return;
        }

        upload.loaded = loaded;
    }),

    removeFileUpload: action((state, payload) => {
        const upload = state.uploads[payload];
        if (upload === undefined) {
            return;
        }

        delete state.uploads[payload];
    }),

    cancelFileUpload: action((state, payload) => {
        const upload = state.uploads[payload];
        if (upload === undefined) {
            return;
        }

        // Abort the request if it is still in flight. If it already completed this is
        // a no-op.
        upload.abort.abort();

        delete state.uploads[payload];
    }),
};

export type { FileUploadData, ServerFileStore };
export default files;
