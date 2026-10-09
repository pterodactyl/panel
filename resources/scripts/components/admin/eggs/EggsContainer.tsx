import { useExtensionTableColumns } from '@/extensions/tableColumns';
import { useMemo } from 'react';
import { Link } from '@tanstack/react-router';
import { getCoreRowModel, useReactTable, type ColumnDef } from '@tanstack/react-table';
import { Egg, Search } from 'lucide-react';
import { httpErrorToHuman } from '@/api/http';
import { type AdminEggListItem, useAdminEggs } from '@/api/admin/eggs/queries';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import ImportEggButton from '@/components/admin/eggs/ImportEggButton';
import ListToolbar from '@/components/elements/ListToolbar';
import { NewLinkButton } from '@/components/elements/NewButton';
import { ServerError } from '@/components/elements/ScreenBlock';
import DataTable from '@/components/elements/table/DataTable';
import { actionsColumn, EditLinkAction, RowActions } from '@/components/elements/table/RowActions';
import { Alert } from '@/components/elements/alert';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { emptyCompactClass } from '@/components/ui/styles';

const eggColumns = [
    {
        id: 'name',
        header: 'Egg',
        cell: ({ row }) => (
            <div className='min-w-0'>
                <Link
                    to='/panel/eggs/$eggId'
                    params={{ eggId: row.original.attributes.id }}
                    className='block truncate font-medium text-foreground no-underline hover:text-accent'
                >
                    {row.original.attributes.name}
                </Link>
                {row.original.attributes.description ? (
                    <p className='mt-1 line-clamp-2 whitespace-normal break-words text-xs leading-relaxed text-muted-foreground md:line-clamp-none'>
                        {row.original.attributes.description}
                    </p>
                ) : null}
            </div>
        ),
        meta: { headerClassName: 'w-auto', cellClassName: 'w-auto' },
    },
    {
        id: 'author',
        header: 'Author',
        cell: ({ row }) => (
            <span className='block max-w-64 truncate text-sm' title={row.original.attributes.author}>
                {row.original.attributes.author}
            </span>
        ),
        meta: { headerClassName: 'hidden w-64 md:table-cell', cellClassName: 'hidden w-64 md:table-cell' },
    },
    {
        id: 'id',
        header: 'ID',
        cell: ({ row }) => <span className='tabular-nums text-sm'>{row.original.attributes.id}</span>,
        meta: { headerClassName: 'w-20 text-right', cellClassName: 'w-20 text-right' },
    },
    actionsColumn<AdminEggListItem>(1, (egg) => (
        <RowActions>
            <EditLinkAction
                aria-label={`Edit ${egg.attributes.name}`}
                to='/panel/eggs/$eggId'
                params={{ eggId: egg.attributes.id }}
            />
        </RowActions>
    )),
] satisfies ColumnDef<AdminEggListItem>[];

export default function EggsContainer() {
    const { data: eggs, error, refetch } = useAdminEggs();
    const data = useMemo(() => eggs?.data ?? [], [eggs?.data]);
    const extensionColumns = useExtensionTableColumns('admin.eggs');
    const columns = useMemo(() => [...eggColumns, ...extensionColumns], [extensionColumns]);
    const table = useReactTable({
        data,
        columns,
        getCoreRowModel: getCoreRowModel(),
        getRowId: (egg) => String(egg.attributes.id),
    });

    if (error) {
        return <ServerError message={httpErrorToHuman(error)} onRetry={() => refetch()} />;
    }

    return (
        <AdminContentBlock
            title='Admin · Eggs'
            heading='Eggs'
            description='Manage server templates, startup configuration, variables, and install scripts.'
        >
            <Alert type='danger' className='mb-6 text-sm'>
                Eggs are powerful and can break servers when edited incorrectly. Avoid editing official eggs unless you
                are certain of the change.
            </Alert>
            <ListToolbar>
                <NewLinkButton to='/panel/eggs/catalog' isSecondary icon={Search}>
                    Browse catalog
                </NewLinkButton>
                <ImportEggButton />
                <NewLinkButton to='/panel/eggs/new'>New egg</NewLinkButton>
            </ListToolbar>
            <DataTable
                table={table}
                tableClassName='table-fixed'
                emptyState={
                    <Empty className={emptyCompactClass}>
                        <EmptyHeader>
                            <EmptyMedia variant='icon'>
                                <Egg />
                            </EmptyMedia>
                            <EmptyTitle>No eggs yet</EmptyTitle>
                            <EmptyDescription>
                                Eggs are the templates servers are built from. Browse the catalog for ready-made eggs or
                                create your own.
                            </EmptyDescription>
                        </EmptyHeader>
                        <EmptyContent>
                            <NewLinkButton to='/panel/eggs/new'>New egg</NewLinkButton>
                        </EmptyContent>
                    </Empty>
                }
            />
        </AdminContentBlock>
    );
}
