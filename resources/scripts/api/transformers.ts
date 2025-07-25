import { Allocation } from '@/api/server/getServer';
import { FractalResponseData } from '@/api/http';
import { FileObject } from '@/api/server/files/loadDirectory';
import { ServerBackup, ServerEggVariable } from '@/api/server/types';

export const rawDataToServerAllocation = (data: FractalResponseData): Allocation => ({
    id: data.attributes.id,
    ip: data.attributes.ip,
    alias: data.attributes.ip_alias,
    port: data.attributes.port,
    notes: data.attributes.notes,
    isDefault: data.attributes.is_default,
});

export const rawDataToFileObject = (data: FractalResponseData): FileObject => ({
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
        return (
            this.isFile &&
            [
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
                'application/x-7z-compressed', // .7z
            ].indexOf(this.mimetype) >= 0
        );
    },

    isEditable: function () {
        if (this.isArchiveType() || !this.isFile) return false;

        const matches = ['application/jar', 'application/octet-stream', 'inode/directory', /^image\/(?!svg\+xml)/];

        return matches.every((m) => !this.mimetype.match(m));
    },
});

export const rawDataToServerBackup = ({ attributes }: FractalResponseData): ServerBackup => ({
    uuid: attributes.uuid,
    isSuccessful: attributes.is_successful,
    isLocked: attributes.is_locked,
    name: attributes.name,
    ignoredFiles: attributes.ignored_files,
    checksum: attributes.checksum,
    bytes: attributes.bytes,
    createdAt: new Date(attributes.created_at),
    completedAt: attributes.completed_at ? new Date(attributes.completed_at) : null,
});

export const rawDataToServerEggVariable = ({ attributes }: FractalResponseData): ServerEggVariable => ({
    name: attributes.name,
    description: attributes.description,
    envVariable: attributes.env_variable,
    defaultValue: attributes.default_value,
    serverValue: attributes.server_value,
    isEditable: attributes.is_editable,
    rules: attributes.rules.split('|'),
});
