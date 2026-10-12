import {
    lazy,
    Suspense,
    useCallback,
    useMemo,
    type ComponentProps,
    type DOMAttributes,
    type ReactElement,
    type ReactNode,
    type RefAttributes,
} from 'react';
import type { LucideIcon } from 'lucide-react';
import {
    getCoreRowModel,
    getSortedRowModel,
    useReactTable,
    type ColumnDef,
    type OnChangeFn,
    type PaginationState,
} from '@tanstack/react-table';
import Spinner from '@/components/elements/Spinner';
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
import PanelTooltip from '@/components/elements/tooltip/Tooltip';
import { resolvePanelDestination, type PanelDestination } from './navigation';

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
export { default as ListToolbar } from '@/components/elements/ListToolbar';

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

function columnDefinition<TRow>(column: TableColumn<TRow>): ColumnDef<TRow> {
    const shared = {
        id: column.id,
        cell: ({ row }: { row: { original: TRow } }) => column.cell(row.original),
        meta: { headerClassName: column.headerClassName, cellClassName: column.cellClassName },
    };

    if (column.sortValue === undefined) {
        const { label } = column;

        return { ...shared, header: () => label, enableSorting: false };
    }

    const { label, sortValue } = column;

    return {
        ...shared,
        accessorFn: sortValue,
        header: ({ column: header }) => <DataTableColumnHeader column={header} title={label} />,
    };
}

export function Table<TRow>({ rows, columns, keyOf, pagination, ...props }: TableProps<TRow>): ReactElement {
    const data = useMemo(() => [...rows], [rows]);
    const definitions = useMemo(() => columns.map(columnDefinition), [columns]);
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

/** The trailing "Actions" column, sized for `actions` icon buttons in a `RowActions`. */
export function actionsColumn<TRow>(actions: 1 | 2 | 3, cell: (row: TRow) => ReactNode): TableColumn<TRow> {
    const className = `${actionWidths[actions]} text-right`;

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

export interface TooltipProps {
    content: ReactNode;
    children: ReactElement<DOMAttributes<Element> & RefAttributes<Element> & { className?: string }>;
    disabled?: boolean;
    className?: string;
    placement?: 'top' | 'bottom' | 'left' | 'right';
    /** What opens the tooltip; hover and focus by default. */
    interactions?: ('hover' | 'focus' | 'click')[];
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
