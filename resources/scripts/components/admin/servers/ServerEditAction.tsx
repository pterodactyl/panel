import type { AdminServer } from '@/api/admin/servers/queries';
import { EditAction, EditLinkAction } from '@/components/elements/table/RowActions';

/** Links to the server's editable Details tab, which only exists once the server is installed. */
export default function ServerEditAction({ server }: { server: AdminServer }) {
    const { id, name, container } = server.attributes;
    const label = `Edit ${name}`;

    if (container.installed !== 1) {
        return (
            <EditAction
                aria-label={label}
                disabled
                disabledReason='Editing is disabled until the server is installed.'
            />
        );
    }

    return <EditLinkAction aria-label={label} to='/panel/servers/$id/details' params={{ id }} />;
}
