import { encodePathSegments } from '@/helpers';
import type { CorePanelDestination } from '@/sdk/navigation';
import type { FileBreadcrumb } from '@/views/server/files';

export const fileListDestination = (id: string, path: string): CorePanelDestination => ({
    to: '/server/$id/files',
    params: { id },
    hash: encodePathSegments(path),
});

/** "container", then each directory of `directory`; the last is a link only when `linkLast` is set. */
export function fileBreadcrumbs(id: string, directory: string, linkLast: boolean): FileBreadcrumb[] {
    const segments = directory.split('/').filter(Boolean);

    return [
        { name: 'container', path: '/', destination: fileListDestination(id, '/') },
        ...segments.map((name, index) => {
            const path = `/${segments.slice(0, index + 1).join('/')}`;

            return {
                name,
                path,
                destination: linkLast || index < segments.length - 1 ? fileListDestination(id, path) : null,
            };
        }),
    ];
}
