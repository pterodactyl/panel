import http, { FractalResponseData } from '@/api/http';

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

const rawDataToFileObject = (data: FractalResponseData): FileObject => ({
    key: `${data.attributes.is_file ? 'file' : 'dir'}_${data.attributes.name}`,
    name: data.attributes.name,
    mode: data.attributes.mode,
    modeBits: data.attributes.mode_bits,
    size: Number(data.attributes.size),
    isFile: data.attributes.is_file,
    isSymlink: data.attributes.is_symlink,
    mimetype: data.attributes.mimetype,
    createdAt: new Date(data.attributes.created_at),
    modifiedAt: new Date(data.attributes.modified_at),

    isArchiveType: function () {
        return this.isFile && [
            'application/vnd.rar', // .rar
            'application/x-rar-compressed', // .rar (2)
            'application/x-tar', // .tar
            'application/x-br', // .tar.br
            'application/x-bzip2', // .tar.bz2, .bz2
            'application/gzip', // .tar.gz, .gz
            'application/x-gzip',
            'application/x-lzip', // .tar.lz4, .lz4 (not sure if this mime type is correct)
            'application/x-sz', // .tar.sz, .sz (not sure if this mime type is correct)
            'application/x-xz', // .tar.xz, .xz
            'application/zstd', // .tar.zst, .zst
            'application/zip', // .zip
        ].indexOf(this.mimetype) >= 0;
    },

    isEditable: function () {
        if (this.isArchiveType() || !this.isFile) return false;

        const matches = [
            'application/jar',
            'application/octet-stream',
            'inode/directory',
            /^image\//,
        ];

        return matches.every(m => !this.mimetype.match(m));
    },
});

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
