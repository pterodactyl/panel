import { useMemo } from 'react';
import { getCoreRowModel, useReactTable, type ColumnDef } from '@tanstack/react-table';
import { Database, SlidersHorizontal } from 'lucide-react';
import { httpErrorToHuman } from '@/api/http';
import { type AdminServer, type AdminServerDatabase, useAdminServerDatabases } from '@/api/admin/servers/queries';
import { useServerDetail } from '@/components/admin/servers/useServerDetail';
import { ServerDatabaseActions } from '@/components/admin/servers/ServerDatabaseRow';
import CreateServerDatabaseButton from '@/components/admin/servers/CreateServerDatabaseButton';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Spinner from '@/components/elements/Spinner';
import { ServerError } from '@/components/elements/ScreenBlock';
import DataTable from '@/components/elements/table/DataTable';
import { actionsColumn } from '@/components/elements/table/RowActions';
import CopyOnClick from '@/components/elements/CopyOnClick';
import ListToolbar from '@/components/elements/ListToolbar';
import { NewLinkButton } from '@/components/elements/NewButton';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { emptyCompactClass } from '@/components/ui/styles';

function ServerDatabaseContent({ server }: { server: AdminServer }) {
    const { attributes } = server;
    const { data: databases, error, isFetching, refetch } = useAdminServerDatabases(attributes.id);
    const data = useMemo(() => databases?.data ?? [], [databases?.data]);
    const columns = useMemo(
        () =>
            [
                {
                    id: 'name',
                    header: 'Database',
                    cell: ({ row }) => (
                        <CopyOnClick text={row.original.attributes.name}>
                            <span className={'font-mono text-sm'}>{row.original.attributes.name}</span>
                        </CopyOnClick>
                    ),
                },
                {
                    id: 'endpoint',
                    header: 'Endpoint',
                    cell: ({ row }) => (
                        <CopyOnClick
                            text={`${row.original.attributes.host.address}:${row.original.attributes.host.port}`}
                        >
                            <span className={'text-xs'}>
                                {row.original.attributes.host.address}:{row.original.attributes.host.port}
                            </span>
                        </CopyOnClick>
                    ),
                    meta: { headerClassName: 'hidden md:table-cell', cellClassName: 'hidden md:table-cell' },
                },
                {
                    id: 'username',
                    header: 'Username',
                    cell: ({ row }) => (
                        <CopyOnClick text={row.original.attributes.username}>
                            <span className={'text-xs'}>{row.original.attributes.username}</span>
                        </CopyOnClick>
                    ),
                    meta: { headerClassName: 'hidden lg:table-cell', cellClassName: 'hidden lg:table-cell' },
                },
                {
                    id: 'max',
                    header: 'Max',
                    cell: ({ row }) => (
                        <span className={'text-xs'}>
                            {row.original.attributes.max_connections === null
                                ? 'Unlimited'
                                : row.original.attributes.max_connections}
                        </span>
                    ),
                    meta: {
                        headerClassName: 'hidden xl:table-cell w-20 text-right',
                        cellClassName: 'hidden xl:table-cell w-20 text-right',
                    },
                },
                actionsColumn<AdminServerDatabase>(2, (database) => (
                    <ServerDatabaseActions serverId={attributes.id} database={database} />
                )),
            ] satisfies ColumnDef<AdminServerDatabase>[],
        [attributes.id]
    );
    const table = useReactTable({
        data,
        columns,
        getCoreRowModel: getCoreRowModel(),
        getRowId: (database) => database.attributes.id,
    });

    if (error) {
        return <ServerError message={httpErrorToHuman(error)} onRetry={() => refetch()} />;
    }

    const databaseLimit = attributes.feature_limits.databases ?? 0;
    const canCreate = databaseLimit > 0 && data.length < databaseLimit;
    const usage =
        databaseLimit > 0 && data.length > 0
            ? `${data.length} of ${databaseLimit} databases have been allocated to this server.`
            : null;

    return (
        <div>
            <TitledGreyBox title={'Databases'}>
                {!databases ? (
                    <Spinner size={'large'} centered />
                ) : (
                    <>
                        {(canCreate || usage) && (
                            <ListToolbar summary={usage}>
                                {canCreate && <CreateServerDatabaseButton serverId={attributes.id} />}
                            </ListToolbar>
                        )}
                        <DataTable
                            table={table}
                            isFetching={isFetching}
                            emptyState={
                                <Empty className={emptyCompactClass}>
                                    <EmptyHeader>
                                        <EmptyMedia variant={'icon'}>
                                            <Database />
                                        </EmptyMedia>
                                        <EmptyTitle>No databases</EmptyTitle>
                                        <EmptyDescription>
                                            {databaseLimit > 0
                                                ? "This server doesn't have any databases yet."
                                                : "This server's database limit is 0. Raise it in the build settings to create databases."}
                                        </EmptyDescription>
                                    </EmptyHeader>
                                    <EmptyContent>
                                        {databaseLimit > 0 ? (
                                            <CreateServerDatabaseButton serverId={attributes.id} />
                                        ) : (
                                            <NewLinkButton
                                                to={`/panel/servers/${attributes.id}/build`}
                                                isSecondary
                                                icon={SlidersHorizontal}
                                            >
                                                Build settings
                                            </NewLinkButton>
                                        )}
                                    </EmptyContent>
                                </Empty>
                            }
                        />
                    </>
                )}
            </TitledGreyBox>
        </div>
    );
}

export default function ServerDatabaseTab() {
    const { server } = useServerDetail();

    if (server.attributes.container.installed !== 1) {
        return (
            <ServerError message={'Access to this resource is not allowed due to the current installation state.'} />
        );
    }

    return <ServerDatabaseContent server={server} />;
}
