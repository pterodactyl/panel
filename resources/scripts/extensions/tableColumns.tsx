import { useMemo } from 'react';
import type { ColumnDef } from '@tanstack/react-table';
import ExtensionMount from './ExtensionMount';
import { getExtensionTableColumns, useExtensionRegistry } from './registry';

import type { ExtensionTableName, ExtensionTableRows } from './tableTypes';

export function useExtensionTableColumns<TName extends ExtensionTableName>(
    name: TName
): ColumnDef<ExtensionTableRows[TName]>[] {
    const columns = useExtensionRegistry(() => getExtensionTableColumns(name));

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
