import { useMemo } from 'react';
import { getCoreRowModel, getSortedRowModel, useReactTable } from '@tanstack/react-table';
import { Database } from 'lucide-react';
import { useCurrentServer } from '@/api/server/queries';
import { useServerDatabases } from '@/api/server/databases/queries';
import Spinner from '@/components/elements/Spinner';
import CreateDatabaseButton from '@/components/server/databases/CreateDatabaseButton';
import ListToolbar from '@/components/elements/ListToolbar';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import { httpErrorToHuman } from '@/api/http';
import { ServerError } from '@/components/elements/ScreenBlock';
import DataTable from '@/components/elements/table/DataTable';
import { databaseColumns } from '@/components/server/databases/DatabaseTable';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { emptyCompactClass } from '@/components/ui/styles';
import { usePermissions } from '@/plugins/usePermissions';

const databaseUsage = (canCreate: boolean, limit: number, count: number): string | null => {
    if (canCreate && limit > 0 && count > 0) {
        return `${count} of ${limit} databases have been allocated to this server.`;
    }

    return null;
};

const DatabasesEmptyState = ({ databaseLimit, canAddDatabase }: { databaseLimit: number; canAddDatabase: boolean }) => (
    <Empty className={emptyCompactClass}>
        <EmptyHeader>
            <EmptyMedia variant='icon'>
                <Database />
            </EmptyMedia>
            <EmptyTitle>No databases</EmptyTitle>
            <EmptyDescription>
                {databaseLimit > 0
                    ? "This server doesn't have any databases yet."
                    : "Databases can't be created because this server's database limit is 0."}
            </EmptyDescription>
        </EmptyHeader>
        {canAddDatabase && (
            <EmptyContent>
                <CreateDatabaseButton />
            </EmptyContent>
        )}
    </Empty>
);

const DatabasesContainer = () => {
    const server = useCurrentServer()!;
    const databaseLimit = server.attributes.feature_limits.databases ?? 0;
    const [canCreate] = usePermissions('database.create');
    const { data: databasesResponse, error, isLoading, refetch } = useServerDatabases(server.attributes.uuid);
    const databases = useMemo(() => databasesResponse?.data ?? [], [databasesResponse?.data]);
    const table = useReactTable({
        data: databases,
        columns: databaseColumns,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
        getRowId: (database) => database.attributes.id,
    });

    if (error && !databasesResponse) {
        return <ServerError message={httpErrorToHuman(error)} onRetry={() => refetch()} />;
    }

    const canAddDatabase = canCreate && databaseLimit > 0 && databases.length < databaseLimit;
    const usage = databaseUsage(canCreate, databaseLimit, databases.length);

    return (
        <ServerContentBlock title='Databases'>
            {!databases.length && isLoading ? (
                <Spinner size='large' centered />
            ) : (
                <>
                    {(usage || canAddDatabase) && (
                        <ListToolbar summary={usage}>{canAddDatabase && <CreateDatabaseButton />}</ListToolbar>
                    )}
                    <DataTable
                        table={table}
                        emptyState={
                            <DatabasesEmptyState databaseLimit={databaseLimit} canAddDatabase={canAddDatabase} />
                        }
                    />
                </>
            )}
        </ServerContentBlock>
    );
};

export default DatabasesContainer;
