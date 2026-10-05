import { isObject } from '@/lib/objects';
import type { ComponentType } from 'react';

export const COMPONENT_NAMES = [
    'dashboard.serverCard',
    'server.files.details',
    'server.files.editor',
    'server.files.manager',
] as const;
export type ComponentName = (typeof COMPONENT_NAMES)[number];

export interface ServerCardMetric {
    readonly value: number;
    readonly limit: number;
    readonly alarm: boolean;
}
export type ServerCardState =
    | { readonly kind: 'loading' }
    | {
          readonly kind: 'unavailable';
          readonly reason:
              | 'suspended'
              | 'connection-error'
              | 'maintenance'
              | 'transferring'
              | 'installing'
              | 'restoring-backup'
              | 'unavailable';
      }
    | {
          readonly kind: 'ready';
          readonly power: 'offline' | 'stopped' | 'starting' | 'running' | 'stopping';
          readonly cpu: ServerCardMetric;
          readonly memory: ServerCardMetric;
          readonly disk: ServerCardMetric;
      };
export interface ServerCardModel {
    readonly identifier: string;
    readonly uuid: string;
    readonly name: string;
    readonly description: string | null;
    readonly address: string | null;
    readonly state: ServerCardState;
}
export interface FileDetailsModel {
    readonly name: string;
    readonly kind: 'file' | 'directory' | 'archive' | 'symlink';
    readonly size: number;
    readonly modifiedAt: string;
}
/** Core loads the document, tracks the reported buffer, writes it, and guards unsaved changes. */
export interface FileEditorModel {
    /** Absolute path of the open file; the target directory while a new file has no name. */
    readonly path: string;
    /** File name, or an empty string for a new file. */
    readonly name: string;
    readonly isNew: boolean;
    /** Text to open: the file as loaded, or the restored draft of a new file. */
    readonly content: string;
    /** Syntax hint as a MIME type, for example "text/x-yaml". */
    readonly language: string;
    /** The user may not write this file; save and saveAs resolve false. */
    readonly readOnly: boolean;
    readonly dirty: boolean;
    readonly saving: boolean;
    /** Report the buffer after every edit; core derives dirty from it and saves this text. */
    readonly change: (content: string) => void;
    /** Write the reported buffer in place; a new file asks for its name first. Resolves whether it was saved. */
    readonly save: () => Promise<boolean>;
    /** Write the reported buffer to a name beside the open file, or to an absolute path, and open it. */
    readonly saveAs: (name: string) => Promise<boolean>;
}
export interface FileManagerEntry {
    readonly name: string;
    readonly path: string;
    readonly kind: FileDetailsModel['kind'];
    readonly size: number;
    readonly mimetype: string;
    /** Symbolic mode such as "-rw-r--r--". */
    readonly mode: string;
    /** Octal mode such as "0644". */
    readonly modeBits: string;
    readonly modifiedAt: string;
    /** open() leads somewhere: a directory, or a text file this user may read. */
    readonly openable: boolean;
}
export interface FileManagerPermissions {
    readonly create: boolean;
    readonly update: boolean;
    readonly delete: boolean;
    readonly archive: boolean;
}
/** Names are entries of the current directory. Core has already shown a toast for a rejection. */
export interface FileManagerActions {
    /** Enter a directory or open a text file in the editor. */
    readonly open: (name: string) => Promise<void>;
    /** Show another directory, given as an absolute path or relative to the current one. */
    readonly navigate: (directory: string) => Promise<void>;
    /** Open the editor for a new file in the current directory. */
    readonly newFile: () => Promise<void>;
    readonly select: (names: readonly string[]) => void;
    readonly refresh: () => Promise<void>;
    readonly createDirectory: (name: string) => Promise<void>;
    /** Rename in place, or move by giving a path relative to the current directory. */
    readonly rename: (files: readonly { readonly from: string; readonly to: string }[]) => Promise<void>;
    /** Delete without a confirmation; ask the user first. */
    readonly remove: (names: readonly string[]) => Promise<void>;
    readonly copy: (name: string) => Promise<void>;
    readonly archive: (names: readonly string[]) => Promise<void>;
    readonly extract: (name: string) => Promise<void>;
    readonly chmod: (files: readonly { readonly file: string; readonly mode: string }[]) => Promise<void>;
    readonly download: (name: string) => Promise<void>;
    readonly upload: (files: readonly File[]) => Promise<void>;
}
/** Core queries the directory, owns the selection, and runs every mutation and navigation. */
export interface FileManagerModel {
    readonly directory: string;
    /** Directories first, then files, by name; at most 250 entries. */
    readonly entries: readonly FileManagerEntry[];
    /** The directory holds more entries than the panel lists. */
    readonly truncated: boolean;
    /** The directory has not been listed yet. */
    readonly loading: boolean;
    readonly refreshing: boolean;
    readonly selection: readonly string[];
    readonly permissions: FileManagerPermissions;
    readonly actions: FileManagerActions;
}
export interface ComponentModels {
    'dashboard.serverCard': ServerCardModel;
    'server.files.details': FileDetailsModel;
    'server.files.editor': FileEditorModel;
    'server.files.manager': FileManagerModel;
}
export interface ComponentPartNames {
    'dashboard.serverCard': 'identity' | 'address' | 'metrics';
    'server.files.details': 'icon' | 'name' | 'size' | 'modified';
    'server.files.editor': 'notice' | 'editor' | 'language' | 'actions';
    'server.files.manager': 'toolbar' | 'list' | 'selection';
}
export type ComponentPartProps<TName extends ComponentName> = { readonly model: ComponentModels[TName] };
export type ComponentParts<TName extends ComponentName> = {
    readonly [TPart in ComponentPartNames[TName]]: ComponentType<ComponentPartProps<TName>>;
};
export interface DefaultComponentProps<TName extends ComponentName> {
    className?: string;
    parts?: Partial<ComponentParts<TName>>;
}
export interface ReplacementProps<TName extends ComponentName> extends ComponentPartProps<TName> {
    readonly Default: ComponentType<DefaultComponentProps<TName>>;
    readonly parts: ComponentParts<TName>;
}
export type ReplacementImporter<TName extends ComponentName> = () => Promise<{
    default: ComponentType<ReplacementProps<TName>>;
}>;
export type ComponentReplacement<TName extends ComponentName> = TName extends ComponentName
    ? ComponentType<ReplacementProps<TName>> | { load: ReplacementImporter<TName> }
    : never;

export function isComponentName(name: string): name is ComponentName {
    return (COMPONENT_NAMES as readonly string[]).includes(name);
}

export function isReplacementComponent<T>(value: T): value is T & ComponentType<never> {
    return (
        value instanceof Function ||
        (isObject(value) &&
            '$$typeof' in value &&
            (value.$$typeof === Symbol.for('react.memo') || value.$$typeof === Symbol.for('react.forward_ref')))
    );
}
