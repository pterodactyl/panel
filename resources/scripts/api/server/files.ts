import http from '@/api/http';
import { rawDataToFileObject } from '@/api/transformers';

export interface FileObject {
    key: string;
    name: string;
    mode: string;
    modeBits: string,
    size: number;
    isFile: boolean;
    isSymlink: boolean;
    mimetype: string;
    createdAt: Date;
    modifiedAt: Date;
    isArchiveType: () => boolean;
    isEditable: () => boolean;
}

const chmodFiles = (uuid: string, directory: string, files: { file: string; mode: string }[]): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/files/chmod`, { root: directory, files })
            .then(() => resolve())
            .catch(reject);
    });
};

const compressFiles = async (uuid: string, directory: string, files: string[]): Promise<FileObject> => {
    const { data } = await http.post(`/api/client/servers/${uuid}/files/compress`, { root: directory, files }, {
        timeout: 60000,
        timeoutErrorMessage: 'It looks like this archive is taking a long time to generate. It will appear once completed.',
    });

    return rawDataToFileObject(data);
};

const copyFiles = (uuid: string, location: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/files/copy`, { location })
            .then(() => resolve())
            .catch(reject);
    });
};

const createDirectory = (uuid: string, root: string, name: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/files/create-folder`, { root, name })
            .then(() => resolve())
            .catch(reject);
    });
};

const decompressFiles = async (uuid: string, directory: string, file: string): Promise<void> => {
    await http.post(`/api/client/servers/${uuid}/files/decompress`, { root: directory, file }, {
        timeout: 300000,
        timeoutErrorMessage: 'It looks like this archive is taking a long time to be unarchived. Once completed the unarchived files will appear.',
    });
};

const deleteFiles = (uuid: string, directory: string, files: string[]): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/files/delete`, { root: directory, files })
            .then(() => resolve())
            .catch(reject);
    });
};

const getFileContents = (server: string, file: string): Promise<string> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${server}/files/contents`, {
            params: { file },
            transformResponse: res => res,
            responseType: 'text',
        })
            .then(({ data }) => resolve(data))
            .catch(reject);
    });
};

const getFileDownloadUrl = (uuid: string, file: string): Promise<string> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${uuid}/files/download`, { params: { file } })
            .then(({ data }) => resolve(data.attributes.url))
            .catch(reject);
    });
};

const getFileUploadUrl = (uuid: string): Promise<string> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${uuid}/files/upload`)
            .then(({ data }) => resolve(data.attributes.url))
            .catch(reject);
    });
};

const loadDirectory = async (uuid: string, directory?: string): Promise<FileObject[]> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/files/list`, {
        params: { directory: directory ?? '/' },
    });

    return (data.data || []).map(rawDataToFileObject);
};

const pullFile = (uuid: string, directory: string, url: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/files/pull`, { root: directory, url })
            .then(() => resolve())
            .catch(reject);
    });
};

const renameFiles = (uuid: string, directory: string, files: { from: string; to: string }[]): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.put(`/api/client/servers/${uuid}/files/rename`, { root: directory, files })
            .then(() => resolve())
            .catch(reject);
    });
};

const saveFileContents = async (uuid: string, file: string, content: string): Promise<void> => {
    await http.post(`/api/client/servers/${uuid}/files/write`, content, {
        params: { file },
        headers: {
            'Content-Type': 'text/plain',
        },
    });
};

export {
    chmodFiles,
    compressFiles,
    copyFiles,
    createDirectory,
    decompressFiles,
    deleteFiles,
    getFileContents,
    getFileDownloadUrl,
    getFileUploadUrl,
    loadDirectory,
    pullFile,
    renameFiles,
    saveFileContents,
};
