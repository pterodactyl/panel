import { action, Action } from 'easy-peasy';
import { cleanDirectoryPath } from '@/helpers';

export interface ServerFileStore {
    directory: string;
    setDirectory: Action<ServerFileStore, string>;
}

const files: ServerFileStore = {
    directory: '/',

    setDirectory: action((state, payload) => {
        state.directory = cleanDirectoryPath(payload);
    }),
};

export default files;
