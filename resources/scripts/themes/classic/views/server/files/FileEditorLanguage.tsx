import { cn, Select } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';

export default function FileEditorLanguage({ model, className }: ViewProps<'server.files.editor.language'>) {
    return (
        <div className={cn('w-full min-w-0 rounded-sm bg-muted sm:w-64', className)}>
            <Select
                value={model.language}
                onChange={(value) => model.setLanguage(String(value))}
                disabled={model.saving}
                options={model.languages.map((language) => ({ value: language.mime, label: language.name }))}
            />
        </div>
    );
}
