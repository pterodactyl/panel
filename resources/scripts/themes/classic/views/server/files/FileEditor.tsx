import { Button, cn, Select, SpinnerOverlay } from '@/sdk';
import type { ViewPartProps, ViewParts, ViewProps } from '@/sdk/theme';

type PartProps = ViewPartProps<'server.files.editor'>;

const codeClass = 'font-mono bg-muted rounded-sm py-px px-1';

function Notice({ model }: PartProps) {
    return model.name.endsWith('.pteroignore') ? (
        <div className='mb-4 p-4 border-l-4 bg-muted rounded-sm border-accent'>
            <p className='text-muted-foreground text-sm'>
                You&apos;re editing a <code className={codeClass}>.pteroignore</code> file. Any files or directories
                listed in here will be excluded from backups. Wildcards are supported by using an asterisk (
                <code className={codeClass}>*</code>
                ). You can negate a prior rule by prepending an exclamation point (<code className={codeClass}>!</code>
                ).
            </p>
        </div>
    ) : null;
}

function Editor({ model }: PartProps) {
    return (
        <div className='relative' aria-busy={model.saving}>
            <SpinnerOverlay visible={model.saving} />
            <model.Editor />
        </div>
    );
}

function Language({ model }: PartProps) {
    return (
        <div className='w-full min-w-0 rounded-sm bg-muted sm:w-64'>
            <Select
                value={model.language}
                onChange={(value) => model.setLanguage(String(value))}
                disabled={model.saving}
                options={model.languages.map((language) => ({ value: language.mime, label: language.name }))}
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

const fileEditorParts: ViewParts<'server.files.editor'> = {
    notice: Notice,
    editor: Editor,
    language: Language,
    actions: Actions,
};

export default function FileEditor({ className, parts, model }: ViewProps<'server.files.editor'>) {
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

FileEditor.parts = fileEditorParts;
