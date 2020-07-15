import { Allocation } from '@/api/server/getServer';
import { FractalResponseData } from '@/api/http';
import { FileObject } from '@/api/server/files/loadDirectory';
import v4 from 'uuid/v4';

export const rawDataToServerAllocation = (data: FractalResponseData): Allocation => ({
    id: data.attributes.id,
    ip: data.attributes.ip,
    alias: data.attributes.ip_alias,
    port: data.attributes.port,
    notes: data.attributes.notes,
    isDefault: data.attributes.is_default,
});

export const rawDataToFileObject = (data: FractalResponseData): FileObject => ({
    uuid: v4(),
    name: data.attributes.name,
    mode: data.attributes.mode,
    size: Number(data.attributes.size),
    isFile: data.attributes.is_file,
    isSymlink: data.attributes.is_symlink,
    isEditable: data.attributes.is_editable,
    mimetype: data.attributes.mimetype,
    createdAt: new Date(data.attributes.created_at),
    modifiedAt: new Date(data.attributes.modified_at),

    isArchiveType: function () {
        return this.isFile && [
            'application/zip',
            'application/gzip',
            'application/x-tar',
        ].indexOf(this.mimetype) >= 0;
    },
});
