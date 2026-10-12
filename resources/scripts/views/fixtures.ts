import type { ClientFileObjectResource as FileObject } from '@/api/extensionTypes';
import type { Server } from '@/api/server/types';
import type { ServerCardModel } from './dashboard';
import type { FileDetailsModel, FileEditorModel, FileManagerEntry, FileManagerModel } from './files';

export interface TestServerOptions {
    identifier?: string;
    uuid?: string;
    name?: string;
    owner?: boolean;
    permissions?: string[];
    status?: Server['attributes']['status'];
    eggFeatures?: string[];
    eggTags?: string[];
}

/** A server payload for fixtures and tests. */
export function createTestServer(options: TestServerOptions = {}): Server {
    const identifier = options.identifier ?? 'test-server';
    const owner = options.owner ?? true;

    return {
        object: 'server',
        attributes: {
            identifier,
            uuid: options.uuid ?? '11111111-1111-4111-8111-111111111111',
            __deprecated_uuid_short: identifier,
            server_identifier: identifier,
            internal_id: 1,
            server_owner: owner,
            name: options.name ?? 'Test server',
            node: 'Test node',
            is_node_under_maintenance: false,
            sftp_details: { ip: '127.0.0.1', port: 2022 },
            description: null,
            limits: { memory: 1024, swap: 0, disk: 1024, io: 500, cpu: 100, threads: null, oom_disabled: true },
            invocation: 'run',
            docker_image: 'test/image',
            egg_features: options.eggFeatures ?? [],
            egg_tags: options.eggTags ?? [],
            feature_limits: { databases: 1, allocations: 1, backups: 1 },
            status: options.status ?? null,
            is_suspended: options.status === 'suspended',
            is_installing: options.status === 'installing',
            is_transferring: false,
            skip_scripts: false,
        },
        meta: { is_server_owner: owner, user_permissions: options.permissions ?? [] },
    };
}

export function createTestServerCard(options: Partial<ServerCardModel> = {}): ServerCardModel {
    return {
        identifier: 'test-server',
        uuid: '11111111-1111-4111-8111-111111111111',
        name: 'Test server',
        description: null,
        address: '127.0.0.1:25565',
        state: {
            kind: 'ready',
            power: 'running',
            cpu: { value: 12, limit: 100, alarm: false },
            memory: { value: 256 * 1024 * 1024, limit: 1024 * 1024 * 1024, alarm: false },
            disk: { value: 512 * 1024 * 1024, limit: 2048 * 1024 * 1024, alarm: false },
        },
        ...options,
    };
}

export function createTestFileDetails(options: Partial<FileDetailsModel> = {}): FileDetailsModel {
    return { name: 'config.json', kind: 'file', size: 1024, modifiedAt: '2026-01-01T12:00:00Z', ...options };
}

/** The file object a file details model was read from. */
export function createTestFileObject(model: FileDetailsModel): FileObject {
    const directory = model.kind === 'directory';

    return {
        object: 'file_object',
        attributes: {
            name: model.name,
            mode: directory ? 'drwxr-xr-x' : '-rw-r--r--',
            mode_bits: directory ? '0755' : '0644',
            size: model.size,
            is_file: !directory,
            is_symlink: model.kind === 'symlink',
            mimetype: directory ? 'inode/directory' : 'text/plain',
            created_at: model.modifiedAt,
            modified_at: model.modifiedAt,
        },
    };
}

export function createTestFileEditor(options: Partial<FileEditorModel> = {}): FileEditorModel {
    return {
        path: '/config/server.properties',
        name: 'server.properties',
        isNew: false,
        content: 'motd=Hello\n',
        language: 'text/x-properties',
        readOnly: false,
        dirty: false,
        saving: false,
        change: () => {},
        save: async () => true,
        saveAs: async () => true,
        ...options,
    };
}

export function createTestFileManagerEntry(options: Partial<FileManagerEntry> = {}): FileManagerEntry {
    const name = options.name ?? 'config.json';

    return {
        name,
        path: `/${name}`,
        kind: 'file',
        size: 1024,
        mimetype: 'application/json',
        mode: '-rw-r--r--',
        modeBits: '0644',
        modifiedAt: '2026-01-01T12:00:00Z',
        openable: true,
        ...options,
    };
}

export function createTestFileManager(options: Partial<FileManagerModel> = {}): FileManagerModel {
    return {
        directory: '/',
        entries: [
            createTestFileManagerEntry({ name: 'plugins', kind: 'directory', mimetype: 'inode/directory', size: 0 }),
            createTestFileManagerEntry(),
        ],
        truncated: false,
        loading: false,
        refreshing: false,
        selection: [],
        permissions: { create: true, update: true, delete: true, archive: true },
        actions: {
            open: async () => {},
            navigate: async () => {},
            newFile: async () => {},
            select: () => {},
            refresh: async () => {},
            createDirectory: async () => {},
            rename: async () => {},
            remove: async () => {},
            copy: async () => {},
            archive: async () => {},
            extract: async () => {},
            chmod: async () => {},
            download: async () => {},
            upload: async () => {},
        },
        ...options,
    };
}
