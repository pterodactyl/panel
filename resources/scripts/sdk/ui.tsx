import {
    lazy,
    Suspense,
    useMemo,
    type ComponentProps,
    type DOMAttributes,
    type ReactElement,
    type ReactNode,
    type RefAttributes,
} from 'react';
import { getCoreRowModel, getSortedRowModel, useReactTable, type ColumnDef } from '@tanstack/react-table';
import Spinner from '@/components/elements/Spinner';
import PanelDataTable from '@/components/elements/table/DataTable';
import DataTableColumnHeader from '@/components/elements/table/DataTableColumnHeader';
import {
    DeleteAction as PanelDeleteAction,
    RowActions as PanelRowActions,
} from '@/components/elements/table/RowActions';
import PanelTooltip from '@/components/elements/tooltip/Tooltip';

export {
    default as DropdownMenu,
    DropdownMenuItem,
    ContextDropdownMenu,
    type ContextDropdownMenuProps,
    type DropdownMenuItemProps,
    type DropdownMenuProps,
} from '@/components/elements/dropdown/DropdownMenu';
export { default as Pagination, type PaginationProps } from '@/components/elements/Pagination';
export {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
    type EmptyMediaVariant,
    type EmptyProps,
    type EmptySize,
} from '@/components/ui/empty';
export { default as Avatar, type AvatarProps, type AvatarVariant } from '@/components/Avatar';

interface TableColumnBase<TRow> {
    id: string;
    cell(row: TRow): ReactNode;
    headerClassName?: string;
    cellClassName?: string;
}
export type TableColumn<TRow> = TableColumnBase<TRow> &
    (
        | { label: ReactNode; sortValue?: never }
        | {
              label: string;
              /** Makes the column sortable by this value; its header becomes the sort button. */
              sortValue(row: TRow): string | number | null;
          }
    );
export interface TableProps<TRow> {
    rows: readonly TRow[];
    columns: readonly TableColumn<TRow>[];
    keyOf(row: TRow): string;
    /** Replaces the table when there are no rows, typically an `Empty`; a string renders as its description. */
    emptyState: ReactNode;
    isFetching?: boolean;
    className?: string;
}

function columnDefinition<TRow>(column: TableColumn<TRow>): ColumnDef<TRow> {
    const shared: ColumnDef<TRow> = {
        id: column.id,
        cell: ({ row }) => column.cell(row.original),
        meta: { headerClassName: column.headerClassName, cellClassName: column.cellClassName },
    };

    if (column.sortValue) {
        const { label } = column;

        return {
            ...shared,
            accessorFn: column.sortValue,
            header: ({ column: header }) => <DataTableColumnHeader column={header} title={label} />,
        };
    }

    return { ...shared, header: () => column.label, enableSorting: false };
}

export function Table<TRow>({ rows, columns, keyOf, ...props }: TableProps<TRow>): ReactElement {
    const data = useMemo(() => [...rows], [rows]);
    const definitions = useMemo(() => columns.map(columnDefinition), [columns]);
    const table = useReactTable({
        data,
        columns: definitions,
        getRowId: keyOf,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
    });

    return <PanelDataTable table={table} {...props} />;
}

/** Lays out a table row's actions: edit, delete, then any overflow menu. */
export function RowActions({ children }: { children: ReactNode }): ReactElement {
    return <PanelRowActions>{children}</PanelRowActions>;
}

export interface DeleteActionProps extends Omit<ComponentProps<'button'>, 'children' | 'color' | 'aria-label'> {
    'aria-label': string;
    /** Tooltip text, "Delete" by default; `disabledReason` replaces it while disabled. */
    label?: string;
    disabledReason?: string;
}
/** A row's delete icon button. */
export function DeleteAction(props: DeleteActionProps): ReactElement {
    return <PanelDeleteAction {...props} />;
}

export interface TooltipProps {
    content: ReactNode;
    children: ReactElement<DOMAttributes<Element> & RefAttributes<Element> & { className?: string }>;
    disabled?: boolean;
    className?: string;
    placement?: 'top' | 'bottom' | 'left' | 'right';
    /** Milliseconds the pointer rests before the tooltip opens. */
    delay?: number;
}
export function Tooltip(props: TooltipProps): ReactElement {
    return <PanelTooltip {...props} />;
}

const QRCodeSVG = lazy(() => import('qrcode.react').then(({ QRCodeSVG: Code }) => ({ default: Code })));

export interface QrCodeProps {
    value: string;
    className?: string;
}
/** `value` as a QR code; the renderer loads on first use, with a spinner meanwhile. */
export function QrCode({ value, className }: QrCodeProps): ReactElement {
    return (
        <Suspense fallback={<Spinner />}>
            <QRCodeSVG value={value} className={className} />
        </Suspense>
    );
}
