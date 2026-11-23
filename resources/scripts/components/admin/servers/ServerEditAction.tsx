import type { AdminServer } from '@/api/admin/servers/queries';
import { EditAction, EditLinkAction } from '@/components/elements/table/RowActions';
import { useTranslation } from '@/sdk/localization';

/** Links to the server's editable Details tab, which only exists once the server is installed. */
export default function ServerEditAction({ server }: { server: AdminServer }) {
    const { t } = useTranslation();
    const { id, name, container } = server.attributes;
    const label = t('Edit {{name}}', { name });

    if (container.installed !== 1) {
        return (
            <EditAction
                aria-label={label}
                disabled
                disabledReason={t('Editing is disabled until the server is installed.')}
            />
        );
    }

    return <EditLinkAction aria-label={label} to='/panel/servers/$id/details' params={{ id }} />;
}
