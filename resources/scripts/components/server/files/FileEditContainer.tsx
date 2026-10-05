import { useCallback, useMemo, useRef, useState } from 'react';
import { basename, dirname, join, normalize } from 'pathe';
import { useCurrentServer } from '@/api/server/queries';
import { httpErrorToHuman } from '@/api/http';
import Spinner from '@/components/elements/Spinner';
import FileManagerBreadcrumbs from '@/components/server/files/FileManagerBreadcrumbs';
import { useLocation, useNavigate, useParams, useRouter } from '@tanstack/react-router';
import FileNameModal from '@/components/server/files/FileNameModal';
import PageContentBlock from '@/components/elements/PageContentBlock';
import { ServerError } from '@/components/elements/ScreenBlock';
import ErrorBoundary from '@/components/elements/ErrorBoundary';
import { encodePathSegments, hashToPath } from '@/helpers';
import { useSaveFileContent, useServerFileContent, writeFileContentsInput } from '@/api/server/files/queries';
import { useDialogState } from '@/components/elements/dialog';
import { clearNewFileDraft, readNewFileDraft, writeNewFileDraft } from '@/lib/fileDrafts';
import { useDebouncedCallback } from '@/plugins/useDebouncedCallback';
import { useNavigationBlocker } from '@/plugins/useNavigationBlocker';
import { usePermissions } from '@/plugins/usePermissions';
import ComponentView from '@/extensions/ComponentView';
import type { FileEditorModel } from '@/extensions/componentTypes';
import { DefaultFileEditor, FileEditorContext, fileEditorParts, modeForFile } from './FileEditorView';

interface FileEditSessionProps {
    id: string;
    uuid: string;
    file: string;
    isNew: boolean;
    content: string;
}

// A new file's draft stays in session storage, so leaving it needs no confirmation.
const keepDraft = () => true;

function FileEditSession({ id, uuid, file, isNew, content }: FileEditSessionProps) {
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
            if (isNew) saveDraft(value);
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
        if (readOnly || saving) return false;
        if (!isNew) return write(file);
        naming.current?.(false);
        const named = new Promise<boolean>((resolve) => {
            naming.current = resolve;
        });
        fileNameDialog.show();
        return named;
    }, [readOnly, saving, isNew, write, file, fileNameDialog]);
    const saveAs = useCallback(
        async (name: string): Promise<boolean> => {
            if (!canCreate || saving || !name) return false;
            return write(normalize(name.startsWith('/') ? name : join(isNew ? file : dirname(file), name)));
        },
        [canCreate, saving, write, isNew, file]
    );
    const model = useMemo<FileEditorModel>(
        () => ({
            path: file,
            name: isNew ? '' : basename(file),
            isNew,
            content,
            language,
            readOnly,
            dirty,
            saving,
            change,
            save,
            saveAs,
        }),
        [file, isNew, content, language, readOnly, dirty, saving, change, save, saveAs]
    );
    const session = useMemo(() => ({ model, read: () => buffer.current, setLanguage }), [model]);

    return (
        <>
            <FileNameModal
                open={fileNameDialog.open}
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
            <FileEditorContext.Provider value={session}>
                <ComponentView
                    name='server.files.editor'
                    resetKey={`${uuid}:${file}`}
                    props={{ model, Default: DefaultFileEditor, parts: fileEditorParts }}
                    loading={<Spinner size={'large'} centered />}
                />
            </FileEditorContext.Provider>
        </>
    );
}

export default function FileEditContainer() {
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
    if (opened?.key !== key) {
        const loaded = hasFile ? (isFetching ? undefined : data) : isEditingFile ? '' : readNewFileDraft(uuid, file);
        if (loaded !== undefined) setOpened({ key, content: loaded });
    }

    if (hasFile && error && opened?.key !== key) {
        return <ServerError message={httpErrorToHuman(error)} onBack={() => router.history.back()} />;
    }

    return (
        <PageContentBlock>
            <ErrorBoundary>
                <div className={'mb-4'}>
                    <FileManagerBreadcrumbs withinFileEditor isNewFile={!isEditingFile} />
                </div>
            </ErrorBoundary>
            {opened?.key === key ? (
                <FileEditSession
                    key={key}
                    id={id}
                    uuid={uuid}
                    file={file}
                    isNew={!isEditingFile}
                    content={opened.content}
                />
            ) : (
                <Spinner size={'large'} centered />
            )}
        </PageContentBlock>
    );
}
