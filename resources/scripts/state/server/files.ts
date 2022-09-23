import { action, Action } from 'easy-peasy';
import { cleanDirectoryPath } from '@/helpers';

export interface FileUpload {
    name: string;
    loaded: number;
    readonly total: number;
}

export interface ServerFileStore {
    directory: string;
    selectedFiles: string[];
    uploads: FileUpload[];

    setDirectory: Action<ServerFileStore, string>;
    setSelectedFiles: Action<ServerFileStore, string[]>;
    appendSelectedFile: Action<ServerFileStore, string>;
    removeSelectedFile: Action<ServerFileStore, string>;

    clearFileUploads: Action<ServerFileStore>;
    appendFileUpload: Action<ServerFileStore, FileUpload>;
    removeFileUpload: Action<ServerFileStore, string>;
}

const files: ServerFileStore = {
    directory: '/',
    selectedFiles: [],
    uploads: [],

    setDirectory: action((state, payload) => {
        state.directory = cleanDirectoryPath(payload);
    }),

    setSelectedFiles: action((state, payload) => {
        state.selectedFiles = payload;
    }),

    appendSelectedFile: action((state, payload) => {
        state.selectedFiles = state.selectedFiles.filter((f) => f !== payload).concat(payload);
    }),

    removeSelectedFile: action((state, payload) => {
        state.selectedFiles = state.selectedFiles.filter((f) => f !== payload);
    }),

    clearFileUploads: action((state) => {
        state.uploads = [];
    }),

    appendFileUpload: action((state, payload) => {
        if (!state.uploads.some(({ name }) => name === payload.name)) {
            state.uploads = [...state.uploads, payload];
        } else {
            state.uploads = state.uploads.map((file) => (file.name === payload.name ? payload : file));
        }
    }),

    removeFileUpload: action((state, payload) => {
        state.uploads = state.uploads.filter(({ name }) => name !== payload);
    }),
};

export default files;
