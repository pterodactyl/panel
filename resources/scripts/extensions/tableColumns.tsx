import { useCallback, useMemo, useSyncExternalStore } from 'react';
import type { ColumnDef } from '@tanstack/react-table';
import ExtensionMount from './ExtensionMount';
import { getExtensionTableColumns, subscribeExtensionRegistry } from './registry';

import type { ExtensionTableName, ExtensionTableRows } from './tableTypes';

export function useExtensionTableColumns<TName extends ExtensionTableName>(
    name: TName
): ColumnDef<ExtensionTableRows[TName]>[] {
    const subscribe = useCallback(
        (changed: () => void) => subscribeExtensionRegistry(`table:${name}`, changed),
        [name]
    );
    const snapshot = useCallback(() => getExtensionTableColumns(name), [name]);
    const columns = useSyncExternalStore(subscribe, snapshot);
    return useMemo(
        () =>
            columns.map(({ id, label, extensionId, component: Component }) => ({
                id: `ext:${extensionId}:${id}`,
                header: label,
                enableSorting: false,
                cell: ({ row }) => (
                    <ExtensionMount
                        extensionId={extensionId}
                        context={`table "${name}", column "${id}"`}
                        resetKey={String(row.original.attributes.id)}
                        isSlot
                    >
                        <Component data={row.original} />
                    </ExtensionMount>
                ),
            })),
        [columns, name]
    );
}
