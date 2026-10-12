import type { ClientFileObjectResource as FileObject } from '@/api/extensionTypes';
import type { Server } from '@/api/server/types';
import { fileBreadcrumbs } from '@/components/server/files/breadcrumbs';
import { editorLanguages, FileEditorCanvas } from '@/components/server/files/fileEditorSession';
import type { ServerCardModel } from './dashboard';
import type {
    FileDetailsModel,
    FileEditorModel,
    FileEditorPageModel,
    FileManagerEntry,
    FileManagerModel,
} from './server/files';

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

const TEST_SERVER = 'test-server';

export function createTestFileEditor(options: Partial<FileEditorModel> = {}): FileEditorModel {
    const content = options.content ?? 'motd=Hello\n';

    return {
        path: '/config/server.properties',
        name: 'server.properties',
        isNew: false,
        content,
        language: 'text/x-properties',
        languages: editorLanguages,
        readOnly: false,
        dirty: false,
        saving: false,
        Editor: FileEditorCanvas,
        read: () => content,
        change: () => {},
        setLanguage: () => {},
        save: async () => true,
        saveAs: async () => true,
        ...options,
    };
}

export function createTestFileEditorPage(options: Partial<FileEditorPageModel> = {}): FileEditorPageModel {
    return {
        breadcrumbs: fileBreadcrumbs(TEST_SERVER, '/config', true),
        file: 'server.properties',
        loading: false,
        error: null,
        back: () => {},
        ...options,
    };
}

export function createTestFileManagerEntry(options: Partial<FileManagerEntry> = {}): FileManagerEntry {
    const name = options.name ?? 'config.json';
    const kind = options.kind ?? 'file';
    const path = options.path ?? `/${name}`;
    const details = {
        name,
        kind,
        size: options.size ?? 1024,
        modifiedAt: options.modifiedAt ?? '2026-01-01T12:00:00Z',
    };
    const file = createTestFileObject(details);

    return {
        ...details,
        path,
        mimetype: kind === 'directory' ? 'inode/directory' : 'application/json',
        mode: file.attributes.mode ?? '',
        modeBits: file.attributes.mode_bits ?? '',
        openable: true,
        extractable: kind === 'archive',
        destination:
            kind === 'directory'
                ? { to: '/server/$id/files', params: { id: TEST_SERVER }, hash: path }
                : { to: '/server/$id/files/$action', params: { id: TEST_SERVER, action: 'edit' }, hash: path },
        file,
        ...options,
    };
}

export function createTestFileManager(options: Partial<FileManagerModel> = {}): FileManagerModel {
    const directory = options.directory ?? '/';
    const entries = options.entries ?? [
        createTestFileManagerEntry({ name: 'plugins', kind: 'directory', size: 0 }),
        createTestFileManagerEntry(),
    ];
    const selection = options.selection ?? [];

    return {
        directory,
        breadcrumbs: fileBreadcrumbs(TEST_SERVER, directory, false),
        entries,
        truncated: false,
        loading: false,
        refreshing: false,
        selection,
        permissions: { create: true, update: true, delete: true, archive: true },
        newFileDestination: {
            to: '/server/$id/files/$action',
            params: { id: TEST_SERVER, action: 'new' },
            hash: directory,
        },
        uploads: [],
        resolve: (name) => `${directory}/${name}`.replace(/^\/+/, ''),
        slot: {
            server: createTestServer(),
            directory,
            files: entries.map((entry) => entry.file),
            selectedFiles: selection,
            isFetching: false,
            setSelectedFiles: () => {},
            refresh: async () => {},
        },
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
            move: async () => {},
            cancelUpload: () => {},
            clearUploads: () => {},
        },
        ...options,
    };
}
