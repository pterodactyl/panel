import { useMemo } from 'react';
import { Link, useNavigate } from '@tanstack/react-router';
import { getCoreRowModel, useReactTable, type ColumnDef, type Table } from '@tanstack/react-table';
import { Server } from 'lucide-react';
import { useAdminNodeServers } from '@/api/admin/nodes/queries';
import type { AdminServer } from '@/api/admin/servers/queries';
import { useNodeDetail } from '@/components/admin/nodes/useNodeDetail';
import ServerEditAction from '@/components/admin/servers/ServerEditAction';
import { getPageSearch, usePageSearch } from '@/router/search';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Code from '@/components/elements/Code';
import Spinner from '@/components/elements/Spinner';
import DataTable from '@/components/elements/table/DataTable';
import { actionsColumn, RowActions } from '@/components/elements/table/RowActions';
import DataTablePagination from '@/components/elements/table/DataTablePagination';
import { Alert } from '@/components/elements/alert';
import Button from '@/components/elements/Button';
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { emptyCompactClass } from '@/components/ui/styles';
import { httpErrorToHuman } from '@/api/http';
import { relationshipAttributes } from '@/api/relationships';

const nodeServerColumns = [
    {
        id: 'name',
        header: 'Server',
        cell: ({ row }) => (
            <div className='w-0 min-w-full'>
                <Link
                    to='/panel/servers/$id'
                    params={{ id: row.original.attributes.id }}
                    title={row.original.attributes.name}
                    className='block truncate text-sm font-medium text-foreground hover:text-accent'
                >
                    {row.original.attributes.name}
                </Link>
                <p
                    className='mt-1 truncate text-xs text-muted-foreground'
                    title={row.original.attributes.description || undefined}
                >
                    {row.original.attributes.description || 'No description'}
                </p>
            </div>
        ),
        meta: { headerClassName: 'min-w-48', cellClassName: 'min-w-48' },
    },
    {
        id: 'owner',
        header: 'Owner',
        cell: ({ row }) => {
            const owner = relationshipAttributes(row.original.attributes.relationships?.user);

            return owner ? (
                <div className='w-0 min-w-full'>
                    <Link
                        to='/panel/users/$id'
                        params={{ id: owner.id }}
                        title={owner.username}
                        className='block truncate text-sm text-foreground hover:text-accent'
                    >
                        {owner.username}
                    </Link>
                    <p className='mt-1 truncate text-xs text-muted-foreground' title={owner.email}>
                        {owner.email}
                    </p>
                </div>
            ) : (
                <span className='text-sm text-muted-foreground'>User #{row.original.attributes.user}</span>
            );
        },
        meta: { headerClassName: 'hidden w-44 @xl:table-cell', cellClassName: 'hidden w-44 @xl:table-cell' },
    },
    {
        id: 'service',
        header: 'Service',
        cell: ({ row }) => {
            const attributes = row.original.attributes;
            const egg = relationshipAttributes(attributes.relationships?.egg);
            const service = egg?.name ?? `Egg #${attributes.egg}`;

            return (
                <span className='block w-0 min-w-full truncate text-sm' title={service}>
                    {service}
                </span>
            );
        },
        meta: { headerClassName: 'hidden w-40 @2xl:table-cell', cellClassName: 'hidden w-40 @2xl:table-cell' },
    },
    {
        id: 'identifier',
        header: 'Identifier',
        cell: ({ row }) => (
            <div className='text-right'>
                <Code>{row.original.attributes.identifier}</Code>
                <p className='mt-1 text-xs text-muted-foreground'>ID {row.original.attributes.id}</p>
            </div>
        ),
        meta: {
            headerClassName: 'hidden w-28 text-right @sm:table-cell',
            cellClassName: 'hidden w-28 text-right @sm:table-cell',
        },
    },
    actionsColumn<AdminServer>(1, (server) => (
        <RowActions>
            <ServerEditAction server={server} />
        </RowActions>
    )),
] satisfies ColumnDef<AdminServer>[];

type NodeServersQuery = ReturnType<typeof useAdminNodeServers>;

interface NodeServerListProps {
    query: NodeServersQuery;
    table: Table<AdminServer>;
}

const NodeServerList = ({ query, table }: NodeServerListProps) => {
    const { data: servers, error, isFetching: loading, refetch } = query;

    if (error && !loading) {
        return (
            <div className='space-y-4'>
                <Alert type='danger'>{httpErrorToHuman(error)}</Alert>
                <Button.Text type='button' onClick={() => refetch()}>
                    Retry
                </Button.Text>
            </div>
        );
    }

    if (!servers) {
        return <Spinner size='large' centered />;
    }

    return (
        <>
            <DataTable
                table={table}
                isFetching={loading}
                emptyState={
                    <Empty className={emptyCompactClass}>
                        <EmptyHeader>
                            <EmptyMedia variant='icon'>
                                <Server />
                            </EmptyMedia>
                            <EmptyTitle>No servers</EmptyTitle>
                            <EmptyDescription>No servers are running on this node yet.</EmptyDescription>
                        </EmptyHeader>
                    </Empty>
                }
            />
            <DataTablePagination
                table={table}
                total={servers.meta.pagination.total}
                count={servers.meta.pagination.count}
                itemLabel='servers'
            />
        </>
    );
};

export default function NodeServersTab() {
    const { node } = useNodeDetail();
    const { attributes } = node;
    const navigate = useNavigate();
    const page = usePageSearch();

    const query = useAdminNodeServers(attributes.id, page);
    const servers = query.data;
    const pagination = { pageIndex: Math.max(page - 1, 0), pageSize: servers?.meta.pagination.per_page ?? 50 };
    const data = useMemo(() => servers?.data ?? [], [servers?.data]);
    const table = useReactTable({
        data,
        columns: nodeServerColumns,
        getCoreRowModel: getCoreRowModel(),
        getRowId: (server) => String(server.attributes.id),
        manualPagination: true,
        rowCount: servers?.meta.pagination.total ?? 0,
        state: { pagination },
        onPaginationChange: (updater) => {
            const next = updater instanceof Function ? updater(pagination) : updater;

            if (next.pageIndex !== pagination.pageIndex) {
                void navigate({
                    to: '/panel/nodes/$id/servers',
                    params: { id: attributes.id },
                    search: getPageSearch(next.pageIndex + 1),
                    replace: true,
                    viewTransition: false,
                });
            }
        },
    });

    return (
        <div>
            <TitledGreyBox title='Server List'>
                <NodeServerList query={query} table={table} />
            </TitledGreyBox>
        </div>
    );
}
