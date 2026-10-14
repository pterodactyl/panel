import { useCallback, useMemo, useRef, useState, type ComponentProps, type ReactElement, type ReactNode } from 'react';
import type { LucideIcon } from 'lucide-react';
import {
    getCoreRowModel,
    getSortedRowModel,
    useReactTable,
    type CellContext,
    type ColumnDef,
    type HeaderContext,
    type OnChangeFn,
    type PaginationState,
} from '@tanstack/react-table';
import PanelDataTable from '@/components/elements/table/DataTable';
import DataTableColumnHeader from '@/components/elements/table/DataTableColumnHeader';
import DataTablePagination from '@/components/elements/table/DataTablePagination';
import {
    actionWidths,
    DeleteAction as PanelDeleteAction,
    EditAction as PanelEditAction,
    EditLinkAction as PanelEditLinkAction,
    RowActionButton as PanelRowActionButton,
    RowActions as PanelRowActions,
    RowActionsMenu as PanelRowActionsMenu,
} from '@/components/elements/table/RowActions';
import { resolvePanelDestination, type PanelDestination } from './navigation';

type TableColumnBase<TRow> = {
    id: string;
    cell(row: TRow): ReactNode;
    headerClassName?: string;
    cellClassName?: string;
};
export type TableColumn<TRow> = TableColumnBase<TRow> &
    (
        | { label: ReactNode; sortValue?: undefined }
        | {
              /** A sortable column's label also names its sort button. */
              label: string;
              /** Sorts the rows by this value when the column header is clicked. */
              sortValue(row: TRow): string | number | boolean | null;
          }
    );
/** The rows are one page of many; a pager follows the table. */
export interface TablePagination {
    page: number;
    perPage: number;
    total: number;
    /** Rows on this page. */
    count: number;
    /** Names the rows in the summary, such as "backups". */
    itemLabel?: string;
    onPageSelect(page: number): void;
}
export interface TableProps<TRow> {
    rows: readonly TRow[];
    columns: readonly TableColumn<TRow>[];
    keyOf(row: TRow): string;
    /** Replaces the table when there are no rows, typically an `Empty`; a string renders as its description. */
    emptyState: ReactNode;
    isFetching?: boolean;
    className?: string;
    pagination?: TablePagination;
}

/** Renderers that look their column up as they render, so new column objects update cells instead of remounting them. */
interface ColumnRenderers<TRow> {
    cell(context: CellContext<TRow, unknown>): ReactNode;
    header(context: HeaderContext<TRow, unknown>): ReactNode;
    sortableHeader(context: HeaderContext<TRow, unknown>): ReactNode;
}

function columnDefinition<TRow>(column: TableColumn<TRow>, renderers: ColumnRenderers<TRow>): ColumnDef<TRow> {
    const shared = {
        id: column.id,
        cell: renderers.cell,
        meta: { headerClassName: column.headerClassName, cellClassName: column.cellClassName },
    };

    if (column.sortValue === undefined) {
        return { ...shared, header: renderers.header, enableSorting: false };
    }

    return { ...shared, accessorFn: column.sortValue, header: renderers.sortableHeader };
}

function useColumnRenderers<TRow>(columns: readonly TableColumn<TRow>[]): ColumnRenderers<TRow> {
    const latest = useRef(columns);

    latest.current = columns;

    const [renderers] = useState<ColumnRenderers<TRow>>(() => {
        const find = (id: string) => latest.current.find((column) => column.id === id);

        return {
            cell: ({ row, column }) => find(column.id)?.cell(row.original),
            header: ({ column }) => find(column.id)?.label,
            sortableHeader: ({ column }) => (
                <DataTableColumnHeader column={column} title={String(find(column.id)?.label)} />
            ),
        };
    });

    return renderers;
}

export function Table<TRow>({ rows, columns, keyOf, pagination, ...props }: TableProps<TRow>): ReactElement {
    const data = useMemo(() => [...rows], [rows]);
    const renderers = useColumnRenderers(columns);
    const definitions = useMemo(
        () => columns.map((column) => columnDefinition(column, renderers)),
        [columns, renderers]
    );
    const page = pagination?.page ?? 1;
    const perPage = pagination?.perPage ?? 1;
    const onPageSelect = pagination?.onPageSelect;
    const state = useMemo<PaginationState>(() => ({ pageIndex: page - 1, pageSize: perPage }), [page, perPage]);
    const onPaginationChange = useCallback<OnChangeFn<PaginationState>>(
        (updater) => {
            const next = updater instanceof Function ? updater(state) : updater;

            if (next.pageIndex !== state.pageIndex) {
                onPageSelect?.(next.pageIndex + 1);
            }
        },
        [state, onPageSelect]
    );
    const table = useReactTable({
        data,
        columns: definitions,
        getRowId: keyOf,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
        ...(pagination && {
            manualPagination: true,
            rowCount: pagination.total,
            state: { pagination: state },
            onPaginationChange,
        }),
    });

    if (!pagination) {
        return <PanelDataTable table={table} {...props} />;
    }

    return (
        <>
            <PanelDataTable table={table} {...props} />
            <DataTablePagination
                table={table}
                total={pagination.total}
                count={pagination.count}
                itemLabel={pagination.itemLabel}
            />
        </>
    );
}

/** Lays out a row's actions: edit, delete, then any `RowActionsMenu`. */
export function RowActions({ children }: { children: ReactNode }): ReactElement {
    return <PanelRowActions>{children}</PanelRowActions>;
}

export interface RowActionButtonProps extends Omit<ComponentProps<'button'>, 'children' | 'color' | 'aria-label'> {
    'aria-label': string;
    icon: LucideIcon;
    /** Tooltip text; `disabledReason` replaces it while the button is disabled. */
    label: string;
    danger?: boolean;
    disabledReason?: string;
}
/** An icon button for a table row, labelled by a tooltip. */
export function RowActionButton(props: RowActionButtonProps): ReactElement {
    return <PanelRowActionButton {...props} />;
}

export type RowActionProps = Omit<RowActionButtonProps, 'icon' | 'label' | 'danger'>;
export function EditAction(props: RowActionProps): ReactElement {
    return <PanelEditAction {...props} />;
}

export function DeleteAction(props: RowActionProps & { label?: string }): ReactElement {
    return <PanelDeleteAction {...props} />;
}

/** The "…" menu for a row's actions other than edit and delete, holding `DropdownMenuItem`s. */
export function RowActionsMenu(props: { label: string; children: ReactNode }): ReactElement {
    return <PanelRowActionsMenu {...props} />;
}

/** The trailing "Actions" column, sized for `actions` icon buttons in a `RowActions`; `width` overrides the size. */
export function actionsColumn<TRow>(
    actions: 1 | 2 | 3,
    cell: (row: TRow) => ReactNode,
    width: string = actionWidths[actions]
): TableColumn<TRow> {
    const className = `${width} text-right`;

    return {
        id: 'actions',
        label: <span className='sr-only'>Actions</span>,
        cell,
        headerClassName: className,
        cellClassName: className,
    };
}

export interface EditLinkActionProps {
    destination: PanelDestination;
    'aria-label': string;
}
/** The row's edit action, for rows edited on another page. */
export function EditLinkAction({ destination, ...props }: EditLinkActionProps): ReactElement {
    const { pathname, ...options } = resolvePanelDestination(destination);

    return <PanelEditLinkAction to={pathname as never} {...options} search={options.search as never} {...props} />;
}
