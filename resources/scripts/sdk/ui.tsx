import { useMemo, type DOMAttributes, type ReactElement, type ReactNode, type RefAttributes } from 'react';
import { getCoreRowModel, useReactTable, type ColumnDef } from '@tanstack/react-table';
import PanelDataTable from '@/components/elements/table/DataTable';
import PanelTooltip from '@/components/elements/tooltip/Tooltip';

export {
    default as DropdownMenu,
    DropdownMenuItem,
    ContextDropdownMenu,
} from '@/components/elements/dropdown/DropdownMenu';
export { default as Pagination } from '@/components/elements/Pagination';
export { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';

export interface TableColumn<TRow> {
    id: string;
    label: ReactNode;
    cell(row: TRow): ReactNode;
}
export interface TableProps<TRow> {
    rows: readonly TRow[];
    columns: readonly TableColumn<TRow>[];
    keyOf(row: TRow): string;
    /** Replaces the table when there are no rows, typically an `Empty`; a string renders as its description. */
    emptyState: ReactNode;
    isFetching?: boolean;
    className?: string;
}
export function Table<TRow>({ rows, columns, keyOf, ...props }: TableProps<TRow>): ReactElement {
    const data = useMemo(() => [...rows], [rows]);
    const definitions = useMemo<ColumnDef<TRow>[]>(
        () =>
            columns.map((column) => ({
                id: column.id,
                header: () => column.label,
                cell: ({ row }) => column.cell(row.original),
            })),
        [columns]
    );
    const table = useReactTable({ data, columns: definitions, getRowId: keyOf, getCoreRowModel: getCoreRowModel() });

    return <PanelDataTable table={table} {...props} />;
}

export interface TooltipProps {
    content: ReactNode;
    children: ReactElement<DOMAttributes<Element> & RefAttributes<Element> & { className?: string }>;
    disabled?: boolean;
    className?: string;
    placement?: 'top' | 'bottom' | 'left' | 'right';
}
export function Tooltip(props: TooltipProps): ReactElement {
    return <PanelTooltip {...props} />;
}
