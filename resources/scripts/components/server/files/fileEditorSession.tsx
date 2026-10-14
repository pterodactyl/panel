import { createContext, use, useCallback, useMemo, useRef, useState, type ComponentType, type ReactNode } from 'react';
import { basename, dirname, join, normalize } from 'pathe';
import { useLocation, useNavigate, useParams, useRouter } from '@tanstack/react-router';
import { httpErrorToHuman } from '@/api/http';
import { useSaveFileContent, useServerFileContent, writeFileContentsInput } from '@/api/server/files/queries';
import { useCurrentServer } from '@/api/server/queries';
import CodemirrorEditor from '@/components/elements/LazyCodemirrorEditor';
import { useDialogState } from '@/components/elements/dialog';
import { encodePathSegments, hashToPath } from '@/helpers';
import { clearNewFileDraft, readNewFileDraft, writeNewFileDraft } from '@/lib/fileDrafts';
import modes from '@/modes';
import { useDebouncedCallback } from '@/plugins/useDebouncedCallback';
import { useNavigationBlocker } from '@/plugins/useNavigationBlocker';
import { usePermissions } from '@/plugins/usePermissions';
import type { FileEditorCanvasProps, FileEditorModel, FileEditorPageModel } from '@/views/server/files';
import { fileBreadcrumbs } from './breadcrumbs';
import FileNameModal from './FileNameModal';

const FileEditorPageContext = createContext<FileEditorPageModel | null>(null);
const FileEditorSessionContext = createContext<FileEditorModel | null>(null);

const findModeByFilename = (filename: string) => {
    const match = modes.find((mode) => mode.file?.test(filename));
    const dot = filename.lastIndexOf('.');
    const ext = dot > -1 ? filename.slice(dot + 1) : undefined;

    return match ?? (ext ? modes.find((mode) => mode.ext?.includes(ext)) : undefined);
};

export const modeForFile = (filename: string) => findModeByFilename(filename)?.mime || 'text/plain';

export const editorLanguages: FileEditorModel['languages'] = modes.map(({ mime, name }) => ({ mime, name }));

/** The code editor of the open document; a remounted one starts from the live buffer. */
export function FileEditorCanvas({ className }: FileEditorCanvasProps) {
    const model = use(FileEditorSessionContext);

    if (!model) {
        throw new Error('The file editor renders inside an open file editing session.');
    }

    const [initialContent] = useState(model.read);

    return (
        <CodemirrorEditor
            className={className}
            mode={model.language}
            initialContent={initialContent}
            onContentChanged={model.change}
            onContentSaved={() => void model.save()}
        />
    );
}

/** The editor canvas bound to a model that has no editing session around it, such as a fixture's. */
export function bindFileEditor(model: FileEditorModel): ComponentType<FileEditorCanvasProps> {
    return function BoundFileEditor(props: FileEditorCanvasProps) {
        return (
            <FileEditorSessionContext.Provider value={model}>
                <FileEditorCanvas {...props} />
            </FileEditorSessionContext.Provider>
        );
    };
}

export function useFileEditorPage(): FileEditorPageModel {
    const page = use(FileEditorPageContext);

    if (!page) {
        throw new Error('The file editor page renders inside its route.');
    }

    return page;
}

export function useFileEditorSession(): FileEditorModel {
    const model = use(FileEditorSessionContext);

    if (!model) {
        throw new Error('The file editor renders inside an open file editing session.');
    }

    return model;
}

interface FileEditSessionProps {
    id: string;
    uuid: string;
    file: string;
    isNew: boolean;
    content: string;
    children?: ReactNode;
}

// A new file's draft stays in session storage, so leaving it needs no confirmation.
const keepDraft = () => true;

