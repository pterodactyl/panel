import { Button, cn, useTranslation } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';

export default function FileEditorActions({ model, className }: ViewProps<'server.files.editor.actions'>) {
    const { t } = useTranslation();

    return model.readOnly ? null : (
        <Button
            className={cn('w-full sm:w-auto', className)}
            disabled={model.saving}
            isLoading={model.saving}
            onClick={() => void model.save()}
        >
            {model.isNew ? t('Create File') : t('Save Content')}
        </Button>
    );
}
