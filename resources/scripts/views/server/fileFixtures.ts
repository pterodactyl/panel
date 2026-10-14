import type { ClientFileObjectResource as FileObject } from '@/api/extensionTypes';
import { fileBreadcrumbs } from '@/components/server/files/breadcrumbs';
import { bindFileEditor, editorLanguages } from '@/components/server/files/fileEditorSession';
import { createTestServer } from '../fixtures';
import type {
    FileDetailsModel,
    FileEditorModel,
    FileEditorPageModel,
    FileManagerEntry,
    FileManagerModel,
} from './files';

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

/** A file editor model; its `Editor` is bound to it, as the editing session binds the panel's. */
export function createTestFileEditor(options: Partial<FileEditorModel> = {}): FileEditorModel {
    const content = options.content ?? 'motd=Hello\n';
    const model: FileEditorModel = {
        path: '/config/server.properties',
        name: 'server.properties',
        isNew: false,
        content,
        language: 'text/x-properties',
        languages: editorLanguages,
        readOnly: false,
        dirty: false,
        saving: false,
        Editor: () => null,
        read: () => content,
        change: () => {},
        setLanguage: () => {},
        save: async () => true,
        saveAs: async () => true,
        ...options,
    };

    return options.Editor ? model : { ...model, Editor: bindFileEditor(model) };
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