function FileEditSession({ id, uuid, file, isNew, content, children }: FileEditSessionProps) {
    const navigate = useNavigate();
    const saveFileContent = useSaveFileContent();
    const fileNameDialog = useDialogState();
    const [canUpdate, canCreate] = usePermissions(['file.update', 'file.create']);
    const readOnly = !(isNew ? canCreate : canUpdate);

    const buffer = useRef(content);
    const saved = useRef(isNew ? '' : content);
    const naming = useRef<((saved: boolean) => void) | null>(null);
    const [dirty, setDirty] = useState(isNew && !readOnly && content !== '');
    const [saving, setSaving] = useState(false);
    const [language, setLanguage] = useState(() => modeForFile(file));
    const saveDraft = useDebouncedCallback((value: string) => writeNewFileDraft(uuid, file, value), 300);

    useNavigationBlocker(dirty, isNew ? { confirm: keepDraft } : undefined);

    const change = useCallback(
        (value: string) => {
            buffer.current = value;
            if (isNew) {
                saveDraft(value);
            }

            setDirty(!readOnly && value !== saved.current);
        },
        [isNew, readOnly, saveDraft]
    );
    const write = useCallback(
        async (target: string): Promise<boolean> => {
            const value = buffer.current;

            setSaving(true);
            try {
                await saveFileContent.mutateAsync(writeFileContentsInput(uuid, target, value));
            } catch {
                // Error toast is handled by the mutation.
                return false;
            } finally {
                setSaving(false);
            }

            if (!isNew && target === file) {
                saved.current = value;
                setDirty(buffer.current !== value);

                return true;
            }

            if (isNew) {
                saveDraft.cancel();
                clearNewFileDraft(uuid, file);
            }

            await navigate({
                to: '/server/$id/files/$action',
                params: { id, action: 'edit' },
                hash: encodePathSegments(target),
                ignoreBlocker: true,
            });

            return true;
        },
        [saveFileContent, navigate, saveDraft, id, uuid, file, isNew]
    );
    const save = useCallback(async (): Promise<boolean> => {
        if (readOnly || saving) {
            return false;
        }

        if (!isNew) {
            return write(file);
        }

        naming.current?.(false);
        const named = new Promise<boolean>((resolve) => {
            naming.current = resolve;
        });

        fileNameDialog.show();

        return named;
    }, [readOnly, saving, isNew, write, file, fileNameDialog]);
    const saveAs = useCallback(
        async (name: string): Promise<boolean> => {
            if (!canCreate || saving || !name) {
                return false;
            }

            return write(normalize(name.startsWith('/') ? name : join(isNew ? file : dirname(file), name)));
        },
        [canCreate, saving, write, isNew, file]
    );
    const read = useCallback(() => buffer.current, []);
    const model = useMemo<FileEditorModel>(
        () => ({
            path: file,
            name: isNew ? '' : basename(file),
            isNew,
            content,
            language,
            languages: editorLanguages,
            readOnly,
            dirty,
            saving,
            Editor: FileEditorCanvas,
            read,
            change,
            setLanguage,
            save,
            saveAs,
        }),
        [file, isNew, content, language, readOnly, dirty, saving, read, change, save, saveAs]
    );

    return (
        <>
            <FileNameModal
                open={fileNameDialog.open}
                directory={file}
                onClose={() => {
                    fileNameDialog.hide();
                    naming.current?.(false);
                    naming.current = null;
                }}
                onFileNamed={(name) => {
                    fileNameDialog.hide();
                    const resolve = naming.current;

                    naming.current = null;
                    void write(name).then(resolve ?? undefined);
                }}
            />
            <FileEditorSessionContext.Provider value={model}>{children}</FileEditorSessionContext.Provider>
        </>
    );
}

/**
 * The editor route's engine frame around its page view: it loads the document, holds the editing
 * session with its unsaved-changes guard, and asks a new file's name, whatever the theme renders.
 */
export default function FileEditorFrame({ children }: { children?: ReactNode }) {
    const { action } = useParams({ strict: false });
    const isEditingFile = action === 'edit';
    const router = useRouter();
    const { hash } = useLocation();
    const file = hashToPath(hash);

    const server = useCurrentServer()!;
    const id = server.attributes.identifier;
    const uuid = server.attributes.uuid;

    const hasFile = isEditingFile && file !== '/';
    const { data, error, isFetching } = useServerFileContent(uuid, file, hasFile);
    const key = `${uuid}:${action}:${file}`;

    // Captured once per document; later cache writes must not replace the buffer.
    const [opened, setOpened] = useState<{ key: string; content: string } | null>(null);

    // Undefined while an existing file's content is still loading.
    const loadContent = () => {
        if (hasFile) {
            return isFetching ? undefined : data;
        }

        if (isEditingFile) {
            return '';
        }

        return readNewFileDraft(uuid, file);
    };

    if (opened?.key !== key) {
        const loaded = loadContent();

        if (loaded !== undefined) {
            setOpened({ key, content: loaded });
        }
    }

    const ready = opened?.key === key;
    const failed = hasFile && !ready && error ? httpErrorToHuman(error) : null;
    const name = isEditingFile ? basename(file) || null : null;
    const page = useMemo<FileEditorPageModel>(
        () => ({
            breadcrumbs: fileBreadcrumbs(id, name ? dirname(file) : file, true),
            file: name,
            loading: !ready && !failed,
            error: failed,
            back: () => router.history.back(),
        }),
        [id, file, name, ready, failed, router]
    );

    return (
        <FileEditorPageContext.Provider value={page}>
            {ready ? (
                <FileEditSession
                    key={key}
                    id={id}
                    uuid={uuid}
                    file={file}
                    isNew={!isEditingFile}
                    content={opened.content}
                >
                    {children}
                </FileEditSession>
            ) : (
                children
            )}
        </FileEditorPageContext.Provider>
    );
}
