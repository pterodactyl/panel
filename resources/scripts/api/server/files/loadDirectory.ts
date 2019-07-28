import http from '@/api/http';

export interface FileObject {
    name: string;
    mode: string;
    size: number;
    isFile: boolean;
    isSymlink: boolean;
    isEditable: boolean;
    mimetype: string;
    createdAt: Date;
    modifiedAt: Date;
}

export default (uuid: string, directory?: string): Promise<FileObject[]> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${uuid}/files/list`, {
            params: { directory },
        })
            .then(response => resolve((response.data.data || []).map((item: any): FileObject => ({
                name: item.attributes.name,
                mode: item.attributes.mode,
                size: Number(item.attributes.size),
                isFile: item.attributes.is_file,
                isSymlink: item.attributes.is_symlink,
                isEditable: item.attributes.is_editable,
                mimetype: item.attributes.mimetype,
                createdAt: new Date(item.attributes.created_at),
                modifiedAt: new Date(item.attributes.modified_at),
            }))))
            .catch(reject);
    });
};
