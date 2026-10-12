import { createContext, createElement, useContext, useMemo, useState, type ReactNode } from 'react';
import Button from '@/components/elements/Button';
import CodemirrorEditor from '@/components/elements/LazyCodemirrorEditor';
import Spinner from '@/components/elements/Spinner';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Select from '@/components/ui/Select';
import modes from '@/modes';
import { cn } from '@/lib/cn';
import { createTestFileEditor } from '@/views/fixtures';
import type { FileEditorModel } from '@/views/files';
import { defineView, registerBaseView, type ViewPartProps, type ViewParts, type ViewProps } from '@/views/registry';

export interface FileEditorSession {
    model: FileEditorModel;
    read: () => string;
    setLanguage: (language: string) => void;
}
export const FileEditorContext = createContext<FileEditorSession | null>(null);

const findModeByFilename = (filename: string) => {
    const match = modes.find((mode) => mode.file?.test(filename));
    const dot = filename.lastIndexOf('.');
    const ext = dot > -1 ? filename.slice(dot + 1) : undefined;

    return match ?? (ext ? modes.find((mode) => mode.ext?.includes(ext)) : undefined);
};

export const modeForFile = (filename: string) => findModeByFilename(filename)?.mime || 'text/plain';

type PartProps = ViewPartProps<'server.files.editor'>;
function Notice({ model }: PartProps) {
    return model.name.endsWith('.pteroignore') ? (
        <div className='mb-4 p-4 border-l-4 bg-muted rounded-sm border-accent'>
            <p className='text-muted-foreground text-sm'>
                You&apos;re editing a <code className='font-mono bg-muted rounded-sm py-px px-1'>.pteroignore</code>{' '}
                file. Any files or directories listed in here will be excluded from backups. Wildcards are supported by
                using an asterisk (<code className='font-mono bg-muted rounded-sm py-px px-1'>*</code>
                ). You can negate a prior rule by prepending an exclamation point (
                <code className='font-mono bg-muted rounded-sm py-px px-1'>!</code>).
            </p>
        </div>
    ) : null;
}

function Editor({ model }: PartProps) {
    const { read } = useContext(FileEditorContext)!;

    // Starts from the live buffer, which may be ahead of the opened content.
    const [initialContent] = useState(read);

    return (
        <div className='relative' aria-busy={model.saving}>
            <SpinnerOverlay visible={model.saving} />
            <CodemirrorEditor
                mode={model.language}
                initialContent={initialContent}
                onContentChanged={model.change}
                onContentSaved={() => void model.save()}
            />
        </div>
    );
}

function Language({ model }: PartProps) {
    const { setLanguage } = useContext(FileEditorContext)!;

    return (
        <div className='w-full min-w-0 rounded-sm bg-muted sm:w-64'>
            <Select
                value={model.language}
                onChange={(value) => setLanguage(String(value))}
                disabled={model.saving}
                options={modes.map((mode) => ({ value: mode.mime, label: mode.name }))}
            />
        </div>
    );
}

function Actions({ model }: PartProps) {
    return model.readOnly ? null : (
        <Button
            className='w-full sm:w-auto'
            disabled={model.saving}
            isLoading={model.saving}
            onClick={() => void model.save()}
        >
            {model.isNew ? 'Create File' : 'Save Content'}
        </Button>
    );
}

export const fileEditorParts: ViewParts<'server.files.editor'> = {
    notice: Notice,
    editor: Editor,
    language: Language,
    actions: Actions,
};
export function DefaultFileEditor({ className, parts, model }: ViewProps<'server.files.editor'>) {
    return (
        <div className={cn('min-w-0', className)}>
            <parts.notice model={model} />
            <parts.editor model={model} />
            <div className='mt-4 flex flex-col gap-3 sm:flex-row sm:items-stretch sm:justify-end'>
                <parts.language model={model} />
                <parts.actions model={model} />
            </div>
        </div>
    );
}

DefaultFileEditor.parts = fileEditorParts;

/** The editing session's model; the file edit page holds the buffer and the write. */
function useFileEditorModel(): FileEditorModel {
    const session = useContext(FileEditorContext);

    if (!session) {
        throw new Error('The file editor renders inside an open file editing session.');
    }

    return session.model;
}

function FixtureEditorSession({ model, children }: { model: FileEditorModel; children?: ReactNode }) {
    const value = useMemo(() => ({ model, read: () => model.content, setLanguage: () => {} }), [model]);

    return <FileEditorContext.Provider value={value}>{children}</FileEditorContext.Provider>;
}

export const fileEditorView = defineView('server.files.editor', {
    context: 'server',
    use: useFileEditorModel,
    fixtureScope: FixtureEditorSession,
    placeholder: createElement(Spinner, { size: 'large', centered: true }),
    fixtures: {
        file: () => createTestFileEditor(),
        dirty: () => createTestFileEditor({ dirty: true }),
        readOnly: () => createTestFileEditor({ readOnly: true }),
        new: () => createTestFileEditor({ path: '/config', name: '', isNew: true, content: '' }),
    },
});
registerBaseView('classic', 'classic', 'server.files.editor', DefaultFileEditor);
