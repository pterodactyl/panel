import { createContext, useContext, useState } from 'react';
import Button from '@/components/elements/Button';
import CodemirrorEditor from '@/components/elements/LazyCodemirrorEditor';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Select from '@/components/ui/Select';
import modes from '@/modes';
import { cn } from '@/lib/cn';
import type {
    ComponentPartProps,
    ComponentParts,
    DefaultComponentProps,
    FileEditorModel,
} from '@/extensions/componentTypes';

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

    return match || (ext ? modes.find((mode) => mode.ext?.includes(ext)) : undefined);
};

export const modeForFile = (filename: string) => findModeByFilename(filename)?.mime || 'text/plain';

type PartProps = ComponentPartProps<'server.files.editor'>;
function Notice({ model }: PartProps) {
    return model.name.endsWith('.pteroignore') ? (
        <div className={'mb-4 p-4 border-l-4 bg-muted rounded-sm border-accent'}>
            <p className={'text-muted-foreground text-sm'}>
                You&apos;re editing a <code className={'font-mono bg-muted rounded-sm py-px px-1'}>.pteroignore</code>{' '}
                file. Any files or directories listed in here will be excluded from backups. Wildcards are supported by
                using an asterisk (<code className={'font-mono bg-muted rounded-sm py-px px-1'}>*</code>
                ). You can negate a prior rule by prepending an exclamation point (
                <code className={'font-mono bg-muted rounded-sm py-px px-1'}>!</code>).
            </p>
        </div>
    ) : null;
}
function Editor({ model }: PartProps) {
    const { read } = useContext(FileEditorContext)!;
    // Starts from the live buffer, which may be ahead of the opened content.
    const [initialContent] = useState(read);
    return (
        <div className={'relative'} aria-busy={model.saving}>
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
        <div className={'w-full min-w-0 rounded-sm bg-muted sm:w-64'}>
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
            className={'w-full sm:w-auto'}
            disabled={model.saving}
            isLoading={model.saving}
            onClick={() => void model.save()}
        >
            {model.isNew ? 'Create File' : 'Save Content'}
        </Button>
    );
}
export const fileEditorParts: ComponentParts<'server.files.editor'> = {
    notice: Notice,
    editor: Editor,
    language: Language,
    actions: Actions,
};
export function DefaultFileEditor({ className, parts }: DefaultComponentProps<'server.files.editor'>) {
    const { model } = useContext(FileEditorContext)!;
    const NoticePart = parts?.notice ?? Notice;
    const EditorPart = parts?.editor ?? Editor;
    const LanguagePart = parts?.language ?? Language;
    const ActionsPart = parts?.actions ?? Actions;
    return (
        <div className={cn('min-w-0', className)}>
            <NoticePart model={model} />
            <EditorPart model={model} />
            <div className={'mt-4 flex flex-col gap-3 sm:flex-row sm:items-stretch sm:justify-end'}>
                <LanguagePart model={model} />
                <ActionsPart model={model} />
            </div>
        </div>
    );
}
