import { action, Action } from 'easy-peasy';
import { cleanDirectoryPath } from '@/helpers';

export interface ServerFileStore {
    directory: string;
    selectedFiles: string[];

    setDirectory: Action<ServerFileStore, string>;
    setSelectedFiles: Action<ServerFileStore, string[]>;
}

const files: ServerFileStore = {
    directory: '/',
    selectedFiles: [],

    setDirectory: action((state, payload) => {
        state.directory = cleanDirectoryPath(payload);
    }),

    setSelectedFiles: action((state, payload) => {
        state.selectedFiles = payload;
    }),
};

export default files;
